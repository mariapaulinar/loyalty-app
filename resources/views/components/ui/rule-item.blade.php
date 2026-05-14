{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Universal Rule Item Component

Purpose:
Displays a single rule/requirement/restriction in a consistent, beautiful format.
Used across loyalty cards, stamp cards, and vouchers for visual consistency.

Props:
- icon: Icon name (string) or emoji (string)
- title: Rule title (string)
- description: Rule description (string, can include HTML)
- color: Color theme (string: 'primary', 'amber', 'emerald', 'blue', 'purple', 'pink', 'green')
- highlight: Special background for important rules (boolean, default: false)
--}}

@props([
    'icon',
    'title',
    'description',
    'color' => 'primary',
    'highlight' => false,
])

@php
// Color mappings for gradients and shadows
$colorMap = [
    'primary' => [
        'gradient' => 'from-primary-500 to-primary-600',
        'shadow' => 'shadow-primary-500/20',
        'text' => 'text-primary-600 dark:text-primary-400',
    ],
    'amber' => [
        'gradient' => 'from-amber-500 to-orange-600',
        'shadow' => 'shadow-amber-500/20',
        'text' => 'text-amber-600 dark:text-amber-400',
    ],
    'emerald' => [
        'gradient' => 'from-emerald-500 to-emerald-600',
        'shadow' => 'shadow-emerald-500/20',
        'text' => 'text-emerald-600 dark:text-emerald-400',
    ],
    'blue' => [
        'gradient' => 'from-blue-500 to-blue-600',
        'shadow' => 'shadow-blue-500/20',
        'text' => 'text-blue-600 dark:text-blue-400',
    ],
    'purple' => [
        'gradient' => 'from-purple-500 to-purple-600',
        'shadow' => 'shadow-purple-500/20',
        'text' => 'text-purple-600 dark:text-purple-400',
    ],
    'pink' => [
        'gradient' => 'from-pink-500 to-pink-600',
        'shadow' => 'shadow-pink-500/20',
        'text' => 'text-pink-600 dark:text-pink-400',
    ],
    'green' => [
        'gradient' => 'from-green-500 to-emerald-600',
        'shadow' => 'shadow-green-500/20',
        'text' => 'text-green-600 dark:text-green-400',
    ],
];

$colors = $colorMap[$color] ?? $colorMap['primary'];

// Check if icon is emoji
$isEmoji = preg_match('/[^\x00-\x7F]/', $icon);

// Highlight background for special rules
$bgClass = $highlight 
    ? 'bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border-amber-200 dark:border-amber-800 hover:border-amber-300 dark:hover:border-amber-700'
    : 'bg-white dark:bg-secondary-800 border-secondary-200 dark:border-secondary-700 hover:border-primary-300 dark:hover:border-primary-700';

$titleColor = $highlight
    ? 'text-amber-900 dark:text-amber-400'
    : 'text-secondary-500 dark:text-secondary-400';
@endphp

<div class="group relative {{ $bgClass }} rounded-2xl p-5 border transition-all duration-300 hover:shadow-lg">
    <div class="flex gap-4">
        {{-- Icon --}}
        <div class="flex-none">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $colors['gradient'] }} flex items-center justify-center shadow-lg {{ $colors['shadow'] }} group-hover:scale-110 transition-transform duration-300">
                @if($isEmoji)
                    <span class="text-2xl">{{ $icon }}</span>
                @else
                    <x-ui.icon :icon="$icon" class="w-6 h-6 text-white" />
                @endif
            </div>
        </div>
        
        {{-- Content --}}
        <div class="flex-1 min-w-0">
            <div class="text-xs font-bold uppercase tracking-wider {{ $titleColor }} mb-1">
                {{ $title }}
            </div>
            <div class="text-sm text-secondary-700 dark:text-secondary-300 leading-relaxed">
                {!! $description !!}
            </div>
        </div>
    </div>
</div>
