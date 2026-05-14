{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Difference Badge — percentage change indicator.

  Follows the stat-card change indicator pattern:
  text + directional icon, no background pill.
  Color communicates sentiment: emerald = growth, red = decline.
--}}

@php
    $numDiff = intval(str_replace('+', '', $diff));
@endphp

@if($diff === '0' || $diff === '+0')
    <span class="inline-flex items-center gap-1 text-xs font-medium text-secondary-500 dark:text-secondary-400">
        <x-ui.icon icon="minus" class="w-3.5 h-3.5" />
        {{ $diff }}%
    </span>
@elseif($numDiff > 0)
    <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
        <x-ui.icon icon="trending-up" class="w-3.5 h-3.5" />
        +{{ $numDiff }}%
    </span>
@else
    <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400">
        <x-ui.icon icon="trending-down" class="w-3.5 h-3.5" />
        {{ $diff }}%
    </span>
@endif
