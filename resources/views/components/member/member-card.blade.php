{{--
    Reward Loyalty - Proprietary Software
    Copyright (c) 2025 NowSquare. All rights reserved.
    See LICENSE file for terms.

    Member Card Component — Revolut × Jony Ive
    
    Responsive: stacks info below name on small screens,
    single row on wider viewports.
--}}

@php
    $tier = $memberTier?->tier;
    $tierName = $tier?->getTranslation('display_name', app()->getLocale());
    $tierColor = $tier?->color ?? '#10B981';
    $tierIcon = $tier?->icon ?? '🎖️';
    $multiplier = $tier?->points_multiplier ?? 1.00;
@endphp

<div {{ $attributes->except('class') }} 
     class="group bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 shadow-sm hover:shadow-lg transition-all duration-300 {{ $attributes->get('class') }}">
    <div class="p-4 sm:p-5">
        {{-- Top row: Avatar + Name + Right-side data (on sm+) --}}
        <div class="flex items-center gap-3 sm:gap-4">
            {{-- Avatar --}}
            <div class="relative flex-shrink-0">
                @if ($member->avatar)
                    <img class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl object-cover" 
                         src="{{ $member->avatar }}" 
                         alt="{{ parse_attr($member->name) }}">
                @else
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center">
                        <x-ui.icon icon="user" class="w-5 h-5 sm:w-6 sm:h-6 text-secondary-400 dark:text-secondary-500" />
                    </div>
                @endif
                {{-- Status dot --}}
                <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 sm:w-3.5 sm:h-3.5 rounded-full border-2 border-white dark:border-secondary-900 animate-pulse"
                      style="background: {{ $showTier && $tier ? $tierColor : '#10B981' }};"></span>
            </div>
            
            {{-- Name + Tier (inline on sm+) --}}
            <div class="flex-1 min-w-0">
                <h4 class="text-sm sm:text-base font-semibold text-secondary-900 dark:text-white truncate">
                    {{ $member->name }}
                </h4>
                @if($showTier && $tier)
                    <span class="text-xs sm:text-sm text-secondary-500 dark:text-secondary-400">{{ $tierIcon }} {{ $tierName }}</span>
                @endif
            </div>
            
            {{-- Right: unique ID + multiplier — visible on sm+ inline --}}
            <div class="hidden sm:block flex-shrink-0 text-right">
                <p class="text-xs font-mono text-secondary-500 dark:text-secondary-400">{{ $member->unique_identifier }}</p>
                <div class="flex items-center justify-end gap-2 mt-0.5">
                    <span class="text-xs text-secondary-400 dark:text-secondary-500">
                        {{ $member->created_at->setTimezone(app()->make('i18n')->time_zone)->translatedFormat('M Y') }}
                    </span>
                    @if($showTier && $tier && $multiplier > 1.00)
                        <span class="text-secondary-300 dark:text-secondary-600">·</span>
                        <span class="text-sm font-semibold text-secondary-900 dark:text-white flex items-center gap-0.5">
                            {{ number_format($multiplier, 1) }}×
                            <x-ui.icon icon="coins" class="w-3.5 h-3.5 text-secondary-400" />
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Bottom row: metadata — visible on mobile only --}}
        <div class="flex items-center justify-between mt-2.5 pt-2.5 border-t border-secondary-100 dark:border-secondary-800 sm:hidden">
            <span class="text-xs font-mono text-secondary-500 dark:text-secondary-400">{{ $member->unique_identifier }}</span>
            <div class="flex items-center gap-2">
                <span class="text-xs text-secondary-400 dark:text-secondary-500">
                    {{ $member->created_at->setTimezone(app()->make('i18n')->time_zone)->translatedFormat('M Y') }}
                </span>
                @if($showTier && $tier && $multiplier > 1.00)
                    <span class="text-secondary-300 dark:text-secondary-600">·</span>
                    <span class="text-xs font-semibold text-secondary-900 dark:text-white flex items-center gap-0.5">
                        {{ number_format($multiplier, 1) }}×
                        <x-ui.icon icon="coins" class="w-3 h-3 text-secondary-400" />
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
