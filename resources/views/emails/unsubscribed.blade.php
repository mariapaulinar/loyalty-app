{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Unsubscribe Confirmation Page

Purpose: Confirm to members that they have been unsubscribed from marketing emails.
Philosophy: Clear, reassuring, no dark patterns.
Design: Simple, friendly, leaves a good impression.
--}}

@php
    // Detect member's preferred locale for this standalone page
    $memberLocale = $member->preferredLocale();
    app()->setLocale($memberLocale);
    $direction = in_array($memberLocale, ['ar_SA']) ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $memberLocale) }}" dir="{{ $direction }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ trans('common.email_campaign.unsubscribed_title') }} - {{ config('default.app_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-meta.favicons />
</head>
<body class="antialiased bg-gradient-to-br from-secondary-50 to-secondary-100 dark:from-secondary-950 dark:to-secondary-900 min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md">
        {{-- Card --}}
        <div class="bg-white dark:bg-secondary-800 rounded-3xl shadow-xl shadow-secondary-200/50 dark:shadow-black/30 p-8 md:p-10 text-center">
            
            {{-- Icon --}}
            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                <x-ui.icon icon="mail-check" class="w-10 h-10 text-emerald-600 dark:text-emerald-400" />
            </div>

            {{-- Title --}}
            <h1 class="text-2xl font-bold text-secondary-900 dark:text-white mb-3">
                {{ trans('common.email_campaign.unsubscribed_title') }}
            </h1>

            {{-- Message --}}
            <p class="text-secondary-600 dark:text-secondary-400 mb-6 leading-relaxed">
                {{ trans('common.email_campaign.unsubscribed_message', ['email' => $member->email]) }}
            </p>

            {{-- Note --}}
            <div class="p-4 bg-secondary-50 dark:bg-secondary-700/50 rounded-xl mb-8">
                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                    {{ trans('common.email_campaign.unsubscribed_note') }}
                </p>
            </div>

            {{-- Action --}}
            <a 
                href="{{ config('app.url') }}" 
                class="inline-flex items-center justify-center gap-2 w-full px-6 py-3 
                       text-sm font-semibold text-white 
                       bg-gradient-to-r from-primary-600 to-primary-500
                       hover:from-primary-500 hover:to-primary-400
                       rounded-xl shadow-lg shadow-primary-500/25 hover:shadow-xl hover:shadow-primary-500/30
                       transition-all duration-200 active:scale-[0.98]"
            >
                <x-ui.icon icon="home" class="w-5 h-5" />
                {{ trans('common.go_to_homepage') }}
            </a>
        </div>

        {{-- Footer --}}
        <p class="mt-6 text-center text-sm text-secondary-400 dark:text-secondary-500">
            {{ config('default.app_name') }}
        </p>
    </div>

</body>
</html>

