<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Sends congratulations notification when member completes a stamp card.
 * Informs members their reward is ready for redemption.
 *
 * Design Tenets:
 * - **Celebratory**: Makes members feel accomplished
 * - **Actionable**: Clear next steps for redemption
 * - **Timely**: Sent immediately upon completion
 * - **Non-Blocking**: Queued for performance
 */

namespace App\Listeners;

use App\Events\StampCardCompleted;
use App\Notifications\Member\StampCardCompleted as StampCardCompletedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendCompletionNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle StampCardCompleted event.
     *
     * Sends congratulations notification to member.
     */
    public function handle(StampCardCompleted $event): void
    {
        try {
            $event->member->notify(new StampCardCompletedNotification(
                card: $event->card,
                completionCount: $event->completionCount
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send stamp card completion notification', [
                'card_id' => $event->card->id,
                'member_id' => $event->member->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
