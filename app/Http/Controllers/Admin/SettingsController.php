<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * SettingsController - Admin System Settings Management
 *
 * Purpose:
 * Provides a centralized interface for super admins to manage various
 * application settings, including branding, compliance, email, and
 * loyalty card configurations. Settings are stored in the database via
 * the SettingsService, allowing for dynamic updates that override .env
 * configurations.
 *
 * Design Tenets:
 * - **Type-safe**: All methods use strict typing
 * - **Modular**: Settings are grouped by category for easy management
 * - **Auditable**: All changes are tracked with user attribution
 * - **Extensible**: New settings categories can be added easily
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ActivityLogService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Inject services for settings management and audit logging.
     */
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly ActivityLogService $activityLogService
    ) {}

    /**
     * Display the system settings page.
     *
     * Retrieves all settings from the database grouped by category and
     * prepares data for the view including select options.
     */
    public function index(string $locale): View
    {
        // Retrieve all settings from the database, grouped by category for display
        $settings = $this->settingsService->getAllGrouped();

        // Prepare data for the 'reward_claim_qr_valid_minutes' select input
        // Short durations for in-person QR code redemptions, plus longer options for flexibility
        $rewardClaimQrOptions = [
            '5' => trans('common.settings_page.qr_valid_minutes_5'),
            '10' => trans('common.settings_page.qr_valid_minutes_10'),
            '15' => trans('common.settings_page.qr_valid_minutes_15'),
            '20' => trans('common.settings_page.qr_valid_minutes_20'),
            '30' => trans('common.settings_page.qr_valid_minutes_30'),
            '1440' => trans('common.settings_page.qr_valid_minutes_1_day'),
            '10080' => trans('common.settings_page.qr_valid_minutes_1_week'),
            '43200' => trans('common.settings_page.qr_valid_minutes_1_month'),
            '525600' => trans('common.settings_page.qr_valid_minutes_1_year'),
        ];

        // Prepare data for the 'code_to_redeem_points_valid_minutes' select input
        // Values are stored as minutes for backend calculations
        $codeValidMinutesOptions = [
            '60' => trans('common.settings_page.code_valid_minutes_1_hour'),
            '720' => trans('common.settings_page.code_valid_minutes_12_hours'),
            '1440' => trans('common.settings_page.code_valid_minutes_1_day'),
            '2880' => trans('common.settings_page.code_valid_minutes_2_days'),
            '4320' => trans('common.settings_page.code_valid_minutes_3_days'),
            '10080' => trans('common.settings_page.code_valid_minutes_1_week'),
            '43200' => trans('common.settings_page.code_valid_minutes_1_month'),
            '525600' => trans('common.settings_page.code_valid_minutes_1_year'),
        ];

        return view('admin.settings.index', [
            'settings' => $settings,
            'rewardClaimQrOptions' => $rewardClaimQrOptions,
            'codeValidMinutesOptions' => $codeValidMinutesOptions,
        ]);
    }

    /**
     * Update system settings.
     *
     * Validates and stores all submitted settings. Each setting is saved
     * with appropriate type casting and user attribution for audit trails.
     */
    public function update(string $locale, Request $request): RedirectResponse
    {
        // Block updates in demo mode
        if (config('default.app_demo')) {
            return redirect()
                ->route('admin.settings.index')
                ->with('toast', [
                    'type' => 'error',
                    'text' => trans('common.demo_mode_update_restricted'),
                ]);
        }

        $admin = Auth::guard('admin')->user();

        // Define validation rules for all settings
        $rules = [
            'app_name' => 'required|string|max:255',
            'brand_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'app_url' => 'required|url|max:255',
            'cookie_consent' => 'nullable',
            'mail_from_name' => 'required|string|max:255',
            'mail_from_address' => 'required|email|max:255',
            'max_member_request_links' => 'required|integer|min:0',
            'reward_claim_qr_valid_minutes' => 'required|integer|min:1|max:525600',
            'code_to_redeem_points_valid_minutes' => 'required|integer|min:1',
            'staff_transaction_days_ago' => 'required|integer|min:0',
            // Branding logo uploads (SVG, PNG, JPG, WebP supported)
            'app_logo' => 'nullable|file|mimes:svg,png,jpg,jpeg,webp|max:2048',
            'app_logo_dark' => 'nullable|file|mimes:svg,png,jpg,jpeg,webp|max:2048',
            // Favicon upload (ICO or SVG only)
            'app_favicon' => 'nullable|file|max:512',
            // PWA settings
            'pwa_app_name' => 'nullable|string|max:50',
            'pwa_short_name' => 'required|string|max:12',
            'pwa_description' => 'required|string|max:200',
            'pwa_theme_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'pwa_background_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'pwa_icon_192' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'pwa_icon_512' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:5120',
            // Homepage settings
            'homepage_layout' => 'nullable|in:directory,showcase,portal',
            'homepage_hero_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'homepage_show_how_it_works' => 'nullable',
            'homepage_show_tiers' => 'nullable',
            'homepage_show_member_count' => 'nullable',
            // Anonymous member settings
            'anonymous_members_enabled' => 'nullable',
            'anonymous_member_code_length' => 'nullable|integer|min:4|max:12',
            'anonymous_logout_on_disable' => 'nullable',
            // Partner registration
            'partners_can_register' => 'nullable',
        ];

        $validated = $request->validate($rules);

        // Define setting categories for proper storage
        $settingCategories = [
            'app_name' => 'branding',
            'brand_color' => 'branding',
            'app_url' => 'branding',
            'cookie_consent' => 'compliance',
            'mail_from_name' => 'email',
            'mail_from_address' => 'email',
            'max_member_request_links' => 'loyalty_cards',
            'reward_claim_qr_valid_minutes' => 'loyalty_cards',
            'code_to_redeem_points_valid_minutes' => 'loyalty_cards',
            'staff_transaction_days_ago' => 'loyalty_cards',
            'pwa_app_name' => 'pwa',
            'pwa_short_name' => 'pwa',
            'pwa_description' => 'pwa',
            'pwa_theme_color' => 'pwa',
            'pwa_background_color' => 'pwa',
            // Homepage settings
            'homepage_layout' => 'homepage',
            'homepage_show_how_it_works' => 'homepage',
            'homepage_show_tiers' => 'homepage',
            'homepage_show_member_count' => 'homepage',
            // Anonymous member settings
            'anonymous_members_enabled' => 'members',
            'anonymous_member_code_length' => 'members',
            // Note: anonymous_logout_on_disable is intentionally NOT saved - it's a one-time action
            // Partner registration
            'partners_can_register' => 'onboarding',
        ];

        // Track changes for audit logging
        $changedSettings = [];

        // IMPORTANT: Capture the OLD value of anonymous_members_enabled BEFORE saving
        // This is needed for the "log out anonymous members" feature to work correctly
        $wasAnonymousEnabled = $this->settingsService->get('anonymous_members_enabled', false);

        // Define checkbox fields that need explicit false handling
        // (checkboxes don't send values when unchecked)
        $checkboxFields = [
            'cookie_consent',
            'homepage_show_how_it_works',
            'homepage_show_tiers',
            'homepage_show_member_count',
            'anonymous_members_enabled',
            'partners_can_register',
        ];
        
        // Ensure all checkbox fields have a value (default to false if not in request)
        foreach ($checkboxFields as $checkbox) {
            if (!isset($validated[$checkbox])) {
                $validated[$checkbox] = false;
            }
        }

        // Process and save each setting
        foreach ($validated as $key => $value) {
            // Skip image uploads (handled separately by dedicated handlers)
            if (in_array($key, ['pwa_icon_192', 'pwa_icon_512', 'app_logo', 'app_logo_dark', 'app_favicon', 'homepage_hero_image'])) {
                continue;
            }
            
            // Skip action-only inputs (not saved to database)
            if ($key === 'anonymous_logout_on_disable') {
                continue;
            }

            // Handle checkbox boolean conversion
            if (in_array($key, $checkboxFields)) {
                $value = (bool) $value;
            }

            // Get old value for audit trail
            $oldValue = $this->settingsService->get($key);

            // Save setting with category metadata
            $setting = $this->settingsService->set($key, $value, $admin);

            // Update category if not set
            if (isset($settingCategories[$key]) && $setting->category !== $settingCategories[$key]) {
                $setting->update(['category' => $settingCategories[$key]]);
            }

            // Track if value changed (compare as strings to handle type differences)
            if ((string) $oldValue !== (string) $value) {
                $changedSettings[$key] = [
                    'old' => $oldValue,
                    'new' => $value,
                    'category' => $settingCategories[$key] ?? 'general',
                ];
            }
        }

        // Handle PWA icon uploads
        $this->handlePwaIconUploads($request, $changedSettings);

        // Handle branding logo uploads
        $this->handleLogoUploads($request, $changedSettings);

        // Handle favicon upload
        $this->handleFaviconUpload($request, $changedSettings);

        // Handle homepage hero image upload
        $this->handleHomepageImageUpload($request, $changedSettings);

        // Handle anonymous member logout action
        // If anonymous mode is disabled AND the logout checkbox was checked, invalidate sessions
        $isDisabled = !$request->boolean('anonymous_members_enabled');
        $shouldLogout = $request->boolean('anonymous_logout_on_disable');
        $isBeingEnabled = !$wasAnonymousEnabled && $request->boolean('anonymous_members_enabled');
        
        // Clear forced logout flag when re-enabling anonymous mode
        if ($isBeingEnabled) {
            $this->settingsService->delete('anonymous_forced_logout_at');
        }
        
        if ($isDisabled && $shouldLogout) {
            // Log out all anonymous members by invalidating their sessions
            // Anonymous members are those without an email address
            
            // Get IDs of all anonymous members
            $anonymousMemberIds = \App\Models\Member::whereNull('email')
                ->orWhere('email', '')
                ->pluck('id')
                ->toArray();
            
            // Clear remember tokens to invalidate "remember me" cookies
            $loggedOutCount = \App\Models\Member::whereIn('id', $anonymousMemberIds)
                ->update(['remember_token' => null]);
            
            // Clear sessions for these members (works for database session driver)
            if (config('session.driver') === 'database' && !empty($anonymousMemberIds)) {
                \Illuminate\Support\Facades\DB::table('sessions')
                    ->whereIn('user_id', $anonymousMemberIds)
                    ->delete();
            }
            
            // Store a flag in settings that middleware will check to force logout
            // This ensures anonymous members are logged out on their next request
            // regardless of session driver (file, redis, etc.)
            $this->settingsService->set(
                'anonymous_forced_logout_at',
                now()->timestamp,
                $admin
            );
            
            $this->activityLogService->log(
                description: "Logged out {$loggedOutCount} anonymous members",
                subject: null,
                event: 'anonymous_members_logged_out',
                properties: [
                    'count' => $loggedOutCount,
                    'ip' => $request->ip(),
                ],
                logName: 'settings'
            );
        }

        // Log the settings update if any changes were made
        if (! empty($changedSettings)) {
            $this->activityLogService->log(
                description: 'System settings updated',
                subject: null,
                event: 'settings_updated',
                properties: [
                    'changes' => $changedSettings,
                    'changed_count' => count($changedSettings),
                    'ip' => $request->ip(),
                ],
                logName: 'settings'
            );
        }

        return redirect()
            ->route('admin.settings.index', ['locale' => $locale])
            ->with('toast', [
                'type' => 'success',
                'text' => trans('common.record_updated'),
            ])
            ->with('active_tab', $request->input('current_tab_index', 1));
    }

    /**
     * Reset a specific setting to its default value.
     *
     * Deletes the setting from the database, causing the application
     * to fall back to the .env or config default value.
     */
    public function reset(string $locale, Request $request): RedirectResponse
    {
        // Block resets in demo mode
        if (config('default.app_demo')) {
            return redirect()
                ->route('admin.settings.index')
                ->with('toast', [
                    'type' => 'error',
                    'text' => trans('common.demo_mode_update_restricted'),
                ]);
        }

        $key = $request->input('key');

        if (! $key) {
            return redirect()
                ->route('admin.settings.index', ['locale' => $locale])
                ->with('toast', [
                    'type' => 'error',
                    'text' => trans('common.settings_page.setting_not_found'),
                ]);
        }

        // Get old value for audit trail before deleting
        $oldValue = $this->settingsService->get($key);

        // Delete the setting from database (reverts to config default)
        $deleted = $this->settingsService->delete($key);

        if ($deleted) {
            // Log the setting reset
            $this->activityLogService->log(
                description: "Setting '{$key}' reset to default",
                subject: null,
                event: 'setting_reset',
                properties: [
                    'key' => $key,
                    'old_value' => $oldValue,
                    'ip' => $request->ip(),
                ],
                logName: 'settings'
            );

            return redirect()
                ->route('admin.settings.index', ['locale' => $locale])
                ->with('toast', [
                    'type' => 'success',
                    'text' => trans('common.settings_page.reset_success'),
                ]);
        }

        return redirect()
            ->route('admin.settings.index', ['locale' => $locale])
            ->with('toast', [
                'type' => 'error',
                'text' => trans('common.settings_page.reset_failed'),
            ]);
    }

    /**
     * Handle PWA icon uploads and deletions
     */
    private function handlePwaIconUploads(Request $request, array &$changedSettings): void
    {
        // Get or create PWA setting record for icon attachments
        $pwaSetting = Setting::firstOrCreate(
            ['key' => 'pwa_app_name'],
            [
                'value' => null,
                'type' => 'string',
                'category' => 'pwa',
            ]
        );

        // Handle 192x192 icon
        if ($request->hasFile('pwa_icon_192') && $request->file('pwa_icon_192')->isValid()) {
            // Upload new icon
            try {
                $pwaSetting->clearMediaCollection('pwa_icon_192');
                $pwaSetting->addMediaFromRequest('pwa_icon_192')
                    ->toMediaCollection('pwa_icon_192');

                $changedSettings['pwa_icon_192'] = [
                    'old' => 'Previous icon',
                    'new' => 'New 192x192 icon uploaded',
                    'category' => 'pwa',
                ];
            } catch (\Exception $e) {
                logger()->error('Failed to upload PWA 192x192 icon', [
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($request->input('pwa_icon_192_deleted') === '1') {
            // Delete icon if explicitly marked for deletion via hidden input
            try {
                $pwaSetting->clearMediaCollection('pwa_icon_192');

                $changedSettings['pwa_icon_192'] = [
                    'old' => '192x192 icon',
                    'new' => 'Icon removed',
                    'category' => 'pwa',
                ];
            } catch (\Exception $e) {
                logger()->error('Failed to delete PWA 192x192 icon', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Handle 512x512 icon
        if ($request->hasFile('pwa_icon_512') && $request->file('pwa_icon_512')->isValid()) {
            // Upload new icon
            try {
                $pwaSetting->clearMediaCollection('pwa_icon_512');
                $pwaSetting->addMediaFromRequest('pwa_icon_512')
                    ->toMediaCollection('pwa_icon_512');

                $changedSettings['pwa_icon_512'] = [
                    'old' => 'Previous icon',
                    'new' => 'New 512x512 icon uploaded',
                    'category' => 'pwa',
                ];
            } catch (\Exception $e) {
                logger()->error('Failed to upload PWA 512x512 icon', [
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($request->input('pwa_icon_512_deleted') === '1') {
            // Delete icon if explicitly marked for deletion via hidden input
            try {
                $pwaSetting->clearMediaCollection('pwa_icon_512');

                $changedSettings['pwa_icon_512'] = [
                    'old' => '512x512 icon',
                    'new' => 'Icon removed',
                    'category' => 'pwa',
                ];
            } catch (\Exception $e) {
                logger()->error('Failed to delete PWA 512x512 icon', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle branding logo uploads and deletions.
     *
     * Similar pattern to PWA icons but for app logos.
     * Supports SVG, PNG, JPG, WebP formats.
     */
    private function handleLogoUploads(Request $request, array &$changedSettings): void
    {
        // Get or create a branding setting record for logo attachments
        $brandingSetting = Setting::firstOrCreate(
            ['key' => 'brand_color'],
            [
                'value' => null,
                'type' => 'string',
                'category' => 'branding',
            ]
        );

        // Handle Light Mode Logo
        if ($request->hasFile('app_logo') && $request->file('app_logo')->isValid()) {
            try {
                $brandingSetting->clearMediaCollection('app_logo');
                $brandingSetting->addMediaFromRequest('app_logo')
                    ->toMediaCollection('app_logo');

                $changedSettings['app_logo'] = [
                    'old' => 'Previous logo',
                    'new' => 'New light mode logo uploaded',
                    'category' => 'branding',
                ];
            } catch (\Exception $e) {
                logger()->error('Failed to upload light mode logo', [
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($request->input('app_logo_deleted') === '1') {
            try {
                $brandingSetting->clearMediaCollection('app_logo');

                $changedSettings['app_logo'] = [
                    'old' => 'Light mode logo',
                    'new' => 'Logo removed',
                    'category' => 'branding',
                ];
            } catch (\Exception $e) {
                logger()->error('Failed to delete light mode logo', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Handle Dark Mode Logo
        if ($request->hasFile('app_logo_dark') && $request->file('app_logo_dark')->isValid()) {
            try {
                $brandingSetting->clearMediaCollection('app_logo_dark');
                $brandingSetting->addMediaFromRequest('app_logo_dark')
                    ->toMediaCollection('app_logo_dark');

                $changedSettings['app_logo_dark'] = [
                    'old' => 'Previous logo',
                    'new' => 'New dark mode logo uploaded',
                    'category' => 'branding',
                ];
            } catch (\Exception $e) {
                logger()->error('Failed to upload dark mode logo', [
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($request->input('app_logo_dark_deleted') === '1') {
            try {
                $brandingSetting->clearMediaCollection('app_logo_dark');

                $changedSettings['app_logo_dark'] = [
                    'old' => 'Dark mode logo',
                    'new' => 'Logo removed',
                    'category' => 'branding',
                ];
            } catch (\Exception $e) {
                logger()->error('Failed to delete dark mode logo', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle favicon upload and deletion.
     *
     * Attached to the brand_color setting record (same as logos).
     * Supports ICO and SVG formats.
     */
    private function handleFaviconUpload(Request $request, array &$changedSettings): void
    {
        // Use the same branding setting record as logos
        $brandingSetting = Setting::firstOrCreate(
            ['key' => 'brand_color'],
            [
                'value' => null,
                'type' => 'string',
                'category' => 'branding',
            ]
        );

        // Handle Favicon Upload
        if ($request->hasFile('app_favicon') && $request->file('app_favicon')->isValid()) {
            // Validate file extension (mimes rule doesn't reliably catch .ico)
            $extension = strtolower($request->file('app_favicon')->getClientOriginalExtension());
            if (! in_array($extension, ['ico', 'svg'])) {
                return;
            }

            try {
                $brandingSetting->clearMediaCollection('app_favicon');
                $brandingSetting->addMediaFromRequest('app_favicon')
                    ->toMediaCollection('app_favicon');

                $changedSettings['app_favicon'] = [
                    'old' => 'Previous favicon',
                    'new' => 'New favicon uploaded',
                    'category' => 'branding',
                ];
            } catch (\Exception $e) {
                logger()->error('Failed to upload favicon', [
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($request->input('app_favicon_deleted') === '1') {
            try {
                $brandingSetting->clearMediaCollection('app_favicon');

                $changedSettings['app_favicon'] = [
                    'old' => 'Custom favicon',
                    'new' => 'Favicon removed (using default)',
                    'category' => 'branding',
                ];
            } catch (\Exception $e) {
                logger()->error('Failed to delete favicon', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle homepage hero image upload and deletion.
     *
     * Used for Showcase layout's optional hero background image.
     * Supports JPG, PNG, WebP formats.
     */
    private function handleHomepageImageUpload(Request $request, array &$changedSettings): void
    {
        // Get or create a homepage setting record for image attachment
        $homepageSetting = Setting::firstOrCreate(
            ['key' => 'homepage_layout'],
            [
                'value' => 'directory',
                'type' => 'string',
                'category' => 'homepage',
            ]
        );

        // Handle Hero Background Image
        if ($request->hasFile('homepage_hero_image') && $request->file('homepage_hero_image')->isValid()) {
            try {
                $homepageSetting->clearMediaCollection('homepage_hero_image');
                $homepageSetting->addMediaFromRequest('homepage_hero_image')
                    ->toMediaCollection('homepage_hero_image');

                $changedSettings['homepage_hero_image'] = [
                    'old' => 'Previous image',
                    'new' => 'New hero background uploaded',
                    'category' => 'homepage',
                ];
            } catch (\Exception $e) {
                logger()->error('Failed to upload homepage hero image', [
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($request->input('homepage_hero_image_deleted') === '1') {
            try {
                $homepageSetting->clearMediaCollection('homepage_hero_image');

                $changedSettings['homepage_hero_image'] = [
                    'old' => 'Hero background image',
                    'new' => 'Image removed',
                    'category' => 'homepage',
                ];
            } catch (\Exception $e) {
                logger()->error('Failed to delete homepage hero image', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
