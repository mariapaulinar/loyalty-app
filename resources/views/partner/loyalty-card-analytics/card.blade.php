@extends('partner.layouts.default')

@section('page_title', $card->name . config('default.page_title_delimiter') . trans('common.analytics') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
    
    {{-- Page Header --}}
    <x-ui.page-header
        icon="credit-card"
        :title="$card->head"
        :description="($resultsFound) ? $cardViews['label'] : trans('common.no_results_found')"
        :breadcrumbs="[
            ['url' => route('partner.index'), 'icon' => 'home', 'title' => trans('common.dashboard')],
            ['url' => route('partner.analytics'), 'text' => trans('common.loyalty_card_analytics')],
            ['text' => $card->head]
        ]"
    >
        <x-slot name="actions">
            <div class="relative group min-w-[280px]">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                    <x-ui.icon icon="calendar"
                        class="h-5 w-5 text-secondary-400 group-hover:text-primary-500 transition-colors" />
                </div>
                <select id="range"
                    class="appearance-none w-full bg-white dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 text-secondary-700 dark:text-secondary-300 text-sm rounded-xl focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 pl-12 pr-12 py-3 transition-all cursor-pointer hover:border-secondary-300 dark:hover:border-secondary-600 shadow-sm">
                            <option value="day" @if($range == 'day') selected @endif>
                                {{ trans('common.show_analytics_from_today') }}</option>
                            <option value="day,-1" @if($range == 'day,-1') selected @endif>
                                {{ trans('common.show_analytics_from_yesterday') }}</option>
                            <option value="week" @if($range == 'week') selected @endif>
                                {{ trans('common.show_analytics_from_this_week') }}</option>
                            <option value="week,-1" @if($range == 'week,-1') selected @endif>
                                {{ trans('common.show_analytics_from_last_week') }}</option>
                            <option value="month" @if($range == 'month') selected @endif>
                                {{ trans('common.show_analytics_from_this_month') }}</option>
                            <option value="month,-1" @if($range == 'month,-1') selected @endif>
                                {{ trans('common.show_analytics_from_last_month') }}</option>
                            <option value="year" @if($range == 'year') selected @endif>
                                {{ trans('common.show_analytics_from_this_year') }}</option>
                            <option value="year,-1" @if($range == 'year,-1') selected @endif>
                                {{ trans('common.show_analytics_from_last_year') }}</option>
                        </select>
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                    <x-ui.icon icon="chevron-down"
                        class="h-4 w-4 text-secondary-400 group-hover:text-secondary-600 dark:group-hover:text-secondary-300 transition-colors" />
                </div>
            </div>
        </x-slot>
    </x-ui.page-header>

    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            const rangeSelect = document.querySelector('#range');
            rangeSelect.addEventListener('change', () => {
                const rangeValue = rangeSelect.value;
                window.location.href = window.location.pathname + '?range=' + encodeURIComponent(rangeValue);
            });
        });
    </script>

    {{-- Content Grid --}}
    <div class="grid md:grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 shadow-sm p-6">
                    <div class="flex flex-col h-full min-h-full">
                        <div class="flex-grow flex items-center justify-center py-8">
                            <x-member.card
                                class="max-w-md mx-auto transform hover:scale-105 transition-transform duration-300"
                                :card="$card" :flippable="false" :links="false" :show-qr="false"
                                :auth-check="true" :show-balance="false" />
                        </div>
                        <div
                            class="grid grid-cols-1 items-center border-t border-secondary-100 dark:border-secondary-800 pt-6 mt-4 justify-between">
                            <div class="flex justify-between items-center gap-4">
                                <a href="{{ route('partner.data.edit', ['name' => 'cards', 'id' => $card->id]) }}"
                                    class="flex-1 justify-center text-sm font-medium text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white inline-flex items-center py-3 px-4 rounded-xl hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors duration-200">
                                    <x-ui.icon icon="pencil" class="w-4 h-4 mr-2" />
                                    {{ trans('common.edit_card') }}
                                </a>
                                @if($card->is_active)
                                    <a href="{{ route('member.card', ['card_id' => $card->id]) }}" target="_blank"
                                        class="flex-1 justify-center text-sm font-medium inline-flex items-center rounded-xl text-white bg-primary-600 hover:bg-primary-500 transition-colors duration-200 px-4 py-3">
                                        {{ trans('common.view_card') }}
                                        <x-ui.icon icon="external-link" class="w-4 h-4 ml-2" />
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="w-full p-6 bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-800 rounded-xl">
                    <div class="flow-root">
                        <ul role="list" class="divide-y divide-secondary-100 dark:divide-secondary-800">
                            <li class="pb-4">
                                <div class="flex items-center space-x-4">
                                    <x-ui.icon icon="credit-card" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                            {{ trans('common.card_views') }}
                                        </p>
                                        <p class="text-xs text-secondary-500 truncate dark:text-secondary-400 mt-0.5">
                                            {{ trans('common.last_view') }}: <span
                                                class="format-date font-medium">{{ ($card->last_view) ? $card->last_view->diffForHumans() : trans('common.never') }}</span>
                                        </p>
                                    </div>
                                    <div
                                        class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                        <span class="format-number">{{ $cardViews['total'] }}</span>
                                    </div>
                                    <div class="w-24 inline-flex justify-end items-center">
                                        @if ($cardViewsDifference == 0)
                                            <span class="text-xs font-medium text-secondary-500 dark:text-secondary-400">
                                                {{ $cardViewsDifference }}%
                                            </span>
                                        @elseif ($cardViewsDifference > 0)
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                                <x-ui.icon icon="trending-up" class="w-3 h-3" />
                                                {{ $cardViewsDifference }}%
                                            </span>
                                        @elseif ($cardViewsDifference < 0 && $cardViewsDifference != '-')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400">
                                                <x-ui.icon icon="trending-down" class="w-3 h-3" />
                                                {{ $cardViewsDifference }}%
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </li>
                            <li class="py-4">
                                <div class="flex items-center space-x-4">
                                    <x-ui.icon icon="gift" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                            {{ trans('common.reward_views') }}
                                        </p>
                                    </div>
                                    <div
                                        class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                        <span class="format-number">{{ $rewardViews['total'] }}</span>
                                    </div>
                                    <div class="w-24 inline-flex justify-end items-center">
                                        @if ($rewardViewsDifference == 0)
                                            <span class="text-xs font-medium text-secondary-500 dark:text-secondary-400">
                                                {{ $rewardViewsDifference }}%
                                            </span>
                                        @elseif ($rewardViewsDifference > 0)
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                                <x-ui.icon icon="trending-up" class="w-3 h-3" />
                                                {{ $rewardViewsDifference }}%
                                            </span>
                                        @elseif ($rewardViewsDifference < 0 && $rewardViewsDifference != '-')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400">
                                                <x-ui.icon icon="trending-down" class="w-3 h-3" />
                                                {{ $rewardViewsDifference }}%
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </li>
                            <li class="py-4">
                                <div class="flex items-center space-x-4">
                                    <x-ui.icon icon="coins" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                            {{ trans('common.points_issued') }}
                                        </p>
                                        <p class="text-xs text-secondary-500 truncate dark:text-secondary-400 mt-0.5">
                                            {{ trans('common.last_points_issued') }}: <span
                                                class="format-date font-medium">{{ ($card->last_points_issued_at) ? $card->last_points_issued_at->diffForHumans() : trans('common.never') }}</span>
                                        </p>
                                    </div>
                                    <div
                                        class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                        <span class="format-number">{{ $pointsIssued['total'] }}</span>
                                    </div>
                                    <div class="w-24 inline-flex justify-end items-center">
                                        @if ($pointsIssuedDifference == 0)
                                            <span class="text-xs font-medium text-secondary-500 dark:text-secondary-400">
                                                {{ $pointsIssuedDifference }}%
                                            </span>
                                        @elseif ($pointsIssuedDifference > 0)
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                                <x-ui.icon icon="trending-up" class="w-3 h-3" />
                                                {{ $pointsIssuedDifference }}%
                                            </span>
                                        @elseif ($pointsIssuedDifference < 0 && $pointsIssuedDifference != '-')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400">
                                                <x-ui.icon icon="trending-down" class="w-3 h-3" />
                                                {{ $pointsIssuedDifference }}%
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </li>
                            <li class="py-4">
                                <div class="flex items-center space-x-4">
                                    <x-ui.icon icon="store" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                            {{ trans('common.points_redeemed') }}
                                        </p>
                                    </div>
                                    <div
                                        class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                        <span class="format-number">{{ $pointsRedeemed['total'] }}</span>
                                    </div>
                                    <div class="w-24 inline-flex justify-end items-center">
                                        @if ($pointsRedeemedDifference == 0)
                                            <span class="text-xs font-medium text-secondary-500 dark:text-secondary-400">
                                                {{ $pointsRedeemedDifference }}%
                                            </span>
                                        @elseif ($pointsRedeemedDifference > 0)
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                                <x-ui.icon icon="trending-up" class="w-3 h-3" />
                                                {{ $pointsRedeemedDifference }}%
                                            </span>
                                        @elseif ($pointsRedeemedDifference < 0 && $pointsRedeemedDifference != '-')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400">
                                                <x-ui.icon icon="trending-down" class="w-3 h-3" />
                                                {{ $pointsRedeemedDifference }}%
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </li>
                            <li class="py-4">
                                <div class="flex items-center space-x-4">
                                    <x-ui.icon icon="trophy" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                            {{ trans('common.rewards_claimed') }}
                                        </p>
                                        <p class="text-xs text-secondary-500 truncate dark:text-secondary-400 mt-0.5">
                                            {{ trans('common.last_reward_claimed') }}: <span
                                                class="format-date font-medium">{{ ($card->last_reward_redeemed_at) ? $card->last_reward_redeemed_at->diffForHumans() : trans('common.never') }}</span>
                                        </p>
                                    </div>
                                    <div
                                        class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                        <span class="format-number">{{ $rewardsClaimed['total'] }}</span>
                                    </div>
                                    <div class="w-24 inline-flex justify-end items-center">
                                        @if ($rewardsClaimedDifference == 0)
                                            <span class="text-xs font-medium text-secondary-500 dark:text-secondary-400">
                                                {{ $rewardsClaimedDifference }}%
                                            </span>
                                        @elseif ($rewardsClaimedDifference > 0)
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                                <x-ui.icon icon="trending-up" class="w-3 h-3" />
                                                {{ $rewardsClaimedDifference }}%
                                            </span>
                                        @elseif ($rewardsClaimedDifference < 0 && $rewardsClaimedDifference != '-')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400">
                                                <x-ui.icon icon="trending-down" class="w-3 h-3" />
                                                {{ $rewardsClaimedDifference }}%
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </li>
                            <li class="pt-4">
                                <div class="flex items-center space-x-4">
                                    <x-ui.icon icon="handshake" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                            {{ trans('common.member_point_transfers') }}
                                        </p>
                                    </div>
                                    <div
                                        class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                        <span class="format-number">{{ $pointRequestsSent['total'] }}</span>
                                    </div>
                                    <div class="w-24 inline-flex justify-end items-center">
                                        @if ($pointRequestsSentDifference == 0)
                                            <span class="text-xs font-medium text-secondary-500 dark:text-secondary-400">
                                                {{ $pointRequestsSentDifference }}%
                                            </span>
                                        @elseif ($pointRequestsSentDifference > 0)
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                                <x-ui.icon icon="trending-up" class="w-3 h-3" />
                                                {{ $pointRequestsSentDifference }}%
                                            </span>
                                        @elseif ($pointRequestsSentDifference < 0 && $pointRequestsSentDifference != '-')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400">
                                                <x-ui.icon icon="trending-down" class="w-3 h-3" />
                                                {{ $pointRequestsSentDifference }}%
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </li>

                        </ul>
                    </div>
                </div>

                @if($resultsFound)

                    <div
                    class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 shadow-sm p-6">
                        <div class="flex justify-between">
                            <div
                                class="flex justify-between pb-4 mb-4 border-b border-secondary-100 dark:border-secondary-800 w-1/2 mr-4">
                                <div class="flex items-center mb-4">
                                    <x-ui.icon icon="credit-card" class="w-5 h-5 text-secondary-300 dark:text-secondary-600 mr-4 hidden md:block" />
                                    <div>
                                        <h5 class="leading-none text-3xl font-bold text-secondary-900 dark:text-white pb-1"><span
                                                class="format-number">{{ $cardViews['total'] }}</span></h5>
                                        <p class="text-sm font-medium text-secondary-500 dark:text-secondary-400 flex items-center">
                                            <span class="flex w-3 h-3 bg-blue-700 rounded-full mr-2"></span>
                                            {{ trans('common.card_views') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="flex justify-between pb-4 mb-4 border-b border-secondary-100 dark:border-secondary-800 w-1/2 ml-4">
                                <div class="flex items-center mb-4">
                                    <x-ui.icon icon="gift" class="w-5 h-5 text-secondary-300 dark:text-secondary-600 mr-4 hidden md:block" />
                                    <div>
                                        <h5 class="leading-none text-3xl font-bold text-secondary-900 dark:text-white pb-1"><span
                                                class="format-number">{{ $rewardViews['total'] }}</span></h5>
                                        <p class="text-sm font-medium text-secondary-500 dark:text-secondary-400 flex items-center">
                                            <span class="flex w-3 h-3 bg-amber-300 rounded-full mr-2"></span>
                                            {{ trans('common.reward_views') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="analytics-views-chart" 
                            data-chart-type="multi-bar"
                            data-colors='["#1A56DB", "#FDBA8C"]'
                            data-labels='{!! '["' . implode('","', $cardViews['units']) . '"]' !!}'
                            data-label1="{{ trans('common.card_views') }}"
                            data-tooltip1="{{ trans('common.chart_tooltip_card_views') }}"
                            data-values1="{{ '[' . implode(',', $cardViews['views']) . ']' }}"
                            data-label2="{{ trans('common.reward_views') }}"
                            data-tooltip2="{{ trans('common.chart_tooltip_reward_views') }}"
                            data-values2="{{ '[' . implode(',', $rewardViews['views']) . ']' }}"></div>

                    </div>

                    <div
                    class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 shadow-sm p-6">
                        <div class="flex justify-between">
                            <div
                                class="flex justify-between pb-4 mb-4 border-b border-secondary-100 dark:border-secondary-800 w-1/2 mr-4">
                                <div class="flex items-center mb-4">
                                    <x-ui.icon icon="coins" class="w-5 h-5 text-secondary-300 dark:text-secondary-600 mr-4 hidden md:block" />
                                    <div>
                                        <h5 class="leading-none text-3xl font-bold text-secondary-900 dark:text-white pb-1"><span
                                                class="format-number">{{ $pointsIssued['total'] }}</span></h5>
                                        <p class="text-sm font-medium text-secondary-500 dark:text-secondary-400 flex items-center">
                                            <span class="flex w-3 h-3 bg-emerald-400 rounded-full mr-2"></span>
                                            {{ trans('common.points_issued') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="flex justify-between pb-4 mb-4 border-b border-secondary-100 dark:border-secondary-800 w-1/2 ml-4">
                                <div class="flex items-center mb-4">
                                    <x-ui.icon icon="store" class="w-5 h-5 text-secondary-300 dark:text-secondary-600 mr-4 hidden md:block" />
                                    <div>
                                        <h5 class="leading-none text-3xl font-bold text-secondary-900 dark:text-white pb-1"><span
                                                class="format-number">{{ $pointsRedeemed['total'] }}</span></h5>
                                        <p class="text-sm font-medium text-secondary-500 dark:text-secondary-400 flex items-center">
                                            <span class="flex w-3 h-3 bg-blue-600 rounded-full mr-2"></span>
                                            {{ trans('common.points_redeemed') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="analytics-interactions-chart" 
                            data-chart-type="multi-bar"
                            data-colors='["#31C48D", "#1C64F2", "#F59E0B"]'
                            data-labels='{!! '["' . implode('","', $pointsIssued['units']) . '"]' !!}'
                            data-label1="{{ trans('common.points_issued') }}"
                            data-tooltip1="{{ trans('common.points_issued') }}"
                            data-values1="{{ '[' . implode(',', $pointsIssued['points']) . ']' }}"
                            data-label2="{{ trans('common.points_redeemed') }}"
                            data-tooltip2="{{ trans('common.points_redeemed') }}"
                            data-values2="{{ '[' . implode(',', $pointsRedeemed['points']) . ']' }}"
                            data-label3="{{ trans('common.rewards_claimed') }}"
                            data-tooltip3="{{ trans('common.rewards_claimed') }}"
                            data-values3="{{ '[' . implode(',', $rewardsClaimed['rewards']) . ']' }}"></div>

                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@stop