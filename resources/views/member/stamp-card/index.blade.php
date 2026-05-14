{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Stamp Card Detail View - Member Interface
Premium, iOS 2030-level design matching loyalty cards.
Beautiful animations, consistent UX, Jony Ive approved.
--}}
@extends('member.layouts.default')

{{-- SEO: Title --}}
@section('page_title', $stampCard->title)

{{-- SEO: Rich meta for social sharing --}}
@section('meta_description', $stampCard->description ?? trans('common.stamp_card_meta_description', ['card' => $stampCard->title, 'stamps' => $stampCard->stamps_required]))
@section('meta_image', $stampCard->getFirstMediaUrl('background', 'md'))

@section('content')
@php
    $urlToCollectStamp = auth('member')->check() 
        ? route('staff.stamps.add.show', ['member_identifier' => auth('member')->user()->unique_identifier, 'stamp_card_id' => $stampCard->id])
        : '';
    $enrollment = auth('member')->check() 
        ? $stampCard->enrollments()->where('member_id', auth('member')->id())->first() 
        : null;
@endphp
<div class="flex flex-col w-full px-4 md:px-8 py-8 md:py-8" x-data="{ showQr: false }">
    <div class="space-y-6 h-full w-full place-items-center">
        
        {{-- Breadcrumbs --}}
        <div class="max-w-lg mx-auto animate-slide-in-right mb-8">
            <x-ui.breadcrumb :crumbs="[
                ['url' => route('member.index'), 'icon' => 'home', 'title' => trans('common.home')], 
                ['text' => $stampCard->title]
            ]" />
        </div>
        
        
        {{-- Premium Stamp Card Display --}}
        <x-member.stamp-card 
            :stampCard="$stampCard" 
            :member="auth('member')->user()"
            :detail-view="true" 
            class="card-stagger" />
        
        {{-- Actions Section - Unified group --}}
        <div class="w-full max-w-lg mx-auto space-y-4">
            
            {{-- Primary Action: Show QR Code --}}
            @if(auth('member')->check() && (!$stampCard->valid_until || !now()->isAfter($stampCard->valid_until)))
                <div class="animate-fade-in-up delay-100">
                    <x-member.action-button
                        icon="qr-code"
                        :title="trans('common.show_qr_code')"
                        :subtitle="trans('common.collect_stamp_from_staff')"
                        color="primary"
                        click="showQr = true"
                    />
                </div>
            @endif
            
            {{-- Collect Reward Button (for completed cards with physical rewards) --}}
            @if(auth('member')->check() && $enrollment && $enrollment->pending_rewards > 0 && $stampCard->requires_physical_claim)
                <div class="animate-fade-in-up delay-150" x-data="{ showRewardQr: false }">
                    <x-member.action-button
                        icon="gift"
                        :title="trans('common.collect_reward')"
                        :subtitle="trans('common.show_qr_to_staff')"
                        color="amber"
                        click="showRewardQr = true"
                    />
                    
                    {{-- Reward QR Modal --}}
                    <div x-show="showRewardQr" style="display: none;"
                        class="fixed inset-0 z-[60] flex items-center justify-center px-4 bg-black/80 backdrop-blur-sm"
                        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <div class="bg-white dark:bg-secondary-900 w-full max-w-sm rounded-3xl p-8 shadow-2xl relative transform transition-all"
                            @click.away="showRewardQr = false" x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 scale-90 translate-y-4">
                            <button @click="showRewardQr = false"
                                class="absolute top-4 right-4 p-2 rounded-full hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors">
                                <x-ui.icon icon="x" class="w-6 h-6 text-secondary-500" />
                            </button>
                            <div class="text-center space-y-6">
                                <div>
                                    <h3 class="text-2xl font-bold text-secondary-900 dark:text-white">{{ trans('common.collect_reward') }}</h3>
                                    <p class="text-secondary-500 dark:text-secondary-400 mt-2">{{ trans('common.show_qr_to_staff') }}</p>
                                </div>
                                <div class="bg-white p-4 rounded-2xl shadow-inner border border-secondary-100 inline-block">
                                    <img src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs="
                                        class="w-64 h-64 object-contain" data-qr-url="{{ route('staff.stamps.claim.show', ['member_identifier' => auth('member')->user()->unique_identifier, 'stamp_card_id' => $stampCard->id]) }}"
                                        data-qr-color-light="#FCFCFC"
                                        data-qr-color-dark="#1F1F1F" />
                                </div>
                                <div class="space-y-1.5 text-center">
                                    <div class="font-bold text-lg text-secondary-900 dark:text-white">
                                        {{ $stampCard->title }}
                                    </div>
                                    @if($stampCard->reward_description)
                                        <div class="text-sm text-secondary-500 dark:text-secondary-400">
                                            {{ $stampCard->reward_description }}
                                        </div>
                                    @endif
                                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg mt-2">
                                        <x-ui.icon icon="gift" class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                                        <span class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                                            {{ trans_choice('common.pending_rewards', $enrollment->pending_rewards, ['count' => $enrollment->pending_rewards]) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        
        {{-- Content Tabs --}}
        <div class="w-full max-w-lg mx-auto pt-4">
            <div class="animate-fade-in-up delay-300 w-full">
                <x-ui.tabs :tabs="[trans('common.progress'), trans('common.history'), trans('common.rules')]"
                    active-tab="1" style="tabs-underline-full-width">
                    <x-slot name="tab1">
                        <x-member.stamp-progress :stampCard="$stampCard" :enrollment="$enrollment" />
                    </x-slot>
                    <x-slot name="tab2">
                        <x-member.stamp-history :stampCard="$stampCard" :enrollment="$enrollment" :show-notes="false" :show-staff="false" />
                    </x-slot>
                    <x-slot name="tab3">
                        <x-member.stamp-rules :stampCard="$stampCard" />
                    </x-slot>
                </x-ui.tabs>
            </div>
        </div>

        {{-- Add/Remove Card Button - Below tabs, above share --}}
        <div class="w-full max-w-lg mx-auto animate-fade-in-up delay-400">
            <x-member.enroll-stamp-card :stampCard="$stampCard" />
        </div>

        {{-- Business Card --}}
        @if($stampCard->partner && $stampCard->partner->hasBusinessProfile())
            <div class="w-full max-w-lg mx-auto animate-fade-in-up delay-450">
                <x-member.business-card :partner="$stampCard->partner" />
            </div>
        @endif
        
        {{-- Share - Page utility at the end --}}
        @if(!($stampCard->valid_until && now()->isAfter($stampCard->valid_until)))
            <div class="w-full max-w-lg mx-auto flex justify-center">
                <x-ui.share :url="url()->current()" :text="$stampCard->title" size="lg" />
            </div>
        @endif
    </div>
    
    {{-- Premium QR Modal --}}
    <x-ui.qr-modal 
        show="showQr"
        :title="trans('common.show_qr_code')"
        :subtitle="trans('common.collect_stamp_from_staff')"
        :qr-url="$urlToCollectStamp"
        qr-color-light="#FCFCFC"
        qr-color-dark="#1F1F1F"
        :identifier="$stampCard->title"
        identifier-label="card"
        icon-color="primary"
        :enable-cache="true"
        :card-id="'stamp-' . $stampCard->id"
        :card-name="$stampCard->title"
        :card-balance="($enrollment?->stamps_collected ?? 0) . ' / ' . $stampCard->stamps_required . ' ' . trans('common.stamps')" />
</div>

@if(auth('member')->check() && (!$stampCard->valid_until || !now()->isAfter($stampCard->valid_until)))
<script>
    console.log('🔗 Collect Stamp QR URL:', '{!! $urlToCollectStamp !!}');
    @if($enrollment && $enrollment->pending_rewards > 0 && $stampCard->requires_physical_claim)
    console.log('🎁 Collect Reward QR URL:', '{{ route('staff.stamps.claim.show', ['member_identifier' => auth('member')->user()->unique_identifier, 'stamp_card_id' => $stampCard->id]) }}');
    @endif
</script>
@endif
@stop