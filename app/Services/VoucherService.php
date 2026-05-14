<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Central orchestrator for all voucher operations. Handles voucher validation,
 * redemption, voiding, code generation, and batch operations.
 *
 * Design Tenets:
 * - **Transactional**: All operations wrapped in DB transactions for consistency
 * - **Event-Driven**: Fires events for webhooks, notifications, and logging
 * - **Type-Safe**: Strict typing and validation throughout
 * - **Atomic**: Operations are all-or-nothing with proper rollback
 *
 * Usage Example:
 * $result = $voucherService->redeem(
 *     voucher: $voucher,
 *     member: $member,
 *     orderAmount: 5000, // $50.00 in cents
 *     staff: $staff,
 * );
 */

namespace App\Services;

use App\Events\VoucherCreated;
use App\Events\VoucherExhausted;
use App\Events\VoucherRedeemed;
use App\Events\VoucherVoided;
use App\Models\Club;
use App\Models\Member;
use App\Models\Staff;
use App\Models\Transaction;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class VoucherService
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    // ═════════════════════════════════════════════════════════════════════════
    // VALIDATION
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Validate a voucher code for a member.
     *
     * Performs comprehensive eligibility checks:
     * - Code exists and is active
     * - Within validity period
     * - Not exhausted (usage limits)
     * - Member meets targeting criteria
     * - Minimum purchase requirements
     *
     * @param  string  $code  Voucher code (case-insensitive)
     * @param  Member  $member  Member attempting to use voucher
     * @param  string  $clubId  Club ID
     * @param  int|null  $orderAmount  Order amount in cents (for min purchase check)
     * @param  array|null  $orderItems  Order items (for product restrictions, future)
     * @return array{valid: bool, voucher: Voucher|null, error_code: string|null, error_message: string|null, discount_amount: int|null, capped: bool|null, original_amount: int|null, final_amount: int|null}
     */
    public function validate(
        string $code,
        Member $member,
        string $clubId,
        ?int $orderAmount = null,
        ?array $orderItems = null
    ): array {
        $code = strtoupper(trim($code));

        // Find voucher
        $voucher = Voucher::query()
            ->forClub($clubId)
            ->byCode($code)
            ->first();

        if (! $voucher) {
            return $this->validationError('invalid_code', 'Voucher code not found.');
        }

        // Check if active
        if (! $voucher->is_active) {
            return $this->validationError('inactive', 'This voucher is not currently active.');
        }

        // Check validity period
        if ($voucher->is_not_yet_valid) {
            return $this->validationError(
                'not_yet_valid',
                'This voucher is not yet valid. It becomes active on '.$voucher->valid_from->format('M j, Y').'.'
            );
        }

        if ($voucher->is_expired) {
            return $this->validationError(
                'expired',
                'This voucher expired on '.$voucher->valid_until->format('M j, Y').'.'
            );
        }

        // Check total usage limit
        if ($voucher->is_exhausted) {
            return $this->validationError('exhausted', 'This voucher has reached its usage limit.');
        }

        // Check member-specific limit
        $memberRemaining = $voucher->getRemainingUsesForMember($member);
        if ($memberRemaining !== null && $memberRemaining <= 0) {
            return $this->validationError(
                'member_limit_reached',
                'You have already used this voucher the maximum number of times.'
            );
        }

        // Check single use
        if ($voucher->is_single_use && $voucher->getMemberUsageCount($member) > 0) {
            return $this->validationError('single_use', 'This voucher can only be used once.');
        }

        // Check target member
        if ($voucher->target_member_id && $voucher->target_member_id !== $member->id) {
            return $this->validationError('not_for_member', 'This voucher is not available to you.');
        }

        // Check tier restriction
        if ($voucher->target_tiers && count($voucher->target_tiers) > 0) {
            $memberTier = $member->tier_id;
            if (! in_array($memberTier, $voucher->target_tiers)) {
                return $this->validationError(
                    'tier_restricted',
                    'This voucher is only available for specific membership tiers.'
                );
            }
        }

        // Check first order only
        if ($voucher->first_order_only) {
            $hasOrders = $member->voucherRedemptions()
                ->whereHas('voucher', fn ($q) => $q->where('club_id', $clubId))
                ->where('status', '!=', 'voided')
                ->exists();

            if ($hasOrders) {
                return $this->validationError('first_order_only', 'This voucher is only valid for first-time orders.');
            }
        }

        // Check new members only
        if ($voucher->new_members_only) {
            $days = $voucher->new_members_days ?? 30;
            if ($member->created_at->lt(now()->subDays($days))) {
                return $this->validationError(
                    'new_members_only',
                    "This voucher is only available for members who joined within the last {$days} days."
                );
            }
        }

        // Check minimum purchase amount
        if ($voucher->min_purchase_amount && $orderAmount !== null) {
            if ($orderAmount < $voucher->min_purchase_amount) {
                $minFormatted = '$'.number_format($voucher->min_purchase_amount / 100, 2);

                return $this->validationError(
                    'minimum_not_met',
                    "Minimum purchase of {$minFormatted} required.",
                    ['minimum_required' => $voucher->min_purchase_amount]
                );
            }
        }

        // Calculate discount
        $discount = $voucher->calculateDiscount($orderAmount ?? 0);

        return [
            'valid' => true,
            'voucher' => $voucher,
            'error_code' => null,
            'error_message' => null,
            'discount_amount' => $discount['discount_amount'],
            'capped' => $discount['capped'],
            'original_amount' => $discount['original_amount'],
            'final_amount' => $discount['final_amount'],
        ];
    }

    /**
     * Build validation error response.
     *
     * @param  string  $code  Error code
     * @param  string  $message  Human-readable error message
     * @param  array  $extra  Additional data
     */
    protected function validationError(string $code, string $message, array $extra = []): array
    {
        return array_merge([
            'valid' => false,
            'voucher' => null,
            'error_code' => $code,
            'error_message' => $message,
            'discount_amount' => null,
        ], $extra);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // REDEMPTION
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Redeem a voucher for a member.
     *
     * Handles:
     * - Re-validation at redemption time
     * - Race condition prevention (database locking)
     * - Discount calculation and application
     * - Bonus points creation (if type = bonus_points)
     * - Counter updates (denormalized statistics)
     * - Event dispatching
     *
     * @param  Voucher  $voucher  The voucher to redeem
     * @param  Member  $member  Member redeeming the voucher
     * @param  int|null  $orderAmount  Order amount in cents
     * @param  string|null  $orderReference  Human-readable order reference
     * @param  Staff|null  $staff  Staff processing redemption (NULL for self-service)
     * @param  UploadedFile|null  $image  Receipt photo (optional)
     * @param  string|null  $locationId  Physical location ID (future multi-location)
     * @return array{success: bool, redemption: VoucherRedemption|null, discount_amount: int, points_awarded: int, voucher_remaining_uses: int|null, error: string|null}
     */
    public function redeem(
        Voucher $voucher,
        Member $member,
        ?int $orderAmount = null,
        ?string $orderReference = null,
        ?Staff $staff = null,
        ?UploadedFile $image = null,
        ?string $locationId = null
    ): array {
        // Re-validate at redemption time
        $validation = $this->validate(
            $voucher->code,
            $member,
            $voucher->club_id,
            $orderAmount
        );

        if (! $validation['valid']) {
            return [
                'success' => false,
                'redemption' => null,
                'discount_amount' => 0,
                'points_awarded' => 0,
                'error' => $validation['error_message'],
            ];
        }

        return DB::transaction(function () use ($voucher, $member, $orderAmount, $orderReference, $staff, $image, $locationId, $validation) {
            // Lock voucher for update to prevent race conditions
            $voucher = Voucher::lockForUpdate()->find($voucher->id);

            // Double-check exhaustion after lock
            if ($voucher->is_exhausted) {
                return [
                    'success' => false,
                    'redemption' => null,
                    'discount_amount' => 0,
                    'points_awarded' => 0,
                    'error' => 'This voucher has reached its usage limit.',
                ];
            }

            $discountAmount = $validation['discount_amount'];
            $pointsAwarded = 0;
            $transactionId = null;

            // Create points transaction for bonus_points type
            if ($voucher->type === 'bonus_points' && $voucher->points_value > 0) {
                $card = $member->cards()->where('club_id', $voucher->club_id)->first();
                if ($card) {
                    // Get current points balance from transactions
                    $currentBalance = Transaction::where('card_id', $card->id)
                        ->where('member_id', $member->id)
                        ->sum('points');

                    $transaction = Transaction::create([
                        'card_id' => $card->id,
                        'member_id' => $member->id,
                        'staff_id' => $staff?->id,
                        'event' => 'voucher_bonus',
                        'points' => $voucher->points_value,
                        'note' => "Bonus points from voucher: {$voucher->code}",
                        'meta' => [
                            'source' => 'voucher_redemption',
                            'voucher_id' => $voucher->id,
                            'voucher_code' => $voucher->code,
                            'points_before' => $currentBalance,
                            'points_after' => $currentBalance + $voucher->points_value,
                        ],
                    ]);

                    // Auto-follow card when voucher bonus points are awarded
                    if (! $card->members()->where('member_id', $member->id)->exists()) {
                        $card->members()->syncWithoutDetaching([$member->id]);
                    }

                    $transactionId = $transaction->id;
                    $pointsAwarded = $voucher->points_value;
                }
            }

            // Determine currency for redemption record
            // Priority: voucher's explicit currency > partner's currency > club's currency > config default
            $redemptionCurrency = $voucher->currency;
            if (! $redemptionCurrency && $voucher->club) {
                // Try partner's currency first (leading)
                if ($voucher->club->created_by) {
                    $partner = \App\Models\Partner::find($voucher->club->created_by);
                    $redemptionCurrency = $partner?->currency;
                }

                // Fall back to club's currency if partner has none
                if (! $redemptionCurrency) {
                    $redemptionCurrency = $voucher->club->currency;
                }
            }
            $redemptionCurrency = $redemptionCurrency ?? config('default.currency');

            // Create redemption record
            $redemption = VoucherRedemption::create([
                'voucher_id' => $voucher->id,
                'member_id' => $member->id,
                'staff_id' => $staff?->id,
                'location_id' => $locationId,
                'order_reference' => $orderReference,
                'discount_amount' => $discountAmount,
                'original_amount' => $orderAmount,
                'final_amount' => $orderAmount ? max(0, $orderAmount - $discountAmount) : null,
                'currency' => $redemptionCurrency,
                'points_awarded' => $pointsAwarded,
                'transaction_id' => $transactionId,
                'status' => 'completed',
                'redeemed_at' => now(),
            ]);

            // Auto-save voucher to member's wallet when redeemed (if not already saved)
            if (! $member->vouchers()->where('vouchers.id', $voucher->id)->exists()) {
                $member->vouchers()->attach($voucher->id, [
                    'claimed_via' => 'qr_scan',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Attach receipt image if provided
            if ($image) {
                $redemption->addMedia($image)->toMediaCollection('image');
            }

            // Update voucher counters
            $voucher->increment('times_used');
            $voucher->increment('total_discount_given', $discountAmount);

            // Check if this is a new unique member
            $existingMemberRedemptions = VoucherRedemption::where('voucher_id', $voucher->id)
                ->where('member_id', $member->id)
                ->where('id', '!=', $redemption->id)
                ->exists();

            if (! $existingMemberRedemptions) {
                $voucher->increment('unique_members_used');
            }

            // Dispatch events
            event(new VoucherRedeemed($voucher, $member, $redemption, $discountAmount));

            // Check if exhausted after this redemption
            if ($voucher->fresh()->is_exhausted) {
                event(new VoucherExhausted($voucher));
            }

            return [
                'success' => true,
                'redemption' => $redemption,
                'discount_amount' => $discountAmount,
                'points_awarded' => $pointsAwarded,
                'voucher_remaining_uses' => $voucher->fresh()->remaining_uses,
                'error' => null,
            ];
        });
    }

    // ═════════════════════════════════════════════════════════════════════════
    // VOID REDEMPTION
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Void a voucher redemption (refund/cancel).
     *
     * Handles:
     * - Status updates
     * - Counter reversals
     * - Points reversal (if bonus_points type)
     * - Event dispatching
     *
     * @param  VoucherRedemption  $redemption  The redemption to void
     * @param  string  $reason  Reason for voiding
     * @param  Staff|null  $staff  Staff voiding the redemption
     *
     * @throws \Exception If redemption already voided
     */
    public function voidRedemption(
        VoucherRedemption $redemption,
        string $reason,
        ?Staff $staff = null
    ): void {
        if ($redemption->is_voided) {
            throw new \Exception('This redemption has already been voided.');
        }

        DB::transaction(function () use ($redemption, $reason, $staff) {
            // Void the redemption
            $redemption->void($reason, $staff);

            // Decrement voucher counters (with underflow protection for unsigned integers)
            $voucher = $redemption->voucher;

            if ($voucher->times_used > 0) {
                $voucher->decrement('times_used');
            }

            if ($voucher->total_discount_given >= $redemption->discount_amount) {
                $voucher->decrement('total_discount_given', $redemption->discount_amount);
            } else {
                $voucher->update(['total_discount_given' => 0]);
            }

            // If this was the only redemption by this member, decrement unique count
            $otherRedemptions = VoucherRedemption::where('voucher_id', $voucher->id)
                ->where('member_id', $redemption->member_id)
                ->where('id', '!=', $redemption->id)
                ->where('status', '!=', 'voided')
                ->exists();

            if (! $otherRedemptions && $voucher->unique_members_used > 0) {
                $voucher->decrement('unique_members_used');
            }

            // Reverse points if applicable
            if ($redemption->points_awarded > 0 && $redemption->transaction_id) {
                $transaction = Transaction::find($redemption->transaction_id);
                if ($transaction) {
                    $card = $transaction->card;
                    if ($card) {
                        // Get current points balance from transactions
                        $currentBalance = Transaction::where('card_id', $card->id)
                            ->where('member_id', $redemption->member_id)
                            ->sum('points');

                        // Create reversal transaction
                        Transaction::create([
                            'card_id' => $card->id,
                            'member_id' => $redemption->member_id,
                            'staff_id' => $staff?->id,
                            'event' => 'voucher_voided',
                            'points' => -$redemption->points_awarded,
                            'note' => "Voucher redemption voided: {$voucher->code} - {$reason}",
                            'meta' => [
                                'source' => 'voucher_void',
                                'voucher_id' => $voucher->id,
                                'voucher_code' => $voucher->code,
                                'original_redemption_id' => $redemption->id,
                                'points_before' => $currentBalance,
                                'points_after' => max(0, $currentBalance - $redemption->points_awarded),
                            ],
                        ]);
                    }
                }
            }

            // Dispatch event
            event(new VoucherVoided($voucher, $redemption, $reason, $staff));
        });
    }

    // ═════════════════════════════════════════════════════════════════════════
    // CODE GENERATION
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Generate a unique voucher code for a club.
     *
     * Handles collision detection with retry logic.
     *
     * @param  string  $clubId  Club ID
     * @param  int  $length  Code length (default 8)
     * @param  string|null  $prefix  Optional prefix (e.g., "SUMMER")
     * @return string Unique uppercase code
     */
    public function generateUniqueCode(
        string $clubId,
        int $length = 8,
        ?string $prefix = null
    ): string {
        $maxAttempts = 10;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = Voucher::generateCode($length, $prefix);

            $exists = Voucher::where('club_id', $clubId)
                ->where('code', $code)
                ->exists();

            if (! $exists) {
                return $code;
            }
        }

        // Fallback: add timestamp suffix
        return Voucher::generateCode($length, $prefix).now()->format('s');
    }

    /**
     * Generate multiple vouchers in a batch.
     *
     * Handles:
     * - Unique code generation for each voucher
     * - Transactional creation (all or nothing)
     * - Media optimization (only first voucher gets media copies)
     * - Event dispatching
     *
     * Storage Optimization:
     * Instead of copying logo/background 1000x, only the FIRST voucher in a batch
     * gets media. The voucher-card component checks for batch membership and uses
     * the first voucher's media for all vouchers in the batch.
     *
     * @param  Club  $club  Club to create vouchers for
     * @param  array  $voucherConfig  Base voucher configuration
     * @param  int  $quantity  Number of vouchers to generate
     * @param  string|null  $codePrefix  Optional code prefix
     * @param  int  $codeLength  Code length (default 8)
     * @param  Voucher|null  $templateVoucher  Template voucher to copy media from (for first voucher only)
     * @return \Illuminate\Support\Collection<int, Voucher> Generated vouchers
     */
    public function generateBatch(
        Club $club,
        array $voucherConfig,
        int $quantity,
        ?string $codePrefix = null,
        int $codeLength = 8,
        ?Voucher $templateVoucher = null
    ): \Illuminate\Support\Collection {
        $vouchers = collect();

        DB::transaction(function () use ($club, $voucherConfig, $quantity, $codePrefix, $codeLength, $templateVoucher, &$vouchers) {
            for ($i = 0; $i < $quantity; $i++) {
                $code = $this->generateUniqueCode($club->id, $codeLength, $codePrefix);

                $voucher = Voucher::create(array_merge($voucherConfig, [
                    'club_id' => $club->id,
                    'code' => $code,
                    'source' => 'batch',
                ]));

                // Copy media ONLY for the first voucher (storage optimization)
                // Other vouchers in the batch will reference this first voucher's media
                if ($i === 0 && $templateVoucher) {
                    // Copy background image
                    if ($templateVoucher->hasMedia('background')) {
                        $templateVoucher->getFirstMedia('background')
                            ->copy($voucher, 'background');
                    }

                    // Copy logo
                    if ($templateVoucher->hasMedia('logo')) {
                        $templateVoucher->getFirstMedia('logo')
                            ->copy($voucher, 'logo');
                    }
                }

                $vouchers->push($voucher);

                // Dispatch event for each voucher
                event(new VoucherCreated($voucher));
            }
        });

        return $vouchers;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // QUERIES
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Get available vouchers for a member.
     *
     * @param  Member  $member  The member
     * @param  Club  $club  The club
     * @param  int|null  $orderAmount  Order amount for min purchase validation
     * @return Collection<int, Voucher> Available vouchers
     */
    public function getAvailableVouchersForMember(
        Member $member,
        Club $club,
        ?int $orderAmount = null
    ): Collection {
        return Voucher::query()
            ->forClub($club->id)
            ->available()
            ->forMember($member)
            ->where(function ($q) use ($member) {
                $q->where('is_public', true)
                    ->orWhere('target_member_id', $member->id);
            })
            ->get()
            ->filter(function ($voucher) use ($member, $orderAmount) {
                // Additional runtime checks
                if (! $voucher->canBeUsedBy($member)) {
                    return false;
                }

                // Check minimum purchase if order amount provided
                if ($orderAmount !== null && $voucher->min_purchase_amount) {
                    if ($orderAmount < $voucher->min_purchase_amount) {
                        return false;
                    }
                }

                return true;
            });
    }

    /**
     * Get member's redemption history.
     *
     * @param  Member  $member  The member
     * @param  Club|null  $club  Optional club filter
     * @return Collection<int, VoucherRedemption> Redemption history
     */
    public function getMemberRedemptionHistory(
        Member $member,
        ?Club $club = null
    ): Collection {
        $query = VoucherRedemption::where('member_id', $member->id)
            ->with(['voucher', 'staff'])
            ->orderBy('created_at', 'desc');

        if ($club) {
            $query->whereHas('voucher', fn ($q) => $q->where('club_id', $club->id));
        }

        return $query->get();
    }

    /**
     * Get voucher statistics.
     *
     * @param  Voucher  $voucher  The voucher
     * @return array<string, mixed> Statistics
     */
    public function getVoucherStatistics(Voucher $voucher): array
    {
        $redemptions = $voucher->redemptions()
            ->where('status', '!=', 'voided')
            ->get();

        $totalOrders = $redemptions->whereNotNull('original_amount')->sum('original_amount');
        $uniqueMembers = $redemptions->pluck('member_id')->unique()->count();

        return [
            'total_redemptions' => $redemptions->count(),
            'total_discount_given' => $voucher->total_discount_given,
            'unique_members' => $uniqueMembers,
            'average_order_value' => $redemptions->count() > 0
                ? (int) round($totalOrders / $redemptions->count())
                : 0,
            'remaining_uses' => $voucher->remaining_uses,
            'is_exhausted' => $voucher->is_exhausted,
            'days_until_expiry' => $voucher->valid_until
                ? now()->diffInDays($voucher->valid_until, false)
                : null,
        ];
    }

    /**
     * Check for auto-apply vouchers.
     *
     * Finds the best auto-apply voucher for a member and order.
     *
     * @param  Member  $member  The member
     * @param  string  $clubId  Club ID
     * @param  int  $orderAmount  Order amount in cents
     * @return Voucher|null Best auto-apply voucher or null
     */
    public function checkAutoApplyVouchers(
        Member $member,
        string $clubId,
        int $orderAmount
    ): ?Voucher {
        return Voucher::query()
            ->forClub($clubId)
            ->available()
            ->forMember($member)
            ->where('is_auto_apply', true)
            ->get()
            ->filter(fn ($v) => $v->canBeUsedBy($member))
            ->filter(function ($voucher) use ($orderAmount) {
                if ($voucher->min_purchase_amount && $orderAmount < $voucher->min_purchase_amount) {
                    return false;
                }

                return true;
            })
            ->sortByDesc(function ($voucher) use ($orderAmount) {
                // Sort by discount value (best first)
                return $voucher->calculateDiscount($orderAmount)['discount_amount'];
            })
            ->first();
    }
}
