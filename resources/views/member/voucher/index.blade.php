{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Voucher Detail View - Member Interface
Premium design matching loyalty and stamp cards.
--}}
@extends('member.layouts.default')

{{-- SEO: Title --}}
@section('page_title', $voucher->title ?: $voucher->name)

{{-- SEO: Rich meta for social sharing --}}
@section('meta_description', $voucher->description ?? trans('common.voucher_meta_description', ['voucher' => $voucher->title ?: $voucher->name]))
@section('meta_image', $voucher->getFirstMediaUrl('image', 'md'))

@section('content')
@php
    $urlToRedeem = auth('member')->check() 
        ? route('staff.vouchers.redeem.show', ['member_identifier' => auth('member')->user()->unique_identifier, 'voucher_id' => $voucher->id])
        : '';
@endphp
<div class="flex flex-col w-full px-4 md:px-8 py-8 md:py-8" x-data="{ showQr: false }">
    <div class="space-y-6 h-full w-full place-items-center">
        
        {{-- Breadcrumbs --}}
        <div class="max-w-lg mx-auto animate-slide-in-right mb-8">
            <x-ui.breadcrumb :crumbs="[
                ['url' => route('member.index'), 'icon' => 'home', 'title' => trans('common.home')],
                ['text' => $voucher->title ?: $voucher->name]
            ]" />
        </div>
        
        
        {{-- Premium Voucher Card Display --}}
        <x-member.voucher-card 
            :voucher="$voucher" 
            :member="auth('member')->user()"
            :detail-view="true" 
            class="card-stagger" />

        {{-- Actions Section - Unified group --}}
        <div class="w-full max-w-lg mx-auto space-y-4">
            
            {{-- Primary Action: Show QR Code to Staff --}}
            @if(auth('member')->check() && $voucher->is_valid)
                <div class="animate-fade-in-up delay-100">
                    <x-member.action-button
                        icon="qr-code"
                        :title="__('common.show_to_staff')"
                        :subtitle="__('common.apply_voucher_at_checkout')"
                        color="primary"
                        click="showQr = true"
                    />
                </div>
            @endif
            
            {{-- Copy Code Button --}}
            @if(auth('member')->check() && $voucher->is_valid)
                <div class="animate-fade-in-up delay-150">
                    <x-member.action-button
                        icon="copy"
                        :title="__('common.copy_code')"
                        :subtitle="$voucher->code"
                        color="primary"
                        :copy-code="$voucher->code"
                        :title-copied="__('common.code_copied')"
                        :subtitle-copied="__('common.paste_at_checkout')"
                    />
                </div>
            @endif
        </div>
        
        {{-- Content Tabs --}}
        <div class="w-full max-w-lg mx-auto pt-4">
            <div class="animate-fade-in-up delay-200">
                <x-ui.tabs 
                    :tabs="[__('common.details'), __('common.history'), __('common.rules')]"
                    active-tab="1"
                >
                    <x-slot name="tab1">
                        <div class="space-y-4">
                            <h3 class="font-semibold text-secondary-900 dark:text-white mb-4">{{ __('common.voucher_details') }}</h3>
                            
                            {{-- Voucher Code (Prominent) --}}
                            <div class="flex justify-between items-center py-3 bg-secondary-50 dark:bg-secondary-800/50 rounded-lg px-4 border border-secondary-200 dark:border-secondary-700">
                                <span class="text-sm font-medium text-secondary-600 dark:text-secondary-400">{{ __('common.voucher_code') }}</span>
                                <span class="font-bold text-lg font-mono text-primary-600 dark:text-primary-400">{{ $voucher->code }}</span>
                            </div>
                            
                            {{-- Detail Items (with automatic dividers, no border on last item) --}}
                            <div class="divide-y divide-secondary-100 dark:divide-secondary-800">
                                {{-- Discount Value --}}
                                <div class="flex justify-between items-center py-3">
                                    <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ __('common.discount_value') }}</span>
                                    <span class="font-semibold text-secondary-900 dark:text-white">{{ $voucher->formatted_value }}</span>
                                </div>
                                
                                {{-- Max Discount Cap (for percentage vouchers) --}}
                                @if($voucher->type === 'percentage' && $voucher->max_discount_amount)
                                    <div class="flex justify-between items-center py-3">
                                        <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ __('common.max_discount') }}</span>
                                        <span class="font-semibold text-secondary-900 dark:text-white">{{ moneyFormat($voucher->max_discount_amount / 100, $voucher->currency) }}</span>
                                    </div>
                                @endif
                                
                                {{-- Minimum Purchase --}}
                                @if($voucher->min_purchase_amount)
                                    <div class="flex justify-between items-center py-3">
                                        <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ __('common.minimum_purchase') }}</span>
                                        <span class="font-semibold text-secondary-900 dark:text-white">{{ moneyFormat($voucher->min_purchase_amount / 100, $voucher->currency) }}</span>
                                    </div>
                                @endif
                                
                                {{-- Validity Period --}}
                                <div class="flex justify-between items-center py-3">
                                    <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ __('common.valid_from') }}</span>
                                    @if($voucher->valid_from)
                                    <span class="font-mono text-sm text-secondary-900 dark:text-white format-date" data-date="{{ $voucher->valid_from }}">{{ $voucher->valid_from->format('M d, Y') }}</span>
                                    @else
                                    <span class="font-mono text-sm text-secondary-900 dark:text-white">{{ __('common.immediate') }}</span>
                                    @endif
                                </div>
                                
                                <div class="flex justify-between items-center py-3">
                                    <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ __('common.valid_until') }}</span>
                                    @if($voucher->valid_until)
                                    <span class="font-mono text-sm text-secondary-900 dark:text-white format-date" data-date="{{ $voucher->valid_until }}">{{ $voucher->valid_until->format('M d, Y') }}</span>
                                    @else
                                    <span class="font-mono text-sm text-secondary-900 dark:text-white">{{ __('common.no_expiration') }}</span>
                                    @endif
                                </div>
                                
                                {{-- Total Usage Limit & Remaining --}}
                                @if($voucher->max_uses_total)
                                    <div class="flex justify-between items-center py-3">
                                        <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ __('common.remaining_uses') }}</span>
                                        <span class="font-semibold text-secondary-900 dark:text-white">
                                            <span class="format-number">{{ $voucher->remaining_uses }}</span> {{ __('common.of') }} <span class="format-number">{{ $voucher->max_uses_total }}</span>
                                            @if($voucher->remaining_uses < 10)
                                                <span class="ml-2 text-xs font-medium text-amber-600 dark:text-amber-400">{{ __('common.limited') }}</span>
                                            @endif
                                        </span>
                                    </div>
                                @endif
                                
                                {{-- Your Personal Usage Limit --}}
                                @if(auth('member')->check() && $voucher->max_uses_per_member)
                                    @php
                                        $memberUsageCount = $voucher->getMemberUsageCount(auth('member')->user());
                                        $memberRemaining = $voucher->getRemainingUsesForMember(auth('member')->user());
                                    @endphp
                                    <div class="flex justify-between items-center py-3">
                                        <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ __('common.your_remaining_uses') }}</span>
                                        <span class="font-semibold text-secondary-900 dark:text-white">
                                            {{ $memberRemaining }} {{ __('common.of') }} {{ $voucher->max_uses_per_member }}
                                        </span>
                                    </div>
                                @endif
                                
                                {{-- Bonus Points Reward Card --}}
                                @if($voucher->type === 'bonus_points' && $voucher->rewardCard)
                                    <div class="flex justify-between items-center py-3">
                                        <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ __('common.points_added_to') }}</span>
                                        <span class="font-semibold text-secondary-900 dark:text-white">{{ $voucher->rewardCard->name }}</span>
                                    </div>
                                @endif
                                
                                {{-- Free Product Name --}}
                                @if($voucher->type === 'free_product' && $voucher->free_product_name)
                                    <div class="flex justify-between items-center py-3">
                                        <span class="text-sm text-secondary-600 dark:text-secondary-400">{{ __('common.free_product') }}</span>
                                        <span class="font-semibold text-secondary-900 dark:text-white">{{ $voucher->free_product_name }}</span>
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Description --}}
                            @if($voucher->description)
                                <div class="mt-6 pt-4 border-t border-secondary-200 dark:border-secondary-700">
                                    <p class="text-sm text-secondary-600 dark:text-secondary-400 leading-relaxed">{{ $voucher->description }}</p>
                                </div>
                            @endif
                            
                            {{-- How to Use Instructions --}}
                            <div class="mt-6 pt-4 border-t border-secondary-200 dark:border-secondary-700">
                                <h4 class="text-sm font-semibold text-secondary-900 dark:text-white mb-2">{{ __('common.how_to_use') }}</h4>
                                <ol class="list-decimal list-inside space-y-1.5 text-sm text-secondary-600 dark:text-secondary-400">
                                    <li>{{ __('common.voucher_instruction_1') }}</li>
                                    <li>{{ __('common.voucher_instruction_2') }}</li>
                                    <li>{{ __('common.voucher_instruction_3') }}</li>
                                </ol>
                            </div>
                        </div>
                    </x-slot>
                    <x-slot name="tab2">
                        <x-member.voucher-history 
                            :member="auth('member')->user()" 
                            :voucher="$voucher"
                            :show-notes="false"
                            :show-staff="false" />
                    </x-slot>
                    <x-slot name="tab3">
                        <x-ui.rules-section :title="__('common.eligibility_rules')">
                            {{-- Usage Restrictions --}}
                            @if($voucher->is_single_use)
                                <x-ui.rule-item
                                    icon="alert-circle"
                                    :title="__('common.usage_restrictions')"
                                    :description="__('common.single_use_per_member')"
                                    color="amber" />
                            @elseif($voucher->max_uses_per_member)
                                <x-ui.rule-item
                                    icon="repeat"
                                    :title="__('common.usage_restrictions')"
                                    :description="__('common.max_uses_per_member', ['count' => $voucher->max_uses_per_member])"
                                    color="purple" />
                            @endif
                            
                            @if(!$voucher->stackable)
                                <x-ui.rule-item
                                    icon="layers"
                                    :title="__('common.stacking_policy')"
                                    :description="__('common.cannot_combine_with_other_vouchers')"
                                    color="amber" />
                            @endif
                            
                            @if($voucher->min_purchase_amount)
                                <x-ui.rule-item
                                    icon="shopping-cart"
                                    :title="__('common.minimum_purchase')"
                                    :description="__('common.requires_minimum_purchase_of') . ' ' . moneyFormat($voucher->min_purchase_amount / 100, $voucher->currency)"
                                    color="emerald" />
                            @endif
                            
                            {{-- Member Eligibility --}}
                            @if($voucher->first_order_only)
                                <x-ui.rule-item
                                    icon="shopping-bag"
                                    :title="__('common.member_eligibility')"
                                    :description="__('common.first_order_only')"
                                    color="blue" />
                            @endif
                            
                            @if($voucher->new_members_only)
                                <x-ui.rule-item
                                    icon="user-plus"
                                    :title="__('common.new_members')"
                                    :description="__('common.new_members_only_days', ['days' => $voucher->new_members_days ?? 30])"
                                    color="green" />
                            @endif
                            
                            @if($voucher->target_tiers && count($voucher->target_tiers) > 0)
                                <x-ui.rule-item
                                    icon="star"
                                    :title="__('common.tier_requirement')"
                                    :description="__('common.available_to_member_tiers') . ': <span class=\'font-medium\'>' . implode(', ', $voucher->target_tiers) . '</span>'"
                                    color="amber" />
                            @endif
                            
                            {{-- Product Restrictions --}}
                            @if($voucher->applicable_products && count($voucher->applicable_products) > 0)
                                <x-ui.rule-item
                                    icon="check-circle"
                                    :title="__('common.applicable_products')"
                                    :description="'<span class=\'font-medium\'>' . __('common.applicable_to_products') . ':</span><br>' . implode(', ', $voucher->applicable_products)"
                                    color="green" />
                            @endif
                            
                            @if($voucher->applicable_categories && count($voucher->applicable_categories) > 0)
                                <x-ui.rule-item
                                    icon="tag"
                                    :title="__('common.applicable_categories')"
                                    :description="'<span class=\'font-medium\'>' . __('common.applicable_to_categories') . ':</span><br>' . implode(', ', $voucher->applicable_categories)"
                                    color="blue" />
                            @endif
                            
                            @if($voucher->excluded_products && count($voucher->excluded_products) > 0)
                                <x-ui.rule-item
                                    icon="x-circle"
                                    :title="__('common.excluded_items')"
                                    :description="'<span class=\'font-medium\'>' . __('common.excluded_products') . ':</span><br>' . implode(', ', $voucher->excluded_products)"
                                    color="pink" />
                            @endif
                            
                            {{-- No Restrictions --}}
                            @if(!$voucher->is_single_use && 
                                !$voucher->max_uses_per_member && 
                                !$voucher->first_order_only && 
                                !$voucher->new_members_only && 
                                $voucher->stackable && 
                                !$voucher->min_purchase_amount &&
                                (!$voucher->target_tiers || count($voucher->target_tiers) === 0) &&
                                (!$voucher->applicable_products || count($voucher->applicable_products) === 0) &&
                                (!$voucher->applicable_categories || count($voucher->applicable_categories) === 0) &&
                                (!$voucher->excluded_products || count($voucher->excluded_products) === 0))
                                <x-ui.rule-item
                                    icon="check-circle"
                                    :title="__('common.no_restrictions')"
                                    :description="__('common.voucher_can_be_used_freely')"
                                    color="green"
                                    :highlight="true" />
                            @endif
                        </x-ui.rules-section>
                    </x-slot>
                </x-ui.tabs>
            </div>
        </div>

        {{-- Add/Remove Card Button - Below tabs, above share --}}
        <div class="w-full max-w-lg mx-auto animate-fade-in-up delay-400">
            <x-member.save-voucher :voucher="$voucher" />
        </div>

        {{-- Business Card --}}
        @php
            $voucherPartner = $voucher->club?->partner ?? $voucher->creator;
        @endphp
        @if($voucherPartner && $voucherPartner->hasBusinessProfile())
            <div class="w-full max-w-lg mx-auto animate-fade-in-up delay-450">
                <x-member.business-card :partner="$voucherPartner" />
            </div>
        @endif
        
        {{-- Share - Page utility at the end --}}
        @if($voucher->is_valid)
            <div class="w-full max-w-lg mx-auto flex justify-center">
                <x-ui.share :url="url()->current()" :text="$voucher->title ?: $voucher->name" size="lg" />
            </div>
        @endif
    </div>
    
    {{-- Premium QR Modal --}}
    @if(auth('member')->check() && $urlToRedeem)
        <x-ui.qr-modal 
            show="showQr"
            :title="__('common.show_to_staff')"
            :subtitle="__('common.scan_to_apply_voucher')"
            :qr-url="$urlToRedeem"
            qr-color-light="#FCFCFC"
            qr-color-dark="#1F1F1F"
            :identifier="$voucher->code"
            identifier-label="code"
            icon-color="primary"
            :enable-cache="true"
            :card-id="'voucher-' . $voucher->id"
            :card-name="$voucher->title ?: $voucher->name"
            :card-balance="$voucher->formatted_value" />
    @endif
</div>

{{-- Confetti celebration when voucher is claimed --}}
@if(session('voucher_claimed'))
<script>
    // Wait for page to fully load, then fire confetti
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            if (window.confettiFireworks) {
                window.confettiFireworks();
            }
        }, 500); // Small delay for smooth experience
    });
</script>
@endif

{{-- Console output for QR URL testing --}}
@if(auth('member')->check() && $voucher->is_valid && $urlToRedeem)
<script>
    console.log('🎟️ Redeem Voucher QR URL:', '{{ $urlToRedeem }}');
</script>
@endif
@stop