{{--
Premium Loyalty Card Component
Responsive design with credit card aspect ratio (1.586:1)
--}}
<div {{ $attributes->except('class') }} id="{{ $element_id }}" data-bg-color="{{ $bgColor }}"
    data-bg-opacity="{{ $bgColorOpacity / 100 }}" data-bg-image="{{ $bgImage }}"
    class="relative @if ($links) cursor-pointer @endif select-none w-full aspect-[1.586/1] max-w-[700px] rounded-2xl bgColor_{{ $element_id }} textColor_{{ $element_id }} bg-center bg-cover bg-no-repeat overflow-hidden {{ $attributes->get('class') }}">

    {{-- Expired ribbon --}}
    @if ($isExpired)
        <div class="absolute right-2 top-2 z-30 pointer-events-none">
            <span class="px-2 py-1 text-xs font-bold uppercase text-white bg-red-600 rounded shadow-lg">
                {{ trans('common.expired') }}
            </span>
        </div>
    @endif

    {{-- Card content with flex layout to fill space properly --}}
    <div class="flex flex-col justify-between h-full gap-4 p-4 md:p-6">

        {{-- Header: Brand/Title + Balance --}}
        <div class="flex items-start justify-between gap-4">
            <div class="flex-grow min-w-0">
                <div class="flex items-center text-lg font-medium">
                    @if ($customLink)
                        <a href="{{ $customLink }}" class="after:absolute after:inset-0"></a>
                    @endif
                    @if ($icon)
                        <span class="mr-2">
                            <x-ui.icon :icon="$icon" class="textColor_{{ $element_id }} w-5 h-5" />
                        </span>
                    @endif
                    @if ($links && !$customLink)
                        <a href="{{ route('member.card', ['card_id' => $id]) }}" class="after:absolute after:inset-0">
                    @endif
                        @if ($logo)
                            <img class="tracking-tight h-10" src="{{ $logo }}" alt="{{ parse_attr($contentHead) }}">
                        @else
                            <span class="tracking-tight truncate">{{ $contentHead }}</span>
                        @endif
                        @if ($links && !$customLink)
                            </a>
                        @endif
                </div>
            </div>

            <div class="flex-none text-right min-w-[6rem]">
                @if ($authCheck)
                    <div class="text-xs font-extralight textLabelColor_{{ $element_id }}">{{ trans('common.balance') }}
                    </div>
                    <div class="flex items-center justify-end">
                        <x-ui.icon icon="coins" class="textColor_{{ $element_id }} w-4 h-4 mr-1" />
                        @if ($showBalance)
                            <div class="text-lg font-medium format-number">{{ $balance }}</div>
                        @else
                            <div class="text-lg font-medium">-</div>
                        @endif
                    </div>
                @else
                    @if(!$hideLogin)
                        <a rel="nofollow" href="{{ route('member.login') }}"
                            class="text-sm font-medium underline hover:underline after:absolute after:inset-0">
                            {{ trans('common.log_in') }}
                        </a>
                    @endif
                @endif
            </div>
        </div>

        {{-- Content: Description --}}
        <div class="flex items-start gap-4">
            <div class="flex-grow min-w-0">
                <h3 class="text-2xl font-extralight line-clamp-2 mb-2">{{ $contentTitle }}</h3>
                <div class="line-clamp-3 font-light text-sm">{{ $contentDescription }}</div>
            </div>
        </div>

        {{-- Footer: Identifier + Dates --}}
        <div class="flex items-end justify-between gap-4 text-xs">
            <div class="flex-grow min-w-0">
                <div class="font-extralight textLabelColor_{{ $element_id }}">{{ trans('common.identifier') }}</div>
                <div class="font-light truncate">{{ $identifier }}</div>
            </div>
            <div class="flex-none hidden sm:block min-w-[5rem] text-right">
                <div class="font-extralight textLabelColor_{{ $element_id }}">{{ trans('common.issue_date') }}</div>
                <div class="font-light format-date" data-date="{{ $issueDate }}">&nbsp;</div>
            </div>
            <div class="flex-none min-w-[5rem] text-right">
                <div class="font-extralight textLabelColor_{{ $element_id }}">{{ trans('common.expiration_date') }}
                </div>
                <div class="font-light format-date" data-date="{{ $expirationDate }}">&nbsp;</div>
            </div>
        </div>
    </div>
</div>

{{-- Dynamic color styles --}}
@php
    // Convert hex to rgba for iOS Safari compatibility
    $hex = ltrim($bgColor, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $alpha = $bgColorOpacity / 100;
@endphp
<style type="text/css">
    /* Background with anti-aliasing optimization */
    .bgColor_{{ $element_id }} {
        background-color: rgba({{ $r }}, {{ $g }}, {{ $b }}, {{ $alpha }});
        /* Prevent scaling artifacts */
        backface-visibility: hidden;
        transform: translateZ(0);
        -webkit-font-smoothing: subpixel-antialiased;
    }

    .textColor_{{ $element_id }} {
        color: {{ $textColor }};
    }

    .textLabelColor_{{ $element_id }} {
        color: {{ $textLabelColor }};
    }
</style>