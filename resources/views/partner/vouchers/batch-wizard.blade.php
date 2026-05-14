{{--
 Reward Loyalty - Proprietary Software
 Copyright (c) 2025 NowSquare. All rights reserved.
 See LICENSE file for terms.

 Voucher Batch Generation Wizard - Template-Based Approach

 Purpose: Premium 3-step wizard using existing vouchers as templates.
 Philosophy: Zero duplication - reuse what's already perfect.
 Design: Apple-inspired elegance, Revolut-level polish, Stripe-quality execution.
--}}

@extends('partner.layouts.default')

@section('page_title', trans('common.batch_voucher_generation') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
    
    {{-- Page Header --}}
    <x-ui.page-header
        icon="sparkles"
        :title="trans('common.generate_batch')"
        :description="trans('common.select_template_and_generate')"
        :breadcrumbs="[
            ['url' => route('partner.index'), 'icon' => 'home', 'title' => trans('common.dashboard')],
            ['url' => route('partner.vouchers.batches'), 'text' => trans('common.batches')],
            ['text' => trans('common.generate_batch')]
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('partner.vouchers.batches') }}"
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
                <span class="hidden sm:inline">{{ trans('common.back_to_batches') }}</span>
            </a>
        </x-slot>
    </x-ui.page-header>

    @if($voucherTemplates->isEmpty())
        {{-- No Templates Available — Premium empty state --}}
        <div class="bg-white dark:bg-secondary-900 rounded-3xl border border-secondary-100 dark:border-secondary-800 shadow-sm overflow-hidden">
            <div class="flex flex-col items-center justify-center py-20 px-8">
                <x-ui.icon icon="ticket" class="w-8 h-8 text-secondary-400 dark:text-secondary-500 mb-6" />
                <h3 class="text-xl font-bold text-secondary-900 dark:text-white mb-3">
                    {{ trans('common.create_voucher_first') }}
                </h3>
                <p class="text-secondary-500 dark:text-secondary-400 mb-8 text-center max-w-md">
                    {{ trans('common.create_voucher_first_description') }}
                </p>
                <a href="{{ route('partner.data.list', ['name' => 'vouchers']) }}"
                    class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded-2xl font-medium text-sm
                        bg-secondary-900 dark:bg-white text-white dark:text-secondary-900 
                        hover:bg-secondary-800 dark:hover:bg-secondary-100
                        transition-colors duration-200">
                    <x-ui.icon icon="plus" class="w-4 h-4" />
                    {{ trans('common.create_first_voucher') }}
                </a>
            </div>
        </div>
    @else
        {{-- Wizard Content --}}
        <div x-data="batchWizard({{ json_encode($voucherTemplates) }})">
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800/50 dark:bg-red-900/20 dark:text-red-200">
                    <div class="font-semibold mb-1">{{ trans('common.error') }}</div>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Progress Indicator — iOS-style clean design --}}
            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-100 dark:border-secondary-800 shadow-sm p-6 mb-8">
                <div class="flex items-start justify-between">
                    <template x-for="(step, index) in steps" :key="index">
                        <div class="flex items-start" :class="index < steps.length - 1 ? 'flex-1' : ''">
                            {{-- Step Circle --}}
                            <div class="relative flex flex-col items-center">
                                <button 
                                    @click="goToStep(index)"
                                    :class="{
                                        'bg-primary-600 text-white shadow-lg shadow-primary-500/25': currentStep === index,
                                        'bg-emerald-500 text-white': currentStep > index,
                                        'bg-secondary-100 dark:bg-secondary-800 text-secondary-400 dark:text-secondary-500': currentStep < index
                                    }"
                                    class="w-11 h-11 rounded-xl flex items-center justify-center font-semibold transition-all duration-300 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                >
                                    <span x-show="currentStep <= index" x-text="index + 1"></span>
                                    <x-ui.icon icon="check" class="w-5 h-5" x-show="currentStep > index" x-cloak />
                                </button>
                                <span 
                                    class="mt-2.5 text-xs font-medium whitespace-nowrap"
                                    :class="currentStep >= index ? 'text-secondary-900 dark:text-white' : 'text-secondary-400 dark:text-secondary-500'"
                                    x-text="step.title"
                                ></span>
                            </div>

                            {{-- Connecting Line --}}
                            <div 
                                x-show="index < steps.length - 1"
                                class="flex-1 h-0.5 mx-4 mt-5 rounded-full transition-all duration-500"
                                :class="currentStep > index ? 'bg-emerald-500' : 'bg-secondary-200 dark:bg-secondary-700'"
                            ></div>
                        </div>
                    </template>
                </div>
            </div>

            <form method="POST" action="{{ route('partner.vouchers.batch.generate') }}" x-ref="form" class="space-y-6">
                @csrf

                {{-- Step 1: Select Template --}}
                <div x-show="currentStep === 0" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-100 dark:border-secondary-800 shadow-sm p-8">
                        <div class="flex items-start gap-4 mb-8">
                            <x-ui.icon icon="layout-template" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-secondary-900 dark:text-white mb-2">
                                    {{ trans('common.select_template_voucher') }}
                                </h3>
                                <p class="text-secondary-500 dark:text-secondary-400">
                                    {{ trans('common.select_template_description') }}
                                </p>
                            </div>
                        </div>

                        {{-- Template Selection Grid --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($voucherTemplates as $template)
                                <button
                                    type="button"
                                    @click="selectTemplate({{ json_encode($template) }})"
                                    class="relative p-6 rounded-xl border-2 transition-colors duration-200 text-left"
                                    :class="selectedTemplate && selectedTemplate.id === '{{ $template->id }}' 
                                        ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 shadow-lg' 
                                        : 'border-secondary-200 dark:border-secondary-700 hover:border-primary-300'"
                                >
                                    {{-- Selected Checkmark --}}
                                    <div 
                                        class="absolute top-4 right-4 w-6 h-6 rounded-full flex items-center justify-center transition-all"
                                        :class="selectedTemplate && selectedTemplate.id === '{{ $template->id }}' 
                                            ? 'bg-primary-500 text-white scale-100' 
                                            : 'bg-secondary-200 dark:bg-secondary-700 scale-0'"
                                    >
                                        <x-ui.icon icon="check" class="w-4 h-4" />
                                    </div>

                                    {{-- Voucher Details --}}
                                    <div class="mb-4">
                                        <h4 class="font-semibold text-secondary-900 dark:text-white mb-1 pr-8">
                                            {{ Str::limit($template->name, 30) }}
                                        </h4>
                                        <p class="text-xs text-secondary-500 dark:text-secondary-400">
                                            {{ $template->code }}
                                        </p>
                                    </div>

                                    {{-- Reward Badge --}}
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-primary-100 dark:bg-primary-900/30 mb-4">
                                        <x-ui.icon icon="gift" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                                        <span class="text-sm font-semibold text-primary-700 dark:text-primary-300">
                                            {{ $template->formatted_value }}
                                        </span>
                                    </div>

                                    {{-- Metadata --}}
                                    <div class="space-y-2 text-xs text-secondary-600 dark:text-secondary-400">
                                        @if($template->club)
                                            <div class="flex items-center gap-2">
                                                <x-ui.icon icon="building" class="w-3.5 h-3.5" />
                                                <span>{{ Str::limit($template->club->name, 25) }}</span>
                                            </div>
                                        @endif
                                        @if($template->valid_until)
                                            <div class="flex items-center gap-2">
                                                <x-ui.icon icon="calendar-x" class="w-3.5 h-3.5" />
                                                <span>{{ trans('common.expires') }}: <span class="format-date" data-date="{{ $template->valid_until }}">{{ $template->valid_until->format('M d, Y') }}</span></span>
                                            </div>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>

                        {{-- Hidden input to store selected template ID --}}
                        <input type="hidden" name="template_id" :value="selectedTemplate ? selectedTemplate.id : ''" />

                        {{-- Validation Error --}}
                        <p x-show="validationErrors.template" x-cloak class="mt-4 text-sm text-red-600 dark:text-red-400 flex items-center gap-1.5">
                            <x-ui.icon icon="alert-circle" class="w-4 h-4" />
                            {{ trans('common.please_select_template') }}
                        </p>
                    </div>
                </div>

                {{-- Step 2: Batch Configuration --}}
                <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-100 dark:border-secondary-800 shadow-sm p-8">
                        <div class="flex items-start gap-4 mb-8">
                            <x-ui.icon icon="settings" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-secondary-900 dark:text-white mb-2">
                                    {{ trans('common.batch_configuration') }}
                                </h3>
                                <p class="text-secondary-500 dark:text-secondary-400">
                                    {{ trans('common.batch_configuration_description') }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            {{-- Club Selection (Optional Override) --}}
                            <div :data-validation-error="validationErrors.club">
                                <x-forms.select
                                    name="club_id"
                                    :label="trans('common.club_optional_override')"
                                    :placeholder="trans('common.use_template_club')"
                                    :options="$clubs->pluck('name', 'id')->toArray()"
                                    :required="false"
                                    :text="trans('common.club_override_helper')"
                                    x-model="formData.club_id"
                                />
                                <p x-show="validationErrors.club" x-cloak class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1.5">
                                    <x-ui.icon icon="alert-circle" class="w-4 h-4" />
                                    {{ trans('common.please_select_club') }}
                                </p>
                            </div>

                            {{-- Batch Name --}}
                            <div :data-validation-error="validationErrors.batch_name">
                                <x-forms.input
                                    name="batch_name"
                                    :label="trans('common.batch_name')"
                                    icon="tag"
                                    placeholder="Summer Sale 2025"
                                    :text="trans('common.batch_name_helper')"
                                    :required="true"
                                    x-model="formData.batch_name"
                                    @input="validationErrors.batch_name = false"
                                    ::class="validationErrors.batch_name ? 'ring-2 ring-red-500 border-red-500' : ''"
                                />
                                <p x-show="validationErrors.batch_name" x-cloak class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1.5">
                                    <x-ui.icon icon="alert-circle" class="w-4 h-4" />
                                    {{ trans('common.batch_name_required') }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Quantity --}}
                                <div :data-validation-error="validationErrors.quantity">
                                    <x-forms.input
                                        name="quantity"
                                        type="number"
                                        :label="trans('common.quantity_codes')"
                                        icon="hash"
                                        placeholder="100"
                                        :text="trans('common.max_10000_codes')"
                                        :required="true"
                                        min="1"
                                        max="10000"
                                        x-model="formData.quantity"
                                        @input="validationErrors.quantity = false"
                                        ::class="validationErrors.quantity ? 'ring-2 ring-red-500 border-red-500' : ''"
                                    />
                                    <p x-show="validationErrors.quantity" x-cloak class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1.5">
                                        <x-ui.icon icon="alert-circle" class="w-4 h-4" />
                                        {{ trans('common.quantity_must_be_between_1_10000') }}
                                    </p>
                                </div>

                                {{-- Code Prefix --}}
                                <x-forms.input
                                    name="code_prefix"
                                    :label="trans('common.code_prefix_optional')"
                                    icon="text-cursor-input"
                                    placeholder="SUMMER"
                                    :text="trans('common.code_prefix_helper')"
                                    x-model="formData.code_prefix"
                                />
                            </div>

                            {{-- Code Preview --}}
                            <div class="bg-gradient-to-r from-primary-50 to-primary-50 dark:from-primary-900/20 dark:to-primary-900/20 border border-primary-200 dark:border-primary-700 rounded-xl p-6">
                                <div class="flex items-center gap-3 mb-3">
                                    <x-ui.icon icon="eye" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                                    <h4 class="font-semibold text-primary-900 dark:text-primary-100">{{ trans('common.code_preview') }}</h4>
                                </div>
                                <div class="font-mono text-2xl font-bold text-primary-700 dark:text-primary-300 text-center" x-text="codePreview"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 3: Review & Generate --}}
                <div x-show="currentStep === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-100 dark:border-secondary-800 shadow-sm p-8">
                        <div class="flex items-start gap-4 mb-8">
                            <x-ui.icon icon="file-check" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-secondary-900 dark:text-white mb-2">
                                    {{ trans('common.review_and_generate') }}
                                </h3>
                                <p class="text-secondary-500 dark:text-secondary-400">
                                    {{ trans('common.review_batch_details') }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            {{-- Template Details --}}
                            <div class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-700 rounded-xl p-6">
                                <h4 class="text-lg font-semibold text-primary-900 dark:text-primary-100 mb-4 flex items-center gap-2">
                                    <x-ui.icon icon="layout-template" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                                    {{ trans('common.template_settings') }}
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ trans('common.template_name') }}:</span>
                                            <span class="text-sm font-semibold text-secondary-900 dark:text-white" x-text="selectedTemplate?.name || '—'"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ trans('common.discount_type') }}:</span>
                                            <span class="text-sm font-semibold text-secondary-900 dark:text-white capitalize" x-text="selectedTemplate?.type || '—'"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ trans('common.discount_value') }}:</span>
                                            <span class="text-sm font-semibold text-secondary-900 dark:text-white" x-text="selectedTemplate?.formatted_value || '—'"></span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ trans('common.club') }}:</span>
                                            <span class="text-sm font-semibold text-secondary-900 dark:text-white" x-text="selectedTemplate?.club?.name || '{{ trans('common.all_clubs') }}'"></span>
                                        </div>
                                        <div class="flex justify-between" x-show="selectedTemplate?.min_purchase_amount">
                                            <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ trans('common.min_purchase') }}:</span>
                                            <span class="text-sm font-semibold text-secondary-900 dark:text-white" x-text="selectedTemplate?.min_purchase_amount ? '$' + (selectedTemplate.min_purchase_amount / 100).toFixed(2) : '—'"></span>
                                        </div>
                                        <div class="flex justify-between" x-show="selectedTemplate?.max_uses_per_member">
                                            <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ trans('common.uses_per_customer') }}:</span>
                                            <span class="text-sm font-semibold text-secondary-900 dark:text-white" x-text="selectedTemplate?.max_uses_per_member || '{{ trans('common.unlimited') }}'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Batch Configuration Summary --}}
                            <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 rounded-xl p-6">
                                <h4 class="text-lg font-semibold text-emerald-900 dark:text-emerald-100 mb-4 flex items-center gap-2">
                                    <x-ui.icon icon="layers" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                                    {{ trans('common.batch_details') }}
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ trans('common.batch_name') }}:</span>
                                            <span class="text-sm font-semibold text-secondary-900 dark:text-white" x-text="formData.batch_name || '—'"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ trans('common.codes_to_generate') }}:</span>
                                            <span class="text-sm font-semibold text-secondary-900 dark:text-white" x-text="formData.quantity || '0'"></span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ trans('common.code_format') }}:</span>
                                            <span class="text-sm font-semibold text-secondary-900 dark:text-white font-mono" x-text="codePreview"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ trans('common.potential_reach') }}:</span>
                                            <span class="text-sm font-semibold text-secondary-900 dark:text-white" x-text="(formData.quantity || 0) + ' {{ trans('common.customers') }}'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- What's Ignored Alert --}}
                            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl p-6">
                                <div class="flex gap-3">
                                    <x-ui.icon icon="info" class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                                    <div>
                                        <h4 class="font-semibold text-amber-900 dark:text-amber-100 mb-2">
                                            {{ trans('common.batch_generation_note') }}
                                        </h4>
                                        <p class="text-sm text-amber-700 dark:text-amber-300">
                                            {{ trans('common.batch_generation_note_description') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Navigation Buttons — Clean, purposeful actions --}}
                <div class="flex items-center justify-between gap-4 pt-2">
                    <button
                        type="button"
                        @click="previousStep()"
                        x-show="currentStep > 0"
                        class="inline-flex items-center gap-2 px-5 py-3 text-sm font-medium 
                            text-secondary-700 dark:text-secondary-300 
                            bg-secondary-100 dark:bg-secondary-800 
                            hover:bg-secondary-200 dark:hover:bg-secondary-700 
                            rounded-xl transition-all duration-200"
                        x-cloak
                    >
                        <x-ui.icon icon="arrow-left" class="w-4 h-4" />
                        {{ trans('common.previous') }}
                    </button>

                    <div class="flex-1"></div>

                    <button
                        type="button"
                        @click="nextStep()"
                        x-show="currentStep < steps.length - 1"
                        class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium 
                            text-white bg-primary-600 hover:bg-primary-500
                            rounded-xl transition-colors duration-200"
                        x-cloak
                    >
                        {{ trans('common.continue') }}
                        <x-ui.icon icon="arrow-right" class="w-4 h-4" />
                    </button>

                    <button
                        type="submit"
                        x-show="currentStep === steps.length - 1"
                        x-data="{ submitting: false }"
                        @submit.window="submitting = true"
                        :disabled="submitting"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium 
                            text-white bg-emerald-600 hover:bg-emerald-500
                            rounded-xl transition-colors duration-200
                            disabled:opacity-75 disabled:cursor-not-allowed"
                        x-cloak
                    >
                        <template x-if="!submitting">
                            <span class="flex items-center gap-2">
                                <x-ui.icon icon="sparkles" class="w-4 h-4" />
                                <span>{{ trans('common.generate_codes') }}</span>
                            </span>
                        </template>
                        <template x-if="submitting">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ trans('common.generating') }}...</span>
                            </span>
                        </template>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>

