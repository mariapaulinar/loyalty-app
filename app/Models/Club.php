<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Represents a Club (brand/location) in the application.
 * Clubs group cards and staff together under a partner's management.
 */

namespace App\Models;

use App\Traits\HasSchemaAccessors;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Club
 *
 * Represents a Club in the application.
 */
class Club extends Model
{
    use HasFactory, HasSchemaAccessors, HasUuids, LogsModelActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'clubs';

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
     * @var array
     */
    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should not be exposed by API and other public responses.
     *
     * @var array
     */
    protected $hiddenForPublic = [
        'description',
        'host',
        'slug',
        'location',
        'street1',
        'street2',
        'box_number',
        'postal_code',
        'city',
        'admin1',
        'admin2',
        'geoname_id',
        'region',
        'region_geoname_id',
        'country_code',
        'lat',
        'lng',
        'locale',
        'currency',
        'time_zone',
        'is_active',
        'is_primary',
        'is_undeletable',
        'is_uneditable',
        'meta',
        'deleted_at',
        'deleted_by',
        'created_by',
        'updated_by',
    ];

    public function hideForPublic()
    {
        $this->makeHidden($this->hiddenForPublic);

        return $this;
    }

    /**
     * Allow mass assignment of a model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the partner associated with the club.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class, 'created_by');
    }

    /**
     * Get the cards associated with the club.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STAMP CARD RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get the stamp cards associated with the club.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stampCards()
    {
        return $this->hasMany(StampCard::class)->orderBy('name');
    }

    /**
     * Get active stamp cards for the club.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeStampCards()
    {
        return $this->hasMany(StampCard::class)->where('is_active', true)->orderBy('name');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VOUCHER RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get all vouchers associated with the club.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vouchers()
    {
        return $this->hasMany(Voucher::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get only active vouchers associated with the club.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeVouchers()
    {
        return $this->vouchers()->where('is_active', true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TIER RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get the tiers associated with the club.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tiers()
    {
        return $this->hasMany(Tier::class)->orderBy('level');
    }

    /**
     * Get active tiers for the club.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeTiers()
    {
        return $this->hasMany(Tier::class)->where('is_active', true)->orderBy('level');
    }

    /**
     * Get the referral setting associated with the club.
     */
    public function referralSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ReferralSetting::class);
    }

    /**
     * Get the default (lowest level) tier for the club.
     *
     * The default tier is always the active tier with the lowest level number.
     * This is derived automatically rather than using a manual toggle to prevent
     * misconfiguration where a high-level tier is accidentally set as default.
     */
    public function getDefaultTier(): ?Tier
    {
        return $this->tiers()
            ->where('is_active', true)
            ->orderBy('level', 'asc')
            ->first();
    }

    /**
     * Get the member tier assignments for the club.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function memberTiers()
    {
        return $this->hasMany(MemberTier::class);
    }

    /**
     * Check if tiers are enabled for this club.
     */
    public function tiersEnabled(): bool
    {
        return $this->tiers()->where('is_active', true)->exists();
    }
}
