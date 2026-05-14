{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Stamp Progress Tab - Revolut × Jony Ive Level Design

Inspired by tier-status component. Clean, minimal, exceptional.
--}}

@php
$currentStamps = $enrollment->current_stamps ?? 0;
$stampsRequired = $stampCard->stamps_required;
$progressPercentage = $stampsRequired > 0 ? round(($currentStamps / $stampsRequired) * 100, 1) : 0;
$pendingRewards = $enrollment->pending_rewards ?? 0;
$completedCount = $enrollment->completed_count ?? 0;
$redeemedCount = $enrollment->redeemed_count ?? 0;
$stampIcon = $stampCard->stamp_icon ?? '⭐';
$isEmoji = preg_match('/[^\x00-\x7F]/', $stampIcon);

// Calculate optimal grid columns - aim for balanced, visually pleasing grids
$gridCols = match(true) {
    $stampsRequired <= 3 => $stampsRequired,      // 1-3: single row
    $stampsRequired == 4 => 2,                     // 4: 2×2 square
    $stampsRequired == 5 => 5,                     // 5: single row
    $stampsRequired == 6 => 3,                     // 6: 3×2 ✓
    $stampsRequired == 7 => 4,                     // 7: 4+3 (not ideal but acceptable)
    $stampsRequired == 8 => 4,                     // 8: 4×2 ✓
    $stampsRequired == 9 => 3,                     // 9: 3×3 perfect square ✓
    $stampsRequired == 10 => 5,                    // 10: 5×2 ✓
    $stampsRequired == 11 => 4,                    // 11: 4+4+3
    $stampsRequired == 12 => 4,                    // 12: 4×3 ✓
    $stampsRequired <= 15 => 5,                    // 13-15: 5×3
    $stampsRequired == 16 => 4,                    // 16: 4×4 perfect square ✓
    $stampsRequired <= 20 => 5,                    // 17-20: 5×4
    default => 5                                   // 21+: cap at 5 cols
};

$gridRows = (int) ceil($stampsRequired / $gridCols);
$totalSlots = $gridCols * $gridRows;
$emptySlots = $totalSlots - $stampsRequired;

// Link to reward card if exists
$rewardCardUrl = $stampCard->reward_card_id ? route('member.card', ['card_id' => $stampCard->reward_card_id]) : null;
@endphp

