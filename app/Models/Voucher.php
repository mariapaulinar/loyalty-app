<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Represents a Voucher (discount code/coupon) template in the application.
 * Vouchers provide instant value perception and flexible marketing capabilities
 * for businesses through code-based discounts.
 *
 * Design Tenets:
 * - **Instant Gratification**: Unlike points, vouchers provide immediate value
 * - **Flexible Targeting**: Support complex eligibility rules and member targeting
 * - **Type Variety**: percentage, fixed_amount, free_product, bonus_points (brick & mortar)
 * - **Performance**: Denormalized counters avoid expensive aggregations
 *
 * Usage Example:
 * $voucher = Voucher::create([
 *     'club_id' => $club->id,
 *     'code' => 'SUMMER20',
 *     'name' => 'Summer Sale 2025',
 *     'type' => 'percentage',
 *     'value' => 20, // 20% off
 * ]);
 */

namespace App\Models;

use App\Traits\HasIdentifier;
use App\Traits\HasSchemaAccessors;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Used in boot() for currency auto-inheritance
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * Class Voucher
 *
 * Represents a voucher template that clubs create.
 */
class Voucher extends Model implements HasMedia
{
    use HasFactory;
    use HasIdentifier;
    use HasSchemaAccessors;
    use HasTranslations;
    use HasUuids;
    use InteractsWithMedia;
    use LogsModelActivity;


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vouchers';

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
        'value' => 'integer',
        'points_value' => 'integer',
        'min_purchase_amount' => 'integer',
        'max_discount_amount' => 'integer',
        'max_uses_total' => 'integer',
        'max_uses_per_member' => 'integer',
        'new_members_days' => 'integer',
        'times_used' => 'integer',
        'total_discount_given' => 'integer',
        'unique_members_used' => 'integer',
        'views' => 'integer',
        'first_order_only' => 'boolean',
        'new_members_only' => 'boolean',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'is_visible_by_default' => 'boolean',
        'is_single_use' => 'boolean',
        'is_auto_apply' => 'boolean',
        'stackable' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'last_view' => 'datetime',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
        'excluded_products' => 'array',
        'target_tiers' => 'array',
        'meta' => 'array',

        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Translatable fields.
     *
     * @var array<string>
     */
    public array $translatable = [
        'title',
        'description',
        'free_product_name',
    ];

    /**
     * Allow mass assignment of all fields.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * Boot method to handle empty string conversions for nullable fields.
     * MySQL strict mode requires NULL instead of empty strings for nullable fields.
     *
     * Also handles:
     * - Automatic currency inheritance from partner when creating vouchers
     * - Code uppercase normalization
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($model) {
            $nullableFields = [
                'min_purchase_amount',
                'max_discount_amount',
                'max_uses_total',
                'max_uses_per_member',
                'new_members_days',
                'points_value',
                'currency',
                'free_product_name',
                'target_member_id',
                'source_id',
                'description',
            ];

            // Use raw attributes to avoid MissingAttributeException when column wasn't loaded
            $attributes = $model->getAttributes();
            foreach ($nullableFields as $field) {
                if (array_key_exists($field, $attributes) && $attributes[$field] === '') {
                    $model->{$field} = null;
                }
            }
        });

        // Ensure code is always uppercase
        static::saving(function ($model) {
            $attributes = $model->getAttributes();
            if (array_key_exists('code', $attributes) && $attributes['code']) {
                $model->code = strtoupper($attributes['code']);
            }
        });
    }

    /**
     * Set the valid_from attribute (handles empty strings for MySQL strict mode).
     */
    public function setValidFromAttribute($value): void
    {
        $this->attributes['valid_from'] = ($value === '' || $value === null) ? null : $value;
    }

    /**
     * Set the valid_until attribute (handles empty strings for MySQL strict mode).
     */
    public function setValidUntilAttribute($value): void
    {
        $this->attributes['valid_until'] = ($value === '' || $value === null) ? null : $value;
    }

    /**
     * Mutator for max_discount_amount - converts empty string to null before casting.
     */
    public function setMaxDiscountAmountAttribute($value): void
    {
        $this->attributes['max_discount_amount'] = ($value === '' || $value === null) ? null : $value;
    }

    /**
     * Mutator for points_value - converts empty string to null before casting.
     */
    public function setPointsValueAttribute($value): void
    {
        $this->attributes['points_value'] = ($value === '' || $value === null) ? null : $value;
    }

