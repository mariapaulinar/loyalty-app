<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Send Voucher Claimed Notification Listener
 *
 * Purpose:
 * Listens for VoucherClaimed events and sends a beautiful email
 * to the member with their voucher code and details.
 *
 * Design Tenets:
 * - **Immediate**: Queued job for fast response
 * - **Reliable**: Catches and logs failures
 * - **Complete**: Sends all details member needs to redeem
 */

namespace App\Listeners;

use App\Events\VoucherClaimed;
use App\Mail\VoucherClaimedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendVoucherClaimedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(VoucherClaimed $event): void
    {
        try {
            Mail::to($event->member->email)
                ->send(new VoucherClaimedMail($event->voucher, $event->member));

            Log::info('Voucher claimed email sent', [
                'voucher_id' => $event->voucher->id,
                'member_id' => $event->member->id,
                'code' => $event->voucher->code,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send voucher claimed email', [
                'voucher_id' => $event->voucher->id,
                'member_id' => $event->member->id,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger queue retry mechanism
            throw $e;
        }
    }

    /**
     * Determine whether the listener should be queued.
     */
    public function shouldQueue(VoucherClaimed $event): bool
    {
        // Only queue if member has a valid email
        return ! empty($event->member->email);
    }
}
