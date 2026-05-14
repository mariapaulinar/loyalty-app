<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Represents a Member's Tier assignment in the application.
 * Tracks each member's current tier status per club, including
 * when they achieved it, qualifying stats, and expiration.
 *
 * Design Tenets:
 * - **Per-Club Tracking**: Members can have different tiers in different clubs
 * - **Historical**: Tracks previous tier for progression analysis
 * - **Expirable**: Supports annual renewal tier programs
 */

namespace App\Models;

use App\Traits\HasSchemaAccessors;
use App\Traits\LogsModelActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class MemberTier
 *
 * Represents a member's tier assignment in the application.
 */
class MemberTier extends Model
{
    use HasFactory, HasSchemaAccessors, HasUuids, LogsModelActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'member_tiers';

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
        'achieved_at' => 'datetime',
        'expires_at' => 'datetime',
        'qualifying_points' => 'integer',
        'qualifying_spend' => 'integer',
        'qualifying_transactions' => 'integer',
        'is_active' => 'boolean',
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

    // ─────────────────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get the member who owns this tier assignment.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the tier assigned to the member.
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class);
    }

    /**
     * Get the club for this tier assignment.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the previous tier (for tracking progression).
     */
    public function previousTier(): BelongsTo
    {
        return $this->belongsTo(Tier::class, 'previous_tier_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Scope to active tier assignments only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
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
     * Scope to filter by member.
     */
    public function scopeForMember(Builder $query, string|Member $member): Builder
    {
        $memberId = $member instanceof Member ? $member->id : $member;

        return $query->where('member_id', $memberId);
    }

    /**
     * Scope to expired tier assignments.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', Carbon::now());
    }

    /**
     * Scope to non-expired (valid) tier assignments.
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', Carbon::now());
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // METHODS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check if this tier assignment has expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return Carbon::now()->gte($this->expires_at);
    }

    /**
     * Get the number of days until expiry (null if no expiry).
     */
    public function daysUntilExpiry(): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        if ($this->isExpired()) {
            return 0;
        }

        return (int) Carbon::now()->diffInDays($this->expires_at);
    }

    /**
     * Check if this represents an upgrade from the previous tier.
     */
    public function isUpgrade(): bool
    {
        if ($this->previous_tier_id === null) {
            return false;
        }

        $previousTier = $this->previousTier;
        if ($previousTier === null) {
            return false;
        }

        return $this->tier->level > $previousTier->level;
    }

    /**
     * Check if this represents a downgrade from the previous tier.
     */
    public function isDowngrade(): bool
    {
        if ($this->previous_tier_id === null) {
            return false;
        }

        $previousTier = $this->previousTier;
        if ($previousTier === null) {
            return false;
        }

        return $this->tier->level < $previousTier->level;
    }

    /**
     * Get the tier's points multiplier (convenience accessor).
     */
    public function getMultiplier(): float
    {
        return (float) ($this->tier->points_multiplier ?? 1.00);
    }

    /**
     * Get the tier's redemption discount (convenience accessor).
     */
    public function getRedemptionDiscount(): float
    {
        return (float) ($this->tier->redemption_discount ?? 0.00);
    }
}
