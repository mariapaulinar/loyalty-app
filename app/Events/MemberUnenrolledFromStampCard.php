<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Event: Member Unenrolled from Stamp Card
 *
 * Fired when a member leaves or is removed from a stamp card program.
 * Important for tracking churn and understanding why members leave.
 */

namespace App\Events;

use App\Models\Member;
use App\Models\StampCard;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberUnenrolledFromStampCard
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public StampCard $card,
        public Member $member,
        public int $stampsLost,
        public int $pendingRewardsLost,
        public string $reason = 'Member requested'
    ) {}
}
