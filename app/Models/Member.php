<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Represents a Member (end customer) in the application.
 * Members collect points, redeem rewards, and manage their loyalty wallets.
 */

namespace App\Models;

use App\QueryBuilders\MemberQueryBuilder;
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
 * Class Member
 *
 * Represents a Member in the application.
 */
class Member extends Authenticatable implements HasLocalePreference, HasMedia
{
    use HasApiTokens, HasFactory, HasIdentifier, HasSchemaAccessors, HasUuids, InteractsWithMedia, LogsModelActivity, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'members';

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
        'last_login_at' => 'datetime',
        'first_interaction_at' => 'datetime',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
        'is_active',
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

    public function newEloquentBuilder($query)
    {
        return new MemberQueryBuilder($query);
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
     * Get the cards associated with the member.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function cards()
    {
        return $this->belongsToMany(Card::class, 'card_member')->withTimestamps();
    }

    /**
     * Get the vouchers that the member has claimed.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function vouchers()
    {
        return $this->belongsToMany(Voucher::class, 'member_voucher')
            ->withPivot('claimed_via')
            ->withTimestamps()
            ->orderByDesc('member_voucher.created_at');
    }

    /**
     * Get the transactions associated with the member.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the point request links associated with the member.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pointRequests()
    {
        return $this->hasMany(PointRequest::class, 'created_by');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STAMP CARD RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get all stamp card enrollments for the member.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stampCardEnrollments()
    {
        return $this->hasMany(StampCardMember::class);
    }

    /**
     * Get all stamp cards the member is enrolled in (through pivot).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function stampCards()
    {
        return $this->belongsToMany(StampCard::class, 'stamp_card_member')
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
     * Get all stamp transactions for the member.
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
     * Get all voucher redemptions for the member.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function voucherRedemptions()
    {
        return $this->hasMany(VoucherRedemption::class);
    }

    /**
     * Get available vouchers for this member in a specific club.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableVouchers(Club $club)
    {
        return Voucher::query()
            ->forClub($club->id)
            ->available()
            ->forMember($this)
            ->where(function ($q) {
                $q->where('is_public', true)
                    ->orWhere('target_member_id', $this->id);
            })
            ->get()
            ->filter(fn ($voucher) => $voucher->canBeUsedBy($this));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TIER RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get all tier assignments for the member.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function memberTiers()
    {
        return $this->hasMany(MemberTier::class);
    }

    /**
     * Get all tiers the member has been assigned to (through pivot).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tiers()
    {
        return $this->belongsToMany(Tier::class, 'member_tiers')
            ->withPivot(['achieved_at', 'expires_at', 'qualifying_points', 'qualifying_spend', 'qualifying_transactions', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get the member's current tier for a specific club.
     */
    public function getTierForClub(string|Club $club): ?Tier
    {
        $clubId = $club instanceof Club ? $club->id : $club;

        $memberTier = $this->memberTiers()
            ->where('club_id', $clubId)
            ->where('is_active', true)
            ->with('tier')
            ->first();

        return $memberTier?->tier;
    }

    /**
     * Get the member's current tier assignment for a specific club.
     */
    public function getMemberTierForClub(string|Club $club): ?MemberTier
    {
        $clubId = $club instanceof Club ? $club->id : $club;

        return $this->memberTiers()
            ->where('club_id', $clubId)
            ->where('is_active', true)
            ->with('tier')
            ->first();
    }

    /**
     * Get the tier multiplier for a specific club.
     */
    public function getTierMultiplierForClub(string|Club $club): float
    {
        $tier = $this->getTierForClub($club);

        return $tier ? (float) $tier->points_multiplier : 1.00;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // ANONYMOUS MEMBER HELPERS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Check if this member is anonymous (no email linked).
     *
     * Anonymous members use the loyalty system without registering.
     * They're identified by a device-bound code stored in localStorage.
     */
    public function isAnonymous(): bool
    {
        return is_null($this->email);
    }

    /**
     * Check if this member has linked an email (registered).
     */
    public function isRegistered(): bool
    {
        return !is_null($this->email);
    }

    /**
     * Get the display name for this member.
     *
     * For anonymous members: Returns their device_code (e.g., "R245")
     * For registered members: Returns name or email
     *
     * Design: We skip the "Guest" prefix for anonymous members because:
     * - Space efficiency in UI (header, lists, etc.)
     * - The code format (R245) is obviously generated, no prefix needed
     */
    public function getDisplayNameFormatted(): string
    {
        if ($this->isAnonymous()) {
            // Just the code — it's obviously generated, no "Guest" prefix needed
            return $this->device_code ?? $this->name ?? '----';
        }

        return $this->name ?? $this->email ?? trans('common.member');
    }

    /**
     * Can this member receive email notifications?
     * Only registered members with emails should receive notifications.
     */
    public function canReceiveEmail(): bool
    {
        return $this->isRegistered()
            && !is_null($this->email)
            && $this->accepts_emails;
    }

    /**
     * Generate a unique device code for anonymous members.
     * Uses a safe character set (excludes 0, O, 1, I, L for readability).
     *
     * @param  int  $length  Code length (4-12 characters)
     * @param  int  $maxAttempts  Maximum generation attempts before failure
     * @return string The generated unique code
     *
     * @throws \RuntimeException If a unique code cannot be generated
     */
    public static function generateDeviceCode(int $length = 4, int $maxAttempts = 10): string
    {
        // Safe characters: A-Z excluding I, L, O + 2-9 (excluding 0, 1)
        // Total: 23 letters + 8 numbers = 31 characters
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $charsLength = strlen($chars);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $chars[random_int(0, $charsLength - 1)];
            }

            // Check uniqueness
            if (!self::where('device_code', $code)->exists()) {
                return $code;
            }
        }

        // Fallback: increase length for the final attempt
        $code = '';
        $fallbackLength = $length + 2;
        for ($i = 0; $i < $fallbackLength; $i++) {
            $code .= $chars[random_int(0, $charsLength - 1)];
        }

        if (!self::where('device_code', $code)->exists()) {
            return $code;
        }

        throw new \RuntimeException('Could not generate unique device code after ' . $maxAttempts . ' attempts');
    }

