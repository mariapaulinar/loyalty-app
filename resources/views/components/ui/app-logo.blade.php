{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Application Logo Component - Smart Logo Rendering

Purpose:
Renders the application logo with intelligent fallback logic:
1. Check for uploaded logo via Media Library (brand_color setting)
2. Fall back to config URL if no upload exists
3. Display branded initial badge if no logo is configured

Usage:
<x-ui.app-logo />                          // Standard logo (h-8)
<x-ui.app-logo class="h-10" />             // Custom height
<x-ui.app-logo :show-name="true" />        // Show app name alongside logo

Supports:
- Light/Dark mode automatic switching
- SVG, PNG, JPG, WebP formats
- Graceful fallback to branded initial badge
--}}
@php
    use App\Models\Setting;
    
    // Try to get logo from media library (attached to brand_color setting)
    $brandingSetting = Setting::where('key', 'brand_color')->first();
    
    // Get uploaded logo URLs (empty string if not uploaded)
    $uploadedLogo = $brandingSetting?->getFirstMediaUrl('app_logo') ?: '';
    $uploadedLogoDark = $brandingSetting?->getFirstMediaUrl('app_logo_dark') ?: '';
    
    // Final logo URLs: prefer uploaded, then fall back to config, then empty
    $logoUrl = $uploadedLogo ?: config('default.app_logo', '');
    $logoDarkUrl = $uploadedLogoDark ?: config('default.app_logo_dark', '');
    
    // App name for alt text and fallback display
    $appName = config('default.app_name', 'Lealmi');
    
    // Get initial for fallback badge
    $initial = mb_substr($appName, 0, 1);
@endphp

@props([
    'class' => 'h-8 w-auto',
    'showName' => false,
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    @if($logoUrl)
        {{-- Light mode logo --}}
        <img 
            src="{{ $logoUrl }}" 
            @if($logoDarkUrl)
                class="{{ $class }} dark:hidden"
            @else
                class="{{ $class }}"
            @endif
            alt="{{ $appName }}"
        >
        
        {{-- Dark mode logo (if different from light) --}}
        @if($logoDarkUrl)
            <img 
                src="{{ $logoDarkUrl }}" 
                class="{{ $class }} hidden dark:block" 
                alt="{{ $appName }}"
            >
        @endif
    @else
        {{-- Fallback: Branded initial badge + app name --}}
        <div class="w-8 h-8 rounded-xl bg-primary-600 flex items-center justify-center text-white font-bold text-lg shadow-sm flex-shrink-0">
            {{ $initial }}
        </div>
        <span class="font-heading font-semibold text-lg text-secondary-900 dark:text-white">{{ $appName }}</span>
    @endif
    
    {{-- Optional: Show app name alongside logo (when logo exists) --}}
    @if($showName && $logoUrl)
        <span class="font-heading font-semibold text-lg text-secondary-900 dark:text-white">{{ $appName }}</span>
    @endif
</div>
