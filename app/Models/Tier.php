<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Represents a Tier (membership level) in the application.
 * Tiers define qualification thresholds, earning multipliers, and benefits
 * that create a status-driven engagement platform.
 *
 * Design Tenets:
 * - **Flexible Qualification**: Support points, spend, and transaction thresholds
 * - **Translatable**: Display names and descriptions support multiple languages
 * - **Hierarchical**: Levels determine tier ordering and progression
 */

namespace App\Models;

use App\Services\TierService;
use App\Traits\HasSchemaAccessors;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Class Tier
 *
 * Represents a membership tier level in the application.
 */
class Tier extends Model
{
    use HasFactory, HasSchemaAccessors, HasTranslations, HasUuids, LogsModelActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tiers';

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
        'level' => 'integer',
        'points_threshold' => 'integer',
        'spend_threshold' => 'integer',
        'transactions_threshold' => 'integer',
        'points_multiplier' => 'decimal:2',
        'redemption_discount' => 'decimal:2',
        'benefits' => 'array',
        'is_default' => 'boolean', // Stored for seeding/migration compat, but accessor overrides reads
        'is_active' => 'boolean',
        'is_undeletable' => 'boolean',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Allow mass assignment of a model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Translatable fields.
     *
     * @var array
     */
    public $translatable = ['display_name', 'description', 'benefits'];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Tier $tier) {
            // Enforce unique level per club to prevent ambiguous default derivation
            $duplicate = static::where('club_id', $tier->club_id)
                ->where('level', $tier->level)
                ->where('id', '!=', $tier->id ?? '')
                ->exists();

            if ($duplicate) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'level' => [trans('common.tier_level_must_be_unique')],
                ]);
            }
        });

        static::updated(function (Tier $tier) {
            if ($tier->isDirty('is_active')) {
                if (! $tier->is_active) {
                    // Deactivated: move members off this tier
                    static::recalculateAffectedMembers($tier);
                } else {
                    // Reactivated: check if lower-tier members now qualify
                    static::recalculateEligibleMembers($tier);
                }
            }
        });

        static::deleting(function (Tier $tier) {
            // Remove from the active set BEFORE recalculating, so evaluateMemberTier
            // won't reassign members back to this tier. Uses query builder to
            // avoid retriggering the updated event.
            \Illuminate\Support\Facades\DB::table('tiers')
                ->where('id', $tier->id)
                ->update(['is_active' => false]);

            static::recalculateAffectedMembers($tier);
        });
    }

    /**
     * Recalculate tiers for all members currently assigned to the given tier.
     *
     * Used when a tier is deactivated or deleted to move affected members
     * to the correct remaining tier (or clear their assignment if none remain).
     */
    public static function recalculateAffectedMembers(Tier $tier): void
    {
        $affectedMemberTiers = MemberTier::where('tier_id', $tier->id)
            ->where('is_active', true)
            ->with('member')
            ->get();

        if ($affectedMemberTiers->isEmpty()) {
            return;
        }

        $tierService = app(TierService::class);
        $club = $tier->club;

        foreach ($affectedMemberTiers as $memberTier) {
            $member = $memberTier->member;
            if ($member && $club) {
                // Don't deactivate the MemberTier first — evaluateMemberTier needs
                // the active assignment to discover the previous tier for history,
                // notifications, and audit trail. assignTier() handles deactivation.
                $tierService->evaluateMemberTier($member, $club, forceDowngrade: true);
            }
        }
    }

    /**
     * Re-evaluate members on lower-level tiers when a tier is reactivated.
     *
     * Members currently on tiers below the reactivated tier may now qualify
     * for it (or an even higher tier). Uses forceDowngrade:false so members
     * can only be upgraded, never downgraded.
     */
    public static function recalculateEligibleMembers(Tier $tier): void
    {
        $club = $tier->club;
        if (! $club) {
            return;
        }

        // Get IDs of all active tiers at a lower level in the same club
        $lowerTierIds = static::where('club_id', $tier->club_id)
            ->where('is_active', true)
            ->where('level', '<', $tier->level)
            ->pluck('id');

        if ($lowerTierIds->isEmpty()) {
            return;
        }

        // Find members currently assigned to those lower tiers
        $affectedMemberTiers = MemberTier::whereIn('tier_id', $lowerTierIds)
            ->where('is_active', true)
            ->with('member')
            ->get();

        if ($affectedMemberTiers->isEmpty()) {
            return;
        }

        $tierService = app(TierService::class);

        foreach ($affectedMemberTiers as $memberTier) {
            $member = $memberTier->member;
            if ($member) {
                $tierService->evaluateMemberTier($member, $club, forceDowngrade: false);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MUTATORS - Handle empty string to null/default conversions
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Set the points_threshold attribute.
     */
    public function setPointsThresholdAttribute(mixed $value): void
    {
        $this->attributes['points_threshold'] = $value === '' || $value === null ? null : (int) $value;
    }

    /**
     * Set the spend_threshold attribute.
     */
    public function setSpendThresholdAttribute(mixed $value): void
    {
        $this->attributes['spend_threshold'] = $value === '' || $value === null ? null : (int) $value;
    }

    /**
     * Set the transactions_threshold attribute.
     */
    public function setTransactionsThresholdAttribute(mixed $value): void
    {
        $this->attributes['transactions_threshold'] = $value === '' || $value === null ? null : (int) $value;
    }

    /**
     * Set the level attribute.
     */
    public function setLevelAttribute(mixed $value): void
    {
        $this->attributes['level'] = $value === '' || $value === null ? 0 : (int) $value;
    }

    /**
     * Set the points_multiplier attribute.
     */
    public function setPointsMultiplierAttribute(mixed $value): void
    {
        $this->attributes['points_multiplier'] = $value === '' || $value === null ? 1.00 : (float) $value;
    }

    /**
     * Set the redemption_discount attribute.
     */
    public function setRedemptionDiscountAttribute(mixed $value): void
    {
        $this->attributes['redemption_discount'] = $value === '' || $value === null ? 0.00 : (float) $value;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get the club that owns the tier.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the partner who created the tier.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'created_by');
    }

    /**
     * Get the partner who last updated the tier.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'updated_by');
    }

    /**
     * Get all member tier assignments for this tier.
     */
    public function memberTiers(): HasMany
    {
        return $this->hasMany(MemberTier::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Scope to active tiers only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by tier level (hierarchy).
     */
    public function scopeOrdered(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('level', $direction);
    }

    /**
     * Scope to filter by club.
     */
    public function scopeForClub(Builder $query, string|Club $club): Builder
    {
        $clubId = $club instanceof Club ? $club->id : $club;

        return $query->where('club_id', $clubId);
    }

    /**
     * Scope to get default (lowest level) tier for a club.
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('level', 'asc');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // METHODS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check if a member qualifies for this tier based on their stats.
     *
     * @param  int  $lifetimePoints  Member's lifetime points
     * @param  int  $lifetimeSpend  Member's lifetime spend in cents
     * @param  int  $transactionCount  Member's transaction count
     * @param  string  $evaluationMode  'any' (any threshold) or 'all' (all thresholds)
     */
    public function memberQualifies(
        int $lifetimePoints,
        int $lifetimeSpend,
        int $transactionCount,
        string $evaluationMode = 'any'
    ): bool {
        $qualifications = [];

        // Check points threshold
        if ($this->points_threshold !== null && $this->points_threshold > 0) {
            $qualifications['points'] = $lifetimePoints >= $this->points_threshold;
        }

        // Check spend threshold
        if ($this->spend_threshold !== null && $this->spend_threshold > 0) {
            $qualifications['spend'] = $lifetimeSpend >= $this->spend_threshold;
        }

        // Check transactions threshold
        if ($this->transactions_threshold !== null && $this->transactions_threshold > 0) {
            $qualifications['transactions'] = $transactionCount >= $this->transactions_threshold;
        }

        // No thresholds defined — this tier qualifies everyone (base/default behavior)
        if (empty($qualifications)) {
            return $this->isLowestLevelInClub();
        }

        // Evaluate based on mode
        if ($evaluationMode === 'all') {
            return ! in_array(false, $qualifications, true);
        }

        // Default: 'any' mode
        return in_array(true, $qualifications, true);
    }

    /**
     * Check if this is the lowest level active tier in its club.
     *
     * Used as the derived "default" — new members start here,
     * and members who don't qualify for any threshold-based tier fall back here.
     */
    public function isLowestLevelInClub(): bool
    {
        $lowestLevel = static::where('club_id', $this->club_id)
            ->where('is_active', true)
            ->min('level');

        return $lowestLevel !== null && $this->level === $lowestLevel;
    }

    /**
     * Computed accessor: is_default is derived from level position.
     *
     * The lowest-level active tier in a club is always the default.
     * This replaces the manual is_default toggle to prevent misconfiguration.
     */
    public function getIsDefaultAttribute(): bool
    {
        return $this->isLowestLevelInClub();
    }

    /**
     * Get the next tier in the hierarchy.
     */
    public function getNextTier(): ?Tier
    {
        return static::where('club_id', $this->club_id)
            ->where('is_active', true)
            ->where('level', '>', $this->level)
            ->orderBy('level', 'asc')
            ->first();
    }

    /**
     * Get the previous tier in the hierarchy.
     */
    public function getPreviousTier(): ?Tier
    {
        return static::where('club_id', $this->club_id)
            ->where('is_active', true)
            ->where('level', '<', $this->level)
            ->orderBy('level', 'desc')
            ->first();
    }

    /**
     * Calculate progress percentages towards this tier for a member.
     *
     * @return array{points: int|null, spend: int|null, transactions: int|null}
     */
    public function getProgressFor(int $lifetimePoints, int $lifetimeSpend, int $transactionCount): array
    {
        $progress = [
            'points' => null,
            'spend' => null,
            'transactions' => null,
        ];

        if ($this->points_threshold !== null && $this->points_threshold > 0) {
            $progress['points'] = min(100, (int) round(($lifetimePoints / $this->points_threshold) * 100));
        }

        if ($this->spend_threshold !== null && $this->spend_threshold > 0) {
            $progress['spend'] = min(100, (int) round(($lifetimeSpend / $this->spend_threshold) * 100));
        }

        if ($this->transactions_threshold !== null && $this->transactions_threshold > 0) {
            $progress['transactions'] = min(100, (int) round(($transactionCount / $this->transactions_threshold) * 100));
        }

        return $progress;
    }

    /**
     * Get the count of members at this tier.
     */
    public function getMemberCount(): int
    {
        return $this->memberTiers()->where('is_active', true)->count();
    }

    /**
     * Get the localized display name, falling back to internal name.
     */
    public function getLocalizedName(?string $locale = null): string
    {
        $displayName = $this->getTranslation('display_name', $locale ?? app()->getLocale());

        return $displayName ?: $this->name;
    }

    /**
     * Apply the tier's points multiplier to a base point amount.
     */
    public function applyMultiplier(int $basePoints): int
    {
        return (int) round($basePoints * (float) $this->points_multiplier);
    }

    /**
     * Apply the tier's redemption discount to a reward's point cost.
     */
    public function applyRedemptionDiscount(int $rewardPoints): int
    {
        $discount = (float) $this->redemption_discount;

        return (int) round($rewardPoints * (1 - $discount));
    }
}
