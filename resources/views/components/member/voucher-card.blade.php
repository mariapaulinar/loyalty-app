{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Premium Voucher Card Component - Coupon/Ticket Design
Clean, modern design with subtle dashed edges for that "cut-out coupon" feel.
Every detail matters. Revolut approved.
--}}

@props(['voucher', 'member' => null, 'showCode' => true, 'compact' => false, 'links' => true, 'detailView' => false, 'customLink' => null])

@php
$element_id = 'voucher-' . $voucher->id;

// Check if member is authenticated
$authCheck = $member ? true : auth('member')->check();

// Voucher info
$code = $voucher->code;
$title = $voucher->title ?: $voucher->name;
$description = $voucher->description;
$type = $voucher->type;
$formattedValue = $voucher->formatted_value;
    $styledValue = ($type === 'fixed_amount') ? moneyFormatStyled($voucher->value / 100, $voucher->currency) : null;

// Status checks
$isExpired = $voucher->is_expired;
$isExhausted = $voucher->is_exhausted;
$isNotYetValid = $voucher->is_not_yet_valid;
$isValid = $voucher->is_valid;
$remainingUses = $voucher->remaining_uses;

// Expiry warning (expires within 7 days)
$isExpiringSoon = $voucher->valid_until && !$isExpired && now()->addDays(7)->isAfter($voucher->valid_until);

// Visual styling - Media lookup handled automatically at model level
// For batch vouchers, getMediaUrl() automatically uses the representative voucher (storage optimization)
$bgImage = $voucher->getMediaUrl('background');
$logo = $voucher->getMediaUrl('logo');
$bgColor = $voucher->bg_color ?: '#7C3AED'; // Purple
$bgColorOpacity = $voucher->bg_color_opacity ?? 85;
$textColor = $voucher->text_color ?: '#FFFFFF';

// Type-specific styling
$typeConfig = match($type) {
    'percentage' => ['icon' => 'percent', 'color' => '#A78BFA', 'label' => __('common.percentage_off')],
    'fixed_amount' => ['icon' => 'banknote', 'color' => '#10B981', 'label' => __('common.fixed_amount_off')],
    'free_product' => ['icon' => 'gift', 'color' => '#EC4899', 'label' => __('common.free_product')],
    'free_shipping' => ['icon' => 'truck', 'color' => '#2563EB', 'label' => __('common.free_shipping')],
    'bonus_points' => ['icon' => 'star', 'color' => '#F59E0B', 'label' => __('common.bonus_points')],
    default => ['icon' => 'tag', 'color' => '#9CA3AF', 'label' => $type],
};

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

// URL for QR code (if member is authenticated and voucher is valid)
// Use explicitly passed $member first (for staff views), fallback to authenticated member
$memberIdentifier = $member?->unique_identifier ?? auth('member')->user()?->unique_identifier;
$urlToRedeem = ($authCheck && $memberIdentifier && $isValid)
    ? route('staff.vouchers.redeem.show', ['member_identifier' => $memberIdentifier, 'voucher_id' => $voucher->id])
    : '';
@endphp

<div {{ $attributes->except('class') }} id="{{ $element_id }}"
    class="voucher-card premium-card group relative w-full h-full max-w-[700px] mx-auto rounded-3xl overflow-hidden shadow-xl select-none @container {{ $attributes->get('class') }}"
    @if($authCheck && $urlToRedeem)
        data-pwa-card
        data-pwa-card-id="voucher-{{ $voucher->id }}"
        data-pwa-card-type="voucher"
        data-pwa-card-name="{{ $title }}"
        data-pwa-card-balance="{{ $formattedValue }}"
        data-pwa-card-qr="{{ $urlToRedeem }}"
    @endif
