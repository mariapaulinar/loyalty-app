<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Listens to voucher events and logs them to the activity log for audit trail
 * and business intelligence purposes.
 *
 * Design Tenets:
 * - **Comprehensive**: Logs all significant voucher events
 * - **Detailed**: Captures all relevant context and data
 * - **Queued**: Runs asynchronously to avoid blocking main request
 * - **Subscribable**: Uses event subscriber pattern for multiple events
 */

namespace App\Listeners;

use App\Events\VoucherCreated;
use App\Events\VoucherExhausted;
use App\Events\VoucherRedeemed;
use App\Events\VoucherVoided;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;

class LogVoucherActivity implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Handle voucher redemption event.
     */
    public function handleVoucherRedeemed(VoucherRedeemed $event): void
    {
        activity('voucher_redemptions')
            ->performedOn($event->voucher)
            ->causedBy($event->member)
            ->event('voucher_redeemed')
            ->withProperties([
                'voucher_id' => $event->voucher->id,
                'voucher_code' => $event->voucher->code,
                'voucher_type' => $event->voucher->type,
                'voucher_value' => $event->voucher->value,
                'member_id' => $event->member->id,
                'member_name' => $event->member->name,
                'member_email' => $event->member->email,
                'discount_amount' => $event->discountAmount,
                'discount_formatted' => '$'.number_format($event->discountAmount / 100, 2),
                'redemption_id' => $event->redemption->id,
                'order_reference' => $event->redemption->order_reference,
                'original_amount' => $event->redemption->original_amount,
                'final_amount' => $event->redemption->final_amount,
                'staff_id' => $event->redemption->staff_id,
                'points_awarded' => $event->redemption->points_awarded,
            ])
            ->log("Voucher redeemed: {$event->voucher->code} by {$event->member->name}");
    }

    /**
     * Handle voucher voided event.
     */
    public function handleVoucherVoided(VoucherVoided $event): void
    {
        activity('voucher_voids')
            ->performedOn($event->voucher)
            ->causedBy($event->staff)
            ->event('voucher_voided')
            ->withProperties([
                'voucher_id' => $event->voucher->id,
                'voucher_code' => $event->voucher->code,
                'redemption_id' => $event->redemption->id,
                'member_id' => $event->redemption->member_id,
                'discount_reversed' => $event->redemption->discount_amount,
                'discount_formatted' => '$'.number_format($event->redemption->discount_amount / 100, 2),
                'reason' => $event->reason,
                'voided_by' => $event->staff?->id,
                'voided_by_name' => $event->staff?->name,
                'points_reversed' => $event->redemption->points_awarded,
            ])
            ->log("Voucher redemption voided: {$event->voucher->code} - {$event->reason}");
    }

    /**
     * Handle voucher exhausted event.
     */
    public function handleVoucherExhausted(VoucherExhausted $event): void
    {
        activity('voucher_exhausted')
            ->performedOn($event->voucher)
            ->event('voucher_exhausted')
            ->withProperties([
                'voucher_id' => $event->voucher->id,
                'voucher_code' => $event->voucher->code,
                'voucher_type' => $event->voucher->type,
                'total_redemptions' => $event->voucher->times_used,
                'total_discount_given' => $event->voucher->total_discount_given,
                'total_discount_formatted' => '$'.number_format($event->voucher->total_discount_given / 100, 2),
                'unique_members' => $event->voucher->unique_members_used,
                'max_uses_total' => $event->voucher->max_uses_total,
            ])
            ->log("Voucher exhausted: {$event->voucher->code} reached maximum uses");
    }

    /**
     * Handle voucher created event.
     */
    public function handleVoucherCreated(VoucherCreated $event): void
    {
        activity('vouchers')
            ->performedOn($event->voucher)
            ->causedBy($event->voucher->creator)
            ->event('voucher_created')
            ->withProperties([
                'voucher_id' => $event->voucher->id,
                'voucher_code' => $event->voucher->code,
                'voucher_name' => $event->voucher->name,
                'voucher_type' => $event->voucher->type,
                'voucher_value' => $event->voucher->value,
                'club_id' => $event->voucher->club_id,
                'source' => $event->voucher->source,
                'is_public' => $event->voucher->is_public,
                'max_uses_total' => $event->voucher->max_uses_total,
                'valid_from' => $event->voucher->valid_from?->toDateTimeString(),
                'valid_until' => $event->voucher->valid_until?->toDateTimeString(),
            ])
            ->log("Voucher created: {$event->voucher->code}");
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            VoucherRedeemed::class => 'handleVoucherRedeemed',
            VoucherVoided::class => 'handleVoucherVoided',
            VoucherExhausted::class => 'handleVoucherExhausted',
            VoucherCreated::class => 'handleVoucherCreated',
        ];
    }
}
