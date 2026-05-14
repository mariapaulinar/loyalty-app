<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Club Integration Model
 *
 * Represents a connection between a loyalty club and an external e-commerce
 * platform (Shopify, WooCommerce, etc.). This is the central entity for all
 * integration-related operations.
 *
 * Core Responsibilities:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - Store and manage OAuth credentials (encrypted access tokens)
 * - Track integration health and lifecycle status
 * - Provide configuration for point calculations and widget display
 * - Generate secure keys for webhook verification and API access
 *
 * Security Model:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - access_token: Laravel's encrypted cast — never stored in plaintext
 * - webhook_secret: Hidden from serialization, used for HMAC verification
 * - public_api_key: Safe for client-side use (prefixed rl_pub_)
 *
 * Settings Architecture:
 * ─────────────────────────────────────────────────────────────────────────────────
 * Integration settings use a two-tier approach:
 *
 * 1. Global defaults: Stored in SettingsService
 *    Key format: integrations.shopify.{setting_key}
 *    Example: integrations.shopify.points_use_card_rules
 *
 * 2. Per-integration overrides: Stored in this model's `settings` JSON column
 *    Used when a specific integration needs different behavior
 *
 * Lookup order: Local settings → Global SettingsService → Hardcoded default
 *
 * @see App\Enums\IntegrationPlatform
 * @see App\Enums\IntegrationStatus
 * @see App\Services\SettingsService
 *
 * @property string $id UUID primary key
 * @property string $club_id Foreign key to clubs table
 * @property string $platform Platform identifier (shopify, woocommerce)
 * @property IntegrationStatus $status Current lifecycle state
 * @property string|null $store_identifier Platform-specific store ID
 * @property string|null $access_token Encrypted OAuth token
 * @property string|null $webhook_secret HMAC secret for webhook verification
 * @property string $public_api_key Client-safe API key
 * @property array|null $settings Platform-specific configuration overrides
 * @property \Carbon\Carbon|null $last_sync_at Last successful sync
 * @property string|null $last_error Most recent error message
 * @property \Carbon\Carbon|null $last_error_at When last error occurred
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Club $club
 * @property-read \Illuminate\Database\Eloquent\Collection<IntegrationWebhookReceipt> $webhookReceipts
 */

namespace App\Models;

