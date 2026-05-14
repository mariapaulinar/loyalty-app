{{--
    Transaction Item Component
    
    A premium transaction list item inspired by Revolut and Monzo.
    Clean iconography, smooth hover states, and clear amount display.
    
    @props
    - transaction: Transaction model
    - showCard: boolean - Show card name (default: false)
    - compact: boolean - Compact mode (default: false)
--}}

@props([
    'transaction',
    'showCard' => false,
    'compact' => false,
])

@php
$isPositive = $transaction->points > 0;

$eventConfig = [
    'purchase' => ['icon' => 'shopping-bag', 'gradient' => 'from-emerald-500 to-teal-600', 'shadow' => 'shadow-emerald-500/20'],
    'bonus' => ['icon' => 'gift', 'gradient' => 'from-amber-500 to-orange-600', 'shadow' => 'shadow-amber-500/20'],
    'reward' => ['icon' => 'award', 'gradient' => 'from-violet-500 to-purple-600', 'shadow' => 'shadow-violet-500/20'],
    'transfer_sent' => ['icon' => 'arrow-up-right', 'gradient' => 'from-rose-500 to-pink-600', 'shadow' => 'shadow-rose-500/20'],
    'transfer_received' => ['icon' => 'arrow-down-left', 'gradient' => 'from-sky-500 to-blue-600', 'shadow' => 'shadow-sky-500/20'],
    'code_redeemed' => ['icon' => 'ticket', 'gradient' => 'from-indigo-500 to-violet-600', 'shadow' => 'shadow-indigo-500/20'],
];

$config = $eventConfig[$transaction->event] ?? ['icon' => 'circle-dot', 'gradient' => 'from-secondary-400 to-secondary-500', 'shadow' => 'shadow-secondary-500/20'];
@endphp

<div class="group flex items-center gap-4 {{ $compact ? 'py-3' : 'py-4 px-4' }} rounded-xl transition-all duration-200 {{ !$compact ? 'hover:bg-secondary-50 dark:hover:bg-secondary-800/50' : '' }}">
    {{-- Icon --}}
    <div class="relative flex-shrink-0">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br {{ $config['gradient'] }} flex items-center justify-center shadow-lg {{ $config['shadow'] }} transition-transform duration-200 group-hover:scale-105">
            <x-ui.icon :icon="$config['icon']" class="w-5 h-5 text-white" />
        </div>
    </div>
    
    {{-- Details --}}
    <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">
            {{ $transaction->description ?? trans('common.transaction') }}
        </p>
        
        <div class="flex items-center gap-2 mt-0.5">
            <time datetime="{{ $transaction->created_at->toIso8601String() }}" 
                  class="text-xs text-secondary-500 dark:text-secondary-400">
                {{ $transaction->created_at->diffForHumans() }}
            </time>
            
            @if($showCard && $transaction->card)
                <span class="text-secondary-300 dark:text-secondary-600">•</span>
                <span class="text-xs text-secondary-500 dark:text-secondary-400 truncate">{{ $transaction->card->name }}</span>
            @endif
        </div>
    </div>
    
    {{-- Amount --}}
    <div class="flex-shrink-0 text-right">
        <p class="text-base font-bold font-mono tracking-tight {{ $isPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-secondary-900 dark:text-white' }}">
            {{ $isPositive ? '+' : '' }}<span class="format-number">{{ $transaction->points }}</span>
        </p>
        <p class="text-xs text-secondary-400 dark:text-secondary-500 uppercase tracking-wider">
            {{ trans('common.points') }}
        </p>
    </div>
</div>
