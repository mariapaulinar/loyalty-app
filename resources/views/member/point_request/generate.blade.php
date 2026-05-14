{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Generate Request Link — Member View

  Purpose:
  Members create a shareable link to request points from others.
  Select a card (or use wildcard), generate, share the URL.

  Design principles:
  - Member Action Page pattern: no ambient blobs, focused layout
  - Card container: rounded-2xl, border-only, no shadow (§6.3)
  - Submit button: primary brand solid, standard CTA
  - Info tips: bare icons, neutral containers
  - All strings translated
--}}

@extends('member.layouts.default')

@section('page_title', trans('common.generate_request_link') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-lg mx-auto px-4 py-8 md:py-14">

    {{-- Page Header --}}
    <x-ui.page-header
        icon="send"
        :title="trans('common.generate_request_link')"
        :description="trans('common.request_link_subtitle')"
        :breadcrumbs="[
            ['url' => route('member.cards'), 'icon' => 'home', 'title' => trans('common.home')],
            ['url' => route('member.data.list', ['name' => 'request-links']), 'text' => trans('common.request_links')],
            ['text' => trans('common.generate_request_link')]
        ]"
        compact
    />

    {{-- Card Context --}}
    @if($selectedCard)
        <div class="mt-5 text-center">
            <p class="text-sm text-secondary-500 dark:text-secondary-400">
                {{ trans('common.generating_request_for') }}
            </p>
            <p class="text-base font-semibold text-secondary-900 dark:text-white mt-0.5">
                {{ $selectedCard->name }}
            </p>
        </div>
    @endif

    {{-- Flash Messages --}}
    <div class="mt-8 mb-6">
        <x-forms.messages />
    </div>

    {{-- Generate Link Form --}}
    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">

        {{-- Form Header --}}
        <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
            <div class="flex items-center gap-3">
                <x-ui.icon icon="link" class="w-5 h-5 text-secondary-400" />
                <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                    {{ trans('common.request_points') }}
                </h3>
            </div>
        </div>

        {{-- Form Content --}}
        <div class="p-6">
            <x-forms.form-open action="{{ route('member.request.points.generate.post') }}" method="POST" />
            @csrf

            @if(!$selectedCard)
                <div class="mb-6">
                    <x-forms.select
                        name="card_id"
                        :label="trans('common.select_card')"
                        :options="$options"
                        placeholder="{{ trans('common.select_card_placeholder') }}"
                        value="wildcard"
                        required
                    />
                </div>
            @endif

            {{-- Submit --}}
            <button type="submit"
                class="w-full flex items-center justify-center gap-2.5 px-6 py-3.5
                       text-sm font-semibold text-white
                       bg-primary-600 hover:bg-primary-500
                       rounded-xl shadow-sm hover:shadow-md
                       focus:outline-none focus:ring-2 focus:ring-primary-500/20
                       transition-all duration-200 active:scale-[0.98]">
                <x-ui.icon icon="link" class="w-4.5 h-4.5" />
                {{ trans('common.generate_link') }}
            </button>

            <x-forms.form-close />
        </div>
    </div>

    {{-- How it Works --}}
    <div class="mt-6">
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
                        {{ trans('common.request_link_info') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
