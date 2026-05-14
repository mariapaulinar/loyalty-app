<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Observers;

use App\Models\Transaction;
use App\Services\ReferralService;

class TransactionObserver
{
    public function __construct(
        protected ReferralService $referralService
    ) {}

    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        // ─────────────────────────────────────────────────────────────────────
        // INTERACTION TRACKING
        // ─────────────────────────────────────────────────────────────────────
        // Record member's first interaction for ghost member cleanup.
        // Any transaction (earning or spending points) counts as engagement.
        if ($member = $transaction->member) {
            $member->recordInteraction();
        }

        // ─────────────────────────────────────────────────────────────────────
        // REFERRAL COMPLETION CHECK
        // ─────────────────────────────────────────────────────────────────────
        // Guard: Skip if this is a referral reward itself to prevent potential loops/noise
        // preventing recursion is good practice, though service logic handles it too.
        if (str_starts_with($transaction->event, 'referral_')) {
            return;
        }

        // Guard: Only process earning transactions
        if ($transaction->points <= 0) {
            return;
        }

        // Delegate business logic to the service
        $this->referralService->checkAndCompleteOnFirstPurchase($transaction);
    }
}
