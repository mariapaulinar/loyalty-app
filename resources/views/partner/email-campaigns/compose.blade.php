{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Email Campaign Composer — Premium Wizard Flow

Purpose: Create and send targeted email campaigns to members.
Philosophy: Progressive disclosure. Show what matters, when it matters.

UX Principles:
- Wizard flow: One decision at a time, never overwhelm
- Live feedback: Recipient count animates, preview is instant
- Confidence: Clear progress, obvious next steps
- Delight: Smooth transitions, satisfying micro-interactions
--}}

@extends('partner.layouts.default')

@section('page_title', trans('common.email_campaign.compose') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div 
    class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8"
    x-data="campaignComposer()"
    x-cloak
>
    {{-- Page Header — Consistent with batch-wizard design --}}
    <x-ui.page-header
        icon="mail-plus"
        :title="trans('common.email_campaign.compose')"
        :description="trans('common.email_campaign.compose_description')"
        :breadcrumbs="[
            ['url' => route('partner.index'), 'icon' => 'home', 'title' => trans('common.dashboard')],
            ['url' => route('partner.email-campaigns.index'), 'text' => trans('common.email_campaigns')],
            ['text' => trans('common.email_campaign.compose')]
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('partner.email-campaigns.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 
                       text-sm font-medium text-secondary-700 dark:text-secondary-300 
                       bg-white dark:bg-secondary-800 
                       border border-secondary-200 dark:border-secondary-700 
                       rounded-xl shadow-sm
                       hover:bg-secondary-50 dark:hover:bg-secondary-700 
                       hover:border-secondary-300 dark:hover:border-secondary-600
                       focus:outline-none focus:ring-2 focus:ring-primary-500/20
                       transition-colors duration-200">
                <x-ui.icon icon="arrow-left" class="w-4 h-4" />
                <span class="hidden sm:inline">{{ trans('common.back_to_campaigns') }}</span>
            </a>

            {{-- Live Recipient Counter — Premium pill --}}
            <div 
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl transition-all duration-300 shrink-0"
                x-bind:class="recipientCount > 0 
                    ? 'bg-primary-50 dark:bg-primary-900/30 border border-primary-200 dark:border-primary-800' 
                    : 'bg-secondary-100 dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700'"
            >
                <div class="w-4 h-4 flex items-center justify-center">
                    {{-- Users icon - hidden when loading --}}
                    <span 
                        x-show="!loading"
                        x-bind:class="recipientCount > 0 ? 'text-primary-600 dark:text-primary-400' : 'text-secondary-400'"
                    >
                        <x-ui.icon icon="users" class="w-4 h-4" />
                    </span>
                    {{-- Spinner - shown when loading --}}
                    <span 
                        x-show="loading"
                        x-cloak
                        class="w-4 h-4 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"
                    ></span>
                </div>
                <span 
                    class="text-sm font-semibold tabular-nums transition-all duration-300"
                    x-bind:class="recipientCount > 0 ? 'text-primary-700 dark:text-primary-300' : 'text-secondary-500'"
                    x-text="loading ? '...' : recipientCount.toLocaleString()"
                ></span>
                <span class="text-xs text-secondary-400 dark:text-secondary-500 hidden sm:inline">
                    {{ trans('common.email_campaign.recipients') }}
                </span>
            </div>
        </x-slot>
    </x-ui.page-header>

    {{-- Progress Indicator — iOS-style clean design (same as batch-wizard) --}}
    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-100 dark:border-secondary-800 shadow-sm p-6 mb-8">
        <div class="flex items-center justify-between gap-4">
            {{-- Step Progress --}}
            <div class="flex items-start flex-1">
                <template x-for="(stepData, index) in steps" :key="index">
                    <div class="flex items-start" x-bind:class="index < steps.length - 1 ? 'flex-1' : ''">
                        {{-- Step Circle --}}
                        <div class="relative flex flex-col items-center">
                            <button 
                                type="button"
                                @click="if (canGoToStep(index)) step = index"
                                x-bind:class="{
                                    'bg-primary-600 text-white shadow-lg shadow-primary-500/25': step === index,
                                    'bg-emerald-500 text-white': step > index,
                                    'bg-secondary-100 dark:bg-secondary-800 text-secondary-400 dark:text-secondary-500': step < index
                                }"
                                class="w-11 h-11 rounded-xl flex items-center justify-center font-semibold transition-all duration-300 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                            >
                                <span x-show="step <= index" x-text="index + 1"></span>
                                <x-ui.icon icon="check" class="w-5 h-5" x-show="step > index" x-cloak />
                            </button>
                            <span 
                                class="mt-2.5 text-xs font-medium whitespace-nowrap"
                                x-bind:class="step >= index ? 'text-secondary-900 dark:text-white' : 'text-secondary-400 dark:text-secondary-500'"
                                x-text="stepData.title"
                            ></span>
                        </div>

                        {{-- Connecting Line --}}
                        <div 
                            x-show="index < steps.length - 1"
                            class="flex-1 h-0.5 mx-4 mt-5 rounded-full transition-all duration-500"
                            x-bind:class="step > index ? 'bg-emerald-500' : 'bg-secondary-200 dark:bg-secondary-700'"
                        ></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Main Form --}}
    <form 
        x-ref="campaignForm"
        action="{{ route('partner.email-campaigns.send') }}" 
        method="POST"
        @submit.prevent="confirmAndSubmit"
        novalidate
        class="space-y-6"
    >
        @csrf
        <input type="hidden" name="send_action" x-ref="sendAction" value="send_now">

        {{-- Error Alert --}}
        @if ($errors->any())
            <div class="mb-8 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl">
                <div class="flex items-start gap-3">
                    <x-ui.icon icon="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
                    <div class="text-sm text-red-700 dark:text-red-300">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════════════════ --}}
        {{-- STEP 0: AUDIENCE — Who are you reaching? --}}
        {{-- ═══════════════════════════════════════════════════════════════════════ --}}
        <div x-show="step === 0" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            
            {{-- Section Header --}}
            <div class="text-center mb-10">
                <x-ui.icon icon="users" class="w-8 h-8 text-secondary-400 dark:text-secondary-500 mb-4 mx-auto" />
                <h2 class="text-2xl md:text-3xl font-bold text-secondary-900 dark:text-white mb-2">
                    {{ trans('common.email_campaign.who_to_reach') }}
                </h2>
                <p class="text-secondary-500 dark:text-secondary-400 max-w-md mx-auto">
                    {{ trans('common.email_campaign.audience_description') }}
                </p>
            </div>

            {{-- Segment Cards — Visual, scannable, delightful --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                @foreach($segmentTypes as $type => $segment)
                    @php
                        // Icons must match segment type keys from EmailCampaignService
                        $icons = [
                            'all_members' => 'users',
                            'card_members' => 'credit-card',
                            'points_below' => 'trending-down',
                            'points_above' => 'trending-up',
                            'stamps_in_progress' => 'target',
                            'has_voucher' => 'ticket',
                            'inactive' => 'clock',
                            'tier' => 'award',
                            'locale' => 'globe',
                        ];
                        $colors = [
                            'all_members' => 'from-violet-500 to-purple-600',
                            'card_members' => 'from-blue-500 to-cyan-500',
                            'points_below' => 'from-amber-500 to-orange-500',
                            'points_above' => 'from-green-500 to-emerald-500',
                            'stamps_in_progress' => 'from-emerald-500 to-teal-500',
                            'has_voucher' => 'from-pink-500 to-rose-500',
                            'inactive' => 'from-secondary-500 to-secondary-600',
                            'tier' => 'from-sky-500 to-blue-500',
                            'locale' => 'from-indigo-500 to-violet-500',
                        ];
                    @endphp
                    <label 
                        class="group relative flex flex-col p-5 rounded-2xl cursor-pointer transition-colors duration-200"
                        x-bind:class="segmentType === '{{ $type }}' 
                            ? 'bg-white dark:bg-secondary-800 ring-2 ring-primary-500 shadow-sm' 
                            : 'bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-800 hover:border-secondary-300 dark:hover:border-secondary-700'"
                    >
                        <input 
                            type="radio" 
                            name="segment_type" 
                            value="{{ $type }}"
                            x-model="segmentType"
                            @change="onSegmentChange"
                            class="sr-only"
                        >
                        
                        {{-- Icon --}}
                        <div 
                            class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-all duration-300"
                            x-bind:class="segmentType === '{{ $type }}' 
                                ? 'bg-gradient-to-br {{ $colors[$type] ?? 'from-primary-500 to-primary-600' }} shadow-lg' 
                                : 'bg-secondary-100 dark:bg-secondary-800 group-hover:bg-secondary-200 dark:group-hover:bg-secondary-700'"
                        >
                            <span 
                                class="transition-colors duration-300"
                                x-bind:class="segmentType === '{{ $type }}' ? 'text-white' : 'text-secondary-500 dark:text-secondary-400'"
                            >
                                <x-ui.icon :icon="$icons[$type] ?? 'users'" class="w-6 h-6" />
                            </span>
                        </div>
                        
                        {{-- Content --}}
                        <h3 class="font-semibold text-secondary-900 dark:text-white mb-1">
                            {{ $segment['label'] }}
                        </h3>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400 leading-relaxed">
                            {{ $segment['description'] }}
                        </p>

                        {{-- Selection indicator --}}
                        <div 
                            class="absolute top-4 right-4 w-6 h-6 rounded-full flex items-center justify-center transition-all duration-300"
                            x-bind:class="segmentType === '{{ $type }}' 
                                ? 'bg-primary-500 scale-100' 
                                : 'bg-secondary-200 dark:bg-secondary-700 scale-75 opacity-0 group-hover:opacity-100'"
                        >
                            <x-ui.icon icon="check" class="w-4 h-4 text-white" x-show="segmentType === '{{ $type }}'" />
                        </div>
                    </label>
                @endforeach
            </div>

            {{-- Dynamic Config Panel — Slides in when needed --}}
            <div 
                x-show="segmentConfig.length > 0"
                x-collapse
                x-cloak
                class="mb-8"
            >
                <div class="bg-secondary-50 dark:bg-secondary-800/50 rounded-2xl p-6 border border-secondary-200 dark:border-secondary-700">
                    <h4 class="text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-4 flex items-center gap-2">
                        <x-ui.icon icon="sliders" class="w-4 h-4" />
                        {{ trans('common.email_campaign.refine_selection') }}
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Card Selection --}}
                        <div x-show="segmentConfig.includes('card_id')" x-cloak>
                            <x-forms.select
                                name="segment_config[card_id]"
                                :label="trans('common.select_card')"
                                x-model="configValues.card_id"
                                @change="onConfigChange"
                                :required="true"
                            >
                                <option value="">{{ trans('common.select_option') }}</option>
                                @foreach($cards as $card)
                                    <option value="{{ $card->id }}">{{ $card->name }}</option>
                                @endforeach
                            </x-forms.select>
                        </div>

                        {{-- Stamp Card Selection --}}
                        <div x-show="segmentConfig.includes('stamp_card_id')" x-cloak>
                            <x-forms.select
                                name="segment_config[stamp_card_id]"
                                :label="trans('common.select_stamp_card')"
                                x-model="configValues.stamp_card_id"
                                @change="onConfigChange"
                                :required="true"
                            >
                                <option value="">{{ trans('common.select_option') }}</option>
                                @foreach($stampCards as $stampCard)
                                    <option value="{{ $stampCard->id }}">{{ $stampCard->name }}</option>
                                @endforeach
                            </x-forms.select>
                        </div>

                        {{-- Voucher Selection --}}
                        <div x-show="segmentConfig.includes('voucher_id')" x-cloak>
                            <x-forms.select
                                name="segment_config[voucher_id]"
                                :label="trans('common.select_voucher')"
                                x-model="configValues.voucher_id"
                                @change="onConfigChange"
                                :required="true"
                            >
                                <option value="">{{ trans('common.select_option') }}</option>
                                @foreach($vouchers as $voucher)
                                    <option value="{{ $voucher->id }}">{{ $voucher->name }}</option>
                                @endforeach
                            </x-forms.select>
                        </div>

                        {{-- Club Selection --}}
                        <div x-show="segmentConfig.includes('club_id')" x-cloak>
                            <x-forms.select
                                name="segment_config[club_id]"
                                :label="trans('common.select_club')"
                                x-model="configValues.club_id"
                                @change="configValues.tier_id = ''; onConfigChange()"
                                :required="true"
                            >
                                <option value="">{{ trans('common.select_option') }}</option>
                                @foreach($clubs as $club)
                                    <option value="{{ $club->id }}">{{ $club->name }}</option>
                                @endforeach
                            </x-forms.select>
                        </div>

                        {{-- Tier Selection --}}
                        <div x-show="segmentConfig.includes('tier_id') && configValues.club_id" x-cloak>
                            <x-forms.select
                                name="segment_config[tier_id]"
                                :label="trans('common.select_tier')"
                                x-model="configValues.tier_id"
                                @change="onConfigChange"
                                :required="true"
                            >
                                <option value="">{{ trans('common.select_option') }}</option>
                                <template x-for="tier in filteredTiers" :key="tier.id">
                                    <option :value="tier.id" x-text="tier.name"></option>
                                </template>
                            </x-forms.select>
                        </div>

                        {{-- Points Threshold --}}
                        <div x-show="segmentConfig.includes('threshold')" x-cloak>
                            <x-forms.input
                                type="number"
                                name="segment_config[threshold]"
                                :label="trans('common.email_campaign.points_threshold')"
                                x-model="configValues.threshold"
                                @input="debounceConfigChange"
                                min="1"
                                :required="true"
                            />
                        </div>

                        {{-- Stamps Remaining --}}
                        <div x-show="segmentConfig.includes('stamps_remaining')" x-cloak>
                            <x-forms.input
                                type="number"
                                name="segment_config[stamps_remaining]"
                                :label="trans('common.email_campaign.stamps_remaining')"
                                :help="trans('common.email_campaign.stamps_remaining_help')"
                                x-model="configValues.stamps_remaining"
                                @input="debounceConfigChange"
                                min="1"
                                max="20"
                                :required="true"
                            />
                        </div>

                        {{-- Inactive Days --}}
                        <div x-show="segmentConfig.includes('days')" x-cloak>
                            <x-forms.input
                                type="number"
                                name="segment_config[days]"
                                :label="trans('common.email_campaign.inactive_days')"
                                :help="trans('common.email_campaign.inactive_days_help')"
                                x-model="configValues.days"
                                @input="debounceConfigChange"
                                min="7"
                                max="365"
                                :required="true"
                            />
                        </div>

                        {{-- Locale Selection --}}
                        <div x-show="segmentConfig.includes('locale')" x-cloak>
                            <x-forms.select
                                name="segment_config[locale]"
                                :label="trans('common.email_campaign.select_locale')"
                                x-model="configValues.locale"
                                @change="onConfigChange"
                                :required="true"
                            >
                                <option value="">{{ trans('common.select_option') }}</option>
                                @foreach($availableLocales as $locale)
                                    <option value="{{ $locale['locale'] }}">
                                        {{ $locale['label'] }} ({{ $locale['count'] }} {{ trans('common.members') }})
                                    </option>
                                @endforeach
                            </x-forms.select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sample Recipients Preview --}}
            <div 
                x-show="sampleRecipients.length > 0 && !loading"
                x-transition
                class="mb-8"
            >
                <div class="flex items-center gap-2 mb-3">
                    <x-ui.icon icon="eye" class="w-4 h-4 text-secondary-400" />
                    <span class="text-sm font-medium text-secondary-600 dark:text-secondary-400">
                        {{ trans('common.email_campaign.sample_recipients') }}
                    </span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="recipient in sampleRecipients.slice(0, 5)" :key="recipient.email">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-secondary-800 rounded-full border border-secondary-200 dark:border-secondary-700 text-sm">
                            <div class="w-6 h-6 rounded-full bg-gradient-to-br from-primary-400 to-violet-500 flex items-center justify-center text-white text-xs font-bold" x-text="recipient.name.charAt(0).toUpperCase()"></div>
                            <span class="text-secondary-700 dark:text-secondary-300 font-medium" x-text="recipient.name"></span>
                        </div>
                    </template>
                    <div 
                        x-show="recipientCount > 5"
                        class="inline-flex items-center px-3 py-1.5 bg-secondary-100 dark:bg-secondary-800 rounded-full text-sm text-secondary-500"
                    >
                        +<span x-text="(recipientCount - 5).toLocaleString()"></span>&nbsp;{{ trans('common.more') }}
                    </div>
                </div>
            </div>

            {{-- No Recipients Warning --}}
            <div 
                x-show="recipientCount === 0 && !loading && segmentType"
                x-transition
                class="mb-8"
            >
                <div class="flex items-center gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                    <x-ui.icon icon="alert-triangle" class="w-5 h-5 text-amber-500 flex-shrink-0" />
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        {{ trans('common.email_campaign.no_recipients') }}
                    </p>
                </div>
            </div>

            {{-- Navigation Buttons — Clean, purposeful actions (same as batch-wizard) --}}
            <div class="flex items-center justify-between gap-4 pt-2">
                <div class="flex-1"></div>

                <button
                    type="button"
                    @click="step = 1"
                    :disabled="recipientCount === 0 || loading"
                    class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium 
                        text-white bg-primary-600 hover:bg-primary-500
                        rounded-xl transition-colors duration-200
                        disabled:opacity-50 disabled:cursor-not-allowed"
                    x-cloak
                >
                    {{ trans('common.continue') }}
                    <x-ui.icon icon="arrow-right" class="w-4 h-4" />
                </button>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════════════ --}}
        {{-- STEP 1: CONTENT — What's your message? --}}
        {{-- ═══════════════════════════════════════════════════════════════════════ --}}
        <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            
            {{-- Inline Validation Error --}}
            <div 
                x-show="validationError"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="mb-6 p-4 rounded-2xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800"
            >
                <div class="flex items-center gap-3">
                    <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                        <x-ui.icon icon="alert-circle" class="w-5 h-5 text-red-600 dark:text-red-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-red-800 dark:text-red-200" x-text="validationError"></p>
                    </div>
                    <button 
                        type="button"
                        @click="validationError = null"
                        class="ml-auto shrink-0 p-1 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-200 transition-colors"
                    >
                        <x-ui.icon icon="x" class="w-4 h-4" />
                    </button>
                </div>
            </div>
            
            {{-- Section Header --}}
            <div class="text-center mb-10">
                <x-ui.icon icon="file-text" class="w-8 h-8 text-secondary-400 dark:text-secondary-500 mb-4 mx-auto" />
                <h2 class="text-2xl md:text-3xl font-bold text-secondary-900 dark:text-white mb-2">
                    {{ trans('common.email_campaign.craft_message') }}
                </h2>
                <p class="text-secondary-500 dark:text-secondary-400 max-w-md mx-auto">
                    {{ trans('common.email_campaign.content_description') }}
                </p>
            </div>

            @php
                $defaultLocale = config('app.locale');
                $hasMultipleLanguages = count($languages['all']) > 1;
                $defaultLanguage = collect($languages['all'])->firstWhere('locale', $defaultLocale) ?? $languages['current'];
                $otherLanguages = collect($languages['all'])->filter(fn($lang) => $lang['locale'] !== $defaultLocale)->values();
            @endphp

            {{-- Content Card --}}
            <div class="bg-white dark:bg-secondary-900 rounded-3xl border border-secondary-200 dark:border-secondary-800 shadow-sm overflow-hidden mb-8">
                
                {{-- Subject Field --}}
                <div class="p-6 border-b border-secondary-100 dark:border-secondary-800">
                    <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-3">
                        {{ trans('common.email_campaign.subject') }} <span class="text-red-500">*</span>
                    </label>
                    
                    {{-- Default Language --}}
                    <div class="relative">
                        @if($hasMultipleLanguages)
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 flex items-center gap-2 pointer-events-none">
                                <span class="w-5 h-5 rounded-full ring-1 ring-secondary-200 dark:ring-secondary-600 overflow-hidden inline-flex items-center justify-center fis fi-{{ strtolower($defaultLanguage['countryCode']) }}"></span>
                            </div>
                        @endif
                        <input 
                            type="text"
                            name="subject[{{ $defaultLocale }}]"
                            x-model="subjectTranslations['{{ $defaultLocale }}']"
                            placeholder="{{ trans('common.email_campaign.subject_placeholder') }}"
                            required
                            class="w-full px-4 py-3 {{ $hasMultipleLanguages ? 'pl-12' : '' }} text-lg font-medium 
                                   bg-secondary-50 dark:bg-secondary-800 
                                   border-0 rounded-xl
                                   text-secondary-900 dark:text-white 
                                   placeholder-secondary-400 dark:placeholder-secondary-500
                                   focus:ring-2 focus:ring-primary-500/20 focus:bg-white dark:focus:bg-secondary-800
                                   transition-all duration-200"
                        >
                    </div>
                    
                    {{-- Other Languages --}}
                    @if($hasMultipleLanguages)
                        <div x-data="{ showTranslations: false }" class="mt-3">
                            <button 
                                type="button" 
                                @click="showTranslations = !showTranslations" 
                                class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400 flex items-center gap-1.5 transition-colors"
                            >
                                <x-ui.icon icon="globe" class="w-4 h-4" />
                                {{ trans('common.add_translations') }}
                                <span class="transition-transform" x-bind:class="{ 'rotate-180': showTranslations }"><x-ui.icon icon="chevron-down" class="w-4 h-4" /></span>
                            </button>
                            
                            <div x-show="showTranslations" x-collapse x-cloak class="mt-3 space-y-3">
                                @foreach($otherLanguages as $language)
                                    <div class="relative">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 flex items-center gap-2 pointer-events-none">
                                            <span class="w-5 h-5 rounded-full ring-1 ring-secondary-200 dark:ring-secondary-600 overflow-hidden inline-flex items-center justify-center fis fi-{{ strtolower($language['countryCode']) }}"></span>
                                        </div>
                                        <input 
                                            type="text"
                                            name="subject[{{ $language['locale'] }}]"
                                            x-model="subjectTranslations['{{ $language['locale'] }}']"
                                            placeholder="{{ $language['languageName'] }}"
                                            class="w-full pl-12 pr-4 py-2.5 
                                                   bg-secondary-50 dark:bg-secondary-800 
                                                   border border-secondary-200 dark:border-secondary-700 rounded-xl
                                                   text-secondary-900 dark:text-white 
                                                   placeholder-secondary-400 dark:placeholder-secondary-500
                                                   focus:ring-2 focus:ring-primary-500/20 focus:border-primary-300
                                                   transition-all duration-200"
                                        >
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Body Field --}}
                <div class="p-6">
                    <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-3">
                        {{ trans('common.email_campaign.body') }} <span class="text-red-500">*</span>
                        @if($hasMultipleLanguages)
                            <span class="ml-2 inline-flex items-center gap-1 text-xs text-secondary-400">
                                <span class="w-4 h-4 rounded-full ring-1 ring-secondary-200 dark:ring-secondary-600 overflow-hidden inline-flex items-center justify-center fis fi-{{ strtolower($defaultLanguage['countryCode']) }}"></span>
                                {{ $defaultLanguage['languageName'] }}
                            </span>
                        @endif
                    </label>
                    
                    <x-forms.tiptap
                        name="body[{{ $defaultLocale }}]"
                        :required="true"
                        class="mb-0"
                    />
                    
                    {{-- Other Languages --}}
                    @if($hasMultipleLanguages)
                        <div x-data="{ showBodyTranslations: false }" class="mt-4">
                            <button 
                                type="button" 
                                @click="showBodyTranslations = !showBodyTranslations" 
                                class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400 flex items-center gap-1.5 transition-colors"
                            >
                                <x-ui.icon icon="globe" class="w-4 h-4" />
                                {{ trans('common.add_translations') }}
                                <span class="transition-transform" x-bind:class="{ 'rotate-180': showBodyTranslations }"><x-ui.icon icon="chevron-down" class="w-4 h-4" /></span>
                            </button>
                            
                            <div x-show="showBodyTranslations" x-collapse x-cloak class="mt-4 space-y-6">
                                @foreach($otherLanguages as $language)
                                    <div>
                                        <label class="block text-sm font-medium text-secondary-600 dark:text-secondary-400 mb-2">
                                            <span class="inline-flex items-center gap-2">
                                                <span class="w-4 h-4 rounded-full ring-1 ring-secondary-200 dark:ring-secondary-600 overflow-hidden inline-flex items-center justify-center fis fi-{{ strtolower($language['countryCode']) }}"></span>
                                                {{ $language['languageName'] }}
                                            </span>
                                        </label>
                                        <x-forms.tiptap
                                            name="body[{{ $language['locale'] }}]"
                                            class="mb-0"
                                        />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Personalization Tip --}}
                <div class="px-6 pb-6">
                    <div class="p-4 bg-gradient-to-r from-primary-50 to-violet-50 dark:from-primary-900/20 dark:to-violet-900/20 rounded-xl border border-primary-100 dark:border-primary-800/50">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center flex-shrink-0">
                                <x-ui.icon icon="sparkles" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-primary-900 dark:text-primary-100">
                                    {{ trans('common.email_campaign.personalization_title') }}
                                </h4>
                                <p class="mt-1 text-xs text-primary-700 dark:text-primary-300">
                                    {{ trans('common.email_campaign.personalization_description') }}
                                </p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <code class="px-2 py-1 bg-white/50 dark:bg-secondary-800/50 rounded text-xs font-mono text-primary-700 dark:text-primary-300">{name}</code>
                                    <code class="px-2 py-1 bg-white/50 dark:bg-secondary-800/50 rounded text-xs font-mono text-primary-700 dark:text-primary-300">{email}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Navigation Buttons — Clean, purposeful actions (same as batch-wizard) --}}
            <div class="flex items-center justify-between gap-4 pt-2">
                <button
                    type="button"
                    @click="step = 0"
                    class="inline-flex items-center gap-2 px-5 py-3 text-sm font-medium 
                        text-secondary-700 dark:text-secondary-300 
                        bg-secondary-100 dark:bg-secondary-800 
                        hover:bg-secondary-200 dark:hover:bg-secondary-700 
                        rounded-xl transition-all duration-200"
                >
                    <x-ui.icon icon="arrow-left" class="w-4 h-4" />
                    {{ trans('common.previous') }}
                </button>

                <div class="flex-1"></div>

                <button
                    type="button"
                    @click="step = 2"
                    class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium 
                        text-white bg-primary-600 hover:bg-primary-500
                        rounded-xl transition-colors duration-200"
                >
                    {{ trans('common.continue') }}
                    <x-ui.icon icon="arrow-right" class="w-4 h-4" />
                </button>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════════════ --}}
        {{-- STEP 2: REVIEW — Final check before sending --}}
        {{-- ═══════════════════════════════════════════════════════════════════════ --}}
        <div x-show="step === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            
            {{-- Section Header --}}
            <div class="text-center mb-10">
                <x-ui.icon icon="check-circle" class="w-8 h-8 text-secondary-400 dark:text-secondary-500 mb-4 mx-auto" />
                <h2 class="text-2xl md:text-3xl font-bold text-secondary-900 dark:text-white mb-2">
                    {{ trans('common.email_campaign.review_send') }}
                </h2>
                <p class="text-secondary-500 dark:text-secondary-400 max-w-md mx-auto">
                    {{ trans('common.email_campaign.review_description') }}
                </p>
            </div>

            {{-- Review Cards --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                
                {{-- Audience Summary --}}
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <x-ui.icon icon="users" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                        <h3 class="font-semibold text-secondary-900 dark:text-white">
                            {{ trans('common.email_campaign.audience') }}
                        </h3>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-secondary-500 dark:text-secondary-400">{{ trans('common.email_campaign.segment_label') }}</span>
                            <span class="text-sm font-medium text-secondary-900 dark:text-white" x-text="segmentTypes[segmentType]?.label || segmentType"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-secondary-500 dark:text-secondary-400">{{ trans('common.email_campaign.recipients') }}</span>
                            <span class="text-lg font-bold text-primary-600 dark:text-primary-400 tabular-nums" x-text="recipientCount.toLocaleString()"></span>
                        </div>
                    </div>
                    
                    <button type="button" @click="step = 0" class="mt-4 text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400 flex items-center gap-1">
                        <x-ui.icon icon="pencil" class="w-3 h-3" />
                        {{ trans('common.edit') }}
                    </button>
                </div>

                {{-- Sender Settings --}}
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <x-ui.icon icon="user-circle" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                        <h3 class="font-semibold text-secondary-900 dark:text-white">
                            {{ trans('common.email_campaign.sender_settings') }}
                        </h3>
                    </div>
                    
                    <div class="space-y-4">
                        <x-forms.input
                            name="sender_name"
                            :label="trans('common.email_campaign.sender_name')"
                            :value="$partner->getCampaignSenderName()"
                            :placeholder="$partner->name"
                        />
                        
                        <x-forms.input
                            type="email"
                            name="reply_to"
                            :label="trans('common.email_campaign.reply_to')"
                            :value="$partner->getCampaignReplyTo()"
                            :placeholder="$partner->email"
                        />
                    </div>

                    <div class="mt-4 p-3 bg-secondary-50 dark:bg-secondary-800 rounded-lg">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400 flex items-start gap-2">
                            <x-ui.icon icon="info" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                            {{ trans('common.email_campaign.system_email_notice', ['email' => config('default.mail_from_address')]) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Preview Button --}}
            <div class="flex justify-center mb-8">
                <button 
                    type="button" 
                    @click="openPreview"
                    class="inline-flex items-center gap-2 px-5 py-2.5 
                           text-sm font-medium text-secondary-700 dark:text-secondary-300 
                           bg-white dark:bg-secondary-800 
                           border border-secondary-200 dark:border-secondary-700 
                           rounded-xl shadow-sm
                           hover:bg-secondary-50 dark:hover:bg-secondary-700 
                           transition-all duration-200"
                >
                    <x-ui.icon icon="eye" class="w-5 h-5" />
                    {{ trans('common.email_campaign.preview') }}
                </button>
            </div>

            {{-- Navigation Buttons — Clean, purposeful actions (same as batch-wizard) --}}
            <div class="flex items-center justify-between gap-4 pt-2">
                <button
                    type="button"
                    @click="step = 1"
                    class="inline-flex items-center gap-2 px-5 py-3 text-sm font-medium 
                        text-secondary-700 dark:text-secondary-300 
                        bg-secondary-100 dark:bg-secondary-800 
                        hover:bg-secondary-200 dark:hover:bg-secondary-700 
                        rounded-xl transition-all duration-200"
                >
                    <x-ui.icon icon="arrow-left" class="w-4 h-4" />
                    {{ trans('common.previous') }}
                </button>

                <div class="flex-1"></div>

                <button
                    type="submit"
                    :disabled="recipientCount === 0 || loading"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium 
                        text-white bg-emerald-600 hover:bg-emerald-500
                        rounded-xl transition-colors duration-200
                        disabled:opacity-75 disabled:cursor-not-allowed"
                >
                    <x-ui.icon icon="send" class="w-4 h-4" />
                    <span>{{ trans('common.email_campaign.send_campaign') }}</span>
                </button>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════════════ --}}
        {{-- MODALS --}}
        {{-- ═══════════════════════════════════════════════════════════════════════ --}}

        {{-- Confirmation Modal --}}
        <template x-teleport="body">
            <div 
                x-show="showConfirmModal"
                x-cloak
                class="fixed inset-0 z-50 overflow-y-auto"
                @keydown.escape.window="showConfirmModal = false"
            >
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showConfirmModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div 
                        class="relative w-full max-w-md bg-white dark:bg-secondary-800 rounded-3xl shadow-2xl overflow-hidden"
                        @click.stop
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                    >
                        <div class="p-8 text-center">
                            <x-ui.icon icon="send" class="w-8 h-8 text-secondary-400 dark:text-secondary-500 mx-auto mb-6" />
                            <h3 class="text-xl font-bold text-secondary-900 dark:text-white mb-2">
                                {{ trans('common.email_campaign.confirm_send_title') }}
                            </h3>
                            <p class="text-secondary-600 dark:text-secondary-400 mb-2">
                                {{ trans('common.email_campaign.confirm_send_message') }}
                            </p>
                            <p class="text-3xl font-bold text-primary-600 dark:text-primary-400 mb-6 tabular-nums" x-text="recipientCount.toLocaleString() + ' ' + '{{ trans('common.members') }}'"></p>
                            
                            <div class="flex flex-col gap-3">
                                {{-- Primary: Send Now --}}
                                <button 
                                    type="button"
                                    @click="submitForm('send_now')"
                                    class="w-full px-4 py-3.5 text-sm font-bold text-white bg-primary-600 hover:bg-primary-500 rounded-xl transition-colors duration-200"
                                >
                                    <span class="flex items-center justify-center gap-2">
                                        <x-ui.icon icon="send" class="w-4 h-4" />
                                        {{ trans('common.email_campaign.send_now') }}
                                    </span>
                                </button>
                                
                                {{-- Secondary: Save for Later --}}
                                <button 
                                    type="button"
                                    @click="submitForm('save_draft')"
                                    class="w-full px-4 py-3 text-sm font-medium text-secondary-700 dark:text-secondary-300 bg-secondary-100 dark:bg-secondary-700 rounded-xl hover:bg-secondary-200 dark:hover:bg-secondary-600 transition-colors"
                                >
                                    <span class="flex items-center justify-center gap-2">
                                        <x-ui.icon icon="save" class="w-4 h-4" />
                                        {{ trans('common.email_campaign.save_for_later') }}
                                    </span>
                                </button>
                                
                                {{-- Tertiary: Cancel --}}
                                <button 
                                    type="button"
                                    @click="showConfirmModal = false"
                                    class="w-full px-4 py-2 text-sm text-secondary-500 dark:text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-300 transition-colors"
                                >
                                    {{ trans('common.cancel') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        {{-- Preview Modal --}}
        <template x-teleport="body">
            <div 
                x-show="showPreviewModal"
                x-cloak
                class="fixed inset-0 z-50 overflow-y-auto"
                @keydown.escape.window="showPreviewModal = false"
            >
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showPreviewModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div 
                        class="relative w-full max-w-4xl bg-white dark:bg-secondary-800 rounded-3xl shadow-2xl overflow-hidden"
                        @click.stop
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                    >
                        {{-- Header --}}
                        <div class="flex items-center justify-between px-6 py-4 border-b border-secondary-200 dark:border-secondary-700">
                            <div>
                                <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
                                    {{ trans('common.email_campaign.preview_title') }}
                                </h3>
                                <p class="text-sm text-secondary-500 dark:text-secondary-400" x-text="previewSubject"></p>
                            </div>
                            
                            @if($hasMultipleLanguages)
                                <div class="flex items-center gap-2 flex-wrap">
                                    @foreach($languages['all'] as $language)
                                        <button 
                                            type="button"
                                            @click="loadPreview('{{ $language['locale'] }}')"
                                            x-bind:class="previewLocale === '{{ $language['locale'] }}' 
                                                ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-600 ring-1 ring-primary-500' 
                                                : 'bg-secondary-100 dark:bg-secondary-700 text-secondary-600 dark:text-secondary-400'"
                                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
                                        >
                                            <span class="w-4 h-4 rounded-full ring-1 ring-secondary-200 dark:ring-secondary-600 overflow-hidden inline-flex items-center justify-center fis fi-{{ strtolower($language['countryCode']) }}"></span>
                                            {{ $language['languageName'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            
                            <button 
                                type="button" 
                                @click="showPreviewModal = false"
                                class="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors"
                            >
                                <x-ui.icon icon="x" class="w-5 h-5 text-secondary-500" />
                            </button>
                        </div>
                        
                        {{-- Content --}}
                        <div class="p-6 max-h-[70vh] overflow-y-auto">
                            <template x-if="previewLoading">
                                <div class="flex items-center justify-center py-12">
                                    <div class="w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
                                </div>
                            </template>
                            <template x-if="!previewLoading">
                                <iframe 
                                    :srcdoc="previewHtml" 
                                    class="w-full min-h-[500px] border border-secondary-200 dark:border-secondary-700 rounded-xl bg-white"
                                    sandbox="allow-same-origin"
                                ></iframe>
                            </template>
                        </div>
                        
                        {{-- Footer --}}
                        <div class="flex justify-end gap-3 px-6 py-4 border-t border-secondary-200 dark:border-secondary-700">
                            <button 
                                type="button" 
                                @click="showPreviewModal = false"
                                class="px-6 py-2 text-secondary-600 hover:text-secondary-800 dark:text-secondary-400 dark:hover:text-secondary-200 transition-colors"
                            >
                                {{ trans('common.close') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('campaignComposer', () => ({
        // Wizard steps
        step: 0,
        steps: [
            { title: '{{ trans('common.email_campaign.step_audience') }}' },
            { title: '{{ trans('common.email_campaign.step_content') }}' },
            { title: '{{ trans('common.email_campaign.step_review') }}' }
        ],
        
        // Segment state
        segmentType: '{{ old('segment_type', 'all_members') }}',
        segmentConfig: @json($segmentTypes[old('segment_type', 'all_members')]['config'] ?? []),
        configValues: {
            card_id: '{{ old('segment_config.card_id', '') }}',
            stamp_card_id: '{{ old('segment_config.stamp_card_id', '') }}',
            voucher_id: '{{ old('segment_config.voucher_id', '') }}',
            club_id: '{{ old('segment_config.club_id', '') }}',
            tier_id: '{{ old('segment_config.tier_id', '') }}',
            threshold: '{{ old('segment_config.threshold', '') }}',
            stamps_remaining: '{{ old('segment_config.stamps_remaining', '') }}',
            days: '{{ old('segment_config.days', '30') }}',
            locale: '{{ old('segment_config.locale', '') }}',
        },
        
        // Tiers data for filtering by club
        allTiers: @json($tiers->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'club_id' => $t->club_id])),
        
        // Computed tiers filtered by selected club
        get filteredTiers() {
            if (!this.configValues.club_id) return [];
            return this.allTiers.filter(t => t.club_id === this.configValues.club_id);
        },
        
        // Recipient preview
        loading: false,
        recipientCount: 0,
        sampleRecipients: [],
        
        // Subject translations
        subjectTranslations: {},
        
        // Modals
        showConfirmModal: false,
        showPreviewModal: false,
        previewLoading: false,
        previewLocale: '{{ config('app.locale') }}',
        validationError: null,
        previewSubject: '',
        previewHtml: '',
        
        // Debounce timer
        debounceTimer: null,
        
        // Segment types data
        segmentTypes: @json($segmentTypes),
        
        init() {
            // Initial preview load
            this.loadRecipientPreview();
        },
        
        canGoToStep(targetStep) {
            if (targetStep === 0) return true;
            if (targetStep === 1) return this.recipientCount > 0;
            if (targetStep === 2) return this.recipientCount > 0;
            return false;
        },
        
        onSegmentChange() {
            this.segmentConfig = this.segmentTypes[this.segmentType]?.config || [];
            this.loadRecipientPreview();
        },
        
        onConfigChange() {
            this.loadRecipientPreview();
        },
        
        debounceConfigChange() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.loadRecipientPreview();
            }, 300);
        },
        
        async loadRecipientPreview() {
            this.loading = true;
            
            try {
                const response = await fetch('{{ route('partner.email-campaigns.preview') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        mode: 'count',
                        segment_type: this.segmentType,
                        segment_config: this.configValues,
                    }),
                });
                
                const data = await response.json();
                this.recipientCount = data.count || 0;
                this.sampleRecipients = data.sample || [];
            } catch (error) {
                console.error('Preview failed:', error);
                this.recipientCount = 0;
                this.sampleRecipients = [];
            } finally {
                this.loading = false;
            }
        },
        
        confirmAndSubmit(e) {
            // Clear previous validation errors
            this.validationError = null;
            
            // Validate required fields before showing confirmation
            if (this.recipientCount === 0) {
                return;
            }
            
            // Check subject is filled for default locale (read from form input)
            const defaultLocale = '{{ config('app.locale') }}';
            const subjectInput = this.$refs.campaignForm.querySelector(`[name="subject[${defaultLocale}]"]`);
            if (!subjectInput?.value?.trim()) {
                this.validationError = '{{ trans('common.email_campaign.validation.subject_required') }}';
                this.step = 1;
                return;
            }
            
            // Check body is filled for default locale (read from hidden TipTap input)
            const bodyInput = this.$refs.campaignForm.querySelector(`[name="body[${defaultLocale}]"]`);
            const bodyValue = bodyInput?.value?.trim() || '';
            if (!bodyValue || bodyValue === '<p></p>') {
                this.validationError = '{{ trans('common.email_campaign.validation.body_required') }}';
                this.step = 1;
                return;
            }
            
            this.showConfirmModal = true;
        },
        
        submitForm(action = 'send_now') {
            this.$refs.sendAction.value = action;
            this.showConfirmModal = false;
            this.$refs.campaignForm.submit();
        },
        
        saveDraft() {
            // For drafts, we don't require content validation
            this.validationError = null;
            this.submitForm('save_draft');
        },
        
        async openPreview() {
            this.showPreviewModal = true;
            this.loadPreview(this.previewLocale);
        },
        
        async loadPreview(locale) {
            this.previewLocale = locale;
            this.previewLoading = true;
            
            // Collect form data
            const subjectInputs = document.querySelectorAll('[name^="subject["]');
            const bodyInputs = document.querySelectorAll('[name^="body["]');
            
            const subject = {};
            const body = {};
            
            subjectInputs.forEach(input => {
                const match = input.name.match(/\[([^\]]+)\]/);
                if (match) subject[match[1]] = input.value;
            });
            
            bodyInputs.forEach(input => {
                const match = input.name.match(/\[([^\]]+)\]/);
                if (match) body[match[1]] = input.value;
            });
            
            try {
                const response = await fetch('{{ route('partner.email-campaigns.preview') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        mode: 'email',
                        subject,
                        body,
                        locale,
                    }),
                });
                
                const data = await response.json();
                this.previewSubject = data.subject || '';
                this.previewHtml = data.html || '';
            } catch (error) {
                console.error('Preview failed:', error);
            } finally {
                this.previewLoading = false;
            }
        },
    }));
});
</script>
@endpush
@endsection
