@extends('partner.layouts.default')

@section('page_title', trans('common.analytics') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
    
    {{-- Page Header --}}
    <x-ui.page-header
        icon="trending-up"
        :title="trans('common.loyalty_card_analytics')"
        :description="trans('common.loyalty_card_analytics_description')"
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
                        class="ml-3 text-sm font-medium text-secondary-700 dark:text-secondary-300 group-hover:text-secondary-900 dark:group-hover:text-white transition-colors">{{ trans('common.only_show_active_cards') }}</span>
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
                                    'total_amount_purchased,desc' => trans('common.sort_by_highest_revenue'),
                                    'total_amount_purchased,asc' => trans('common.sort_by_lowest_revenue'),
                                    'number_of_points_issued,desc' => trans('common.sort_by_most_points_issued'),
                                    'number_of_points_issued,asc' => trans('common.sort_by_fewest_points_issued'),
                                    'number_of_points_redeemed,desc' => trans('common.sort_by_most_points_redeemed'),
                                    'number_of_points_redeemed,asc' => trans('common.sort_by_fewest_points_redeemed'),
                                    'number_of_rewards_redeemed,desc' => trans('common.sort_by_most_rewards_claimed'),
                                    'number_of_rewards_redeemed,asc' => trans('common.sort_by_fewest_rewards_claimed'),
                                    'last_points_issued_at,desc' => trans('common.sort_by_most_recently_issued_points'),
                                    'last_points_issued_at,asc' => trans('common.sort_by_least_recently_issued_points'),
                                    'last_reward_redeemed_at,desc' => trans('common.sort_by_most_recently_claimed_reward'),
                                    'last_reward_redeemed_at,asc' => trans('common.sort_by_least_recently_claimed_reward')
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
        @foreach($cards as $card)
            <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 p-6 flex flex-col h-full transition-colors duration-200 hover:border-secondary-300 dark:hover:border-secondary-700">
                        <div class="relative overflow-visible mb-6 transform-gpu">
                            <x-member.premium-card
                                :card="$card"
                                :flippable="false"
                                :links="true"
                                :show-qr="false"
                                :show-balance="false"
                                :custom-link="route('partner.analytics.card', ['card_id' => $card->id])"
                            />
                        </div>
                        <div class="flow-root mt-auto">
                            <ul role="list" class="divide-y divide-secondary-100 dark:divide-secondary-800">
                                <li class="py-4">
                                    <div class="flex items-center space-x-4">
                                        <x-ui.icon icon="credit-card" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 shrink-0" />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-secondary-900 dark:text-white truncate">
                                                {{ $card->name }}</p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span
                                                    class="{{ ($card->is_active) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }} text-xs font-bold px-2.5 py-0.5 rounded-full">{{ ($card->is_active) ? trans('common.active') : trans('common.deactivated') }}</span>
                                                <span
                                                    class="bg-secondary-100 text-secondary-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-secondary-800 dark:text-secondary-300">{{ $card->club->name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="py-4">
                                    <div class="flex items-center space-x-4">
                                        <x-ui.icon icon="eye" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 shrink-0" />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                                {{ trans('common.views') }}</p>
                                            <p class="text-xs text-secondary-500 truncate dark:text-secondary-400 mt-0.5">
                                                {{ trans('common.last_view') }}: <span
                                                    class="format-date font-medium">{{ ($card->last_view) ? $card->last_view->diffForHumans() : trans('common.never') }}</span>
                                            </p>
                                        </div>
                                        <div
                                            class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                            <span class="format-number">{{ $card->views }}</span>
                                        </div>
                                    </div>
                                </li>
                                <li class="py-4">
                                    <div class="flex items-center space-x-4">
                                        <x-ui.icon icon="banknote" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 shrink-0" />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                                {{ trans('common.total_purchased') }}</p>
                                        </div>
                                        <div
                                            class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                            <span
                                                class="format-number">{{ $card->parseMoney($card->total_amount_purchased) }}</span>
                                        </div>
                                    </div>
                                </li>
                                <li class="py-4">
                                    <div class="flex items-center space-x-4">
                                        <x-ui.icon icon="coins" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 shrink-0" />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                                {{ trans('common.points_issued') }}</p>
                                            <p class="text-xs text-secondary-500 truncate dark:text-secondary-400 mt-0.5">
                                                {{ trans('common.last_points_issued') }}: <span
                                                    class="format-date font-medium">{{ ($card->last_points_issued_at) ? $card->last_points_issued_at->diffForHumans() : trans('common.never') }}</span>
                                            </p>
                                        </div>
                                        <div
                                            class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                            <span class="format-number">{{ $card->number_of_points_issued }}</span>
                                        </div>
                                    </div>
                                </li>
                                <li class="py-4">
                                    <div class="flex items-center space-x-4">
                                        <x-ui.icon icon="store" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 shrink-0" />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                                {{ trans('common.points_redeemed') }}</p>
                                        </div>
                                        <div
                                            class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                            <span class="format-number">{{ $card->number_of_points_redeemed }}</span>
                                        </div>
                                    </div>
                                </li>
                                <li class="pt-4">
                                    <div class="flex items-center space-x-4">
                                        <x-ui.icon icon="trophy" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 shrink-0" />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-secondary-900 truncate dark:text-white">
                                                {{ trans('common.rewards_claimed') }}</p>
                                            <p class="text-xs text-secondary-500 truncate dark:text-secondary-400 mt-0.5">
                                                {{ trans('common.last_reward_claimed') }}: <span
                                                    class="format-date font-medium">{{ ($card->last_reward_redeemed_at) ? $card->last_reward_redeemed_at->diffForHumans() : trans('common.never') }}</span>
                                            </p>
                                        </div>
                                        <div
                                            class="inline-flex items-center text-lg font-bold text-secondary-900 dark:text-white">
                                            <span class="format-number">{{ $card->number_of_rewards_redeemed }}</span>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div
                            class="grid grid-cols-1 items-center border-t border-secondary-100 dark:border-secondary-800 justify-between mt-6 pt-6">
                            <div class="flex justify-between items-center gap-4">
                                <a href="{{ route('partner.data.edit', ['name' => 'cards', 'id' => $card->id]) }}"
                                    class="flex-1 justify-center text-sm font-medium text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white inline-flex items-center py-3 px-4 rounded-xl hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors duration-200">
                                    <x-ui.icon icon="pencil" class="w-4 h-4 mr-2" />
                                    {{ trans('common.edit_card') }}
                                </a>
                                @if($card->is_active)
                                    <a href="{{ route('partner.analytics.card', ['card_id' => $card->id]) }}"
                                        class="flex-1 justify-center text-sm font-medium inline-flex items-center rounded-xl text-white bg-primary-600 hover:bg-primary-500 transition-colors duration-200 px-4 py-3">
                                        {{ trans('common.view_details') }}
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