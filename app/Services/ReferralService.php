<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * REFERRAL SERVICE - Campaign-Centric Architecture
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 * Philosophy:
 * -----------
 * This service orchestrates the member-to-member referral system with surgical
 * precision. Unlike traditional club-centric models, we operate on CAMPAIGNS—
 * discrete, configurable reward programs that transcend club boundaries.
 *
 * Key Architectural Decisions:
 * ----------------------------
 * 1. CAMPAIGN-FIRST: Referrals are tied to campaigns (ReferralSettings), not clubs
 * 2. CARD-REWARDS: Points are awarded to specific cards, enabling cross-club rewards
 * 3. MEMBER-CENTRIC: Members can participate in multiple campaigns simultaneously
 * 4. TRANSACTION-DRIVEN: Referral completion is triggered by actual card usage
 *
 * Data Flow:
 * ----------
 * Partner creates Campaign → Member gets Code → Shares with Friend →
 * Friend signs up → Friend makes first purchase → Both receive card points
 *
 * This is Stripe-level API design. Every method is intentional, every edge case
 * is handled with grace, every transaction is atomic.
 *
 * @see \App\Models\ReferralSetting
 * @see \App\Models\MemberReferralCode
 * @see \App\Models\Referral
 */

namespace App\Services;

use App\Models\Card;
use App\Models\Member;
use App\Models\MemberReferralCode;
use App\Models\Referral;
use App\Models\ReferralSetting;
use App\Models\Transaction;
use App\Services\Card\TransactionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferralService
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    // ═════════════════════════════════════════════════════════════════════════
    // PUBLIC API - Campaign Management
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Get or create a referral code for a member in a specific campaign.
     *
     * This is the entry point for members to participate in a referral campaign.
     * Each member gets ONE unique code per campaign, ensuring clean tracking.
     *
     * @param  Member  $member  The member who will share the code
     * @param  ReferralSetting  $campaign  The campaign they're participating in
     * @return MemberReferralCode|null The code, or null if campaign is disabled
     */
    public function getOrCreateCode(Member $member, ReferralSetting $campaign): ?MemberReferralCode
    {
        // Guard: Campaign must be active
        if (! $campaign->is_enabled) {
            return null;
        }

        // Check for existing code
        $existing = MemberReferralCode::where('member_id', $member->id)
            ->where('referral_setting_id', $campaign->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Generate new code
        $code = $this->generateUniqueCode();

        return MemberReferralCode::create([
            'referral_setting_id' => $campaign->id,
            'member_id' => $member->id,
            'code' => $code,
        ]);
    }

    /**
     * Find a referral code by its string representation.
     *
     * This is called when someone clicks a referral link. We need to load
     * the full context: the campaign, the referrer, everything.
     *
     * @param  string  $code  The code from the URL (e.g., "AB12CD34")
     */
    public function findByCode(string $code): ?MemberReferralCode
    {
        return MemberReferralCode::where('code', strtoupper(trim($code)))
            ->with(['referralSetting.referrerCard.club', 'referralSetting.refereeCard', 'member'])
            ->first();
    }

    /**
     * Get all active campaigns for a member.
     *
     * Members see campaigns based on the cards they hold. If they have a card
     * that's used as a reward in any active campaign, they can participate.
     *
     * @return Collection<ReferralSetting>
     */
    public function getActiveCampaignsForMember(Member $member): Collection
    {
        // Get all cards the member has
        $cardIds = $member->cards()->pluck('cards.id');

        // Find campaigns where either reward card is one the member has
        return ReferralSetting::where('is_enabled', true)
            ->where(function ($query) use ($cardIds) {
                $query->whereIn('referrer_card_id', $cardIds)
                    ->orWhereIn('referee_card_id', $cardIds);
            })
            ->with(['referrerCard', 'refereeCard'])
            ->get();
    }

    // ═════════════════════════════════════════════════════════════════════════
    // PUBLIC API - Referral Lifecycle
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Create a pending referral when someone signs up via a referral link.
     *
     * This is called during member registration. The referral starts in "pending"
     * state and will be completed when the referee makes their first purchase.
     *
     * @param  MemberReferralCode  $referralCode  The code that was used
     * @param  Member  $referee  The new member who signed up
     * @return Referral|null The created referral, or null if invalid
     */
    public function createPendingReferral(MemberReferralCode $referralCode, Member $referee): ?Referral
    {
        // Validation: Cannot refer yourself
        if ($referralCode->member_id === $referee->id) {
            return null;
        }

        // Validation: Can only be referred once per campaign
        $exists = Referral::where('referral_code_id', $referralCode->id)
            ->where('referee_id', $referee->id)
            ->exists();

        if ($exists) {
            return null;
        }

        return DB::transaction(function () use ($referralCode, $referee) {
            $referral = Referral::create([
                'referral_code_id' => $referralCode->id,
                'referrer_id' => $referralCode->member_id,
                'referee_id' => $referee->id,
                'status' => 'pending',
                'signed_up_at' => now(),
            ]);

            // Update denormalized stats
            $referralCode->increment('referral_count');

            return $referral;
        });
    }

    /**
     * Check and complete referral on first card transaction.
     *
     * This is called from TransactionObserver. When a member makes their first
     * points-earning transaction on ANY card, we check if they have pending
     * referrals and complete them.
     *
     * Why ANY card? Because members don't "join clubs" - they use cards. The
     * first transaction proves they're an active, engaged member.
     *
     * @param  Transaction  $transaction  The transaction that just occurred
     */
    public function checkAndCompleteOnFirstPurchase(Transaction $transaction): void
    {
        // Guard: Must be a points-earning transaction
        if ($transaction->points <= 0) {
            return;
        }

        $member = $transaction->member;
        if (! $member) {
            return;
        }

        // Find ALL pending referrals for this member
        $pendingReferrals = Referral::where('referee_id', $member->id)
            ->where('status', 'pending')
            ->with('referralCode.referralSetting')
            ->get();

        if ($pendingReferrals->isEmpty()) {
            return;
        }

        // Check if this is the member's FIRST points-earning transaction
        $transactionCount = Transaction::where('member_id', $member->id)
            ->where('points', '>', 0)
            ->count();

        // If more than 1, this isn't their first transaction
        if ($transactionCount > 1) {
            return;
        }

        // Complete all pending referrals (they might have multiple campaigns)
        foreach ($pendingReferrals as $referral) {
            $this->completeReferral($referral);
        }
    }

    /**
     * Complete a referral and award points to both parties.
     *
     * This is the money shot. Both the referrer and referee get points on their
     * respective cards. Transactions are created, stats are updated, emails are
     * sent. Everything happens atomically.
     *
     * @param  Referral  $referral  The referral to complete
     * @return bool Success status
     */
    public function completeReferral(Referral $referral): bool
    {
        // Guard: Must be pending
        if ($referral->status !== 'pending') {
            return false;
        }

        $campaign = $referral->referralCode->referralSetting;

        // Guard: Campaign must still be enabled
        if (! $campaign || ! $campaign->is_enabled) {
            return false;
        }

        return DB::transaction(function () use ($referral, $campaign) {
            // Update referral status
            $referral->status = 'completed';
            $referral->completed_at = now();

            // Award Referrer Points
            if ($campaign->referrer_points > 0) {
                $referee = Member::find($referral->referee_id);
                $note = sprintf(
                    'Referral bonus for inviting %s (%s)',
                    $referee?->name ?? 'Unknown',
                    $referee?->email ?? 'unknown@email.com'
                );
                
                $referrerTransaction = $this->awardPoints(
                    $campaign->referrer_card_id,
                    $referral->referrer_id,
                    $campaign->referrer_points,
                    'referral_bonus',
                    ['referral_id' => $referral->id, 'campaign' => $campaign->name],
                    $note
                );
                $referral->referrer_transaction_id = $referrerTransaction?->id;
            }

            // Award Referee Points
            if ($campaign->referee_points > 0) {
                $referrer = Member::find($referral->referrer_id);
                $note = sprintf(
                    'Welcome bonus from %s (%s)',
                    $referrer?->name ?? 'Unknown',
                    $referrer?->email ?? 'unknown@email.com'
                );
                
                $refereeTransaction = $this->awardPoints(
                    $campaign->referee_card_id,
                    $referral->referee_id,
                    $campaign->referee_points,
                    'referral_welcome_bonus',
                    ['referral_id' => $referral->id, 'campaign' => $campaign->name],
                    $note
                );
                $referral->referee_transaction_id = $refereeTransaction?->id;
            }

            $referral->save();

            // Update denormalized stats
            $referral->referralCode->increment('successful_count');
            if ($referral->referrer_transaction_id) {
                $referral->referralCode->increment('points_earned', $campaign->referrer_points);
            }

            // Send notification emails
            $this->sendCompletionEmails($referral, $campaign);

            return true;
        });
    }

    // ═════════════════════════════════════════════════════════════════════════
    // PUBLIC API - Statistics & Reporting
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Get statistics for a member's participation in a campaign.
     *
     * This powers the member dashboard, showing them how many people they've
     * referred and how many points they've earned.
     */
    public function getMemberStats(Member $member, ReferralSetting $campaign): array
    {
        $code = $this->getOrCreateCode($member, $campaign);

        if (! $code) {
            return [];
        }

        return [
            'code' => $code->code,
            'share_url' => $code->share_url,
            'referral_count' => $code->referral_count,
            'successful_count' => $code->successful_count,
            'points_earned' => $code->points_earned,
        ];
    }

    /**
     * Get recent referrals for a member in a campaign.
     *
     * Shows the member who they've referred and the status of each referral.
     * Privacy-conscious: we only show first names in the view layer.
     *
     * @return Collection<Referral>
     */
    public function getMemberReferrals(Member $member, ReferralSetting $campaign, int $limit = 10): Collection
    {
        $code = MemberReferralCode::where('member_id', $member->id)
            ->where('referral_setting_id', $campaign->id)
            ->first();

        if (! $code) {
            return new Collection;
        }

        return Referral::where('referral_code_id', $code->id)
            ->with('referee')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    // ═════════════════════════════════════════════════════════════════════════
    // PROTECTED HELPERS - The Engine Room
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Generate a unique, human-friendly referral code.
     *
     * Codes are 6 characters, uppercase, avoiding ambiguous characters (O/0, I/1, L).
     * This makes them easy to share verbally and type accurately.
     *
     * Collision handling: Try 10 times, then fall back to 8-char random string.
     *
     * @return string The generated code (e.g., "AB12CD")
     */
    protected function generateUniqueCode(): string
    {
        $attempts = 0;
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // No O, 0, I, 1, L

        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }

            $exists = MemberReferralCode::where('code', $code)->exists();
            $attempts++;
        } while ($exists && $attempts < 10);

        // Fallback for extreme collision cases
        if ($exists) {
            return strtoupper(Str::random(8));
        }

        return $code;
    }

    /**
     * Award points to a member on a specific card.
     *
     * This is the atomic unit of reward distribution. We create a transaction,
     * attach metadata for audit trails, and let the TransactionService handle
     * all the balance updates and tier calculations.
     *
     * @param  string  $cardId  The card to credit
     * @param  string  $memberId  The member receiving points
     * @param  int  $points  Amount of points
     * @param  string  $event  Event type for transaction log
     * @param  array  $meta  Additional metadata
     * @param  string  $note  Optional internal note for staff/partner view
     */
    protected function awardPoints(
        string $cardId,
        string $memberId,
        int $points,
        string $event,
        array $meta = [],
        string $note = 'Referral Reward'
    ): ?Transaction {
        $card = Card::find($cardId);
        $member = Member::find($memberId);

        if (! $card || ! $member) {
            return null;
        }

        // Create the transaction via TransactionService
        $transaction = $this->transactionService->addSystemPoints(
            $member,
            $card,
            $points,
            $event,
            $note
        );

        // Attach metadata for audit trail
        if ($transaction && ! empty($meta)) {
            $existingMeta = $transaction->meta ?? [];
            $transaction->meta = array_merge($existingMeta, $meta);
            $transaction->save();
        }

        return $transaction;
    }

    /**
     * Send completion notification emails to both parties.
     *
     * Referrer gets: "You earned X points!"
     * Referee gets: "Welcome! Here's your bonus!"
     */
    protected function sendCompletionEmails(Referral $referral, ReferralSetting $campaign): void
    {
        // Notify Referrer
        if ($campaign->referrer_points > 0 && $referral->referrer) {
            $referral->referrer->notify(
                new \App\Notifications\Member\ReferralCompletedReferrer(
                    $referral,
                    $campaign->referrer_points,
                    $campaign->referrerCard?->title ?? $campaign->referrerCard?->name ?? 'Loyalty Card',
                    $campaign->referrerCard?->club?->name ?? config('app.name'),
                )
            );
        }

        // Notify Referee
        if ($campaign->referee_points > 0 && $referral->referee) {
            $referral->referee->notify(
                new \App\Notifications\Member\ReferralCompletedReferee(
                    $referral,
                    $campaign->referee_points,
                    $campaign->refereeCard?->title ?? $campaign->refereeCard?->name ?? 'Loyalty Card',
                    $campaign->refereeCard?->club?->name ?? config('app.name'),
                )
            );
        }
    }
}
