{{--
    Reward Card Component
    
    A beautiful reward display card inspired by Smile.io's reward marketplace.
    Shows image, points required, and claim status.
    
    @props
    - reward: Reward model
    - card: Card model - For checking member balance
    - size: 'sm'|'md'|'lg' (default: 'md')
    - layout: 'vertical'|'horizontal' (default: 'vertical')
    
    @example
    <x-member.reward-card :reward="$reward" :card="$card" />
--}}

@props([
    'reward',
    'card' => null,
    'size' => 'md',
    'layout' => 'vertical',
    'showButton' => true,
])

@php
$memberBalance = $card ? $card->getMemberBalance(auth('member')->user()) : 0;
$canClaim = $memberBalance >= $reward->points;
$pointsNeeded = max(0, $reward->points - $memberBalance);
$progress = $reward->points > 0 ? min(($memberBalance / $reward->points) * 100, 100) : 0;

// Get image URL from images array or fallback
$imageUrl = null;
if ($reward->images && count($reward->images) > 0) {
    $imageUrl = $reward->images[0]['md'] ?? $reward->images[0]['sm'] ?? null;
}

$sizes = [
    'sm' => 'w-48',
    'md' => 'w-64',
    'lg' => 'w-80',
    'full' => 'w-full',
];
@endphp

<div class="{{ $layout === 'vertical' ? $sizes[$size] : 'w-full' }} group">
    <x-ui.card :hover="true" padding="p-0" class="overflow-hidden h-full">
        @if($layout === 'vertical')
            {{-- Vertical Layout --}}
            {{-- Image --}}
            @if($imageUrl)
                <div class="aspect-video w-full overflow-hidden bg-secondary-100 dark:bg-secondary-800">
                    <img 
                        src="{{ $imageUrl }}" 
                        alt="{{ $reward->title }}"
                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                    >
                </div>
            @else
                <div class="aspect-video w-full bg-gradient-to-br from-primary-600 via-primary-500 to-primary-400 flex items-center justify-center relative overflow-hidden">
                    {{-- Decorative pattern --}}
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-4 left-4 w-32 h-32 border-2 border-white rounded-full"></div>
                        <div class="absolute bottom-4 right-4 w-24 h-24 border-2 border-white rounded-full"></div>
                    </div>
                    <x-ui.icon icon="gift" class="w-16 h-16 text-white/70" />
                </div>
            @endif
            
            {{-- Content --}}
            <div class="p-4">
                <h3 class="font-semibold text-secondary-900 dark:text-white mb-2 line-clamp-2 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                    {{ $reward->title }}
                </h3>
                
                @if($reward->description)
                    <p class="text-sm text-secondary-600 dark:text-secondary-400 mb-4 line-clamp-2">
                        {{ $reward->description }}
                    </p>
                @endif
                
                {{-- Points Required --}}
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm text-secondary-500 dark:text-secondary-400">
                        {{ trans('common.points') }}
                    </span>
                    <span class="text-lg font-bold font-mono text-secondary-900 dark:text-white format-number">
                        {{ $reward->points }}
                    </span>
                </div>
                
                {{-- Progress Bar --}}
                @if($card && !$canClaim)
                    <x-ui.progress-bar 
                        :current="$memberBalance" 
                        :max="$reward->points"
                        size="sm"
                        :showPercentage="false"
                        class="mb-3"
                    />
                    <p class="text-xs text-secondary-500 dark:text-secondary-400 mb-3">
                        <span class="format-number">{{ $pointsNeeded }}</span> points away
                    </p>
                @endif
                
                {{-- Claim Button --}}
                @if($showButton)
                    @if($canClaim)
                        <x-ui.button variant="primary" size="sm" class="w-full">
                            <x-ui.icon icon="gift" class="w-4 h-4" />
                            {{ trans('common.claim') }}
                        </x-ui.button>
                    @elseif($card)
                        <x-ui.button variant="secondary" size="sm" class="w-full" disabled>
                            {{ trans('common.insufficient_points') }}
                        </x-ui.button>
                    @endif
                @endif
            </div>
        @else
            {{-- Horizontal Layout --}}
            <div class="flex gap-4 p-4">
                {{-- Image --}}
                @if($imageUrl)
                    <div class="w-24 h-24 flex-shrink-0 rounded-xl overflow-hidden bg-secondary-100 dark:bg-secondary-800">
                        <img 
                            src="{{ $imageUrl }}" 
                            alt="{{ $reward->title }}"
                            class="w-full h-full object-cover"
                        >
                    </div>
                @endif
                
                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-secondary-900 dark:text-white mb-1">
                        {{ $reward->title }}
                    </h3>
                    <p class="text-sm text-secondary-600 dark:text-secondary-400 mb-2 line-clamp-1">
                        {{ $reward->description }}
                    </p>
                    <div class="flex items-center gap-2">
                        <x-ui.badge variant="primary">
                            <span class="format-number">{{ $reward->points }}</span> pts
                        </x-ui.badge>
                        @if($canClaim)
                            <x-ui.badge variant="success" dot>
                                Claim Now
                            </x-ui.badge>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </x-ui.card>
</div>
