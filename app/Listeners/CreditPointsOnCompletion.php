<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Auto-credits loyalty points to the linked reward card when a stamp card
 * is completed.
 *
 * Design Tenets:
 * - **Automatic**: No manual intervention needed
 * - **Transactional**: Uses proper transaction service
 * - **Auditable**: Creates proper transaction record
 */

namespace App\Listeners;

use App\Events\StampCardCompleted;
use App\Models\StampTransaction;
use App\Models\Transaction;
use Carbon\Carbon;

class CreditPointsOnCompletion
{
    /**
     * Handle the event.
     */
    public function handle(StampCardCompleted $event): void
    {
        $card = $event->card;
        $member = $event->member;

        // Only proceed if reward points and reward card are configured
        if (! $card->reward_points || ! $card->reward_card_id) {
            return;
        }

        // Lazy loading is disabled; ensure relations are explicitly loaded.
        $card->loadMissing([
            'rewardCard.partner',
        ]);

        $rewardCard = $card->getRelation('rewardCard');

        if (! $rewardCard) {
            return;
        }

        $rewardPartner = $rewardCard->getRelation('partner');
        if (! $rewardPartner) {
            return;
        }

        // Inherit staff attribution from the source stamp transaction.
        // If a staff member completed the card, the point reward should
        // carry their attribution. System/automated completions keep null.
        $sourceTransaction = $event->transaction;
        $sourceStaffId = $sourceTransaction->staff_id;
        $sourceStaffName = 'System';
        $sourceStaffEmail = null;

        if ($sourceStaffId !== null) {
            // Eagerly loaded or fetch the staff for denormalized snapshot
            $sourceStaff = $sourceTransaction->relationLoaded('staff')
                ? $sourceTransaction->getRelation('staff')
                : \App\Models\Staff::find($sourceStaffId);

            if ($sourceStaff && $sourceStaff->getKey() !== null) {
                $sourceStaffName = $sourceStaff->name;
                $sourceStaffEmail = $sourceStaff->email;
            }
        }

        // Create transaction to credit points
        $expiresAt = Carbon::now('UTC')->addMonths((int) $rewardCard->points_expiration_months);

        $transaction = Transaction::create([
            'member_id' => $member->id,
            'card_id' => $rewardCard->id,
            'staff_id' => $sourceStaffId,
            'points' => $card->reward_points,
            'event' => 'stamp_card_completion',
            'note' => trans('common.stamp_card_completion_points', ['card' => $card->name]),
            'partner_name' => $rewardPartner->name,
            'partner_email' => $rewardPartner->email,
            'staff_name' => $sourceStaffName,
            'staff_email' => $sourceStaffEmail,
            'card_title' => $rewardCard->getTranslations('head'),
            'currency' => $rewardCard->currency,
            'points_per_currency' => $rewardCard->points_per_currency,
            'meta' => [
                'round_points_up' => $rewardCard->meta['round_points_up'] ?? true,
                'source' => 'stamp_card_completion',
                'stamp_card_id' => $card->id,
                'stamp_card_name' => $card->name,
            ],
            'min_points_per_purchase' => $rewardCard->min_points_per_purchase,
            'max_points_per_purchase' => $rewardCard->max_points_per_purchase,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'created_by' => $rewardPartner->id,
            'created_at' => Carbon::now('UTC'),
            'updated_at' => Carbon::now('UTC'),
        ]);

        // Update loyalty card stats
        $rewardCard->number_of_points_issued += $card->reward_points;
        $rewardCard->last_points_issued_at = Carbon::now('UTC');
        $rewardCard->save();

        // Create stamp transaction record for history (links to loyalty transaction)
        StampTransaction::create([
            'stamp_card_id' => $card->id,
            'member_id' => $member->id,
            'staff_id' => $sourceStaffId,
            'stamps' => 0, // No stamp change, this is a points reward
            'stamps_before' => 0,
            'stamps_after' => 0,
            'event' => StampTransaction::EVENT_POINTS_REWARDED,
            'note' => trans('common.stamp_card_completion_points', ['card' => $card->name]),
            'meta' => [
                'points_awarded' => $card->reward_points,
                'reward_card_id' => $rewardCard->id,
                'reward_card_title' => $rewardCard->head,
                'loyalty_transaction_id' => $transaction->id,
            ],
        ]);

        // Log activity
        activity('stamp_completions')
            ->performedOn($rewardCard)
            ->causedBy($member)
            ->event('points_credited')
            ->withProperties([
                'points' => $card->reward_points,
                'stamp_card_id' => $card->id,
                'stamp_card_name' => $card->name,
            ])
            ->log("Points credited from stamp card completion: {$card->name} (+{$card->reward_points} points)");
    }
}
