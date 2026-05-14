@extends('member.layouts.default')

@section('page_title', trans('common.claim_reward') . config('default.page_title_delimiter') . $reward->title . config('default.page_title_delimiter') . $card->head . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="space-y-6 max-w-xl mx-auto px-4 md:px-8 py-8 md:py-8">
    {{-- Simple Back Button (Mobile-First) --}}
    <div class="mb-6 animate-fade-in">
        <a href="{{ route('member.card.reward', ['card_id' => $card->id, 'reward_id' => $reward->id]) }}" 
           class="inline-flex items-center gap-2 text-sm font-medium text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white transition-colors">
            <x-ui.icon icon="arrow-left" class="w-4 h-4" />
            <span>{{ trans('common.back') }}</span>
        </a>
    </div>

    {{-- Reward Info Card (Above the fold - What am I claiming?) --}}
    <div class="mb-6 animate-fade-in-up">
        <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 shadow-sm overflow-hidden">
            <div class="p-6 md:p-8 space-y-5">
                {{-- Reward Title & Visual Confirmation --}}
                <div class="text-center space-y-4">
                    {{-- Reward Image (Visual confirmation of what you're claiming) --}}
                    @if($reward->images && isset($reward->images[0]))
                        <div class="flex justify-center">
                            <div class="relative group">
                                <img src="{{ $reward->images[0]['sm'] ?? $reward->images[0]['md'] }}" 
                                     alt="{{ parse_attr($reward->title) }}"
                                     class="w-28 h-28 md:w-36 md:h-36 object-cover rounded-2xl shadow-lg ring-1 ring-secondary-900/5 dark:ring-white/5" />
                                {{-- Subtle glow on hover --}}
                                <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-primary-500/0 to-primary-500/0 group-hover:from-primary-500/5 group-hover:to-transparent transition-all duration-300 pointer-events-none"></div>
                            </div>
                        </div>
                    @endif
                    
                    <h1 class="text-2xl md:text-3xl font-bold text-secondary-900 dark:text-white leading-tight">
                        {{ $reward->title }}
                    </h1>
                </div>

                {{-- Points Transaction Summary --}}
                <div class="bg-secondary-50 dark:bg-secondary-900/50 rounded-2xl p-5 space-y-3 border border-secondary-200/80 dark:border-secondary-700/50">
                    {{-- Cost --}}
                    <div class="flex items-center justify-between pb-3 border-b border-secondary-200/60 dark:border-secondary-700/60">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-lg shadow-amber-500/25">
                                <x-ui.icon icon="coins" class="w-4.5 h-4.5 text-white drop-shadow-sm" />
                            </div>
                            <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300">{{ trans('common.points_required') }}</span>
                        </div>
                        <span class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums format-number">
                            {{ $reward->points }}
                        </span>
                    </div>
                    
                    {{-- Balance Before --}}
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-secondary-600 dark:text-secondary-400">{{ trans('common.your_balance') }}</span>
                        <span class="font-semibold text-secondary-900 dark:text-white tabular-nums format-number">
                            {{ $balance }}
                        </span>
                    </div>
                    
                    {{-- Balance After --}}
                    <div class="flex items-center justify-between text-sm pt-3 border-t border-secondary-200/60 dark:border-secondary-700/60">
                        <span class="text-secondary-600 dark:text-secondary-400">{{ trans('common.balance_after_redemption') }}</span>
                        <span class="font-semibold tabular-nums format-number {{ ($balance - $reward->points) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ max(0, $balance - $reward->points) }}
                        </span>
                    </div>
                </div>

                {{-- Status Badge --}}
                @if(auth('member')->check())
                    @if($card->isExpired)
                        <div class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl w-full justify-center">
                            <x-ui.icon icon="alert-triangle" class="w-4 h-4 text-red-600 dark:text-red-400" />
                            <span class="font-medium text-sm text-red-700 dark:text-red-300">{{ trans('common.card_expired') }}</span>
                        </div>
                    @elseif($reward->points <= $balance)
                        <div class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl w-full justify-center">
                            <x-ui.icon icon="check-circle" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                            <span class="font-medium text-sm text-emerald-700 dark:text-emerald-300">{{ trans('common.ready_to_claim') }}</span>
                        </div>
                    @else
                        <div class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl w-full justify-center">
                            <x-ui.icon icon="coins" class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                            <span class="font-medium text-sm text-amber-700 dark:text-amber-300">
                                {{ trans('common.need') }} <span class="format-number">{{ $reward->points - $balance }}</span> {{ trans('common.more_points') }}
                            </span>
                        </div>
                    @endif
                @else
                    <div class="inline-flex items-center gap-2 px-4 py-2.5 bg-secondary-100 dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 rounded-xl w-full justify-center">
                        <x-ui.icon icon="log-in" class="w-4 h-4 text-secondary-600 dark:text-secondary-400" />
                        <span class="font-medium text-sm text-secondary-700 dark:text-secondary-300">{{ trans('common.log_in_to_claim_reward') }}</span>
                    </div>
                @endif

                {{-- Reward Validity & Terms --}}
                @if($reward->expiration_date || $reward->active_from)
                    <div class="pt-3 border-t border-secondary-200/60 dark:border-secondary-700/60 space-y-2">
                        <div class="flex items-center gap-2 text-xs text-secondary-500 dark:text-secondary-400">
                            <x-ui.icon icon="clock" class="w-3.5 h-3.5" />
                            <span class="format-date-range"
                                  data-date-from="{{ $reward->active_from }}"
                                  data-date-to="{{ $reward->expiration_date }}"
                                  data-prefix-from="{{ trans('common.valid_from') }}"
                                  data-prefix-to="{{ trans('common.expires') }}">
                                @if($reward->active_from && $reward->expiration_date)
                                    {{ trans('common.valid') }} {{ $reward->active_from->format('M d') }} - {{ $reward->expiration_date->format('M d, Y') }}
                                @elseif($reward->expiration_date)
                                    {{ trans('common.expires') }} {{ $reward->expiration_date->format('M d, Y') }}
                                @elseif($reward->active_from)
                                    {{ trans('common.valid_from') }} {{ $reward->active_from->format('M d, Y') }}
                                @endif
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- QR Code Section (Hero - The Main Event) --}}
    <div class="animate-fade-in-up" style="animation-delay: 100ms;">
        <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 shadow-sm overflow-hidden">
            {{-- Instructions Header (When Claimable) - Borderless, clean --}}
            @if(auth('member')->check() && $reward->points <= $balance && !$card->isExpired)
                <div class="px-6 py-4 bg-gradient-to-r from-secondary-50/80 to-secondary-100/50 dark:from-secondary-900/40 dark:to-secondary-800/30">
                    <div class="flex items-center justify-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-primary-500/10 dark:bg-primary-500/20 flex items-center justify-center flex-shrink-0">
                            <x-ui.icon icon="scan" class="w-4.5 h-4.5 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                                {{ trans('common.show_qr_to_staff') }}
                            </p>
                            <p class="text-xs text-secondary-500 dark:text-secondary-400">
                                {{ trans('common.staff_will_scan_to_complete') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif
            
            {{-- QR Code Display --}}
            <div class="p-8 md:p-10 flex items-center justify-center bg-gradient-to-br from-secondary-50 via-white to-secondary-50/80 dark:from-secondary-900 dark:via-secondary-800/50 dark:to-secondary-900">
                @if(auth('member')->check() && $reward->points <= $balance && !$card->isExpired)
                    <div class="relative group">
                        {{-- Ambient Glow --}}
                        <div class="absolute inset-0 bg-gradient-to-br from-primary-500/20 via-primary-400/10 to-transparent blur-3xl rounded-full scale-75 group-hover:scale-100 transition-all duration-700 ease-out"></div>
                        
                        {{-- QR Code Container --}}
                        <div class="relative bg-white dark:bg-secondary-900 p-5 md:p-6 rounded-2xl shadow-2xl ring-1 ring-secondary-900/5 dark:ring-white/5">
                            <img
                                class="w-64 h-64 md:w-80 md:h-80 rounded-xl"
                                data-qr-url="{!! $claimRewardUrl !!}"
                                data-qr-color-light="#ffffff"
                                data-qr-color-dark="#18181b"
                                data-qr-scale="8"
                                alt="QR Code"
                            />
                        </div>
                        
                        {{-- Animated Corner Markers (Refined, subtle) --}}
                        <div class="absolute -top-1.5 -left-1.5 w-8 h-8 border-t-[3px] border-l-[3px] border-primary-500 rounded-tl-xl animate-pulse-subtle"></div>
                        <div class="absolute -top-1.5 -right-1.5 w-8 h-8 border-t-[3px] border-r-[3px] border-primary-500 rounded-tr-xl animate-pulse-subtle" style="animation-delay: 0.15s;"></div>
                        <div class="absolute -bottom-1.5 -left-1.5 w-8 h-8 border-b-[3px] border-l-[3px] border-primary-500 rounded-bl-xl animate-pulse-subtle" style="animation-delay: 0.3s;"></div>
                        <div class="absolute -bottom-1.5 -right-1.5 w-8 h-8 border-b-[3px] border-r-[3px] border-primary-500 rounded-br-xl animate-pulse-subtle" style="animation-delay: 0.45s;"></div>
                    </div>
                @else
                    {{-- Placeholder QR (Elegant, minimal) --}}
                    <div class="relative">
                        <div class="w-64 h-64 md:w-80 md:h-80 bg-secondary-100 dark:bg-secondary-800/50 rounded-2xl flex items-center justify-center border-2 border-dashed border-secondary-300/60 dark:border-secondary-700/60">
                            <div class="text-center p-6 space-y-4">
                                <div class="w-20 h-20 mx-auto rounded-2xl bg-secondary-200/50 dark:bg-secondary-700/50 flex items-center justify-center">
                                    <x-ui.icon icon="qr-code" class="w-12 h-12 text-secondary-400 dark:text-secondary-600 opacity-60" />
                                </div>
                                <div class="space-y-1">
                                    <p class="text-sm font-semibold text-secondary-700 dark:text-secondary-300">
                                        @if(!auth('member')->check())
                                            {{ trans('common.login_required') }}
                                        @elseif($card->isExpired)
                                            {{ trans('common.card_expired') }}
                                        @else
                                            {{ trans('common.insufficient_points') }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-secondary-500 dark:text-secondary-400">
                                        @if(!auth('member')->check())
                                            {{ trans('common.please_log_in') }}
                                        @elseif(!$card->isExpired && $reward->points > $balance)
                                            {{ trans('common.earn_more_to_unlock') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Loyalty Card Reference (Minimal, elegant) --}}
    <div class="mt-6 animate-fade-in-up text-center" style="animation-delay: 200ms;">
        <a href="{{ route('member.card', ['card_id' => $card->id]) }}" 
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm text-secondary-500 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white hover:bg-secondary-100/50 dark:hover:bg-secondary-800/50 transition-all duration-300">
            <x-ui.icon icon="credit-card" class="w-4 h-4" />
            <span>{{ $card->head }}</span>
        </a>
    </div>
</div>

@if(auth('member')->check() && $reward->points <= $balance && !$card->isExpired)
<script>
    // QR URL for testing - easily scannable from console
    console.log('🔗 Claim Reward QR URL:', '{!! $claimRewardUrl !!}');
</script>
@endif

<style>
    /* Premium fade-in animations */
    .animate-fade-in {
        animation: fade-in 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }
    
    .animate-fade-in-up {
        animation: fade-in-up 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        opacity: 0;
    }
    
    @keyframes fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
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
    
    /* Refined pulse for QR corners */
    @keyframes pulse-subtle {
        0%, 100% { 
            opacity: 1;
            transform: scale(1);
        }
        50% { 
            opacity: 0.6;
            transform: scale(0.95);
        }
    }
    
    .animate-pulse-subtle {
        animation: pulse-subtle 3s ease-in-out infinite;
    }
    
    /* Accessibility: Respect prefers-reduced-motion */
    @media (prefers-reduced-motion: reduce) {
        .animate-fade-in,
        .animate-fade-in-up,
        .animate-pulse-subtle {
            animation: none !important;
            opacity: 1 !important;
            transform: none !important;
        }
    }
</style>
@stop
