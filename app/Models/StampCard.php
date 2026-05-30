<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Represents a Stamp Card (digital punch card) template in the application.
 * Stamp cards are the "Buy 10, Get 1 Free" style loyalty mechanic that operates
 * independently from the points system.
 *
 * Design Tenets:
 * - **Simple Rules**: Clear stamp requirements, no complex calculations
 * - **Visual Appeal**: Customizable colors and icons for branding
 * - **Flexibility**: Supports various earning and expiration rules
 * - **Performance**: Denormalized counters avoid expensive aggregations
 *
 * Usage Example:
 * $card = StampCard::create([
 *     'club_id' => $club->id,
 *     'name' => 'Coffee Loyalty Card',
 *     'stamps_required' => 10,
 *     'reward_title' => ['en' => 'Free Medium Coffee'],
 * ]);
 */

namespace App\Models;

use App\Traits\HasIdentifier;
use App\Traits\HasSchemaAccessors;
use App\Traits\LogsModelActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * Class StampCard
 *
 * Represents a stamp card template that clubs create.
 */
class StampCard extends Model implements HasMedia
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
    protected $table = 'stamp_cards';

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
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'last_view' => 'datetime',
        'is_active' => 'boolean',
        'is_visible_by_default' => 'boolean',
        'is_undeletable' => 'boolean',
        'requires_physical_claim' => 'boolean',
        'show_monetary_value' => 'boolean',
        'stamps_required' => 'integer',
        'stamps_per_purchase' => 'integer',
        'max_stamps_per_day' => 'integer',
        'max_stamps_per_transaction' => 'integer',
        'stamps_expire_days' => 'integer',
        'reward_points' => 'integer',
        'views' => 'integer',
        'total_stamps_issued' => 'integer',
        'total_completions' => 'integer',
        'total_redemptions' => 'integer',
        'min_purchase_amount' => 'float',
        'reward_value' => 'float',
        // Don't cast reward_card_id to string - it needs to stay null when empty
        'bg_color_opacity' => 'integer',
        'qualifying_products' => 'array',
        'meta' => 'array',

        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Backward-compatible alias for staff redemption requirement.
     *
     * The database column is `requires_physical_claim`. Older/newer UI/service code
     * may reference `require_staff_for_redemption`. This accessor prevents
     * MissingAttributeException and keeps intent consistent.
     */
    public function getRequireStaffForRedemptionAttribute(): bool
    {
        $attributes = $this->getAttributes();

        // Prefer explicit column when it is present in the selected columns.
        if (array_key_exists('requires_physical_claim', $attributes)) {
            return (bool) $attributes['requires_physical_claim'];
        }

        // Fallback: support meta flag if present (and selected).
        if (array_key_exists('meta', $attributes) && is_array($attributes['meta'])) {
            return (bool) ($attributes['meta']['require_staff_for_redemption'] ?? false);
        }

        return false;
    }

    /**
     * Translatable fields.
     *
     * @var array<string>
     */
    public array $translatable = [
        'title',
        'description',
        'reward_title',
        'reward_description',
    ];

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
     * Set the min_purchase_amount attribute (handles empty strings before float cast).
     */
    public function setMinPurchaseAmountAttribute($value): void
    {
        $this->attributes['min_purchase_amount'] = ($value === '' || $value === null) ? null : $value;
    }

    /**
     * Set the reward_value attribute (handles empty strings before float cast).
     */
    public function setRewardValueAttribute($value): void
    {
        $this->attributes['reward_value'] = ($value === '' || $value === null) ? null : $value;
    }

    /**
     * Boot method to handle empty string conversions for numeric/string fields.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Convert empty strings to null for nullable numeric/string fields (MySQL strict mode)
        // Use getRawOriginal/getAttributes to check actual raw values before casting
        static::saving(function ($model) {
            $attributes = $model->getAttributes();
            foreach (['max_stamps_per_day', 'max_stamps_per_transaction', 'min_purchase_amount', 'stamps_expire_days', 'reward_value', 'reward_points', 'currency', 'description', 'reward_description'] as $field) {
                if (array_key_exists($field, $attributes) && $attributes[$field] === '') {
                    $model->{$field} = null;
                }
            }
        });
    }

    /**
     * Allow mass assignment of a model.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * Register media collections for the model.
     */
    public function registerMediaCollections(): void
    {
        // Background image for the stamp card
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

        // Optional logo for the stamp card
        $this
            ->addMediaCollection('logo')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                // Small conversion for list view
                $this
                    ->addMediaConversion('sm')
                    ->fit(Fit::Max, 200, 200)
                    ->keepOriginalImageFormat();

                // Medium conversion for edit/view
                $this
                    ->addMediaConversion('md')
                    ->fit(Fit::Max, 600, 600)
                    ->keepOriginalImageFormat();
            });
    }

    // ═════════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Get the club that owns this stamp card.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the club that owns this stamp card.
     */
    public function clubes(): BelongsToMany
    {
        return $this->belongsToMany(Club::class, 'stamp_card_club');
    }

    /**
     * Partner who owns this stamp card (through club).
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'created_by');
    }

    /**
     * Get the loyalty card that receives points when this stamp card completes.
     */
    public function rewardCard(): BelongsTo
    {
        return $this->belongsTo(Card::class, 'reward_card_id');
    }

    /**
     * Get all enrollments for this stamp card.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StampCardMember::class);
    }

    /**
     * Get active enrollments for this stamp card.
     */
    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(StampCardMember::class)->where('is_active', true);
    }

    /**
     * Get all transactions for this stamp card.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(StampTransaction::class);
    }

    /**
     * Get the members enrolled in this stamp card.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'stamp_card_member')
            ->withPivot([
                'current_stamps',
                'lifetime_stamps',
                'completed_count',
                'redeemed_count',
                'pending_rewards',
                'enrolled_at',
                'last_stamp_at',
                'last_completed_at',
                'last_redeemed_at',
                'is_active',
            ])
            ->withTimestamps();
    }

    /**
     * Get the partner who created this stamp card.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'created_by');
    }

    /**
     * Get the partner who last updated this stamp card.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'updated_by');
    }

    /**
     * Get the partner who deleted this stamp card.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'deleted_by');
    }

    // ═════════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Scope to only active stamp cards.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only visible stamp cards.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible_by_default', true);
    }

    /**
     * Scope to stamp cards for a specific club.
     */
    public function scopeForClub(Builder $query, string|Club $club): Builder
    {
        $clubId = $club instanceof Club ? $club->id : $club;

        return $query->where('club_id', $clubId);
    }

    /**
     * Scope to available stamp cards (active, visible, and not expired).
     */
    public function scopeAvailable(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query
            ->where('is_active', true)
            ->where('is_visible_by_default', true)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $now);
            });
    }

    /**
     * Scope to stamp cards that are currently valid (within valid_from and valid_until dates).
     */
    public function scopeCurrentlyValid(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $now);
            });
    }

    // ═════════════════════════════════════════════════════════════════════════
    // BUSINESS LOGIC METHODS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Check if the stamp card is currently available for use.
     *
     * Available means: active, within valid dates, and not soft-deleted.
     */
    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = Carbon::now();

        // Check valid_from
        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        // Check valid_until
        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the card is expired (past valid_until date).
     */
    public function isExpired(): bool
    {
        if (! $this->valid_until) {
            return false;
        }

        return Carbon::now()->gt($this->valid_until);
    }

    /**
     * Get a member's progress on this stamp card.
     *
     * Returns the StampCardMember pivot record or null if not enrolled.
     */
    public function getMemberProgress(?Member $member): ?StampCardMember
    {
        if (! $member) {
            return null;
        }

        return StampCardMember::where('stamp_card_id', $this->id)
            ->where('member_id', $member->id)
            ->first();
    }

    /**
     * Check if a member is enrolled in this stamp card.
     */
    public function isMemberEnrolled(Member $member): bool
    {
        return StampCardMember::where('stamp_card_id', $this->id)
            ->where('member_id', $member->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if member can earn stamps today (respects daily limit).
     *
     * @return bool True if member can earn stamps, false if daily limit reached
     */
    public function canMemberEarnToday(Member $member): bool
    {
        if (! $this->max_stamps_per_day) {
            return true; // No daily limit
        }

        $today = Carbon::today();
        $stampsEarnedToday = StampTransaction::where('stamp_card_id', $this->id)
            ->where('member_id', $member->id)
            ->whereIn('event', ['stamp_earned', 'stamps_bonus'])
            ->whereDate('created_at', $today)
            ->sum('stamps');

        return $stampsEarnedToday < $this->max_stamps_per_day;
    }

    /**
     * Check if member can earn stamps now (all eligibility checks).
     *
     * @param  Member  $member  The member attempting to earn
     * @param  int  $stamps  Number of stamps attempting to earn (default 1)
     * @return bool True if eligible to earn
     */
    public function canMemberEarnNow(Member $member, int $stamps = 1): bool
    {
        // Card must be available
        if (! $this->isAvailable()) {
            return false;
        }

        // Check daily limit
        if (! $this->canMemberEarnToday($member)) {
            return false;
        }

        // Check per-transaction limit
        if ($this->max_stamps_per_transaction && $stamps > $this->max_stamps_per_transaction) {
            return false;
        }

        return true;
    }

    /**
     * Calculate completion percentage for given current stamps.
     *
     * @param  int  $currentStamps  Number of stamps member has
     * @return float Percentage (0-100)
     */
    public function getCompletionPercentage(int $currentStamps): float
    {
        if ($this->stamps_required === 0) {
            return 0.0;
        }

        return round(($currentStamps / $this->stamps_required) * 100, 2);
    }

    /**
     * Get the number of stamps needed to complete the card.
     *
     * @param  int  $currentStamps  Number of stamps member has
     * @return int Stamps remaining
     */
    public function getStampsNeeded(int $currentStamps): int
    {
        $remaining = $this->stamps_required - $currentStamps;

        return max(0, $remaining);
    }

    
    /**
     * Determine if this stamp card is accessible by the staff member's club.
     *
     * Checks if the provided staff club ID gives access to this stamp card:
     * - For cards of type 'event', it verifies if the staff club is associated with the card through the pivot table.
     * - For other card types, it compares the card's club_id with the staff club ID.
     *
     * @param  string|null  $staffClubId  The ID of the staff's club
     * @return bool  True if accessible by the staff club, false otherwise
     */
    public function isAccessibleByStaffClub(?string $staffClubId): bool
    {
        if (! $staffClubId) {
            return false;
        }

        if ($this->card_type === 'event') {
            return $this->clubes()->where('club_id', $staffClubId)->exists();
        }

        return $this->club_id === $staffClubId;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // MEDIA ACCESSORS
    // ═════════════════════════════════════════════════════════════════════════

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
        if ($this->getFirstMediaUrl($collection) !== '') {
            $media = $this->getMedia($collection);

            return $media[0]->getFullUrl($conversion);
        }

        return null;
    }

    /**
     * Magic getter for dynamic image conversions.
     *
     * Allows accessing: $card->{'background-sm'}, $card->{'logo-md'}, etc.
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
