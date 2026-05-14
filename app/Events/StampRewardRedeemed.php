<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Event fired when a member redeems a stamp card reward.
 * Triggers confirmation notification, webhooks, and activity logging.
 */

namespace App\Events;

use App\Models\Member;
use App\Models\StampCard;
use App\Models\StampTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StampRewardRedeemed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  StampCard  $card  The stamp card
     * @param  Member  $member  The member redeeming the reward
     * @param  StampTransaction  $transaction  The transaction record
     */
    public function __construct(
        public StampCard $card,
        public Member $member,
        public StampTransaction $transaction
    ) {}
}
