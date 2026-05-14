{{--
Premium Loyalty Card Component
Clean, crisp, Revolut-style design.
No weird filters, just pure content and perfect typography.

Uses CSS container queries to scale text based on card width:
- In grids: compact text
- Standalone: larger, more prominent text
--}}
@php
    // Calculate shadow class based on TEXT color (not background - users set text color explicitly)
    // Light text (white) needs dark shadow, dark text needs light shadow
    $textHex = ltrim($textColor, '#');
    if (strlen($textHex) === 3) {
        $textHex = $textHex[0].$textHex[0].$textHex[1].$textHex[1].$textHex[2].$textHex[2];
    }
    $textR = hexdec(substr($textHex, 0, 2));
    $textG = hexdec(substr($textHex, 2, 2));
    $textB = hexdec(substr($textHex, 4, 2));
    $textLuminance = (0.299 * $textR) + (0.587 * $textG) + (0.114 * $textB);
    // Light text (like white) gets black shadow for contrast; dark text gets white shadow
    $shadowClass = $textLuminance > 128 ? 'text-shadow-subtle-black' : 'text-shadow-subtle-white';
@endphp
<div {{ $attributes->except('class') }} id="{{ $element_id }}"
    class="premium-card group relative w-full h-full max-w-[700px] mx-auto rounded-3xl overflow-hidden select-none @container {{ $attributes->get('class') }}"
    @if($authCheck && $urlToEarnPoints)
        data-pwa-card
        data-pwa-card-id="{{ $id }}"
        data-pwa-card-type="loyalty"
        data-pwa-card-name="{{ $contentHead }}"
        data-pwa-card-balance="{{ $balance }} {{ trans('common.points') }}"
        data-pwa-card-qr="{{ $urlToEarnPoints }}"
    @endif
