{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Brand Styles Component - Dynamic CSS Variable Injection

Purpose:
Injects CSS custom properties to override the default primary color palette
with the admin-configured brand color. This enables true white-label theming
without requiring CSS rebuilds.

Usage:
Include once in each layout's <head> section:
<x-ui.brand-styles />

Defaults (Lealmi production): brand_color #FCD34D
--}}
@php
    use App\Helpers\ColorHelper;

    $brandColor = (string) config('default.brand_color', '#FCD34D');
    if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $brandColor)) {
        $brandColor = '#FCD34D';
    }

    $palette = ColorHelper::generatePalette($brandColor);
    $foreground = ColorHelper::getContrastColor($brandColor);
@endphp
{{-- Always inject so primary-* match admin brand (Lealmi gold is not the Tailwind @theme blue) --}}
<style id="brand-color-overrides">
    /*
     * Dynamic Brand Color Palette
     * Generated from: {{ $brandColor }}
     */
    :root {
        --color-primary-50: {{ $palette[50] }};
        --color-primary-100: {{ $palette[100] }};
        --color-primary-200: {{ $palette[200] }};
        --color-primary-300: {{ $palette[300] }};
        --color-primary-400: {{ $palette[400] }};
        --color-primary-500: {{ $palette[500] }};
        --color-primary-600: {{ $palette[600] }};
        --color-primary-700: {{ $palette[700] }};
        --color-primary-800: {{ $palette[800] }};
        --color-primary-900: {{ $palette[900] }};
        --color-primary-950: {{ $palette[950] }};

        --color-brand: {{ $brandColor }};
        --color-brand-foreground: {{ $foreground }};

        --shadow-glow-primary: 0 0 40px -10px {{ $palette[500] }};
    }

    .dark {
        --color-primary-50: {{ $palette[50] }};
        --color-primary-100: {{ $palette[100] }};
        --color-primary-200: {{ $palette[200] }};
        --color-primary-300: {{ $palette[300] }};
        --color-primary-400: {{ $palette[400] }};
        --color-primary-500: {{ $palette[500] }};
        --color-primary-600: {{ $palette[600] }};
        --color-primary-700: {{ $palette[700] }};
        --color-primary-800: {{ $palette[800] }};
        --color-primary-900: {{ $palette[900] }};
        --color-primary-950: {{ $palette[950] }};
    }
</style>
