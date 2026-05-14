<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Represents a Staff member in the application.
 * Staff are employees who scan QR codes and issue points to members.
 */

namespace App\Models;

use App\QueryBuilders\StaffQueryBuilder;
use App\Traits\HasIdentifier;
use App\Traits\HasSchemaAccessors;
use App\Traits\LogsModelActivity;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Class Staff
 *
 * Represents a Staff member in the application.
 */
class Staff extends Authenticatable implements HasLocalePreference, HasMedia
{
    use HasApiTokens, HasFactory, HasIdentifier, HasSchemaAccessors, HasUuids, InteractsWithMedia, LogsModelActivity, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'staff';

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
        'email_verified_at' => 'datetime',
        'meta' => 'array',
        'created_at' => 'datetime',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should not be exposed by API and other public responses.
     *
     * @var array
     */
    protected $hiddenForPublic = [
        'role',
        'display_name',
        'unique_identifier',
        'email_verified_at',
        'password',
        'remember_token',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'account_expires_at',
        'country_code',
        'currency',
        'phone_prefix',
        'phone_country',
        'phone',
        'phone_e164',
        'phone_prefix',
        'meta',
        'is_active',
        'is_undeletable',
        'is_uneditable',
        'media',
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
     * Append programmatically added columns.
     *
     * @var array
     */
    protected $appends = [
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function newEloquentBuilder($query)
    {
        return new StaffQueryBuilder($query);
    }

    /**
     * Get the user's preferred locale.
     *
     * @return string
     */
    public function preferredLocale()
    {
        $locale = $this->locale;
        $defaultLocale = config('app.locale');

        return File::exists(lang_path().'/'.$locale) ? $locale : $defaultLocale;
    }

    /**
     * Check if user has a specific role.
     *
     * @param  array|string  $roles
     */
    public function hasRole($roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return in_array($this->role, $roles);
    }

    /**
     * Register media collections for the model.
     */
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('avatar')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                // First conversion: small
                $this
                    ->addMediaConversion('small')
                    ->fit(Fit::Max, 80, 80)
                    ->keepOriginalImageFormat();

                // Second conversion: medium
                $this
                    ->addMediaConversion('medium')
                    ->fit(Fit::Max, 320, 320)
                    ->keepOriginalImageFormat();
            });
    }

    /**
     * Retrieve the value of an attribute or a dynamically generated image URL.
     *
     * @param  string  $key  The attribute key or the image key with a specific conversion.
     * @return mixed The value of the attribute or the image conversion URL.
     *
     * @throws \Illuminate\Database\Eloquent\RelationNotFoundException If the relationship is not found.
     */
    public function __get($key)
    {
        if (substr($key, 0, 7) === 'avatar-') {
            return $this->getAvatarUrl(substr($key, 7, strlen($key)));
        }

        return parent::__get($key);
    }

    /**
     * Get the avatar URL.
     *
     * @return string|null
     */
    public function getAvatarAttribute()
    {
        return $this->getAvatarUrl();
    }

    /**
     * Get the avatar URL with a specific conversion.
     *
     * @param  string|null  $conversion
     * @return string|null
     */
    public function getAvatarUrl($conversion = '')
    {
        if ($this->getFirstMediaUrl('avatar') !== '') {
            $media = $this->getMedia('avatar');

            // Get the resized image URL with the specified conversion
            return $media[0]->getFullUrl($conversion);
        } else {
            return null;
        }
    }

    /**
     * Checks if a card is related to the staff's partner and club.
     *
     * @return bool
     */
    public function isRelatedToCard(Card $card)
    {
        // Retrieve associated partner and club for the staff
        $partner = $this->partner;
        $club = $this->club;

        // Return false if either the partner or club does not exist
        if (! $partner || ! $club) {
            return false;
        }

        // Return true if the card's creator is the partner and the card's club is the staff's club.
        // Otherwise, return false.
        return $card->created_by == $partner->id && $card->club_id == $club->id;
    }

    /**
     * Get the club associated with the staff member.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the partner associated with the staff member.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class, 'created_by');
    }

    /**
     * Get the transactions associated with the staff member.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the stamp transactions processed by this staff member.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stampTransactions()
    {
        return $this->hasMany(StampTransaction::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VOUCHER RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get all voucher redemptions processed by this staff member.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function voucherRedemptions()
    {
        return $this->hasMany(VoucherRedemption::class);
    }
}
