<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Represents a member's enrollment and progress on a specific stamp card.
 * This is the pivot table with additional tracking data for member progress.
 *
 * Design Tenets:
 * - **Progress Tracking**: Real-time stamp counts and completion status
 * - **Historical Data**: Lifetime stamps and completion counts never decrease
 * - **Performance**: Denormalized counts prevent expensive aggregations
 * - **Engagement**: Timestamps track member's interaction patterns
 *
 * Usage Example:
 * $enrollment = StampCardMember::create([
 *     'stamp_card_id' => $card->id,
 *     'member_id' => $member->id,
 *     'current_stamps' => 0,
 *     'enrolled_at' => now(),
 * ]);
 */

namespace App\Models;

use App\Traits\HasSchemaAccessors;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
/**
 * Class StampCardMember
 *
 * Represents a member's enrollment in a stamp card program.
 */
class StampCardMember extends Model
{
    use HasFactory;
    use HasSchemaAccessors;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stamp_card_member';

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
        'current_stamps' => 'integer',
        'lifetime_stamps' => 'integer',
        'completed_count' => 'integer',
        'redeemed_count' => 'integer',
        'pending_rewards' => 'integer',
        'enrolled_at' => 'datetime',
        'last_stamp_at' => 'datetime',
        'last_completed_at' => 'datetime',
        'last_redeemed_at' => 'datetime',
        'next_stamp_expires_at' => 'datetime',
        'is_active' => 'boolean',
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

    // ═════════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Get the stamp card this enrollment belongs to.
     */
    public function stampCard(): BelongsTo
    {
        return $this->belongsTo(StampCard::class);
    }

    /**
     * Get the member who owns this enrollment.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get all stamp transactions for this enrollment.
     *
     * Scoped to this specific stamp card and member combination.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(StampTransaction::class, 'stamp_card_id', 'stamp_card_id')
            ->where('member_id', $this->member_id);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Get the progress percentage (0-100).
     */
    public function getProgressPercentageAttribute(): float
    {
        if (! $this->stampCard || $this->stampCard->stamps_required === 0) {
            return 0.0;
        }

        return round(($this->current_stamps / $this->stampCard->stamps_required) * 100, 2);
    }

    /**
     * Get the number of stamps needed to complete the card.
     */
    public function getStampsNeededAttribute(): int
    {
        if (! $this->stampCard) {
            return 0;
        }

        $remaining = $this->stampCard->stamps_required - $this->current_stamps;

        return max(0, $remaining);
    }

    /**
     * Check if member has a pending reward.
     */
    public function getHasPendingRewardAttribute(): bool
    {
        return $this->pending_rewards > 0;
    }

    /**
     * Check if the card is complete (ready for redemption).
     */
    public function getIsCompleteAttribute(): bool
    {
        if (! $this->stampCard) {
            return false;
        }

        return $this->current_stamps >= $this->stampCard->stamps_required;
    }

    /**
     * Get days until stamps expire (null if no expiration).
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (! $this->next_stamp_expires_at) {
            return null;
        }

        $now = Carbon::now();

        if ($this->next_stamp_expires_at->lt($now)) {
            return 0; // Already expired
        }

        return $now->diffInDays($this->next_stamp_expires_at);
    }

    /**
     * Check if stamps are close to expiring (within 7 days).
     */
    public function getIsExpiringAttribute(): bool
    {
        $daysUntil = $this->days_until_expiration;

        if ($daysUntil === null) {
            return false;
        }

        return $daysUntil <= 7 && $daysUntil >= 0;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // BUSINESS LOGIC METHODS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Add stamps to this enrollment (internal method).
     *
     * This is called by StampService and handles the stamp addition logic
     * including overflow when card is completed.
     *
     * @param  int  $count  Number of stamps to add
     * @return array ['completed' => bool, 'overflow' => int, 'completions' => int]
     */
    public function addStamps(int $count): array
    {
        if (! $this->stampCard) {
            return [
                'completed' => false,
                'overflow' => 0,
                'completions' => 0,
            ];
        }
        
        $newTotal = $this->current_stamps + $count;
        $stampsRequired = $this->stampCard->stamps_required;

        // Guard against misconfigured stamp cards with 0 required stamps
        if ($stampsRequired <= 0) {
            $this->lifetime_stamps += $count;
            $this->last_stamp_at = now();
            $this->save();

            return [
                'completed' => false,
                'overflow' => 0,
                'completions' => 0,
            ];
        }

        // Calculate completions and overflow
        $completions = intdiv($newTotal, $stampsRequired);
        $overflow = $newTotal % $stampsRequired;

        // Update enrollment
        $this->current_stamps = $overflow;
        $this->lifetime_stamps += $count;
        $this->completed_count += $completions;
        $this->pending_rewards += $completions;
        $this->last_stamp_at = now();

        if ($completions > 0) {
            $this->last_completed_at = now();
        }

        // Calculate next expiration if card has expiration rule
        if ($this->stampCard->stamps_expire_days) {
            $this->next_stamp_expires_at = now()->addDays($this->stampCard->stamps_expire_days);
        }

        $this->save();

        return [
            'completed' => $completions > 0,
            'overflow' => $overflow,
            'completions' => $completions,
        ];
    }

    /**
     * Redeem a reward (internal method).
     *
     * Decrements pending_rewards and updates timestamps.
     * Called by StampService.
     *
     * @return bool True if redemption successful, false if no pending rewards
     */
    public function redeemReward(): bool
    {
        if ($this->pending_rewards === 0) {
            return false;
        }

        $this->pending_rewards--;
        $this->redeemed_count++;
        $this->last_redeemed_at = now();
        $this->save();

        return true;
    }

    /**
     * Check if stamps have expired.
     *
     * Called by ProcessExpiredStamps job.
     *
     * @return bool True if stamps have expired
     */
    public function checkExpiration(): bool
    {
        if (! $this->next_stamp_expires_at) {
            return false; // No expiration configured
        }

        if ($this->current_stamps === 0) {
            return false; // No stamps to expire
        }

        return Carbon::now()->gte($this->next_stamp_expires_at);
    }

    /**
     * Expire all current stamps (internal method).
     *
     * Called by StampService when stamps expire.
     *
     * @return int Number of stamps that expired
     */
    public function expireStamps(): int
    {
        $expiredStamps = $this->current_stamps;

        if ($expiredStamps === 0) {
            return 0;
        }

        $this->current_stamps = 0;
        $this->next_stamp_expires_at = null;
        $this->save();

        return $expiredStamps;
    }

    /**
     * Adjust stamps manually (for corrections).
     *
     * Used by staff/admin for manual adjustments.
     *
     * @param  int  $adjustment  Positive to add, negative to subtract
     * @return bool True if adjustment successful
     */
    public function adjustStamps(int $adjustment): bool
    {
        $newStamps = $this->current_stamps + $adjustment;

        if ($newStamps < 0) {
            $newStamps = 0;
        }

        if (! $this->stampCard || $newStamps > $this->stampCard->stamps_required) {
            $newStamps = $this->stampCard->stamps_required;
        }

        $this->current_stamps = $newStamps;

        if ($adjustment > 0) {
            $this->lifetime_stamps += $adjustment;
        }

        $this->save();

        return true;
    }

    /**
     * Deactivate this enrollment.
     *
     * Unenrolls the member but preserves historical data.
     */
    public function deactivate(): void
    {
        $this->is_active = false;
        $this->save();
    }

    /**
     * Reactivate this enrollment.
     */
    public function reactivate(): void
    {
        $this->is_active = true;
        $this->save();
    }
}
