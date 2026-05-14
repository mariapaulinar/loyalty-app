<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Updates denormalized counters on stamp_cards table for performance.
 * Maintains real-time statistics without expensive COUNT queries.
 *
 * Design Tenets:
 * - **Performance**: Increments only, no COUNT queries
 * - **Real-time**: Updates immediately after events
 * - **Atomic**: Uses increment() for race condition safety
 * - **Non-Blocking**: Queued to not slow down transactions
 */

namespace App\Listeners;

use App\Events\StampCardCompleted;
use App\Events\StampEarned;
use App\Events\StampRewardRedeemed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateStampCardCounters implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle StampEarned event.
     *
     * Increments total_stamps_issued counter.
     */
    public function handleStampEarned(StampEarned $event): void
    {
        try {
            $event->card->increment('total_stamps_issued', $event->stamps);
        } catch (\Exception $e) {
            Log::error('Failed to update stamp card counter (stamps_issued)', [
                'card_id' => $event->card->id,
                'stamps' => $event->stamps,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle StampCardCompleted event.
     *
     * Increments total_completions counter.
     */
    public function handleStampCardCompleted(StampCardCompleted $event): void
    {
        try {
            $event->card->increment('total_completions', $event->completionCount);
        } catch (\Exception $e) {
            Log::error('Failed to update stamp card counter (completions)', [
                'card_id' => $event->card->id,
                'completions' => $event->completionCount,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle StampRewardRedeemed event.
     *
     * Increments total_redemptions counter.
     */
    public function handleStampRewardRedeemed(StampRewardRedeemed $event): void
    {
        try {
            $event->card->increment('total_redemptions');
        } catch (\Exception $e) {
            Log::error('Failed to update stamp card counter (redemptions)', [
                'card_id' => $event->card->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(): array
    {
        return [
            StampEarned::class => 'handleStampEarned',
            StampCardCompleted::class => 'handleStampCardCompleted',
            StampRewardRedeemed::class => 'handleStampRewardRedeemed',
        ];
    }
}
