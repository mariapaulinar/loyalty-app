<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Represents a Partner (Business Owner) in the application.
 * Partners create and manage loyalty programs, cards, rewards, staff, and clubs.
 */

namespace App\Models;

use App\QueryBuilders\PartnerQueryBuilder;
use App\Traits\HasPlan;
use App\Traits\HasSchemaAccessors;
use App\Traits\LogsModelActivity;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\File;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Class Partner
 *
 * Represents a Partner in the application.
 */
class Partner extends Authenticatable implements HasLocalePreference, HasMedia
{
    use Billable, HasApiTokens, HasFactory, HasPlan, HasSchemaAccessors, HasUuids, InteractsWithMedia, LogsModelActivity, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'partners';

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
        'social_links' => 'array',
        'opening_hours' => 'array',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should not be exposed by API and other public responses.
     *
     * @var array
     */
    protected $hiddenForPublic = [
        'affiliate_id',
        'role',
        'member_number',
        'display_name',
        'birthday',
        'gender',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'account_expires_at',
        'premium_expires_at',
        'country_code',
        'phone_prefix',
        'phone_country',
        'phone',
        'phone_e164',
        'is_vip',
        'accepts_text_messages',
        'is_undeletable',
        'is_uneditable',
        'number_of_emails_received',
        'number_of_text_messages_received',
        'number_of_reviews_written',
        'number_of_ratings_given',
        'meta',
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

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'plan' => 'tier1',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Partner $partner) {
            // Ensure default plan is set if not specified
            if (empty($partner->plan)) {
                $partner->plan = static::getDefaultPlan();
            }
        });
    }

    public function newEloquentBuilder($query)
    {
        return new PartnerQueryBuilder($query);
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

        $role = $this->getAttribute('role');

        if ($role === null && $this->exists) {
             // Fallback: This should ideally not happen for Auth::user(), but if it does, 
             // reload the model or query the role directly to prevent 403 lockouts.
             $role = $this->fresh()->getAttribute('role');
        }

        return in_array($role, $roles);
    }

    /**
     * Register media collections for the model.
     */
    public function registerMediaCollections(): void
    {
        // Avatar/Logo collection
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

        // Cover image collection for business profile
        $this
            ->addMediaCollection('cover')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                // Thumbnail for list views
                $this
                    ->addMediaConversion('thumb')
                    ->fit(Fit::Crop, 400, 200)
                    ->keepOriginalImageFormat();

                // Large for hero display
                $this
                    ->addMediaConversion('large')
                    ->fit(Fit::Max, 1920, 960)
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

        if (substr($key, 0, 6) === 'cover-') {
            return $this->getCoverUrl(substr($key, 6, strlen($key)));
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
     * Get the cover image URL.
     *
     * @return string|null
     */
    public function getCoverAttribute()
    {
        return $this->getCoverUrl();
    }

    /**
     * Get the cover image URL with a specific conversion.
     *
     * @param  string|null  $conversion
     * @return string|null
     */
    public function getCoverUrl($conversion = '')
    {
        if ($this->getFirstMediaUrl('cover') !== '') {
            $media = $this->getMedia('cover');

            return $media[0]->getFullUrl($conversion);
        } else {
            return null;
        }
    }

    /**
     * Get the network associated with the partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function network()
    {
        return $this->belongsTo(Network::class);
    }

    /**
     * Get the clubs associated with the partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clubs()
    {
        return $this->hasMany(Club::class, 'created_by');
    }

    /**
     * Get the rewards associated with the partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function rewards()
    {
        return $this->hasMany(Reward::class, 'created_by');
    }

    /**
     * Get the cards associated with the partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cards()
    {
        return $this->hasMany(Card::class, 'created_by');
    }

    /**
     * Get the stamp cards associated with the partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stampCards()
    {
        return $this->hasMany(StampCard::class, 'created_by');
    }

    /**
     * Get the vouchers associated with the partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vouchers()
    {
        return $this->hasMany(Voucher::class, 'created_by');
    }

    /**
     * Get the transactions associated with the partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    /**
     * Get the staff members associated with the partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function staff()
    {
        return $this->hasMany(Staff::class, 'created_by');
    }

    /**
     * Get the email campaigns associated with the partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function emailCampaigns()
    {
        return $this->hasMany(EmailCampaign::class);
    }

    // ═══════════════════════════════════════════════════════════════════
    // EMAIL CAMPAIGN SENDER SETTINGS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Get the sender name for email campaigns.
     *
     * Falls back in order:
     * 1. Configured sender name in meta
     * 2. Partner's company name
     * 3. System default from config
     *
     * @return string The sender name to display in emails
     */
    public function getCampaignSenderName(): string
    {
        return ($this->meta ?? [])['email_campaign_sender_name']
            ?? $this->name
            ?? config('default.mail_from_name');
    }

    /**
     * Get the reply-to email for campaigns.
     *
     * Falls back in order:
     * 1. Configured reply-to in meta
     * 2. Partner's own email
     * 3. null (no reply-to header)
     *
     * @return string|null The reply-to email address
     */
    public function getCampaignReplyTo(): ?string
    {
        return ($this->meta ?? [])['email_campaign_reply_to']
            ?? $this->email
            ?? null;
    }

    /**
     * Update campaign sender settings.
     *
     * Stores settings in the meta JSON column to avoid
     * database migrations for optional features.
     *
     * @param  string|null  $senderName  Custom sender name
     * @param  string|null  $replyTo  Reply-to email address
     */
    public function updateCampaignSenderSettings(?string $senderName, ?string $replyTo): void
    {
        $meta = $this->meta ?? [];

        if ($senderName !== null) {
            $meta['email_campaign_sender_name'] = $senderName;
        }

        if ($replyTo !== null) {
            $meta['email_campaign_reply_to'] = $replyTo;
        }

        $this->update(['meta' => $meta]);
    }
    // ═══════════════════════════════════════════════════════════════════
    // PERMISSIONS & LIMITS (Stored in meta)
    // ═══════════════════════════════════════════════════════════════════

    public function getCardsOnHomepageAttribute()
    {
        return ($this->meta ?? [])['cards_on_homepage'] ?? false;
    }

    public function setCardsOnHomepageAttribute($value)
    {
        $meta = $this->meta ?? [];
        $meta['cards_on_homepage'] = $value;
        $this->meta = $meta;
    }

    public function getLoyaltyCardsPermissionAttribute()
    {
        $meta = $this->meta ?? [];
        if (is_array($meta) && array_key_exists('loyalty_cards_permission', $meta)) {
            return (bool) $meta['loyalty_cards_permission'];
        }
        // Use getAttribute() which properly handles session-hydrated models
        $role = $this->getAttribute('role');
        if ($role == 1) return true;
        return $this->exists ? true : null;
    }

    public function setLoyaltyCardsPermissionAttribute($value)
    {
        $meta = $this->meta ?? [];
        $meta['loyalty_cards_permission'] = $value;
        $this->meta = $meta;
    }

    public function getLoyaltyCardsLimitAttribute()
    {
        return ($this->meta ?? [])['loyalty_cards_limit'] ?? -1;
    }

    public function setLoyaltyCardsLimitAttribute($value)
    {
        $meta = $this->meta ?? [];
        $meta['loyalty_cards_limit'] = $value;
        $this->meta = $meta;
    }

    public function getRewardsLimitAttribute()
    {
        return ($this->meta ?? [])['rewards_limit'] ?? -1;
    }

    public function setRewardsLimitAttribute($value)
    {
        $meta = $this->meta ?? [];
        $meta['rewards_limit'] = $value;
        $this->meta = $meta;
    }

    public function getStampCardsPermissionAttribute()
    {
        $meta = $this->meta ?? [];
        if (is_array($meta) && array_key_exists('stamp_cards_permission', $meta)) {
            return (bool) $meta['stamp_cards_permission'];
        }
        $role = $this->getAttribute('role');
        if ($role == 1) return true;
        return $this->exists ? true : null;
    }

    public function setStampCardsPermissionAttribute($value)
    {
        $meta = $this->meta ?? [];
        $meta['stamp_cards_permission'] = $value;
        $this->meta = $meta;
    }

    public function getStampCardsLimitAttribute()
    {
        return ($this->meta ?? [])['stamp_cards_limit'] ?? -1;
    }

    public function setStampCardsLimitAttribute($value)
    {
        $meta = $this->meta ?? [];
        $meta['stamp_cards_limit'] = $value;
        $this->meta = $meta;
    }

    public function getVouchersPermissionAttribute()
    {
        $meta = $this->meta ?? [];
        if (is_array($meta) && array_key_exists('vouchers_permission', $meta)) {
            return (bool) $meta['vouchers_permission'];
        }
        $role = $this->getAttribute('role');
        if ($role == 1) return true;
        return $this->exists ? true : null;
    }

    public function setVouchersPermissionAttribute($value)
    {
        $meta = $this->meta ?? [];
        $meta['vouchers_permission'] = $value;
        $this->meta = $meta;
    }

    public function getVoucherBatchesPermissionAttribute()
    {
        $meta = $this->meta ?? [];
        if (is_array($meta) && array_key_exists('voucher_batches_permission', $meta)) {
            return (bool) $meta['voucher_batches_permission'];
        }
        $role = $this->getAttribute('role');
        if ($role == 1) return true;
        return $this->exists ? true : null;
    }

    public function setVoucherBatchesPermissionAttribute($value)
    {
        $meta = $this->meta ?? [];
        $meta['voucher_batches_permission'] = $value;
        $this->meta = $meta;
    }

    public function getVouchersLimitAttribute()
    {
        return ($this->meta ?? [])['vouchers_limit'] ?? -1;
    }

    public function setVouchersLimitAttribute($value)
    {
        $meta = $this->meta ?? [];
        $meta['vouchers_limit'] = $value;
        $this->meta = $meta;
    }

    public function getVouchersOnHomepageAttribute()
    {
        return ($this->meta ?? [])['vouchers_on_homepage'] ?? ($this->exists ? true : null);
    }

    public function setVouchersOnHomepageAttribute($value)
    {
        $meta = $this->meta ?? [];
        $meta['vouchers_on_homepage'] = $value;
        $this->meta = $meta;
    }

    public function getStaffMembersLimitAttribute()
    {
        return ($this->meta ?? [])['staff_members_limit'] ?? -1;
    }

    public function setStaffMembersLimitAttribute($value)
    {
        $meta = $this->meta ?? [];
        $meta['staff_members_limit'] = $value;
        $this->meta = $meta;
    }

    public function getEmailCampaignsPermissionAttribute()
    {
        $meta = $this->meta ?? [];
        if (is_array($meta) && array_key_exists('email_campaigns_permission', $meta)) {
            return (bool) $meta['email_campaigns_permission'];
        }
        $role = $this->getAttribute('role');
        if ($role == 1) return true;
        return $this->exists ? true : null;
    }

    public function setEmailCampaignsPermissionAttribute($value)
    {
        $meta = $this->meta ?? [];
        $meta['email_campaigns_permission'] = $value;
        $this->meta = $meta;
    }

    public function getActivityPermissionAttribute()
    {
        $meta = $this->meta ?? [];
        if (is_array($meta) && array_key_exists('activity_permission', $meta)) {
            return (bool) $meta['activity_permission'];
        }
        $role = $this->getAttribute('role');
        if ($role == 1) return true;
        return $this->exists ? true : null;
    }

    public function setActivityPermissionAttribute($value)
    {
        $meta = $this->meta ?? [];
        $meta['activity_permission'] = $value;
        $this->meta = $meta;
    }

    public function getAgentApiPermissionAttribute()
    {
        $meta = $this->meta ?? [];
        if (is_array($meta) && array_key_exists('agent_api_permission', $meta)) {
            return (bool) $meta['agent_api_permission'];
        }
        // Default: false (must be explicitly enabled by admin for safety)
        // Unlike other permissions that default to true for role=1,
        // the agent API is opt-in to prevent accidental key exposure.
        return false;
    }

    public function setAgentApiPermissionAttribute($value)
    {
        $meta = $this->meta ?? [];
        $meta['agent_api_permission'] = $value;
        $this->meta = $meta;
    }

    public function getAgentKeysLimitAttribute()
    {
        return ($this->meta ?? [])['agent_keys_limit'] ?? 5;
    }

    public function setAgentKeysLimitAttribute($value)
    {
        $meta = $this->meta ?? [];
        $meta['agent_keys_limit'] = $value;
        $this->meta = $meta;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // BUSINESS PROFILE HELPERS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Check if partner has a configured business profile.
     * The business card should only be shown if business_name is set.
     */
    public function hasBusinessProfile(): bool
    {
        return !empty($this->business_name);
    }

    /**
     * Get the formatted full address.
     */
    public function getFullAddress(): ?string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
        ]);

        return count($parts) > 0 ? implode(', ', $parts) : null;
    }

    /**
     * Get active social media links (non-empty values only).
     *
     * @return array<string, string>
     */
    public function getActiveSocialLinks(): array
    {
        $links = $this->social_links ?? [];
        
        return array_filter($links, fn($url) => !empty($url));
    }

    /**
     * Check if the business is currently open based on opening hours.
     */
    public function isCurrentlyOpen(): bool
    {
        $hours = $this->opening_hours;
        
        if (empty($hours)) {
            return false;
        }

        $timezone = $this->time_zone ?? config('app.timezone', 'UTC');
        $now = now()->setTimezone($timezone);
        $dayOfWeek = strtolower($now->format('l'));
        
        $todayHours = $hours[$dayOfWeek] ?? null;
        
        if (!$todayHours || ($todayHours['closed'] ?? false)) {
            return false;
        }

        $openTime = $todayHours['open'] ?? null;
        $closeTime = $todayHours['close'] ?? null;

        if (!$openTime || !$closeTime) {
            return false;
        }

        $currentTime = $now->format('H:i');
        
        return $currentTime >= $openTime && $currentTime <= $closeTime;
    }

    /**
     * Get today's opening hours as a formatted string.
     */
    public function getTodaysHours(): ?string
    {
        $hours = $this->opening_hours;
        
        if (empty($hours)) {
            return null;
        }

        $timezone = $this->time_zone ?? config('app.timezone', 'UTC');
        $now = now()->setTimezone($timezone);
        $dayOfWeek = strtolower($now->format('l'));
        
        $todayHours = $hours[$dayOfWeek] ?? null;
        
        if (!$todayHours) {
            return null;
        }

        if ($todayHours['closed'] ?? false) {
            return trans('common.closed');
        }

        $openTime = $todayHours['open'] ?? null;
        $closeTime = $todayHours['close'] ?? null;

        if (!$openTime || !$closeTime) {
            return null;
        }

        return "{$openTime} – {$closeTime}";
    }
}
