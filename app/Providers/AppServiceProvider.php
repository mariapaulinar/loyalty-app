<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Providers;

use App\Contracts\BillingProvider;
use App\Models\Club;
use App\Models\Partner;
use App\Models\Setting;
use App\Observers\ClubObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Disable Cashier's auto-registered routes — we define our own
        // webhook route under /api/stripe/webhook in routes/api.php.
        Cashier::ignoreRoutes();

        // Tell Cashier to use Partner as the billable model instead of
        // the default App\Models\User (which does not exist in this app).
        Cashier::useCustomerModel(Partner::class);

        // Register billing manager as a singleton
        $this->app->singleton(\App\Services\Billing\BillingManager::class);

        // Bind the BillingProvider contract to the active provider
        $this->app->bind(BillingProvider::class, function ($app) {
            return $app->make(\App\Services\Billing\BillingManager::class)->provider();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(UrlGenerator $url): void
    {
        if (! app()->runningInConsole()) {
            // Force SSL if required
            if (config('default.force_ssl')) {
                $url->forceScheme('https');
            }
        }

        // Or force strict mode if not production
        Model::shouldBeStrict(! app()->isProduction());

        // Register model observers
        Club::observe(ClubObserver::class);
        \App\Models\Transaction::observe(\App\Observers\TransactionObserver::class);

        // Load database settings and override config values
        $this->loadDatabaseSettings();
    }

    /**
     * Load settings from database and override config values.
     *
     * This allows database-stored settings to take precedence over
     * .env and config/default.php values.
     */
    private function loadDatabaseSettings(): void
    {
        // Skip if app is not installed or running in console during migrations
        if (! config('default.app_is_installed')) {
            return;
        }

        // Skip if database connection isn't available
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        // Mapping of setting keys to config paths
        $settingsToConfig = [
            'app_name' => 'default.app_name',
            'brand_color' => 'default.brand_color',
            'app_url' => 'default.app_url',
            'cookie_consent' => 'default.cookie_consent',
            'mail_from_name' => 'default.mail_from_name',
            'mail_from_address' => 'default.mail_from_address',
            'max_member_request_links' => 'default.max_member_request_links',
            'reward_claim_qr_valid_minutes' => 'default.reward_claim_qr_valid_minutes',
            'code_to_redeem_points_valid_minutes' => 'default.code_to_redeem_points_valid_minutes',
            'staff_transaction_days_ago' => 'default.staff_transaction_days_ago',
            // Anonymous member settings
            'anonymous_members_enabled' => 'default.anonymous_members_enabled',
            'anonymous_member_code_length' => 'default.anonymous_member_code_length',
            'anonymous_forced_logout_at' => 'default.anonymous_forced_logout_at',
        ];

        try {
            // Fetch all relevant settings in one query
            $settings = Setting::whereIn('key', array_keys($settingsToConfig))->get();

            foreach ($settings as $setting) {
                if (isset($settingsToConfig[$setting->key])) {
                    $configPath = $settingsToConfig[$setting->key];
                    $value = $setting->value;

                    // Type cast based on setting type
                    $value = match ($setting->type) {
                        'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                        'integer' => (int) $value,
                        'number' => (float) $value,
                        default => $value,
                    };

                    // Override the config value
                    config([$configPath => $value]);
                }
            }
        } catch (\Exception $e) {
            // Silently fail - don't break the app if settings can't be loaded
            // This can happen during initial setup or if database is unavailable
        }
    }
}
