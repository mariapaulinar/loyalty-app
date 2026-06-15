<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Central orchestrator for all stamp card operations. Handles stamp earning,
 * reward redemption, member enrollment, adjustments, and expiration processing.
 *
 * Design Tenets:
 * - **Transactional**: All operations wrapped in DB transactions for consistency
 * - **Event-Driven**: Fires events for webhooks, notifications, and logging
 * - **Type-Safe**: Strict typing and validation throughout
 * - **Atomic**: Operations are all-or-nothing with proper rollback
 *
 * Usage Example:
 * $result = $stampService->addStamp(
 *     card: $stampCard,
 *     member: $member,
 *     staff: $staff,
 *     stamps: 1,
 *     purchaseAmount: 5.00, // $5.00 (decimal currency units)
 * );
 */

namespace App\Services;

use App\Events\MemberEnrolledInStampCard;
use App\Events\StampCardCompleted;
use App\Events\StampEarned;
use App\Events\StampRewardRedeemed;
use App\Events\StampsAdjusted;
use App\Events\StampsExpired;
use App\Events\StampsVoided;
use App\Models\Club;
use App\Models\Member;
use App\Models\Staff;
use App\Models\StampCard;
use App\Models\StampCardMember;
use App\Models\StampTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StampService
{
    /**
     * Add stamp(s) to a member's card.
     *
     * Handles:
     * - Eligibility validation (daily limits, per-transaction limits)
     * - Auto-enrollment if configured
     * - Completion detection and overflow
     * - Event dispatching
     *
     * @param  StampCard  $card  The stamp card
     * @param  Member  $member  The member earning stamps
     * @param  Staff|null  $staff  Staff processing (null for API/automated)
     * @param  int  $stamps  Number of stamps to add (default 1)
     * @param  float|null  $purchaseAmount  Purchase amount in currency units (optional, e.g., 10.50 for $10.50)
     * @param  UploadedFile|null  $image  Receipt photo (optional)
     * @param  string|null  $note  Staff note (optional)
     * @return array StampResult
     */
    public function addStamp(
        StampCard $card,
        Member $member,
        ?Staff $staff = null,
        int $stamps = 1,
        ?float $purchaseAmount = null,
        ?UploadedFile $image = null,
        ?string $note = null,
        ?string $createdAt = null
    ): array {
        // Validate eligibility
        $eligibility = $this->checkEarningEligibility($card, $member, $purchaseAmount, $staff);

        if (! $eligibility['eligible']) {
            return [
                'success' => false,
                'stamps_added' => 0,
                'current_stamps' => 0,
                'stamps_required' => $card->stamps_required,
                'completed' => false,
                'pending_rewards' => 0,
                'error' => $eligibility['reason'],
                'transaction' => null,
            ];
        }

        // Respect per-transaction limit
        if ($card->max_stamps_per_transaction && $stamps > $card->max_stamps_per_transaction) {
            $stamps = $card->max_stamps_per_transaction;
        }

        // Start database transaction for atomicity
        return DB::transaction(function () use ($card, $member, $staff, $stamps, $purchaseAmount, $image, $note, $createdAt) {
            // Get or create enrollment
            $enrollment = $this->getOrCreateEnrollment($card, $member);

            // Lock enrollment for update to prevent race conditions
            $enrollment = StampCardMember::where('id', $enrollment->id)
                ->lockForUpdate()
                ->first();

            $stampsBefore = $enrollment->current_stamps;

            // Add stamps and handle completion/overflow
            $result = $enrollment->addStamps($stamps);

            $stampsAfter = $enrollment->current_stamps;

            // Create transaction record
            $transactionData = [
                'stamp_card_id' => $card->id,
                'member_id' => $member->id,
                'staff_id' => $staff?->id,
                'stamps' => $stamps,
                'stamps_before' => $stampsBefore,
                'stamps_after' => $stampsAfter,
                'event' => StampTransaction::EVENT_STAMP_EARNED,
                'purchase_amount' => $purchaseAmount,
                'currency' => $card->currency,
                'note' => $note,
            ];

            if ($createdAt !== null) {
                $transactionData['created_at'] = $createdAt;
            }

            $transaction = StampTransaction::create($transactionData);

            // Attach receipt image if provided
            if ($image) {
                $transaction->addMedia($image)->toMediaCollection('image');
            }

            // Fire StampEarned event
            event(new StampEarned(
                card: $card,
                member: $member,
                stamps: $stamps,
                currentTotal: $stampsAfter,
                transaction: $transaction
            ));

            // If card was completed, fire StampCardCompleted event
            if ($result['completed']) {
                event(new StampCardCompleted(
                    card: $card,
                    member: $member,
                    completionCount: $result['completions'],
                    transaction: $transaction
                ));
            }

            // Record member interaction for ghost cleanup
            $member->recordInteraction();

            return [
                'success' => true,
                'stamps_added' => $stamps,
                'current_stamps' => $stampsAfter,
                'stamps_required' => $card->stamps_required,
                'completed' => $result['completed'],
                'pending_rewards' => $enrollment->pending_rewards,
                'error' => null,
                'transaction' => $transaction,
            ];
        });
    }

    /**
     * Redeem a pending reward.
     *
     * Handles:
     * - Validation (has pending reward)
     * - Staff requirement check
     * - Transaction creation
     * - Event dispatching
     *
     * @param  StampCard  $card  The stamp card
     * @param  Member  $member  The member redeeming
     * @param  Staff|null  $staff  Staff processing redemption
     * @param  UploadedFile|null  $image  Receipt photo (optional)
     * @param  string|null  $note  Staff note (optional)
     * @return array RedemptionResult
     */
    public function redeemReward(
        StampCard $card,
        Member $member,
        ?Staff $staff = null,
        ?UploadedFile $image = null,
        ?string $note = null,
        ?string $createdAt = null
    ): array {
        // Check if staff is required for redemption (per-card setting)
        if ($card->require_staff_for_redemption && ! $staff) {
            return [
                'success' => false,
                'reward_title' => null,
                'reward_value' => null,
                'remaining_rewards' => 0,
                'error' => 'Staff confirmation required for redemption',
                'transaction' => null,
            ];
        }

        // Get enrollment
        $enrollment = $this->getMemberProgress($card, $member);

        if (! $enrollment) {
            return [
                'success' => false,
                'reward_title' => null,
                'reward_value' => null,
                'remaining_rewards' => 0,
                'error' => 'Member not enrolled in this stamp card',
                'transaction' => null,
            ];
        }

        if ($enrollment->pending_rewards === 0) {
            return [
                'success' => false,
                'reward_title' => null,
                'reward_value' => null,
                'remaining_rewards' => 0,
                'error' => 'No pending rewards to redeem',
                'transaction' => null,
            ];
        }

        // Start database transaction
        return DB::transaction(function () use ($card, $member, $staff, $image, $note, $enrollment, $createdAt) {
            // Lock enrollment
            $enrollment = StampCardMember::where('id', $enrollment->id)
                ->lockForUpdate()
                ->first();

            $stampsBefore = $enrollment->current_stamps;
            $pendingBefore = $enrollment->pending_rewards;

            // Redeem reward
            $enrollment->redeemReward();

            $stampsAfter = $enrollment->current_stamps;
            $pendingAfter = $enrollment->pending_rewards;

            // Create transaction record
            $transactionData = [
                'stamp_card_id' => $card->id,
                'member_id' => $member->id,
                'staff_id' => $staff?->id,
                'stamps' => 0, // No stamp change for redemption marker
                'stamps_before' => $stampsBefore,
                'stamps_after' => $stampsAfter,
                'event' => StampTransaction::EVENT_REWARD_REDEEMED,
                'note' => $note ?? 'Reward redeemed',
            ];

            if ($createdAt !== null) {
                $transactionData['created_at'] = $createdAt;
            }

            $transaction = StampTransaction::create($transactionData);

            // Attach receipt image if provided
            if ($image) {
                $transaction->addMedia($image)->toMediaCollection('image');
            }

            // Fire StampRewardRedeemed event
            event(new StampRewardRedeemed(
                card: $card,
                member: $member,
                transaction: $transaction
            ));

            // Get reward title (translatable)
            $rewardTitle = $card->getTranslation('reward_title', app()->getLocale());

            return [
                'success' => true,
                'reward_title' => $rewardTitle,
                'reward_value' => $card->reward_value,
                'remaining_rewards' => $pendingAfter,
                'error' => null,
                'transaction' => $transaction,
            ];
        });
    }

    /**
     * Enroll a member in a stamp card.
     *
     * Creates enrollment record if not exists.
     *
     * @param  StampCard  $card  The stamp card
     * @param  Member  $member  The member to enroll
     * @param  bool  $isAutoEnroll  Whether this is an auto-enrollment
     */
    public function enrollMember(StampCard $card, Member $member, bool $isAutoEnroll = false): StampCardMember
    {
        $wasNewlyCreated = false;

        $enrollment = StampCardMember::firstOrCreate(
            [
                'stamp_card_id' => $card->id,
                'member_id' => $member->id,
            ],
            [
                'current_stamps' => 0,
                'lifetime_stamps' => 0,
                'completed_count' => 0,
                'redeemed_count' => 0,
                'pending_rewards' => 0,
                'enrolled_at' => now(),
                'is_active' => true,
            ]
        );

        // If enrollment already existed but was inactive (hidden from My Cards), reactivate it
        if (! $enrollment->wasRecentlyCreated && ! $enrollment->is_active) {
            $enrollment->is_active = true;
            $enrollment->save();
        }

        // Fire event only if this was a new enrollment
        if ($enrollment->wasRecentlyCreated) {
            event(new MemberEnrolledInStampCard(
                card: $card,
                member: $member,
                enrollment: $enrollment,
                wasAutoEnrolled: $isAutoEnroll
            ));
            if ($card->card_type === 'event' && $card->stamp_on_enrollment) {
                    $this->addStamp(
                        card: $card,
                        member: $member,
                        staff: null,
                        stamps: 1,
                        purchaseAmount: null,
                        image: null,
                        note: trans('common.stamp_for_enrollment')
                    );
            }
        }

        return $enrollment;
    }

    /**
     * Get or create enrollment (idempotent).
     *
     * @param  StampCard  $card  The stamp card
     * @param  Member  $member  The member
     */
    public function getOrCreateEnrollment(StampCard $card, Member $member): StampCardMember
    {
        return $this->enrollMember($card, $member);
    }

    /**
     * Get a member's progress on a stamp card.
     *
     * @param  StampCard  $card  The stamp card
     * @param  Member  $member  The member
     */
    public function getMemberProgress(StampCard $card, Member $member): ?StampCardMember
    {
        return StampCardMember::where('stamp_card_id', $card->id)
            ->where('member_id', $member->id)
            ->first();
    }

    /**
     * Manually adjust stamps (staff/admin only).
     *
     * Used for corrections, bonuses, or penalties.
     *
     * @param  StampCard  $card  The stamp card
     * @param  Member  $member  The member
     * @param  int  $adjustment  Positive to add, negative to subtract
     * @param  string  $reason  Reason for adjustment
     * @param  Staff|null  $staff  Staff making adjustment
     */
    public function adjustStamps(
        StampCard $card,
        Member $member,
        int $adjustment,
        string $reason,
        ?Staff $staff = null
    ): StampTransaction {
        return DB::transaction(function () use ($card, $member, $adjustment, $reason, $staff) {
            // Get or create enrollment
            $enrollment = $this->getOrCreateEnrollment($card, $member);

            // Lock enrollment
            $enrollment = StampCardMember::where('id', $enrollment->id)
                ->lockForUpdate()
                ->first();

            $stampsBefore = $enrollment->current_stamps;

            // Adjust stamps
            $enrollment->adjustStamps($adjustment);

            $stampsAfter = $enrollment->current_stamps;

            if ($card->card_type === 'event' && $staff) {
                $staff->loadMissing('club.partner');
            
                $partner = $staff->club?->partner ?? $staff->partner;
            
                $transactionData['meta'] = [
                    'club_id' => $staff->club_id,
                    'club_name' => $staff->club?->name,
                    'partner_avatar_url' => $partner?->getAvatarUrl('small'),
                ];
            }

            // Create transaction record
            $transaction = StampTransaction::create([
                'stamp_card_id' => $card->id,
                'member_id' => $member->id,
                'staff_id' => $staff?->id,
                'stamps' => $adjustment,
                'stamps_before' => $stampsBefore,
                'stamps_after' => $stampsAfter,
                'event' => StampTransaction::EVENT_STAMPS_ADJUSTED,
                'note' => $reason,
            ]);

            // Fire StampsAdjusted event
            event(new StampsAdjusted(
                card: $card,
                member: $member,
                adjustment: $adjustment,
                reason: $reason,
                transaction: $transaction,
                staff: $staff
            ));

            return $transaction;
        });
    }

    /**
     * Void a previous stamp transaction.
     *
     * Creates a negative transaction to reverse the original.
     *
     * @param  StampTransaction  $transaction  The transaction to void
     * @param  string  $reason  Reason for voiding
     * @return StampTransaction The void transaction
     */
    public function voidStamps(StampTransaction $transaction, string $reason): StampTransaction
    {
        return DB::transaction(function () use ($transaction, $reason) {
            // Get enrollment
            $enrollment = StampCardMember::where('stamp_card_id', $transaction->stamp_card_id)
                ->where('member_id', $transaction->member_id)
                ->lockForUpdate()
                ->firstOrFail();

            $stampsBefore = $enrollment->current_stamps;

            // Reverse the stamps
            $reversal = -1 * $transaction->stamps;
            $enrollment->adjustStamps($reversal);

            $stampsAfter = $enrollment->current_stamps;

            // Create void transaction
            $voidTransaction = StampTransaction::create([
                'stamp_card_id' => $transaction->stamp_card_id,
                'member_id' => $transaction->member_id,
                'staff_id' => $transaction->staff_id,
                'stamps' => $reversal,
                'stamps_before' => $stampsBefore,
                'stamps_after' => $stampsAfter,
                'event' => StampTransaction::EVENT_STAMPS_VOIDED,
                'note' => "Void of transaction {$transaction->id}: {$reason}",
                'meta' => [
                    'voided_transaction_id' => $transaction->id,
                ],
            ]);

            // Fire StampsVoided event
            $card = StampCard::find($transaction->stamp_card_id);
            $member = Member::find($transaction->member_id);

            event(new StampsVoided(
                card: $card,
                member: $member,
                originalTransaction: $transaction,
                voidTransaction: $voidTransaction,
                reason: $reason
            ));

            return $voidTransaction;
        });
    }

    /**
     * Process expired stamps (batch job).
     *
     * Finds all enrollments with expired stamps and processes them.
     *
     * @return int Number of expirations processed
     */
    public function processExpiredStamps(): int
    {
        $expiredCount = 0;
        $now = Carbon::now();

        // Find enrollments with expired stamps
        $expiredEnrollments = StampCardMember::where('next_stamp_expires_at', '<=', $now)
            ->where('current_stamps', '>', 0)
            ->where('is_active', true)
            ->with(['stampCard', 'member'])
            ->get();

        foreach ($expiredEnrollments as $enrollment) {
            try {
                DB::transaction(function () use ($enrollment, &$expiredCount) {
                    // Lock enrollment
                    $enrollment = StampCardMember::where('id', $enrollment->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $enrollment || ! $enrollment->checkExpiration()) {
                        return;
                    }

                    $stampsBefore = $enrollment->current_stamps;
                    $stampsExpired = $enrollment->expireStamps();
                    $stampsAfter = $enrollment->current_stamps;

                    // Create expiration transaction
                    $transaction = StampTransaction::create([
                        'stamp_card_id' => $enrollment->stamp_card_id,
                        'member_id' => $enrollment->member_id,
                        'staff_id' => null, // System transaction
                        'stamps' => -1 * $stampsExpired,
                        'stamps_before' => $stampsBefore,
                        'stamps_after' => $stampsAfter,
                        'event' => StampTransaction::EVENT_STAMPS_EXPIRED,
                        'note' => 'Stamps expired after inactivity period',
                    ]);

                    // Fire StampsExpired event
                    event(new StampsExpired(
                        card: $enrollment->stampCard,
                        member: $enrollment->member,
                        stampsExpired: $stampsExpired,
                        transaction: $transaction
                    ));

                    $expiredCount++;
                });
            } catch (\Exception $e) {
                Log::error('Failed to process stamp expiration', [
                    'enrollment_id' => $enrollment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $expiredCount;
    }

    /**
     * Get all stamp cards for a member in a club.
     *
     * Returns enrolled and available cards.
     *
     * @param  Member  $member  The member
     * @param  Club  $club  The club
     * @return Collection<StampCard>
     */
    public function getMemberStampCards(Member $member, Club $club): Collection
    {
        // Get enrolled cards with pivot data
        $enrolledCards = $member->stampCards()
            ->where('stamp_cards.club_id', $club->id)
            ->where('stamp_card_member.is_active', true)
            ->with('media')
            ->get();

        return $enrolledCards;
    }

    /**
     * Get statistics for a stamp card.
     *
     * @param  StampCard  $card  The stamp card
     * @return array Statistics
     */
    public function getCardStatistics(StampCard $card): array
    {
        $enrollmentCount = StampCardMember::where('stamp_card_id', $card->id)
            ->where('is_active', true)
            ->count();

        $avgStamps = StampCardMember::where('stamp_card_id', $card->id)
            ->where('is_active', true)
            ->avg('current_stamps');

        $completionRate = $enrollmentCount > 0
            ? ($card->total_completions / $enrollmentCount) * 100
            : 0;

        $redemptionRate = $card->total_completions > 0
            ? ($card->total_redemptions / $card->total_completions) * 100
            : 0;

        return [
            'enrollment_count' => $enrollmentCount,
            'total_stamps_issued' => $card->total_stamps_issued,
            'total_completions' => $card->total_completions,
            'total_redemptions' => $card->total_redemptions,
            'avg_stamps_per_member' => round((float) ($avgStamps ?? 0), 2),
            'completion_rate' => round($completionRate, 2),
            'redemption_rate' => round($redemptionRate, 2),
        ];
    }

    /**
     * Check if member is eligible to earn stamps.
     *
     * Validates:
     * - Card availability
     * - Daily limits
     * - Minimum purchase amount
     * - Qualifying products (future)
     *
     * @param  StampCard  $card  The stamp card
     * @param  Member  $member  The member
     * @param  float|null  $purchaseAmount  Purchase amount in currency units (e.g., 10.50 for $10.50)
     * @return array EligibilityResult
     */
    public function checkEarningEligibility(
        StampCard $card,
        Member $member,
        ?float $purchaseAmount = null,
        ?Staff $staff = null
    ): array {
        // Card must be available
        if (! $card->isAvailable()) {
            return [
                'eligible' => false,
                'reason' => 'Stamp card is not currently available',
                'stamps_available' => 0,
            ];
        }

        // Check minimum purchase amount
        if ($card->min_purchase_amount && $purchaseAmount < $card->min_purchase_amount) {
            return [
                'eligible' => false,
                'reason' => 'Purchase amount does not meet minimum requirement',
                'stamps_available' => 0,
            ];
        }

        // Check daily limit
        if (! $card->canMemberEarnToday($member)) {
            return [
                'eligible' => false,
                'reason' => 'Daily stamp limit reached',
                'stamps_available' => 0,
            ];
        }

        if ($card->card_type === 'event') {
            if ($staff) {
                if (
                    $staff->club_id &&
                    !$card->clubes()->where('club_id', $staff->club_id)->exists()
                ) {
                    return [
                        'eligible' => false,
                        'reason' => trans('common.staff_not_related_to_club'),
                        'stamps_available' => 0,
                    ];
                } else {
                    $staffClubId = $staff->club_id;
                    // Contar los sellos hechos por staff de ese club para este member y este card
                    // Agregar where para filtrar desde el ultimo last_completed_at de StampCardMember
                    $memberProgress = $card->getMemberProgress($member);
                    $lastCompletedAt = $memberProgress?->last_completed_at;

                    $query = \App\Models\StampTransaction::query()
                        ->where('stamp_card_id', $card->id)
                        ->where('member_id', $member->id)
                        ->where('staff_id', $staff->id)
                        ->whereHas('staff', function ($query) use ($staffClubId) {
                            $query->where('club_id', $staffClubId);
                        })
                        ->where('event', StampTransaction::EVENT_STAMP_EARNED);

                    if ($lastCompletedAt) {
                        $query->where('created_at', '>', $lastCompletedAt);
                    }

                    $countStampsByClub = $query->count();
                   
                    // Comparar contra stamps_required_per_club
                    if ($countStampsByClub >= $card->stamps_required_per_club) {
                        return [
                            'eligible' => false,
                            'reason' => trans('common.stamps_required_per_club_not_reached'),
                            'stamps_available' => 0,
                        ];
                    }
                }
            } elseif($card->stamp_on_enrollment && !$member->stampCards()->where('stamp_card_id', $card->id)->exists()) {
                $stampsAvailable = $card->stamps_per_purchase;
                if ($card->max_stamps_per_transaction && $stampsAvailable > $card->max_stamps_per_transaction) {
                    $stampsAvailable = $card->max_stamps_per_transaction;
                }
                return [
                    'eligible' => true,
                    'reason' => null,
                    'stamps_available' => $stampsAvailable,
                ];
            }
            
        }

        // Calculate stamps available (respecting limits)
        $stampsAvailable = $card->stamps_per_purchase;

        if ($card->max_stamps_per_transaction && $stampsAvailable > $card->max_stamps_per_transaction) {
            $stampsAvailable = $card->max_stamps_per_transaction;
        }

        return [
            'eligible' => true,
            'reason' => null,
            'stamps_available' => $stampsAvailable,
        ];
    }
}
