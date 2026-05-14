{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2026 NowSquare. All rights reserved.
See LICENSE file for terms.

Error Page Content Partial

Purpose:
Shared content block for context-aware error pages (401, 403, 404, 419, 500).
These pages extend the appropriate dashboard layout based on URL context,
then render this partial for consistent error presentation.

Usage:
@include('errors.partials.error-content', [
    'code' => '404',
    'icon' => 'search',
    'home' => $home,
])

Design:
- Large typographic error code (mono, ultra-light weight)
- Contextual icon in a neutral container
- Clear title and description from translation keys
- Single CTA back to contextual home
- No gradients, no glow, no ambient effects
- Vertical rhythm: centered, generous spacing
--}}
<section class="flex items-center justify-center px-4 py-16 md:py-24">
    <div class="max-w-md w-full text-center">
        {{-- Error Code — Large, faded, typographic element --}}
        <p class="text-[120px] md:text-[160px] font-bold leading-none tracking-tight text-secondary-200 dark:text-secondary-800 select-none font-mono tabular-nums">
            {{ $code }}
        </p>

        {{-- Icon --}}
        <div class="flex justify-center mt-2 mb-6">
            <div class="w-14 h-14 rounded-2xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center">
                <x-ui.icon :icon="$icon" class="w-7 h-7 text-secondary-400 dark:text-secondary-500" />
            </div>
        </div>

        {{-- Title --}}
        <h1 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white tracking-tight mb-3">
            {{ trans("common.{$code}_title") }}
        </h1>

        {{-- Description --}}
        <p class="text-sm text-secondary-500 dark:text-secondary-400 leading-relaxed mb-8 max-w-sm mx-auto">
            {!! trans("common.{$code}_description") !!}
        </p>

        {{-- CTA --}}
        <x-ui.button :href="$home" variant="primary" size="md" icon="arrow-left">
            {{ trans('common.go_back_home') }}
        </x-ui.button>
    </div>
</section>
