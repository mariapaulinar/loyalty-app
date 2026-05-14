@extends('partner.layouts.default')

@section('page_title', trans('common.voucher') . ' ' . trans('common.analytics') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
    
    {{-- Page Header --}}
    <x-ui.page-header
        icon="ticket"
        :title="trans('common.voucher_analytics')"
        :description="trans('common.voucher_analytics_description')"
    >
        <x-slot name="actions">
            <div class="flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer group">
                    <input type="checkbox" id="active_only" name="active_only" value="true" class="sr-only peer"
                        @if($active_only == 'true') checked @endif>
                    <div
                        class="w-11 h-6 bg-secondary-200 dark:bg-secondary-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-500/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-secondary-300 dark:after:border-secondary-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600">
                    </div>
                    <span
                        class="ml-3 text-sm font-medium text-secondary-700 dark:text-secondary-300 group-hover:text-secondary-900 dark:group-hover:text-white transition-colors">{{ trans('common.only_show_active') }}</span>
                </label>
            </div>
            <div class="relative group min-w-[280px]">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                            <x-ui.icon icon="arrow-down-up"
                                class="h-5 w-5 text-secondary-400 group-hover:text-primary-500 transition-colors" />
                        </div>
                        <select id="sort"
                            class="appearance-none w-full bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-800 text-secondary-700 dark:text-secondary-300 text-sm rounded-xl focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 pl-12 pr-12 py-3 transition-all cursor-pointer hover:border-secondary-300 dark:hover:border-secondary-700 shadow-sm hover:shadow-md">
                            @php
                                $sortOptions = [
                                    'views,desc' => trans('common.sort_by_most_viewed'),
                                    'views,asc' => trans('common.sort_by_least_viewed'),
                                    'last_view,desc' => trans('common.sort_by_most_recently_viewed'),
                                    'last_view,asc' => trans('common.sort_by_least_recently_viewed'),
                                    'name,asc' => trans('common.sort_by_name_asc'),
                                    'name,desc' => trans('common.sort_by_name_desc'),
                                    'times_used,desc' => trans('common.sort_by_most_redemptions'),
                                    'times_used,asc' => trans('common.sort_by_fewest_redemptions'),
                                    'total_discount_given,desc' => trans('common.sort_by_highest_discount'),
                                    'total_discount_given,asc' => trans('common.sort_by_lowest_discount'),
                                    'unique_members_used,desc' => trans('common.sort_by_most_members'),
                                    'unique_members_used,asc' => trans('common.sort_by_fewest_members'),
                                    'created_at,desc' => trans('common.sort_by_newest_first'),
                                    'created_at,asc' => trans('common.sort_by_oldest_first'),
                                    'updated_at,desc' => trans('common.sort_by_recently_updated'),
                                    'updated_at,asc' => trans('common.sort_by_least_recently_updated'),
                                ];
                            @endphp
                            @foreach($sortOptions as $value => $label)
                                <option value="{{ $value }}" @if($sort == $value) selected @endif>{{ $label }}</option>
                            @endforeach
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
            const sortSelect = document.querySelector('#sort');
            const activeOnlyCheckbox = document.querySelector('#active_only');

            sortSelect.addEventListener('change', reloadWithQueryString);
            activeOnlyCheckbox.addEventListener('change', reloadWithQueryString);

            function reloadWithQueryString() {
                const sortValue = sortSelect.value;
                const activeOnlyValue = activeOnlyCheckbox.checked ? 'true' : 'false';

                window.location.href = window.location.pathname + '?sort=' + encodeURIComponent(sortValue) + '&active_only=' + encodeURIComponent(activeOnlyValue);
            }
        });
    </script>

    {{-- Cards Grid --}}
    <div class="grid sm:grid-cols-1 md:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3 gap-6">
        @foreach($vouchers as $voucher)
            <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 p-6 flex flex-col h-full transition-colors duration-200 hover:border-secondary-300 dark:hover:border-secondary-700">
                        <div class="relative overflow-visible mb-6 transform-gpu">
                            <x-member.voucher-card
                                class="max-w-md mx-auto"
                                :voucher="$voucher"
                                :links="true"
                                :custom-link="$voucher->is_active ? route('partner.voucher-analytics.voucher', ['voucher_id' => $voucher->id]) : null"
                            />
                        </div>
                        <div class="flow-root mt-4">
                            <ul role="list" class="divide-y divide-secondary-100 dark:divide-secondary-800">
                                <li class="py-4">
                                    <div class="flex items-center space-x-4">
                                        <x-ui.icon icon="eye" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 shrink-0" />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                                {{ trans('common.views') }}</p>
                                            <p class="text-xs text-secondary-500 truncate dark:text-secondary-400 mt-0.5">
                                                {{ trans('common.last_view') }}: <span
                                                    class="format-date font-medium">{{ ($voucher->last_view) ? $voucher->last_view->diffForHumans() : trans('common.never') }}</span>
                                            </p>
                                        </div>
                                        <div
                                            class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                            <span class="format-number">{{ $voucher->views ?? 0 }}</span>
                                        </div>
                                    </div>
                                </li>
                                <li class="py-4">
                                    <div class="flex items-center space-x-4">
                                        <x-ui.icon icon="ticket" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 shrink-0" />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                                {{ trans('common.times_used') }}</p>
                                        </div>
                                        <div
                                            class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                            <span class="format-number">{{ $voucher->times_used ?? 0 }}</span>
                                        </div>
                                    </div>
                                </li>
                                <li class="py-4">
                                    <div class="flex items-center space-x-4">
                                        <x-ui.icon icon="trending-down" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 shrink-0" />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                                {{ trans('common.total_discount_given') }}</p>
                                        </div>
                                        <div
                                            class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                            <span class="format-money" data-currency="{{ auth('partner')->user()->currency }}" data-amount="{{ $voucher->total_discount_given }}">{{ moneyFormat($voucher->total_discount_given / 100, auth('partner')->user()->currency) }}</span>
                                        </div>
                                    </div>
                                </li>
                                <li class="pt-4">
                                    <div class="flex items-center space-x-4">
                                        <x-ui.icon icon="users" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 shrink-0" />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                                {{ trans('common.unique_members') }}</p>
                                        </div>
                                        <div
                                            class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                            <span class="format-number">{{ $voucher->unique_members_used ?? 0 }}</span>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div
                            class="grid grid-cols-1 items-center border-t border-secondary-100 dark:border-secondary-800 justify-between mt-6 pt-6">
                            <div class="flex justify-between items-center gap-4">
                                <a href="{{ route('partner.data.edit', ['name' => 'vouchers', 'id' => $voucher->id]) }}"
                                    class="flex-1 justify-center text-sm font-medium text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white inline-flex items-center py-3 px-4 rounded-xl hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors duration-200">
                                    <x-ui.icon icon="pencil" class="w-4 h-4 mr-2" />
                                    {{ trans('common.edit') }} {{ trans('common.voucher') }}
                                </a>
                                @if($voucher->is_active)
                                    <a href="{{ route('partner.voucher-analytics.voucher', ['voucher_id' => $voucher->id]) }}"
                                        class="flex-1 justify-center text-sm font-medium inline-flex items-center rounded-xl text-white bg-primary-600 hover:bg-primary-500 transition-colors duration-200 px-4 py-3">
                                        {{ trans('common.view') }} {{ trans('common.details') }}
                                        <x-ui.icon icon="arrow-right" class="w-4 h-4 ml-2" />
                                    </a>
                                @endif
                            </div>
                        </div>
                </div>
            @endforeach
        </div>
    </div>
@stop
