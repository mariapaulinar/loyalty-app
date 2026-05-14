<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Event fired when a member completes a stamp card (reaches stamps_required).
 * Triggers congratulations notification, webhooks, and celebration UI.
 */

namespace App\Events;

use App\Models\Member;
use App\Models\StampCard;
use App\Models\StampTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StampCardCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  StampCard  $card  The stamp card that was completed
     * @param  Member  $member  The member who completed the card
     * @param  int  $completionCount  Number of times completed in this transaction
     * @param  StampTransaction  $transaction  The transaction record
     */
    public function __construct(
        public StampCard $card,
        public Member $member,
        public int $completionCount,
        public StampTransaction $transaction
    ) {}
}
