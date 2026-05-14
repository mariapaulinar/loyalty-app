{{--
    Reward Loyalty - Proprietary Software
    Copyright (c) 2025 NowSquare. All rights reserved.
    See LICENSE file for terms.

    Admin Dashboard — Command Center
    
    Design: Structure communicates hierarchy. Data speaks.
    No decorative icon backgrounds, no gradient blobs, no emoji.
    Every element earns its space.
--}}

@extends('admin.layouts.default')

@section('page_title', ((auth('admin')->user()->role == 1) ? trans('common.administrator') : trans('common.manager')) . config('default.page_title_delimiter') . trans('common.dashboard') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="min-h-screen relative" x-data="adminDashboard()" x-init="initCounters()">

    <div class="w-full max-w-7xl mx-auto px-6 py-8 md:px-10 md:py-14 lg:py-16">
        
        {{-- ═══════════════════════════════════════════════════════════════════════════
            HERO SECTION
        ═══════════════════════════════════════════════════════════════════════════ --}}
        <header class="mb-12 md:mb-16 animate-fade-in">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-8">
                {{-- Greeting & Context --}}
                <div class="space-y-4">
                    {{-- Status Badge --}}
                    <div class="flex items-center gap-4">
                        <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full text-xs font-medium
                            bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            {{ trans('common.all_systems_operational') }}
                        </span>
                        <span class="text-xs text-secondary-300 dark:text-secondary-600 font-mono tracking-wide">
                            v{{ config('version.current') }}
                        </span>
                    </div>
                    
                    {{-- Main Greeting --}}
                    <div>
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-secondary-900 dark:text-white tracking-tight leading-tight">
                            <span x-data="{ greeting: '{{ $greeting }}' }" x-init="
                                const h = new Date().getHours();
                                greeting = h >= 5 && h < 12 ? '{{ trans('common.good_morning') }}'
                                    : h < 17 ? '{{ trans('common.good_afternoon') }}'
                                    : h < 21 ? '{{ trans('common.good_evening') }}'
                                    : '{{ trans('common.good_night') }}';
                            " x-text="greeting">{{ $greeting }}</span>, {{ $user->name }}
                        </h1>
                        <p class="text-lg text-secondary-400 dark:text-secondary-500 mt-3 font-light max-w-xl">
                            @if($isAdmin)
                                {{ trans('common.your_command_center') }}
                            @else
                                {{ trans('common.manager_welcome') }}
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="flex flex-wrap items-center gap-3">
                    @foreach($quickActions as $action)
                        <a href="{{ $action['link'] }}"
                            @class([
                                'inline-flex items-center gap-2.5 px-5 py-3 rounded-2xl text-sm font-medium transition-all duration-300 ease-out',
                                'bg-secondary-900 dark:bg-white text-white dark:text-secondary-900 hover:scale-[1.02] active:scale-[0.98]' => $action['variant'] === 'primary',
                                'text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white hover:bg-secondary-100 dark:hover:bg-secondary-800' => $action['variant'] === 'secondary',
                                'text-secondary-500 dark:text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-300' => $action['variant'] === 'ghost',
                            ])>
                            <x-ui.icon :icon="$action['icon']" class="w-4 h-4" />
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                    
                    @if($hasMigrations)
                        <a href="{{ route('admin.migrate') }}"
                            class="inline-flex items-center gap-2.5 px-5 py-3 rounded-2xl text-sm font-medium
                                bg-amber-500/10 text-amber-600 dark:text-amber-400
                                hover:bg-amber-500/20 transition-all duration-300">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                            </span>
                            {{ trans('common.database_update') }}
                        </a>
                    @endif
                </div>
            </div>
        </header>

        @if($isAdmin && $dashboardData)

        {{-- ═══════════════════════════════════════════════════════════════════════════
            KEY METRICS — Clean Cards, No Icon Backgrounds
        ═══════════════════════════════════════════════════════════════════════════ --}}
        <section class="mb-14 animate-fade-in" style="animation-delay: 80ms;">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                {{-- Members --}}
                <div class="bg-white dark:bg-secondary-900 rounded-2xl p-6
                    border border-secondary-200 dark:border-secondary-800
                    hover:border-secondary-300 dark:hover:border-secondary-700
                    transition-colors duration-200">
                    <div class="flex items-start justify-between mb-1">
                        <p class="text-sm font-medium text-secondary-500 dark:text-secondary-400">{{ trans('common.total_members') }}</p>
                        @if($dashboardData['growth']['members']['current'] > 0 || $dashboardData['growth']['members']['previous'] > 0)
                            <span @class([
                                'inline-flex items-center gap-1 text-xs font-medium',
                                'text-emerald-600 dark:text-emerald-400' => $dashboardData['growth']['members']['trend'] === 'up',
                                'text-red-600 dark:text-red-400' => $dashboardData['growth']['members']['trend'] === 'down',
                            ])>
                                <x-ui.icon :icon="$dashboardData['growth']['members']['trend'] === 'up' ? 'trending-up' : 'trending-down'" class="w-3 h-3" />
                                {{ $dashboardData['growth']['members']['percentage'] }}%
                            </span>
                        @endif
                    </div>
                    <p class="text-3xl md:text-4xl font-bold text-secondary-900 dark:text-white tabular-nums tracking-tight"
                       x-text="animatedMembers.toLocaleString()">
                        {{ $dashboardData['stats']['members'] }}
                    </p>
                </div>

                {{-- Cards & Vouchers --}}
                <div class="bg-white dark:bg-secondary-900 rounded-2xl p-6
                    border border-secondary-200 dark:border-secondary-800
                    hover:border-secondary-300 dark:hover:border-secondary-700
                    transition-colors duration-200">
                    <div class="flex items-start justify-between mb-1">
                        <p class="text-sm font-medium text-secondary-500 dark:text-secondary-400">{{ trans('common.cards_and_vouchers') }}</p>
                        @if($dashboardData['growth']['cards']['current'] > 0 || $dashboardData['growth']['cards']['previous'] > 0)
                            <span @class([
                                'inline-flex items-center gap-1 text-xs font-medium',
                                'text-emerald-600 dark:text-emerald-400' => $dashboardData['growth']['cards']['trend'] === 'up',
                                'text-red-600 dark:text-red-400' => $dashboardData['growth']['cards']['trend'] === 'down',
                            ])>
                                <x-ui.icon :icon="$dashboardData['growth']['cards']['trend'] === 'up' ? 'trending-up' : 'trending-down'" class="w-3 h-3" />
                                {{ $dashboardData['growth']['cards']['percentage'] }}%
                            </span>
                        @endif
                    </div>
                    <p class="text-3xl md:text-4xl font-bold text-secondary-900 dark:text-white tabular-nums tracking-tight"
                       x-text="animatedCards.toLocaleString()">
                        {{ $dashboardData['stats']['cards'] }}
                    </p>
                </div>

                {{-- Partners --}}
                <div class="bg-white dark:bg-secondary-900 rounded-2xl p-6
                    border border-secondary-200 dark:border-secondary-800
                    hover:border-secondary-300 dark:hover:border-secondary-700
                    transition-colors duration-200">
                    <div class="flex items-start justify-between mb-1">
                        <p class="text-sm font-medium text-secondary-500 dark:text-secondary-400">{{ trans('common.total_partners') }}</p>
                        @if($dashboardData['growth']['partners']['current'] > 0 || $dashboardData['growth']['partners']['previous'] > 0)
                            <span @class([
                                'inline-flex items-center gap-1 text-xs font-medium',
                                'text-emerald-600 dark:text-emerald-400' => $dashboardData['growth']['partners']['trend'] === 'up',
                                'text-red-600 dark:text-red-400' => $dashboardData['growth']['partners']['trend'] === 'down',
                            ])>
                                <x-ui.icon :icon="$dashboardData['growth']['partners']['trend'] === 'up' ? 'trending-up' : 'trending-down'" class="w-3 h-3" />
                                {{ $dashboardData['growth']['partners']['percentage'] }}%
                            </span>
                        @endif
                    </div>
                    <p class="text-3xl md:text-4xl font-bold text-secondary-900 dark:text-white tabular-nums tracking-tight"
                       x-text="animatedPartners.toLocaleString()">
                        {{ $dashboardData['stats']['partners'] }}
                    </p>
                </div>

                {{-- Activity Today --}}
                <div class="bg-white dark:bg-secondary-900 rounded-2xl p-6
                    border border-secondary-200 dark:border-secondary-800
                    hover:border-secondary-300 dark:hover:border-secondary-700
                    transition-colors duration-200">
                    <div class="flex items-start justify-between mb-1">
                        <p class="text-sm font-medium text-secondary-500 dark:text-secondary-400">{{ trans('common.activity_today') }}</p>
                        @php
                            $todayDiff = (int) ($dashboardData['activity']['todayDiff'] ?? 0);
                            $isPositive = $todayDiff > 0;
                            $isNeutral = $todayDiff === 0;
                            $showPercentage = abs($todayDiff) < 100 && $dashboardData['activity']['yesterday'] > 0;
                        @endphp
                        @if($showPercentage && !$isNeutral)
                            <span @class([
                                'inline-flex items-center gap-1 text-xs font-medium',
                                'text-emerald-600 dark:text-emerald-400' => $isPositive,
                                'text-red-600 dark:text-red-400' => !$isPositive,
                            ])>
                                <x-ui.icon :icon="$isPositive ? 'trending-up' : 'trending-down'" class="w-3 h-3" />
                                {{ $isPositive ? '+' : '' }}{{ $todayDiff }}%
                            </span>
                        @elseif($dashboardData['activity']['yesterday'] > 0)
                            <span class="text-xs text-secondary-400 dark:text-secondary-500">
                                {{ trans('common.vs_yesterday') }}: <span class="format-number tabular-nums">{{ $dashboardData['activity']['yesterday'] }}</span>
                            </span>
                        @endif
                    </div>
                    <p class="text-3xl md:text-4xl font-bold text-secondary-900 dark:text-white tabular-nums tracking-tight"
                       x-text="animatedActivity.toLocaleString()">
                        {{ $dashboardData['activity']['today'] }}
                    </p>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════════════════════════
            INSIGHTS — Flat cards with left accent border
        ═══════════════════════════════════════════════════════════════════════════ --}}
        @if(!empty($dashboardData['insights']))
        <section class="mb-14 animate-fade-in" style="animation-delay: 160ms;">
            @foreach($dashboardData['insights'] as $insight)
                <div class="flex items-center gap-4 p-5 bg-white dark:bg-secondary-900
                    border border-secondary-200 dark:border-secondary-800 rounded-xl
                    border-l-2 border-l-primary-500 dark:border-l-primary-400">
                    <x-ui.icon :icon="$insight['icon']" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 flex-shrink-0" />
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ $insight['title'] }}</p>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-0.5">{{ $insight['message'] }}</p>
                    </div>
                    @if(isset($insight['action']))
                        <a href="{{ route($insight['action']['route'], $insight['action']['params'] ?? []) }}"
                           class="flex-shrink-0 inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors">
                            {{ $insight['action']['label'] }}
                            <x-ui.icon icon="arrow-right" class="w-4 h-4" />
                        </a>
                    @endif
                </div>
                @if(!$loop->last)
                    <div class="h-3"></div>
                @endif
            @endforeach
        </section>
        @endif

        {{-- ═══════════════════════════════════════════════════════════════════════════
            TIER 2: TWO-COLUMN LAYOUT
        ═══════════════════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 xl:grid-cols-5 gap-8 mb-14 xl:items-stretch">
            
            {{-- LEFT COLUMN (3/5) — Week Summary --}}
            <div class="xl:col-span-3 flex">
                @if(!empty($dashboardData['weekSummary']))
                <section class="bg-white dark:bg-secondary-900 rounded-2xl p-8 border border-secondary-200 dark:border-secondary-800 w-full min-h-[400px] flex flex-col animate-fade-in" style="animation-delay: 240ms;">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 dark:text-white">{{ trans('common.week_highlights') }}</h2>
                        <a href="{{ route('admin.activity-logs.analytics') }}" 
                           class="text-sm text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-300 flex items-center gap-1.5 transition-colors">
                            {{ trans('common.system_analytics') }}
                            <x-ui.icon icon="arrow-right" class="w-4 h-4" />
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        {{-- Best Day --}}
                        @if($dashboardData['weekSummary']['bestDay'])
                        <div class="text-center">
                            <x-ui.icon icon="star" class="w-5 h-5 text-secondary-300 dark:text-secondary-600 mx-auto mb-2" />
                            <p class="text-2xl font-bold text-secondary-900 dark:text-white">
                                {{ $dashboardData['weekSummary']['bestDay']['dayName'] }}
                            </p>
                            <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-1">{{ trans('common.best_day') }}</p>
                        </div>
                        @endif

                        {{-- New Partners --}}
                        <div class="text-center">
                            <x-ui.icon icon="store" class="w-5 h-5 text-secondary-300 dark:text-secondary-600 mx-auto mb-2" />
                            <p class="text-2xl font-bold text-secondary-900 dark:text-white">
                                +{{ $dashboardData['weekSummary']['newPartners'] }}
                            </p>
                            <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-1">{{ trans('common.partners') }}</p>
                        </div>

                        {{-- New Members --}}
                        <div class="text-center">
                            <x-ui.icon icon="users" class="w-5 h-5 text-secondary-300 dark:text-secondary-600 mx-auto mb-2" />
                            <p class="text-2xl font-bold text-secondary-900 dark:text-white">
                                +{{ $dashboardData['weekSummary']['newMembers'] }}
                            </p>
                            <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-1">{{ trans('common.members') }}</p>
                        </div>

                        {{-- Transactions --}}
                        <div class="text-center">
                            <x-ui.icon icon="repeat" class="w-5 h-5 text-secondary-300 dark:text-secondary-600 mx-auto mb-2" />
                            <p class="text-2xl font-bold text-secondary-900 dark:text-white format-number tabular-nums">
                                {{ $dashboardData['weekSummary']['newTransactions'] }}
                            </p>
                            <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-1">{{ trans('common.transactions') }}</p>
                        </div>
                    </div>
                </section>
                @endif
            </div>

            {{-- RIGHT COLUMN (2/5) — Activity Feed --}}
            <div class="xl:col-span-2 flex">
                <section class="bg-white dark:bg-secondary-900 rounded-2xl p-6 border border-secondary-200 dark:border-secondary-800 w-full min-h-[400px] flex flex-col animate-fade-in" style="animation-delay: 320ms;">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold text-secondary-900 dark:text-white flex items-center gap-2.5">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-primary-500"></span>
                            </span>
                            {{ trans('common.live_feed') }}
                        </h2>
                        <a href="{{ route('admin.data.list', ['name' => 'activity-logs']) }}" 
                           class="text-xs text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-300 transition-colors">
                            {{ trans('common.view_all') }}
                        </a>
                    </div>

                    <div class="space-y-1 flex-1 activity-feed-scroll">
                        @forelse($dashboardData['recentActivity']->take(7) as $activity)
                            @php
                                $avatarBg = match($activity['event']) {
                                    'created' => 'bg-emerald-500',
                                    'updated' => 'bg-amber-500',
                                    'deleted' => 'bg-red-500',
                                    'login' => 'bg-primary-500',
                                    'logout' => 'bg-secondary-400',
                                    default => 'bg-secondary-400',
                                };
                            @endphp
                            <div class="group flex items-start gap-3 p-3 -mx-1 rounded-xl hover:bg-secondary-50 dark:hover:bg-secondary-800/50 transition-colors duration-200">
                                {{-- Avatar with Initial --}}
                                <div class="flex-shrink-0 w-9 h-9 rounded-full {{ $avatarBg }} flex items-center justify-center text-white text-xs font-semibold uppercase">
                                    {{ substr($activity['causer_name'] ?? '?', 0, 1) }}
                                </div>
                                <div class="flex-1 min-w-0 pt-0.5">
                                    <p class="text-sm text-secondary-900 dark:text-white">
                                        <span class="font-medium">{{ $activity['causer_name'] }}</span>
                                        <span class="text-secondary-400 dark:text-secondary-500 font-normal"> · {{ Str::limit($activity['description'], 30) }}</span>
                                    </p>
                                    <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-1">
                                        {{ $activity['time_ago'] }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <x-ui.icon icon="inbox" class="w-8 h-8 text-secondary-300 dark:text-secondary-600 mb-4" />
                                <p class="text-sm font-medium text-secondary-400 dark:text-secondary-500">{{ trans('common.no_recent_activity') }}</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════════════════
            QUICK NAVIGATION
        ═══════════════════════════════════════════════════════════════════════════ --}}
        <section class="animate-fade-in" style="animation-delay: 400ms;">
            <h2 class="text-xl font-bold text-secondary-900 dark:text-white mb-6">{{ trans('common.quick_navigation') }}</h2>
            
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Partners --}}
                <a href="{{ route('admin.data.list', ['name' => 'partners']) }}"
                   class="group flex items-center gap-4 p-5 bg-white dark:bg-secondary-900 rounded-xl 
                       border border-secondary-200 dark:border-secondary-800
                       hover:border-secondary-300 dark:hover:border-secondary-700
                       transition-colors duration-200">
                    <x-ui.icon icon="store" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-secondary-900 dark:text-white">{{ trans('common.partners') }}</p>
                        <p class="text-sm text-secondary-400 dark:text-secondary-500 mt-0.5 truncate">
                            {{ trans('common.adminDashboardBlocks.partners', ['localeSlug' => '/' . $localeSlug . '/partner/']) }}
                        </p>
                    </div>
                    <x-ui.icon icon="chevron-right" class="w-5 h-5 text-secondary-300 dark:text-secondary-600 group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 transition-transform duration-200" />
                </a>

                {{-- Members --}}
                <a href="{{ route('admin.data.list', ['name' => 'members']) }}"
                   class="group flex items-center gap-4 p-5 bg-white dark:bg-secondary-900 rounded-xl 
                       border border-secondary-200 dark:border-secondary-800
                       hover:border-secondary-300 dark:hover:border-secondary-700
                       transition-colors duration-200">
                    <x-ui.icon icon="users" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-secondary-900 dark:text-white">{{ trans('common.members') }}</p>
                        <p class="text-sm text-secondary-400 dark:text-secondary-500 mt-0.5 truncate">{{ trans('common.adminDashboardBlocks.members') }}</p>
                    </div>
                    <x-ui.icon icon="chevron-right" class="w-5 h-5 text-secondary-300 dark:text-secondary-600 group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 transition-transform duration-200" />
                </a>

                {{-- Analytics --}}
                <a href="{{ route('admin.activity-logs.analytics') }}"
                   class="group flex items-center gap-4 p-5 bg-white dark:bg-secondary-900 rounded-xl 
                       border border-secondary-200 dark:border-secondary-800
                       hover:border-secondary-300 dark:hover:border-secondary-700
                       transition-colors duration-200">
                    <x-ui.icon icon="bar-chart-2" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-secondary-900 dark:text-white">{{ trans('common.analytics') }}</p>
                        <p class="text-sm text-secondary-400 dark:text-secondary-500 mt-0.5 truncate">{{ trans('common.adminDashboardBlocks.analytics') }}</p>
                    </div>
                    <x-ui.icon icon="chevron-right" class="w-5 h-5 text-secondary-300 dark:text-secondary-600 group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 transition-transform duration-200" />
                </a>
            </div>
        </section>

        @else
        {{-- Manager View (Non-admin) --}}
        <section class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-12 animate-fade-in">
            <div class="text-center max-w-md mx-auto">
                <x-ui.icon icon="user-circle" class="w-10 h-10 text-secondary-400 dark:text-secondary-500 mx-auto mb-5" />
                <h2 class="text-2xl font-bold text-secondary-900 dark:text-white mb-3">
                    {{ trans('common.welcome_user', ['user' => $user->name]) }}
                </h2>
                <p class="text-secondary-400 dark:text-secondary-500 mb-8 text-lg font-light">
                    {{ trans('common.managerDashboardBlocksTitle') }}
                </p>
                <a href="{{ route('admin.data.list', ['name' => 'partners']) }}"
                   class="inline-flex items-center gap-2.5 px-8 py-4 bg-secondary-900 dark:bg-white text-white dark:text-secondary-900 rounded-2xl font-medium
                       hover:scale-[1.02] active:scale-[0.98]
                       transition-all duration-300 ease-out">
                    <x-ui.icon icon="store" class="w-5 h-5" />
                    {{ trans('common.partners') }}
                </a>
            </div>
        </section>
        @endif

    </div>
