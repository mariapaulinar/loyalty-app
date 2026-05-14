<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Logs all stamp-related events to the activity log for audit trails.
 * Provides complete traceability of all stamp operations.
 *
 * Design Tenets:
 * - **Comprehensive**: Logs all stamp events
 * - **Structured**: Consistent property format across events
 * - **Contextual**: Includes all relevant IDs and metadata
 * - **Non-Blocking**: Queued to not slow down transactions
 */

namespace App\Listeners;

use App\Events\MemberEnrolledInStampCard;
use App\Events\MemberUnenrolledFromStampCard;
use App\Events\StampCardCompleted;
use App\Events\StampEarned;
use App\Events\StampRewardRedeemed;
use App\Events\StampsAdjusted;
use App\Events\StampsExpired;
use App\Events\StampsVoided;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogStampActivity implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {}

    /**
     * Handle StampEarned event.
     */
    public function handleStampEarned(StampEarned $event): void
    {
        $this->activityLogService->log(
            description: "Member earned {$event->stamps} stamp(s) on {$event->card->name}",
            subject: $event->card,
            event: 'stamp_earned',
            properties: [
                'stamp_card_id' => $event->card->id,
                'stamp_card_name' => $event->card->name,
                'member_id' => $event->member->id,
                'member_name' => $event->member->name,
                'stamps_earned' => $event->stamps,
                'current_total' => $event->currentTotal,
                'stamps_required' => $event->card->stamps_required,
                'transaction_id' => $event->transaction->id,
            ],
            logName: 'stamp_transactions'
        );
    }

    /**
     * Handle StampCardCompleted event.
     */
    public function handleStampCardCompleted(StampCardCompleted $event): void
    {
        $this->activityLogService->log(
            description: "Member completed stamp card: {$event->card->name}",
            subject: $event->card,
            event: 'stamp_card_completed',
            properties: [
                'stamp_card_id' => $event->card->id,
                'stamp_card_name' => $event->card->name,
                'member_id' => $event->member->id,
                'member_name' => $event->member->name,
                'completion_count' => $event->completionCount,
                'reward_title' => $event->card->reward_title,
                'transaction_id' => $event->transaction->id,
            ],
            logName: 'stamp_completions'
        );
    }

    /**
     * Handle StampRewardRedeemed event.
     */
    public function handleStampRewardRedeemed(StampRewardRedeemed $event): void
    {
        $rewardTitle = $event->card->getTranslation('reward_title', app()->getLocale());

        $this->activityLogService->log(
            description: "Member redeemed stamp card reward: {$rewardTitle}",
            subject: $event->card,
            event: 'stamp_reward_redeemed',
            properties: [
                'stamp_card_id' => $event->card->id,
                'stamp_card_name' => $event->card->name,
                'member_id' => $event->member->id,
                'member_name' => $event->member->name,
                'reward_title' => $rewardTitle,
                'reward_value' => $event->card->reward_value,
                'transaction_id' => $event->transaction->id,
            ],
            logName: 'stamp_redemptions'
        );
    }

    /**
     * Handle StampsExpired event.
     */
    public function handleStampsExpired(StampsExpired $event): void
    {
        $this->activityLogService->log(
            description: "Member's stamps expired on {$event->card->name}",
            subject: $event->card,
            event: 'stamps_expired',
            properties: [
                'stamp_card_id' => $event->card->id,
                'stamp_card_name' => $event->card->name,
                'member_id' => $event->member->id,
                'member_name' => $event->member->name,
                'stamps_expired' => $event->stampsExpired,
                'transaction_id' => $event->transaction->id,
            ],
            logName: 'stamp_expirations'
        );
    }

    /**
     * Handle StampsAdjusted event.
     */
    public function handleStampsAdjusted(StampsAdjusted $event): void
    {
        $action = $event->adjustment > 0 ? 'added' : 'removed';
        $amount = abs($event->adjustment);
        $staffInfo = $event->staff ? " by {$event->staff->name}" : ' by system';

        $this->activityLogService->log(
            description: "Manual adjustment: {$amount} stamp(s) {$action}{$staffInfo} - {$event->reason}",
            subject: $event->card,
            event: 'stamps_adjusted',
            properties: [
                'stamp_card_id' => $event->card->id,
                'stamp_card_name' => $event->card->name,
                'member_id' => $event->member->id,
                'member_name' => $event->member->name,
                'adjustment' => $event->adjustment,
                'reason' => $event->reason,
                'staff_id' => $event->staff?->id,
                'staff_name' => $event->staff?->name,
                'transaction_id' => $event->transaction->id,
            ],
            logName: 'stamp_adjustments'
        );
    }

    /**
     * Handle StampsVoided event.
     */
    public function handleStampsVoided(StampsVoided $event): void
    {
        $this->activityLogService->log(
            description: "Transaction voided: {$event->reason}",
            subject: $event->card,
            event: 'stamps_voided',
            properties: [
                'stamp_card_id' => $event->card->id,
                'stamp_card_name' => $event->card->name,
                'member_id' => $event->member->id,
                'member_name' => $event->member->name,
                'original_transaction_id' => $event->originalTransaction->id,
                'void_transaction_id' => $event->voidTransaction->id,
                'stamps_reversed' => $event->voidTransaction->stamps,
                'reason' => $event->reason,
            ],
            logName: 'stamp_voids'
        );
    }

    /**
     * Handle MemberEnrolledInStampCard event.
     */
    public function handleMemberEnrolled(MemberEnrolledInStampCard $event): void
    {
        $enrollmentType = $event->wasAutoEnrolled ? 'auto-enrolled' : 'manually enrolled';

        $this->activityLogService->log(
            description: "Member {$enrollmentType} in stamp card: {$event->card->name}",
            subject: $event->card,
            event: 'member_enrolled',
            properties: [
                'stamp_card_id' => $event->card->id,
                'stamp_card_name' => $event->card->name,
                'member_id' => $event->member->id,
                'member_name' => $event->member->name,
                'enrollment_id' => $event->enrollment->id,
                'was_auto_enrolled' => $event->wasAutoEnrolled,
                'enrolled_at' => $event->enrollment->enrolled_at?->toIso8601String(),
            ],
            logName: 'stamp_enrollments'
        );
    }

    /**
     * Handle MemberUnenrolledFromStampCard event.
     */
    public function handleMemberUnenrolled(MemberUnenrolledFromStampCard $event): void
    {
        $this->activityLogService->log(
            description: "Member unenrolled from stamp card: {$event->card->name} - {$event->reason}",
            subject: $event->card,
            event: 'member_unenrolled',
            properties: [
                'stamp_card_id' => $event->card->id,
                'stamp_card_name' => $event->card->name,
                'member_id' => $event->member->id,
                'member_name' => $event->member->name,
                'stamps_lost' => $event->stampsLost,
                'pending_rewards_lost' => $event->pendingRewardsLost,
                'reason' => $event->reason,
            ],
            logName: 'stamp_unenrollments'
        );
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(): array
    {
        return [
            StampEarned::class => 'handleStampEarned',
            StampCardCompleted::class => 'handleStampCardCompleted',
            StampRewardRedeemed::class => 'handleStampRewardRedeemed',
            StampsExpired::class => 'handleStampsExpired',
            StampsAdjusted::class => 'handleStampsAdjusted',
            StampsVoided::class => 'handleStampsVoided',
            MemberEnrolledInStampCard::class => 'handleMemberEnrolled',
            MemberUnenrolledFromStampCard::class => 'handleMemberUnenrolled',
        ];
    }
}