    /**
     * Find a member by device UUID.
     */
    public static function findByDeviceUuid(string $uuid): ?self
    {
        return self::where('device_uuid', $uuid)->first();
    }

    /**
     * Find a member by device code.
     */
    public static function findByDeviceCode(string $code): ?self
    {
        return self::where('device_code', strtoupper(trim($code)))->first();
    }

    /**
     * Find a member by any identifier (email, unique_identifier, device_code, member_number).
     */
    public static function findByAnyIdentifier(string $identifier): ?self
    {
        $identifier = trim($identifier);

        return self::query()
            ->where(function ($q) use ($identifier) {
                $q->where('email', strtolower($identifier))
                    ->orWhere('unique_identifier', $identifier)
                    ->orWhere('device_code', strtoupper($identifier))
                    ->orWhere('member_number', $identifier);
            })
            ->first();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // INTERACTION TRACKING
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Check if this member has interacted with the platform.
     *
     * "Interaction" means any meaningful engagement:
     * - Earned points, stamps, or vouchers
     * - Claimed a reward
     * - Followed a card
     * - Made any transaction
     *
     * Ghost members (created but never interacted) have NULL first_interaction_at.
     */
    public function hasInteracted(): bool
    {
        return !is_null($this->first_interaction_at);
    }

    /**
     * Check if this member is a "ghost" (created but never engaged).
     *
     * Ghost members can be safely purged after a retention period.
     */
    public function isGhost(): bool
    {
        return is_null($this->first_interaction_at);
    }

    /**
     * Record the member's first interaction with the platform.
     *
     * This is idempotent — calling multiple times only sets the first timestamp.
     * Call this whenever the member performs a meaningful action.
     */
    public function recordInteraction(): void
    {
        if (is_null($this->first_interaction_at)) {
            $this->update(['first_interaction_at' => now()]);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // SCOPES: Member State Queries
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Scope: Only members who have interacted with the platform.
     *
     * Usage: Member::active()->get()
     */
    public function scopeActive($query)
    {
        return $query->whereNotNull('first_interaction_at');
    }

    /**
     * Scope: Only ghost members (created but never interacted).
     *
     * Usage: Member::ghost()->olderThan(6)->count()
     */
    public function scopeGhost($query)
    {
        return $query->whereNull('first_interaction_at');
    }

    /**
     * Scope: Only anonymous members (no email linked).
     *
     * Usage: Member::anonymous()->active()->get()
     */
    public function scopeAnonymous($query)
    {
        return $query->whereNull('email');
    }

    /**
     * Scope: Only registered members (have email).
     *
     * Usage: Member::registered()->get()
     */
    public function scopeRegistered($query)
    {
        return $query->whereNotNull('email');
    }

    /**
     * Scope: Members older than X months.
     *
     * Usage: Member::ghost()->olderThan(6)->delete()
     */
    public function scopeOlderThan($query, int $months)
    {
        return $query->where('created_at', '<', now()->subMonths($months));
    }

    /**
     * Scope: Purgeable ghost members.
     *
     * These are anonymous members who:
     * - Have never interacted (ghost)
     * - Were created more than 6 months ago
     *
     * Usage: Member::purgeable()->delete()
     */
    public function scopePurgeable($query, int $retentionMonths = 6)
    {
        return $query
            ->ghost()
            ->anonymous()
            ->olderThan($retentionMonths);
    }
}

