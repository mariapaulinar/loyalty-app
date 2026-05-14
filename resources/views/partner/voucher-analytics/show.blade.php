@extends('partner.layouts.default')

@section('page_title', $voucher->name . config('default.page_title_delimiter') . trans('common.analytics') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
    
    {{-- Page Header --}}
    <x-ui.page-header
        icon="ticket"
        :title="$voucher->name"
        :description="($resultsFound) ? $voucherViews['label'] : trans('common.no_results_found')"
        :breadcrumbs="[
            ['url' => route('partner.index'), 'icon' => 'home', 'title' => trans('common.dashboard')],
            ['url' => route('partner.voucher-analytics'), 'text' => trans('common.voucher_analytics')],
            ['text' => $voucher->name]
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
                            <x-member.voucher-card
                                class="max-w-md mx-auto transform hover:scale-105 transition-transform duration-300"
                                :voucher="$voucher" :links="false" />
                        </div>
                        <div
                            class="grid grid-cols-1 items-center border-t border-secondary-100 dark:border-secondary-800 pt-6 mt-4 justify-between">
                            <div class="flex justify-between items-center gap-4">
                                <a href="{{ route('partner.data.edit', ['name' => 'vouchers', 'id' => $voucher->id]) }}"
                                    class="flex-1 justify-center text-sm font-medium text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white inline-flex items-center py-3 px-4 rounded-xl hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors duration-200">
                                    <x-ui.icon icon="pencil" class="w-4 h-4 mr-2" />
                                    {{ trans('common.edit') }} {{ trans('common.voucher') }}
                                </a>
                                @if($voucher->is_active)
                                    <a href="{{ route('member.voucher', ['voucher_id' => $voucher->id]) }}" target="_blank"
                                        class="flex-1 justify-center text-sm font-medium inline-flex items-center rounded-xl text-white bg-primary-600 hover:bg-primary-500 transition-colors duration-200 px-4 py-3">
                                        {{ trans('common.view') }} {{ trans('common.voucher') }}
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
                                    <x-ui.icon icon="eye" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                            {{ trans('common.voucher_views') }}
                                        </p>
                                        <p class="text-xs text-secondary-500 truncate dark:text-secondary-400 mt-0.5">
                                            {{ trans('common.last_view') }}: <span
                                                class="format-date font-medium">{{ ($voucher->last_view) ? $voucher->last_view->diffForHumans() : trans('common.never') }}</span>
                                        </p>
                                    </div>
                                    <div
                                        class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                        <span class="format-number">{{ $voucherViews['total'] ?? 0 }}</span>
                                    </div>
                                    <div class="w-24 inline-flex justify-end items-center">
                                        @if ($voucherViewsDifference == 0)
                                            <span class="text-xs font-medium text-secondary-500 dark:text-secondary-400">
                                                {{ $voucherViewsDifference }}%
                                            </span>
                                        @elseif ($voucherViewsDifference > 0)
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                                <x-ui.icon icon="trending-up" class="w-3 h-3" />
                                                {{ $voucherViewsDifference }}%
                                            </span>
                                        @elseif ($voucherViewsDifference < 0 && $voucherViewsDifference != '-')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400">
                                                <x-ui.icon icon="trending-down" class="w-3 h-3" />
                                                {{ $voucherViewsDifference }}%
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </li>
                            <li class="py-4">
                                <div class="flex items-center space-x-4">
                                    <x-ui.icon icon="ticket" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                            {{ trans('common.times_used') }}
                                        </p>
                                    </div>
                                    <div
                                        class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                        <span class="format-number">{{ $voucher->times_used ?? 0 }}</span>
                                    </div>
                                </div>
                            </li>
                            <li class="py-4">
                                <div class="flex items-center space-x-4">
                                    <x-ui.icon icon="trending-down" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                            {{ trans('common.total_discount_given') }}
                                        </p>
                                    </div>
                                    <div
                                        class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                        <span class="format-money" data-currency="{{ $voucher->currency }}" data-amount="{{ $voucher->total_discount_given }}">{{ moneyFormat($voucher->total_discount_given / 100, $voucher->currency) }}</span>
                                    </div>
                                </div>
                            </li>
                            <li class="pt-4">
                                <div class="flex items-center space-x-4">
                                    <x-ui.icon icon="users" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                            {{ trans('common.unique_members') }}
                                        </p>
                                    </div>
                                    <div
                                        class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                        <span class="format-number">{{ $voucher->unique_members_used ?? 0 }}</span>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                @if($resultsFound)
                    <div
                    class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 shadow-sm p-6 lg:col-span-2">
                        <div class="flex justify-between pb-4 mb-4 border-b border-secondary-100 dark:border-secondary-800">
                            <div class="flex items-center mb-4">
                                    <x-ui.icon icon="eye" class="w-5 h-5 text-secondary-300 dark:text-secondary-600 mr-4 hidden md:block" />
                                <div>
                                    <h5 class="leading-none text-3xl font-bold text-secondary-900 dark:text-white pb-1">
                                        <span class="format-number">{{ $voucherViews['total'] }}</span>
                                    </h5>
                                    <p class="text-sm font-medium text-secondary-500 dark:text-secondary-400 flex items-center">
                                        <span class="flex w-3 h-3 bg-violet-600 rounded-full mr-2"></span>
                                        {{ trans('common.voucher_views') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div id="analytics-views-chart" 
                            data-chart-type="bar"
                            data-color="#7C3AED"
                            data-labels='{!! '["' . implode('","', $voucherViews['units']) . '"]' !!}'
                            data-label="{{ trans('common.voucher_views') }}"
                            data-tooltip="{{ trans('common.chart_tooltip_voucher_views') }}"
                            data-values="{{ '[' . implode(',', $voucherViews['views']) . ']' }}"></div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@stop
