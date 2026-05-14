{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Generate Redemption Code — Staff View

  Purpose:
  Allows staff to generate a single-use redemption code for a loyalty card.
  Members enter the code in their dashboard to receive points.

  Design principles:
  - Card container: rounded-2xl, border-only, no shadow (§6.3)
  - Section header: bare icon, no icon backgrounds (Card Section Header Pattern)
  - Submit button: primary brand gradient, standard platform CTA
  - Info tips: bare icons, no decorative backgrounds
  - All strings translated, no hardcoded English
--}}

@extends('staff.layouts.default')

@section('page_title', $card->head . config('default.page_title_delimiter') . trans('common.generate_code') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-lg mx-auto px-4 py-6 md:py-10">
    
    {{-- Page Header --}}
    <x-ui.page-header
        icon="hash"
        :title="trans('common.generate_code')"
        :description="trans('common.generate_code_description')"
        compact
    />

    {{-- Card Preview --}}
    <div class="mt-6 mb-6">
        <x-member.card
            :card="$card"
            :member="null"
            :flippable="false"
            :links="false"
            :show-qr="false"
        />
    </div>

    {{-- Flash Messages --}}
    <div class="mb-6">
        <x-forms.messages />
    </div>

    {{-- Generate Code Form --}}
    <x-forms.form-open
        action="{{ route('staff.code.generate.post', ['card_identifier' => $card->unique_identifier]) }}"
        method="POST"
    />
        <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
            
            {{-- Form Header --}}
            <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
                <div class="flex items-center gap-3">
                    <x-ui.icon icon="coins" class="w-5 h-5 text-secondary-400" />
                    <div>
                        <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                            {{ trans('common.points') }}
                        </h3>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400">
                            {{ trans('common.points_range_value', ['min' => $card->min_points_per_purchase, 'max' => $card->max_points_per_purchase]) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Form Content --}}
            <div class="p-6">
                <x-forms.input
                    name="points"
                    :label="trans('common.points')"
                    type="number"
                    inputmode="numeric"
                    icon="coins"
                    affix-class="text-secondary-400 dark:text-secondary-500 text-lg"
                    input-class="text-lg"
                    :min="$card->min_points_per_purchase"
                    :max="$card->max_points_per_purchase"
                    step="1"
                    :placeholder="trans('common.points_placeholder', ['min' => $card->min_points_per_purchase, 'max' => $card->max_points_per_purchase])"
                    required
                />
            </div>

            {{-- Submit --}}
            <div class="px-6 pb-6">
                <button type="submit"
                    class="w-full flex items-center justify-center gap-2.5 px-6 py-3.5 
                           text-sm font-semibold text-white 
                           bg-primary-600 hover:bg-primary-500 
                           rounded-xl shadow-sm hover:shadow-md 
                           focus:outline-none focus:ring-2 focus:ring-primary-500/20 
                           transition-all duration-200 active:scale-[0.98]">
                    <x-ui.icon icon="hash" class="w-4.5 h-4.5" />
                    {{ trans('common.generate_code') }}
                </button>
            </div>
        </div>
    <x-forms.form-close />

    {{-- Info Tips --}}
    <div class="mt-6 space-y-3">
        {{-- How it works --}}
        <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 p-4">
            <div class="flex gap-3">
                <div class="flex-shrink-0 mt-0.5">
                    <x-ui.icon icon="info" class="w-4 h-4 text-secondary-400" />
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-medium text-secondary-900 dark:text-white mb-0.5">
                        {{ trans('common.how_it_works') }}
                    </h4>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400 leading-relaxed">
                        {{ trans('common.generate_code_info', ['expiry' => Carbon\CarbonInterval::minutes(config('default.code_to_redeem_points_valid_minutes'))->cascade()->forHumans(['parts' => 2])]) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Tier Multiplier Info --}}
        <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 p-4">
            <div class="flex gap-3">
                <div class="flex-shrink-0 mt-0.5">
                    <x-ui.icon icon="trending-up" class="w-4 h-4 text-emerald-500 dark:text-emerald-400" />
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-medium text-secondary-900 dark:text-white mb-0.5">
                        {{ trans('common.tier_multiplier_applied') }}
                    </h4>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400 leading-relaxed">
                        {{ trans('common.tier_multiplier_code_info') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Points validation script --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('points');
    if (!input) return;

    input.addEventListener('input', () => {
        input.value = input.value.replace(/[^0-9]/g, '').slice(0, {{ strlen($card->max_points_per_purchase) }});
    });
});
</script>
@stop
