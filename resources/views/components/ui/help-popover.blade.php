{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Help Popover Component
  
  iOS-style popover for contextual help text with smart positioning and smooth animations.
  
  Props:
  - title (optional) - Popover header text
  - width (optional, default: w-72) - Custom width class
  - icon (optional, default: info) - Lucide icon for trigger button
  - trigger (optional, default: hover) - 'hover' or 'click'
--}}

@props([
    'title' => null,
    'width' => 'w-72',
    'icon' => 'info',
    'trigger' => 'hover',
])

<div 
    x-data="{ 
        open: false,
        trigger: '{{ $trigger }}',
        hideTimeout: null,
        toggle() {
            if (this.trigger === 'click') {
                this.open = !this.open;
            }
        },
        show() {
            if (this.trigger === 'hover') {
                clearTimeout(this.hideTimeout);
                this.open = true;
            }
        },
        hide() {
            if (this.trigger === 'hover') {
                this.hideTimeout = setTimeout(() => {
                    this.open = false;
                }, 150);
            }
        }
    }"
    @click.away="open = false"
    @keydown.escape.window="open = false"
    @mouseenter="show()"
    @mouseleave="hide()"
    class="relative inline-flex"
>
    {{-- Trigger Button --}}
    <button 
        type="button"
        @click="toggle()"
        class="inline-flex items-center justify-center p-0.5 rounded-md 
               text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-300 
               focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:ring-offset-0
               transition-colors duration-200"
        aria-label="Help"
        :aria-expanded="open"
    >
        <x-ui.icon :icon="$icon" class="h-4 w-4" />
    </button>

    {{-- Popover Content --}}
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        x-init="
            $nextTick(() => {
                const rect = $el.getBoundingClientRect();
                const viewportWidth = window.innerWidth;
                
                // Check if popover overflows right edge
                if (rect.right > viewportWidth - 10) {
                    $el.style.left = 'auto';
                    $el.style.right = '0';
                }
            });
        "
        @mouseenter="show()"
        @mouseleave="hide()"
        class="absolute z-9999 {{ $width }} mt-2 left-0
               bg-white dark:bg-secondary-800 
               border border-secondary-200 dark:border-secondary-700 
               rounded-xl shadow-lg 
               p-4"
        style="display: none;"
        @click.stop
    >
        {{-- Optional Title --}}
        @if ($title)
            <div class="text-sm font-semibold text-secondary-900 dark:text-white mb-2">
                {{ $title }}
            </div>
        @endif

        {{-- Content --}}
        <div class="text-sm text-secondary-600 dark:text-secondary-400 leading-relaxed">
            {{ $slot }}
        </div>
    </div>
</div>
