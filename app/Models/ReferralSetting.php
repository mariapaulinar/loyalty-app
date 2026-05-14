<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Models;

use App\Traits\HasSchemaAccessors;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Referral Campaign Configuration
 *
 * This model represents a referral campaign - a discrete, configurable program
 * where members can refer friends and both parties receive rewards on specific cards.
 *
 * Key Relationships:
 * - referrerCard: The card that receives points when someone refers a friend
 * - refereeCard: The card that receives welcome bonus points for new members
 * - codes: All referral codes generated for this campaign
 *
 * Note: NO club relationship. Campaigns are card-based, not club-based.
 */
class ReferralSetting extends Model
{
    use HasSchemaAccessors, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
        'referrer_points' => 'integer',
        'referee_points' => 'integer',
    ];

    /**
     * The card that receives referrer rewards.
     */
    public function referrerCard(): BelongsTo
    {
        return $this->belongsTo(Card::class, 'referrer_card_id');
    }

    /**
     * The card that receives referee welcome bonuses.
     */
    public function refereeCard(): BelongsTo
    {
        return $this->belongsTo(Card::class, 'referee_card_id');
    }

    /**
     * The partner who created this campaign.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'created_by');
    }

    /**
     * All referral codes generated for this campaign.
     */
    public function codes(): HasMany
    {
        return $this->hasMany(MemberReferralCode::class, 'referral_setting_id');
    }
}
