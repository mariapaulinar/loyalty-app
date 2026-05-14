{{--
 Reward Loyalty - Proprietary Software
 Copyright (c) 2025 NowSquare. All rights reserved.
 See LICENSE file for terms.

 Voucher Batch Claim Page - The Disney Castle Moment ✨

 Purpose: Create the feeling of walking towards Cinderella's Castle at Disney World.
 That first glimpse of magic. Pure wonder. Pixar-level delight.

 Design Philosophy:
 - Grand, not loud
 - Sophisticated, not childish  
 - Magical, not gimmicky
 - Anticipation building to celebration
--}}

@extends('member.layouts.default')

@section('page_title', trans('common.claim_voucher') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
{{-- Ambient Magic - Floating Particles & Light Rays --}}
<div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none bg-gradient-to-b from-secondary-50 via-primary-50/20 to-secondary-50 dark:from-secondary-950 dark:via-primary-950/10 dark:to-secondary-950">
    {{-- Floating particles - like fireflies in Disney --}}
    <div class="absolute top-1/4 left-1/4 w-1 h-1 bg-primary-400/40 rounded-full animate-float-particle" style="animation-delay: 0s;"></div>
    <div class="absolute top-1/3 right-1/3 w-1.5 h-1.5 bg-emerald-400/30 rounded-full animate-float-particle" style="animation-delay: 2s;"></div>
    <div class="absolute top-1/2 left-1/2 w-1 h-1 bg-violet-400/40 rounded-full animate-float-particle" style="animation-delay: 4s;"></div>
    <div class="absolute bottom-1/3 right-1/4 w-1 h-1 bg-primary-400/30 rounded-full animate-float-particle" style="animation-delay: 1s;"></div>
    <div class="absolute bottom-1/4 left-1/3 w-1.5 h-1.5 bg-emerald-400/40 rounded-full animate-float-particle" style="animation-delay: 3s;"></div>
    
    {{-- Soft light rays from top --}}
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-96 h-96 bg-gradient-radial from-primary-200/20 via-transparent to-transparent dark:from-primary-500/10 blur-3xl animate-pulse-glow"></div>
    <div class="absolute -top-20 left-1/4 w-80 h-80 bg-gradient-radial from-violet-200/15 via-transparent to-transparent dark:from-violet-500/8 blur-3xl animate-pulse-glow-delayed"></div>
</div>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-lg w-full" 
         x-data="{ 
             mounted: false,
             celebrateSuccess: {{ $existingVoucher ? 'true' : 'false' }},
             confettiTriggered: false
         }" 
         x-init="
             setTimeout(() => mounted = true, 100);
             if (celebrateSuccess && !confettiTriggered && typeof window.confettiFireworks !== 'undefined') {
                 confettiTriggered = true;
                 setTimeout(() => window.confettiFireworks(), 600);
             }
         ">
        
        {{-- ═══════════════════════════════════════════════════════════════
            SUCCESS STATE: The Castle Reveal! ✨
        ═══════════════════════════════════════════════════════════════ --}}
        @if($existingVoucher)
            <div class="space-y-6" 
                 x-show="mounted"
                 x-transition:enter="transition ease-out duration-700 delay-100"
                 x-transition:enter-start="opacity-0 scale-90 translate-y-8"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0">
                
                {{-- Success Icon - Elegant & Grand --}}
                <div class="relative mx-auto w-24 h-24 mb-2">
                    {{-- Radiating glow --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/30 to-teal-500/30 dark:from-emerald-500/20 dark:to-teal-500/20 rounded-2xl blur-xl animate-pulse-success"></div>
                    {{-- Main icon container --}}
                    <div class="relative w-full h-full bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl shadow-xl shadow-emerald-500/40 dark:shadow-emerald-500/30 flex items-center justify-center backdrop-blur-sm">
                        <x-ui.icon icon="sparkles" class="h-12 w-12 text-white animate-sparkle" />
                    </div>
                </div>

                <div class="text-center">
                    <h2 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-secondary-900 via-primary-600 to-secondary-900 dark:from-white dark:via-primary-400 dark:to-white bg-clip-text text-transparent mb-2 tracking-tight leading-tight">
                        {{ trans('common.voucher_claimed') }}!
                    </h2>
                    <p class="text-sm text-secondary-600 dark:text-secondary-400">
                        {{ trans('common.your_exclusive_discount') }}
                    </p>
                </div>

                {{-- Voucher Details Card - Premium & Clear --}}
                <div class="bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-800 rounded-2xl shadow-xl shadow-secondary-900/5 dark:shadow-black/20 overflow-hidden backdrop-blur-sm">
                    {{-- Voucher Title & Description --}}
                    <div class="p-6 border-b border-secondary-200 dark:border-secondary-800 bg-gradient-to-br from-secondary-50 to-white dark:from-secondary-900 dark:to-secondary-900/50">
                        <h3 class="font-bold text-lg text-secondary-900 dark:text-white mb-1">
                            {{ $existingVoucher->title ?: $existingVoucher->name }}
                        </h3>
                        @if($existingVoucher->description)
                            <p class="text-sm text-secondary-600 dark:text-secondary-400 leading-relaxed">
                                {{ $existingVoucher->description }}
                            </p>
                        @endif
                    </div>

                    {{-- Code Display - The Star --}}
                    <div class="p-8 bg-gradient-to-br from-primary-50 via-violet-50 to-emerald-50 dark:from-primary-950/30 dark:via-violet-950/20 dark:to-emerald-950/20 border-y-2 border-dashed border-primary-300 dark:border-primary-800">
                        <div class="text-center">
                            <div class="text-[10px] uppercase tracking-widest text-secondary-500 dark:text-secondary-400 mb-3 font-bold">
                                {{ trans('common.your_code') }}
                            </div>
                            <div class="relative inline-block">
                                <div class="text-4xl sm:text-5xl font-bold font-mono text-primary-600 dark:text-primary-400 tracking-[0.3em] px-6 py-3 bg-white dark:bg-secondary-900 rounded-xl shadow-lg">
                                    {{ $existingVoucher->code }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Voucher Details Grid --}}
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Discount Value --}}
                        <div class="flex items-start gap-3 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20">
                            <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center flex-shrink-0">
                                <x-ui.icon icon="{{ $existingVoucher->type === 'percentage' ? 'percent' : 'gift' }}" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mb-0.5">{{ trans('common.discount') }}</div>
                                <div class="font-bold text-emerald-900 dark:text-emerald-300">
                                    {{ $existingVoucher->formatted_value }}
                                </div>
                            </div>
                        </div>

                        {{-- Expiry Date --}}
                        @if($existingVoucher->valid_until)
                            <div class="flex items-start gap-3 p-3 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20">
                                <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-500/20 flex items-center justify-center flex-shrink-0">
                                    <x-ui.icon icon="calendar-clock" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs text-amber-600 dark:text-amber-400 font-medium mb-0.5">{{ trans('common.expires') }}</div>
                                    <div class="font-bold text-amber-900 dark:text-amber-300 text-sm format-date" data-date="{{ $existingVoucher->valid_until }}">
                                        {{ $existingVoucher->valid_until->format('M d, Y') }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Min Purchase (if applicable) --}}
                        @if($existingVoucher->min_purchase_amount)
                            <div class="flex items-start gap-3 p-3 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20">
                                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                                    <x-ui.icon icon="shopping-cart" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs text-blue-600 dark:text-blue-400 font-medium mb-0.5">{{ trans('common.minimum') }}</div>
                                    <div class="font-bold text-blue-900 dark:text-blue-300 text-sm">
                                        {{ moneyFormat($existingVoucher->min_purchase_amount / 100, $existingVoucher->currency) }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Usage Info --}}
                        <div class="flex items-start gap-3 p-3 rounded-xl bg-violet-50 dark:bg-violet-500/10 border border-violet-200 dark:border-violet-500/20">
                            <div class="w-10 h-10 rounded-lg bg-violet-100 dark:bg-violet-500/20 flex items-center justify-center flex-shrink-0">
                                <x-ui.icon icon="ticket" class="w-5 h-5 text-violet-600 dark:text-violet-400" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-violet-600 dark:text-violet-400 font-medium mb-0.5">{{ trans('common.usage') }}</div>
                                <div class="font-bold text-violet-900 dark:text-violet-300 text-sm">
                                    {{ $existingVoucher->is_single_use ? trans('common.one_time_use') : trans('common.reusable') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col gap-3 pt-2">
                    <x-ui.button
                        variant="primary"
                        size="lg"
                        icon="arrow-right"
                        :href="route('member.voucher', ['voucher_id' => $existingVoucher->id])"
                        class="w-full group"
                    >
                        <span>{{ trans('common.view_my_voucher') }}</span>
                        <x-ui.icon icon="arrow-right" class="w-4 h-4 ml-2 rtl:ml-0 rtl:mr-2 transition-transform group-hover:translate-x-1 rtl:group-hover:-translate-x-1" />
                    </x-ui.button>
                    
                    <x-ui.button
                        variant="secondary"
                        size="md"
                        icon="wallet"
                        :href="route('member.cards')"
                        class="w-full"
                    >
                        {{ trans('common.view_in_wallet') }}
                    </x-ui.button>
                </div>
            </div>

        {{-- ═══════════════════════════════════════════════════════════════
            CLAIM STATE: The Walk Towards the Castle 🏰
        ═══════════════════════════════════════════════════════════════ --}}
        @elseif($batch->hasAvailableVouchers())
            <div class="space-y-6"
                 x-show="mounted"
                 x-transition:enter="transition ease-out duration-700 delay-100"
                 x-transition:enter-start="opacity-0 scale-90 translate-y-8"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0">
                
                {{-- Hero Icon - Building Anticipation --}}
                <div class="relative mx-auto w-20 h-20 mb-2">
                    {{-- Radiating rings --}}
                    <div class="absolute inset-0 bg-primary-500/20 dark:bg-primary-500/10 rounded-2xl animate-ping-slow"></div>
                    <div class="absolute inset-0 bg-gradient-to-br from-primary-600 to-violet-600 rounded-2xl shadow-xl shadow-primary-500/40 dark:shadow-primary-500/30 flex items-center justify-center backdrop-blur-sm animate-float-gentle">
                        <x-ui.icon icon="gift" class="h-10 w-10 text-white" />
                    </div>
                </div>

                {{-- Title & Description (Translatable!) --}}
                <div class="text-center px-4">
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold bg-gradient-to-r from-secondary-900 via-primary-600 to-secondary-900 dark:from-white dark:via-primary-400 dark:to-white bg-clip-text text-transparent mb-3 tracking-tight leading-tight">
                        {{ $templateVoucher?->title ?? $batch->name }}
                    </h1>

                    @if($templateVoucher?->description ?? $batch->description)
                        <p class="text-base sm:text-lg text-secondary-600 dark:text-secondary-400 leading-relaxed max-w-md mx-auto">
                            {{ $templateVoucher?->description ?? $batch->description }}
                        </p>
                    @endif
                </div>

                {{-- Main Details Card --}}
                <div class="bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-800 rounded-2xl shadow-xl shadow-secondary-900/5 dark:shadow-black/20 overflow-hidden backdrop-blur-sm">
                    
                    {{-- What You'll Get --}}
                    <div class="p-6 space-y-4">
                        <h3 class="text-xs uppercase tracking-widest text-secondary-500 dark:text-secondary-400 font-bold">
                            {{ trans('common.whats_included') }}
                        </h3>
                        
                        {{-- Discount Display - Large & Clear --}}
                        @if($batch->config['type'] === 'percentage' || $batch->config['type'] === 'fixed_amount')
                            <div class="flex items-center gap-4 p-5 rounded-xl bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-950/30 dark:to-teal-950/20 border border-emerald-200 dark:border-emerald-800/50">
                                <div class="flex-shrink-0 w-14 h-14 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                                    <x-ui.icon icon="{{ $batch->config['type'] === 'percentage' ? 'percent' : 'banknote' }}" class="w-7 h-7 text-white" />
                                </div>
                                <div class="flex-1">
                                    <div class="text-2xl sm:text-3xl font-black text-emerald-900 dark:text-emerald-100">
                                        @if($batch->config['type'] === 'percentage')
                                            {{ $batch->config['value'] }}%
                                        @else
                                            {{ moneyFormat(($batch->config['value'] ?? 0) / 100, $batch->partner->currency ?? 'USD') }}
                                        @endif
                                        <span class="text-base font-semibold ml-1">{{ trans('common.off') }}</span>
                                    </div>
                                    <div class="text-xs text-emerald-700 dark:text-emerald-400 font-medium mt-0.5">
                                        {{ trans('common.instant_discount') }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Additional Info Grid --}}
                        <div class="grid grid-cols-1 gap-3 pt-2">
                            {{-- Expiry --}}
                            @if($batch->config['valid_until'] ?? false)
                                <div class="flex items-center gap-3 p-3 rounded-lg bg-secondary-50 dark:bg-secondary-800/50">
                                    <div class="w-9 h-9 rounded-lg bg-amber-100 dark:bg-amber-500/20 flex items-center justify-center flex-shrink-0">
                                        <x-ui.icon icon="calendar-x" class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                                    </div>
                                    <div class="flex-1 text-sm">
                                        <span class="text-secondary-600 dark:text-secondary-400">{{ trans('common.expires') }}: </span>
                                        <span class="font-bold text-secondary-900 dark:text-white format-date" data-date="{{ $batch->config['valid_until'] }}">
                                            {{ \Carbon\Carbon::parse($batch->config['valid_until'])->format('M d, Y') }}
                                        </span>
                                    </div>
                                </div>
                            @endif

                            {{-- Availability --}}
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-secondary-50 dark:bg-secondary-800/50">
                                <div class="w-9 h-9 rounded-lg bg-primary-100 dark:bg-primary-500/20 flex items-center justify-center flex-shrink-0">
                                    <x-ui.icon icon="users" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                                </div>
                                <div class="flex-1 text-sm">
                                    <span class="font-bold text-secondary-900 dark:text-white tabular-nums format-number">
                                        {{ $batch->unclaimed_count }}
                                    </span>
                                    <span class="text-secondary-600 dark:text-secondary-400"> {{ trans('common.vouchers_remaining') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Progress Bar - Smooth Animation --}}
                    @php
                        $availablePercent = $batch->vouchers_created > 0 
                            ? round((($batch->vouchers_created - $batch->claimed_count) / $batch->vouchers_created) * 100) 
                            : 0;
                    @endphp
                    <div class="px-6 pb-6">
                        <div class="relative h-2 bg-secondary-200 dark:bg-secondary-800 rounded-full overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-primary-500 via-violet-500 to-emerald-500 rounded-full transition-all duration-1000 ease-out origin-left"
                                 style="width: {{ $availablePercent }}%"
                                 x-data="{ width: 0 }"
                                 x-init="setTimeout(() => width = {{ $availablePercent }}, 400)"
                                 :style="`width: ${width}%`"></div>
                        </div>
                        <p class="text-xs text-center text-secondary-500 dark:text-secondary-400 mt-2 font-medium tabular-nums">
                            {{ $availablePercent }}% {{ trans('common.still_available') }}
                        </p>
                    </div>
                </div>

                {{-- Claim Button - The Magic Moment --}}
                @auth('member')
                    <form method="POST" 
                          action="{{ route('member.vouchers.claim.process', ['batchId' => $batch->id, 'token' => $batch->claim_token]) }}"
                          x-data="{ claiming: false }"
                          @submit="claiming = true"
                          class="pt-2">
                        @csrf
                        <button type="submit"
                                :disabled="claiming"
                                class="relative w-full group overflow-hidden cursor-pointer disabled:cursor-not-allowed"
                                :class="claiming && 'scale-95'">
                            {{-- Shimmer effect on hover --}}
                            <div class="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-1000 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                            
                            {{-- Button content --}}
                            <div class="relative px-8 py-4 bg-gradient-to-r from-primary-600 via-violet-600 to-primary-600 hover:from-primary-500 hover:via-violet-500 hover:to-primary-500 disabled:from-primary-400 disabled:via-violet-400 disabled:to-primary-400 text-white font-bold text-lg rounded-xl shadow-lg shadow-primary-500/40 hover:shadow-xl hover:shadow-primary-500/50 transition-all duration-300 disabled:cursor-not-allowed flex items-center justify-center gap-3">
                                <x-ui.icon icon="gift" 
                                           class="w-6 h-6 transition-transform duration-300" 
                                           ::class="claiming ? 'animate-spin' : 'group-hover:scale-110 group-hover:rotate-12'" />
                                <span x-text="claiming ? '{{ trans('common.claiming') }}...' : '{{ trans('common.claim_my_voucher') }}'"></span>
                                <x-ui.icon icon="sparkles" class="w-5 h-5 animate-pulse" x-show="!claiming" />
                            </div>
                        </button>
                    </form>
                @else
                    <div class="space-y-3 pt-2">
                        <x-ui.button
                            variant="primary"
                            size="lg"
                            icon="log-in"
                            :href="route('member.login')"
                            class="w-full text-lg py-4"
                        >
                            {{ trans('common.login_to_claim') }}
                        </x-ui.button>

                        <p class="text-sm text-center text-secondary-600 dark:text-secondary-400">
                            {{ trans('common.dont_have_account') }}
                            <a href="{{ route('member.register') }}" class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-semibold underline underline-offset-2 transition-colors">
                                {{ trans('common.sign_up_free') }}
                            </a>
                        </p>
                    </div>
                @endauth
            </div>

        {{-- ═══════════════════════════════════════════════════════════════
            PAUSED STATE: Temporarily Unavailable
        ═══════════════════════════════════════════════════════════════ --}}
        @elseif($batch->status === 'paused')
            <div class="text-center space-y-6"
                 x-show="mounted"
                 x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                
                <div class="mx-auto w-20 h-20 rounded-2xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <x-ui.icon icon="pause-circle" class="h-10 w-10 text-amber-600 dark:text-amber-400" />
                </div>

                <div>
                    <h2 class="text-3xl font-bold text-secondary-900 dark:text-white mb-2">
                        {{ trans('common.promotion_paused') }}
                    </h2>
                    <p class="text-base text-secondary-600 dark:text-secondary-400 max-w-sm mx-auto leading-relaxed">
                        {{ trans('common.promotion_paused_message') }}
                    </p>
                </div>

                {{-- Show batch details so they know what it is --}}
                @if($templateVoucher)
                    <div class="bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-800 rounded-xl p-5 text-left">
                        <h3 class="font-bold text-lg text-secondary-900 dark:text-white mb-1">
                            {{ $templateVoucher->title ?? $batch->name }}
                        </h3>
                        @if($templateVoucher->description)
                            <p class="text-sm text-secondary-600 dark:text-secondary-400">
                                {{ $templateVoucher->description }}
                            </p>
                        @endif
                    </div>
                @endif

                <x-ui.button
                    variant="secondary"
                    size="lg"
                    icon="home"
                    :href="route('member.index')"
                    class="w-full"
                >
                    {{ trans('common.back_to_home') }}
                </x-ui.button>
            </div>

        {{-- ═══════════════════════════════════════════════════════════════
            EXHAUSTED STATE: The Castle Lights Are Off
        ═══════════════════════════════════════════════════════════════ --}}
        @else
            <div class="text-center space-y-6"
                 x-show="mounted"
                 x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                
                <div class="mx-auto w-20 h-20 rounded-2xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center">
                    <x-ui.icon icon="inbox" class="h-10 w-10 text-secondary-400 dark:text-secondary-500" />
                </div>

                <div>
                    <h2 class="text-3xl font-bold text-secondary-900 dark:text-white mb-2">
                        {{ trans('common.all_claimed') }}
                    </h2>
                    <p class="text-base text-secondary-600 dark:text-secondary-400 max-w-sm mx-auto">
                        {{ trans('common.all_vouchers_from_batch_claimed') }}
                    </p>
                </div>

                <x-ui.button
                    variant="secondary"
                    size="lg"
                    icon="home"
                    :href="route('member.index')"
                    class="w-full"
                >
                    {{ trans('common.back_to_home') }}
                </x-ui.button>
            </div>
        @endif
    </div>
</div>

{{-- Magical Animations CSS --}}
@push('styles')
<style>
/* Floating particles - like fireflies */
@keyframes float-particle {
    0%, 100% { 
        transform: translate(0, 0) scale(1);
        opacity: 0.3;
    }
    25% { 
        transform: translate(40px, -60px) scale(1.2);
        opacity: 0.6;
    }
    50% { 
        transform: translate(-30px, -100px) scale(0.8);
        opacity: 0.8;
    }
    75% { 
        transform: translate(20px, -70px) scale(1.1);
        opacity: 0.5;
    }
}

/* Gentle pulsing glow */
@keyframes pulse-glow {
    0%, 100% { 
        opacity: 0.3;
        transform: scale(1);
    }
    50% { 
        opacity: 0.5;
        transform: scale(1.1);
    }
}

@keyframes pulse-glow-delayed {
    0%, 100% { 
        opacity: 0.2;
        transform: scale(1);
    }
    50% { 
        opacity: 0.4;
        transform: scale(1.15);
    }
}

/* Success pulse */
@keyframes pulse-success {
    0%, 100% { 
        opacity: 0.4;
        transform: scale(1);
    }
    50% { 
        opacity: 0.6;
        transform: scale(1.2);
    }
}

/* Sparkle twinkle */
@keyframes sparkle {
    0%, 100% { 
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }
    50% { 
        opacity: 0.7;
        transform: scale(1.1) rotate(180deg);
    }
}

/* Slow ping for anticipation */
@keyframes ping-slow {
    0% {
        transform: scale(1);
        opacity: 0.4;
    }
    50% {
        transform: scale(1.3);
        opacity: 0;
    }
    100% {
        transform: scale(1.3);
        opacity: 0;
    }
}

/* Gentle floating */
@keyframes float-gentle {
    0%, 100% { 
        transform: translateY(0px);
    }
    50% { 
        transform: translateY(-8px);
    }
}

.animate-float-particle {
    animation: float-particle 15s ease-in-out infinite;
}

.animate-pulse-glow {
    animation: pulse-glow 4s ease-in-out infinite;
}

.animate-pulse-glow-delayed {
    animation: pulse-glow-delayed 5s ease-in-out infinite;
    animation-delay: 1s;
}

.animate-pulse-success {
    animation: pulse-success 2s ease-in-out infinite;
}

.animate-sparkle {
    animation: sparkle 3s ease-in-out infinite;
}

.animate-ping-slow {
    animation: ping-slow 3s cubic-bezier(0, 0, 0.2, 1) infinite;
}

.animate-float-gentle {
    animation: float-gentle 3s ease-in-out infinite;
}

/* Radial gradient for light rays */
.bg-gradient-radial {
    background: radial-gradient(circle, var(--tw-gradient-stops));
}
</style>
@endpush

@endsection
