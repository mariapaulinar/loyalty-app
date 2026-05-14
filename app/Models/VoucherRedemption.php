<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Represents a single voucher redemption - the complete audit trail of a voucher
 * being used by a member. This is an immutable record for perfect historical
 * reconstruction and fraud detection.
 *
 * Design Tenets:
 * - **Immutable**: Redemptions are never updated, only created (or voided with new status)
 * - **Complete Audit**: Stores all context (member, staff, order, discount given)
 * - **Traceable**: Links to staff, member, voucher for complete context
 * - **Reversible**: Supports voiding with reason tracking
 *
 * Redemption Statuses:
 * - applied: Discount applied, order pending
 * - completed: Order completed with discount
 * - voided: Redemption cancelled/refunded
 * - expired: Redemption expired (if time-limited)
 */

namespace App\Models;

use App\Traits\HasSchemaAccessors;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Class VoucherRedemption
 *
 * Represents a voucher redemption transaction.
 */
class VoucherRedemption extends Model implements HasMedia
{
    use HasFactory;
    use HasSchemaAccessors;
    use HasUuids;
    use InteractsWithMedia;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'voucher_redemptions';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'discount_amount' => 'integer',
        'original_amount' => 'integer',
        'final_amount' => 'integer',
        'points_awarded' => 'integer',
        'redeemed_at' => 'datetime',
        'voided_at' => 'datetime',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Allow mass assignment of all fields.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * Status constants for type-safe code.
     */
    public const STATUS_APPLIED = 'applied';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_VOIDED = 'voided';

    public const STATUS_EXPIRED = 'expired';

    // ═════════════════════════════════════════════════════════════════════════
    // MEDIA LIBRARY
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Register media collections for receipt photos.
     */
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('image')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                // Conversion: sm (thumbnail for history lists)
                $this
                    ->addMediaConversion('sm')
                    ->fit(Fit::Max, 80, 80)
                    ->keepOriginalImageFormat();

                // Conversion: md (full view in modals)
                $this
                    ->addMediaConversion('md')
                    ->fit(Fit::Max, 800, 800)
                    ->keepOriginalImageFormat();
            });
    }

    /**
     * Retrieve the value of an attribute or a dynamically generated image URL.
     */
    public function __get($key)
    {
        $collectionNames = ['image'];
        foreach ($collectionNames as $collectionName) {
            if (substr($key, 0, strlen($collectionName) + 1) === $collectionName.'-') {
                return $this->getImageUrl($collectionName, substr($key, strlen($collectionName) + 1, strlen($key)));
            }
        }

        return parent::__get($key);
    }

    /**
     * Get the URL of a collection with a specific conversion.
     */
    public function getImageUrl(string $collection, string $conversion = ''): ?string
    {
        if ($this->getFirstMediaUrl($collection) !== '') {
            $media = $this->getMedia($collection);

            return $media[0]->getFullUrl($conversion);
        }

        return null;
    }

    /**
     * Get the image URL attribute.
     */
    public function getImageAttribute(): ?string
    {
        return $this->getImageUrl('image');
    }

    // ═════════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Get the voucher that was redeemed.
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * Get the member who redeemed the voucher.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the staff who processed the redemption (nullable for self-service).
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class)->withDefault([
            'name' => 'Self-Service',
        ]);
    }

    /**
     * Get the points transaction if bonus points were awarded.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the staff who voided this redemption.
     */
    public function voidedByStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'voided_by');
    }

    /**
     * Get the club through the voucher relationship.
     *
     * This is a convenience accessor - the club is accessed via voucher.
     */
    public function getClubAttribute(): ?Club
    {
        return $this->voucher?->club;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Scope to completed redemptions.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to voided redemptions.
     */
    public function scopeVoided(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VOIDED);
    }

    /**
     * Scope to redemptions for a specific voucher.
     */
    public function scopeForVoucher(Builder $query, string|Voucher $voucher): Builder
    {
        $voucherId = $voucher instanceof Voucher ? $voucher->id : $voucher;

        return $query->where('voucher_id', $voucherId);
    }

    /**
     * Scope to redemptions for a specific member.
     */
    public function scopeForMember(Builder $query, string|Member $member): Builder
    {
        $memberId = $member instanceof Member ? $member->id : $member;

        return $query->where('member_id', $memberId);
    }

    /**
     * Scope to recent redemptions (last N days, default 30).
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to redemptions by a specific staff member.
     */
    public function scopeByStaff(Builder $query, string|Staff $staff): Builder
    {
        $staffId = $staff instanceof Staff ? $staff->id : $staff;

        return $query->where('staff_id', $staffId);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Void this redemption.
     *
     * @param  string  $reason  Reason for voiding
     * @param  Staff|null  $staff  Staff who is voiding
     */
    public function void(string $reason, ?Staff $staff = null): void
    {
        $this->update([
            'status' => self::STATUS_VOIDED,
            'voided_at' => now(),
            'voided_by' => $staff?->id,
            'void_reason' => $reason,
        ]);
    }

    /**
     * Mark this redemption as completed.
     */
    public function complete(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Check if this redemption is voided.
     */
    public function getIsVoidedAttribute(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    /**
     * Check if this redemption is completed.
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Get formatted discount amount.
     *
     * Uses the redemption's currency (inherited from voucher at redemption time)
     * for proper localized formatting.
     */
    public function getFormattedDiscountAttribute(): string
    {
        $currency = $this->currency ?? config('default.currency');

        return moneyFormat($this->discount_amount / 100, $currency);
    }

    /**
     * Get formatted original amount.
     *
     * Uses the redemption's currency for proper localized formatting.
     */
    public function getFormattedOriginalAmountAttribute(): ?string
    {
        if ($this->original_amount === null) {
            return null;
        }

        $currency = $this->currency ?? config('default.currency');

        return moneyFormat($this->original_amount / 100, $currency);
    }

    /**
     * Get formatted final amount.
     *
     * Uses the redemption's currency for proper localized formatting.
     */
    public function getFormattedFinalAmountAttribute(): ?string
    {
        if ($this->final_amount === null) {
            return null;
        }

        $currency = $this->currency ?? config('default.currency');

        return moneyFormat($this->final_amount / 100, $currency);
    }

    /**
     * Check if this redemption was processed by staff (vs system).
     */
    public function isProcessedByStaff(): bool
    {
        return $this->staff_id !== null;
    }

    /**
     * Check if this redemption awarded bonus points.
     */
    public function hasPointsAwarded(): bool
    {
        return $this->points_awarded !== null && $this->points_awarded > 0;
    }
}