</div>

{{-- Alpine.js Dashboard Component with Animated Counters --}}
<script>
function adminDashboard() {
    return {
        animatedMembers: 0,
        animatedCards: 0,
        animatedPartners: 0,
        animatedActivity: 0,
        
        initCounters() {
            @if($isAdmin && $dashboardData)
            const targetMembers = {{ $dashboardData['stats']['members'] }};
            const targetCards = {{ $dashboardData['stats']['cards'] }};
            const targetPartners = {{ $dashboardData['stats']['partners'] }};
            const targetActivity = {{ $dashboardData['activity']['today'] }};
            
            this.animateValue('animatedMembers', targetMembers, 1000);
            this.animateValue('animatedCards', targetCards, 1200);
            this.animateValue('animatedPartners', targetPartners, 1400);
            this.animateValue('animatedActivity', targetActivity, 1600);
            @endif
        },
        
        animateValue(property, target, duration) {
            const start = 0;
            const startTime = performance.now();
            
            const easeOutExpo = (t) => t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
            
            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                this[property] = Math.round(easeOutExpo(progress) * target);
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            };
            
            requestAnimationFrame(animate);
        },
        
        formatNumber(value) {
            return new Intl.NumberFormat().format(value);
        }
    }
}
</script>

{{-- Custom Animations & Styles --}}
<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) backwards;
}

/* Activity Feed — Show scrollbar only on hover */
.activity-feed-scroll {
    max-height: 420px;
    overflow-y: auto;
    scrollbar-width: none;
}

.activity-feed-scroll::-webkit-scrollbar {
    width: 0;
    transition: width 0.2s ease;
}

.activity-feed-scroll:hover {
    scrollbar-width: thin;
    scrollbar-color: oklch(0.85 0.005 250) transparent;
}

.activity-feed-scroll:hover::-webkit-scrollbar {
    width: 4px;
}

.activity-feed-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.activity-feed-scroll::-webkit-scrollbar-thumb {
    background: oklch(0.85 0.005 250);
    border-radius: 9999px;
}

.dark .activity-feed-scroll:hover {
    scrollbar-color: oklch(0.3 0.005 250) transparent;
}

.dark .activity-feed-scroll::-webkit-scrollbar-thumb {
    background: oklch(0.3 0.005 250);
}
</style>
@stop
