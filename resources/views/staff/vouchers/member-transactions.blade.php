{{--
  Staff Voucher Transaction History Per Member

  Design: Follows canonical staff action page pattern (§6.3 design system).
  No ambient effects, no custom keyframes.
--}}

@extends('staff.layouts.default')

@section('page_title', trans('common.voucher_redemption_history') . config('default.page_title_delimiter') . ($member ? $member->name : '') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-lg mx-auto px-4 py-8 md:py-14">
    @if($member && $voucher)
        {{-- Header --}}
        <x-ui.page-header
            icon="ticket"
            :title="trans('common.voucher_redemption_history')"
            :description="$voucher->code"
            compact
        />

        {{-- Messages --}}
        <div class="mt-8 mb-6">
            <x-forms.messages />
        </div>

        {{-- Voucher Card Display --}}
        <div class="mb-6">
            <x-member.voucher-card
                :voucher="$voucher"
                :member="$member"
                :show-balance="true"
                :links="false"
            />
        </div>

        {{-- Redeem Voucher Button --}}
        <div class="mb-6">
            <a href="{{ route('staff.vouchers.redeem.show', ['member_identifier' => $member->unique_identifier, 'voucher_id' => $voucher->id]) }}" 
               class="w-full flex items-center justify-center gap-2.5 px-6 py-3.5
                      text-sm font-semibold text-white
                      bg-primary-600 hover:bg-primary-500
                      rounded-xl shadow-sm hover:shadow-md
                      focus:outline-none focus:ring-2 focus:ring-primary-500/20
                      transition-all duration-200 active:scale-[0.98]">
                <x-ui.icon icon="scan" class="w-5 h-5" />
                <span>{{ trans('common.redeem_voucher') }}</span>
            </a>
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
                <x-member.member-card :member="$member" />
            </div>
            
            <div>
                <div class="bg-white dark:bg-secondary-900 rounded-2xl 
                            border border-secondary-200 dark:border-secondary-800 
                            overflow-hidden">
                    <div class="px-6 py-4 border-b border-secondary-200 dark:border-secondary-800">
                        <div class="flex items-center gap-3">
                            <x-ui.icon icon="clock" class="w-5 h-5 text-secondary-400" />
                            <div>
                                <h3 class="font-semibold text-secondary-900 dark:text-white">{{ trans('common.redemption_history') }}</h3>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ trans('common.member_redemptions_for_voucher') }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <x-member.voucher-history 
                            :member="$member" 
                            :voucher="$voucher"
                            :show-notes="true"
                            :show-staff="true" />
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Error State --}}
        <div class="space-y-6">
            <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 rounded-xl p-8 text-center">
                <div class="w-16 h-16 mx-auto rounded-xl bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center mb-4">
                    <x-ui.icon icon="user-x" class="w-8 h-8 text-rose-600 dark:text-rose-400" />
                </div>
                <h3 class="text-lg font-semibold text-rose-900 dark:text-rose-300">{{ trans('common.member_not_found') }}</h3>
            </div>
        </div>
    @endif
</div>
@stop
