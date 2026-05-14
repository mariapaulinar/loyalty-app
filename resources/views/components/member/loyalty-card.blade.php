{{--
    Loyalty Card Component
    
    A premium, reusable loyalty card display inspired by Apple Wallet and Revolut.
    Features gradient backgrounds, balance display, and optional QR code.
    
    @props
    - card: Card model - The loyalty card object
    - size: 'sm'|'md'|'lg'|'full' (default: 'md')
    - showQr: boolean - Display QR code (default: false)
    - clickable: boolean - Make card clickable (default: true)
    - animated: boolean - Add hover animations (default: true)
    
    @example
    <x-member.loyalty-card :card="$card" size="lg" />
--}}

@props([
    'card',
    'size' => 'md',
    'showQr' => false,
    'clickable' => true,
    'animated' => true,
])

@php
$sizes = [
    'sm' => 'w-64 h-40',
    'md' => 'w-80 h-48',
    'lg' => 'w-96 h-56',
    'full' => 'w-full h-64',
];

$cardSize = $sizes[$size] ?? $sizes['md'];
$balance = $card->getMemberBalance(auth('member')->user());

// Fetch member's tier for this card's club
$member = auth('member')->user();
$memberTier = null;
$tierIcon = null;
$tierColor = null;
$tierMultiplier = 1.00;

if ($member && $card && $card->club) {
    $activeTier = $member->memberTiers()
        ->forClub($card->club)
        ->active()
        ->with('tier')
        ->first();
    
    // Only show tier if member actually qualifies for it (has enough points)
    if ($activeTier && $activeTier->tier) {
        $pointsThreshold = $activeTier->tier->points_threshold ?? 0;
        
        // Only display tier if member has met the points threshold
        if ($balance >= $pointsThreshold) {
            $memberTier = $activeTier;
            $tierIcon = $activeTier->tier->icon ?? '🥉';
            $tierColor = $activeTier->tier->color ?? '#2563EB';
            $tierMultiplier = $activeTier->tier->points_multiplier ?? 1.00;
        }
    }
}
@endphp

<div class="{{ $clickable ? 'cursor-pointer' : '' }} {{ $cardSize }} group">
    <div class="relative h-full rounded-3xl overflow-hidden shadow-xl {{ $animated ? 'transition-all duration-500 group-hover:scale-[1.02] group-hover:shadow-2xl' : '' }}"
         style="background: linear-gradient(135deg, {{ $card->bg_color }} 0%, {{ $card->bg_color }}dd 100%);">
        
        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-10 {{ $animated ? 'transition-opacity duration-500 group-hover:opacity-20' : '' }}">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white rounded-full -translate-y-1/2 translate-x-1/2 {{ $animated ? 'transition-transform duration-700 group-hover:scale-150' : '' }}"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-white rounded-full translate-y-1/2 -translate-x-1/2 {{ $animated ? 'transition-transform duration-700 group-hover:scale-150' : '' }}"></div>
        </div>

        {{-- Card Content --}}
        <div class="relative z-10 h-full p-6 flex flex-col justify-between text-white">
            {{-- Header --}}
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-white/80 text-xs font-medium uppercase tracking-wider mb-1">
                        {{ trans('common.loyalty_cards') }}
                    </p>
                    <h3 class="text-xl font-bold {{ $animated ? 'transition-transform duration-300 group-hover:translate-x-1' : '' }}">
                        {{ $card->name }}
                    </h3>
                    
                    {{-- Tier Badge --}}
                    @if ($memberTier && $tierMultiplier > 1)
                        <div class="mt-1.5 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/20 backdrop-blur-sm border border-white/30 w-fit">
                            <span class="text-base">{{ $tierIcon }}</span>
                            <span class="text-sm font-bold tracking-wide">{{ number_format($tierMultiplier, 1) }}×</span>
                        </div>
                    @endif
                </div>
                
                @if(!$showQr)
                    <x-ui.icon icon="credit-card" class="w-8 h-8 text-white/60 {{ $animated ? 'transition-all duration-300 group-hover:rotate-12 group-hover:scale-110' : '' }}" />
                @endif
            </div>

            {{-- QR Code or Balance --}}
            @if($showQr)
                <div class="flex items-center justify-center flex-1">
                    <div class="bg-white p-3 rounded-2xl">
                        {!! $card->qr_code !!}
                    </div>
                </div>
            @else
                {{-- Balance --}}
                <div>
                    <p class="text-white/80 text-xs mb-1">{{ trans('common.balance') }}</p>
                    <p class="text-3xl font-bold font-mono {{ $animated ? 'transition-all duration-300 group-hover:scale-105 origin-left' : '' }}">
                        {{ number_format($balance) }} <span class="text-lg font-normal">pts</span>
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
