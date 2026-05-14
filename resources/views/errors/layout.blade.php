{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2026 NowSquare. All rights reserved.
See LICENSE file for terms.

Error Page Layout — Premium Error Experience

Purpose:
Standalone error page layout used by 429 and 503 error pages
that cannot rely on the application's main layout system.
Uses Vite-compiled assets for design system consistency.

Design:
- Centered, vertically aligned
- Large typographic error code (mono, lightweight)
- Clear title and description
- Single CTA back to home
- Full dark mode support
- No gradients, no glow, no ambient effects
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>@yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/core.js'])
    <x-meta.favicons />
</head>

<body class="antialiased h-full bg-secondary-50 dark:bg-secondary-950">
    <div class="min-h-full flex items-center justify-center px-6 py-16">
        <div class="max-w-md w-full text-center">
            {{-- Error Code — Typographic hero --}}
            <p class="text-[120px] md:text-[160px] font-bold leading-none tracking-tight text-secondary-200 dark:text-secondary-800 select-none font-mono tabular-nums">
                @yield('code')
            </p>

            {{-- Title --}}
            <h1 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white tracking-tight -mt-4 mb-3">
                @yield('title')
            </h1>

            {{-- Description --}}
            @hasSection('message')
                <p class="text-sm text-secondary-500 dark:text-secondary-400 leading-relaxed mb-8">
                    @yield('message')
                </p>
            @endif

            {{-- CTA --}}
            <a href="{{ route('redir.locale') }}"
                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 rounded-xl shadow-sm transition-all duration-200 active:scale-[0.98]">
                ← {{ trans('common.go_back_home') }}
            </a>
        </div>
    </div>
</body>

</html>
