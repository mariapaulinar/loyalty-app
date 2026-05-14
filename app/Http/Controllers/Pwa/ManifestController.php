<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * PWA Manifest Controller
 *
 * Purpose:
 * Dynamically generates the PWA manifest.json with configuration from app settings.
 * This ensures the manifest always reflects current app branding and configuration.
 *
 * Design Tenets:
 * - Dynamic values from config (not static file)
 * - Proper Content-Type header for manifest
 * - 24-hour cache control for performance
 * - Short name truncation for compliance
 */

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class ManifestController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService
    ) {}

    /**
     * Generate and return the PWA manifest.
     * Uses database settings first, falls back to config, then to hardcoded defaults.
     *
     * IMPORTANT: The start_url uses ROOT (/) instead of a locale-prefixed URL.
     * This allows the LocaleController to redirect to the user's preferred locale
     * when the PWA reopens, based on: 1) authenticated member's DB preference,
     * 2) cookie preference, or 3) browser Accept-Language detection.
     *
     * The scope is also root to allow navigation between different locales.
     */
    public function show(string $locale): JsonResponse
    {
        // Get settings from database with config fallbacks
        $appName = $this->settingsService->get(
            'pwa_app_name',
            config('default.pwa_app_name', config('default.app_name', 'Reward Loyalty'))
        ) ?? 'Reward Loyalty';

        $shortName = $this->settingsService->get(
            'pwa_short_name',
            config('default.pwa_short_name', $this->truncateShortName($appName))
        ) ?? 'Rewards';

        // Get icon URLs from uploaded media or fallback to default files
        // Ensure absolute URLs for iOS/Android compatibility
        $pwaSetting = Setting::where('key', 'pwa_app_name')->first();
        $icon192Path = $pwaSetting?->getFirstMediaUrl('pwa_icon_192') ?: asset('icons/pwa-192.png');
        $icon512Path = $pwaSetting?->getFirstMediaUrl('pwa_icon_512') ?: asset('icons/pwa-512.png');
        
        // Convert to absolute URLs
        $icon192Url = str_starts_with($icon192Path, 'http') ? $icon192Path : url($icon192Path);
        $icon512Url = str_starts_with($icon512Path, 'http') ? $icon512Path : url($icon512Path);

        // Build URLs for PWA navigation
        // IMPORTANT: start_url uses ROOT (/) so the LocaleController can redirect to the user's
        // preferred locale. This fixes the issue where PWAs always started in the install-time locale.
        // The redirect hierarchy is: 1) Auth member's DB locale, 2) Cookie preference, 3) Browser detection
        $startUrl = "/?source=pwa";
        $scope = "/"; // Root scope allows navigation to any locale
        // Shortcuts keep the current locale since the user will already be redirected correctly
        $myCardsUrl = "/{$locale}/my-cards?source=pwa_shortcut";

        $manifest = [
            'name' => $appName,
            'short_name' => $shortName,
            'description' => $this->settingsService->get(
                'pwa_description',
                config('default.pwa_description', 'Your digital loyalty card')
            ),
            'start_url' => $startUrl,
            'scope' => $scope,
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => $this->settingsService->get(
                'pwa_background_color',
                config('default.pwa_background_color', '#ffffff')
            ),
            'theme_color' => $this->settingsService->get(
                'pwa_theme_color',
                config('default.pwa_theme_color', '#4F46E5')
            ),
            'icons' => [
                [
                    'src' => $icon192Url,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => $icon512Url,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
            'shortcuts' => [
                [
                    'name' => trans('common.my_cards'),
                    'short_name' => trans('common.my_cards'),
                    'description' => 'View your loyalty cards',
                    'url' => $myCardsUrl,
                    'icons' => [
                        [
                            'src' => $icon192Url,
                            'sizes' => '192x192',
                        ],
                    ],
                ],
            ],
        ];

        return response()
            ->json($manifest)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=86400'); // 24 hours
    }

    /**
     * Truncate short name to 12 characters max for PWA compliance.
     */
    private function truncateShortName(?string $name): string
    {
        // Handle null or empty string
        if (empty($name)) {
            return 'Rewards';
        }

        if (mb_strlen($name) <= 12) {
            return $name;
        }

        return mb_substr($name, 0, 12);
    }
}
