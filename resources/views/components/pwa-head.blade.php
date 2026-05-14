{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

PWA Head Component

Purpose:
Include PWA meta tags, manifest link, and service worker registration.
Add this component in the <head> section of your main layout.

Usage:
<x-pwa-head />
--}}

@php
    use App\Services\SettingsService;
    $settingsService = app(SettingsService::class);
    $themeColor = $settingsService->get('pwa_theme_color', config('default.pwa_theme_color', '#4F46E5'));
    $backgroundColor = $settingsService->get('pwa_background_color', config('default.pwa_background_color', '#ffffff'));
    $shortName = $settingsService->get('pwa_short_name', config('default.pwa_short_name', 'Rewards'));
    
    // Get icon URLs from uploaded media or fallback - ensure absolute URLs
    $pwaSetting = \App\Models\Setting::where('key', 'pwa_app_name')->first();
    $icon192Path = $pwaSetting?->getFirstMediaUrl('pwa_icon_192') ?: asset('icons/pwa-192.png');
    $icon512Path = $pwaSetting?->getFirstMediaUrl('pwa_icon_512') ?: asset('icons/pwa-512.png');
    
    // Ensure URLs are absolute (iOS requires absolute URLs)
    $icon192 = str_starts_with($icon192Path, 'http') ? $icon192Path : url($icon192Path);
    $icon512 = str_starts_with($icon512Path, 'http') ? $icon512Path : url($icon512Path);
@endphp

{{-- PWA Manifest --}}
<link rel="manifest" href="{{ route('pwa.manifest') }}">

{{-- Theme Color - Used by browsers for toolbar/chrome --}}
<meta name="theme-color" content="{{ $themeColor }}">
<meta name="theme-color" content="{{ $themeColor }}" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#1e293b" media="(prefers-color-scheme: dark)">

{{-- iOS/PWA Support --}}
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ $shortName }}">
<link rel="apple-touch-icon" href="{{ $icon192 }}">
<link rel="apple-touch-icon" sizes="192x192" href="{{ $icon192 }}">
<link rel="apple-touch-icon" sizes="512x512" href="{{ $icon512 }}">

{{-- iOS Splash Screen - Use 512x512 PNG icon (iOS will display on white background) --}}
<link rel="apple-touch-startup-image" href="{{ $icon512 }}">

{{-- Windows Tile --}}
<meta name="msapplication-TileImage" content="{{ $icon512 }}">
<meta name="msapplication-TileColor" content="{{ $themeColor }}">

{{-- Service Worker Registration --}}
<script>
    'use strict';
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/service-worker.js', { scope: '/' });
        });
    }
</script>