>

    {{-- Clickable Wrapper --}}
    @if ($links)
        @if ($customLink)
            <a href="{{ $customLink }}" class="card-hit absolute inset-0 z-20"></a>
        @elseif ($detailView && !$authCheck)
            <a href="{{ route('member.login') }}" class="card-hit absolute inset-0 z-20"></a>
        @elseif (!$detailView)
            <a href="{{ route('member.voucher', ['voucher_id' => $voucher->id]) }}" class="card-hit absolute inset-0 z-20"></a>
        @endif
    @endif

    {{-- Background Layer - Pixel-perfect rendering --}}
    <div class="absolute inset-0 z-0 rounded-3xl overflow-hidden">
        {{-- Background Image with proper containment --}}
        @if ($bgImage)
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat rounded-3xl transform-gpu" 
             style="background-image: url('{{ $bgImage }}'); will-change: auto;"></div>
        @endif

        {{-- Colored Overlay - uses rgba for iOS Safari compatibility --}}
        <div class="absolute inset-0 bgColorOverlay_{{ $element_id }} rounded-3xl"></div>
    </div>

    {{-- Status Ribbons --}}
    @if ($isExpired)
        <div class="absolute end-2 top-2 z-30 pointer-events-none">
            <span class="px-2 py-1 text-xs font-bold uppercase text-white bg-red-600 rounded shadow-lg">
                {{ __('common.expired') }}
            </span>
        </div>
    @elseif ($isExhausted)
        <div class="absolute end-2 top-2 z-30 pointer-events-none">
            <span class="px-2 py-1 text-xs font-bold uppercase text-white bg-secondary-600 rounded shadow-lg">
                {{ __('common.voucher_exhausted') }}
            </span>
        </div>
    @elseif ($isExpiringSoon)
        <div class="absolute end-2 top-2 z-30 pointer-events-none">
            <span class="px-2 py-1 text-xs font-bold uppercase text-white bg-amber-600 rounded shadow-lg animate-pulse-gentle">
                {{ __('common.ending_soon') }}
            </span>
        </div>
    @endif

    {{-- Card Content --}}
    <div class="relative z-10 flex flex-col h-full p-5 @[400px]:p-6 @[500px]:p-8 textColor_{{ $element_id }}">

        {{-- Header: Title/Description (Left) + Discount (Right) --}}
        <div class="flex items-start justify-between gap-3 mb-4 @[500px]:mb-6">
            {{-- Left: Logo/Title + Description --}}
            <div class="flex-1 min-w-0 max-w-[60%]">
                @if ($logo)
                    <img class="h-8 @[400px]:h-10 @[500px]:h-12 object-contain object-start drop-shadow-lg"
                        src="{{ $logo }}"
                        alt="{{ parse_attr($title) }}">
                    <div class="mb-3 @[500px]:mb-4"></div>
                @else
                    <h3
                        class="text-base @[400px]:text-lg @[500px]:text-xl font-bold tracking-tight leading-snug line-clamp-2 text-start mb-3 @[500px]:mb-4 {{ $shadowClass }}">
                        {{ html_entity_decode($title) }}
                    </h3>
                @endif

                @if ($description)
                    <p class="text-xs @[400px]:text-sm @[500px]:text-base font-medium opacity-90 leading-relaxed line-clamp-2 text-start">
                        {{ html_entity_decode($description) }}
                    </p>
                @endif
            </div>
            {{-- Discount Value (Large, Prominent) --}}
            <div class="flex-none text-end relative z-30 ms-auto max-w-[35%]">
                <div class="text-[9px] @[400px]:text-[10px] font-bold uppercase tracking-widest opacity-60 mb-1">
                    {{ $typeConfig['label'] }}
                </div>
                @if($type === 'free_product')
                    <div class="text-lg @[400px]:text-xl @[500px]:text-2xl font-black tracking-tight leading-tight line-clamp-2 {{ $shadowClass }}">
                        {{ html_entity_decode($formattedValue) }}
                    </div>
                @elseif($styledValue)
                    <div class="text-3xl @[400px]:text-4xl @[500px]:text-5xl font-black tracking-tighter {{ $shadowClass }}">
                        {!! $styledValue !!}
                    </div>
                @else
                    <div class="text-3xl @[400px]:text-4xl @[500px]:text-5xl font-black tracking-tighter {{ $shadowClass }}">
                        {{ html_entity_decode($formattedValue) }}
                    </div>
                @endif
                @if($voucher->max_discount_amount && $type === 'percentage')
                    <div class="text-[10px] @[500px]:text-xs opacity-75 mt-1">
                        {{ __('common.up_to') }} {{ moneyFormat($voucher->max_discount_amount / 100, $voucher->currency) }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Spacer to keep footer pinned to bottom --}}
        <div class="flex-1"></div>

        {{-- Footer: Code + Expiry (no shadow on small details) --}}
        <div class="flex items-center justify-between gap-4 pt-4 border-t border-white/20 mt-auto">
            @if($showCode)
                {{-- Voucher Code (Monospace, Large) --}}
                <div class="min-w-0">
                    <div class="text-[9px] @[500px]:text-[10px] font-bold uppercase tracking-widest opacity-60 mb-0.5">
                        {{ __('common.code') }}
                    </div>
                    <div class="font-mono text-base @[500px]:text-lg font-bold tracking-[0.2em] truncate relative z-30">{{ $code }}</div>
                </div>
            @else
                {{-- Minimum Purchase (if applicable) --}}
                @if($voucher->min_purchase_amount)
                    <div class="min-w-0">
                        <div class="text-[9px] @[500px]:text-[10px] font-bold uppercase tracking-widest opacity-60 mb-0.5">
                            {{ __('common.min_purchase') }}
                        </div>
                        <div class="text-sm @[500px]:text-base font-semibold">{{ moneyFormat($voucher->min_purchase_amount / 100, $voucher->currency) }}</div>
                    </div>
                @else
                    <div></div>
                @endif
            @endif

            {{-- Expiry / Uses Remaining --}}
            <div class="text-end">
                @if($remainingUses !== null)
                    <div class="text-[9px] @[500px]:text-[10px] font-bold uppercase tracking-widest opacity-60 mb-0.5">
                        {{ __('common.uses_left') }}
                    </div>
                    <div class="font-mono text-sm @[500px]:text-base font-bold format-number">{{ $remainingUses }}</div>
                @elseif($voucher->valid_until)
                    <div class="text-[9px] @[500px]:text-[10px] font-bold uppercase tracking-widest opacity-60 mb-0.5">
                        {{ __('common.expires') }}
                    </div>
                    <div class="font-mono text-xs @[500px]:text-sm format-date" data-date="{{ $voucher->valid_until }}">{{ $voucher->valid_until->format('M d, Y') }}</div>
                @else
                    <div class="text-[9px] @[500px]:text-[10px] font-bold uppercase tracking-widest opacity-60 mb-0.5">
                        {{ __('common.validity') }}
                    </div>
                    <div class="text-xs @[500px]:text-sm font-semibold">{{ __('common.unlimited') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

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

<style>
    #{{ $element_id }} {
        --card-accent-rgb: {{ $r }}, {{ $g }}, {{ $b }};
    }

    /* Background overlay - smooth gradient for depth */
    .bgColorOverlay_{{ $element_id }} {
        background: linear-gradient(135deg, 
            rgba({{ $r }}, {{ $g }}, {{ $b }}, {{ $alpha }}) 0%,
            rgba({{ $r }}, {{ $g }}, {{ $b }}, {{ max(0, $alpha - 0.05) }}) 50%,
            rgba({{ $r }}, {{ $g }}, {{ $b }}, {{ $alpha }}) 100%
        );
        /* Prevent scaling artifacts */
        backface-visibility: hidden;
        transform: translateZ(0);
        -webkit-font-smoothing: subpixel-antialiased;
    }

    .textColor_{{ $element_id }} {
        color: {{ $textColor }};
    }
    
    /* Gentle pulse for expiring soon */
    @keyframes pulseGentle {
        0%, 100% {
            opacity: 0.8;
            transform: scale(1);
        }
        50% {
            opacity: 1;
            transform: scale(1.05);
        }
    }
    
    .animate-pulse-gentle {
        animation: pulseGentle 2s ease-in-out infinite;
    }
    
    /* Accessibility: Respect prefers-reduced-motion */
    @media (prefers-reduced-motion: reduce) {
        .animate-pulse-gentle {
            animation: none;
        }
    }
    /* Premium currency display — Apple-grade superscript cents
       Positioned at cap-height of main digits, not floating above.
       Size ratio tuned for readability at all responsive breakpoints. */
    #{{ $element_id }} .money-cents {
        font-size: 0.58em;
        vertical-align: baseline;
        position: relative;
        top: -0.55em;
        font-weight: inherit;
        opacity: 0.9;
        letter-spacing: 0.01em;
        line-height: 1;
        margin-left: 0.04em;
    }
</style>
