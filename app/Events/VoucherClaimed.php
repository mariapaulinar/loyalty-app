<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Voucher Claimed Event
 *
 * Purpose:
 * Fired when a member successfully claims a voucher from a batch via QR code.
 * Triggers email notification with voucher code and details.
 *
 * Design Tenets:
 * - **Instant Feedback**: Member receives email with code immediately
 * - **Complete Details**: Includes code, discount, expiry, usage instructions
 * - **Wallet Link**: Deep link to view voucher in member wallet
 */

namespace App\Events;

use App\Models\Member;
use App\Models\Voucher;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoucherClaimed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Voucher $voucher;

    public Member $member;

    /**
     * Create a new event instance.
     */
    public function __construct(Voucher $voucher, Member $member)
    {
        $this->voucher = $voucher;
        $this->member = $member;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('member.'.$this->member->id),
        ];
    }
}
