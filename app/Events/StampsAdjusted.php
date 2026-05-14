<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Event: Stamps Manually Adjusted
 *
 * Fired when staff/admin manually adjusts a member's stamp count.
 * Critical for audit trail of manual corrections, bonuses, or penalties.
 */

namespace App\Events;

use App\Models\Member;
use App\Models\Staff;
use App\Models\StampCard;
use App\Models\StampTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StampsAdjusted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public StampCard $card,
        public Member $member,
        public int $adjustment,
        public string $reason,
        public StampTransaction $transaction,
        public ?Staff $staff = null
    ) {}
}
