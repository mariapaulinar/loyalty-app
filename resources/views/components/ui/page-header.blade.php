{{--
    Reward Loyalty - Proprietary Software
    Copyright (c) 2025 NowSquare. All rights reserved.
    See LICENSE file for terms.

    Universal Page Header Component
    
    Purpose:
    Provides consistent, beautiful page headers across ALL views (CRUD, custom pages, analytics).
    Inspired by Linear's clean hierarchy and Stripe's elegant spacing.
    
    Design Philosophy:
    - Information hierarchy: Icon → Title → Description → Actions (left to right)
    - Breathing room: Generous padding, clear visual separation
    - Mobile-first: Stacks vertically on small screens
    - Flexible: Slots for custom content, breadcrumbs optional
    
    Usage:
    <x-ui.page-header
        icon="credit-card"
        :title="trans('common.loyalty_cards')"
        :description="trans('common.manage_your_cards')"
        :breadcrumbs="[...]"
    >
        <x-slot name="actions">
            <a href="#" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
                <x-ui.icon icon="plus" class="w-4 h-4" />
                Add New
            </a>
        </x-slot>
    </x-ui.page-header>
--}}

@props([
    'icon' => null,
    'iconBg' => 'primary', // DEPRECATED: Accepted for backward compat but ignored. Icons render bare.
    'title' => null,
    'description' => null,
    'breadcrumbs' => null,
    'badge' => null, // Optional badge (e.g., count, status)
    'compact' => false, // Smaller padding for nested contexts
])


<div {{ $attributes->merge(['class' => ($compact ? 'mb-6' : 'mb-8')]) }}>
    {{-- Breadcrumbs (if provided) --}}
    @if($breadcrumbs)
        <div class="mb-4">
            <x-ui.breadcrumb :crumbs="$breadcrumbs" />
        </div>
    @endif

    {{-- Main Header Content --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        {{-- Left Side: Icon, Title, Description --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-start gap-3">
                {{-- Custom Icon Slot OR Standard Icon --}}
                @if(isset($icon) && $icon instanceof \Illuminate\View\ComponentSlot)
                    <div class="shrink-0">
                        {{ $icon }}
                    </div>
                @elseif($icon)
                    {{-- Icon container height matches title line-height (text-2xl=h-8, md:text-3xl=h-9) for perfect vertical centering --}}
                    <div class="shrink-0 h-8 md:h-9 flex items-center">
                        <x-ui.icon :icon="$icon" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                    </div>
                @endif

                {{-- Title & Description --}}
                <div class="flex-1 min-w-0 @if(!$description) flex items-center min-h-[40px] @endif">
                    <div class="flex items-center gap-3 flex-wrap">
                        {{-- Title --}}
                        @if($title)
                            <h1 class="text-2xl md:text-3xl font-bold text-secondary-900 dark:text-white tracking-tight">
                                {!! $title !!}
                            </h1>
                        @endif

                        {{-- Badge (optional) --}}
                        @if($badge)
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-secondary-100 dark:bg-secondary-800 text-secondary-600 dark:text-secondary-400">
                                {!! $badge !!}
                            </span>
                        @endif
                    </div>

                    {{-- Description (optional) --}}
                    @if($description)
                        <p class="mt-2 text-sm text-secondary-600 dark:text-secondary-400 leading-relaxed max-w-3xl">
                            {!! $description !!}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Side: Actions (slot) --}}
        @if(isset($actions))
            <div class="w-full lg:w-auto lg:shrink-0">
                <div class="flex flex-wrap items-center gap-3 lg:justify-end">
                    {{ $actions }}
                </div>
            </div>
        @endif
    </div>

    {{-- Optional Default Slot (for extra content below header) --}}
    @if(isset($slot) && trim($slot))
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>
