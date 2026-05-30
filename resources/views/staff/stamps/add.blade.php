{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Staff Add Stamp Page

  Design: Follows canonical staff action page pattern (§6.3 design system).
  No ambient effects, no custom keyframes.
--}}

@extends('staff.layouts.default')

@section('page_title', ($card?->name ?? trans('common.add_stamp')) . config('default.page_title_delimiter') . trans('common.add_stamp') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-lg mx-auto px-4 py-8 md:py-14">
    @if($member && $card)
        @php
            $stampIcon = $card->stamp_icon ?? '☕';
            $isEmoji = preg_match('/[^\x00-\x7F]/', $stampIcon);
        @endphp
        
        {{-- Header --}}
        <x-ui.page-header
            icon="stamp"
            :title="trans('common.add_stamp')"
            :description="trans('common.award_stamp_for_member_purchase')"
            compact
        />

        {{-- Member Card --}}
        <div class="mt-8 mb-6">
            <x-member.member-card :member="$member" :club="$card->club" />
        </div>

        {{-- Messages --}}
        <div class="mb-6">
            <x-forms.messages />
        </div>

        {{-- Stamp Card Progress --}}
        @if($enrollment)
            <div class="mb-6">
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-secondary-900 dark:text-white">{{ trans('common.current_progress') }}</h3>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $enrollment->current_stamps }}</span>
                            <span class="text-secondary-500 dark:text-secondary-400">/</span>
                            <span class="text-xl text-secondary-600 dark:text-secondary-400">{{ $card->stamps_required }}</span>
                        </div>
                    </div>
                    
                    {{-- Progress Bar --}}
                    <div class="w-full bg-secondary-200 dark:bg-secondary-700 rounded-full h-2.5 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-emerald-500 to-green-500 rounded-full transition-all duration-500"
                             style="width: {{ min(($enrollment->current_stamps / $card->stamps_required) * 100, 100) }}%"></div>
                    </div>
                    
                    @if($enrollment->current_stamps >= $card->stamps_required)
                        <div class="mt-4 flex items-center gap-2 text-emerald-600 dark:text-emerald-400">
                            <x-ui.icon icon="check-circle" class="w-5 h-5" />
                            <span class="font-semibold">{{ trans('common.reward_ready') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Main Form --}}
        <x-forms.form-open action="{{ route('staff.stamps.add') }}" enctype="multipart/form-data" method="POST" />
            <input type="hidden" name="member_identifier" value="{{ $member->unique_identifier }}">
            <input type="hidden" name="stamp_card_id" value="{{ $card->id }}">
            
            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                
                {{-- Form Header --}}
                <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
                    <div class="flex items-center gap-3">
                        <x-ui.icon icon="plus" class="w-5 h-5 text-secondary-400" />
                        <div>
                            <h3 class="font-semibold text-secondary-900 dark:text-white">{{ trans('common.purchase_details') }}</h3>
                            <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ trans('common.record_transaction_details') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Form Inputs --}}
                <div class="p-6 space-y-5">
                    {{-- Purchase Amount --}}
                    <div>
                        <label class="block text-sm font-semibold text-secondary-700 dark:text-secondary-300 mb-2">
                            {{ trans('common.purchase_amount') }}
                            @if($card->min_purchase_amount && $card->min_purchase_amount > 0)
                                <span class="text-rose-500 dark:text-rose-400">*</span>
                            @else
                                <span class="text-secondary-400 dark:text-secondary-500 font-normal">({{ trans('common.optional') }})</span>
                            @endif
                        </label>
                        <x-forms.input
                            name="purchase_amount"
                            id="purchase_amount_input"
                            value="{{ old('purchase_amount') }}"
                            :label="null"
                            type="number"
                            inputmode="decimal"
                            :suffix="$card->currency"
                            :min="0"
                            :step="0.01"
                            :placeholder="0.00"
                        />
                        <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">
                            {{ trans('common.internal_only_not_visible_to_customers') }}
                            @if($card->min_purchase_amount && $card->min_purchase_amount > 0)
                                {{ ' ' . trans('common.minimum_purchase_of_amount_required_to_earn_stamp', ['amount' => moneyFormat((float) $card->min_purchase_amount, $card->currency)]) }}
                            @endif
                        </p>
                        
                        @if($card->min_purchase_amount && $card->min_purchase_amount > 0)
                            <div id="purchase_amount_warning" class="hidden mt-2 p-3 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800">
                                <div class="flex items-start gap-2">
                                    <x-ui.icon icon="alert-circle" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                                    <p class="text-sm text-amber-700 dark:text-amber-300">
                                        {{ trans('common.minimum_purchase_required', ['amount' => moneyFormat((float) $card->min_purchase_amount, $card->currency)]) }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Stamps to Award --}}
                    @if($card->max_stamps_per_transaction && $card->max_stamps_per_transaction > 1)
                        <div>
                            <label class="block text-sm font-semibold text-secondary-700 dark:text-secondary-300 mb-2">
                                {{ trans('common.stamps_to_award') }} <span class="text-rose-500 dark:text-rose-400">*</span>
                            </label>
                            @php
                                $stampOptions = [];
                                for ($i = 1; $i <= $card->max_stamps_per_transaction; $i++) {
                                    $stampOptions[$i] = $i . ' ' . ($i === 1 ? trans('common.stamp') : trans('common.stamps'));
                                }
                            @endphp
                            <x-forms.select
                                name="stamps"
                                :value="old('stamps', $card->stamps_per_purchase)"
                                :options="$stampOptions"
                                :label="null"
                                :text="trans('common.select_stamps_to_award_for_this_purchase', ['max' => $card->max_stamps_per_transaction])"
                            />
                        </div>
                    @else
                        <input type="hidden" name="stamps" value="{{ $card->stamps_per_purchase }}">
                    @endif

                    {{-- Photo Upload (Optional) --}}
                    <div>
                        <label class="block text-sm font-semibold text-secondary-700 dark:text-secondary-300 mb-2">
                            {{ trans('common.receipt_photo') }} <span class="text-secondary-400 dark:text-secondary-500 font-normal">({{ trans('common.optional') }})</span>
                        </label>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400 mb-3">
                            {{ trans('common.receipt_photo_help') }}
                        </p>
                        <x-forms.image
                            type="image"
                            capture="environment"
                            icon="camera"
                            name="image"
                            :placeholder="trans('common.add_photo_of_receipt')"
                            accept="image/*"
                        />
                    </div>

                    {{-- Notes (Optional) --}}
                    <div class="pb-2">
                        <label class="block text-sm font-semibold text-secondary-700 dark:text-secondary-300 mb-2">
                            {{ trans('common.notes') }} <span class="text-secondary-400 dark:text-secondary-500 font-normal">({{ trans('common.optional') }})</span>
                        </label>
                        <textarea 
                            name="note"
                            rows="2"
                            placeholder="{{ trans('common.internal_only_not_visible_to_customers') }}"
                            class="w-full bg-white dark:bg-secondary-800 border text-secondary-900 dark:text-white text-sm rounded-xl block px-4 py-3 resize-none transition-all duration-200 placeholder:text-secondary-400 dark:placeholder:text-secondary-500 focus:outline-none focus:ring-2 focus:ring-offset-0 border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 focus:border-primary-500 focus:ring-primary-500/20">{{ old('note') }}</textarea>
                    </div>
                </div>

                {{-- Eligibility Warning --}}
                @if(!$eligibility['eligible'] && !str_contains(strtolower($eligibility['reason'] ?? ''), 'purchase') && !str_contains(strtolower($eligibility['reason'] ?? ''), 'amount'))
                    <div class="px-6 pb-6">
                        <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800">
                            <div class="flex gap-3">
                                <x-ui.icon icon="alert-circle" class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                                <div class="flex-1">
                                    <p class="font-semibold text-amber-900 dark:text-amber-100 mb-1">{{ trans('common.not_eligible') }}</p>
                                    <p class="text-sm text-amber-700 dark:text-amber-300">{{ $eligibility['reason'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Submit Button --}}
                <div class="px-6 pb-6 pt-0">
                    <button type="submit" 
                            id="submit_stamp_button"
                            x-data="{ submitting: false }"
                            @submit.window="submitting = true"
                            :disabled="submitting"
                            class="w-full flex items-center justify-center gap-2.5 px-6 py-3.5
                                   text-sm font-semibold text-white
                                   bg-primary-600 hover:bg-primary-500
                                   rounded-xl shadow-sm hover:shadow-md
                                   focus:outline-none focus:ring-2 focus:ring-primary-500/20
                                   transition-all duration-200 active:scale-[0.98]">
                        <template x-if="!submitting">
                            <span class="flex items-center gap-2">
                                @if($isEmoji)
                                    <span class="text-xl">{{ $stampIcon }}</span>
                                @else
                                    <x-ui.icon :icon="$stampIcon" class="w-5 h-5" />
                                @endif
                                <span>{{ trans('common.add_stamp') }}</span>
                            </span>
                        </template>
                        <template x-if="submitting">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ trans('common.adding') }}...</span>
                            </span>
                        </template>
                    </button>
                </div>
                
                {{-- Stamp Card Rules --}}
                <div class="px-6 pb-6 pt-4">
                    <div class="flex items-start gap-2.5">
                        <x-ui.icon icon="list-checks" class="w-4 h-4 text-secondary-400 shrink-0 mt-0.5" />
                        <div class="flex-1">
                            <h5 class="font-medium text-secondary-600 dark:text-secondary-400 text-xs mb-3">{{ trans('common.stamp_card_rules') }}</h5>
                            
                            <div class="space-y-2.5 text-xs">
                                <div class="flex items-start justify-between py-1">
                                    <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.stamps_required_for_reward') }}</span>
                                    <span class="font-medium text-secondary-900 dark:text-white">{{ $card->stamps_required }}</span>
                                </div>

                                <div class="flex items-start justify-between py-1">
                                    <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.stamps_per_purchase') }}</span>
                                    <span class="font-medium text-secondary-900 dark:text-white">{{ $card->stamps_per_purchase }}</span>
                                </div>

                                <div class="flex items-start justify-between py-1">
                                    <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.physical_reward') }}</span>
                                    <span class="font-medium text-secondary-900 dark:text-white">{{ $card->reward_title }}</span>
                                </div>

                                @if($card->reward_points && $card->reward_points > 0)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.bonus_points') }}</span>
                                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                                            +<span class="format-number">{{ $card->reward_points }}</span>
                                            @if($card->rewardCard)
                                                <span class="text-xs text-secondary-500 dark:text-secondary-400">→ {{ $card->rewardCard->name }}</span>
                                            @endif
                                        </span>
                                    </div>
                                @endif

                                @if($card->min_purchase_amount && $card->min_purchase_amount > 0)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.min_purchase') }}</span>
                                        <span class="font-medium text-secondary-900 dark:text-white">{{ moneyFormat((float) $card->min_purchase_amount, $card->currency) }}</span>
                                    </div>
                                @endif

                                @if($card->max_stamps_per_transaction && $card->max_stamps_per_transaction > 1)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.max_per_transaction') }}</span>
                                        <span class="font-medium text-secondary-900 dark:text-white">{{ $card->max_stamps_per_transaction }}</span>
                                    </div>
                                @endif

                                @if($card->max_stamps_per_day)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.max_per_day') }}</span>
                                        <span class="font-medium text-secondary-900 dark:text-white">{{ $card->max_stamps_per_day }}</span>
                                    </div>
                                @endif

                                @if($card->stamps_expire_days)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.stamps_expire_after') }}</span>
                                        <span class="font-medium text-secondary-900 dark:text-white">{{ $card->stamps_expire_days }} {{ trans('common.days') }}</span>
                                    </div>
                                @endif

                                @if($card->valid_from || $card->valid_until)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.valid_period') }}</span>
                                        <span class="font-medium text-secondary-900 dark:text-white text-right format-date-range"
                                              data-date-from="{{ $card->valid_from }}"
                                              data-date-to="{{ $card->valid_until }}"
                                              data-prefix-to="{{ trans('common.until') }}">
                                            @if($card->valid_from && $card->valid_until)
                                                {{ $card->valid_from->format('M d') }} - {{ $card->valid_until->format('M d, Y') }}
                                            @elseif($card->valid_until)
                                                {{ trans('common.until') }} {{ $card->valid_until->format('M d, Y') }}
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <x-forms.form-close />

        {{-- Cancel Link --}}
        <div class="mt-6 text-center">
            <a href="{{ route('staff.stamp.transactions', ['member_identifier' => $member->unique_identifier, 'stamp_card_id' => $card->id]) }}" 
               class="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white transition-colors">
                <x-ui.icon icon="arrow-left" class="w-4 h-4" />
                <span>{{ trans('common.cancel_and_view_member_history') }}</span>
            </a>
        </div>
    @else
        {{-- Error State --}}
        <div class="text-center py-12">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-xl bg-rose-100 dark:bg-rose-900/50 mb-4">
                <x-ui.icon icon="x-circle" class="w-8 h-8 text-rose-600 dark:text-rose-400" />
            </div>
            <h2 class="text-xl font-semibold text-secondary-900 dark:text-white mb-2">{{ trans('common.error') }}</h2>
            <p class="text-secondary-600 dark:text-secondary-400">{{ trans('common.member_or_card_not_found') }}</p>
            <a href="{{ route('staff.qr.scanner') }}" class="inline-block mt-6 px-6 py-3 rounded-xl font-semibold text-white bg-primary-600 hover:bg-primary-500 transition-colors">
                {{ trans('common.back_to_scanner') }}
            </a>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const purchaseAmountInput = document.getElementById('purchase_amount_input');
    const warningMessage = document.getElementById('purchase_amount_warning');
    const minPurchaseAmount = parseFloat({{ $card->min_purchase_amount ?? 0 }});
    const requiresMinimum = {{ $card->min_purchase_amount && $card->min_purchase_amount > 0 ? 'true' : 'false' }};
    
    if (purchaseAmountInput && warningMessage && requiresMinimum) {
        function checkPurchaseAmount() {
            const inputValue = purchaseAmountInput.value.trim();
            const value = parseFloat(inputValue);
            const meetsMinimum = !isNaN(value) && value >= minPurchaseAmount;
            
            if (warningMessage) {
                if (inputValue !== '' && !meetsMinimum) {
                    warningMessage.classList.remove('hidden');
                } else {
                    warningMessage.classList.add('hidden');
                }
            }
        }
        
        purchaseAmountInput.addEventListener('input', checkPurchaseAmount);
        purchaseAmountInput.addEventListener('change', checkPurchaseAmount);
        purchaseAmountInput.addEventListener('blur', checkPurchaseAmount);
    }
});
</script>

@stop
