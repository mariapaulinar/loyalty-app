<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Represents a one-time password (OTP) code for passwordless authentication.
 * OTP codes are short-lived, single-use tokens sent via email (or SMS in future).
 *
 * Design Tenets:
 * - **Security First**: Codes are hashed, attempts are tracked, expiration enforced
 * - **Multi-Guard**: Supports member, staff, partner, and admin authentication
 * - **Future-Ready**: Schema supports phone/SMS delivery when implemented
 * - **Audit-Friendly**: Tracks IP, user agent, and verification timestamps
 *
 * Usage:
 * // Create via OtpService, not directly
 * $otp = OtpCode::query()
 *     ->forIdentifier('user@example.com')
 *     ->forPurpose('login')
 *     ->active()
 *     ->first();
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $identifier
 * @property string $identifier_type
 * @property string $code
 * @property string $purpose
 * @property string $guard
 * @property int $attempts
 * @property int $max_attempts
 * @property bool $is_verified
 * @property Carbon|null $verified_at
 * @property Carbon $expires_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OtpCode extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'otp_codes';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'identifier',
        'identifier_type',
        'code',
        'purpose',
        'guard',
        'attempts',
        'max_attempts',
        'is_verified',
        'verified_at',
        'expires_at',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Scope to filter by identifier (email or phone).
     */
    public function scopeForIdentifier(Builder $query, string $identifier): Builder
    {
        return $query->where('identifier', strtolower($identifier));
    }

    /**
     * Scope to filter by identifier type.
     */
    public function scopeForIdentifierType(Builder $query, string $type): Builder
    {
        return $query->where('identifier_type', $type);
    }

    /**
     * Scope to filter by purpose.
     */
    public function scopeForPurpose(Builder $query, string $purpose): Builder
    {
        return $query->where('purpose', $purpose);
    }

    /**
     * Scope to filter by guard.
     */
    public function scopeForGuard(Builder $query, string $guard): Builder
    {
        return $query->where('guard', $guard);
    }

    /**
     * Scope to get only active (not expired, not verified, not locked) OTPs.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('expires_at', '>', now())
            ->where('is_verified', false)
            ->whereColumn('attempts', '<', 'max_attempts');
    }

    /**
     * Scope to get expired OTPs (for cleanup).
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope to get verified OTPs.
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get locked OTPs (max attempts reached).
     */
    public function scopeLocked(Builder $query): Builder
    {
        return $query->whereColumn('attempts', '>=', 'max_attempts');
    }

    /**
     * Scope to get OTPs created within a time window (for rate limiting).
     */
    public function scopeCreatedWithin(Builder $query, int $minutes): Builder
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPER METHODS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check if this OTP is still valid (not expired, not verified, not locked).
     */
    public function isValid(): bool
    {
        return ! $this->isExpired()
            && ! $this->is_verified
            && ! $this->isLocked();
    }

    /**
     * Check if this OTP has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if this OTP is locked due to too many attempts.
     */
    public function isLocked(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }

    /**
     * Get the number of remaining attempts.
     */
    public function remainingAttempts(): int
    {
        return max(0, $this->max_attempts - $this->attempts);
    }

    /**
     * Increment the attempt counter.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Mark this OTP as verified.
     */
    public function markAsVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Get minutes until expiration (or 0 if already expired).
     */
    public function minutesUntilExpiration(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return (int) now()->diffInMinutes($this->expires_at, false);
    }

    /**
     * Get seconds until expiration (or 0 if already expired).
     */
    public function secondsUntilExpiration(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return (int) now()->diffInSeconds($this->expires_at, false);
    }
}
