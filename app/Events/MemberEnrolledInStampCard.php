<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Event: Member Enrolled in Stamp Card
 *
 * Fired when a member enrolls (or is auto-enrolled) in a stamp card program.
 * Important for tracking program adoption and member engagement.
 */

namespace App\Events;

use App\Models\Member;
use App\Models\StampCard;
use App\Models\StampCardMember;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberEnrolledInStampCard
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public StampCard $card,
        public Member $member,
        public StampCardMember $enrollment,
        public bool $wasAutoEnrolled = false
    ) {}
}
