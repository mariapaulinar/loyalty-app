{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Premium QR Code Modal - iOS Premium Edition
Approved by Steve Jobs and Jony Ive (in spirit)

@props
- show: Alpine variable name for visibility (default: 'showQr')
- title: Modal title
- subtitle: Modal subtitle
- qrUrl: URL to encode in QR
- qrColorLight: Light theme QR color (default: '#FCFCFC')
- qrColorDark: Dark theme QR color (default: '#1F1F1F')
- identifier: Optional identifier text to show below QR
- identifierLabel: Label for identifier (default: 'Identifier')
- iconColor: Gradient color for icon badge (default: 'primary')
- enableCache: Enable PWA offline caching (default: false)
- cardId: Unique card ID for multi-card caching (required for caching)
- cardName: Card name for cache info (optional)
- cardBalance: Card balance for cache info (optional)
--}}

@props([
    'show' => 'showQr',
    'title',
    'subtitle',
    'qrUrl',
    'qrColorLight' => '#FCFCFC',
    'qrColorDark' => '#1F1F1F',
    'identifier' => null,
    'identifierLabel' => 'Identifier',
    'iconColor' => 'primary',
    'enableCache' => false,
    'cardId' => null,
    'cardName' => null,
    'cardBalance' => null,
])

@php
    $iconGradients = [
        'primary' => 'from-primary-500 to-primary-600 shadow-primary-500/25',
        'amber' => 'from-amber-500 to-orange-600 shadow-amber-500/25',
        'emerald' => 'from-emerald-500 to-emerald-600 shadow-emerald-500/25',
    ];
    $gradient = $iconGradients[$iconColor] ?? $iconGradients['primary'];
@endphp

<div x-show="{{ $show }}" 
     style="display: none;"
     @click.self="{{ $show }} = false"
     class="fixed inset-0 z-[60] flex items-center justify-center px-4 bg-black/80 backdrop-blur-sm"
     x-transition:enter="transition ease-out duration-300" 
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100" 
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100" 
     x-transition:leave-end="opacity-0">

    <div @click.away="{{ $show }} = false"
         class="relative bg-white dark:bg-secondary-900 w-full max-w-sm rounded-3xl p-8 shadow-2xl transform"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-90 translate-y-4">
        
        {{-- Premium Close Button --}}
        <button @click="{{ $show }} = false"
                class="absolute top-4 right-4 w-10 h-10 rounded-full bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center hover:bg-secondary-200 dark:hover:bg-secondary-700 transition-all duration-200 hover:scale-110">
            <x-ui.icon icon="x" class="w-5 h-5 text-secondary-600 dark:text-secondary-400" />
        </button>
        
        {{-- Content --}}
        <div class="text-center space-y-6">
            {{-- Icon Badge - The Premium Touch --}}
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br {{ $gradient }} shadow-xl mb-2">
                <x-ui.icon icon="qr-code" class="w-8 h-8 text-white" />
            </div>
            
            {{-- Title & Subtitle --}}
            <div class="space-y-2">
                <h3 class="text-2xl font-bold text-secondary-900 dark:text-white">{{ $title }}</h3>
                <p class="text-sm text-secondary-500 dark:text-secondary-400">{{ $subtitle }}</p>
            </div>

            {{-- QR Code Container - Clean & Minimal --}}
            <div class="bg-white dark:bg-white p-6 rounded-2xl shadow-inner border border-secondary-200 dark:border-secondary-300 inline-block"
                 @if($enableCache && ($cardName || $cardBalance))
                     data-card-info
                     @if($cardName) data-card-name="{{ $cardName }}" @endif
                     @if($cardBalance) data-card-balance="{{ $cardBalance }}" @endif
                 @endif>
                <img src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs="
                     class="w-56 h-56 object-contain qr-code" 
                     data-qr-url="{{ $qrUrl }}"
                     data-qr-color-light="{{ $qrColorLight }}"
                     data-qr-color-dark="{{ $qrColorDark }}"
                     @if($enableCache) data-qr-cache @endif
                     @if($cardId) data-card-id="{{ $cardId }}" @endif
                     alt="QR Code" />
            </div>

            {{-- Identifier - Beautiful Typography --}}
            @if($identifier)
                <div class="space-y-1.5 pt-2">
                    <div class="text-xs font-bold uppercase tracking-widest text-secondary-400">
                        {{ trans('common.' . strtolower($identifierLabel)) }}
                    </div>
                    <div class="font-mono text-xl font-bold text-secondary-900 dark:text-white tracking-wider">
                        {{ $identifier }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
