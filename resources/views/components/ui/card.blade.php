{{--
Premium Card Component

Modern glassmorphism card with subtle borders and shadows.
Inspired by Linear and Apple's design language.

@props
- padding: string - Padding class (default: 'p-6')
- hover: boolean - Enable hover effects (default: false)
--}}

@props([
    'padding' => 'p-6',
    'hover' => false,
])

@php
$baseClasses = 'bg-white/80 dark:bg-secondary-800/80 backdrop-blur-xl rounded-2xl border border-secondary-200/50 dark:border-secondary-700/50 shadow-lg shadow-secondary-900/5 dark:shadow-secondary-900/50';

$hoverClasses = $hover 
    ? 'transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-secondary-900/10 dark:hover:shadow-secondary-900/60 hover:border-secondary-300/50 dark:hover:border-secondary-600/50' 
    : '';
@endphp

<div {{ $attributes->merge(['class' => "$baseClasses $hoverClasses $padding"]) }}>
    {{ $slot }}
</div>
