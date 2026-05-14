<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Event: Stamps Transaction Voided
 *
 * Fired when a previous stamp transaction is voided/reversed.
 * Critical for fraud detection and audit compliance.
 */

namespace App\Events;

use App\Models\Member;
use App\Models\StampCard;
use App\Models\StampTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StampsVoided
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public StampCard $card,
        public Member $member,
        public StampTransaction $originalTransaction,
        public StampTransaction $voidTransaction,
        public string $reason
    ) {}
}