<div class="space-y-6">
    {{-- Premium Stats Card (Tier-Inspired) --}}
    <div class="group relative bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden">
        {{-- Ambient glow --}}
        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-primary-500 to-primary-600 opacity-0 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 group-hover:opacity-5 transition-opacity duration-500"></div>
        
        <div class="p-6 relative">
            {{-- Header with Progress --}}
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-4">
                    {{-- Icon --}}
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-3xl shadow-lg shadow-primary-500/20">
                        @if ($isEmoji)
                            <span class="drop-shadow-sm">{{ $stampIcon }}</span>
                        @else
                            <x-ui.icon :icon="$stampIcon" class="w-8 h-8 text-white drop-shadow-sm" />
                        @endif
                    </div>
                    
                    {{-- Title with Status Dot --}}
                    <div>
                        <div class="flex items-center gap-2 mb-0.5">
                            <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 uppercase tracking-wider">
                                {{ trans('common.progress') }}
                            </p>
                            {{-- Pulsating status dot (green if complete, primary otherwise) --}}
                            <span class="w-2 h-2 rounded-full animate-pulse {{ $currentStamps >= $stampsRequired ? 'bg-emerald-500' : 'bg-primary-500' }}"></span>
                        </div>
                        <h3 class="text-xl font-semibold text-secondary-900 dark:text-white">
                            {{ $currentStamps }} / {{ $stampsRequired }} {{ trans('common.stamps') }}
                        </h3>
                    </div>
                </div>
                
                {{-- Percentage (Large, Bold, Tabular) --}}
                <div class="text-right">
                    <p class="text-3xl font-bold text-secondary-900 dark:text-white tabular-nums">{{ $progressPercentage }}%</p>
                </div>
            </div>
            
            {{-- Progress Bar (Minimal, Revolut-Style) --}}
            <div class="mb-5">
                <div class="h-1.5 bg-secondary-100 dark:bg-secondary-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-primary-500 to-primary-600 rounded-full transition-all duration-700"
                         style="width: {{ $progressPercentage }}%;">
                    </div>
                </div>
            </div>

            {{-- Stats Grid (Compact, Tier-Style) --}}
            <div class="pt-5 border-t border-secondary-100 dark:border-secondary-800">
                <div class="grid grid-cols-3 gap-4">
                    {{-- Rewards Ready --}}
                    <div class="text-center">
                        <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-emerald-100 dark:bg-emerald-500/10 flex items-center justify-center">
                            <x-ui.icon icon="gift" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums">{{ $pendingRewards }}</div>
                        <div class="text-[10px] text-secondary-500 dark:text-secondary-400 uppercase tracking-wider mt-1">
                            {{ trans('common.rewards_ready') }}
                        </div>
                    </div>

                    {{-- Completed --}}
                    <div class="text-center">
                        <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-amber-100 dark:bg-amber-500/10 flex items-center justify-center">
                            <x-ui.icon icon="trophy" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums">{{ $completedCount }}</div>
                        <div class="text-[10px] text-secondary-500 dark:text-secondary-400 uppercase tracking-wider mt-1">
                            {{ trans('common.completed') }}
                        </div>
                    </div>

                    {{-- Redeemed --}}
                    <div class="text-center">
                        <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-primary-100 dark:bg-primary-500/10 flex items-center justify-center">
                            <x-ui.icon icon="check-circle" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div class="text-2xl font-bold text-secondary-900 dark:text-white tabular-nums">{{ $redeemedCount }}</div>
                        <div class="text-[10px] text-secondary-500 dark:text-secondary-400 uppercase tracking-wider mt-1">
                            {{ trans('common.redeemed') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Visual Stamp Grid (Tap/Hover Icon Display) --}}
    <div class="bg-white dark:bg-secondary-800 rounded-2xl p-6 border border-secondary-200 dark:border-secondary-700 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-sm font-bold uppercase tracking-wider text-secondary-500 dark:text-secondary-400">
                {{ trans('common.stamp_collection') }}
            </h4>
            <div class="text-xs text-secondary-400 dark:text-secondary-500 hidden sm:block">
                {{ trans('common.hover_to_preview') }}
            </div>
            <div class="text-xs text-secondary-400 dark:text-secondary-500 sm:hidden">
                {{ trans('common.tap_to_preview') }}
            </div>
        </div>
        
        <div 
            class="grid gap-2.5" 
            style="grid-template-columns: repeat({{ $gridCols }}, minmax(0, 1fr));"
            x-data="{ activeStamp: null }"
            @click.away="activeStamp = null"
        >
            @for ($i = 1; $i <= $stampsRequired; $i++)
                <div 
                    class="group/stamp relative aspect-square"
                    @click="activeStamp = activeStamp === {{ $i }} ? null : {{ $i }}"
                    @mouseenter="activeStamp = {{ $i }}"
                    @mouseleave="activeStamp = null"
                >
                    <div 
                        class="absolute inset-0 rounded-xl flex items-center justify-center transition-all duration-300 
                            {{ $i <= $currentStamps 
                                ? 'bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-md shadow-emerald-500/20' 
                                : 'bg-secondary-100 dark:bg-secondary-700' }}"
                        :class="activeStamp === {{ $i }} && {{ $i }} > {{ $currentStamps }} ? 'bg-secondary-200 dark:bg-secondary-600' : ''"
                    >
                        @if ($i <= $currentStamps)
                            {{-- Filled Stamp --}}
                            @if ($isEmoji)
                                <span 
                                    class="text-4xl drop-shadow-lg transition-transform duration-200"
                                    :class="activeStamp === {{ $i }} ? 'scale-110' : ''"
                                >{{ $stampIcon }}</span>
                            @else
                                <x-ui.icon 
                                    :icon="$stampIcon" 
                                    class="w-7 h-7 text-white drop-shadow-lg transition-transform duration-200"
                                    ::class="activeStamp === {{ $i }} ? 'scale-110' : ''"
                                />
                            @endif
                            {{-- Checkmark --}}
                            <div class="absolute -top-1 -right-1 w-5 h-5 bg-white rounded-full flex items-center justify-center shadow-md">
                                <x-ui.icon icon="check" class="w-3 h-3 text-emerald-600" />
                            </div>
                        @else
                            {{-- Empty Stamp - Show icon on hover/tap --}}
                            <span 
                                class="text-sm font-bold transition-opacity duration-200"
                                :class="activeStamp === {{ $i }} ? 'opacity-0' : 'opacity-40'"
                            >{{ $i }}</span>
                            <div 
                                class="absolute inset-0 flex items-center justify-center transition-opacity duration-200"
                                :class="activeStamp === {{ $i }} ? 'opacity-100' : 'opacity-0'"
                            >
                                @if ($isEmoji)
                                    <span class="text-4xl opacity-60">{{ $stampIcon }}</span>
                                @else
                                    <x-ui.icon :icon="$stampIcon" class="w-7 h-7 text-secondary-400 dark:text-secondary-500 opacity-60" />
                                @endif
                            </div>
                            {{-- Dotted guide --}}
                            <div class="absolute inset-2 border-2 border-dashed border-secondary-300 dark:border-secondary-600 rounded-lg opacity-40"></div>
                        @endif
                    </div>
                </div>
            @endfor
        </div>
    </div>

    {{-- Reward Card (With Link to Loyalty Card if exists) --}}
    <div class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/10 dark:to-orange-900/10 rounded-2xl border border-amber-200 dark:border-amber-800 shadow-sm overflow-hidden">
        @if($rewardCardUrl)
            <a href="{{ $rewardCardUrl }}" class="block p-6 hover:bg-amber-100/50 dark:hover:bg-amber-900/20 transition-colors group">
        @else
            <div class="p-6">
        @endif
            <div class="flex items-start gap-4">
                <div class="flex-none w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-lg shadow-amber-500/30">
                    <x-ui.icon icon="gift" class="w-7 h-7 text-white" />
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-amber-900 dark:text-amber-400">
                            {{ trans('common.your_reward') }}
                        </h4>
                        @if($rewardCardUrl)
                            <x-ui.icon icon="arrow-right" class="w-4 h-4 text-amber-600 dark:text-amber-400 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform" />
                        @endif
                    </div>
                    <p class="text-lg font-bold text-secondary-900 dark:text-white mb-1">
                        {{ $stampCard->reward_title }}
                    </p>
                    @if ($stampCard->reward_description)
                        <p class="text-sm text-secondary-600 dark:text-secondary-400 line-clamp-2">
                            {{ $stampCard->reward_description }}
                        </p>
                    @endif
                    
                    <div class="mt-3 flex flex-wrap gap-2">
                        {{-- Points Reward --}}
                        @if ($stampCard->reward_points)
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white/50 dark:bg-white/5 border border-amber-200 dark:border-amber-800">
                                <x-ui.icon icon="coins" class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                                <span class="text-sm font-bold text-amber-600 dark:text-amber-400">
                                    +<span class="format-number">{{ $stampCard->reward_points }}</span> {{ trans('common.points') }}
                                </span>
                            </div>
                        @endif

                        {{-- Monetary Value --}}
                        @if ($stampCard->show_monetary_value && $stampCard->reward_value)
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white/50 dark:bg-white/5 border border-amber-200 dark:border-amber-800">
                                <span class="text-xs font-medium text-secondary-600 dark:text-secondary-400">{{ trans('common.value') }}:</span>
                                <span class="text-sm font-bold text-amber-600 dark:text-amber-400">
                                    {{ moneyFormat((float) $stampCard->reward_value, $stampCard->currency) }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @if($rewardCardUrl)
            </a>
        @else
            </div>
        @endif
    </div>
</div>
