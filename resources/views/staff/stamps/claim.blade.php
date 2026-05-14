{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Staff Physical Reward Claim Page

Design: Follows canonical staff action page pattern (§6.3 design system).
No ambient effects, no custom keyframes.
--}}

@extends('staff.layouts.default')

@section('page_title', trans('common.collect_reward') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-lg mx-auto px-4 py-8 md:py-14">
    @if($member && $card && $enrollment)
        {{-- Header --}}
        @php
            $stampIcon = $card->stamp_icon ?? '🎁';
            $isEmoji = preg_match('/[^\x00-\x7F]/', $stampIcon);
        @endphp
        
        <x-ui.page-header
            icon="gift"
            :title="trans('common.collect_reward')"
            :description="$card->title"
            compact
        />

        {{-- Member Card --}}
        <div class="mt-8 mb-6">
            <x-member.member-card :member="$member" :club="$card->club" :show-tier="true" />
        </div>

        {{-- Messages --}}
        <div class="mb-6">
            <x-forms.messages />
        </div>

        {{-- Claim Form --}}
        <x-forms.form-open action="{{ route('staff.stamps.claim') }}" enctype="multipart/form-data" method="POST" />
            <input type="hidden" name="member_identifier" value="{{ $member->unique_identifier }}">
            <input type="hidden" name="stamp_card_id" value="{{ $card->id }}">
            
            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                
                {{-- Form Content --}}
                <div class="p-6 space-y-5">
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

                {{-- Submit Button --}}
                <div class="px-6 pb-6 pt-0">
                    <button type="submit" 
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
                                <x-ui.icon icon="gift" class="w-5 h-5" />
                                <span>{{ trans('common.confirm_reward_claimed') }}</span>
                            </span>
                        </template>
                        <template x-if="submitting">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ trans('common.redeeming') }}...</span>
                            </span>
                        </template>
                    </button>
                </div>
                
                {{-- Reward Details --}}
                <div class="px-6 pb-6 pt-4">
                    <div class="flex items-start gap-2.5">
                        <x-ui.icon icon="gift" class="w-4 h-4 text-secondary-400 shrink-0 mt-0.5" />
                        <div class="flex-1">
                            <h5 class="font-medium text-secondary-600 dark:text-secondary-400 text-xs mb-3">{{ trans('common.reward_to_give') }}</h5>
                            
                            {{-- Reward Name & Description --}}
                            <div class="mb-4 pb-3 border-b border-secondary-200 dark:border-secondary-800">
                                <div class="text-sm font-bold text-secondary-900 dark:text-white mb-1.5">
                                    {{ $card->reward_title }}
                                </div>
                                @if($card->reward_description)
                                    <div class="text-xs text-secondary-600 dark:text-secondary-400 leading-relaxed">
                                        {{ $card->reward_description }}
                                    </div>
                                @endif
                            </div>

                            {{-- Details Table --}}
                            <div class="space-y-2.5 text-xs mb-4">
                                <div class="flex items-start justify-between py-1">
                                    <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.stamps_required') }}</span>
                                    <span class="font-medium text-secondary-900 dark:text-white">{{ $card->stamps_required }}</span>
                                </div>

                                @if($card->reward_value && $card->show_monetary_value)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.value') }}</span>
                                        <span class="font-medium text-secondary-900 dark:text-white">{{ moneyFormat((float) $card->reward_value, $card->currency) }}</span>
                                    </div>
                                @endif

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
                            </div>

                            {{-- Status Badge --}}
                            <div class="inline-flex items-center gap-1.5 px-2 py-1 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-md">
                                <x-ui.icon icon="check-circle-2" class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                                <span class="font-medium text-xs text-emerald-700 dark:text-emerald-300">{{ trans('common.card_completed') }}</span>
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
        {{-- Error States --}}
        <div class="space-y-6">
            @if(!$member)
                <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 rounded-xl p-8 text-center">
                    <div class="w-16 h-16 mx-auto rounded-xl bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center mb-4">
                        <x-ui.icon icon="user-x" class="w-8 h-8 text-rose-600 dark:text-rose-400" />
                    </div>
                    <h3 class="text-lg font-semibold text-rose-900 dark:text-rose-300">{{ trans('common.member_not_found') }}</h3>
                </div>
            @endif

            @if(!$card)
                <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 rounded-xl p-8 text-center">
                    <div class="w-16 h-16 mx-auto rounded-xl bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center mb-4">
                        <x-ui.icon icon="credit-card" class="w-8 h-8 text-rose-600 dark:text-rose-400" />
                    </div>
                    <h3 class="text-lg font-semibold text-rose-900 dark:text-rose-300">{{ trans('common.stamp_card_not_found') }}</h3>
                </div>
            @endif

            @if($member && $card && !$enrollment)
                <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl p-8 text-center">
                    <div class="w-16 h-16 mx-auto rounded-xl bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center mb-4">
                        <x-ui.icon icon="alert-circle" class="w-8 h-8 text-amber-500 dark:text-amber-400" />
                    </div>
                    <h3 class="text-lg font-semibold text-amber-800 dark:text-amber-300">{{ trans('common.member_not_enrolled') }}</h3>
                </div>
            @endif
        </div>
    @endif
</div>
@stop
