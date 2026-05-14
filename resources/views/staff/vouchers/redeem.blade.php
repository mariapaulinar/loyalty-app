{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Staff Voucher Redemption Interface
Tablet-optimized with large touch targets.
NO page redirects on validation errors - inline display only.

Design: Follows canonical staff action page pattern (§6.3 design system).
No ambient effects, no custom keyframes.
--}}

@extends('staff.layouts.default')

@section('page_title', __('common.redeem_voucher') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-lg mx-auto px-4 py-8 md:py-14"
     x-data="voucherRedemption"
     x-init="init()">

    {{-- Header --}}
    <x-ui.page-header
        icon="ticket"
        :title="__('common.redeem_voucher')"
        :description="__('common.apply_discount_for_member')"
        compact
    />

    {{-- Member Identification (if not pre-selected) --}}
    @if(!isset($member))
        <div class="mt-8 mb-6">
            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-6">
                <h3 class="font-semibold text-secondary-900 dark:text-white mb-4 flex items-center gap-2">
                    <x-ui.icon icon="user" class="w-5 h-5 text-secondary-400" />
                    {{ __('common.identify_member') }}
                </h3>
                
                {{-- QR Scanner Button (Large touch target) --}}
                <button type="button" 
                        @click="scanMemberQr()"
                        class="w-full mb-4 flex items-center gap-4 px-5 py-6 
                               bg-primary-600 hover:bg-primary-500 
                               text-white font-semibold rounded-2xl 
                               shadow-sm hover:shadow-md 
                               transition-all duration-200 active:scale-[0.98]">
                    <span class="shrink-0 w-14 h-14 rounded-xl bg-white/20 flex items-center justify-center">
                        <x-ui.icon icon="qr-code" class="w-7 h-7" />
                    </span>
                    <span class="flex-1 text-left text-lg">{{ __('common.scan_member_qr') }}</span>
                </button>
                
                <div class="relative mb-4">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-secondary-300 dark:border-secondary-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white dark:bg-secondary-900 text-secondary-500 dark:text-secondary-400">{{ __('common.or') }}</span>
                    </div>
                </div>
                
                {{-- Manual Entry --}}
                <input type="text" 
                       x-model="memberIdentifier"
                       @input="memberError = ''"
                       placeholder="{{ __('common.enter_member_id_or_email') }}"
                       class="w-full px-4 py-4 text-lg rounded-xl border-2 border-secondary-200 dark:border-secondary-800 bg-white dark:bg-secondary-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all">
                
                <button type="button"
                        @click="lookupMember()"
                        :disabled="!memberIdentifier"
                        :class="memberIdentifier ? 'bg-primary-600 hover:bg-primary-500' : 'bg-secondary-300 dark:bg-secondary-700 cursor-not-allowed'"
                        class="w-full mt-4 px-6 py-4 text-white font-semibold rounded-xl shadow-sm transition-all duration-200">
                    {{ __('common.find_member') }}
                </button>
                
                <div x-show="memberError" x-cloak class="mt-3 text-sm text-rose-600 dark:text-rose-400" x-text="memberError"></div>
            </div>
        </div>
    @endif

    {{-- Member Card (when identified) --}}
    @if(isset($member))
        <div class="mt-8 mb-6">
            <x-member.member-card :member="$member" :club="$club" />
        </div>
    @else
        <div x-show="member" x-cloak class="mt-8 mb-6">
            <div class="bg-white/80 dark:bg-secondary-800/80 backdrop-blur-xl rounded-2xl border border-secondary-200/50 dark:border-secondary-800/50 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-primary-600 flex items-center justify-center text-white font-bold text-xl">
                        <span x-text="member?.name?.charAt(0) || 'M'"></span>
                    </div>
                    <div>
                        <p class="font-semibold text-secondary-900 dark:text-white" x-text="member?.name || ''"></p>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400" x-text="member?.email || ''"></p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Main Redemption Form --}}
    <div>
        <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
            
            {{-- Error Banner --}}
            <div x-ref="errorBanner"
                 x-show="errorMessage" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-cloak
                 class="bg-rose-50 dark:bg-rose-950/30 border-b border-rose-200 dark:border-rose-900">
                <div class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="shrink-0 w-10 h-10 rounded-full bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center">
                            <x-ui.icon icon="alert-circle" class="w-5 h-5 text-rose-600 dark:text-rose-400" />
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-rose-800 dark:text-rose-300" x-text="errorMessage"></p>
                        </div>
                        <button @click="errorMessage = ''" 
                                class="shrink-0 p-1 hover:bg-rose-100 dark:hover:bg-rose-900/50 rounded-lg transition-colors">
                            <x-ui.icon icon="x" class="w-5 h-5 text-rose-600 dark:text-rose-400" />
                        </button>
                    </div>
                </div>
            </div>

            {{-- Form Header --}}
            <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
                <div class="flex items-center gap-3">
                    <x-ui.icon icon="ticket" class="w-5 h-5 text-secondary-400" />
                    <div>
                        <h3 class="font-semibold text-secondary-900 dark:text-white">{{ __('common.voucher_redemption') }}</h3>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ __('common.validate_and_apply_discount') }}</p>
                    </div>
                </div>
            </div>

            {{-- Form Inputs --}}
            <div class="p-6 space-y-5">
                {{-- Voucher Code Input (LARGE touch target) --}}
                <div>
                    <label class="block text-sm font-semibold text-secondary-700 dark:text-secondary-300 mb-2">
                        {{ __('common.voucher_code') }}
                        @if(isset($voucher))
                            <span class="ml-2 text-xs text-emerald-600 dark:text-emerald-400 font-normal">✓ {{ __('common.pre_filled') }}</span>
                        @endif
                    </label>
                    <input type="text" 
                           x-model="voucherCode"
                           @input="voucherCode = voucherCode.toUpperCase(); errorMessage = '';"
                           placeholder="{{ __('common.enter_code_caps') }}"
                           class="w-full px-5 py-5 text-xl font-mono font-bold tracking-widest text-center uppercase rounded-2xl border-2 transition-all placeholder:text-secondary-400 dark:placeholder:text-secondary-600"
                           :class="voucherCode ? 'border-emerald-500 dark:border-emerald-600 bg-emerald-50 dark:bg-emerald-950 text-emerald-900 dark:text-emerald-100' : 'border-secondary-200 dark:border-secondary-800 bg-white dark:bg-secondary-900 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/20'"
                           maxlength="32"
                           @if(isset($voucher)) readonly @endif>
                </div>

                {{-- Purchase Amount (Optional) --}}
                <div>
                    <label class="block text-sm font-semibold text-secondary-700 dark:text-secondary-300 mb-2">
                        {{ __('common.purchase_amount') }} <span class="text-secondary-400 dark:text-secondary-500 font-normal">({{ __('common.optional') }})</span>
                    </label>
                    <div class="relative">
                        <input type="number"
                               x-model="orderAmount"
                               @input="errorMessage = ''; calculateDiscount()"
                               inputmode="decimal"
                               min="0"
                               step="0.01"
                               placeholder="0.00"
                               class="w-full bg-white dark:bg-secondary-800 border text-secondary-900 dark:text-white text-sm rounded-xl block px-4 py-3 pr-16 transition-all duration-200 placeholder:text-secondary-400 dark:placeholder:text-secondary-500 focus:outline-none focus:ring-2 focus:ring-offset-0"
                               :class="orderAmount && errorMessage ? 'border-rose-500 dark:border-rose-600 bg-rose-50 dark:bg-rose-950/30 focus:border-rose-500 focus:ring-rose-500/20' : 'border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 focus:border-primary-500 focus:ring-primary-500/20'">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <span class="text-sm text-secondary-500 dark:text-secondary-400">{{ $club->currency ?? 'USD' }}</span>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">{{ __('common.internal_only_not_visible_to_customers') }}</p>
                    
                    {{-- Real-time Discount Calculation (for percentage vouchers) --}}
                    @if(isset($voucher) && $voucher->type === 'percentage')
                        <div x-show="orderAmount && parseFloat(orderAmount) > 0 && !errorMessage" 
                             x-transition
                             x-cloak
                             class="mt-3 p-3 rounded-lg bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/50">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-emerald-700 dark:text-emerald-300 font-medium">{{ __('common.calculated_discount') }}:</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400" x-text="calculatedDiscountDisplay"></span>
                            </div>
                            <template x-if="isCapped">
                                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">{{ __('common.discount_capped') }}</p>
                            </template>
                            <template x-if="orderAmount && parseFloat(orderAmount) > 0 && calculatedDiscount > 0">
                                <div class="mt-2 pt-2 border-t border-emerald-200 dark:border-emerald-900/50 flex items-center justify-between text-sm">
                                    <span class="text-secondary-600 dark:text-secondary-400">{{ __('common.final_amount') }}:</span>
                                    <span class="font-semibold text-secondary-900 dark:text-white" x-text="finalAmountDisplay"></span>
                                </div>
                            </template>
                        </div>
                    @endif
                </div>

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
                        x-model="orderReference"
                        rows="2"
                        placeholder="{{ trans('common.internal_only_not_visible_to_customers') }}"
                        class="w-full bg-white dark:bg-secondary-800 border text-secondary-900 dark:text-white text-sm rounded-xl block px-4 py-3 resize-none transition-all duration-200 placeholder:text-secondary-400 dark:placeholder:text-secondary-500 focus:outline-none focus:ring-2 focus:ring-offset-0 border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 focus:border-primary-500 focus:ring-primary-500/20"></textarea>
                </div>
            </div>

            {{-- Redeem Button --}}
            <div class="px-6 pb-6 pt-0">
                <button type="button"
                        @click="redeemVoucher()"
                        :disabled="!voucherCode || redeeming"
                        :class="voucherCode ? 'bg-primary-600 hover:bg-primary-500 shadow-sm hover:shadow-md' : 'bg-secondary-300 dark:bg-secondary-700 cursor-not-allowed'"
                        class="w-full flex items-center justify-center gap-2.5 px-6 py-3.5
                               text-sm font-semibold text-white
                               rounded-xl
                               focus:outline-none focus:ring-2 focus:ring-primary-500/20
                               transition-all duration-200 active:scale-[0.98]">
                    <template x-if="!redeeming">
                        <span class="flex items-center gap-2">
                            <x-ui.icon icon="ticket" class="w-5 h-5" />
                            <span>{{ __('common.redeem_voucher') }}</span>
                        </span>
                    </template>
                    <template x-if="redeeming">
                        <span class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('common.redeeming') }}
                        </span>
                    </template>
                </button>
            </div>
            
            {{-- Voucher Details (Only if voucher is pre-selected) --}}
            @if(isset($voucher))
                <div class="px-6 pb-6 pt-4">
                    <div class="flex items-start gap-2.5">
                        <x-ui.icon icon="ticket" class="w-4 h-4 text-secondary-400 shrink-0 mt-0.5" />
                        <div class="flex-1">
                            <h5 class="font-medium text-secondary-600 dark:text-secondary-400 text-xs mb-3">{{ __('common.voucher_details') }}</h5>
                            
                            {{-- Voucher Type Badge --}}
                            <div class="mb-4 pb-3 border-b border-secondary-200 dark:border-secondary-800">
                                <div class="inline-flex items-center gap-1.5 px-2 py-1 bg-secondary-50 dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 rounded-md">
                                    @if($voucher->type === 'percentage')
                                        <x-ui.icon icon="percent" class="w-3 h-3 text-secondary-500" />
                                        <span class="font-medium text-xs text-secondary-700 dark:text-secondary-300">{{ __('common.percentage_discount') }}</span>
                                    @elseif($voucher->type === 'fixed_amount')
                                        <x-ui.icon icon="dollar-sign" class="w-3 h-3 text-secondary-500" />
                                        <span class="font-medium text-xs text-secondary-700 dark:text-secondary-300">{{ __('common.fixed_amount_discount') }}</span>
                                    @elseif($voucher->type === 'free_product')
                                        <x-ui.icon icon="gift" class="w-3 h-3 text-secondary-500" />
                                        <span class="font-medium text-xs text-secondary-700 dark:text-secondary-300">{{ __('common.free_product') }}</span>
                                    @elseif($voucher->type === 'free_shipping')
                                        <x-ui.icon icon="truck" class="w-3 h-3 text-secondary-500" />
                                        <span class="font-medium text-xs text-secondary-700 dark:text-secondary-300">{{ __('common.free_shipping') }}</span>
                                    @elseif($voucher->type === 'bonus_points')
                                        <x-ui.icon icon="award" class="w-3 h-3 text-secondary-500" />
                                        <span class="font-medium text-xs text-secondary-700 dark:text-secondary-300">{{ __('common.bonus_points') }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Rules Table --}}
                            <div class="space-y-2.5 text-xs">
                                <div class="flex items-start justify-between py-1">
                                    <span class="text-secondary-500 dark:text-secondary-400">{{ __('common.discount') }}</span>
                                    <span class="font-semibold text-secondary-900 dark:text-white text-right">
                                        @if($voucher->type === 'percentage')
                                            {{ rtrim(rtrim(number_format($voucher->value / 100, 2, '.', ''), '0'), '.') }}%
                                            @if($voucher->max_discount_amount)
                                                <span class="block text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">({{ __('common.max') }} {{ moneyFormat($voucher->max_discount_amount / 100, $voucher->currency ?? 'USD') }})</span>
                                            @endif
                                        @elseif($voucher->type === 'fixed_amount')
                                            {{ moneyFormat($voucher->value / 100, $voucher->currency ?? 'USD') }}
                                        @elseif($voucher->type === 'free_product')
                                            {{ $voucher->free_product_name }}
                                        @elseif($voucher->type === 'bonus_points')
                                            +<span class="format-number">{{ $voucher->points_value }}</span> {{ __('common.points') }}
                                            @if($voucher->rewardCard)
                                                <span class="block text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">→ {{ $voucher->rewardCard->name }}</span>
                                            @endif
                                        @elseif($voucher->type === 'free_shipping')
                                            {{ __('common.free_shipping') }}
                                        @endif
                                    </span>
                                </div>

                                @if($voucher->min_purchase_amount)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ __('common.min_purchase') }}</span>
                                        <span class="font-medium text-secondary-900 dark:text-white">{{ moneyFormat($voucher->min_purchase_amount / 100, $voucher->currency ?? 'USD') }}</span>
                                    </div>
                                @endif

                                @if($voucher->max_uses_total)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ __('common.usage_limit_total') }}</span>
                                        <span class="font-medium text-secondary-900 dark:text-white">
                                            <span class="format-number">{{ $voucher->times_used }}</span> / <span class="format-number">{{ $voucher->max_uses_total }}</span>
                                            @if($voucher->remaining_uses !== null)
                                                <span class="text-xs text-secondary-500 dark:text-secondary-400">(<span class="format-number">{{ $voucher->remaining_uses }}</span> {{ __('common.left') }})</span>
                                            @endif
                                        </span>
                                    </div>
                                @else
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ __('common.total_uses') }}</span>
                                        <span class="font-medium text-secondary-900 dark:text-white"><span class="format-number">{{ $voucher->times_used }}</span> <span class="text-xs text-secondary-500 dark:text-secondary-400">({{ __('common.unlimited') }})</span></span>
                                    </div>
                                @endif

                                @if($voucher->is_single_use)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ __('common.per_member_limit') }}</span>
                                        <span class="font-medium text-amber-600 dark:text-amber-400">{{ __('common.single_use_only') }}</span>
                                    </div>
                                @elseif($voucher->max_uses_per_member)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ __('common.per_member_limit') }}</span>
                                        <span class="font-medium text-secondary-900 dark:text-white format-number">{{ $voucher->max_uses_per_member }}</span>
                                    </div>
                                @endif

                                @if($voucher->valid_from || $voucher->valid_until)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ __('common.validity_period') }}</span>
                                        <span class="font-medium text-secondary-900 dark:text-white text-right format-date-range"
                                              data-date-from="{{ $voucher->valid_from }}"
                                              data-date-to="{{ $voucher->valid_until }}"
                                              data-prefix-from="{{ __('common.from') }}"
                                              data-prefix-to="{{ __('common.until') }}">
                                            @if($voucher->valid_from && $voucher->valid_until)
                                                {{ $voucher->valid_from->format('M d') }} - {{ $voucher->valid_until->format('M d, Y') }}
                                            @elseif($voucher->valid_from)
                                                {{ __('common.from') }} {{ $voucher->valid_from->format('M d, Y') }}
                                            @elseif($voucher->valid_until)
                                                {{ __('common.until') }} {{ $voucher->valid_until->format('M d, Y') }}
                                            @endif
                                        </span>
                                    </div>
                                @endif

                                @if($voucher->first_order_only)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ __('common.restriction') }}</span>
                                        <span class="font-medium text-amber-600 dark:text-amber-400">{{ __('common.first_order_only') }}</span>
                                    </div>
                                @endif

                                @if($voucher->new_members_only)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ __('common.restriction') }}</span>
                                        <span class="font-medium text-amber-600 dark:text-amber-400">
                                            {{ __('common.new_members_only') }}
                                            @if($voucher->new_members_days)
                                                <span class="block text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">({{ $voucher->new_members_days }} {{ __('common.days') }})</span>
                                            @endif
                                        </span>
                                    </div>
                                @endif

                                @if($voucher->stackable)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ __('common.stackable') }}</span>
                                        <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ __('common.yes') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Cancel Link --}}
        @if(isset($member) && isset($voucher))
        <div class="mt-6 text-center">
            <a href="{{ route('staff.voucher.transactions', ['member_identifier' => $member->unique_identifier, 'voucher_id' => $voucher->id]) }}" 
               class="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white transition-colors">
                <x-ui.icon icon="arrow-left" class="w-4 h-4" />
                <span>{{ trans('common.cancel_and_view_member_history') }}</span>
            </a>
        </div>
        @endif
    </div>
