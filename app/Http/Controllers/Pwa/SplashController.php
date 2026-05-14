<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * PWA Splash Screen Controller
 *
 * Purpose:
 * Dynamically generates SVG splash screen images for iOS.
 * Uses the pwa_background_color and pwa_icon_512 settings.
 */

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\Response;

class SplashController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService
    ) {}

    /**
     * Generate an SVG splash screen with centered icon on background color.
     * This is used for iOS apple-touch-startup-image.
     */
    public function show(int $width = 1170, int $height = 2532): Response
    {
        // Get background color from settings
        $backgroundColor = $this->settingsService->get(
            'pwa_background_color',
            config('default.pwa_background_color', '#ffffff')
        );

        // Get icon URL
        $pwaSetting = Setting::where('key', 'pwa_app_name')->first();
        $iconUrl = $pwaSetting?->getFirstMediaUrl('pwa_icon_512') ?: asset('icons/pwa-512.png');
        
        // Make icon URL absolute
        if (!str_starts_with($iconUrl, 'http')) {
            $iconUrl = url($iconUrl);
        }

        // Calculate icon position (centered)
        $iconSize = 200; // Display size of icon
        $iconX = ($width - $iconSize) / 2;
        $iconY = ($height - $iconSize) / 2 - 50; // Slightly above center

        // Generate SVG
        $svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
     width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
    <rect width="100%" height="100%" fill="{$backgroundColor}"/>
    <image x="{$iconX}" y="{$iconY}" width="{$iconSize}" height="{$iconSize}" 
           xlink:href="{$iconUrl}" preserveAspectRatio="xMidYMid meet"/>
</svg>
SVG;

        return response($svg)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=86400'); // 24 hours
    }
}
