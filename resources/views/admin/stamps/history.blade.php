{{--
  Admin Stamp Card History

  Design: Follows canonical admin action page pattern (§6.3 design system).
  No ambient effects, no custom keyframes.
  Card containers: rounded-2xl, border-only. Section headers: bare icons.
--}}

@extends('admin.layouts.default')

@section('page_title', trans('common.history') . config('default.page_title_delimiter') . ($card ? $card->name : '') . config('default.page_title_delimiter') . ($member ? $member->name : '') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-lg mx-auto px-4 py-8 md:py-14">
    @if($member && $card)
        {{-- Page Header --}}
        @php
            $stampIcon = $card->stamp_icon ?? '☕';
            $isEmoji = preg_match('/[^\x00-\x7F]/', $stampIcon);
            $iconToUse = $isEmoji ? 'stamp' : $stampIcon;
        @endphp
        
        <x-ui.page-header
            :icon="$iconToUse"
            :title="trans('common.stamp_card_history')"
            :description="$card->name"
            :breadcrumbs="[
                ['url' => route('admin.index'), 'icon' => 'home', 'title' => trans('common.home')],
                ['url' => route('admin.data.list', ['name' => 'members']), 'text' => trans('common.members')],
                ['text' => trans('common.stamp_card')],
                ['url' => route('member.stamp-card', ['stamp_card_id' => $card->id]), 'text' => trans('common.view_card'), 'target' => '_blank']
            ]"
            compact
        />

        {{-- Messages --}}
        <div class="mt-8 mb-6">
            <x-forms.messages />
        </div>

        {{-- Card Display --}}
        <div class="mb-6">
            <x-member.stamp-card
                :stamp-card="$card"
                :show-balance="true"
                :links="false"
            />
        </div>

        {{-- Quick Actions --}}
        <div class="flex gap-3 mb-6">
            <a href="{{ route('member.stamp-card', ['stamp_card_id' => $card->id]) }}" 
               target="_blank"
               class="flex-1 flex items-center justify-center gap-2 px-4 py-3 
                      bg-white dark:bg-secondary-800 
                      border border-secondary-200 dark:border-secondary-700 
                      rounded-xl text-sm font-medium 
                      text-secondary-700 dark:text-secondary-300 
                      hover:bg-secondary-50 dark:hover:bg-secondary-700 
                      hover:border-secondary-300 dark:hover:border-secondary-600 
                      shadow-sm transition-all duration-200">
                <x-ui.icon icon="external-link" class="w-4 h-4" />
                {{ trans('common.view_card') }}
            </a>
            
            {{-- Delete Last Stamp --}}
            <div x-data="{ confirming: false }" class="flex-1">
                {{-- Initial State --}}
                <button @click="confirming = true" 
                        x-show="!confirming"
                        class="group w-full flex items-center justify-center gap-2 px-4 py-3 
                               bg-white dark:bg-secondary-800 
                               border border-secondary-200 dark:border-secondary-700 
                               rounded-xl text-sm font-medium 
                               text-secondary-700 dark:text-secondary-300 
                               hover:bg-rose-50 dark:hover:bg-rose-950/20 
                               hover:border-rose-200 dark:hover:border-rose-900/50 
                               hover:text-rose-600 dark:hover:text-rose-400 
                               shadow-sm transition-all duration-200">
                    <x-ui.icon icon="trash" class="w-4 h-4" />
                    {{ trans('common.delete_last_stamp') }}
                </button>
                
                {{-- Confirmation State --}}
                <div x-show="confirming"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-cloak
                     class="flex items-center gap-2 p-1 
                            bg-rose-50 dark:bg-rose-950/30 
                            border-2 border-rose-200 dark:border-rose-900 
                            rounded-xl">
                    <button @click="confirming = false"
                            class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2.5 
                                   text-sm font-medium 
                                   text-secondary-600 dark:text-secondary-400 
                                   hover:text-secondary-900 dark:hover:text-white 
                                   hover:bg-white dark:hover:bg-secondary-800 
                                   rounded-lg transition-all duration-150">
                        <x-ui.icon icon="x" class="w-4 h-4" />
                        {{ trans('common.cancel') }}
                    </button>
                    <a href="{{ route('admin.delete.last.stamp', ['member_identifier' => $member->unique_identifier, 'stamp_card_id' => $card->id]) }}"
                       class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2.5 
                              bg-rose-600 hover:bg-rose-500 
                              text-white text-sm font-semibold rounded-lg 
                              shadow-sm hover:shadow-md 
                              transition-all duration-150 active:scale-[0.98]">
                        <x-ui.icon icon="trash" class="w-4 h-4" />
                        {{ trans('common.confirm') }}
                    </a>
                </div>
            </div>
        </div>

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
        <div class="space-y-6">
            <div>
                <x-member.member-card :member="$member" :club="$card->club" :show-tier="true" />
            </div>
            
            <div>
                <div class="bg-white dark:bg-secondary-900 rounded-2xl 
                            border border-secondary-200 dark:border-secondary-800 
                            overflow-hidden">
                    {{-- History Header --}}
                    <div class="px-6 py-4 border-b border-secondary-200 dark:border-secondary-800">
                        <div class="flex items-center gap-3">
                            <x-ui.icon icon="clock" class="w-5 h-5 text-secondary-400" />
                            <div>
                                <h3 class="font-semibold text-secondary-900 dark:text-white">{{ trans('common.history') }}</h3>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ trans('common.stamps_earned_and_rewards_claimed') }}</p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- History Content --}}
                    <div class="p-6">
                        <x-member.stamp-history :stamp-card="$card" :member="$member" :show-notes="true" :show-attachments="true" :show-staff="true" />
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Error States --}}
        <div class="space-y-6">
            @if(!$member)
                <div class="bg-rose-50 dark:bg-rose-950/30 
                            border border-rose-200 dark:border-rose-900 
                            rounded-xl p-8 text-center">
                    <div class="w-16 h-16 mx-auto rounded-xl 
                                bg-rose-100 dark:bg-rose-900/50 
                                flex items-center justify-center mb-4">
                        <x-ui.icon icon="user-x" class="w-8 h-8 text-rose-600 dark:text-rose-400" />
                    </div>
                    <h3 class="text-lg font-semibold text-rose-900 dark:text-rose-300">{{ trans('common.member_not_found') }}</h3>
                </div>
            @endif

            @if(!$card)
                <div class="bg-rose-50 dark:bg-rose-950/30 
                            border border-rose-200 dark:border-rose-900 
                            rounded-xl p-8 text-center">
                    <div class="w-16 h-16 mx-auto rounded-xl 
                                bg-rose-100 dark:bg-rose-900/50 
                                flex items-center justify-center mb-4">
                        <x-ui.icon icon="credit-card" class="w-8 h-8 text-rose-600 dark:text-rose-400" />
                    </div>
                    <h3 class="text-lg font-semibold text-rose-900 dark:text-rose-300">{{ trans('common.stamp_card_not_found') }}</h3>
                </div>
            @endif
        </div>
    @endif
</div>
@stop
