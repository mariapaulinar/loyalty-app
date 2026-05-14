{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Add/Remove Card Component

Add or remove card from "My Cards" with primary/ghost styling.
--}}

@if(!$follows)
    {{-- Add to My Cards Button (Primary - Amber) --}}
    <a {{ $attributes->except(['class', 'href']) }}
       href="{{ route('member.card.follow', ['card_id' => $card->id]) }}"
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
            onclick="removeCard()"
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
        function removeCard() {
            appConfirm(
                @json(trans('common.remove_card'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE),
                @json(trans('common.remove_card_confirm'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE),
                {
                    'btnConfirm': {
                        'text': @json(trans('common.remove'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE),
                        'type': 'danger',
                        'click': function() { 
                            document.location = '{{ route('member.card.unfollow', ['card_id' => $card->id]) }}';
                        }
                    }
                }
            );
        }
    </script>
@endif
