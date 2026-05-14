{{--
Premium Progress Bar Component

Modern progress indicator with smooth animations and gradient fills.
Inspired by Monzo and Revolut's progress indicators.

@props
- current: number - Current value
- max: number - Maximum value
- label: string - Optional label
- showPercentage: boolean (default: true)
- showValues: boolean (default: false)
- size: 'sm'|'md'|'lg' (default: 'md')
- color: 'primary'|'success'|'warning'|'danger' (default: 'primary')
- animated: boolean (default: true)
--}}

@props([
    'current' => 0,
    'max' => 100,
    'label' => null,
    'showPercentage' => true,
    'showValues' => false,
    'size' => 'md',
    'color' => 'primary',
    'animated' => true,
])

@php
$percentage = $max > 0 ? min(($current / $max) * 100, 100) : 0;

$gradients = [
    'primary' => 'from-primary-500 to-primary-600',
    'success' => 'from-emerald-500 to-teal-600',
    'warning' => 'from-amber-500 to-orange-600',
    'danger' => 'from-red-500 to-rose-600',
];

$glows = [
    'primary' => 'shadow-primary-500/30',
    'success' => 'shadow-emerald-500/30',
    'warning' => 'shadow-amber-500/30',
    'danger' => 'shadow-red-500/30',
];

$sizes = [
    'sm' => 'h-1.5',
    'md' => 'h-2.5',
    'lg' => 'h-4',
];

$barGradient = $gradients[$color] ?? $gradients['primary'];
$barGlow = $glows[$color] ?? $glows['primary'];
$barHeight = $sizes[$size] ?? $sizes['md'];
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    {{-- Header --}}
    @if($label || $showPercentage || $showValues)
        <div class="flex items-center justify-between mb-2">
            @if($label)
                <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300">
                    {{ $label }}
                </span>
            @endif
            
            <div class="flex items-center gap-3 text-sm ml-auto">
                @if($showValues)
                    <span class="font-mono text-secondary-500 dark:text-secondary-400">
                        {{ number_format($current) }}<span class="text-secondary-400 dark:text-secondary-500">/{{ number_format($max) }}</span>
                    </span>
                @endif
                
                @if($showPercentage)
                    <span class="font-semibold text-secondary-900 dark:text-white tabular-nums">
                        {{ number_format($percentage, 0) }}%
                    </span>
                @endif
            </div>
        </div>
    @endif
    
    {{-- Progress Bar --}}
    <div class="relative w-full {{ $barHeight }} bg-secondary-100 dark:bg-secondary-800 rounded-full overflow-hidden">
        {{-- Progress Fill --}}
        <div 
            class="absolute inset-y-0 left-0 bg-gradient-to-r {{ $barGradient }} rounded-full shadow-lg {{ $barGlow }} transition-all duration-1000 ease-out"
            style="width: {{ $animated ? 0 : $percentage }}%"
            x-data="{ width: {{ $animated ? 0 : $percentage }} }"
            x-init="setTimeout(() => width = {{ $percentage }}, 100)"
            :style="`width: ${width}%`"
        >
            {{-- Shine effect --}}
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/25 to-transparent -translate-x-full animate-[shine_2s_infinite]"></div>
        </div>
    </div>
</div>

<style>
@keyframes shine {
    100% { transform: translateX(200%); }
}
</style>