>

    {{-- Clickable Wrapper --}}
    @if ($links)
        @if (($customLink ?? null))
            <a href="{{ $customLink }}" class="card-hit absolute inset-0 z-20"></a>
        @elseif ($detailView && !$authCheck)
            {{-- Detail view + not logged in: clicking card goes to login --}}
            <a href="{{ route('member.login') }}" class="card-hit absolute inset-0 z-20"></a>
        @elseif (!$detailView)
            {{-- List view: clicking card goes to card detail --}}
            <a href="{{ route('member.card', ['card_id' => $id]) }}" class="card-hit absolute inset-0 z-20"></a>
        @endif
    @endif

    {{-- Background Layer - Pixel-perfect rendering --}}
    <div class="absolute inset-0 z-0 rounded-3xl overflow-hidden">
        {{-- Background Image with proper containment --}}
        @if ($bgImage)
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat rounded-3xl transform-gpu" 
             style="background-image: url('{{ $bgImage }}'); will-change: auto;">
        </div>
        @endif

        {{-- Colored Overlay - uses rgba for iOS Safari compatibility --}}
        <div class="absolute inset-0 bgColorOverlay_{{ $element_id }} rounded-3xl"></div>

        {{-- Premium Gradient Overlay - Subtle bottom fade for text legibility --}}
        <div class="absolute inset-0 rounded-3xl bg-gradient-to-t from-black/20 via-transparent to-transparent"></div>
    </div>

    {{-- Expired Ribbon --}}
    @if ($isExpired)
        <div class="absolute right-2 top-2 z-30 pointer-events-none">
            <span class="px-2 py-1 text-xs font-bold uppercase text-white bg-red-600 rounded shadow-lg">
                {{ trans('common.expired') }}
            </span>
        </div>
    @endif

    {{-- Card Content - uses flex to push footer to bottom --}}
    <div class="relative z-10 flex flex-col h-full p-5 @[400px]:p-6 @[500px]:p-8 textColor_{{ $element_id }}">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-3 mb-4 @[500px]:mb-6">
            {{-- Brand --}}
            <div class="flex items-center gap-2 @[500px]:gap-3 min-w-0 relative z-30">
                @if ($icon)
                    <div class="flex-none">
                        <x-ui.icon :icon="$icon" class="w-5 h-5 @[500px]:w-6 @[500px]:h-6 textColor_{{ $element_id }}" />
                    </div>
                @endif

                <div class="flex flex-col min-w-0">
                    @if ($logo)
                        <img class="h-6 @[400px]:h-7 @[500px]:h-8 object-contain object-left drop-shadow-md" src="{{ $logo }}" alt="{{ parse_attr($contentHead) }}">
                    @else
                        <span class="text-sm @[400px]:text-base @[500px]:text-lg font-bold tracking-tight truncate {{ $shadowClass }}">{{ html_entity_decode($contentHead) }}</span>
                    @endif
                    
                    {{-- Tier Badge --}}
                    @if ($memberTier && $tierMultiplier > 1)
                        <div class="mt-1.5 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/20 backdrop-blur-sm border border-white/30 w-fit">
                            <span class="text-sm">{{ $tierIcon }}</span>
                            <span class="text-xs font-bold tracking-wide">{{ number_format($tierMultiplier, 1) }}×</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Balance / Earn Points Indicator --}}
            @if ($authCheck && $showBalance)
                {{-- Logged in + showBalance: Show balance --}}
                <div class="flex-none relative z-30">
                    <div class="text-right">
                        <div class="text-[9px] @[500px]:text-[10px] font-bold uppercase tracking-widest opacity-70 mb-0.5">
                            {{ trans('common.balance') }}
                        </div>
                        <div class="flex items-center justify-end gap-1 @[500px]:gap-1.5 {{ $shadowClass }}">
                            <x-ui.icon icon="coins" class="w-3.5 h-3.5 @[500px]:w-4 @[500px]:h-4 opacity-90" />
                            <span class="text-base @[400px]:text-lg @[500px]:text-xl font-mono font-bold tracking-tight format-number">{{ $balance }}</span>
                        </div>
                    </div>
                </div>
            @elseif (!$authCheck)
                {{-- Not logged in: Inviting "Earn Points" indicator --}}
                <div class="flex-none relative z-30">
                    <div class="group/earn relative flex items-center gap-1.5 @[500px]:gap-2 px-2.5 py-1.5 @[500px]:px-3 @[500px]:py-2 
                                rounded-xl bg-white/20 backdrop-blur-md border border-white/30
                                transition-all duration-300 ease-out
                                hover:bg-white/30 hover:border-white/50 hover:scale-105">
                        {{-- Shimmer effect --}}
                        <span class="absolute inset-0 rounded-xl overflow-hidden pointer-events-none">
                            <span class="absolute inset-0 bg-linear-to-r from-transparent via-white/30 to-transparent
                                         -translate-x-full group-hover/earn:translate-x-full 
                                         transition-transform duration-700 ease-out"></span>
                        </span>
                        {{-- Coins icon --}}
                        <x-ui.icon icon="coins" class="relative w-3.5 h-3.5 @[500px]:w-4 @[500px]:h-4 drop-shadow-sm" />
                        {{-- Text --}}
                        <span class="relative text-[10px] @[500px]:text-xs font-semibold tracking-wide whitespace-nowrap drop-shadow-sm">
                            {{ trans('common.earn_points') }}
                        </span>
                    </div>
                </div>
            @endif
            {{-- authCheck && !showBalance: Show nothing (for partner dashboard previews) --}}
        </div>

        {{-- Body - flex-1 pushes footer to bottom --}}
        <div class="flex-1 mb-4 @[500px]:mb-8 pointer-events-none">
            <h3 class="text-lg @[400px]:text-xl @[500px]:text-2xl @[600px]:text-3xl font-light tracking-tight mb-2 @[500px]:mb-3 leading-snug {{ $shadowClass }}">{{ html_entity_decode($contentTitle) }}</h3>
            <p class="text-xs @[400px]:text-sm @[500px]:text-base font-normal opacity-90 leading-relaxed line-clamp-2 @[500px]:line-clamp-none">{{ html_entity_decode($contentDescription) }}</p>
        </div>

        {{-- Footer - always at bottom (no shadow on small details) --}}
        <div class="flex items-center justify-between gap-4 @[500px]:gap-6 pt-4 @[500px]:pt-6 border-t border-white/20 pointer-events-none mt-auto">
            <div>
                <div class="text-[9px] @[500px]:text-[10px] font-bold uppercase tracking-widest opacity-60 mb-0.5 @[500px]:mb-1">
                    {{ trans('common.identifier') }}
                </div>
                <div class="font-mono text-xs @[500px]:text-sm tracking-wider">{{ $identifier }}</div>
            </div>

            <div class="text-right">
                <div class="text-[9px] @[500px]:text-[10px] font-bold uppercase tracking-widest opacity-60 mb-0.5 @[500px]:mb-1">
                    {{ trans('common.expiration_date') }}
                </div>
                <div class="font-mono text-xs @[500px]:text-sm tracking-wider format-date" data-date="{{ $expirationDate }}">&nbsp;</div>
            </div>
        </div>
    </div>
</div>

@php
    // Convert hex to rgba for iOS Safari compatibility (opacity property can be buggy on iOS)
    $hex = ltrim($bgColor, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $alpha = $bgColorOpacity / 100;
@endphp
<style>
    #{{ $element_id }} {
        --card-accent-rgb: {{ $r }}, {{ $g }}, {{ $b }};
    }

    /* Background overlay with anti-aliasing optimization */
    .bgColorOverlay_{{ $element_id }} {
        background-color: rgba({{ $r }}, {{ $g }}, {{ $b }}, {{ $alpha }});
        /* Prevent scaling artifacts */
        backface-visibility: hidden;
        transform: translateZ(0);
        -webkit-font-smoothing: subpixel-antialiased;
    }

    .textColor_{{ $element_id }} {
        color: {{ $textColor }};
    }
</style>
