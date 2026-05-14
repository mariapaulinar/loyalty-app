{{--
Premium Skeleton Loader Component

Modern loading skeletons with smooth shimmer animation.
Inspired by Linear and Vercel's loading states.

@props
- type: 'text'|'card'|'circle'|'rectangle'|'list' (default: 'text')
- lines: number - Number of text lines (default: 3)
- width: string - Custom width (default: 'w-full')
- height: string - Custom height
- count: number - Repeat count for lists (default: 1)
--}}

@props([
    'type' => 'text',
    'lines' => 3,
    'width' => 'w-full',
    'height' => null,
    'count' => 1,
])

@php
$baseClasses = 'bg-secondary-200 dark:bg-secondary-700 rounded-lg relative overflow-hidden';
$shimmerClasses = 'absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/40 dark:via-white/10 to-transparent animate-[shimmer_2s_infinite]';
@endphp

@if($type === 'text')
    <div class="space-y-3">
        @for($i = 0; $i < $lines; $i++)
            <div class="{{ $baseClasses }} h-4 {{ $i === $lines - 1 ? 'w-3/4' : $width }}">
                <div class="{{ $shimmerClasses }}"></div>
            </div>
        @endfor
    </div>

@elseif($type === 'card')
    <div class="{{ $baseClasses }} {{ $width }} {{ $height ?? 'h-48' }} p-5 rounded-2xl">
        <div class="{{ $shimmerClasses }}"></div>
        <div class="flex items-start gap-4 h-full relative">
            <div class="bg-secondary-300 dark:bg-secondary-600 w-14 h-14 rounded-xl flex-shrink-0"></div>
            <div class="flex-1 space-y-3 py-1">
                <div class="bg-secondary-300 dark:bg-secondary-600 h-4 w-3/4 rounded"></div>
                <div class="bg-secondary-300 dark:bg-secondary-600 h-4 w-1/2 rounded"></div>
                <div class="bg-secondary-300 dark:bg-secondary-600 h-4 w-5/6 rounded"></div>
            </div>
        </div>
    </div>

@elseif($type === 'circle')
    <div class="{{ $baseClasses }} {{ $width ?? 'w-12' }} {{ $height ?? 'h-12' }} rounded-full">
        <div class="{{ $shimmerClasses }}"></div>
    </div>

@elseif($type === 'rectangle')
    <div class="{{ $baseClasses }} {{ $width }} {{ $height ?? 'h-32' }} rounded-xl">
        <div class="{{ $shimmerClasses }}"></div>
    </div>

@elseif($type === 'list')
    <div class="space-y-4">
        @for($i = 0; $i < $count; $i++)
            <div class="flex items-center gap-4">
                <div class="{{ $baseClasses }} w-12 h-12 rounded-xl">
                    <div class="{{ $shimmerClasses }}"></div>
                </div>
                <div class="flex-1 space-y-2">
                    <div class="{{ $baseClasses }} h-4 w-3/4">
                        <div class="{{ $shimmerClasses }}"></div>
                    </div>
                    <div class="{{ $baseClasses }} h-3 w-1/2">
                        <div class="{{ $shimmerClasses }}"></div>
                    </div>
                </div>
            </div>
        @endfor
    </div>
@endif

<style>
@keyframes shimmer {
    100% { transform: translateX(100%); }
}
</style>
