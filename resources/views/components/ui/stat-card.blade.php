{{--
Stat Card Component

Clean metric display. The number is the content.

@props
- label: string - Metric label
- value: string|number - Main value
- change: string - Change indicator (e.g., "+12%")
- changeType: 'positive'|'negative'|'neutral'
- icon: string - Optional small icon (decorative only)
- iconColor: string - Ignored (kept for backward compatibility)
- trend: array - Optional sparkline data
--}}

@props([
    'label' => '',
    'value' => '',
    'change' => null,
    'changeType' => 'neutral',
    'icon' => null,
    'iconColor' => null,
    'trend' => null,
])

@php
$changeColors = match($changeType) {
    'positive' => 'text-emerald-600 dark:text-emerald-400',
    'negative' => 'text-red-600 dark:text-red-400',
    default => 'text-secondary-500 dark:text-secondary-400',
};
$changeIcon = match($changeType) {
    'positive' => 'trending-up',
    'negative' => 'trending-down',
    default => 'minus',
};
@endphp

<div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-5 transition-colors duration-200 hover:border-secondary-300 dark:hover:border-secondary-700">
    <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
            {{-- Label --}}
            <p class="text-sm font-medium text-secondary-500 dark:text-secondary-400 mb-1">
                {{ $label }}
            </p>
            
            {{-- Value --}}
            <p class="text-3xl font-bold text-secondary-900 dark:text-white tracking-tight tabular-nums">
                {{ $value }}
            </p>
        </div>
        
        {{-- Change Indicator (text only, no pill) --}}
        @if($change)
            <span class="inline-flex items-center gap-1 text-xs font-medium {{ $changeColors }} mt-1">
                <x-ui.icon :icon="$changeIcon" class="w-3.5 h-3.5" />
                {{ $change }}
            </span>
        @endif
    </div>
    
    {{-- Trend Sparkline --}}
    @if($trend)
        <div class="mt-4 pt-4 border-t border-secondary-100 dark:border-secondary-800">
            <div class="h-8 flex items-end gap-0.5">
                @foreach($trend as $point)
                    <div 
                        class="flex-1 bg-secondary-200 dark:bg-secondary-700 rounded-t hover:bg-primary-500/60 dark:hover:bg-primary-400/60 transition-colors duration-150" 
                        style="height: {{ $point }}%"
                    ></div>
                @endforeach
            </div>
        </div>
    @endif
</div>