use App\Enums\IntegrationPlatform;
use App\Enums\IntegrationStatus;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ClubIntegration extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    // ─────────────────────────────────────────────────────────────────────────
    // SETTINGS KEY CONSTANTS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Global SettingsService key prefix for Shopify integration settings.
     */
    private const SETTINGS_PREFIX = 'integrations.shopify.';

    /**
     * Mapping of setting names to their SettingsService keys and defaults.
     *
     * Format: 'local_key' => ['global_key', default_value]
     *
     * @var array<string, array{0: string, 1: mixed}>
     */
    private const SETTINGS_MAP = [
        // Point calculation settings
        'points_use_card_rules' => ['points_use_card_rules', true],
        'points_per_currency_fallback' => ['points_per_currency_fallback', 10],
        'points_rounding_fallback' => ['points_rounding_fallback', 'down'],
        'award_on' => ['award_on', 'order_paid'],
        'deduct_on_refund' => ['deduct_on_refund', true],
        'first_order_bonus' => ['first_order_bonus', 0],

        // Discount settings
        'use_automatic_discounts' => ['use_automatic_discounts', true],

        // Widget settings
        'widget.program_name' => ['widget.program_name', 'Rewards'],
        'widget.primary_color' => ['widget.primary_color', '#F59E0B'],
        'widget.mode' => ['widget.mode', 'auto'],
        'widget.position' => ['widget.position', 'bottom-right'],
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // MODEL CONFIGURATION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * The table associated with the model.
     */
    protected $table = 'club_integrations';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     * Using guarded = [] allows all attributes (we trust internal code).
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * Notable casts:
     * - status: IntegrationStatus enum for type-safe state machine
     * - access_token: encrypted to protect OAuth credentials
     * - settings: array for JSON column convenience
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => IntegrationStatus::class,
        'access_token' => 'encrypted',
        'settings' => 'array',
        'last_sync_at' => 'datetime',
        'last_error_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * Security: Prevents accidental exposure of sensitive credentials
     * in API responses, logs, or debug output.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'access_token',
        'webhook_secret',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // MODEL LIFECYCLE HOOKS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * The "booted" method of the model.
     *
     * Auto-generates secure keys on creation:
     * - public_api_key: rl_pub_ prefix for easy identification
     * - webhook_secret: 32-character random string for HMAC
     */
    protected static function booted(): void
    {
        static::creating(function (self $integration): void {
            // Generate public API key if not provided
            // Format: rl_pub_XXXXXXXXXXXXXXXXXXXXXXXX (28 chars total)
            $integration->public_api_key ??= 'rl_pub_'.Str::random(24);

            // Generate webhook secret if not provided
            // 32 characters of cryptographically secure randomness
            $integration->webhook_secret ??= Str::random(32);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get the club (loyalty program) that owns this integration.
     *
     * @return BelongsTo<Club, ClubIntegration>
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the webhook receipt log for this integration.
     *
     * Receipts provide idempotency (prevent duplicate processing)
     * and debugging (what webhooks were received and their outcomes).
     *
     * @return HasMany<IntegrationWebhookReceipt>
     */
    public function webhookReceipts(): HasMany
    {
        return $this->hasMany(IntegrationWebhookReceipt::class, 'club_integration_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SETTINGS ACCESS (TWO-TIER: LOCAL → GLOBAL)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get a Shopify setting with local-then-global fallback.
     *
     * Lookup order:
     * 1. Local: Check this integration's `settings` JSON column
     * 2. Global: Check SettingsService (integrations.shopify.{key})
     * 3. Default: Return hardcoded default from SETTINGS_MAP
     *
     * @param  string  $key  Setting key (e.g., 'points_use_card_rules')
     * @return mixed Setting value
     */
    public function getShopifySetting(string $key): mixed
    {
        // 1. Check local settings JSON (per-integration override)
        $localValue = Arr::get($this->settings ?? [], $key);
        if ($localValue !== null) {
            return $localValue;
        }

        // 2. Get from SETTINGS_MAP or construct the global key
        $globalKey = self::SETTINGS_PREFIX.$key;
        $default = self::SETTINGS_MAP[$key][1] ?? null;

        // 3. Fetch from SettingsService (lazy-initializes if needed)
        return app(SettingsService::class)->get($globalKey, $default);
    }

    /**
     * Set a local setting override for this integration.
     *
     * Stores the value in this integration's `settings` JSON column,
     * which takes precedence over global SettingsService values.
     *
     * @param  string  $key  Setting key
     * @param  mixed  $value  Value to store
     */
    public function setLocalSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        Arr::set($settings, $key, $value);
        $this->settings = $settings;
        $this->save();
    }

    /**
     * Remove a local setting override, reverting to global default.
     *
     * @param  string  $key  Setting key to remove
     */
    public function removeLocalSetting(string $key): void
    {
        $settings = $this->settings ?? [];
        Arr::forget($settings, $key);
        $this->settings = $settings;
        $this->save();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POINT CALCULATION SETTINGS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Whether to use the card's rules for point calculations.
     *
     * When true: Points calculated using card.points_per_currency
     * When false: Use getFallbackPointsPerCurrency() value
     *
     * @see integrations.shopify.points_use_card_rules
     */
    public function useCardRules(): bool
    {
        return (bool) $this->getShopifySetting('points_use_card_rules');
    }

    /**
     * Fallback points-per-currency when card rules disabled.
     *
     * Example: 10 = earn 10 points per $1 spent
     *
     * @see integrations.shopify.points_per_currency_fallback
     */
    public function getFallbackPointsPerCurrency(): int
    {
        return (int) $this->getShopifySetting('points_per_currency_fallback');
    }

    /**
     * Rounding strategy for point calculations.
     *
     * Possible values: 'down', 'up', 'nearest'
     *
     * @see integrations.shopify.points_rounding_fallback
     */
    public function getFallbackRounding(): string
    {
        return (string) $this->getShopifySetting('points_rounding_fallback');
    }

    /**
     * When to award points: 'order_paid' or 'order_fulfilled'.
     *
     * @see integrations.shopify.award_on
     */
    public function getAwardOn(): string
    {
        return (string) $this->getShopifySetting('award_on');
    }

    /**
     * Whether to deduct points when a refund occurs.
     *
     * When true: Refunded orders trigger point deduction
     * When false: Points remain even after refund (generous policy)
     *
     * @see integrations.shopify.deduct_on_refund
     */
    public function shouldDeductOnRefund(): bool
    {
        return (bool) $this->getShopifySetting('deduct_on_refund');
    }

    /**
     * Bonus points for member's first order through this integration.
     *
     * 0 = no bonus, N = N extra points on first purchase
     *
     * @see integrations.shopify.first_order_bonus
     */
    public function getFirstOrderBonus(): int
    {
        return (int) $this->getShopifySetting('first_order_bonus');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DISCOUNT SETTINGS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Whether to use Shopify automatic discounts vs. discount codes.
     *
     * Automatic: Better UX, applied at checkout without code entry
     * Codes: Works on all Shopify plans, requires customer action
     *
     * @see integrations.shopify.use_automatic_discounts
     */
    public function useAutomaticDiscounts(): bool
    {
        return (bool) $this->getShopifySetting('use_automatic_discounts');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WIDGET CONFIGURATION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Program name displayed in the widget.
     *
     * Example: "Star Rewards", "VIP Club", etc.
     *
     * @see integrations.shopify.widget.program_name
     */
    public function getWidgetProgramName(): string
    {
        return (string) $this->getShopifySetting('widget.program_name');
    }

    /**
     * Primary color for widget theming (hex format).
     *
     * Example: '#F59E0B' (amber), '#3B82F6' (blue)
     *
     * @see integrations.shopify.widget.primary_color
     */
    public function getWidgetPrimaryColor(): string
    {
        return (string) $this->getShopifySetting('widget.primary_color');
    }

    /**
     * Widget color mode: 'light', 'dark', or 'auto'.
     *
     * auto: Follows system/browser preference
     *
     * @see integrations.shopify.widget.mode
     */
    public function getWidgetMode(): string
    {
        return (string) $this->getShopifySetting('widget.mode');
    }

    /**
     * Widget position on the storefront.
     *
     * Options: 'bottom-right', 'bottom-left', 'top-right', 'top-left'
     *
     * @see integrations.shopify.widget.position
     */
    public function getWidgetPosition(): string
    {
        return (string) $this->getShopifySetting('widget.position');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STATUS MANAGEMENT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Mark the integration as active and processing.
     *
     * Called after successful OAuth or manual reactivation.
     * Activity logging happens at the service/controller layer.
     */
    public function markActive(): void
    {
        $this->status = IntegrationStatus::ACTIVE;
        $this->save();
    }

    /**
     * Mark the integration as manually paused.
     *
     * Webhooks will be logged but not processed.
     */
    public function markPaused(): void
    {
        $this->status = IntegrationStatus::PAUSED;
        $this->save();
    }

    /**
     * Mark the integration as errored with a message.
     *
     * @param  string  $message  Error description for debugging
     */
    public function markError(string $message): void
    {
        $this->status = IntegrationStatus::ERROR;
        $this->last_error = $message;
        $this->last_error_at = now();
        $this->save();
    }

    /**
     * Mark the integration as disconnected.
     *
     * Called when the store uninstalls the app or revokes access.
     */
    public function markDisconnected(): void
    {
        $this->status = IntegrationStatus::DISCONNECTED;
        $this->save();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // QUERY SCOPES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Scope: Only active integrations.
     *
     * @param  Builder<ClubIntegration>  $query
     * @return Builder<ClubIntegration>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', IntegrationStatus::ACTIVE);
    }

    /**
     * Scope: Integrations that can process webhooks.
     *
     * Currently same as active(), but allows future expansion
     * (e.g., if we add a "processing" sub-state).
     *
     * @param  Builder<ClubIntegration>  $query
     * @return Builder<ClubIntegration>
     */
    public function scopeCanProcess(Builder $query): Builder
    {
        return $query->where('status', IntegrationStatus::ACTIVE);
    }

    /**
     * Scope: Only Shopify integrations.
     *
     * @param  Builder<ClubIntegration>  $query
     * @return Builder<ClubIntegration>
     */
    public function scopeShopify(Builder $query): Builder
    {
        return $query->where('platform', IntegrationPlatform::SHOPIFY->value);
    }

    /**
     * Scope: Integrations for a specific club.
     *
     * @param  Builder<ClubIntegration>  $query
     * @param  string|Club  $club  Club instance or UUID
     * @return Builder<ClubIntegration>
     */
    public function scopeForClub(Builder $query, string|Club $club): Builder
    {
        $clubId = $club instanceof Club ? $club->id : $club;

        return $query->where('club_id', $clubId);
    }
}
