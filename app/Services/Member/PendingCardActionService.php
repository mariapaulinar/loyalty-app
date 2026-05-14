<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Handles pending "Add to My Cards" actions for unauthenticated users.
 * When a guest clicks "Add to My Cards", we store the action in session.
 * After successful login/registration, the action is executed automatically.
 *
 * This provides a seamless UX where the card is added and the user
 * lands on "My Cards" with a success toast.
 */

namespace App\Services\Member;

use App\Models\Card;
use App\Models\Member;
use App\Models\StampCard;
use App\Models\Voucher;
use App\Services\Card\CardService;
use App\Services\StampService;
use Illuminate\Support\Facades\Log;

class PendingCardActionService
{
    private const SESSION_KEY = 'pending_card_action';

    private const ACTION_TTL_SECONDS = 1800; // 30 minutes

    /**
     * Store a pending "add to my cards" action in session.
     *
     * @param  string  $type  One of: 'card', 'stamp_card', 'voucher'
     * @param  string|int  $entityId  The ID or unique_identifier of the entity
     */
    public function store(string $type, string|int $entityId): void
    {
        session()->put(self::SESSION_KEY, [
            'type' => $type,
            'entity_id' => $entityId,
            'created_at' => now()->timestamp,
        ]);
    }

    /**
     * Check if there's a pending action in session.
     */
    public function hasPending(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    /**
     * Execute pending action for the authenticated member.
     * Returns true if an action was executed, false otherwise.
     *
     * @param  Member  $member  The authenticated member
     */
    public function execute(Member $member): bool
    {
        $action = session()->pull(self::SESSION_KEY);

        if (! $action) {
            return false;
        }

        // Check if action has expired
        $createdAt = $action['created_at'] ?? 0;
        if (now()->timestamp - $createdAt > self::ACTION_TTL_SECONDS) {
            Log::debug('PendingCardAction expired', ['action' => $action]);

            return false;
        }

        $type = $action['type'] ?? null;
        $entityId = $action['entity_id'] ?? null;

        if (! $type || ! $entityId) {
            return false;
        }

        try {
            return match ($type) {
                'card' => $this->executeFollowCard($entityId, $member),
                'stamp_card' => $this->executeEnrollStampCard($entityId, $member),
                'voucher' => $this->executeSaveVoucher($entityId, $member),
                default => false,
            };
        } catch (\Exception $e) {
            Log::error('PendingCardAction execution failed', [
                'action' => $action,
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Follow a loyalty card.
     */
    private function executeFollowCard(string|int $cardId, Member $member): bool
    {
        $cardService = resolve(CardService::class);
        $card = $cardService->findActiveCard($cardId);

        if (! $card) {
            return false;
        }

        // Check if already following
        if ($card->members()->where('members.id', $member->id)->exists()) {
            // Already following - still return true so we show "added" message
            return true;
        }

        // Use direct pivot table insert since CardService::followCard uses auth()
        $card->members()->syncWithoutDetaching([$member->id]);

        return true;
    }

    /**
     * Enroll in a stamp card program.
     */
    private function executeEnrollStampCard(string|int $stampCardId, Member $member): bool
    {
        $stampCard = StampCard::where(function ($query) use ($stampCardId) {
            $query->where('id', $stampCardId)
                ->orWhere('unique_identifier', $stampCardId);
        })
            ->where('is_active', true)
            ->first();

        if (! $stampCard) {
            return false;
        }

        // Check if already enrolled and active
        $enrollment = $stampCard->enrollments()
            ->where('member_id', $member->id)
            ->first();

        if ($enrollment && $enrollment->is_active) {
            // Already enrolled - still return true
            return true;
        }

        $stampService = resolve(StampService::class);
        $stampService->enrollMember($stampCard, $member);

        return true;
    }

    /**
     * Save a voucher to member's wallet.
     */
    private function executeSaveVoucher(string|int $voucherId, Member $member): bool
    {
        $voucher = Voucher::where(function ($query) use ($voucherId) {
            $query->where('id', $voucherId)
                ->orWhere('unique_identifier', $voucherId);
        })
            ->where('is_active', true)
            ->first();

        if (! $voucher) {
            return false;
        }

        // Check if already saved (manual save, not auto-redeemed)
        $alreadySaved = $member->vouchers()
            ->where('vouchers.id', $voucher->id)
            ->wherePivot('claimed_via', null)
            ->exists();

        if ($alreadySaved) {
            // Already saved - still return true
            return true;
        }

        // Attach voucher to member via pivot table
        $member->vouchers()->attach($voucher->id, [
            'claimed_via' => null, // Manual "Add to My Cards" action
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    /**
     * Clear any pending action from session.
     */
    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
