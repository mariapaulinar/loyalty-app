<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Event fired when stamps expire due to inactivity.
 * Triggers notification to member and activity logging.
 */

namespace App\Events;

use App\Models\Member;
use App\Models\StampCard;
use App\Models\StampTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StampsExpired
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  StampCard  $card  The stamp card
     * @param  Member  $member  The member whose stamps expired
     * @param  int  $stampsExpired  Number of stamps that expired
     * @param  StampTransaction  $transaction  The transaction record
     */
    public function __construct(
        public StampCard $card,
        public Member $member,
        public int $stampsExpired,
        public StampTransaction $transaction
    ) {}
}
