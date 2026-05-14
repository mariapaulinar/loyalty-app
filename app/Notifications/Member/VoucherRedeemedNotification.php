<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Confirms voucher redemption to member.
 */

namespace App\Notifications\Member;

use App\Models\VoucherRedemption;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VoucherRedeemedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VoucherRedemption $redemption
    ) {}

    public function via(object $notifiable): array
    {
        return ['database']; // Mail optional
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'voucher_redeemed',
            'redemption_id' => $this->redemption->id,
            'voucher_code' => $this->redemption->voucher->code,
            'discount_amount' => $this->redemption->formatted_discount,
            'order_reference' => $this->redemption->order_reference,
        ];
    }
}
