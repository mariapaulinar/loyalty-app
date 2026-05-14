{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Partner Registration Page — Enterprise SaaS Experience
  Form-first layout with plan comparison as supporting content
  Default plan (free) is the starting plan; upgrades are admin-managed
--}}

@extends('partner.layouts.default', ['robots' => false, 'authPage' => true])

@section('page_title', trans('common.registration_title') . config('default.page_title_delimiter') . config('default.app_name'))

@php
    $defaultPlanConfig = $plans[$defaultPlan] ?? [];
    // Resolve plan name through translation key with fallback to raw config string
    $defaultPlanName = isset($defaultPlanConfig['name_key'])
        ? trans($defaultPlanConfig['name_key'])
        : ($defaultPlanConfig['name'] ?? ucfirst($defaultPlan));
@endphp

@section('content')
<section class="min-h-screen bg-white dark:bg-secondary-950">
    <div class="w-full min-h-screen flex flex-col">

        {{-- Top Bar --}}
        <div class="w-full px-6 lg:px-12 py-6 grid grid-cols-3 items-center relative z-10">
            <a href="{{ route('member.index') }}"
                class="justify-self-start flex items-center gap-2 text-sm font-medium text-secondary-500 hover:text-secondary-900 dark:text-secondary-400 dark:hover:text-white transition-colors group">
                <div
                    class="w-10 h-10 rounded-full bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center group-hover:bg-secondary-200 dark:group-hover:bg-secondary-700 transition-colors duration-200">
                    <x-ui.icon icon="chevron-left" class="w-5 h-5" />
                </div>
                <span class="hidden sm:inline font-medium">{{ trans('common.home') }}</span>
            </a>

            <a href="{{ route('member.index') }}" class="justify-self-center inline-block">
                <x-ui.app-logo class="h-8 lg:h-10" />
            </a>

            <a href="{{ route('partner.login') }}"
                class="justify-self-end text-sm font-medium text-secondary-500 hover:text-secondary-900 dark:text-secondary-400 dark:hover:text-white transition-colors">
                {{ trans('common.sign_in') }}
            </a>
        </div>

        {{-- Hero + Registration Form (first viewport) --}}
        <div class="max-w-lg mx-auto px-6 pt-2 pb-10 lg:pb-14 w-full animate-fade-in-up">
            {{-- Hero --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary-50 dark:bg-primary-500/10 border border-primary-100 dark:border-primary-500/20 mb-5">
                    <x-ui.icon icon="briefcase" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                    <span class="text-sm font-medium text-primary-700 dark:text-primary-300">{{ trans('common.partner_register_badge') }}</span>
                </div>

                <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-secondary-900 dark:text-white mb-3 leading-tight">
                    {{ trans('common.partner_register_hero_title') }}
                </h1>
                <p class="text-base text-secondary-500 dark:text-secondary-400">
                    {{ trans('common.partner_register_hero_subtitle', ['plan' => $defaultPlanName]) }}
                </p>
            </div>

            {{-- Default Plan Callout --}}
            <div class="p-4 rounded-xl bg-primary-50/50 dark:bg-primary-500/5 border border-primary-100 dark:border-primary-500/20 mb-6">
                <div class="flex gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <x-ui.icon icon="gift" class="w-5 h-5 text-accent-600 dark:text-accent-400" />
                    </div>
                    <div class="text-sm text-secondary-700 dark:text-secondary-300">
                        <p class="font-bold mb-1">{{ trans('common.start_free') }}</p>
                        <p class="text-secondary-500 dark:text-secondary-400">{{ trans('common.partner_register_default_plan_note', ['plan' => $defaultPlanName]) }}</p>
                    </div>
                </div>
            </div>

            <x-forms.messages />

            @if (!Session::has('success'))
                <x-forms.form-open class="space-y-5" :action="route('partner.register.post')" method="POST" />
                {{-- Honeypot — hidden bot trap. Bots auto-fill all fields; humans never see this. --}}
                <div aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;opacity:0;height:0;overflow:hidden;">
                    <label for="website_url">Website</label>
                    <input type="text" name="website_url" id="website_url" value="" tabindex="-1" autocomplete="nope" />
                </div>
                <input type="hidden" name="time_zone" id="time_zone" />
                <script>
                    window.onload = function () {
                        var timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                        if (!timeZone) {
                            timeZone = '{{ app()->make('i18n')->time_zone }}';
                        }
                        document.getElementById('time_zone').value = timeZone;
                    }
                </script>

                <div class="space-y-5">
                    <x-forms.input
                        type="text"
                        name="name"
                        icon="building"
                        :label="trans('common.business_name')"
                        :placeholder="trans('common.business_name_placeholder')"
                        :required="true"
                        class="transition-all focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 hover:border-secondary-400 dark:hover:border-secondary-500" />

                    <x-forms.input
                        type="email"
                        name="email"
                        icon="mail"
                        :label="trans('common.email_address')"
                        :placeholder="trans('common.your_email')"
                        :required="true"
                        :value="$email ?? ''"
                        class="transition-all focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 hover:border-secondary-400 dark:hover:border-secondary-500" />
                </div>

                <div class="space-y-4 pt-1">
                    <x-forms.checkbox
                        name="consent"
                        :label="trans('common.registration_consent', [
                            'terms_of_use' => '<a rel=\'nofollow\' tabindex=\'-1\' target=\'_blank\' class=\'font-medium text-primary-600 hover:underline dark:text-primary-400\' href=\'' . route('member.terms') . '\'>' . trans('common.terms') . '</a>',
                            'privacy_policy' => '<a rel=\'nofollow\' tabindex=\'-1\' target=\'_blank\' class=\'font-medium text-primary-600 hover:underline dark:text-primary-400\' href=\'' . route('member.privacy') . '\'>' . trans('common.privacy_policy') . '</a>',
                        ])" />
                </div>

                <x-forms.button
                    :label="trans('common.create_account')"
                    button-class="w-full py-3.5 text-base font-bold text-white rounded-xl transition-colors duration-200" />
                <x-forms.form-close />

                {{-- Login Link --}}
                <div class="text-center pt-4">
                    <p class="text-secondary-500 dark:text-secondary-400">
                        {{ trans('common.already_have_account') }}
                        <a href="{{ route('partner.login') }}"
                            class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 transition-colors hover:underline decoration-2 underline-offset-2">
                            {{ trans('common.login_link') }}
                        </a>
                    </p>
                </div>
            @endif
        </div>

        {{-- Plan Comparison (supporting content — confidence builder) --}}
        <div class="bg-secondary-50 dark:bg-secondary-900 border-t border-secondary-200 dark:border-secondary-800">
            <div class="px-6 lg:px-12 py-12 lg:py-16 max-w-6xl mx-auto w-full">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold tracking-tight text-secondary-900 dark:text-white mb-2">
                        {{ trans('common.partner_register_form_title') }}
                    </h2>
                    <p class="text-secondary-500 dark:text-secondary-400">
                        {{ trans('common.partner_register_form_subtitle', ['plan' => $defaultPlanName]) }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-5">
                    @foreach($plans as $slug => $plan)
                        @php
                            $isDefault = $slug === $defaultPlan;
                            $isPopular = $plan['is_popular'] ?? false;
                            $isFree = ($plan['price_monthly'] ?? 0) === 0;
                            $currency = $plan['currency'] ?? 'USD';
                        @endphp
                        <div class="relative rounded-2xl border transition-all duration-200
                            {{ $isDefault
                                ? 'border-primary-300 dark:border-primary-600 bg-white dark:bg-secondary-950 ring-2 ring-primary-500/20'
                                : ($isPopular
                                    ? 'border-accent-300 dark:border-accent-600 bg-white dark:bg-secondary-950'
                                    : 'border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-950')
                            }}">

                            {{-- Popular / Default Badge --}}
                            @if($isDefault)
                                <div class="absolute -top-3 left-1/2 -translate-x-1/2 z-10">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-primary-600 text-white shadow-md">
                                        <x-ui.icon icon="check-circle" class="w-3.5 h-3.5" />
                                        {{ trans('common.plan_badge_your_plan') }}
                                    </span>
                                </div>
                            @elseif($isPopular)
                                <div class="absolute -top-3 left-1/2 -translate-x-1/2 z-10">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-accent-500 text-secondary-900 shadow-md">
                                        <x-ui.icon icon="star" class="w-3.5 h-3.5" />
                                        {{ trans('common.popular') }}
                                    </span>
                                </div>
                            @endif

                            <div class="p-5 lg:p-6 {{ ($isDefault || $isPopular) ? 'pt-7' : '' }}">
                                {{-- Plan Name & Description --}}
                                @php
                                    $planName = isset($plan['name_key']) ? trans($plan['name_key']) : $plan['name'];
                                    $planDesc = isset($plan['description_key']) ? trans($plan['description_key']) : $plan['description'];
                                @endphp
                                <div class="mb-4">
                                    <h3 class="text-lg font-bold text-secondary-900 dark:text-white">
                                        {{ $planName }}
                                    </h3>
                                    <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">
                                        {{ $planDesc }}
                                    </p>
                                </div>

                                {{-- Price --}}
                                <div class="mb-5">
                                    @if($isFree)
                                        <div class="flex items-baseline gap-1">
                                            <span class="text-3xl font-bold text-secondary-900 dark:text-white">{{ trans('common.plan_price_free') }}</span>
                                        </div>
                                        <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-1">{{ trans('common.plan_price_free_forever') }}</p>
                                    @else
                                        <div class="flex items-baseline gap-0.5">
                                            <span class="text-3xl font-bold text-secondary-900 dark:text-white">{!! moneyStyled($plan['price_monthly'] ?? 0, $currency) !!}</span>
                                            <span class="text-sm text-secondary-500 dark:text-secondary-400 ml-0.5">{{ trans('common.plan_price_per_month') }}</span>
                                        </div>
                                        <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-1">{{ trans('common.plan_price_upgrade_note') }}</p>
                                    @endif
                                </div>

                                {{-- Limits --}}
                                <div class="space-y-2.5 text-sm">
                                    @php
                                        $limits = [
                                            ['key' => 'max_members', 'icon' => 'users', 'label' => trans('common.plan_limit_members')],
                                            ['key' => 'max_staff', 'icon' => 'user-check', 'label' => trans('common.plan_limit_staff')],
                                            ['key' => 'max_cards', 'icon' => 'credit-card', 'label' => trans('common.plan_limit_cards')],
                                            ['key' => 'max_stamp_cards', 'icon' => 'stamp', 'label' => trans('common.plan_limit_stamp_cards')],
                                            ['key' => 'max_vouchers', 'icon' => 'ticket', 'label' => trans('common.plan_limit_vouchers')],
                                            ['key' => 'max_rewards', 'icon' => 'gift', 'label' => trans('common.plan_limit_rewards')],
                                        ];
                                    @endphp

                                    @foreach($limits as $limit)
                                        @php $limitValue = $plan[$limit['key']] ?? 0; @endphp
                                        <div class="flex items-center gap-2.5 {{ $limitValue === 0 ? 'text-secondary-300 dark:text-secondary-600' : 'text-secondary-600 dark:text-secondary-400' }}">
                                            <x-ui.icon icon="{{ $limit['icon'] }}" class="w-4 h-4 flex-shrink-0 text-secondary-400 dark:text-secondary-500" />
                                            <span>
                                                @if($limitValue === -1)
                                                    {{ trans('common.plan_limit_unlimited') }}
                                                @elseif($limitValue === 0)
                                                    —
                                                @else
                                                    {{ number_format($limitValue) }}
                                                @endif
                                                {{ $limit['label'] }}
                                            </span>
                                        </div>
                                    @endforeach

                                    {{-- Feature flags --}}
                                    @php
                                        $features = [
                                            ['key' => 'has_vouchers', 'label' => trans('common.plan_feature_vouchers')],
                                            ['key' => 'has_email_campaigns', 'label' => trans('common.plan_feature_email_campaigns')],
                                            ['key' => 'has_activity_log', 'label' => trans('common.plan_feature_analytics')],
                                            ['key' => 'has_agent_api', 'label' => trans('common.plan_feature_agent_api')],
                                        ];
                                    @endphp

                                    <div class="pt-2 border-t border-secondary-100 dark:border-secondary-800 space-y-2">
                                        @foreach($features as $feature)
                                            @php $hasFeature = $plan[$feature['key']] ?? false; @endphp
                                            <div class="flex items-center gap-2.5 {{ $hasFeature ? 'text-secondary-600 dark:text-secondary-400' : 'text-secondary-300 dark:text-secondary-600' }}">
                                                @if($hasFeature)
                                                    <x-ui.icon icon="check" class="w-4 h-4 flex-shrink-0 text-success-500" />
                                                @else
                                                    <x-ui.icon icon="minus" class="w-4 h-4 flex-shrink-0" />
                                                @endif
                                                <span>{{ $feature['label'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- CTA / Status --}}
                                <div class="mt-6">
                                    @if($isDefault)
                                        <div class="w-full py-2.5 px-4 text-center text-sm font-semibold text-primary-700 dark:text-primary-300 bg-primary-100 dark:bg-primary-500/10 rounded-xl border border-primary-200 dark:border-primary-500/20">
                                            {{ trans('common.plan_cta_register_now') }}
                                        </div>
                                    @else
                                        <div class="w-full py-2.5 px-4 text-center text-sm font-medium text-secondary-400 dark:text-secondary-500">
                                            {{ trans('common.plan_cta_upgrade_later') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Registration Footer (full — matches member surface) --}}
<footer class="bg-secondary-50/50 dark:bg-secondary-900/50 border-t border-secondary-100 dark:border-secondary-800/50 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {{-- Utility Row: Language + Theme Toggle --}}
        <div class="flex items-center justify-center gap-3 mb-4" x-data>
            {{-- Language Selector (pill, opens modal) --}}
            @if (count($languages['all'] ?? []) > 1)
                <button @click="showLanguageModal = true"
                    class="inline-flex items-center gap-2 px-3.5 py-1.5 text-xs font-medium text-secondary-500 dark:text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-300 rounded-full border border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-all cursor-pointer">
                    <div class="fi-{{ strtolower($languages['current']['countryCode'] ?? 'us') }} fis w-4 h-4 rounded-full"></div>
                    <span>{{ $languages['current']['languageName'] ?? '' }}</span>
                </button>
            @endif

            {{-- Theme Toggle (icon-only pill) --}}
            <button type="button" onclick="toggleTheme()"
                class="inline-flex items-center justify-center w-8 h-8 rounded-full border border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 text-secondary-400 dark:text-secondary-500 hover:text-secondary-700 dark:hover:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-all cursor-pointer">
                <x-ui.icon icon="sun" class="hidden w-4 h-4 dark:block" />
                <x-ui.icon icon="moon" class="w-4 h-4 dark:hidden" />
            </button>
        </div>

        {{-- Compact Link Row --}}
        <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-[11px] text-secondary-400 dark:text-secondary-500">
            <a href="{{ route('member.about') }}" class="hover:text-secondary-600 dark:hover:text-secondary-400 transition-colors">{{ trans('common.about') }}</a>
            <span class="text-secondary-300 dark:text-secondary-700">·</span>
            <a href="{{ route('member.contact') }}" class="hover:text-secondary-600 dark:hover:text-secondary-400 transition-colors">{{ trans('common.contact') }}</a>
            <span class="text-secondary-300 dark:text-secondary-700">·</span>
            <a href="{{ route('member.faq') }}" class="hover:text-secondary-600 dark:hover:text-secondary-400 transition-colors">{{ trans('common.faq') }}</a>
            <span class="text-secondary-300 dark:text-secondary-700">·</span>
            <a href="{{ route('member.privacy') }}" class="hover:text-secondary-600 dark:hover:text-secondary-400 transition-colors">{{ trans('common.privacy') }}</a>
            <span class="text-secondary-300 dark:text-secondary-700">·</span>
            <a href="{{ route('member.terms') }}" class="hover:text-secondary-600 dark:hover:text-secondary-400 transition-colors">{{ trans('common.terms') }}</a>
        </div>

        {{-- Copyright --}}
        <p class="mt-4 text-center text-[10px] text-secondary-300 dark:text-secondary-600">
            &copy; {{ date('Y') }} {{ config('default.app_name') }}
        </p>
    </div>
</footer>

{{-- Premium currency display for plan pricing --}}
<style>
    .money-cents {
        font-size: 0.58em;
        vertical-align: baseline;
        position: relative;
        top: -0.55em;
        font-weight: inherit;
        opacity: 0.85;
        letter-spacing: 0.01em;
        line-height: 1;
        margin-left: 0.04em;
    }
</style>
@stop
