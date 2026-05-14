{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Generic Modal Component - Premium Design
Reusable modal with Alpine.js integration

@props
- show: Alpine variable name for visibility control
- maxWidth: Maximum width class (default: 'max-w-md')
- closeable: Whether clicking outside closes the modal (default: true)
- closeButton: Whether to show close button (default: true)
--}}

@props([
    'show' => 'showModal',
    'maxWidth' => 'max-w-md',
    'closeable' => true,
    'closeButton' => true,
])

<div x-show="{{ $show }}" 
     style="display: none;"
     x-effect="document.body.style.overflow = {{ $show }} ? 'hidden' : ''"
     @if($closeable) @click.self="{{ $show }} = false" @endif
     class="fixed inset-0 z-[60] flex items-center justify-center px-4 bg-black/80 backdrop-blur-sm min-h-screen"
     x-transition:enter="transition ease-out duration-300" 
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100" 
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100" 
     x-transition:leave-end="opacity-0">

    <div @if($closeable) @click.away="{{ $show }} = false" @endif
         class="relative bg-white dark:bg-secondary-900 w-full {{ $maxWidth }} rounded-2xl shadow-2xl transform overflow-hidden"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-90 translate-y-4">
        
        {{-- Close Button --}}
        @if($closeButton)
            <button @click="{{ $show }} = false"
                    type="button"
                    class="absolute top-4 right-4 w-8 h-8 rounded-full bg-secondary-100 dark:bg-secondary-800 
                           flex items-center justify-center 
                           hover:bg-secondary-200 dark:hover:bg-secondary-700 
                           transition-all duration-200 hover:scale-110 z-10">
                <x-ui.icon icon="x" class="w-4 h-4 text-secondary-600 dark:text-secondary-400" />
            </button>
        @endif
        
        {{-- Modal Content --}}
        <div class="p-6">
            {{ $slot }}
        </div>
    </div>
</div>
