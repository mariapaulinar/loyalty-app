{{--
  Staff Points Transaction History

  Design: Follows canonical staff action page pattern (§6.3 design system).
  No ambient effects, no custom keyframes. Clean, content-focused layout.
--}}

@extends('staff.layouts.default')

@section('page_title', trans('common.points_transaction_history') . config('default.page_title_delimiter') . $card->head . config('default.page_title_delimiter') . $member->name . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-lg mx-auto px-4 py-8 md:py-14">
    {{-- Header --}}
    <x-ui.page-header
        icon="coins"
        :title="trans('common.points_transaction_history')"
        compact
    />

    {{-- Messages --}}
    <div class="mt-8 mb-6">
        <x-forms.messages />
    </div>

    {{-- Card Display --}}
    @if($card)
        <div class="mb-6">
            <x-member.card
                :card="$card"
                :member="$member"
                :flippable="false"
                :links="false"
                :show-qr="false"
            />
        </div>
    @endif

    {{-- Add Transaction Button --}}
    @if($card && $member)
        <div class="mb-6">
            <a href="{{ route('staff.earn.points', ['member_identifier' => $member->unique_identifier, 'card_identifier' => $card->unique_identifier]) }}" 
               class="w-full flex items-center justify-center gap-2.5 px-6 py-3.5
                      text-sm font-semibold text-white
                      bg-primary-600 hover:bg-primary-500
                      rounded-xl shadow-sm hover:shadow-md
                      focus:outline-none focus:ring-2 focus:ring-primary-500/20
                      transition-all duration-200 active:scale-[0.98]">
                <x-ui.icon icon="coins" class="w-5 h-5" />
                <span>{{ trans('common.add_transaction') }}</span>
            </a>
        </div>
    @endif

    {{-- Divider --}}
    <div class="relative my-8">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-secondary-200 dark:border-secondary-800"></div>
        </div>
        <div class="relative flex justify-center">
            <span class="px-4 text-xs font-medium text-secondary-500 dark:text-secondary-400 
                         bg-white dark:bg-secondary-950 uppercase tracking-wider">
                {{ trans('common.member') }}
            </span>
        </div>
    </div>

    {{-- Member Card & History --}}
    @if($member)
        <div class="space-y-6">
            <div>
                <x-member.member-card :member="$member" :club="$card?->club" :show-tier="true" />
            </div>
            
            <div>
                <div class="bg-white dark:bg-secondary-900 rounded-2xl 
                            border border-secondary-200 dark:border-secondary-800 
                            overflow-hidden">
                    <div class="px-6 py-4 border-b border-secondary-200 dark:border-secondary-800">
                        <div class="flex items-center gap-3">
                            <x-ui.icon icon="clock" class="w-5 h-5 text-secondary-400" />
                            <div>
                                <h3 class="font-semibold text-secondary-900 dark:text-white">{{ trans('common.transaction_history') }}</h3>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ trans('common.points_earned_and_rewards_claimed') }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <x-member.history :card="$card" :show-notes="true" :show-attachments="true" :member="$member" />
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@stop
