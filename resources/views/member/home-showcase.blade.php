{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2026 NowSquare. All rights reserved.
See LICENSE file for terms.

Homepage - Showcase Layout

Purpose:
Editorial presentation for single businesses or curated experiences.
Features a hero section with CTA, optional "How it works" guide,
featured cards as curated presentation, and membership tiers preview.
--}}

@extends('member.layouts.default')

@section('page_title', config('default.app_name'))

@section('content')
<div class="min-h-screen bg-white dark:bg-secondary-950">
    {{-- Hero Section --}}
    <section class="relative overflow-hidden">
        {{-- Background Image (if uploaded) or Gradient --}}
        @if($heroImageUrl)
            <div class="absolute inset-0">
                <img src="{{ $heroImageUrl }}" 
                     alt="" 
                     class="w-full h-full object-cover opacity-20 dark:opacity-10">
                <div class="absolute inset-0 bg-gradient-to-b from-white/80 via-white/60 to-white dark:from-secondary-950/80 dark:via-secondary-950/60 dark:to-secondary-950"></div>
            </div>
        @else
            <div class="absolute inset-0">
                <div class="absolute -top-32 -right-32 w-[400px] h-[400px] md:w-[600px] md:h-[600px] bg-primary-100/30 dark:bg-primary-900/15 rounded-full blur-[100px]"></div>
                <div class="absolute -bottom-32 -left-32 w-[300px] h-[300px] md:w-[400px] md:h-[400px] bg-accent-100/25 dark:bg-accent-900/10 rounded-full blur-[100px]"></div>
            </div>
        @endif

        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16 lg:pt-28 lg:pb-24">
            <div class="text-center">
                {{-- Headline - Clean, no logo duplication --}}
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-semibold tracking-tight text-secondary-900 dark:text-white mb-5 leading-[1.15]">
                    {{ trans('common.showcase_headline') }}
                </h1>
                
                <p class="text-lg lg:text-xl text-secondary-500 dark:text-secondary-400 mb-8 max-w-xl mx-auto leading-relaxed">
                    {{ trans('common.showcase_subheadline') }}
                </p>

                {{-- Single Primary CTA - Sign In is already in header --}}
                <div class="flex justify-center">
                    @guest('member')
                        <a href="{{ route('member.register') }}"
                           class="group inline-flex items-center px-8 py-3.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl transition-all duration-200 hover:shadow-lg hover:shadow-primary-500/25 active:scale-[0.98]">
                            {{ trans('common.get_started_free') }}
                            <x-ui.icon icon="arrow-right" class="w-4 h-4 ml-2 rtl:ml-0 rtl:mr-2 group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 transition-transform" />
                        </a>
                    @else
                        <a href="{{ route('member.cards') }}"
                           class="group inline-flex items-center px-8 py-3.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl transition-all duration-200 hover:shadow-lg hover:shadow-primary-500/25 active:scale-[0.98]">
                            {{ trans('common.access_my_wallet') }}
                            <x-ui.icon icon="arrow-right" class="w-4 h-4 ml-2 rtl:ml-0 rtl:mr-2 group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 transition-transform" />
                        </a>
                    @endguest
                </div>

                {{-- Member Count (Social Proof) --}}
                @if($showMemberCount && $memberCount > 0)
                    <p class="mt-8 text-sm text-secondary-500 dark:text-secondary-400">
                        <x-ui.icon icon="users" class="w-4 h-4 inline-block mr-1" />
                        {{ trans('common.showcase_members_joined', ['count' => number_format($memberCount)]) }}
                    </p>
                @endif
            </div>
        </div>
    </section>

    {{-- How It Works Section --}}
    @if($showHowItWorks)
    <section class="relative py-20 lg:py-28 bg-secondary-50 dark:bg-secondary-950">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-secondary-900 dark:text-white mb-4">
                    {{ trans('common.how_it_works') }}
                </h2>
                <p class="text-lg text-secondary-600 dark:text-secondary-400">
                    {{ trans('common.how_it_works_subtitle') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-12">
                {{-- Step 1: Visit --}}
                <div class="text-center group">
                    <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shadow-lg shadow-primary-500/25 group-hover:scale-110 transition-transform duration-300">
                        <x-ui.icon icon="scan-line" class="w-10 h-10 text-white" />
                    </div>
                    <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 font-bold text-sm mb-4">
                        1
                    </div>
                    <h3 class="text-xl font-semibold text-secondary-900 dark:text-white mb-2">
                        {{ trans('common.how_it_works_step_1_title') }}
                    </h3>
                    <p class="text-secondary-600 dark:text-secondary-400">
                        {{ trans('common.how_it_works_step_1_desc') }}
                    </p>
                </div>

                {{-- Step 2: Collect --}}
                <div class="text-center group">
                    <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-accent-500 to-accent-600 flex items-center justify-center shadow-lg shadow-accent-500/25 group-hover:scale-110 transition-transform duration-300">
                        <x-ui.icon icon="smartphone" class="w-10 h-10 text-white" />
                    </div>
                    <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-accent-100 dark:bg-accent-900/30 text-accent-600 dark:text-accent-400 font-bold text-sm mb-4">
                        2
                    </div>
                    <h3 class="text-xl font-semibold text-secondary-900 dark:text-white mb-2">
                        {{ trans('common.how_it_works_step_2_title') }}
                    </h3>
                    <p class="text-secondary-600 dark:text-secondary-400">
                        {{ trans('common.how_it_works_step_2_desc') }}
                    </p>
                </div>

                {{-- Step 3: Enjoy --}}
                <div class="text-center group">
                    <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/25 group-hover:scale-110 transition-transform duration-300">
                        <x-ui.icon icon="sparkles" class="w-10 h-10 text-white" />
                    </div>
                    <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 font-bold text-sm mb-4">
                        3
                    </div>
                    <h3 class="text-xl font-semibold text-secondary-900 dark:text-white mb-2">
                        {{ trans('common.how_it_works_step_3_title') }}
                    </h3>
                    <p class="text-secondary-600 dark:text-secondary-400">
                        {{ trans('common.how_it_works_step_3_desc') }}
                    </p>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- Featured Programs Section --}}
    @if(($cards && $cards->isNotEmpty()) || ($vouchers && $vouchers->isNotEmpty()) || ($stampCards && $stampCards->isNotEmpty()))
    <section class="relative py-20 lg:py-28 bg-white dark:bg-secondary-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 lg:mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-secondary-900 dark:text-white mb-4">
                    {{ trans('common.featured_programs') }}
                </h2>
                <p class="text-lg text-secondary-600 dark:text-secondary-400 max-w-2xl mx-auto">
                    {{ trans('common.featured_programs_subtitle') }}
                </p>
            </div>

            {{-- Cards Grid --}}
            @if($cards && $cards->isNotEmpty())
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8 mb-12">
                    @foreach($cards->take(6) as $index => $card)
                        <div class="transform hover:z-10 relative animate-fade-in-up" style="animation-delay: {{ ($index + 1) * 100 }}ms;">
                            <x-member.premium-card :card="$card" :flippable="false" :links="true" :show-qr="true" :show-balance="true" />
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Vouchers Grid --}}
            @if($vouchers && $vouchers->isNotEmpty())
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8 mb-12">
                    @foreach($vouchers->take(6) as $index => $voucher)
                        <div class="transform hover:z-10 relative animate-fade-in-up" style="animation-delay: {{ ($index + 1) * 100 }}ms;">
                            <x-member.voucher-card 
                                :voucher="$voucher"
                                :member="auth('member')->user()" />
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Stamp Cards Grid --}}
            @if($stampCards && $stampCards->isNotEmpty())
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8">
                    @foreach($stampCards->take(6) as $index => $stampCard)
                        <div class="transform hover:z-10 relative animate-fade-in-up" style="animation-delay: {{ ($index + 1) * 100 }}ms;">
                            <x-member.stamp-card 
                                :stamp-card="$stampCard"
                                :member="auth('member')->user()" />
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
    @endif

    {{-- Membership Tiers Preview (if enabled and tiers exist) --}}
    @if($showTiers)
        @php
            $tiers = \App\Models\Tier::where('is_active', true)
                ->orderBy('level', 'asc')
                ->limit(5)
                ->get();
        @endphp
        
        @if($tiers->isNotEmpty())
        <section class="relative py-20 lg:py-28 bg-gradient-to-b from-secondary-50 to-white dark:from-secondary-950 dark:to-secondary-950">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12 lg:mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold text-secondary-900 dark:text-white mb-4">
                        {{ trans('common.membership_tiers') }}
                    </h2>
                    <p class="text-lg text-secondary-600 dark:text-secondary-400 max-w-2xl mx-auto">
                        {{ trans('common.tiers_description') }}
                    </p>
                </div>

                {{-- Dynamic grid based on tier count --}}
                @php
                    $tierCount = $tiers->count();
                    // Grid logic - always responsive for mobile-first
                    // 1 tier: full width
                    // 2 tiers: 2 columns
                    // 3 tiers: 3 columns on lg, 1 on mobile
                    // 4 tiers: 2 columns on mobile, 4 on lg
                    // 5+ tiers: 2 on mobile, 3 on lg
                    $gridClass = match(true) {
                        $tierCount === 1 => 'grid-cols-1 max-w-sm mx-auto',
                        $tierCount === 2 => 'grid-cols-1 sm:grid-cols-2 max-w-2xl mx-auto',
                        $tierCount === 3 => 'grid-cols-1 sm:grid-cols-3 max-w-4xl mx-auto',
                        $tierCount === 4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
                        default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
                    };
                @endphp
                <div class="grid {{ $gridClass }} gap-5">
                    @foreach($tiers as $tier)
                        @php
                            $tierColor = $tier->color ?? '#6366f1';
                            $isDefault = $tier->is_default;
                        @endphp
                        <div class="group relative flex flex-col bg-white dark:bg-secondary-900 rounded-2xl overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1 border border-secondary-200/50 dark:border-secondary-700/50">
                            
                            {{-- Tier color accent bar at top --}}
                            <div class="h-1 w-full" style="background-color: {{ $tierColor }};"></div>
                            
                            {{-- Card content --}}
                            <div class="flex flex-col flex-1 p-5 text-center">
                                {{-- Emoji/Icon at fixed size --}}
                                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl flex items-center justify-center text-4xl"
                                     style="background-color: {{ $tierColor }}15;">
                                    @if($tier->icon)
                                        <span>{{ $tier->icon }}</span>
                                    @else
                                        <x-ui.icon icon="award" class="w-8 h-8" style="color: {{ $tierColor }};" />
                                    @endif
                                </div>
                                
                                {{-- Title --}}
                                <h3 class="text-lg font-bold text-secondary-900 dark:text-white mb-1.5">
                                    {{ $tier->display_name ?: $tier->name }}
                                </h3>
                                
                                {{-- Default tier indicator (subtle) --}}
                                @if($isDefault)
                                    <span class="inline-flex items-center justify-center gap-1 text-xs font-medium mb-2" style="color: {{ $tierColor }};">
                                        <x-ui.icon icon="check-circle" class="w-3.5 h-3.5" />
                                        {{ trans('common.starting_tier') }}
                                    </span>
                                @endif
                                
                                {{-- Description with fixed min-height for alignment --}}
                                <p class="text-sm text-secondary-500 dark:text-secondary-400 leading-relaxed flex-1 min-h-[3rem]">
                                    {{ $tier->description ?: '—' }}
                                </p>
                                
                                {{-- Points threshold badge --}}
                                @if($tier->points_threshold > 0)
                                    <div class="mt-4 pt-4 border-t border-secondary-100 dark:border-secondary-800">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold"
                                              style="background-color: {{ $tierColor }}15; color: {{ $tierColor }};">
                                            <span class="format-number">{{ $tier->points_threshold }}</span> {{ trans('common.points') }}
                                        </span>
                                    </div>
                                @else
                                    <div class="mt-4 pt-4 border-t border-secondary-100 dark:border-secondary-800">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium text-secondary-400 dark:text-secondary-500 bg-secondary-100 dark:bg-secondary-800">
                                            {{ trans('common.no_points_required') }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif
    @endif

    {{-- Final CTA Section --}}
    <section class="relative py-20 lg:py-28 bg-gradient-to-br from-primary-600 to-primary-700 dark:from-primary-700 dark:to-primary-800">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>
        <div class="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
                {{ trans('common.get_started_free') }}
            </h2>
            <p class="text-xl text-white/80 mb-10">
                {{ trans('common.your_loyalty_simplified_subtitle') }}
            </p>
            @guest('member')
                <x-ui.button href="{{ route('member.register') }}" variant="white" size="lg" class="px-10 py-4 text-lg">
                    {{ trans('common.create_account') }}
                </x-ui.button>
            @else
                <x-ui.button href="{{ route('member.cards') }}" variant="white" size="lg" class="px-10 py-4 text-lg">
                    {{ trans('common.access_my_wallet') }}
                </x-ui.button>
            @endguest
        </div>
    </section>
</div>

<style>
    @keyframes fade-in-up {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .animate-fade-in-up {
        animation: fade-in-up 0.5s ease-out forwards;
        opacity: 0;
    }
</style>
@stop
