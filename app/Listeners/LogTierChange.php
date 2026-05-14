<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Logs tier changes to the activity log for audit purposes.
 */

namespace App\Listeners;

use App\Events\MemberTierChanged;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogTierChange implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(MemberTierChanged $event): void
    {
        $description = $this->buildDescription($event);
        $eventType = $this->getEventType($event);

        activity('member_tiers')
            ->performedOn($event->member)
            ->event($eventType)
            ->withProperties([
                'member_id' => $event->member->id,
                'member_email' => $event->member->email,
                'club_id' => $event->club->id,
                'club_name' => $event->club->name,
                'new_tier_id' => $event->newTier->id,
                'new_tier_name' => $event->newTier->name,
                'new_tier_level' => $event->newTier->level,
                'new_multiplier' => $event->newTier->points_multiplier,
                'previous_tier_id' => $event->previousTier?->id,
                'previous_tier_name' => $event->previousTier?->name,
                'previous_tier_level' => $event->previousTier?->level,
                'is_upgrade' => $event->isUpgrade(),
                'is_downgrade' => $event->isDowngrade(),
                'is_first_assignment' => $event->isFirstAssignment(),
            ])
            ->log($description);
    }

    /**
     * Determine the event type based on tier change.
     */
    protected function getEventType(MemberTierChanged $event): string
    {
        if ($event->isFirstAssignment()) {
            return 'tier_assigned';
        }

        if ($event->isUpgrade()) {
            return 'tier_upgraded';
        }

        if ($event->isDowngrade()) {
            return 'tier_downgraded';
        }

        return 'tier_changed';
    }

    /**
     * Build a human-readable description of the tier change.
     */
    protected function buildDescription(MemberTierChanged $event): string
    {
        $memberName = $event->member->name ?? $event->member->email;

        if ($event->isFirstAssignment()) {
            return "Member {$memberName} assigned to {$event->newTier->name} tier";
        }

        if ($event->isUpgrade()) {
            return "Member {$memberName} upgraded from {$event->previousTier->name} to {$event->newTier->name}";
        }

        if ($event->isDowngrade()) {
            return "Member {$memberName} downgraded from {$event->previousTier->name} to {$event->newTier->name}";
        }

        return "Member {$memberName} tier changed to {$event->newTier->name}";
    }
}
