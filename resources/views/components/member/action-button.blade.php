{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Premium Animated Action Button - Member Interface
A living gem button with continuous diamond-like border glow.
--}}

@props([
    'icon' => 'qr-code',
    'title',
    'subtitle',
    'color' => 'primary',
    'iconEnd' => 'chevron-right',
    'click' => null,
    'copyCode' => null,
    'titleCopied' => null,
    'subtitleCopied' => null,
])

@php
$colorSchemes = [
    'primary' => [
        'border' => 'border-glow-primary',
        'icon' => 'from-primary-500 to-primary-600 dark:from-primary-400 dark:to-primary-500',
        'iconShadow' => 'shadow-primary-500/25 group-hover:shadow-primary-500/40',
        'endBg' => 'bg-secondary-100 dark:bg-secondary-700/80',
        'endBgHover' => 'group-hover:bg-primary-50 dark:group-hover:bg-primary-900/40',
        'endIcon' => 'text-secondary-400 group-hover:text-primary-500 dark:group-hover:text-primary-400',
        'focusRing' => 'focus-visible:ring-primary-500/50',
    ],
    'amber' => [
        'border' => 'border-glow-amber',
        'icon' => 'from-amber-500 to-orange-600',
        'iconShadow' => 'shadow-amber-500/25 group-hover:shadow-amber-500/40',
        'endBg' => 'bg-amber-100 dark:bg-amber-900/40',
        'endBgHover' => 'group-hover:bg-amber-50 dark:group-hover:bg-amber-800/60',
        'endIcon' => 'text-amber-600 dark:text-amber-400',
        'focusRing' => 'focus-visible:ring-amber-500/50',
        'title' => 'text-amber-900 dark:text-amber-100',
        'subtitle' => 'text-amber-700/80 dark:text-amber-300/80',
    ],
    'emerald' => [
        'border' => 'border-glow-emerald',
        'icon' => 'from-emerald-500 to-emerald-600',
        'iconShadow' => 'shadow-emerald-500/25 group-hover:shadow-emerald-500/40',
        'endBg' => 'bg-emerald-100 dark:bg-emerald-900/40',
        'endBgHover' => 'group-hover:bg-emerald-50 dark:group-hover:bg-emerald-800/60',
        'endIcon' => 'text-emerald-600 dark:text-emerald-400',
        'focusRing' => 'focus-visible:ring-emerald-500/50',
    ],
];

$scheme = $colorSchemes[$color] ?? $colorSchemes['primary'];
$titleColorClass = $scheme['title'] ?? 'text-secondary-900 dark:text-white';
$subtitleColorClass = $scheme['subtitle'] ?? 'text-secondary-500 dark:text-secondary-400';

$isCopyMode = !empty($copyCode);
$titleCopied = $titleCopied ?? __('common.code_copied');
$subtitleCopied = $subtitleCopied ?? __('common.paste_at_checkout');
@endphp

<div 
    class="group relative w-full {{ $attributes->get('class') }}"
    @if($isCopyMode)
        x-data="{ 
            copied: false,
            async copyCode() {
                const code = '{{ $copyCode }}';
                try {
                    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                        await navigator.clipboard.writeText(code);
                    } else {
                        const textarea = document.createElement('textarea');
                        textarea.value = code;
                        textarea.style.cssText = 'position:fixed;left:-9999px;opacity:0';
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textarea);
                    }
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2500);
                } catch (err) {
                    console.error('Copy failed:', err);
                }
            }
        }"
        @click="copyCode()"
    @elseif($click)
        @click="{{ $click }}"
    @endif
>
    {{-- Animated border glow - the living gem effect --}}
    <div 
        class="absolute inset-0 rounded-2xl border-glow {{ $scheme['border'] }}"
        @if($isCopyMode) :class="copied && 'border-glow-emerald'" @endif
    ></div>

    {{-- Button surface --}}
    <button 
        type="button"
        class="relative w-full flex items-center gap-4 px-5 py-4 cursor-pointer rounded-2xl
               bg-white dark:bg-secondary-900
               border border-secondary-200/50 dark:border-secondary-700/50
               shadow-sm
               transition-all duration-300 ease-out
               group-hover:shadow-lg
               focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 
               dark:focus-visible:ring-offset-secondary-900 {{ $scheme['focusRing'] }}"
        @if($isCopyMode) :class="copied && '!bg-emerald-500 dark:!bg-emerald-500 !border-emerald-400/50'" @endif
    >
        {{-- Icon container --}}
        <span 
            class="relative shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center 
                   shadow-lg transition-all duration-500 ease-out
                   group-hover:scale-105 group-hover:shadow-xl
                   {{ $scheme['icon'] }} {{ $scheme['iconShadow'] }}"
            @if($isCopyMode) :class="copied && '!from-emerald-400 !to-emerald-500 !shadow-emerald-500/40 scale-105'" @endif
        >
            <span class="absolute inset-0 rounded-xl bg-gradient-to-br from-white/25 to-transparent"></span>
            
            @if($isCopyMode)
                <x-ui.icon 
                    x-show="!copied" 
                    :icon="$icon" 
                    class="relative w-6 h-6 text-white drop-shadow-sm" 
                />
                <x-ui.icon 
                    x-cloak
                    x-show="copied"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-50 rotate-[-20deg]"
                    x-transition:enter-end="opacity-100 scale-100 rotate-0"
                    icon="check" 
                    class="relative w-6 h-6 text-white drop-shadow-sm" 
                />
            @else
                <x-ui.icon :icon="$icon" class="relative w-6 h-6 text-white drop-shadow-sm" />
            @endif
        </span>
        
        {{-- Text content --}}
        <span class="flex-1 text-left rtl:text-right min-w-0">
            @if($isCopyMode)
                <span 
                    class="block font-semibold truncate transition-colors duration-300 {{ $titleColorClass }}"
                    :class="copied && '!text-white'"
                    x-text="copied ? '{{ $titleCopied }}' : '{{ $title }}'"
                ></span>
                <span 
                    x-show="!copied" 
                    class="block text-sm font-mono font-medium tracking-wide truncate {{ $subtitleColorClass }}"
                >{{ $subtitle }}</span>
                <span 
                    x-cloak
                    x-show="copied"
                    x-transition:enter="transition ease-out duration-300 delay-100"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="block text-sm text-emerald-50"
                >{{ $subtitleCopied }}</span>
            @else
                <span class="block font-semibold truncate {{ $titleColorClass }}">{{ $title }}</span>
                <span class="block text-sm truncate {{ $subtitleColorClass }}">{{ $subtitle }}</span>
            @endif
        </span>
        
        {{-- End indicator --}}
        <span 
            class="shrink-0 flex items-center justify-center w-8 h-8 rounded-full 
                   transition-all duration-300 ease-out
                   {{ $scheme['endBg'] }} {{ $scheme['endBgHover'] }}"
            @if($isCopyMode) :class="copied && '!bg-emerald-400/80'" @endif
        >
            @if($isCopyMode)
                <x-ui.icon 
                    x-show="!copied" 
                    :icon="$iconEnd" 
                    class="w-4 h-4 transition-transform duration-300 ease-out group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 {{ $scheme['endIcon'] }}" 
                />
                <x-ui.icon 
                    x-cloak
                    x-show="copied" 
                    icon="check" 
                    class="w-4 h-4 text-white" 
                />
            @else
                <x-ui.icon 
                    :icon="$iconEnd" 
                    class="w-4 h-4 transition-transform duration-300 ease-out group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 {{ $scheme['endIcon'] }}" 
                />
            @endif
        </span>
    </button>
</div>