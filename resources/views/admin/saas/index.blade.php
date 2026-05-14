{{--
    Reward Loyalty - Proprietary Software
    Copyright (c) 2025 NowSquare. All rights reserved.
    See LICENSE file for terms.

    Admin SaaS Dashboard — Subscription & Platform Overview
--}}

@extends('admin.layouts.default')

@section('page_title', trans('common.saas_overview') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="min-h-screen relative" x-data="saasDashboard()" x-init="initCounters()">

    <div class="w-full max-w-7xl mx-auto px-6 py-8 md:px-10 md:py-14 lg:py-16">

        {{-- HEADER --}}
        <header class="mb-12 md:mb-16 animate-fade-in">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full text-xs font-medium
                            {{ $billingEnabled ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-secondary-500/10 text-secondary-600 dark:text-secondary-400' }}">
                            <x-ui.icon :icon="$billingEnabled ? 'credit-card' : 'hand-coins'" class="w-3.5 h-3.5" />
                            {{ $billingEnabled ? ucfirst($providerName) : trans('common.saas_manual') }}
                        </span>
                    </div>
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-secondary-900 dark:text-white tracking-tight">
                        {{ trans('common.saas_overview') }}
                    </h1>
                    <p class="text-lg text-secondary-400 dark:text-secondary-500 font-light max-w-xl">
                        {{ trans('common.saas_dashboard_subtitle') }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.data.insert', ['name' => 'partners']) }}"
                        class="inline-flex items-center gap-2.5 px-5 py-3 rounded-2xl text-sm font-medium
                            bg-secondary-900 dark:bg-white text-white dark:text-secondary-900
                            hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 ease-out">
                        <x-ui.icon icon="plus" class="w-4 h-4" />
                        {{ trans('common.add_partner') }}
                    </a>
                </div>
            </div>
        </header>

        @if($partnerCount === 0)
            {{-- EMPTY STATE --}}
            <section class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-12 animate-fade-in">
                <div class="text-center max-w-md mx-auto">
                    <x-ui.icon icon="store" class="w-10 h-10 text-secondary-300 dark:text-secondary-600 mx-auto mb-5" />
                    <h2 class="text-2xl font-bold text-secondary-900 dark:text-white mb-3">{{ trans('common.saas_no_partners') }}</h2>
                    <p class="text-secondary-400 dark:text-secondary-500 mb-8 text-lg font-light">
                        {{ trans('common.saas_billing_disabled_hint') }}
                    </p>
                    <a href="{{ route('admin.data.insert', ['name' => 'partners']) }}"
                        class="inline-flex items-center gap-2.5 px-8 py-4 bg-secondary-900 dark:bg-white text-white dark:text-secondary-900 rounded-2xl font-medium
                            hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 ease-out">
                        <x-ui.icon icon="plus" class="w-5 h-5" />
                        {{ trans('common.add_partner') }}
                    </a>
                </div>
            </section>
        @else

        {{-- PARTNER STATUS BREAKDOWN --}}
        <section class="mb-14 animate-fade-in" style="animation-delay: 80ms;">
            <h2 class="text-xl font-bold text-secondary-900 dark:text-white mb-6">{{ trans('common.saas_partner_status') }}</h2>

            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                <div class="divide-y divide-secondary-100 dark:divide-secondary-800">
                    @php
                        // Full ordered status list with display config
                        $statusDisplay = [
                            \App\Services\EntitlementService::STATUS_ACTIVE => [
                                'label' => trans('common.saas_active_subscriptions'),
                                'dot'   => 'bg-emerald-500',
                                'text'  => 'text-emerald-600 dark:text-emerald-400',
                            ],
                            \App\Services\EntitlementService::STATUS_TRIALING => [
                                'label' => trans('common.saas_trialing'),
                                'dot'   => 'bg-primary-500',
                                'text'  => 'text-primary-600 dark:text-primary-400',
                            ],
                            \App\Services\EntitlementService::STATUS_PAST_DUE => [
                                'label' => trans('common.saas_past_due'),
                                'dot'   => 'bg-amber-500',
                                'text'  => 'text-amber-600 dark:text-amber-400',
                            ],
                            \App\Services\EntitlementService::STATUS_CANCELLED => [
                                'label' => trans('common.saas_cancelled'),
                                'dot'   => 'bg-red-500',
                                'text'  => 'text-red-600 dark:text-red-400',
                            ],
                            \App\Services\EntitlementService::STATUS_INCOMPLETE => [
                                'label' => trans('common.saas_incomplete'),
                                'dot'   => 'bg-orange-500',
                                'text'  => 'text-orange-600 dark:text-orange-400',
                            ],
                            \App\Services\EntitlementService::STATUS_SUSPENDED => [
                                'label' => trans('common.saas_suspended'),
                                'dot'   => 'bg-rose-500',
                                'text'  => 'text-rose-600 dark:text-rose-400',
                            ],
                            \App\Services\EntitlementService::STATUS_MANUAL => [
                                'label' => trans('common.saas_manual'),
                                'dot'   => 'bg-secondary-400 dark:bg-secondary-500',
                                'text'  => 'text-secondary-600 dark:text-secondary-400',
                            ],
                            \App\Services\EntitlementService::STATUS_LEGACY => [
                                'label' => trans('common.saas_legacy'),
                                'dot'   => 'bg-secondary-300 dark:bg-secondary-600',
                                'text'  => 'text-secondary-500 dark:text-secondary-400',
                            ],
                            \App\Services\EntitlementService::STATUS_BILLING_DISABLED => [
                                'label' => trans('common.saas_billing_disabled'),
                                'dot'   => 'bg-secondary-300 dark:bg-secondary-600',
                                'text'  => 'text-secondary-500 dark:text-secondary-400',
                            ],
                        ];
                    @endphp

                    @foreach($statusDisplay as $statusKey => $display)
                        @php
                            $count = $statusGroups[$statusKey]['count'] ?? 0;
                        @endphp
                        <div class="flex items-center justify-between px-6 py-4 {{ $count === 0 ? 'opacity-40' : '' }}">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex w-2.5 h-2.5 rounded-full {{ $display['dot'] }} flex-shrink-0"></span>
                                <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300">
                                    {{ $display['label'] }}
                                </span>
                            </div>
                            <span class="text-lg font-bold tabular-nums {{ $count > 0 ? $display['text'] : 'text-secondary-300 dark:text-secondary-600' }}">
                                {{ $count }}
                            </span>
                        </div>
                    @endforeach

                    {{-- Total --}}
                    <div class="flex items-center justify-between px-6 py-4 bg-secondary-50 dark:bg-secondary-800/50">
                        <span class="text-sm font-semibold text-secondary-900 dark:text-white">
                            {{ trans('common.saas_total_partners') }}
                        </span>
                        <span class="text-lg font-bold tabular-nums text-secondary-900 dark:text-white">
                            {{ $partnerCount }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        {{-- TWO-COLUMN: Plan Distribution + Attention --}}
        <div class="grid grid-cols-1 xl:grid-cols-5 gap-8 mb-14 xl:items-stretch">

            {{-- LEFT: Plan Distribution (3/5) --}}
            <div class="xl:col-span-3 flex">
                <section class="bg-white dark:bg-secondary-900 rounded-2xl p-8 border border-secondary-200 dark:border-secondary-800 w-full flex flex-col animate-fade-in" style="animation-delay: 160ms;">
                    <h2 class="text-xl font-bold text-secondary-900 dark:text-white mb-8">{{ trans('common.saas_plan_distribution') }}</h2>

                    <div class="space-y-5 flex-1">
                        @foreach($planDistribution as $key => $tier)
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300">{{ $tier['name'] }}</span>
                                    <span class="text-sm text-secondary-500 dark:text-secondary-400 tabular-nums font-mono">
                                        {{ $tier['count'] }} <span class="text-secondary-300 dark:text-secondary-600">({{ $tier['percentage'] }}%)</span>
                                    </span>
                                </div>
                                <div class="w-full h-2.5 bg-secondary-100 dark:bg-secondary-800 rounded-full overflow-hidden">
                                    @php
                                        $barColor = match($tier['color']) {
                                            'primary' => 'bg-primary-500',
                                            'amber' => 'bg-amber-500',
                                            'violet' => 'bg-violet-500',
                                            default => 'bg-secondary-400 dark:bg-secondary-500',
                                        };
                                    @endphp
                                    <div class="{{ $barColor }} h-full rounded-full transition-all duration-700 ease-out"
                                         style="width: {{ max($tier['percentage'], $tier['count'] > 0 ? 2 : 0) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 pt-6 border-t border-secondary-100 dark:border-secondary-800">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-secondary-500 dark:text-secondary-400">{{ trans('common.saas_total_partners') }}</span>
                            <span class="text-lg font-bold text-secondary-900 dark:text-white tabular-nums">{{ $partnerCount }}</span>
                        </div>
                    </div>
                </section>
            </div>

            {{-- RIGHT: Attention Required (2/5) --}}
            <div class="xl:col-span-2 flex">
                <section class="bg-white dark:bg-secondary-900 rounded-2xl p-6 border border-secondary-200 dark:border-secondary-800 w-full flex flex-col animate-fade-in" style="animation-delay: 240ms;">
                    <h2 class="text-lg font-bold text-secondary-900 dark:text-white mb-6 flex items-center gap-2.5">
                        <x-ui.icon icon="alert-triangle" class="w-5 h-5 text-amber-500" />
                        {{ trans('common.saas_attention_required') }}
                    </h2>

                    <div class="space-y-4 flex-1">
                        {{-- Trial Expiring --}}
                        @if($trialExpiring->isNotEmpty())
                            <div class="mb-4">
                                <p class="text-xs font-semibold text-secondary-400 dark:text-secondary-500 uppercase tracking-wider mb-3">
                                    {{ trans('common.saas_trial_expiring_soon') }}
                                </p>
                                @foreach($trialExpiring->take(5) as $trial)
                                    <a href="{{ route('admin.data.edit', ['name' => 'partners', 'id' => $trial['id']]) }}"
                                       class="flex items-center justify-between p-3 -mx-1 rounded-xl hover:bg-secondary-50 dark:hover:bg-secondary-800/50 transition-colors duration-200">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-secondary-900 dark:text-white truncate">{{ $trial['business_name'] ?? $trial['name'] }}</p>
                                            <p class="text-xs text-secondary-400 dark:text-secondary-500">{{ $trial['days_remaining'] }}d remaining</p>
                                        </div>
                                        <x-ui.icon icon="chevron-right" class="w-4 h-4 text-secondary-300 dark:text-secondary-600 flex-shrink-0" />
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        {{-- Past Due --}}
                        @if($pastDuePartners->isNotEmpty())
                            <div>
                                <p class="text-xs font-semibold text-secondary-400 dark:text-secondary-500 uppercase tracking-wider mb-3">
                                    {{ trans('common.saas_past_due') }}
                                </p>
                                @foreach($pastDuePartners->take(5) as $partner)
                                    <a href="{{ route('admin.data.edit', ['name' => 'partners', 'id' => $partner['id']]) }}"
                                       class="flex items-center justify-between p-3 -mx-1 rounded-xl hover:bg-secondary-50 dark:hover:bg-secondary-800/50 transition-colors duration-200">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-secondary-900 dark:text-white truncate">{{ $partner['business_name'] ?? $partner['name'] }}</p>
                                            <p class="text-xs text-red-500 dark:text-red-400">{{ trans('common.saas_past_due') }}</p>
                                        </div>
                                        <x-ui.icon icon="chevron-right" class="w-4 h-4 text-secondary-300 dark:text-secondary-600 flex-shrink-0" />
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        {{-- All Clear --}}
                        @if($trialExpiring->isEmpty() && $pastDuePartners->isEmpty())
                            <div class="flex flex-col items-center justify-center py-8 text-center flex-1">
                                <x-ui.icon icon="check-circle" class="w-8 h-8 text-emerald-400 dark:text-emerald-500 mb-3" />
                                <p class="text-sm font-medium text-secondary-500 dark:text-secondary-400">{{ trans('common.saas_no_attention_needed') }}</p>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        {{-- RECENT REGISTRATIONS --}}
        @if($recentRegistrations->isNotEmpty())
        <section class="mb-14 animate-fade-in" style="animation-delay: 320ms;">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-secondary-900 dark:text-white">{{ trans('common.saas_recent_registrations') }}</h2>
                <a href="{{ route('admin.data.list', ['name' => 'partners']) }}"
                   class="text-sm text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-300 flex items-center gap-1.5 transition-colors">
                    {{ trans('common.view_all') }}
                    <x-ui.icon icon="arrow-right" class="w-4 h-4" />
                </a>
            </div>

            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                <div class="divide-y divide-secondary-100 dark:divide-secondary-800">
                    @foreach($recentRegistrations as $partner)
                        <a href="{{ route('admin.data.edit', ['name' => 'partners', 'id' => $partner['id']]) }}"
                           class="flex items-center gap-4 px-6 py-4 hover:bg-secondary-50 dark:hover:bg-secondary-800/50 transition-colors duration-200">
                            <div class="flex-shrink-0 w-9 h-9 rounded-full bg-primary-500/10 flex items-center justify-center text-primary-600 dark:text-primary-400 text-xs font-semibold uppercase">
                                {{ substr($partner['name'] ?? '?', 0, 1) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-secondary-900 dark:text-white truncate">
                                    {{ $partner['business_name'] ?? $partner['name'] }}
                                </p>
                                <p class="text-xs text-secondary-400 dark:text-secondary-500 truncate">{{ $partner['email'] }}</p>
                            </div>
                            @php
                                $planName = $plans[$partner['plan']]['name'] ?? $partner['plan'];
                            @endphp
                            <span class="text-xs font-medium text-secondary-400 dark:text-secondary-500 tabular-nums">{{ $planName }}</span>
                            <span class="text-xs text-secondary-300 dark:text-secondary-600">{{ $partner['time_ago'] }}</span>
                            <x-ui.icon icon="chevron-right" class="w-4 h-4 text-secondary-300 dark:text-secondary-600 flex-shrink-0" />
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- USAGE OVERVIEW --}}
        <section class="animate-fade-in" style="animation-delay: 400ms;">
            <h2 class="text-xl font-bold text-secondary-900 dark:text-white mb-6">{{ trans('common.saas_usage_overview') }}</h2>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-6">
                @php
                    $usageItems = [
                        ['key' => 'total_partners', 'icon' => 'store', 'label' => trans('common.partners')],
                        ['key' => 'total_staff', 'icon' => 'user-check', 'label' => trans('common.staff')],
                        ['key' => 'total_cards', 'icon' => 'credit-card', 'label' => trans('common.loyalty_cards')],
                        ['key' => 'total_stamp_cards', 'icon' => 'stamp', 'label' => trans('common.stamp_cards')],
                        ['key' => 'total_vouchers', 'icon' => 'ticket', 'label' => trans('common.vouchers')],
                    ];
                @endphp

                @foreach($usageItems as $item)
                    <div class="bg-white dark:bg-secondary-900 rounded-2xl p-5
                        border border-secondary-200 dark:border-secondary-800 text-center">
                        <x-ui.icon :icon="$item['icon']" class="w-5 h-5 text-secondary-300 dark:text-secondary-600 mx-auto mb-2" />
                        <p class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums">
                            {{ number_format($usageOverview[$item['key']] ?? 0) }}
                        </p>
                        <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-1">{{ $item['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        @endif {{-- end partnerCount > 0 --}}

    </div>
</div>

<script>
function saasDashboard() {
    return {
        initCounters() {
            // Status breakdown uses static rendering, no animated counters needed
        }
    }
}
</script>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) backwards;
}
</style>
@stop
