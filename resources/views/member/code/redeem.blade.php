{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Enter Code — Member View

  Purpose:
  Members enter a 4-digit code (provided by staff) to redeem points
  for a loyalty card. One input, one submit. Clean and focused.

  Design principles:
  - Member Action Page pattern: no ambient blobs, focused layout
  - Card container: rounded-2xl, border-only, no shadow (§6.3)
  - Submit button: primary brand solid, standard CTA
  - Info tips: bare icons, neutral containers
  - All strings translated
--}}

@extends('member.layouts.default')

@section('page_title', trans('common.enter_code') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-lg mx-auto px-4 py-8 md:py-14">

    {{-- Page Header --}}
    <x-ui.page-header
        icon="hash"
        :title="trans('common.enter_code')"
        :description="trans('common.enter_code_description')"
        :breadcrumbs="[
            ['url' => route('member.cards'), 'icon' => 'home', 'title' => trans('common.home')],
            ['url' => route('member.cards'), 'text' => trans('common.wallet')],
            ['text' => trans('common.enter_code')]
        ]"
        compact
    />

    {{-- Card Context --}}
    @if(isset($card))
        <div class="mt-5 text-center">
            <p class="text-sm text-secondary-500 dark:text-secondary-400">
                {{ trans('common.redeeming_points_for') }}
            </p>
            <p class="text-base font-semibold text-secondary-900 dark:text-white mt-0.5">
                {{ $card->name }}
            </p>
        </div>
    @endif

    {{-- Flash Messages --}}
    <div class="mt-8 mb-6">
        <x-forms.messages />
    </div>

    {{-- Code Entry Form --}}
    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">

        {{-- Form Header --}}
        <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
            <div class="flex items-center gap-3">
                <x-ui.icon icon="keyboard" class="w-5 h-5 text-secondary-400" />
                <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                    {{ trans('common.four_digit_code') }}
                </h3>
            </div>
        </div>

        {{-- Form Content --}}
        <div class="p-6 md:p-8">
            <x-forms.form-open action="{{ route('member.code.enter.post') }}" method="POST" />

            <div class="mb-6">
                <input
                    type="text"
                    id="code"
                    name="code"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="4"
                    placeholder="••••"
                    required
                    autofocus
                    class="w-full text-center text-4xl md:text-5xl font-bold font-mono tracking-[0.5em]
                           bg-secondary-50 dark:bg-secondary-800
                           border border-secondary-200 dark:border-secondary-700
                           rounded-xl px-6 py-5
                           text-secondary-900 dark:text-white
                           placeholder:text-secondary-300 dark:placeholder:text-secondary-600
                           focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:focus:ring-primary-400/20
                           focus:border-primary-500 dark:focus:border-primary-500
                           transition-all duration-200"
                />
            </div>

            {{-- Submit --}}
            <button type="submit"
                class="w-full flex items-center justify-center gap-2.5 px-6 py-3.5
                       text-sm font-semibold text-white
                       bg-primary-600 hover:bg-primary-500
                       rounded-xl shadow-sm hover:shadow-md
                       focus:outline-none focus:ring-2 focus:ring-primary-500/20
                       transition-all duration-200 active:scale-[0.98]">
                <x-ui.icon icon="check-circle" class="w-4.5 h-4.5" />
                {{ trans('common.submit_code') }}
            </button>

            <x-forms.form-close />
        </div>
    </div>

    {{-- Help Tip --}}
    <div class="mt-6">
        <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 p-4">
            <div class="flex gap-3">
                <div class="flex-shrink-0 mt-0.5">
                    <x-ui.icon icon="lightbulb" class="w-4 h-4 text-secondary-400" />
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-medium text-secondary-900 dark:text-white mb-0.5">
                        {{ trans('common.where_to_find_code') }}
                    </h4>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400 leading-relaxed">
                        {{ trans('common.enter_code_help', ['expiry' => Carbon\CarbonInterval::minutes(config('default.code_to_redeem_points_valid_minutes'))->cascade()->forHumans(['parts' => 2])]) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Input validation --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('code');
    if (!input) return;

    input.addEventListener('input', (e) => {
        let value = e.target.value.replace(/[^0-9]/g, '');
        e.target.value = value.slice(0, 4);
    });

    input.focus();
});
</script>
@stop
