@extends('staff.layouts.default')

@section('page_title', $reward->title . config('default.page_title_delimiter') . trans('common.claim_reward') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-lg mx-auto px-4 py-8 md:py-14">
    @if($member && $card && $reward)
        {{-- Header --}}
        <x-ui.page-header
            icon="gift"
            :title="trans('common.claim_reward')"
            :description="trans('common.redeem_reward_for_member')"
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

        {{-- Reward Card Visual --}}
        <div class="mb-6">
            <x-member.reward-card :reward="$reward" :card="$card" size="full" :showButton="false" />
        </div>

        @if($canRedeem)
            {{-- Claim Form --}}
            <x-forms.form-open action="{{ route('staff.claim.reward.post', ['member_identifier' => $member->unique_identifier, 'card_id' => $card->id, 'reward_id' => $reward->id]) }}" enctype="multipart/form-data" method="POST" />
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
                    <div class="px-6 pb-6 pt-2">
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
                                    <span>{{ trans('common.claim_reward') }}</span>
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
                                        {{ $reward->title }}
                                    </div>
                                    @if($reward->description)
                                        <div class="text-xs text-secondary-600 dark:text-secondary-400 leading-relaxed">
                                            {{ $reward->description }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Details Table --}}
                                <div class="space-y-2.5 text-xs mb-4">
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.points_required') }}</span>
                                        <span class="font-semibold text-secondary-900 dark:text-white format-number">{{ $reward->points }}</span>
                                    </div>

                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.member_balance') }}</span>
                                        <span class="font-semibold {{ $memberBalance >= $reward->points ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} format-number">
                                            {{ $memberBalance }}
                                        </span>
                                    </div>

                                    @if($reward->active_from || $reward->expiration_date)
                                        <div class="flex items-start justify-between py-1">
                                            <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.reward_validity') }}</span>
                                            <span class="font-medium text-secondary-900 dark:text-white text-right">
                                                @if($reward->active_from && $reward->expiration_date)
                                                    {{ $reward->active_from->isoFormat('MMM D') }} - {{ $reward->expiration_date->isoFormat('MMM D, YYYY') }}
                                                @elseif($reward->expiration_date)
                                                    {{ trans('common.until') }} {{ $reward->expiration_date->isoFormat('LL') }}
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Status Badge --}}
                                @if($canRedeem)
                                    <div class="inline-flex items-center gap-1.5 px-2 py-1 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-md">
                                        <x-ui.icon icon="check-circle-2" class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                                        <span class="font-medium text-xs text-emerald-700 dark:text-emerald-300">{{ trans('common.member_has_enough_points') }}</span>
                                    </div>
                                @else
                                    <div class="inline-flex items-center gap-1.5 px-2 py-1 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-md">
                                        <x-ui.icon icon="x-circle" class="w-3 h-3 text-rose-600 dark:text-rose-400" />
                                        <span class="font-medium text-xs text-rose-700 dark:text-rose-300">{{ trans('common.member_not_enough_points_for_reward') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            <x-forms.form-close />

            {{-- Cancel Link --}}
            <div class="mt-6 text-center">
                <a href="{{ route('staff.transactions', ['member_identifier' => $member->unique_identifier, 'card_identifier' => $card->unique_identifier]) }}" 
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white transition-colors">
                    <x-ui.icon icon="arrow-left" class="w-4 h-4" />
                    <span>{{ trans('common.cancel_and_view_member_history') }}</span>
                </a>
            </div>
        @else
            {{-- Cannot Redeem Message --}}
            <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl p-6">
                <div class="flex gap-3">
                    <x-ui.icon icon="alert-circle" class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                    <div class="flex-1">
                        <p class="font-semibold text-amber-900 dark:text-amber-100 mb-1">{{ trans('common.not_enough_points') }}</p>
                        <p class="text-sm text-amber-700 dark:text-amber-300">{{ trans('common.member_not_enough_points_for_reward') }}</p>
                    </div>
                </div>
            </div>
        @endif
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
                    <h3 class="text-lg font-semibold text-rose-900 dark:text-rose-300">{{ trans('common.card_not_found') }}</h3>
                </div>
            @endif

            @if(!$reward)
                <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 rounded-xl p-8 text-center">
                    <div class="w-16 h-16 mx-auto rounded-xl bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center mb-4">
                        <x-ui.icon icon="gift" class="w-8 h-8 text-rose-600 dark:text-rose-400" />
                    </div>
                    <h3 class="text-lg font-semibold text-rose-900 dark:text-rose-300">{{ trans('common.reward_not_found') }}</h3>
                </div>
            @endif
        </div>
    @endif
</div>
@stop
