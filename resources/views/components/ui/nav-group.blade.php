{{-- 
 Reward Loyalty - Proprietary Software
 Copyright (c) 2025 NowSquare. All rights reserved.
 See LICENSE file for terms.

 Navigation Group Component

 Section label with collapsible children.
 Headers are small uppercase labels that organize — they do
 not compete with nav items for attention.
 Active child detection auto-opens the group.

 Pattern reference: Linear, Stripe Dashboard, Vercel.
--}}

@props([
    'title',
    'icon' => null,
    'open' => false,
    'badgeLabel' => null,
    'badgeColor' => 'warning',
    'navGroupChild' => true,
])

<div x-data="{ 
    open: @js($open),
    hasActiveChild: false,
    init() {
        this.hasActiveChild = this.$el.querySelectorAll('[data-nav-active=true]').length > 0;
        if (this.hasActiveChild) {
            this.open = true;
        }
    }
}" class="group/nav-group mt-6 first:mt-0">
    {{-- Section Label --}}
    <button 
        @click="open = !open"
        class="w-full flex items-center gap-2 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-widest transition-colors duration-200 hover:text-secondary-500 dark:hover:text-secondary-400"
        :class="{ 
            'text-secondary-500 dark:text-secondary-400': hasActiveChild,
            'text-secondary-400 dark:text-secondary-500': !hasActiveChild 
        }">
        
        <span class="flex-1 text-left">
            {{ $title }}
        </span>
        
        @if($badgeLabel)
            <x-ui.badge 
                :variant="$badgeColor" 
                size="sm">
                {{ $badgeLabel }}
            </x-ui.badge>
        @endif
        
        <x-ui.icon 
            icon="chevron-right" 
            class="w-3 h-3 transition-transform duration-200 flex-shrink-0 text-secondary-400 dark:text-secondary-500" 
            ::class="{ 'rotate-90': open }" />
    </button>

    {{-- Group Items --}}
    <div 
        x-show="open" 
        x-collapse
        class="mt-1 space-y-1">
        {{ $slot }}
    </div>
</div>
