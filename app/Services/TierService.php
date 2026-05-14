<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Central orchestrator for all tier operations including evaluation,
 * assignment, multiplier calculations, and default tier creation.
 *
 * Design Tenets:
 * - **Transactional**: Database operations wrapped in transactions
 * - **Event-driven**: Emits events for tier changes
 * - **Configurable**: Respects club-level tier settings
 */

namespace App\Services;

use App\Events\MemberTierChanged;
use App\Models\Club;
use App\Models\Member;
use App\Models\MemberTier;
use App\Models\Tier;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TierService
{
    /**
     * Get tier settings for a club from meta or defaults.
     *
     * @return array{enabled: bool, evaluation_mode: string, include_pending_points: bool, downgrade_enabled: bool, downgrade_period_days: int, show_progress_to_members: bool, notify_on_upgrade: bool, notify_on_downgrade: bool}
     */
    public function getTierSettings(Club $club): array
    {
        $meta = $club->meta ?? [];
        $tierSettings = $meta['tiers'] ?? [];

        return [
            'enabled' => (bool) ($tierSettings['enabled'] ?? true),
            'evaluation_mode' => (string) ($tierSettings['evaluation_mode'] ?? 'any'),
            'include_pending_points' => (bool) ($tierSettings['include_pending_points'] ?? false),
            'downgrade_enabled' => (bool) ($tierSettings['downgrade_enabled'] ?? false),
            'downgrade_period_days' => (int) ($tierSettings['downgrade_period_days'] ?? 365),
            'show_progress_to_members' => (bool) ($tierSettings['show_progress_to_members'] ?? true),
            'notify_on_upgrade' => (bool) ($tierSettings['notify_on_upgrade'] ?? true),
            'notify_on_downgrade' => (bool) ($tierSettings['notify_on_downgrade'] ?? true),
        ];
    }

    /**
     * Save tier settings for a club.
     *
     * @param  array<string, mixed>  $settings
     */
    public function saveTierSettings(Club $club, array $settings): Club
    {
        $meta = $club->meta ?? [];
        $meta['tiers'] = array_merge($meta['tiers'] ?? [], $settings);
        $club->meta = $meta;
        $club->save();

        return $club;
    }

    /**
     * Get qualifying stats for a member in a club.
     *
     * @return array{lifetime_points: int, lifetime_spend: int, transaction_count: int}
     */
    public function getQualifyingStats(Member $member, Club $club): array
    {
        // Get all cards in this club
        $cardIds = $club->cards()->pluck('id');

        // Sum lifetime points (only positive point transactions)
        $lifetimePoints = (int) Transaction::where('member_id', $member->id)
            ->whereIn('card_id', $cardIds)
            ->where('points', '>', 0)
            ->sum('points');

        // Sum lifetime spend
        $lifetimeSpend = (int) Transaction::where('member_id', $member->id)
            ->whereIn('card_id', $cardIds)
            ->whereNotNull('purchase_amount')
            ->sum('purchase_amount');

        // Count transactions (purchase transactions only)
        $transactionCount = (int) Transaction::where('member_id', $member->id)
            ->whereIn('card_id', $cardIds)
            ->whereIn('event', ['staff_credited_points_for_purchase', 'staff_credited_points', 'initial_bonus_points'])
            ->count();

        return [
            'lifetime_points' => $lifetimePoints,
            'lifetime_spend' => $lifetimeSpend,
            'transaction_count' => $transactionCount,
        ];
    }

    /**
     * Evaluate and potentially update a member's tier for a club.
     *
     * @param  bool  $forceDowngrade  When true, bypass the downgrade_enabled guard.
     *                                Use this when correcting data (e.g. after deleting a transaction).
     */
    public function evaluateMemberTier(Member $member, Club $club, bool $forceDowngrade = false): ?MemberTier
    {
        $settings = $this->getTierSettings($club);

        // Skip if tiers are disabled
        if (! $settings['enabled']) {
            return null;
        }

        // Get qualifying stats
        $stats = $this->getQualifyingStats($member, $club);

        // Get all active tiers for the club, ordered by level descending (highest first)
        $tiers = Tier::where('club_id', $club->id)
            ->where('is_active', true)
            ->orderBy('level', 'desc')
            ->get();

        if ($tiers->isEmpty()) {
            // No active tiers — deactivate any existing assignment
            MemberTier::where('member_id', $member->id)
                ->where('club_id', $club->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            return null;
        }

        // Find the highest tier the member qualifies for
        $qualifiedTier = null;
        foreach ($tiers as $tier) {
            if ($tier->memberQualifies(
                $stats['lifetime_points'],
                $stats['lifetime_spend'],
                $stats['transaction_count'],
                $settings['evaluation_mode']
            )) {
                $qualifiedTier = $tier;
                break; // Found the highest qualifying tier
            }
        }

        // Fall back to lowest level tier (the default) if no qualification
        if ($qualifiedTier === null) {
            $qualifiedTier = $tiers->sortBy('level')->first();
        }

        if ($qualifiedTier === null) {
            return null;
        }

        // Get current member tier for this club
        $currentMemberTier = MemberTier::where('member_id', $member->id)
            ->where('club_id', $club->id)
            ->where('is_active', true)
            ->first();

        // If member has no tier or qualifies for a different tier, assign it
        if ($currentMemberTier === null) {
            return $this->assignTier($member, $qualifiedTier, $club, $stats);
        }

        // Check if tier needs to change
        if ($currentMemberTier->tier_id !== $qualifiedTier->id) {
            $isUpgrade = $qualifiedTier->level > $currentMemberTier->tier->level;

            // Only allow downgrade if enabled in settings or forced (e.g. transaction deletion)
            if (! $isUpgrade && ! $forceDowngrade && ! $settings['downgrade_enabled']) {
                return $currentMemberTier;
            }

            return $this->assignTier($member, $qualifiedTier, $club, $stats, $currentMemberTier->tier);
        }

        // Update qualifying stats even if tier didn't change
        $currentMemberTier->update([
            'qualifying_points' => $stats['lifetime_points'],
            'qualifying_spend' => $stats['lifetime_spend'],
            'qualifying_transactions' => $stats['transaction_count'],
        ]);

        return $currentMemberTier;
    }

    /**
     * Directly assign a tier to a member.
     *
     * @param  array{lifetime_points: int, lifetime_spend: int, transaction_count: int}|null  $stats
     */
    public function assignTier(
        Member $member,
        Tier $tier,
        Club $club,
        ?array $stats = null,
        ?Tier $previousTier = null
    ): MemberTier {
        // Get stats if not provided
        if ($stats === null) {
            $stats = $this->getQualifyingStats($member, $club);
        }

        // Get the previous tier if we have an existing assignment
        $existingMemberTier = MemberTier::where('member_id', $member->id)
            ->where('club_id', $club->id)
            ->where('is_active', true)
            ->first();

        if ($existingMemberTier !== null && $previousTier === null) {
            $previousTier = $existingMemberTier->tier;
        }

        $memberTier = DB::transaction(function () use ($member, $tier, $club, $stats, $previousTier, $existingMemberTier) {
            // Deactivate any existing tier assignment
            if ($existingMemberTier !== null) {
                $existingMemberTier->update(['is_active' => false]);
            }

            // Create new tier assignment
            return MemberTier::create([
                'member_id' => $member->id,
                'tier_id' => $tier->id,
                'club_id' => $club->id,
                'achieved_at' => Carbon::now(),
                'qualifying_points' => $stats['lifetime_points'],
                'qualifying_spend' => $stats['lifetime_spend'],
                'qualifying_transactions' => $stats['transaction_count'],
                'previous_tier_id' => $previousTier?->id,
                'is_active' => true,
            ]);
        });

        // Dispatch event
        event(new MemberTierChanged($member, $previousTier, $tier, $club));

        return $memberTier;
    }

    /**
     * Calculate points with tier multiplier applied.
     */
    public function calculateMultipliedPoints(int $basePoints, Member $member, Club $club): int
    {
        $settings = $this->getTierSettings($club);

        if (! $settings['enabled']) {
            return $basePoints;
        }

        $tier = $member->getTierForClub($club);

        if ($tier === null) {
            return $basePoints;
        }

        return $tier->applyMultiplier($basePoints);
    }

    /**
     * Get the points multiplier for a member in a club.
     */
    public function getMultiplierForMember(Member $member, Club $club): float
    {
        $settings = $this->getTierSettings($club);

        if (! $settings['enabled']) {
            return 1.00;
        }

        $tier = $member->getTierForClub($club);

        return $tier ? (float) $tier->points_multiplier : 1.00;
    }

    /**
     * Get progress information for a member towards tiers in a club.
     *
     * @return array{current_tier: Tier|null, next_tier: Tier|null, progress: array, stats: array}
     */
    public function getMemberProgress(Member $member, Club $club): array
    {
        $currentTier = $member->getTierForClub($club);
        $nextTier = $currentTier?->getNextTier();
        $stats = $this->getQualifyingStats($member, $club);

        $progress = [];
        if ($nextTier !== null) {
            $progress = $nextTier->getProgressFor(
                $stats['lifetime_points'],
                $stats['lifetime_spend'],
                $stats['transaction_count']
            );
        }

        return [
            'current_tier' => $currentTier,
            'next_tier' => $nextTier,
            'progress' => $progress,
            'stats' => $stats,
        ];
    }

    /**
     * Recalculate tiers for all members in a club.
     *
     * @return int Number of members recalculated
     */
    public function recalculateAllMembers(Club $club): int
    {
        $cardIds = $club->cards()->pluck('id');

        // Get all unique member IDs who have transactions with this club's cards
        $memberIds = Transaction::whereIn('card_id', $cardIds)
            ->distinct()
            ->pluck('member_id');

        $count = 0;
        foreach ($memberIds as $memberId) {
            $member = Member::find($memberId);
            if ($member) {
                $this->evaluateMemberTier($member, $club);
                $count++;
            }
        }

        Log::info('Recalculated tiers for club', [
            'club_id' => $club->id,
            'members_processed' => $count,
        ]);

        return $count;
    }

    /**
     * Get or assign a tier for a member in a club.
     *
     * Returns null if the club has no tiers configured (tiers are optional).
     * Returns existing tier if member already has one.
     * Assigns the default tier if member has none but club has tiers.
     */
    public function ensureMemberHasTier(Member $member, Club $club): ?MemberTier
    {
        // Skip if tiers are disabled for this club
        $settings = $this->getTierSettings($club);
        if (! $settings['enabled']) {
            return null;
        }

        $existingTier = MemberTier::where('member_id', $member->id)
            ->where('club_id', $club->id)
            ->where('is_active', true)
            ->first();

        if ($existingTier !== null) {
            return $existingTier;
        }

        // Get default tier for the club - if none exists, tiers are not configured
        $defaultTier = $club->getDefaultTier();

        if ($defaultTier === null) {
            // Club has no tiers configured - this is valid, tiers are optional
            return null;
        }

        return $this->assignTier($member, $defaultTier, $club);
    }

    /**
     * Get tier distribution for a club (for analytics).
     *
     * @return array<string, int>
     */
    public function getTierDistribution(Club $club): array
    {
        return MemberTier::where('club_id', $club->id)
            ->where('is_active', true)
            ->selectRaw('tier_id, COUNT(*) as count')
            ->groupBy('tier_id')
            ->pluck('count', 'tier_id')
            ->toArray();
    }

    /**
     * Check if a tier can be safely deleted.
     */
    public function canDeleteTier(Tier $tier): bool
    {
        // Cannot delete if undeletable
        if ($tier->is_undeletable) {
            return false;
        }

        // Cannot delete if it's the lowest level tier (the default)
        if ($tier->isLowestLevelInClub()) {
            $otherTierExists = Tier::where('club_id', $tier->club_id)
                ->where('id', '!=', $tier->id)
                ->where('is_active', true)
                ->exists();

            if (! $otherTierExists) {
                return false;
            }
        }

        // Cannot delete if members are assigned (they need to be reassigned first)
        $memberCount = MemberTier::where('tier_id', $tier->id)
            ->where('is_active', true)
            ->count();

        return $memberCount === 0;
    }

    /**
     * Get the reasons why a tier cannot be deleted.
     *
     * @return array<string>
     */
    public function getDeleteBlockReasons(Tier $tier): array
    {
        $reasons = [];

        if ($tier->is_undeletable) {
            $reasons[] = trans('common.tier_is_protected');
        }

        if ($tier->isLowestLevelInClub()) {
            $otherTierExists = Tier::where('club_id', $tier->club_id)
                ->where('id', '!=', $tier->id)
                ->where('is_active', true)
                ->exists();

            if (! $otherTierExists) {
                $reasons[] = trans('common.tier_is_only_default');
            }
        }

        $memberCount = MemberTier::where('tier_id', $tier->id)
            ->where('is_active', true)
            ->count();

        if ($memberCount > 0) {
            $reasons[] = trans('common.tier_has_members', ['count' => $memberCount]);
        }

        return $reasons;
    }
}
