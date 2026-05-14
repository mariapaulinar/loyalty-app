{{--
    Reward Loyalty - Proprietary Software
    Copyright (c) 2025 NowSquare. All rights reserved.
    See LICENSE file for terms.
    
    Tier Status Widget - Revolut × Jony Ive
    
    Minimal. One pulsating status dot in tier color.
    Everything else: clean neutrals.
--}}

@props([
    'memberTier' => null,
    'club' => null,
    'card' => null,
    'progress' => [],
])

@php
    $tier = $memberTier?->tier;
    $tierName = $tier?->getTranslation('display_name', app()->getLocale()) ?? trans('common.bronze');
    $tierColor = $tier?->color ?? '#2563EB';
    $tierIcon = $tier?->icon ?? '🥉';
    $multiplier = $tier?->points_multiplier ?? 1.00;
    $nextTier = $tier?->getNextTier();
    $nextTierName = $nextTier?->getTranslation('display_name', app()->getLocale());
    
    $progressPercentage = 0;
    $pointsCurrent = 0;
    $pointsNeeded = 0;
    if ($nextTier && isset($progress['points'])) {
        $progressPercentage = round(min(100, $progress['points']['percentage'] ?? 0));
        $pointsCurrent = $progress['points']['current'] ?? 0;
        $pointsNeeded = $progress['points']['threshold'] ?? 0;
    }
    
    $isTopTier = !$nextTier;
    
    // Get all active, non-expired cards from this club
    $clubCards = collect();
    if ($club && auth('member')->check()) {
        $member = auth('member')->user();
        $clubCards = $club->cards()
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNull('expiration_date')
                      ->orWhere('expiration_date', '>', now());
            })
            ->whereHas('members', function($query) use ($member) {
                $query->where('member_id', $member->id);
            })
            ->get()
            ->take(5); // Limit to 5 cards for UI
    }
@endphp

<div class="group relative bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 shadow-sm hover:shadow-md transition-all duration-300">
    {{-- Tier Content --}}
    <div class="p-6">
        {{-- Tier Header --}}
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-4">
                {{-- Icon --}}
                <div class="w-14 h-14 rounded-2xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center text-2xl flex-shrink-0">
                    {{ $tierIcon }}
                </div>
                
                {{-- Title --}}
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 mb-0.5">
                        <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 uppercase tracking-wider">
                            {{ trans('common.your_status') }}
                        </p>
                        {{-- Status dot --}}
                        <span class="w-2 h-2 rounded-full animate-pulse flex-shrink-0" style="background: {{ $tierColor }};"></span>
                    </div>
                    <h3 class="text-xl font-semibold text-secondary-900 dark:text-white">
                        {{ $tierName }}
                    </h3>
                </div>
            </div>
            
            {{-- Multiplier --}}
            @if($multiplier > 1)
                <div class="flex items-center gap-1.5 text-right flex-shrink-0">
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums">{{ number_format($multiplier, 1) }}×</p>
                    <x-ui.icon icon="coins" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                </div>
            @endif
        </div>

        {{-- Club Cards (Apple Wallet style) --}}
        @if($clubCards->isNotEmpty())
            <div class="mb-5 pb-5 border-b border-secondary-100 dark:border-secondary-800">
                <div class="flex items-center gap-2 mb-3">
                    <x-ui.icon icon="credit-card" class="w-3.5 h-3.5 text-secondary-400 dark:text-secondary-500" />
                    <p class="text-xs font-medium text-secondary-500 dark:text-secondary-400">
                        {{ trans('common.applies_to') }} {{ $clubCards->count() }} {{ trans_choice('common.card', $clubCards->count()) }}
                    </p>
                </div>
                
                {{-- Horizontal scrollable card chips (Clean, minimal) --}}
                <div class="flex gap-2 overflow-x-auto pb-2 -mx-6 px-6 scrollbar-hide">
                    @foreach($clubCards as $clubCard)
                        <a href="{{ route('member.card', ['card_id' => $clubCard->id]) }}" 
                           class="group flex-shrink-0 flex items-center gap-2 px-3 py-2 rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-800 hover:border-secondary-300 dark:hover:border-secondary-600 hover:shadow-md transition-all duration-200 max-w-[160px]">
                            {{-- Card name (multi-line, no truncate) --}}
                            <span class="text-xs font-medium text-secondary-700 dark:text-secondary-300 group-hover:text-secondary-900 dark:group-hover:text-white transition-colors leading-snug">
                                {{ $clubCard->head }}
                            </span>
                            {{-- Arrow --}}
                            <x-ui.icon icon="arrow-right" class="w-3.5 h-3.5 text-secondary-400 group-hover:text-secondary-600 dark:group-hover:text-secondary-300 group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 transition-all flex-shrink-0 ml-auto" />
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
        
        {{-- Progress or Top Tier --}}
        @if(!$isTopTier)
            <div class="pt-5 border-t border-secondary-100 dark:border-secondary-800">
                {{-- Progress header --}}
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">
                        {{ trans('common.next') }}: <span class="font-medium text-secondary-700 dark:text-secondary-300">{{ $nextTierName }}</span>
                    </p>
                    <p class="text-sm font-medium text-secondary-900 dark:text-white tabular-nums">{{ $progressPercentage }}%</p>
                </div>
                
                {{-- Progress bar --}}
                <div class="h-1.5 bg-secondary-100 dark:bg-secondary-800 rounded-full overflow-hidden">
                    <div class="h-full bg-secondary-900 dark:bg-white rounded-full transition-all duration-700"
                         style="width: {{ $progressPercentage }}%;"></div>
                </div>
                
                {{-- Points --}}
                <div class="flex justify-between mt-2 text-xs text-secondary-400 dark:text-secondary-500">
                    <span class="flex items-center gap-1">
                        <span class="format-number">{{ $pointsCurrent }}</span>
                        <x-ui.icon icon="coins" class="w-3 h-3" />
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="format-number">{{ $pointsNeeded }}</span>
                        <x-ui.icon icon="coins" class="w-3 h-3" />
                    </span>
                </div>
            </div>
        @else
            <div class="pt-5 border-t border-secondary-100 dark:border-secondary-800">
                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                    🏆 {{ trans('common.you_are_at_highest_tier') }}
                </p>
            </div>
        @endif
    </div>
</div>
