{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Add/Remove Voucher Component
  
  Add or remove voucher from "My Cards" with primary/ghost styling.
  Only shown for not publicly visible vouchers (is_visible_by_default = false).
--}}

@php
    $member = auth('member')->user();
    // Check full eligibility, not just is_active
    $canSave = $voucher->is_active && $voucher->is_valid;

    // Don't show button if targeted to a different member
    if ($canSave && $member && $voucher->target_member_id && $voucher->target_member_id !== $member->id) {
        $canSave = false;
    }

    // Don't show button if limited-use voucher is fully claimed by other members
    if ($canSave && $voucher->max_uses_total !== null) {
        $claimCount = $voucher->members()->count();
        // Allow if this member already has it (they see "Remove" instead)
        $memberHasIt = $member && $member->vouchers()->where('vouchers.id', $voucher->id)->exists();
        if (!$memberHasIt && $claimCount >= $voucher->max_uses_total) {
            $canSave = false;
        }
    }

    // Check if saved via manual "Add to My Cards" (claimed_via is null)
    $saved = $member && $member->vouchers()
        ->where('vouchers.id', $voucher->id)
        ->wherePivot('claimed_via', null) // Only count manual saves, not auto-redeemed
        ->exists();
@endphp

@if($canSave)
    @if(!$saved)
        {{-- Add to My Cards Button (Primary - Amber) --}}
        <a {{ $attributes->except(['class', 'href']) }}
           href="{{ route('member.voucher.save', ['voucher_id' => $voucher->id]) }}"
           rel="nofollow"
           class="group relative inline-flex items-center justify-center gap-2 px-6 py-3.5 w-full
                  bg-gradient-to-r from-amber-500 to-orange-500 
                  hover:from-amber-400 hover:to-orange-400 
                  text-white font-semibold text-base
                  rounded-xl shadow-md shadow-amber-500/20
                  hover:shadow-lg hover:shadow-amber-500/30
                  focus:outline-none focus:ring-2 focus:ring-amber-500/20
                  transition-all duration-200 {{ $attributes->get('class') }}">
            <x-ui.icon icon="plus-circle" class="w-5 h-5 group-hover:scale-110 transition-transform" />
            <span>{{ trans('common.add_to_my_cards') }}</span>
        </a>
    @else
        {{-- Remove from My Cards Button (Ghost - Text Only) --}}
        <button {{ $attributes->except('class') }}
                type="button"
                onclick="removeVoucher()"
                class="group relative inline-flex items-center justify-center gap-2 px-4 py-2.5 w-full
                       text-secondary-500 dark:text-secondary-400 
                       hover:text-secondary-700 dark:hover:text-secondary-300
                       font-medium text-sm
                       focus:outline-none focus:ring-2 focus:ring-secondary-500/20
                       transition-colors duration-200 {{ $attributes->get('class') }}">
            <x-ui.icon icon="x-circle" class="w-4 h-4 opacity-70 group-hover:opacity-100 transition-opacity" />
            <span>{{ trans('common.remove_from_my_cards') }}</span>
        </button>

    <script>
        function removeVoucher() {
            appConfirm(
                @json(trans('common.remove_card'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE),
                @json(trans('common.remove_card_confirm'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE),
                {
                    'btnConfirm': {
                        'text': @json(trans('common.remove'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE),
                        'type': 'danger',
                        'click': function() { 
                            document.location = '{{ route('member.voucher.unsave', ['voucher_id' => $voucher->id]) }}';
                        }
                    }
                }
            );
        }
    </script>
    @endif
@endif
