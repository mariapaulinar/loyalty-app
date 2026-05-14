<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Admin;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * SettingsService - Centralized Settings Management
 *
 * Purpose:
 * Provides a unified interface for reading and writing application settings
 * with support for encryption, caching, and audit trails.
 *
 * Design Tenets:
 * - **Secure**: Sensitive settings are encrypted at rest
 * - **Fast**: Frequently accessed settings are cached
 * - **Auditable**: All changes are tracked with user attribution
 * - **Simple**: Clean API for get/set operations
 */
class SettingsService
{
    /**
     * Cache prefix for settings
     */
    private const CACHE_PREFIX = 'settings:';

    /**
     * Get a setting value
     *
     * @param  string  $key  Setting key (e.g., 'rewardloyalty.license_token')
     * @param  mixed  $default  Default value if setting doesn't exist
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Try cache first
        $cacheKey = self::CACHE_PREFIX.$key;
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $this->decryptIfNeeded($key, $cached);
        }

        // Fetch from database
        $setting = Setting::where('key', $key)->first();

        if (! $setting) {
            // Lazy initialization for PWA settings
            if ($this->isPwaKey($key)) {
                $this->initializePwaSettings();
                $setting = Setting::where('key', $key)->first();
            }

            // Lazy initialization for Shopify integration settings
            if ($this->isShopifyKey($key)) {
                $this->initializeShopifySettings();
                $setting = Setting::where('key', $key)->first();
            }

            if (! $setting) {
                return $default;
            }
        }

        // Cache if enabled
        if ($setting->is_cached && $setting->cache_ttl > 0) {
            Cache::put($cacheKey, $setting->value, $setting->cache_ttl);
        }

        $value = $this->decryptIfNeeded($key, $setting->value, $setting);

        // Type conversion based on stored type
        return $this->castValue($value, $setting->type);
    }

    /**
     * Cast value based on type
     */
    private function castValue(mixed $value, ?string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'number' => is_numeric($value) ? (float) $value : $value,
            default => $value,
        };
    }

    /**
     * Set a setting value
     *
     * @param  string  $key  Setting key
     * @param  mixed  $value  Value to store
     * @param  Admin|null  $user  User making the change (for audit)
     * @param  string|null  $type  Type hint for the value (string, boolean, integer, number)
     */
    public function set(string $key, mixed $value, ?Admin $user = null, ?string $type = null): Setting
    {
        $setting = Setting::where('key', $key)->first();

        // Determine the type if not provided
        if ($type === null) {
            $type = $this->inferType($value);
        }

        // Encrypt if this is an encrypted setting
        $storedValue = $value;
        if ($setting?->is_encrypted && ! empty($value)) {
            $storedValue = Crypt::encryptString(is_string($value) ? $value : json_encode($value));
        }

        $setting = Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
                'last_modified_by' => $user?->id,
                'last_modified_at' => now(),
            ]
        );

        // Clear cache
        Cache::forget(self::CACHE_PREFIX.$key);

        return $setting;
    }

    /**
     * Infer the type of a value
     */
    private function inferType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_float($value)) {
            return 'number';
        }
        if (is_array($value)) {
            return 'array';
        }

        return 'string';
    }

    /**
     * Delete a setting
     */
    public function delete(string $key): bool
    {
        Cache::forget(self::CACHE_PREFIX.$key);

        return Setting::where('key', $key)->delete() > 0;
    }

    /**
     * Check if a setting exists
     */
    public function has(string $key): bool
    {
        return Setting::where('key', $key)->exists();
    }

    /**
     * Get all settings in a category
     *
     * @return array<string, mixed>
     */
    public function getByCategory(string $category): array
    {
        $settings = Setting::where('category', $category)
            ->orderBy('sort_order')
            ->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = $this->decryptIfNeeded($setting->key, $setting->value, $setting);
        }

        return $result;
    }

    /**
     * Get all settings grouped by category
     *
     * Returns a nested array structure:
     * [
     *     'branding' => [
     *         'app_name' => ['value' => 'My App', 'type' => 'string'],
     *         'app_logo' => ['value' => 'https://...', 'type' => 'string'],
     *     ],
     *     'compliance' => [...]
     * ]
     *
     * @return array<string, array<string, array{value: mixed, type: string|null}>>
     */
    public function getAllGrouped(): array
    {
        $settings = Setting::orderBy('category')
            ->orderBy('sort_order')
            ->get();

        $result = [];
        foreach ($settings as $setting) {
            $category = $setting->category ?? 'general';
            $value = $this->decryptIfNeeded($setting->key, $setting->value, $setting);
            $castedValue = $this->castValue($value, $setting->type);

            $result[$category][$setting->key] = [
                'value' => $castedValue,
                'type' => $setting->type,
            ];
        }

        return $result;
    }

    /**
     * Clear all cached settings
     */
    public function clearCache(): void
    {
        $settings = Setting::where('is_cached', true)->pluck('key');

        foreach ($settings as $key) {
            Cache::forget(self::CACHE_PREFIX.$key);
        }
    }

    /**
     * Decrypt value if setting is marked as encrypted
     */
    private function decryptIfNeeded(string $key, mixed $value, ?Setting $setting = null): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Load setting if not provided
        if (! $setting) {
            $setting = Setting::where('key', $key)->first();
        }

        if (! $setting?->is_encrypted) {
            return $value;
        }

        try {
            $decrypted = Crypt::decryptString(is_string($value) ? $value : json_encode($value));

            // Try to decode JSON
            $decoded = json_decode($decrypted, true);

            return $decoded !== null ? $decoded : $decrypted;
        } catch (\Exception $e) {
            Log::error('Failed to decrypt setting', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if key is a PWA setting
     */
    private function isPwaKey(string $key): bool
    {
        return str_starts_with($key, 'pwa_');
    }

    /**
     * Check if key is a Shopify integration setting
     */
    private function isShopifyKey(string $key): bool
    {
        return str_starts_with($key, 'integrations.shopify.');
    }

    /**
     * Initialize PWA settings if they don't exist
     * Called automatically on first access (lazy initialization)
     */
    private function initializePwaSettings(): void
    {
        // Check if already initialized
        if (Setting::where('key', 'pwa_short_name')->exists()) {
            return;
        }

        $pwaSettings = [
            [
                'key' => 'pwa_app_name',
                'value' => null,
                'type' => 'string',
                'category' => 'pwa',
                'label' => 'PWA App Name',
                'description' => 'Full application name. Leave blank to use APP_NAME.',
                'is_cached' => true,
                'cache_ttl' => 3600,
            ],
            [
                'key' => 'pwa_short_name',
                'value' => 'Rewards',
                'type' => 'string',
                'category' => 'pwa',
                'label' => 'PWA Short Name',
                'description' => 'Short name for home screen (max 12 characters).',
                'is_cached' => true,
                'cache_ttl' => 3600,
            ],
            [
                'key' => 'pwa_description',
                'value' => 'Your digital loyalty cards',
                'type' => 'string',
                'category' => 'pwa',
                'label' => 'PWA Description',
                'description' => 'Description shown in install prompts.',
                'is_cached' => true,
                'cache_ttl' => 3600,
            ],
            [
                'key' => 'pwa_theme_color',
                'value' => '#F39C12',
                'type' => 'string',
                'category' => 'pwa',
                'label' => 'PWA Theme Color',
                'description' => 'Theme color for browser chrome (hex code).',
                'is_cached' => true,
                'cache_ttl' => 3600,
            ],
            [
                'key' => 'pwa_background_color',
                'value' => '#ffffff',
                'type' => 'string',
                'category' => 'pwa',
                'label' => 'PWA Background Color',
                'description' => 'Background color during app launch (hex code).',
                'is_cached' => true,
                'cache_ttl' => 3600,
            ],
        ];

        foreach ($pwaSettings as $settingData) {
            Setting::firstOrCreate(
                ['key' => $settingData['key']],
                $settingData
            );
        }

        // Attach default icons if they exist
        $this->attachDefaultPwaIcons();

        Log::info('PWA settings initialized automatically');
    }

    /**
     * Initialize Shopify integration settings if they don't exist.
     *
     * These are global defaults for all Shopify integrations. Individual
     * integrations can override these via their local `settings` JSON column.
     *
     * Settings Architecture:
     * ─────────────────────────────────────────────────────────────────────────────
     * - Global defaults: Stored here in SettingsService
     * - Per-integration: Stored in ClubIntegration.settings JSON
     * - Lookup order: Local override → Global default → Hardcoded fallback
     *
     * @see App\Models\ClubIntegration::getShopifySetting()
     */
    private function initializeShopifySettings(): void
    {
        // Check if already initialized
        if (Setting::where('key', 'integrations.shopify.points_use_card_rules')->exists()) {
            return;
        }

        $shopifySettings = [
            // ─────────────────────────────────────────────────────────────────
            // POINT CALCULATION SETTINGS
            // ─────────────────────────────────────────────────────────────────
            [
                'key' => 'integrations.shopify.points_use_card_rules',
                'value' => true,
                'type' => 'boolean',
                'category' => 'integrations',
                'label' => 'Use Card Rules',
                'description' => 'When enabled, points are calculated using the loyalty card\'s points_per_currency setting. When disabled, uses the fallback value below.',
                'is_cached' => true,
                'cache_ttl' => 3600,
            ],
            [
                'key' => 'integrations.shopify.points_per_currency_fallback',
                'value' => 10,
                'type' => 'integer',
                'category' => 'integrations',
                'label' => 'Fallback Points Per Currency',
                'description' => 'Points awarded per currency unit (e.g., 10 = 10 points per $1) when card rules are disabled.',
                'is_cached' => true,
                'cache_ttl' => 3600,
            ],
            [
                'key' => 'integrations.shopify.points_rounding_fallback',
                'value' => 'down',
                'type' => 'string',
                'category' => 'integrations',
                'label' => 'Fallback Rounding',
                'description' => 'How to round fractional points: down (floor), up (ceil), or nearest (round).',
                'is_cached' => true,
                'cache_ttl' => 3600,
            ],
            [
                'key' => 'integrations.shopify.award_on',
                'value' => 'order_paid',
                'type' => 'string',
                'category' => 'integrations',
                'label' => 'Award Points On',
                'description' => 'Shopify webhook event that triggers point issuance: order_paid or order_fulfilled.',
                'is_cached' => true,
                'cache_ttl' => 3600,
            ],
            [
                'key' => 'integrations.shopify.deduct_on_refund',
                'value' => true,
                'type' => 'boolean',
                'category' => 'integrations',
                'label' => 'Deduct Points on Refund',
                'description' => 'When enabled, points are deducted when an order is refunded.',
                'is_cached' => true,
                'cache_ttl' => 3600,
            ],
            [
                'key' => 'integrations.shopify.first_order_bonus',
                'value' => 0,
                'type' => 'integer',
                'category' => 'integrations',
                'label' => 'First Order Bonus',
                'description' => 'Extra points awarded on a member\'s first order through Shopify. 0 = no bonus.',
                'is_cached' => true,
                'cache_ttl' => 3600,
            ],
            // ─────────────────────────────────────────────────────────────────
            // DISCOUNT SETTINGS
            // ─────────────────────────────────────────────────────────────────
            [
                'key' => 'integrations.shopify.use_automatic_discounts',
                'value' => true,
                'type' => 'boolean',
                'category' => 'integrations',
                'label' => 'Use Automatic Discounts',
                'description' => 'When enabled, discounts are applied automatically at checkout (Shopify Plus). When disabled, generates unique codes.',
                'is_cached' => true,
                'cache_ttl' => 3600,
            ],
            // ─────────────────────────────────────────────────────────────────
            // WIDGET CONFIGURATION
            // ─────────────────────────────────────────────────────────────────
            [
                'key' => 'integrations.shopify.widget.program_name',
                'value' => 'Rewards',
                'type' => 'string',
                'category' => 'integrations',
                'label' => 'Widget Program Name',
                'description' => 'The loyalty program name displayed in the Shopify widget.',
                'is_cached' => true,
                'cache_ttl' => 3600,
            ],
            [
                'key' => 'integrations.shopify.widget.primary_color',
                'value' => '#F59E0B',
                'type' => 'string',
                'category' => 'integrations',
                'label' => 'Widget Primary Color',
                'description' => 'Primary brand color for the widget (hex format).',
                'is_cached' => true,
                'cache_ttl' => 3600,
            ],
            [
                'key' => 'integrations.shopify.widget.mode',
                'value' => 'auto',
                'type' => 'string',
                'category' => 'integrations',
                'label' => 'Widget Color Mode',
                'description' => 'Widget appearance: auto (follows system), light, or dark.',
                'is_cached' => true,
                'cache_ttl' => 3600,
            ],
            [
                'key' => 'integrations.shopify.widget.position',
                'value' => 'bottom-right',
                'type' => 'string',
                'category' => 'integrations',
                'label' => 'Widget Position',
                'description' => 'Where the widget appears on the storefront: bottom-right, bottom-left, top-right, or top-left.',
                'is_cached' => true,
                'cache_ttl' => 3600,
            ],
        ];

        foreach ($shopifySettings as $settingData) {
            Setting::firstOrCreate(
                ['key' => $settingData['key']],
                $settingData
            );
        }

        Log::info('Shopify integration settings initialized automatically');
    }

    /**
     * Attach default PWA icons to the setting model.
     *
     * Only attaches if no user-uploaded icons exist. Uses the same pattern
     * as SettingsController: clear collection, then add media. This ensures
     * no orphaned files accumulate even if singleFile() fails silently.
     */
    private function attachDefaultPwaIcons(): void
    {
        $pwaSetting = Setting::where('key', 'pwa_app_name')->first();

        if (! $pwaSetting) {
            return;
        }

        $icon192Path = public_path('icons/pwa-192.png');
        $icon512Path = public_path('icons/pwa-512.png');

        // Only attach default icons if no user-uploaded icons exist
        if (file_exists($icon192Path) && ! $pwaSetting->hasMedia('pwa_icon_192')) {
            try {
                $pwaSetting->clearMediaCollection('pwa_icon_192');
                $pwaSetting->addMedia($icon192Path)
                    ->preservingOriginal()
                    ->toMediaCollection('pwa_icon_192');
            } catch (\Exception $e) {
                Log::warning('Could not attach default 192x192 PWA icon', ['error' => $e->getMessage()]);
            }
        }

        if (file_exists($icon512Path) && ! $pwaSetting->hasMedia('pwa_icon_512')) {
            try {
                $pwaSetting->clearMediaCollection('pwa_icon_512');
                $pwaSetting->addMedia($icon512Path)
                    ->preservingOriginal()
                    ->toMediaCollection('pwa_icon_512');
            } catch (\Exception $e) {
                Log::warning('Could not attach default 512x512 PWA icon', ['error' => $e->getMessage()]);
            }
        }
    }
}
