<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Event dispatched when a member's tier changes (upgrade or downgrade).
 * Triggers notifications, webhooks, and activity logging.
 */

namespace App\Events;

use App\Models\Club;
use App\Models\Member;
use App\Models\Tier;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberTierChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Member $member,
        public ?Tier $previousTier,
        public Tier $newTier,
        public Club $club
    ) {}

    /**
     * Check if this is an upgrade.
     */
    public function isUpgrade(): bool
    {
        if ($this->previousTier === null) {
            return false;
        }

        return $this->newTier->level > $this->previousTier->level;
    }

    /**
     * Check if this is a downgrade.
     */
    public function isDowngrade(): bool
    {
        if ($this->previousTier === null) {
            return false;
        }

        return $this->newTier->level < $this->previousTier->level;
    }

    /**
     * Check if this is a first tier assignment (no previous tier).
     */
    public function isFirstAssignment(): bool
    {
        return $this->previousTier === null;
    }
}
