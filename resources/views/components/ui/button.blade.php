{{--
Premium Button Component

Modern buttons with gradients, shadows, and smooth interactions.
Inspired by Linear, Stripe, and Vercel's button systems.

@props
- variant: 'primary'|'secondary'|'trust'|'danger'|'ghost'|'link' (default: 'primary')
- size: 'sm'|'md'|'lg'|'icon' (default: 'md')
- icon: string - Lucide icon name
- href: string - If provided, renders as anchor
- type: string - Button type (default: 'button')
- submit: boolean - Shorthand for type="submit"
--}}

@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'href' => null,
    'type' => 'button',
    'submit' => false,
    'text' => null,
])

@php
$baseClasses = 'relative inline-flex items-center justify-center font-semibold transition-all duration-300 ease-out focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-secondary-900 active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none cursor-pointer overflow-hidden';

$variants = [
    'primary' => 'bg-gradient-to-r from-primary-600 to-primary-500 hover:from-primary-500 hover:to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:shadow-xl hover:shadow-primary-500/30 focus:ring-primary-500 border border-transparent hover:-translate-y-0.5',
    'accent' => 'bg-gradient-to-r from-accent-500 to-accent-400 hover:from-accent-400 hover:to-accent-300 text-secondary-900 shadow-lg shadow-accent-500/30 hover:shadow-xl hover:shadow-accent-500/40 focus:ring-accent-500 border border-accent-400/50 hover:-translate-y-0.5 font-bold',
    'secondary' => 'bg-white/80 dark:bg-secondary-800/80 backdrop-blur-sm text-secondary-700 dark:text-secondary-200 border border-secondary-200/50 dark:border-secondary-700/50 hover:bg-secondary-50 dark:hover:bg-secondary-700/80 hover:border-secondary-300 dark:hover:border-secondary-600 focus:ring-secondary-500 shadow-sm hover:shadow-md',
    'trust' => 'bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:shadow-emerald-500/30 focus:ring-emerald-500 border border-transparent hover:-translate-y-0.5',
    'danger' => 'bg-gradient-to-r from-red-600 to-rose-500 hover:from-red-500 hover:to-rose-400 text-white shadow-lg shadow-red-500/25 hover:shadow-xl hover:shadow-red-500/30 focus:ring-red-500 border border-transparent hover:-translate-y-0.5',
    'ghost' => 'text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white hover:bg-secondary-100 dark:hover:bg-secondary-800 focus:ring-secondary-500',
    'link' => 'text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 underline-offset-4 hover:underline p-0 h-auto focus:ring-primary-500',
];

$sizes = [
    'sm' => 'text-xs px-3.5 py-2 rounded-xl gap-1.5',
    'md' => 'text-sm px-5 py-2.5 rounded-xl gap-2',
    'lg' => 'text-base px-6 py-3 rounded-2xl gap-2.5',
    'icon' => 'p-2.5 rounded-xl',
];

$iconSizes = [
    'sm' => 'w-3.5 h-3.5',
    'md' => 'w-4 h-4',
    'lg' => 'w-5 h-5',
    'icon' => 'w-5 h-5',
];

$classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
$iconClass = $iconSizes[$size] ?? $iconSizes['md'];
$displayText = $text ?? $slot;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <x-ui.icon :icon="$icon" class="{{ $iconClass }}" />
        @endif
        @if ($text)
            {!! $text !!}
        @else
            {{ $slot }}
        @endif
    </a>
@else
    <button type="{{ $submit ? 'submit' : $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <x-ui.icon :icon="$icon" class="{{ $iconClass }}" />
        @endif
        @if ($text)
            {!! $text !!}
        @else
            {{ $slot }}
        @endif
    </button>
@endif
