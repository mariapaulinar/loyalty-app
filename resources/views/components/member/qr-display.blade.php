{{--
    QR Display Component
    
    A premium QR code presentation inspired by Apple Wallet's pass display.
    Clean, centered, with optional instructions and branding.
    
    @props
    - qrCode: string - QR code SVG or image
    - title: string - Optional title
    - instructions: string - Optional instructions
    - size: 'sm'|'md'|'lg' (default: 'md')
    
    @example
    <x-member.qr-display 
        :qrCode="$card->qr_code" 
        title="Show this code at checkout"
    />
--}}

@props([
    'qrCode' => '',
    'title' => null,
    'instructions' => null,
    'size' => 'md',
])

@php
$sizes = [
    'sm' => 'w-48 h-48',
    'md' => 'w-64 h-64',
    'lg' => 'w-80 h-80',
];

$qrSize = $sizes[$size] ?? $sizes['md'];
@endphp

<div class="flex flex-col items-center text-center">
    {{-- Title --}}
    @if($title)
        <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-4">
            {{ $title }}
        </h3>
    @endif
    
    {{-- QR Code --}}
    <div class="relative {{ $qrSize }} p-6 bg-white dark:bg-white rounded-3xl shadow-2xl">
        {{-- Decorative corners --}}
        <div class="absolute top-4 left-4 w-6 h-6 border-t-4 border-l-4 border-primary-600 rounded-tl-lg"></div>
        <div class="absolute top-4 right-4 w-6 h-6 border-t-4 border-r-4 border-primary-600 rounded-tr-lg"></div>
        <div class="absolute bottom-4 left-4 w-6 h-6 border-b-4 border-l-4 border-primary-600 rounded-bl-lg"></div>
        <div class="absolute bottom-4 right-4 w-6 h-6 border-b-4 border-r-4 border-primary-600 rounded-br-lg"></div>
        
        {{-- QR Code Content --}}
        <div class="w-full h-full flex items-center justify-center">
            {!! $qrCode !!}
        </div>
    </div>
    
    {{-- Instructions --}}
    @if($instructions)
        <p class="text-sm text-secondary-600 dark:text-secondary-400 mt-4 max-w-xs">
            {{ $instructions }}
        </p>
    @endif
    
    {{-- Scan Animation Hint --}}
    <div class="mt-6 flex items-center gap-2 text-xs text-secondary-500 dark:text-secondary-400">
        <x-ui.icon icon="scan" class="w-4 h-4 animate-pulse" />
        <span>Ready to scan</span>
    </div>
</div>
