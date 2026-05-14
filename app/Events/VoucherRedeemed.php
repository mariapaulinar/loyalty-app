<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Events;

use App\Models\Member;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoucherRedeemed
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Voucher $voucher,
        public Member $member,
        public VoucherRedemption $redemption,
        public int $discountAmount
    ) {}
}