    /**
     * Mutator for max_uses_total - converts empty string to null before casting.
     */
    public function setMaxUsesTotalAttribute($value): void
    {
        $this->attributes['max_uses_total'] = ($value === '' || $value === null) ? null : $value;
    }

    /**
     * Mutator for max_uses_per_member - converts empty string to null before casting.
     */
    public function setMaxUsesPerMemberAttribute($value): void
    {
        $this->attributes['max_uses_per_member'] = ($value === '' || $value === null) ? null : $value;
    }

    /**
     * Mutator for min_purchase_amount - converts empty string to null before casting.
     */
    public function setMinPurchaseAmountAttribute($value): void
    {
        $this->attributes['min_purchase_amount'] = ($value === '' || $value === null) ? null : $value;
    }

    /**
     * Mutator for new_members_days - converts empty string to null before casting.
     */
    public function setNewMembersDaysAttribute($value): void
    {
        $this->attributes['new_members_days'] = ($value === '' || $value === null) ? null : $value;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Get the club that owns this voucher.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the members who have claimed this voucher.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'member_voucher')
            ->withPivot('claimed_via')
            ->withTimestamps();
    }

    /**
     * Get all redemptions for this voucher.
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class);
    }

    /**
     * Get the specific member this voucher targets (if targeted).
     */
    public function targetMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'target_member_id');
    }

    /**
     * Get the loyalty card for bonus points rewards.
     */
    public function rewardCard(): BelongsTo
    {
        return $this->belongsTo(Card::class, 'reward_card_id');
    }

    /**
     * Get the partner who created this voucher.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'created_by');
    }

    /**
     * Get the batch this voucher belongs to (if batch-generated).
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(VoucherBatch::class, 'batch_id');
    }

    /**
     * Get the representative voucher for this batch (first voucher with media).
     *
     * Purpose:
     * For storage optimization, only the FIRST voucher in a batch gets media
     * (logo/background) copied from the template. This method retrieves that
     * representative voucher so all vouchers in the batch can share the same media.
     *
     * Returns:
     * - The first voucher in the batch (ordered by created_at) if this is a batch voucher
     * - null if this is NOT a batch voucher
     * - The voucher itself if it's the first in the batch
     */
    public function getRepresentativeVoucherForMedia(): ?Voucher
    {
        $batchId = $this->getBatchIdForMedia();

        // Not a batch voucher? No representative needed
        if (! $batchId) {
            return null;
        }

        // Get the first voucher in this batch (has the media)
        $representative = static::where('batch_id', $batchId)
            ->orderBy('created_at', 'asc')
            ->first();

        return $representative;
    }

    /**
     * Get the partner who last updated this voucher.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'updated_by');
    }

    /**
     * Get the partner who deleted this voucher.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'deleted_by');
    }

    // ═════════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Scope to only active vouchers.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only public vouchers.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to available vouchers (active, within validity period, not exhausted).
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses_total')
                    ->orWhereColumn('times_used', '<', 'max_uses_total');
            });
    }

    /**
     * Scope to vouchers for a specific club.
     */
    public function scopeForClub(Builder $query, string|Club $club): Builder
    {
        $clubId = $club instanceof Club ? $club->id : $club;

        return $query->where('club_id', $clubId);
    }

    /**
     * Scope to find voucher by code (case-insensitive).
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', strtoupper($code));
    }

    /**
     * Scope to vouchers available for a specific member.
     */
    public function scopeForMember(Builder $query, Member $member): Builder
    {
        return $query->where(function ($q) use ($member) {
            // No target member restriction OR targets this member
            $q->whereNull('target_member_id')
                ->orWhere('target_member_id', $member->id);
        });
    }

    // ═════════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Check if voucher is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    /**
     * Check if voucher is expiring soon (within 7 days).
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->valid_until
            && ! $this->is_expired
            && now()->addDays(7)->isAfter($this->valid_until);
    }

    /**
     * Check if voucher is not yet valid.
     */
    public function getIsNotYetValidAttribute(): bool
    {
        return $this->valid_from && $this->valid_from->isFuture();
    }

    /**
     * Check if voucher is exhausted (reached max uses).
     */
    public function getIsExhaustedAttribute(): bool
    {
        return $this->max_uses_total !== null && $this->times_used >= $this->max_uses_total;
    }

    /**
     * Get remaining uses (NULL = unlimited).
     */
    public function getRemainingUsesAttribute(): ?int
    {
        if ($this->max_uses_total === null) {
            return null; // Unlimited
        }

        return max(0, $this->max_uses_total - $this->times_used);
    }

    /**
     * Get formatted value string based on voucher type.
     *
     * Uses proper currency formatting via moneyFormat() helper
     * for consistent localized display across the application.
     *
     * Storage Format (minor units × 100):
     * - Percentages: 2000 = 20%, 1500 = 15% (divide by 100 for display)
     * - Fixed amounts: 1500 = $15.00 (divide by 100 for display)
     *
     * The UI input field has minorUnits=100 in VoucherDataDefinition,
     * so user enters 20 (for 20%) and the form stores 2000.
     */
    public function getFormattedValueAttribute(): string
    {
        $currency = $this->currency ?? config('default.currency');

        return match ($this->type) {
            // Percentages stored as minor units: 2000 = 20%, divide by 100 for display
            'percentage' => rtrim(rtrim(number_format($this->value / 100, 2, '.', ''), '0'), '.').'%',
            'fixed_amount' => moneyFormat($this->value / 100, $currency),
            'free_product' => $this->free_product_name ?? trans('common.free_product'),
            'free_shipping' => trans('common.free_shipping'),
            'bonus_points' => trans('common.amount_points', ['points' => number_format((int) $this->points_value)]),
            default => (string) $this->value,
        };
    }

    /**
     * Check if voucher is currently valid (active, within dates, not exhausted).
     */
    public function getIsValidAttribute(): bool
    {
        return $this->is_active
            && ! $this->is_expired
            && ! $this->is_not_yet_valid
            && ! $this->is_exhausted;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Generate a random voucher code.
     *
     * @param  int  $length  Code length (default 8)
     * @param  string|null  $prefix  Optional prefix (e.g., "SUMMER")
     * @return string Uppercase code (e.g., "ABCD1234" or "SUMMER-ABCD1234")
     */
    public static function generateCode(int $length = 8, ?string $prefix = null): string
    {
        // Readable character set (excludes confusing chars like 0, O, I, L, 1)
        $characters = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $prefix ? strtoupper($prefix).'-'.$code : $code;
    }

    /**
     * Get how many times a specific member has used this voucher.
     */
    public function getMemberUsageCount(Member $member): int
    {
        return $this->redemptions()
            ->where('member_id', $member->id)
            ->where('status', '!=', 'voided')
            ->count();
    }

    /**
     * Get remaining uses for a specific member (NULL = unlimited).
     */
    public function getRemainingUsesForMember(Member $member): ?int
    {
        if ($this->max_uses_per_member === null) {
            return null; // Unlimited per member
        }

        $used = $this->getMemberUsageCount($member);

        return max(0, $this->max_uses_per_member - $used);
    }

    /**
     * Quick eligibility check for a member (full validation in VoucherService).
     *
     * @param  Member  $member  The member to check
     * @return bool True if member can potentially use this voucher
     */
    public function canBeUsedBy(Member $member): bool
    {
        // Quick eligibility check (full validation in VoucherService)
        if (! $this->is_valid) {
            return false;
        }

        // Target member check
        if ($this->target_member_id && $this->target_member_id !== $member->id) {
            return false;
        }

        // Member usage limit check
        $remaining = $this->getRemainingUsesForMember($member);
        if ($remaining !== null && $remaining <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount for a given order amount.
     *
     * @param  int  $orderAmount  Order amount in cents
     * @return array{discount_amount: int, capped: bool, original_amount: int, final_amount: int}
     */
    public function calculateDiscount(int $orderAmount): array
    {
        $discount = 0;
        $capped = false;

        switch ($this->type) {
            case 'percentage':
                // Both orderAmount and value are in minor units (cents).
                // value=2000 means 20%. orderAmount=10000 means $100.00.
                // 10000 * (2000 / 10000) = 2000 = $20.00 discount.
                $discount = (int) round($orderAmount * ($this->value / 10000));
                if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
                    $discount = $this->max_discount_amount;
                    $capped = true;
                }
                break;

            case 'fixed_amount':
                $discount = min($this->value, $orderAmount);
                break;

            case 'bonus_points':
            case 'free_product':
                // No monetary discount - handled separately in redemption flow
                $discount = 0;
                break;
        }

        return [
            'discount_amount' => $discount,
            'capped' => $capped,
            'original_amount' => $orderAmount,
            'final_amount' => max(0, $orderAmount - $discount),
        ];
    }

    // ═════════════════════════════════════════════════════════════════════════
    // MEDIA COLLECTIONS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Register media collections for the model.
     */
    public function registerMediaCollections(): void
    {
        // Background image for the voucher card
        $this
            ->addMediaCollection('background')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                // Small conversion for thumbnails
                $this
                    ->addMediaConversion('sm')
                    ->fit(Fit::Max, 320, 240)
                    ->keepOriginalImageFormat();

                // Medium conversion for display
                $this
                    ->addMediaConversion('md')
                    ->fit(Fit::Max, 800, 600)
                    ->keepOriginalImageFormat();
            });

        // Optional logo for the voucher card
        $this
            ->addMediaCollection('logo')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                // Small conversion for list view
                $this
                    ->addMediaConversion('sm')
                    ->fit(Fit::Max, 200, 200)
                    ->keepOriginalImageFormat();

                // Medium conversion for detail view
                $this
                    ->addMediaConversion('md')
                    ->fit(Fit::Max, 400, 400)
                    ->keepOriginalImageFormat();
            });
    }

    /**
     * Get media URL with batch voucher optimization.
     *
     * Storage Optimization:
     * For batch-generated vouchers, only the FIRST voucher has media attached.
     * All other vouchers in the batch automatically use the first voucher's media.
     *
     * This prevents duplicating the same logo/background 1000x times.
     *
     * Usage: Instead of $voucher->getFirstMediaUrl('logo'),
     * use $voucher->getMediaUrl('logo')
     *
     * Behavior:
     * - Manual vouchers (batch_id = null): Use their own media
     * - Batch vouchers WITH media: Use their own media (they're the first in batch)
     * - Batch vouchers WITHOUT media: Use the first voucher's media (shared)
     *
     * @param  string  $collection  Collection name
     * @param  string  $conversion  Conversion name
     */
    public function getMediaUrl(string $collection = 'default', string $conversion = ''): string
    {
        // Check if this voucher has media directly using Spatie's trait method
        $mediaCollection = $this->getMedia($collection);
        $hasOwnMedia = $mediaCollection->isNotEmpty();

        $batchId = $this->getBatchIdForMedia();

        // If voucher has its own media OR is not a batch voucher, use standard behavior
        if ($hasOwnMedia || ! $batchId) {
            return $this->getFirstMediaUrl($collection, $conversion);
        }

        // This is a batch voucher without media - use the representative voucher's media
        $representative = $this->getRepresentativeVoucherForMedia();

        if ($representative && $representative->id !== $this->id) {
            // Recursively call to check if representative has media
            return $representative->getMediaUrl($collection, $conversion);
        }

        // Fallback: no media found anywhere
        return '';
    }

    /**
     * Read batch_id without triggering MissingAttributeException.
     *
     * Some list queries select a subset of columns. Accessing $this->batch_id
     * when it wasn't selected will throw when "prevent accessing missing attributes"
     * is enabled. For media sharing logic, missing batch_id should behave like null.
     */
    private function getBatchIdForMedia(): ?string
    {
        $attributes = $this->getAttributes();

        if (! array_key_exists('batch_id', $attributes)) {
            return null;
        }

        $value = $attributes['batch_id'];

        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        return $value !== '' ? $value : null;
    }

    /**
     * Get the background image URL.
     */
    public function getBackgroundAttribute(): ?string
    {
        return $this->getImageUrl('background');
    }

    /**
     * Get the logo image URL.
     */
    public function getLogoAttribute(): ?string
    {
        return $this->getImageUrl('logo');
    }

    /**
     * Get the URL of a media collection with a specific conversion.
     *
     * @param  string  $collection  Media collection name
     * @param  string  $conversion  Conversion name (empty for original)
     */
    public function getImageUrl(string $collection, string $conversion = ''): ?string
    {
        // Use batch-aware media URL method
        $url = $this->getMediaUrl($collection, $conversion);

        if ($url !== '') {
            return $url;
        }

        return null;
    }

    /**
     * Magic getter for dynamic image conversions.
     *
     * Allows accessing: $voucher->{'background-md'}, $voucher->{'logo-sm'}, etc.
     */
    public function __get($key)
    {
        $collectionNames = ['background', 'logo'];

        foreach ($collectionNames as $collectionName) {
            if (substr($key, 0, strlen($collectionName) + 1) === $collectionName.'-') {
                return $this->getImageUrl($collectionName, substr($key, strlen($collectionName) + 1));
            }
        }

        return parent::__get($key);
    }
}