@push('scripts')
<script>
function batchWizard(templates) {
    return {
        currentStep: 0,
        selectedTemplate: null,
        validationErrors: {
            template: false,
            club: false,
            batch_name: false,
            quantity: false
        },
        steps: [
            { title: '{{ trans("common.select_template") }}' },
            { title: '{{ trans("common.configuration") }}' },
            { title: '{{ trans("common.review") }}' }
        ],
        formData: {
            club_id: '',
            batch_name: '',
            quantity: 100,
            code_prefix: ''
        },
        
        selectTemplate(template) {
            this.selectedTemplate = template;
            this.validationErrors.template = false;

            // Pre-fill batch name based on template
            if (!this.formData.batch_name) {
                const date = new Date().toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                this.formData.batch_name = `${template.name} - ${date}`;
            }
        },
        
        get codePreview() {
            const prefix = this.formData.code_prefix || 'VOUCHER';
            return prefix.toUpperCase() + '-ABCD1234';
        },
        
        validateStep(stepIndex) {
            // Reset validation errors
            Object.keys(this.validationErrors).forEach(key => this.validationErrors[key] = false);
            
            if (stepIndex === 0) {
                // Step 1: Template Selection
                if (!this.selectedTemplate) {
                    this.validationErrors.template = true;
                    return false;
                }
                return true;
            } else if (stepIndex === 1) {
                // Step 2: Batch Configuration
                let isValid = true;

                // If the template is not tied to a club, a club override is required.
                if (this.selectedTemplate && !this.selectedTemplate.club_id && !this.formData.club_id) {
                    this.validationErrors.club = true;
                    isValid = false;
                }
                
                if (!this.formData.batch_name || !this.formData.batch_name.trim()) {
                    this.validationErrors.batch_name = true;
                    isValid = false;
                }
                if (!this.formData.quantity || this.formData.quantity < 1 || this.formData.quantity > 10000) {
                    this.validationErrors.quantity = true;
                    isValid = false;
                }
                
                return isValid;
            }
            
            return true;
        },

        nextStep() {
            // Validate current step
            if (!this.validateStep(this.currentStep)) {
                // Scroll to first error
                setTimeout(() => {
                    const firstError = document.querySelector('[data-validation-error="true"]');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 100);
                return;
            }

            // All validation passed, advance to next step
            if (this.currentStep < this.steps.length - 1) {
                this.currentStep++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },
        
        previousStep() {
            if (this.currentStep > 0) {
                this.currentStep--;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },
        
        goToStep(index) {
            // Allow clicking on completed steps or current step
            if (index <= this.currentStep) {
                this.currentStep = index;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
    }
}
</script>
@endpush
@endsection
