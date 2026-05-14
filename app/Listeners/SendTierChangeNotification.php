<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Sends notifications to members when their tier changes.
 * Respects club settings for notification preferences.
 */

namespace App\Listeners;

use App\Events\MemberTierChanged;
use App\Notifications\Member\TierDowngraded;
use App\Notifications\Member\TierUpgraded;
use App\Services\TierService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTierChangeNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected TierService $tierService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(MemberTierChanged $event): void
    {
        $settings = $this->tierService->getTierSettings($event->club);

        // Skip first assignment notifications (unless it's an upgrade from nothing)
        if ($event->isFirstAssignment()) {
            return;
        }

        if ($event->isUpgrade() && $settings['notify_on_upgrade']) {
            $event->member->notify(new TierUpgraded(
                $event->member,
                $event->previousTier,
                $event->newTier,
                $event->club
            ));
        } elseif ($event->isDowngrade() && $settings['notify_on_downgrade']) {
            $event->member->notify(new TierDowngraded(
                $event->member,
                $event->previousTier,
                $event->newTier,
                $event->club
            ));
        }
    }
}
