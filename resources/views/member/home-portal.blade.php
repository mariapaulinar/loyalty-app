{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2026 NowSquare. All rights reserved.
See LICENSE file for terms.

Homepage - Portal Layout

Design Philosophy:
Centered, focused, with the same visual language as Smart Wallet.
Card visual centered with CTA below.
--}}

@extends('member.layouts.default')

@section('page_title', config('default.app_name'))

@section('content')
<div class="relative flex items-center justify-center bg-white dark:bg-secondary-950" style="min-height: calc(100vh - 177px);">
    
    {{-- Ambient Background --}}
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-[500px] h-[500px] md:w-[700px] md:h-[700px] bg-gradient-to-br from-blue-50/50 to-purple-50/50 dark:from-blue-900/10 dark:to-purple-900/10 rounded-full blur-3xl opacity-60"></div>
        <div class="absolute bottom-0 left-0 translate-y-1/4 -translate-x-1/4 w-[400px] h-[400px] md:w-[500px] md:h-[500px] bg-gradient-to-tr from-amber-50/50 to-orange-50/50 dark:from-amber-900/10 dark:to-orange-900/10 rounded-full blur-3xl opacity-60"></div>
    </div>

    {{-- Main Content - Centered --}}
    <div class="relative w-full max-w-lg mx-auto px-6 py-12 text-center"
         x-data="{ mounted: false }"
         x-init="setTimeout(() => mounted = true, 50)">
        
        <div x-show="mounted"
             x-transition:enter="transition ease-out duration-600"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0">
            
            {{-- Card Visual --}}
            <div class="relative h-[320px] md:h-[380px] flex items-center justify-center mb-8">
                
                {{-- Central Card --}}
                <div class="w-[280px] h-[175px] md:w-[320px] md:h-[200px] rounded-2xl shadow-2xl overflow-hidden"
                     style="background: linear-gradient(135deg, #202E44 0%, #1A2537 50%, #141D2B 100%); transform: rotate(-6deg); border: 1px solid rgba(255,255,255,0.1);">
                    {{-- Card Glow --}}
                    <div class="absolute inset-0 bg-gradient-to-tr from-white/5 via-transparent to-white/10"></div>
                    {{-- Card Content --}}
                    <div class="relative h-full p-5 md:p-6 flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <div class="w-9 h-9 md:w-10 md:h-10 rounded-full bg-white/15 flex items-center justify-center" style="backdrop-filter: blur(8px);">
                                <svg class="w-5 h-5 md:w-6 md:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            <div class="text-white/70 font-mono text-xs uppercase tracking-wider">{{ trans('common.premium_member') }}</div>
                        </div>
                        <div>
                            <div class="text-white/60 text-sm mb-1">{{ trans('common.balance') }}</div>
                            <div class="text-white text-xl md:text-2xl font-bold tracking-wider flex items-center gap-2">
                                24,500
                                <x-ui.icon icon="coins" class="w-5 h-5 text-amber-400" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Floating Icons --}}
                <div class="absolute top-4 right-2 md:right-6 w-11 h-11 md:w-14 md:h-14 bg-white dark:bg-secondary-800 rounded-2xl shadow-lg flex items-center justify-center portal-float">
                    <x-ui.icon icon="coins" class="w-5 h-5 md:w-7 md:h-7 text-amber-500" />
                </div>

                <div class="absolute bottom-10 left-2 md:left-6 w-14 h-14 md:w-16 md:h-16 bg-white dark:bg-secondary-800 rounded-full shadow-xl flex items-center justify-center portal-float portal-delay-2">
                    <x-ui.icon icon="gift" class="w-6 h-6 md:w-7 md:h-7 text-pink-500" />
                </div>

                <div class="absolute top-1/4 left-4 md:left-10 w-10 h-10 md:w-12 md:h-12 bg-white dark:bg-secondary-800 rounded-xl shadow-md flex items-center justify-center portal-float portal-delay-4">
                    <x-ui.icon icon="credit-card" class="w-4 h-4 md:w-5 md:h-5 text-blue-500" />
                </div>

                <div class="absolute bottom-6 right-6 md:right-14 w-11 h-11 md:w-14 md:h-14 bg-white dark:bg-secondary-800 rounded-2xl shadow-lg flex items-center justify-center portal-float portal-delay-1">
                    <x-ui.icon icon="award" class="w-5 h-5 md:w-7 md:h-7 text-emerald-500" />
                </div>

                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-9 h-9 md:w-10 md:h-10 bg-white dark:bg-secondary-800 rounded-full shadow-md flex items-center justify-center portal-float portal-delay-3">
                    <x-ui.icon icon="star" class="w-4 h-4 md:w-5 md:h-5 text-purple-500" />
                </div>
            </div>

            {{-- Text --}}
            <h1 class="text-2xl sm:text-3xl font-semibold text-secondary-900 dark:text-white tracking-tight mb-2">
                {{ trans('common.portal_welcome') }}
            </h1>
            
            <p class="text-secondary-500 dark:text-secondary-400 text-base mb-8 max-w-sm mx-auto">
                {{ trans('common.portal_tagline') }}
            </p>

            {{-- CTA --}}
            <div class="space-y-4 max-w-xs mx-auto">
                @guest('member')
                    <a href="{{ route('member.login') }}"
                       class="group relative flex items-center justify-center w-full px-6 py-3.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl transition-all duration-200 hover:shadow-lg hover:shadow-primary-500/25 active:scale-[0.98]">
                        <x-ui.icon icon="log-in" class="w-4 h-4 mr-2.5 opacity-80 group-hover:opacity-100 transition-opacity" />
                        {{ trans('common.sign_in') }}
                    </a>

                    <p class="text-sm text-secondary-500 dark:text-secondary-400">
                        {{ trans('common.dont_have_account') }}
                        <a href="{{ route('member.register') }}" 
                           class="text-primary-600 dark:text-primary-400 font-medium hover:underline underline-offset-2">
                            {{ trans('common.sign_up_free') }}
                        </a>
                    </p>
                @else
                    <a href="{{ route('member.cards') }}"
                       class="group relative flex items-center justify-center w-full px-6 py-3.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl transition-all duration-200 hover:shadow-lg hover:shadow-primary-500/25 active:scale-[0.98]">
                        <x-ui.icon icon="wallet" class="w-4 h-4 mr-2.5 opacity-80 group-hover:opacity-100 transition-opacity" />
                        {{ trans('common.access_my_wallet') }}
                    </a>
                @endguest
            </div>

            {{-- Trust indicators --}}
            <div class="mt-8 flex items-center justify-center gap-6 text-xs text-secondary-400 dark:text-secondary-500">
                <span class="flex items-center gap-1.5">
                    <x-ui.icon icon="shield-check" class="w-3.5 h-3.5" />
                    {{ trans('common.secure') }}
                </span>
                <span class="w-px h-3 bg-secondary-200 dark:bg-secondary-700"></span>
                <span class="flex items-center gap-1.5">
                    <x-ui.icon icon="zap" class="w-3.5 h-3.5" />
                    {{ trans('common.instant') }}
                </span>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes portalFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-12px); }
    }
    .portal-float { animation: portalFloat 5s ease-in-out infinite; }
    .portal-delay-1 { animation-delay: 1s; }
    .portal-delay-2 { animation-delay: 2s; }
    .portal-delay-3 { animation-delay: 3s; }
    .portal-delay-4 { animation-delay: 4s; }
</style>
@endsection
