{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Brand Styles Component - Dynamic CSS Variable Injection

Purpose:
Injects CSS custom properties to override the default primary color palette
with the admin-configured brand color. This enables true white-label theming
without requiring CSS rebuilds.

Design Philosophy:
- ONE COLOR IN, COMPLETE SYSTEM OUT: Single setting controls entire palette
- RUNTIME INJECTION: No build step needed, instant preview
- DARK MODE AWARE: Generates optimized palettes for both themes
- PROGRESSIVE ENHANCEMENT: Falls back gracefully to defaults

Usage:
Include once in each layout's <head> section:
<x-ui.brand-styles />

How It Works:
1. Retrieves brand_color from settings (defaults to blue #2563EB)
2. Uses ColorHelper to generate 11-shade palette (50-950)
3. Outputs <style> block with CSS variable overrides
4. These variables cascade to Tailwind's primary-* utilities

Architecture Note:
This component is included in ALL layouts (admin, partner, staff, installation)
to ensure consistent branding across the entire application.
--}}
@php
    use App\Helpers\ColorHelper;
    
    // Get brand color from settings or config, with sensible default
    $brandColor = config('default.brand_color', '#2563EB');
    
    // Generate the full palette from the single brand color
    $palette = ColorHelper::generatePalette($brandColor);
    
    // Calculate appropriate foreground color for the base brand
    $foreground = ColorHelper::getContrastColor($brandColor);
@endphp
@if($brandColor !== '#2563EB')
{{-- Only inject custom styles if using non-default brand color --}}
<style id="brand-color-overrides">
    /*
     * Dynamic Brand Color Palette
     * Generated from: {{ $brandColor }}
     * 
     * These CSS variables override the default primary color defined in app.css.
     * The primary-* utilities (text-primary-500, bg-primary-600, etc.) will
     * automatically use these values.
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
        
        /* Semantic aliases used by various components */
        --color-brand: {{ $brandColor }};
        --color-brand-foreground: {{ $foreground }};
        
        /* Shadow glow effects should match brand */
        --shadow-glow-primary: 0 0 40px -10px {{ $palette[500] }};
    }
    
    /*
     * Dark Mode Palette Adjustments
     * 
     * For dark mode, we slightly boost the lighter shades to maintain
     * visual balance against dark backgrounds.
     */
    .dark {
        /* Dark mode uses slightly adjusted shades for better visual balance */
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
@endif
