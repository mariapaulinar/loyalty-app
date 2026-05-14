@extends('member.layouts.default')

@section('page_title', config('default.app_name'))

@section('content')
{{--
Member Home Page - Premium Landing Experience

A captivating hero section with animated mesh gradients,
designed to convert visitors into members.
--}}

{{-- Hero Section with Animated Mesh Gradient --}}
    {{-- GLOBAL WALLET HERO --}}
    <div class="relative overflow-hidden bg-white dark:bg-secondary-950">
        {{-- Background Elements --}}
        <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-[500px] h-[500px] md:w-[800px] md:h-[800px] bg-gradient-to-br from-blue-50/50 to-purple-50/50 dark:from-blue-900/10 dark:to-purple-900/10 rounded-full blur-3xl opacity-60"></div>
        <div class="absolute bottom-0 left-0 translate-y-1/4 -translate-x-1/4 w-[400px] h-[400px] md:w-[600px] md:h-[600px] bg-gradient-to-tr from-amber-50/50 to-orange-50/50 dark:from-amber-900/10 dark:to-orange-900/10 rounded-full blur-3xl opacity-60"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-24 lg:pt-32 lg:pb-40">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center">
                
                {{-- LEFT: Content --}}
                <div class="text-center lg:text-start z-10">
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-secondary-400 dark:text-secondary-500 mb-8 block">
                        {{ config('default.app_name') }}
                    </span>

                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold tracking-tight text-secondary-900 dark:text-white mb-6 leading-[1.1]">
                        {{ trans('common.your_loyalty_simplified_title') }}
                    </h1>
                    
                    <p class="text-xl text-secondary-600 dark:text-secondary-400 mb-10 leading-relaxed max-w-lg mx-auto lg:mx-0">
                        {{ trans('common.your_loyalty_simplified_subtitle') }}
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        @guest('member')
                            {{-- Primary Action: Get Started Free (Vibrant Orange/Accent) --}}
                            <x-ui.button href="{{ route('member.register') }}" variant="accent" size="lg" class="px-8 py-4 text-lg shadow-xl shadow-accent-500/25 hover:shadow-2xl hover:shadow-accent-500/30 hover:-translate-y-1 transition-all">
                                {{ trans('common.get_started_free') }}
                            </x-ui.button>
                            {{-- Secondary Action: Sign In (Ghost Button - Transparent, Thin Border) --}}
                            <a href="{{ route('member.login') }}" 
                                class="inline-flex items-center justify-center px-8 py-4 text-lg font-medium text-secondary-700 dark:text-white bg-transparent border border-secondary-300 dark:border-white/20 rounded-xl hover:bg-secondary-100 dark:hover:bg-white/10 hover:border-secondary-400 dark:hover:border-white/30 transition-all duration-200">
                                {{ trans('common.sign_in') }}
                            </a>
                        @else
                            <x-ui.button href="{{ route('member.cards') }}" variant="accent" size="lg" class="px-8 py-4 text-lg shadow-xl shadow-accent-500/25 hover:shadow-2xl hover:shadow-accent-500/30 hover:-translate-y-1 transition-all">
                                {{ trans('common.access_my_wallet') }}
                            </x-ui.button>
                        @endguest
                    </div>

                    {{-- Trust Badges --}}
                    <div class="mt-12 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-sm text-secondary-500 dark:text-secondary-400">
                        <div class="flex items-center gap-2">
                            <x-ui.icon icon="shield-check" class="w-5 h-5 text-emerald-500" />
                            <span>{{ trans('common.secure') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-ui.icon icon="zap" class="w-5 h-5 text-amber-500" />
                            <span>{{ trans('common.instant') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-ui.icon icon="heart" class="w-5 h-5 text-pink-500" />
                            <span>{{ trans('common.free_forever') }}</span>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Visual (The Global Wallet) --}}
                <div class="relative h-[400px] md:h-[500px] flex items-center justify-center perspective-1000">
                    
                    {{-- Central Card - Midnight Blue/Charcoal Premium Aesthetic --}}
                    <div class="relative w-[280px] h-[175px] md:w-[320px] md:h-[200px] bg-gradient-to-br from-secondary-900 via-secondary-900 to-secondary-950 rounded-2xl shadow-2xl shadow-secondary-900/40 transform rotate-y-12 rotate-x-6 hover:rotate-y-0 hover:rotate-x-0 transition-transform duration-700 ease-out-back z-20 border border-white/10 group overflow-hidden">
                        {{-- Card Subtle Glow/Overlay --}}
                        <div class="absolute inset-0 bg-gradient-to-tr from-white/5 via-transparent to-white/10 rounded-2xl"></div>
                        {{-- Subtle Pattern Overlay --}}
                        <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
                        
                        {{-- Card Content --}}
                        <div class="absolute inset-0 p-6 flex flex-col justify-between">
                            <div class="flex justify-between items-start">
                                <div class="w-10 h-10 rounded-full bg-white/15 backdrop-blur-md flex items-center justify-center ring-1 ring-white/10">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                                <div class="text-white/70 font-mono text-xs uppercase tracking-wider" style="text-shadow: 0 1px 2px rgba(0,0,0,0.3);">{{ trans('common.premium_member') }}</div>
                            </div>
                            <div>
                                <div class="text-white/70 text-sm mb-1" style="text-shadow: 0 1px 2px rgba(0,0,0,0.3);">{{ trans('common.balance') }}</div>
                                <div class="text-white text-2xl font-bold tracking-wider flex items-center gap-2" style="text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                                    24,500
                                    <svg class="w-5 h-5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="6"/><path d="M18.09 10.37A6 6 0 1 1 10.34 18"/><path d="M7 6h1v4"/><path d="m16.71 13.88.7.71-2.82 2.82"/></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Floating Loyalty Icons --}}
                    
                    {{-- 1. Coins / Points --}}
                    <div class="absolute top-10 right-2 md:right-10 w-12 h-12 md:w-16 md:h-16 bg-white dark:bg-secondary-800 rounded-2xl shadow-lg flex items-center justify-center animate-float z-10">
                        <x-ui.icon icon="coins" class="w-6 h-6 md:w-8 md:h-8 text-amber-500" />
                    </div>

                    {{-- 2. Gift / Rewards --}}
                    <div class="absolute bottom-20 left-2 md:left-0 w-16 h-16 md:w-20 md:h-20 bg-white dark:bg-secondary-800 rounded-full shadow-xl flex items-center justify-center animate-float animation-delay-2000 z-30">
                        <x-ui.icon icon="gift" class="w-7 h-7 md:w-9 md:h-9 text-pink-500" />
                    </div>

                    {{-- 3. Credit Card --}}
                    <div class="absolute top-1/4 left-4 md:left-10 w-12 h-12 md:w-14 md:h-14 bg-white dark:bg-secondary-800 rounded-xl shadow-md flex items-center justify-center animate-float animation-delay-4000 z-10">
                        <x-ui.icon icon="credit-card" class="w-5 h-5 md:w-6 md:h-6 text-blue-500" />
                    </div>

                    {{-- 4. Award / Tier --}}
                    <div class="absolute bottom-10 right-12 md:right-20 w-14 h-14 md:w-18 md:h-18 bg-white dark:bg-secondary-800 rounded-2xl shadow-lg flex items-center justify-center animate-float animation-delay-1000 z-10">
                        <x-ui.icon icon="award" class="w-7 h-7 md:w-8 md:h-8 text-emerald-500" />
                    </div>

                    {{-- 5. Star / Stamps --}}
                    <div class="absolute top-0 md:-top-4 left-1/2 w-10 h-10 md:w-12 md:h-12 bg-white dark:bg-secondary-800 rounded-full shadow-md flex items-center justify-center animate-float animation-delay-3000 z-10">
                        <x-ui.icon icon="star" class="w-4 h-4 md:w-5 md:h-5 text-purple-500" />
                    </div>

                </div>
            </div>
        </div>
    </div>

    <style>
        .perspective-1000 { perspective: 1000px; }
        .rotate-y-12 { transform: rotateY(-12deg) rotateX(6deg); }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animation-delay-1000 { animation-delay: 1s; }
        .animation-delay-2000 { animation-delay: 2s; }
        .animation-delay-3000 { animation-delay: 3s; }
        .animation-delay-4000 { animation-delay: 4s; }
    </style>

{{-- Available Cards Section --}}
@if ($cards && $cards->isNotEmpty())
    <div class="relative bg-white dark:bg-secondary-950">
        {{-- Subtle divider --}}
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-secondary-200 dark:via-secondary-800 to-transparent"></div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
            {{-- Section Header - Primary Blue for Loyalty (Core Product) --}}
            <div class="text-center mb-12 lg:mb-16 space-y-3">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-secondary-400 dark:text-secondary-500">
                    {{ trans('common.loyalty') }}
                </span>
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-secondary-900 dark:text-white tracking-tight">
                    {{ trans('common.available_loyalty_programs') }}
                </h2>
                <p class="text-lg lg:text-xl text-secondary-500 dark:text-secondary-400 max-w-2xl mx-auto leading-relaxed">
                    {{ trans('common.discover_and_join_exclusive_rewards') }}
                </p>
            </div>

            {{-- Cards Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8 items-stretch">
                @foreach($cards as $index => $card)
                    <div class="transform hover:z-10 relative animate-fade-in-up" style="animation-delay: {{ ($index + 1) * 100 }}ms;">
                        <x-member.premium-card :card="$card" :flippable="false" :links="true" :show-qr="true" :show-balance="true" />
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@else
    {{-- Empty State --}}
    <div class="bg-white dark:bg-secondary-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
            <div class="w-24 h-24 mx-auto mb-6 rounded-3xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center">
                <x-ui.icon icon="inbox" class="w-12 h-12 text-secondary-400" />
            </div>
            <h3 class="text-2xl font-bold text-secondary-900 dark:text-white mb-2">
                {{ trans('common.no_cards_collected_yet') }}
            </h3>
            <p class="text-secondary-600 dark:text-secondary-400 mb-8 max-w-md mx-auto">
                {{ __('common.no_programs_available_message') }}
            </p>
            @guest('member')
                <x-ui.button href="{{ route('member.register') }}" variant="accent" size="lg">
                    <x-ui.icon icon="user-plus" class="w-5 h-5" />
                    {{ trans('common.create_account') }}
                </x-ui.button>
            @endguest
        </div>
    </div>
@endif

{{-- Vouchers Section --}}
@if ($vouchers && $vouchers->isNotEmpty())
    <div class="relative bg-gradient-to-b from-secondary-50 to-white dark:from-secondary-950 dark:to-secondary-950 overflow-hidden">
        {{-- Decorative Elements (Purple theme for vouchers) --}}
        <div class="absolute top-0 right-1/4 w-[300px] h-[300px] md:w-[500px] md:h-[500px] bg-gradient-to-br from-purple-100/30 to-violet-100/30 dark:from-purple-900/10 dark:to-violet-900/10 rounded-full blur-3xl opacity-50"></div>
        <div class="absolute bottom-0 left-1/4 w-[250px] h-[250px] md:w-[400px] md:h-[400px] bg-gradient-to-tl from-pink-100/30 to-fuchsia-100/30 dark:from-pink-900/10 dark:to-fuchsia-900/10 rounded-full blur-3xl opacity-50"></div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
            {{-- Section Header - Deep Plum/Violet for Vouchers (Immediate Value) --}}
            <div class="text-center mb-12 lg:mb-16 space-y-3">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-secondary-400 dark:text-secondary-500">
                    {{ trans('common.vouchers') }}
                </span>
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-secondary-900 dark:text-white tracking-tight">
                    {{ trans('common.exclusive_offers_discounts') }}
                </h2>
                <p class="text-lg lg:text-xl text-secondary-500 dark:text-secondary-400 max-w-2xl mx-auto leading-relaxed">
                    {{ trans('common.homepage_vouchers_description') }}
                </p>
            </div>

            {{-- Vouchers Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8 items-stretch">
                @foreach($vouchers as $index => $voucher)
                    <div class="transform hover:z-10 relative animate-fade-in-up" style="animation-delay: {{ ($index + 1) * 100 }}ms;">
                        <x-member.voucher-card 
                            :voucher="$voucher"
                            :member="auth('member')->user()" />
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

{{-- Stamp Cards Section --}}
@if ($stampCards && $stampCards->isNotEmpty())
    <div class="relative bg-gradient-to-b from-white to-secondary-50 dark:from-secondary-950 dark:to-secondary-950 overflow-hidden">
        {{-- Decorative Elements --}}
        <div class="absolute top-0 left-1/4 w-[300px] h-[300px] md:w-[500px] md:h-[500px] bg-gradient-to-br from-green-100/30 to-emerald-100/30 dark:from-green-900/10 dark:to-emerald-900/10 rounded-full blur-3xl opacity-50"></div>
        <div class="absolute bottom-0 right-1/4 w-[250px] h-[250px] md:w-[400px] md:h-[400px] bg-gradient-to-tl from-amber-100/30 to-yellow-100/30 dark:from-amber-900/10 dark:to-yellow-900/10 rounded-full blur-3xl opacity-50"></div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
            {{-- Section Header - Vivid Emerald for Stamps (Activity/Engagement) --}}
            <div class="text-center mb-12 lg:mb-16 space-y-3">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-secondary-400 dark:text-secondary-500">
                    {{ trans('common.stamps') }}
                </span>
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-secondary-900 dark:text-white tracking-tight">
                    {{ trans('common.collect_stamps_earn_rewards') }}
                </h2>
                <p class="text-lg lg:text-xl text-secondary-500 dark:text-secondary-400 max-w-2xl mx-auto leading-relaxed">
                    {{ trans('common.stamp_cards_description') }}
                </p>
            </div>

            {{-- Stamp Cards Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8 items-stretch">
                @foreach($stampCards as $index => $stampCard)
                    <div class="transform hover:z-10 relative animate-fade-in-up" style="animation-delay: {{ ($index + 1) * 100 }}ms;">
                        <x-member.stamp-card 
                            :stamp-card="$stampCard"
                            :member="auth('member')->user()" />
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

{{-- Inline Styles for Animations --}}
<style>
    @keyframes blob {
        0%, 100% { transform: translate(0, 0) scale(1); }
        25% { transform: translate(20px, -30px) scale(1.05); }
        50% { transform: translate(-20px, 20px) scale(0.95); }
        75% { transform: translate(30px, 10px) scale(1.02); }
    }
    
    .animate-blob {
        animation: blob 8s ease-in-out infinite;
    }
    
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    
    .animation-delay-4000 {
        animation-delay: 4s;
    }
</style>
@stop
