{{--
    Points Badge Component
    
    An animated points display badge inspired by gaming achievements
    and Robinhood's stock price displays. Perfect for showing balances.
    
    @props
    - points: number - Points to display
    - label: string - Optional label (default: 'Points')
    - size: 'sm'|'md'|'lg' (default: 'md')
    - animated: boolean - Animate on load (default: true)
    - showPlus: boolean - Show + for positive (default: false)
    
    @example
    <x-member.points-badge :points="250" animated />
--}}

@props([
    'points' => 0,
    'label' => 'Points',
    'size' => 'md',
    'animated' => true,
    'showPlus' => false,
])

@php
$sizes = [
    'sm' => 'text-lg',
    'md' => 'text-2xl',
    'lg' => 'text-4xl',
];

$textSize = $sizes[$size] ?? $sizes['md'];
$isPositive = $points > 0;
@endphp

<div class="inline-flex flex-col items-center">
    <div class="font-bold font-mono {{ $textSize }} text-secondary-900 dark:text-white"
         x-data="{ 
             count: 0, 
             target: {{ $points }},
             duration: {{ $animated ? 1000 : 0 }}
         }"
         x-init="
             if (duration > 0) {
                 let start = 0;
                 let startTime = Date.now();
                 let timer = setInterval(() => {
                     let elapsed = Date.now() - startTime;
                     let progress = Math.min(elapsed / duration, 1);
                     count = Math.floor(start + (target - start) * progress);
                     if (progress >= 1) clearInterval(timer);
                 }, 16);
             } else {
                 count = target;
             }
         "
    >
        <span x-text="'{{ $showPlus && $isPositive ? '+' : '' }}' + count.toLocaleString()"></span>
    </div>
    
    @if($label)
        <span class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">
            {{ $label }}
        </span>
    @endif
</div>
