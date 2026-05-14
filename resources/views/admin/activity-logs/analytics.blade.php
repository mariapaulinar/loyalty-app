{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Activity Log Analytics Dashboard - Admin View

  Design principles:
  - Stat cards: use <x-ui.stat-card> component (§6.3)
  - Chart cards: rounded-2xl, border-only, no shadow-2xl (§6.3)
  - Chart section titles: plain text, no icons (analytics pattern)
  - Activity feed: bare semantic icons, no colored backgrounds
  - Auth stats: clean table layout, semantic colors on values only
--}}

@extends('admin.layouts.default')

@section('page_title', trans('common.activity_log_analytics') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
    
    {{-- Page Header --}}
    <x-ui.page-header
        icon="activity"
        :title="trans('common.activity_log_analytics')"
        :description="trans('common.system_wide_audit_insights')"
    >
        <x-slot name="actions">
            <div class="flex items-center gap-4">
                {{-- Range Selector --}}
                <div class="relative group min-w-[200px]">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                        <x-ui.icon icon="calendar" class="h-5 w-5 text-secondary-400 group-hover:text-secondary-600 dark:group-hover:text-secondary-400 transition-colors" />
                    </div>
                    <select id="range"
                        class="appearance-none w-full bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-800 text-secondary-700 dark:text-secondary-300 text-sm rounded-xl focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 pl-12 pr-12 py-3 transition-all cursor-pointer hover:border-secondary-300 dark:hover:border-secondary-700">
                        <option value="7" @if($range == '7') selected @endif>{{ trans('common.last_7_days') }}</option>
                        <option value="14" @if($range == '14') selected @endif>{{ trans('common.last_14_days') }}</option>
                        <option value="30" @if($range == '30') selected @endif>{{ trans('common.last_30_days') }}</option>
                        <option value="90" @if($range == '90') selected @endif>{{ trans('common.last_90_days') }}</option>
                        <option value="365" @if($range == '365') selected @endif>{{ trans('common.last_year') }}</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                        <x-ui.icon icon="chevron-down" class="h-4 w-4 text-secondary-400 group-hover:text-secondary-600 dark:group-hover:text-secondary-300 transition-colors" />
                    </div>
                </div>
                {{-- View Full List Button --}}
                <a href="{{ route('admin.data.list', ['name' => 'activity-logs']) }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 
                           text-sm font-medium text-white 
                           bg-primary-600 hover:bg-primary-500
                           rounded-xl shadow-sm hover:shadow-md
                           focus:outline-none focus:ring-2 focus:ring-primary-500/20
                           transition-all duration-200 active:scale-[0.98]">
                    <x-ui.icon icon="list" class="w-4 h-4" />
                    {{ trans('common.view_all_logs') }}
                </a>
            </div>
        </x-slot>
    </x-ui.page-header>

    {{-- Content Grid --}}
    <div class="space-y-6 mt-6">

        {{-- Summary Metrics — stat-card pattern: value is the hero --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            {{-- Total Activities --}}
            <x-ui.stat-card
                :label="trans('common.total_activities')"
                :value="number_format($metrics['total'])"
            />

            {{-- Today --}}
            @php
                $todayNum = intval(str_replace('+', '', $todayDiff));
                $todayType = $todayDiff === '0' || $todayDiff === '+0' ? 'neutral' : ($todayNum > 0 ? 'positive' : 'negative');
            @endphp
            <x-ui.stat-card
                :label="trans('common.today')"
                :value="number_format($metrics['today'])"
                :change="$todayDiff . '%'"
                :changeType="$todayType"
            />

            {{-- This Week --}}
            @php
                $weekNum = intval(str_replace('+', '', $weekDiff));
                $weekType = $weekDiff === '0' || $weekDiff === '+0' ? 'neutral' : ($weekNum > 0 ? 'positive' : 'negative');
            @endphp
            <x-ui.stat-card
                :label="trans('common.this_week')"
                :value="number_format($metrics['this_week'])"
                :change="$weekDiff . '%'"
                :changeType="$weekType"
            />

            {{-- This Month --}}
            @php
                $monthNum = intval(str_replace('+', '', $monthDiff));
                $monthType = $monthDiff === '0' || $monthDiff === '+0' ? 'neutral' : ($monthNum > 0 ? 'positive' : 'negative');
            @endphp
            <x-ui.stat-card
                :label="trans('common.this_month')"
                :value="number_format($metrics['this_month'])"
                :change="$monthDiff . '%'"
                :changeType="$monthType"
            />
        </div>

        {{-- Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Activity Timeline --}}
            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-6 transition-colors duration-200 hover:border-secondary-300 dark:hover:border-secondary-700">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                        {{ trans('common.activity_timeline') }}
                    </h3>
                    <span class="text-sm text-secondary-500 dark:text-secondary-400 tabular-nums">
                        {{ trans('common.total') }}: <span class="font-semibold text-secondary-900 dark:text-white format-number">{{ $timeline['total'] }}</span>
                    </span>
                </div>
                <div id="activity-timeline-chart"
                    data-chart-type="line"
                    data-labels='@json($timeline['labels'])'
                    data-values='@json($timeline['values'])'
                    data-label="{{ trans('common.activities') }}"
                    data-color="#2563EB"
                    data-height="280"></div>
            </div>

            {{-- Event Breakdown Donut --}}
            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-6 transition-colors duration-200 hover:border-secondary-300 dark:hover:border-secondary-700">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                        {{ trans('common.events_breakdown') }}
                    </h3>
                </div>
                <div id="event-breakdown-chart"
                    data-chart-type="donut"
                    data-labels='@json($eventBreakdown->keys())'
                    data-values='@json($eventBreakdown->values())'
                    data-colors='["#10B981","#059669","#EA580C","#2563EB","#7C3AED","#0EA5E9"]'
                    data-total-label="{{ trans('common.total') }}"
                    data-height="280"></div>
            </div>
        </div>

        {{-- Second Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Category Breakdown --}}
            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-6 transition-colors duration-200 hover:border-secondary-300 dark:hover:border-secondary-700">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                        {{ trans('common.categories_breakdown') }}
                    </h3>
                </div>
                <div id="category-breakdown-chart"
                    data-chart-type="donut"
                    data-labels='@json($logNameBreakdown->keys())'
                    data-values='@json($logNameBreakdown->values())'
                    data-colors='["#2563EB","#7C3AED","#EA580C","#10B981","#059669","#0EA5E9"]'
                    data-total-label="{{ trans('common.total') }}"
                    data-height="280"></div>
            </div>

            {{-- User Type Breakdown --}}
            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-6 transition-colors duration-200 hover:border-secondary-300 dark:hover:border-secondary-700">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                        {{ trans('common.user_types_breakdown') }}
                    </h3>
                </div>
                <div id="user-type-breakdown-chart"
                    data-chart-type="donut"
                    data-labels='@json($causerTypeBreakdown->keys())'
                    data-values='@json($causerTypeBreakdown->values())'
                    data-colors='["#2563EB","#EA580C","#10B981","#7C3AED","#059669"]'
                    data-total-label="{{ trans('common.total') }}"
                    data-height="280"></div>
            </div>
        </div>

        {{-- Bottom Row: Auth Stats + Most Active Users + Recent Activity --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Authentication Statistics --}}
            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-6 transition-colors duration-200 hover:border-secondary-300 dark:hover:border-secondary-700">
                <h3 class="text-base font-semibold text-secondary-900 dark:text-white mb-6">
                    {{ trans('common.authentication_stats') }}
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between py-2.5">
                        <div class="flex items-center gap-3">
                            <x-ui.icon icon="log-in" class="w-4 h-4 text-emerald-500 dark:text-emerald-400" />
                            <span class="text-sm font-medium text-secondary-600 dark:text-secondary-400">{{ trans('common.successful_logins') }}</span>
                        </div>
                        <span class="text-sm font-bold text-secondary-900 dark:text-white tabular-nums">{{ $authStats['logins'] }}</span>
                    </div>
                    <div class="border-t border-secondary-100 dark:border-secondary-800"></div>
                    <div class="flex items-center justify-between py-2.5">
                        <div class="flex items-center gap-3">
                            <x-ui.icon icon="log-out" class="w-4 h-4 text-secondary-400 dark:text-secondary-500" />
                            <span class="text-sm font-medium text-secondary-600 dark:text-secondary-400">{{ trans('common.logouts') }}</span>
                        </div>
                        <span class="text-sm font-bold text-secondary-900 dark:text-white tabular-nums">{{ $authStats['logouts'] }}</span>
                    </div>
                    <div class="border-t border-secondary-100 dark:border-secondary-800"></div>
                    <div class="flex items-center justify-between py-2.5">
                        <div class="flex items-center gap-3">
                            <x-ui.icon icon="alert-triangle" class="w-4 h-4 text-red-500 dark:text-red-400" />
                            <span class="text-sm font-medium text-secondary-600 dark:text-secondary-400">{{ trans('common.failed_logins') }}</span>
                        </div>
                        <span class="text-sm font-bold text-secondary-900 dark:text-white tabular-nums">{{ $authStats['failed_logins'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Most Active Users --}}
            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-6 transition-colors duration-200 hover:border-secondary-300 dark:hover:border-secondary-700">
                <h3 class="text-base font-semibold text-secondary-900 dark:text-white mb-6">
                    {{ trans('common.most_active_users') }}
                </h3>
                <div class="divide-y divide-secondary-100 dark:divide-secondary-800">
                    @forelse($mostActiveUsers->take(5) as $index => $user)
                        @php
                            $rankClass = match($index) {
                                0 => 'bg-accent-500/15 text-accent-600 dark:text-accent-400',
                                1 => 'bg-secondary-100 text-secondary-600 dark:bg-secondary-800 dark:text-secondary-300',
                                2 => 'bg-secondary-100 text-secondary-600 dark:bg-secondary-800 dark:text-secondary-300',
                                default => 'bg-secondary-50 text-secondary-500 dark:bg-secondary-800/50 dark:text-secondary-400',
                            };
                        @endphp
                        <div class="flex items-center justify-between py-3 first:pt-0 last:pb-0">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold {{ $rankClass }}">
                                    {{ $index + 1 }}
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-secondary-900 dark:text-white">{{ $user['name'] }}</p>
                                    <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ $user['type'] }}</p>
                                </div>
                            </div>
                            <span class="text-sm font-bold text-secondary-900 dark:text-white tabular-nums format-number">{{ $user['count'] }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-secondary-500 dark:text-secondary-400 text-center py-8">{{ trans('common.no_data_available') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- Recent Activity Feed --}}
            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-6 transition-colors duration-200 hover:border-secondary-300 dark:hover:border-secondary-700">
                <h3 class="text-base font-semibold text-secondary-900 dark:text-white mb-6">
                    {{ trans('common.recent_activity') }}
                </h3>
                <div class="divide-y divide-secondary-100 dark:divide-secondary-800 max-h-80 overflow-y-auto">
                    @forelse($recentActivities->take(5) as $activity)
                        @php
                            $iconName = match($activity->event) {
                                'created' => 'plus',
                                'updated' => 'pencil',
                                'deleted' => 'trash-2',
                                'login' => 'log-in',
                                'logout' => 'log-out',
                                default => 'activity',
                            };
                            $iconClass = match($activity->event) {
                                'created' => 'text-emerald-500 dark:text-emerald-400',
                                'updated' => 'text-secondary-400 dark:text-secondary-500',
                                'deleted' => 'text-red-500 dark:text-red-400',
                                'login' => 'text-primary-600 dark:text-primary-400',
                                'logout' => 'text-secondary-400 dark:text-secondary-500',
                                default => 'text-secondary-400 dark:text-secondary-500',
                            };
                        @endphp
                        <div class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                            <div class="flex-shrink-0 mt-0.5">
                                <x-ui.icon :icon="$iconName" class="w-4 h-4 {{ $iconClass }}" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-secondary-900 dark:text-white leading-snug">{{ $activity->description }}</p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                                    <span class="text-primary-600 dark:text-primary-400 font-medium">{{ $activity->causer?->name ?? $activity->causer?->email ?? 'System' }}</span>
                                    <span class="mx-1">·</span>
                                    {{ $activity->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-secondary-500 dark:text-secondary-400 text-center py-8">{{ trans('common.no_recent_activity') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Range selector script --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const rangeSelect = document.querySelector('#range');
        if (rangeSelect) {
            rangeSelect.addEventListener('change', () => {
                window.location.href = window.location.pathname + '?range=' + encodeURIComponent(rangeSelect.value);
            });
        }
    });
</script>
@stop

{{-- Charts initialize automatically via resources/js/analytics.js --}}
