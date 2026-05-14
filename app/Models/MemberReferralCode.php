<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Member Referral Code
 *
 * Each member gets ONE unique code per campaign. This code is their personal
 * referral link that they share with friends and family.
 *
 * The code is:
 * - 6 characters (human-friendly, easy to share verbally)
 * - Uppercase only
 * - No ambiguous characters (O/0, I/1, L)
 * - Globally unique across all campaigns
 *
 * Stats are denormalized for performance - we don't want to count referrals
 * every time someone views their dashboard.
 */
class MemberReferralCode extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'referral_count' => 'integer',
        'successful_count' => 'integer',
        'points_earned' => 'integer',
    ];

    protected $appends = ['share_url'];

    /**
     * The campaign this code belongs to.
     */
    public function referralSetting(): BelongsTo
    {
        return $this->belongsTo(ReferralSetting::class, 'referral_setting_id');
    }

    /**
     * The member who owns this code.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * All referrals made with this code.
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referral_code_id');
    }

    /**
     * Generate the shareable URL for this code.
     *
     * Returns a short, locale-agnostic URL that's perfect for sharing.
     * The redirect controller will detect the user's language and route them
     * to the appropriate localized landing page.
     *
     * Format: https://example.com/r/AB12CD
     * Redirects to: https://example.com/{locale}/r/AB12CD
     */
    protected function shareUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => url('r/'.$this->code),
        );
    }
}