</div>

@stop

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('voucherRedemption', () => ({
        // Member data
        memberIdentifier: '{{ $member->unique_identifier ?? "" }}',
        member: {!! isset($member) ? json_encode(['name' => $member->name, 'email' => $member->email, 'id' => $member->id]) : 'null' !!},
        memberError: '',
        
        // Voucher data
        voucherCode: '{{ $voucher->code ?? "" }}',
        orderAmount: '',
        orderReference: '',
        
        @if(isset($voucher))
        // Voucher config (for discount calculation)
        voucherType: '{{ $voucher->type }}',
        voucherPercentage: {{ $voucher->type === 'percentage' ? $voucher->value / 100 : 0 }},
        voucherFixedAmount: {{ $voucher->type === 'fixed_amount' ? $voucher->value : 0 }},
        voucherMaxCap: {{ $voucher->max_discount_amount ?? 'null' }},
        voucherCurrency: '{{ $voucher->currency ?? "USD" }}',
        @endif
        
        // State
        redeeming: false,
        errorMessage: '',
        
        // Calculated values
        calculatedDiscount: 0,
        calculatedDiscountDisplay: '',
        isCapped: false,
        finalAmountDisplay: '',
        
        init() {
            // Debug logging removed for production
        },
        
        async findMember() {
            if (!this.memberIdentifier) return;
            
            this.memberError = '';
            this.member = null;
            
            try {
                const response = await fetch(`/api/members/find/${this.memberIdentifier}`);
                const data = await response.json();
                
                if (data.success) {
                    this.member = data.member;
                } else {
                    this.memberError = data.message || '{{ __('common.member_not_found') }}';
                }
            } catch (error) {
                this.memberError = '{{ __('common.network_error') }}';
            }
        },
        
        async redeemVoucher() {
            this.redeeming = true;
            this.errorMessage = '';
            
            const orderAmountCents = this.orderAmount && parseFloat(this.orderAmount) > 0 
                ? Math.round(parseFloat(this.orderAmount) * 100)
                : null;
            
            try {
                const validateResponse = await fetch('{{ route("staff.vouchers.validate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        code: this.voucherCode,
                        member_id: this.member?.id || '{{ $member->id ?? "" }}',
                        club_id: '{{ $club->id ?? "" }}',
                        order_amount: orderAmountCents
                    })
                });
                
                const validateData = await validateResponse.json();
                
                if (!validateData.valid) {
                    this.errorMessage = validateData.error_message || '{{ __('common.voucher_invalid') }}';
                    this.redeeming = false;
                    this.$nextTick(() => {
                        if (this.$refs.errorBanner) {
                            this.$refs.errorBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    });
                    return;
                }
                
                const formData = new FormData();
                formData.append('voucher_id', validateData.voucher.id);
                formData.append('member_id', this.member?.id || '{{ $member->id ?? "" }}');
                if (orderAmountCents !== null) {
                    formData.append('order_amount', orderAmountCents);
                }
                if (this.orderReference) {
                    formData.append('order_reference', this.orderReference);
                }
                
                const imageInput = document.querySelector('input[name="image"]');
                if (imageInput && imageInput.files && imageInput.files[0]) {
                    formData.append('image', imageInput.files[0]);
                }
                
                const redeemResponse = await fetch('{{ route("staff.vouchers.redeem.post") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                
                const redeemData = await redeemResponse.json();
                
                if (redeemData.success) {
                    if (redeemData.redirect_url) {
                        window.location.href = redeemData.redirect_url;
                    }
                } else {
                    this.errorMessage = redeemData.message || '{{ __('common.redemption_failed') }}';
                }
            } catch (error) {
                console.error('Redemption error:', error);
                this.errorMessage = '{{ __('common.network_error') }}';
            } finally {
                this.redeeming = false;
            }
        },
        
        resetForm() {
            this.voucherCode = '{{ $voucher->code ?? "" }}';
            this.orderAmount = '';
            this.orderReference = '';
            this.errorMessage = '';
            this.calculatedDiscount = 0;
            this.calculatedDiscountDisplay = '';
            this.isCapped = false;
            this.finalAmountDisplay = '';
            
            const imageInput = document.querySelector('input[name="image"]');
            if (imageInput) {
                imageInput.value = '';
            }
        },
        
        calculateDiscount() {
            @if(isset($voucher) && $voucher->type === 'percentage')
                if (!this.orderAmount || parseFloat(this.orderAmount) <= 0) {
                    this.calculatedDiscount = 0;
                    this.calculatedDiscountDisplay = '';
                    this.isCapped = false;
                    this.finalAmountDisplay = '';
                    return;
                }
                
                const orderAmt = parseFloat(this.orderAmount);
                let discount = orderAmt * (this.voucherPercentage / 100);
                this.isCapped = false;
                
                if (this.voucherMaxCap && (discount * 100) > this.voucherMaxCap) {
                    discount = this.voucherMaxCap / 100;
                    this.isCapped = true;
                }
                
                this.calculatedDiscount = discount;
                this.calculatedDiscountDisplay = `${discount.toFixed(2)} ${this.voucherCurrency}`;
                
                const finalAmount = Math.max(0, orderAmt - discount);
                this.finalAmountDisplay = `${finalAmount.toFixed(2)} ${this.voucherCurrency}`;
            @endif
        },
        
        formatCurrency(cents) {
            const amount = (cents / 100).toFixed(2);
            return `${amount} {{ $club->currency ?? 'USD' }}`;
        }
    }));
});
</script>
@endpush
