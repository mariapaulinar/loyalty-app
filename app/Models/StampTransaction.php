<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Represents a single stamp transaction - the complete audit trail of all
 * stamp earning, redemption, adjustment, and expiration events.
 *
 * Design Tenets:
 * - **Immutable**: Transactions are never updated, only created (or voided with new transaction)
 * - **Complete Audit**: Stores before/after state for perfect reconstruction
 * - **Event-Driven**: Each transaction has a specific event type
 * - **Traceable**: Links to staff, member, card for complete context
 *
 * Event Types:
 * - stamp_earned: Normal stamp from qualifying purchase
 * - stamps_bonus: Bonus stamps (promotion, manual award)
 * - stamps_adjusted: Manual adjustment by staff/admin
 * - stamps_expired: Automatic expiration of unused stamps
 * - card_completed: Card filled (triggers reward availability)
 * - reward_redeemed: Member claimed reward
 * - stamps_voided: Reversal (e.g., refund, mistake correction)
 * - points_rewarded: Loyalty points credited from stamp card completion (bridge record)
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
 * Class StampTransaction
 *
 * Represents a stamp transaction (earn, redeem, adjust, expire, void).
 */
class StampTransaction extends Model implements HasMedia
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
    protected $table = 'stamp_transactions';

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
        'stamps' => 'integer',
        'stamps_before' => 'integer',
        'stamps_after' => 'integer',
        'purchase_amount' => 'float',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Allow mass assignment of a model.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * Set the purchase_amount attribute (handles empty strings before float cast).
     */
    public function setPurchaseAmountAttribute($value): void
    {
        $this->attributes['purchase_amount'] = ($value === '' || $value === null) ? null : $value;
    }

    /**
     * Event type constants for type-safe code.
     */
    public const EVENT_STAMP_EARNED = 'stamp_earned';

    public const EVENT_STAMPS_BONUS = 'stamps_bonus';

    public const EVENT_STAMPS_ADJUSTED = 'stamps_adjusted';

    public const EVENT_STAMPS_EXPIRED = 'stamps_expired';

    public const EVENT_CARD_COMPLETED = 'card_completed';

    public const EVENT_REWARD_REDEEMED = 'reward_redeemed';

    public const EVENT_STAMPS_VOIDED = 'stamps_voided';

    public const EVENT_POINTS_REWARDED = 'points_rewarded';

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
     * Get the stamp card this transaction belongs to.
     */
    public function stampCard(): BelongsTo
    {
        return $this->belongsTo(StampCard::class);
    }

    /**
     * Get the member involved in this transaction.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the staff who processed this transaction (nullable for system transactions).
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class)->withDefault([
            'name' => 'System',
        ]);
    }

    // Future: Add location relationship when multi-location support is added
    // public function location(): BelongsTo
    // {
    //     return $this->belongsTo(Location::class);
    // }

    // Future: Add order relationship for e-commerce integration
    // public function order(): BelongsTo
    // {
    //     return $this->belongsTo(Order::class);
    // }

    // ═════════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Scope to earned stamp transactions (positive stamps).
     */
    public function scopeEarned(Builder $query): Builder
    {
        return $query->whereIn('event', [
            self::EVENT_STAMP_EARNED,
            self::EVENT_STAMPS_BONUS,
        ])->where('stamps', '>', 0);
    }

    /**
     * Scope to redeemed stamp transactions.
     */
    public function scopeRedeemed(Builder $query): Builder
    {
        return $query->where('event', self::EVENT_REWARD_REDEEMED);
    }

    /**
     * Scope to transactions for a specific card.
     */
    public function scopeForCard(Builder $query, string|StampCard $card): Builder
    {
        $cardId = $card instanceof StampCard ? $card->id : $card;

        return $query->where('stamp_card_id', $cardId);
    }

    /**
     * Scope to transactions for a specific member.
     */
    public function scopeForMember(Builder $query, string|Member $member): Builder
    {
        $memberId = $member instanceof Member ? $member->id : $member;

        return $query->where('member_id', $memberId);
    }

    /**
     * Scope to transactions by a specific staff member.
     */
    public function scopeByStaff(Builder $query, string|Staff $staff): Builder
    {
        $staffId = $staff instanceof Staff ? $staff->id : $staff;

        return $query->where('staff_id', $staffId);
    }

    /**
     * Scope to recent transactions (last N days, default 30).
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to transactions of a specific event type.
     */
    public function scopeOfEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    /**
     * Scope to transactions within a date range.
     */
    public function scopeDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Check if this is an earning transaction (positive stamps).
     */
    public function getIsEarningAttribute(): bool
    {
        return $this->stamps > 0 && in_array($this->event, [
            self::EVENT_STAMP_EARNED,
            self::EVENT_STAMPS_BONUS,
            self::EVENT_STAMPS_ADJUSTED,
        ]);
    }

    /**
     * Check if this is a redemption transaction.
     */
    public function getIsRedemptionAttribute(): bool
    {
        return $this->event === self::EVENT_REWARD_REDEEMED;
    }

    /**
     * Check if this is an expiration transaction.
     */
    public function getIsExpirationAttribute(): bool
    {
        return $this->event === self::EVENT_STAMPS_EXPIRED;
    }

    /**
     * Check if this is a completion transaction.
     */
    public function getIsCompletionAttribute(): bool
    {
        return $this->event === self::EVENT_CARD_COMPLETED;
    }

    /**
     * Check if this is a void transaction.
     */
    public function getIsVoidAttribute(): bool
    {
        return $this->event === self::EVENT_STAMPS_VOIDED;
    }

    /**
     * Get a human-readable description of the transaction.
     */
    public function getDescriptionAttribute(): string
    {
        return match ($this->event) {
            self::EVENT_STAMP_EARNED => 'Stamp earned from purchase',
            self::EVENT_STAMPS_BONUS => 'Bonus stamps awarded',
            self::EVENT_STAMPS_ADJUSTED => 'Stamps manually adjusted',
            self::EVENT_STAMPS_EXPIRED => 'Stamps expired',
            self::EVENT_CARD_COMPLETED => 'Stamp card completed',
            self::EVENT_REWARD_REDEEMED => 'Reward redeemed',
            self::EVENT_STAMPS_VOIDED => 'Stamps voided',
            self::EVENT_POINTS_REWARDED => 'Points rewarded from completion',
            default => 'Unknown transaction',
        };
    }

    // ═════════════════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Get the net stamp change (stamps in this transaction).
     *
     * Positive for earning, negative for redemption/expiration.
     */
    public function getStampChange(): int
    {
        return $this->stamps;
    }

    /**
     * Check if this transaction was processed by staff (vs system).
     */
    public function isProcessedByStaff(): bool
    {
        return $this->staff_id !== null;
    }

    /**
     * Check if this transaction is related to a purchase.
     */
    public function hasPurchaseAmount(): bool
    {
        return $this->purchase_amount !== null && $this->purchase_amount > 0;
    }
}
