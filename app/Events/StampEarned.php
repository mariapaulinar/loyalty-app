<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Event fired when a member earns stamp(s) on a stamp card.
 * Triggers notifications, webhooks, activity logging, and counter updates.
 */

namespace App\Events;

use App\Models\Member;
use App\Models\StampCard;
use App\Models\StampTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StampEarned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  StampCard  $card  The stamp card
     * @param  Member  $member  The member who earned stamps
     * @param  int  $stamps  Number of stamps earned
     * @param  int  $currentTotal  Current stamps after earning
     * @param  StampTransaction  $transaction  The transaction record
     */
    public function __construct(
        public StampCard $card,
        public Member $member,
        public int $stamps,
        public int $currentTotal,
        public StampTransaction $transaction
    ) {}
}
