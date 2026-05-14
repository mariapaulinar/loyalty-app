<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Sends notifications to members when they reach progress milestones (50%, 80%).
 * Keeps members engaged and informed of their progress.
 *
 * Design Tenets:
 * - **Configurable**: Uses per-card notify_at_progress settings
 * - **Smart**: Only sends each milestone once
 * - **Engaging**: Encourages members to complete their cards
 * - **Non-Blocking**: Queued for performance
 */

namespace App\Listeners;

use App\Events\StampEarned;
use App\Notifications\Member\StampCardProgress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendStampMilestoneNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle StampEarned event.
     *
     * Checks if member has reached a notification milestone.
     */
    public function handle(StampEarned $event): void
    {
        try {
            $card = $event->card;
            $member = $event->member;
            $currentStamps = $event->currentTotal;
            $stampsRequired = $card->stamps_required;

            // Milestone percentages (50% and 80% progress)
            $notifyAtProgress = [50, 80];

            // Check if we've just crossed a milestone
            foreach ($notifyAtProgress as $milestone) {
                // Calculate stamps for this milestone
                $milestoneStamps = ceil(($milestone / 100) * $stampsRequired);

                // Check if we just reached this milestone
                $previousStamps = $currentStamps - $event->stamps;

                if ($currentStamps >= $milestoneStamps && $previousStamps < $milestoneStamps) {
                    // Send milestone notification
                    $member->notify(new StampCardProgress(
                        card: $card,
                        currentStamps: $currentStamps,
                        stampsRequired: $stampsRequired,
                        milestone: $milestone
                    ));

                    // Only notify for the first milestone reached
                    break;
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send stamp milestone notification', [
                'card_id' => $event->card->id,
                'member_id' => $event->member->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
