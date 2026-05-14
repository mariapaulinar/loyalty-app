@extends('member.layouts.default')

{{-- SEO: Title --}}
@section('page_title', $card->head)

{{-- SEO: Rich meta for social sharing --}}
@section('meta_description', $card->description ?? trans('common.card_meta_description', ['card' => $card->head]))
@section('meta_image', $card->getFirstMediaUrl('logo', 'md') ?: $card->getFirstMediaUrl('background', 'md'))

@section('content')
@php
    $urlToEarnPoints = auth('member')->check() ? route('staff.earn.points', ['member_identifier' => auth('member')->user()->unique_identifier, 'card_identifier' => $card->unique_identifier]) : '';
@endphp
<div class="flex flex-col w-full px-4 md:px-8 py-8 md:py-8" x-data="{ showQr: false }">
    <div class="space-y-6 h-full w-full place-items-center">
        
        {{-- Breadcrumbs --}}
        <div class="max-w-lg mx-auto animate-slide-in-right mb-8">
            <x-ui.breadcrumb :crumbs="[
                ['url' => route('member.index'), 'icon' => 'home', 'title' => trans('common.home')], 
                ['text' => $card->head]
            ]" />
        </div>
        
        <x-member.premium-card :card="$card" :flippable="true" :detail-view="true" class="card-stagger" />

        {{-- Actions Section - Unified group --}}
        <div class="w-full max-w-lg mx-auto space-y-4">
            
            {{-- Primary Action: Scan Card --}}
            @if(auth('member')->check() && !$card->isExpired)
                <div class="animate-fade-in-up delay-100">
                    <x-member.action-button
                        icon="qr-code"
                        :title="trans('common.scan_card')"
                        :subtitle="trans('common.show_qr_to_staff')"
                        color="primary"
                        click="showQr = true"
                    />
                </div>
            @endif
            
            {{-- Secondary Action: Contact --}}
            <x-member.card-contact :card="$card" class="animate-fade-in-up delay-200" />
        </div>

        {{-- Content Tabs - Separate section with more breathing room --}}
        <div class="w-full max-w-lg mx-auto pt-4">
            <div class="animate-fade-in-up delay-300 w-full">

                <x-ui.tabs :tabs="[trans('common.rewards'), trans('common.history'), trans('common.rules')]"
                    active-tab="1" style="tabs-underline-full-width">
                    <x-slot name="tab1">
                        <x-member.rewards :card="$card" :show-claimable="true" />
                    </x-slot>
                    <x-slot name="tab2">
                        <x-member.history :card="$card" :member="auth('member')->user() ?? null"
                            :show-expired-and-used-transactions="true" />
                    </x-slot>
                    <x-slot name="tab3">
                        <x-ui.rules-section>
                            @if($card->initial_bonus_points > 0)
                            <x-ui.rule-item
                                icon="gift"
                                :title="trans('common.welcome_bonus')"
                                :description="trans('common.rules_1', ['initial_bonus_points' => '<span class=\'font-bold text-green-600 dark:text-green-400 format-number\'>' . $card->initial_bonus_points . '</span>'])"
                                color="green" />
                        @endif
                        
                        <x-ui.rule-item
                            icon="clock"
                            :title="trans('common.expiration_policy')"
                            :description="trans('common.rules_2', ['points_expiration_months' => $card->points_expiration_months])"
                            color="amber" />
                        
                        <x-ui.rule-item
                            icon="coins"
                            :title="trans('common.earning_rate')"
                            :description="trans('common.rules_3', ['currency_unit_amount' => '<span class=\'font-bold text-accent-600 dark:text-accent-400 format-number\'>' . $card->currency_unit_amount . '</span>', 'currency' => $card->currency, 'points_per_currency' => '<span class=\'font-bold text-accent-600 dark:text-accent-400\'>' . $card->points_per_currency . '</span>'])"
                            color="primary" />
                        
                        <x-ui.rule-item
                            icon="trending-up"
                            :title="trans('common.points_range')"
                            :description="trans('common.rules_4', ['min_points_per_purchase' => '<span class=\'font-bold text-primary-600 dark:text-primary-400 format-number\'>' . $card->min_points_per_purchase . '</span>', 'max_points_per_purchase' => '<span class=\'font-bold text-primary-600 dark:text-primary-400 format-number\'>' . $card->max_points_per_purchase . '</span>'])"
                            color="primary" />
                        </x-ui.rules-section>
                    </x-slot>
                </x-ui.tabs>
            </div>
        </div>

        {{-- Tier Status for This Club (Contextual - Above Add/Remove) --}}
        @if(isset($memberTierData) && $memberTierData)
            <div class="w-full max-w-lg mx-auto mt-8 animate-fade-in-up delay-400">
                <x-member.tier-status 
                    :memberTier="$memberTierData['memberTier']" 
                    :club="$memberTierData['club']"
                    :card="$memberTierData['card']"
                    :progress="$memberTierData['progress']" />
            </div>
        @endif

        {{-- Add/Remove Card Button - Below tier, above share --}}
        <div class="w-full max-w-lg mx-auto animate-fade-in-up delay-450">
            <x-member.follow-card :card="$card" />
        </div>

        {{-- Business Card --}}
        @if($card->partner && $card->partner->hasBusinessProfile())
            <div class="w-full max-w-lg mx-auto animate-fade-in-up delay-500">
                <x-member.business-card :partner="$card->partner" />
            </div>
        @endif

        {{-- Share - Page utility at the end --}}
        @if(!$card->isExpired)
        <div class="w-full max-w-lg mx-auto flex justify-center">
            <x-ui.share :url="url()->current()" :text="$card->head" size="lg" />
        </div>
        @endif
    </div>
    
    {{-- Premium QR Modal --}}
    <x-ui.qr-modal 
        show="showQr"
        :title="trans('common.scan_card')"
        :subtitle="trans('common.show_qr_to_staff')"
        :qr-url="$urlToEarnPoints"
        :identifier="$card->unique_identifier"
        identifier-label="identifier"
        icon-color="primary"
        :enable-cache="true"
        :card-id="$card->id"
        :card-name="$card->head"
        :card-balance="(auth('member')->user()?->points ?? 0) . ' ' . trans('common.points')" />
</div>

@if(auth('member')->check() && !$card->isExpired)
<script>
    console.log('🔗 Earn Points QR URL:', '{!! $urlToEarnPoints !!}');
</script>
@endif
@stop