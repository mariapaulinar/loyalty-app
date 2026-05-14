@extends('member.layouts.default')

{{-- SEO: Title with proper hierarchy --}}
@section('page_title', $reward->title . config('default.page_title_delimiter') . $card->head)

{{-- SEO: Rich meta for social sharing --}}
@section('meta_description', $reward->description ?? trans('common.reward_meta_description', ['reward' => $reward->title, 'points' => number_format($reward->points), 'card' => $card->head]))
@section('meta_image', $reward->image1 ?? $card->getFirstMediaUrl('logo', 'md'))
@section('meta_type', 'product')

@section('content')
<div class="space-y-6 max-w-2xl mx-auto px-4 md:px-8 py-8 md:py-8">
    {{-- Simple Back Button (Mobile-First, consistent with claim page) --}}
    <div class="animate-fade-in">
        <a href="{{ route('member.card', ['card_id' => $card->id]) }}" 
           class="inline-flex items-center gap-2 text-sm font-medium text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white transition-colors">
            <x-ui.icon icon="arrow-left" class="w-4 h-4" />
            <span>{{ $card->head }}</span>
        </a>
    </div>

    {{-- Main Card --}}
    <div
        class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 shadow-sm overflow-hidden animate-fade-in-up">
        {{-- Image Gallery --}}
        @if ($reward->images && count($reward->images) > 0)
            <div x-data="{ 
                        activeIndex: 0, 
                        images: {{ json_encode($reward->images) }},
                        lightboxOpen: false,
                        touchStartX: 0,
                        touchEndX: 0,
                        handleSwipe() {
                            if (this.touchEndX < this.touchStartX - 50) this.next();
                            if (this.touchEndX > this.touchStartX + 50) this.prev();
                        },
                        next() { this.activeIndex = (this.activeIndex + 1) % this.images.length },
                        prev() { this.activeIndex = (this.activeIndex - 1 + this.images.length) % this.images.length }
                    }" class="relative group">

                {{-- Main Image --}}
                <div class="relative overflow-hidden bg-secondary-100 dark:bg-secondary-900"
                    style="aspect-ratio: {{ $reward->images[0]['ratio'] ?? '16/9' }};"
                    @touchstart="touchStartX = $event.changedTouches[0].screenX"
                    @touchend="touchEndX = $event.changedTouches[0].screenX; handleSwipe()">

                    @foreach ($reward->images as $index => $image)
                        <div x-show="activeIndex === {{ $index }}" x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-105" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                            class="absolute inset-0 cursor-zoom-in" @click="lightboxOpen = true">
                            <img src="{{ $image['md'] }}" alt="{{ parse_attr($reward->title) }}"
                                class="w-full h-full object-cover">
                        </div>
                    @endforeach

                    {{-- Navigation Arrows (shown on hover for desktop, always on mobile) --}}
                    @if(count($reward->images) > 1)
                        <button @click.stop="prev()"
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 dark:bg-secondary-800/90 backdrop-blur-sm flex items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 md:group-hover:opacity-100 transition-all duration-300 hover:scale-110 active:scale-95">
                            <x-ui.icon icon="chevron-left" class="w-5 h-5 text-secondary-700 dark:text-secondary-300" />
                        </button>
                        <button @click.stop="next()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 dark:bg-secondary-800/90 backdrop-blur-sm flex items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 md:group-hover:opacity-100 transition-all duration-300 hover:scale-110 active:scale-95">
                            <x-ui.icon icon="chevron-right" class="w-5 h-5 text-secondary-700 dark:text-secondary-300" />
                        </button>

                        {{-- Dots Indicator --}}
                        <div
                            class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2 px-3 py-1.5 rounded-full bg-black/30 backdrop-blur-sm">
                            @foreach ($reward->images as $index => $image)
                                <button @click.stop="activeIndex = {{ $index }}"
                                    class="w-2 h-2 rounded-full transition-all duration-300"
                                    :class="activeIndex === {{ $index }} ? 'bg-white w-6' : 'bg-white/50 hover:bg-white/80'">
                                </button>
                            @endforeach
                        </div>

                        {{-- Image Counter --}}
                        <div
                            class="absolute top-4 right-4 px-3 py-1.5 rounded-full bg-black/30 backdrop-blur-sm text-white text-sm font-medium">
                            <span x-text="activeIndex + 1"></span> / {{ count($reward->images) }}
                        </div>
                    @endif

                    {{-- Expand Icon --}}
                    <div
                        class="absolute top-4 left-4 w-10 h-10 rounded-full bg-black/30 backdrop-blur-sm flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                        <x-ui.icon icon="maximize-2" class="w-5 h-5" />
                    </div>
                </div>

                {{-- Thumbnail Strip (for multiple images) --}}
                @if(count($reward->images) > 1)
                    <div class="flex gap-2 p-4 overflow-x-auto scrollbar-thin">
                        @foreach ($reward->images as $index => $image)
                            <button @click="activeIndex = {{ $index }}"
                                class="flex-shrink-0 w-16 h-16 rounded-xl overflow-hidden border-2 transition-all duration-300"
                                :class="activeIndex === {{ $index }} ? 'border-primary-500 ring-2 ring-primary-500/20' : 'border-transparent opacity-60 hover:opacity-100'">
                                <img src="{{ $image['sm'] ?? $image['md'] }}" alt="{{ parse_attr($reward->title) }}"
                                    class="w-full h-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Lightbox Modal (teleported to body to escape overflow-hidden) --}}
                <template x-teleport="body">
                    <div x-show="lightboxOpen" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0" @click="lightboxOpen = false"
                        @keydown.escape.window="lightboxOpen = false"
                        class="fixed inset-0 z-[9999] bg-black/95 flex items-center justify-center" x-cloak>

                        {{-- Close Button --}}
                        <button @click="lightboxOpen = false"
                            class="absolute top-4 right-4 z-10 w-12 h-12 rounded-full bg-white/10 backdrop-blur-sm flex items-center justify-center text-white hover:bg-white/20 transition-colors">
                            <x-ui.icon icon="x" class="w-6 h-6" />
                        </button>

                        {{-- Image Container --}}
                        <div @click.stop class="relative w-full h-full flex items-center justify-center p-4 md:p-8"
                            @touchstart="touchStartX = $event.changedTouches[0].screenX"
                            @touchend="touchEndX = $event.changedTouches[0].screenX; handleSwipe()">
                            <template x-for="(image, index) in images" :key="index">
                                <img x-show="activeIndex === index" x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100" :src="image.lg || image.md"
                                    :alt="'{{ parse_attr($reward->title) }}'" class="max-w-full max-h-full object-contain">
                            </template>
                        </div>

                        {{-- Navigation in Lightbox --}}
                        @if(count($reward->images) > 1)
                            <button @click.stop="prev()"
                                class="absolute left-4 top-1/2 -translate-y-1/2 w-14 h-14 rounded-full bg-white/10 backdrop-blur-sm flex items-center justify-center text-white hover:bg-white/20 transition-colors">
                                <x-ui.icon icon="chevron-left" class="w-7 h-7" />
                            </button>
                            <button @click.stop="next()"
                                class="absolute right-4 top-1/2 -translate-y-1/2 w-14 h-14 rounded-full bg-white/10 backdrop-blur-sm flex items-center justify-center text-white hover:bg-white/20 transition-colors">
                                <x-ui.icon icon="chevron-right" class="w-7 h-7" />
                            </button>

                            {{-- Lightbox Counter --}}
                            <div
                                class="absolute bottom-4 left-1/2 -translate-x-1/2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-sm text-white font-medium">
                                <span x-text="activeIndex + 1"></span> / {{ count($reward->images) }}
                            </div>
                        @endif
                    </div>
                </template>
            </div>
        @endif

        {{-- Secondary Action: Contact --}}
        <div class="animate-fade-in-up delay-200 mb-6">
            <div class="flex items-center justify-center">
                <x-member.card-contact :card="$card" />
            </div>
        </div>

        {{-- Content --}}
        <div class="p-6 md:p-8 space-y-6">
            {{-- Title & Description (Centered for focus) --}}
            <div class="text-center">
                <h1 class="text-2xl md:text-3xl font-bold text-secondary-900 dark:text-white mb-3">
                    {{ $reward->title }}
                </h1>
                @if($reward->description)
                    <div class="prose prose-secondary dark:prose-invert prose-sm max-w-none text-secondary-600 dark:text-secondary-400 mx-auto">
                        {!! $reward->description !!}
                    </div>
                @endif
            </div>

            {{-- Points Transaction Card (Like claim page) --}}
            <div class="bg-secondary-50 dark:bg-secondary-900/50 rounded-2xl p-5 space-y-3 border border-secondary-200/80 dark:border-secondary-700/50">
                {{-- Cost --}}
                <div class="flex items-center justify-between @if(auth('member')->check()) pb-3 border-b border-secondary-200 dark:border-secondary-700 @endif">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-md shadow-amber-500/20">
                            <x-ui.icon icon="coins" class="w-4.5 h-4.5 text-white" />
                        </div>
                        <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300">{{ trans('common.cost') }}</span>
                    </div>
                    <span class="text-2xl font-bold text-secondary-900 dark:text-white format-number">
                        {{ $reward->points }}
                    </span>
                </div>
                
                {{-- Balance (if logged in) --}}
                @if(auth('member')->check())
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-secondary-600 dark:text-secondary-400">{{ trans('common.your_balance') }}</span>
                        <span class="font-semibold text-secondary-900 dark:text-white format-number">
                            {{ $balance }}
                        </span>
                    </div>
                    
                    {{-- Balance After --}}
                    <div class="flex items-center justify-between text-sm pt-3 border-t border-secondary-200 dark:border-secondary-700">
                        <span class="text-secondary-600 dark:text-secondary-400">{{ trans('common.balance_after_redemption') }}</span>
                        <span class="font-semibold format-number {{ ($balance - $reward->points) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ max(0, $balance - $reward->points) }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- CTA Button (Centered, Gold for rewards!) --}}
            <div class="flex justify-center pt-2">
                @if($card->isExpired)
                    <div class="inline-flex items-center gap-2 px-5 py-3.5 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 font-medium">
                        <x-ui.icon icon="alert-triangle" class="w-4 h-4" />
                        <span>{{ trans('common.card_expired') }}</span>
                    </div>
                @else
                    @if(auth('member')->check())
                        @if($reward->points <= $balance)
                            {{-- Ready to claim - Gold gradient! --}}
                            <a href="{{ route('member.card.reward.claim', ['card_id' => $card->id, 'reward_id' => $reward->id]) }}"
                                class="group inline-flex items-center justify-center gap-2.5 px-8 py-3.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-white font-semibold rounded-xl shadow-lg shadow-amber-500/30 hover:shadow-amber-500/50 transition-all duration-300 hover:-translate-y-0.5 hover:scale-105">
                                <x-ui.icon icon="gift" class="w-5 h-5 group-hover:scale-110 transition-transform" />
                                <span>{{ trans('common.claim_reward') }}</span>
                            </a>
                        @else
                            {{-- Insufficient points - muted --}}
                            <div class="inline-flex items-center gap-2 px-5 py-3.5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 font-medium">
                                <x-ui.icon icon="coins" class="w-4 h-4" />
                                <span>{{ trans('common.need') }} <span class="format-number">{{ $reward->points - $balance }}</span> {{ trans('common.more_points') }}</span>
                            </div>
                        @endif
                    @else
                        {{-- Login required --}}
                        <a href="{{ route('member.login') }}"
                            class="group inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-secondary-900 dark:bg-white hover:bg-secondary-800 dark:hover:bg-secondary-100 text-white dark:text-secondary-900 font-semibold rounded-xl shadow-lg transition-all duration-300">
                            <x-ui.icon icon="log-in" class="w-5 h-5 group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 transition-transform" />
                            <span>{{ trans('common.log_in_to_claim_reward') }}</span>
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- More Rewards (Increased top spacing for breathing room) --}}
    <div class="bg-white mt-8 dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 shadow-sm animate-fade-in-up" style="animation-delay: 200ms;">
        <div class="p-6">
            <x-member.rewards :card="$card" :current-reward="$reward" :show-claimable="true" />
        </div>
    </div>

    {{-- Tier Status for This Club (Contextual - Above Add/Remove) --}}
    @if(isset($memberTierData) && $memberTierData)
        <div class="w-full max-w-2xl mx-auto mt-8">
            <x-member.tier-status 
                :memberTier="$memberTierData['memberTier']"
                :club="$memberTierData['club']"
                :card="$memberTierData['card']"
                :progress="$memberTierData['progress']" />
        </div>
    @endif

    {{-- Add/Remove Card Button - Below tier, above share (uniform spacing) --}}
    <div class="w-full max-w-2xl mx-auto">
        <x-member.follow-card :card="$card" />
    </div>

    {{-- Share - Page utility at the end --}}
    @if(!$card->isExpired)
        <div class="w-full max-w-lg mx-auto flex justify-center">
            <x-ui.share :url="url()->current()" :text="$card->head . ' - ' . $reward->title" size="lg" />
        </div>
    @endif
</div>
@stop