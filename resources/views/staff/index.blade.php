{{--
    Reward Loyalty - Proprietary Software
    Copyright (c) 2025 NowSquare. All rights reserved.
    See LICENSE file for terms.

    Staff Dashboard - Mobile-First Quick Action Hub
    iOS x Revolut Design Standard

    Design Philosophy:
    - Mobile-first with large tap targets (44x44 minimum)
    - Clean, sophisticated aesthetic with subtle depth
    - Fast, intuitive customer lookup
    - Game-like engagement metrics
--}}

@extends('staff.layouts.default')

@section('page_title', trans('common.staff_member') . config('default.page_title_delimiter') . trans('common.dashboard') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div x-data="staffDashboard()">

    <div class="w-full max-w-2xl mx-auto px-4 py-6 md:px-6 md:py-10">
        {{-- ═══════════════════════════════════════════════════════════════════════
            HERO SECTION - Friendly, Personalized Greeting
        ═══════════════════════════════════════════════════════════════════════ --}}
        <header class="mb-8 animate-fade-in">
            <div class="space-y-2">
                <h1 class="text-2xl md:text-3xl font-bold text-secondary-900 dark:text-white tracking-tight">
                    <span x-data="{ greeting: '{{ $greeting }}' }" x-init="
                        const h = new Date().getHours();
                        greeting = h >= 5 && h < 12 ? '{{ trans('common.good_morning') }}'
                            : h < 17 ? '{{ trans('common.good_afternoon') }}'
                            : h < 21 ? '{{ trans('common.good_evening') }}'
                            : '{{ trans('common.good_night') }}';
                    " x-text="greeting">{{ $greeting }}</span>, <span class="text-primary-600 dark:text-primary-400">{{ auth('staff')->user()->name }}</span>
                </h1>
                
                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                    {!! trans('common.staffDashboardBlocksTitle') !!}
                </p>
            </div>
        </header>

        {{-- ═══════════════════════════════════════════════════════════════════════
            PRIMARY ACTION: Customer Search
            iOS x Revolut: Large, prominent, elegant focus states
        ═══════════════════════════════════════════════════════════════════════ --}}
        <section class="mb-6 animate-fade-in relative" 
            style="animation-delay: 80ms;" 
            x-data="memberSearch()"
            :class="{ 'z-50': showResults || isSearching || showNoResults }">
            <div class="bg-white dark:bg-secondary-900 rounded-3xl border border-secondary-100 dark:border-secondary-800 
                shadow-sm hover:shadow-lg hover:shadow-secondary-900/[0.03] dark:hover:shadow-black/10
                transition-all duration-300"
                x-on:click.outside="clearSearch()">
                
                {{-- Search Input - Large, Prominent --}}
                <div class="p-5 md:p-6">
                    <div class="relative">
                        {{-- Search Icon --}}
                        <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                            <x-ui.icon icon="search" class="w-5 h-5 text-secondary-400 transition-colors duration-200" 
                                ::class="{ 'text-primary-500': focused }" />
                        </div>
                        
                        {{-- Input Field - Large Tap Target --}}
                        <input 
                            type="text"
                            x-model="searchQuery"
                            x-on:input.debounce.300ms="search()"
                            x-on:focus="focused = true"
                            x-on:blur="focused = false"
                            x-on:keydown.escape="clearSearch()"
                            x-on:keydown.down.prevent="highlightNext()"
                            x-on:keydown.up.prevent="highlightPrev()"
                            x-on:keydown.enter.prevent="selectHighlighted()"
                            placeholder="{{ trans('common.find_customer_by_email_or_name') }}"
                            class="w-full bg-secondary-50 dark:bg-secondary-800 
                                border-2 border-secondary-100 dark:border-secondary-700 
                                text-secondary-900 dark:text-white text-base md:text-lg
                                rounded-2xl pl-14 pr-5 py-4 md:py-5
                                placeholder:text-secondary-400 dark:placeholder:text-secondary-500 
                                hover:border-secondary-200 dark:hover:border-secondary-600 
                                focus:outline-none focus:border-primary-500 focus:bg-white dark:focus:bg-secondary-900
                                focus:shadow-lg focus:shadow-primary-500/10
                                transition-all duration-300"
                        />
                        
                        {{-- Clear Button --}}
                        <button 
                            x-show="searchQuery.length > 0"
                            x-on:click="clearSearch()"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-90"
                            x-transition:enter-end="opacity-100 scale-100"
                            class="absolute inset-y-0 right-0 pr-5 flex items-center"
                            type="button">
                            <div class="w-7 h-7 rounded-full bg-secondary-200 dark:bg-secondary-700 flex items-center justify-center
                                hover:bg-secondary-300 dark:hover:bg-secondary-600 transition-colors duration-200">
                                <x-ui.icon icon="x" class="w-4 h-4 text-secondary-500 dark:text-secondary-400" />
                            </div>
                        </button>
                    </div>
                </div>
            </div>
            
            {{-- ═══════════════════════════════════════════════════════════════════════
                Search Results Dropdown - Full Width Overlay (Outside card for proper z-index)
            ═══════════════════════════════════════════════════════════════════════ --}}
            <div 
                x-show="showResults || isSearching || showNoResults"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                style="display: none;"
                class="absolute left-0 right-0 top-full mt-2 bg-white dark:bg-secondary-900 
                    rounded-2xl border border-secondary-100 dark:border-secondary-800 
                    shadow-2xl shadow-secondary-900/20 dark:shadow-black/40 
                    overflow-hidden z-[100]">
                    
                {{-- Loading State: Shimmer Skeletons --}}
                    <div x-show="isSearching" class="p-2">
                        {{-- Indeterminate Progress Bar --}}
                        <div class="h-0.5 bg-secondary-100 dark:bg-secondary-800 rounded-full overflow-hidden mb-2">
                            <div class="h-full bg-primary-500 rounded-full animate-shimmer-slide"></div>
                        </div>
                        
                        {{-- Skeleton Cards --}}
                        <template x-for="i in 3" :key="i">
                            <div class="flex items-center gap-4 p-4 animate-pulse">
                                <div class="w-12 h-12 rounded-full bg-secondary-200 dark:bg-secondary-700"></div>
                                <div class="flex-1 space-y-2">
                                    <div class="h-4 bg-secondary-200 dark:bg-secondary-700 rounded-lg w-3/4"></div>
                                    <div class="h-3 bg-secondary-100 dark:bg-secondary-800 rounded-lg w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    {{-- No Results State --}}
                    <div x-show="showNoResults && !isSearching" class="p-8 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center">
                            <x-ui.icon icon="user-search" class="w-8 h-8 text-secondary-400 dark:text-secondary-500" />
                        </div>
                        <p class="text-base font-semibold text-secondary-900 dark:text-white mb-1">
                            {{ trans('common.no_members_found') }}
                        </p>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400">
                            {{ trans('common.try_searching_by_name_or_email') }}
                        </p>
                    </div>
                    
                    {{-- Results List --}}
                    <div x-show="results.length > 0 && !isSearching" class="max-h-[320px] overflow-y-auto">
                        <template x-for="(result, index) in results" :key="result.id">
                            <a 
                                :href="result.url"
                                x-on:mouseenter="highlightedIndex = index"
                                :class="{ 'bg-primary-50 dark:bg-primary-500/10': highlightedIndex === index }"
                                class="flex items-center gap-4 p-4 hover:bg-secondary-50 dark:hover:bg-secondary-800/50 
                                    border-b border-secondary-100 dark:border-secondary-800 last:border-0 
                                    transition-colors duration-150 min-h-[72px]"
                                x-ref="resultItem">
                                
                                {{-- Avatar with Type Badge --}}
                                <div class="flex-shrink-0 relative">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 
                                        flex items-center justify-center text-white font-semibold text-sm">
                                        <span x-text="result.initials"></span>
                                    </div>
                                    {{-- Type Indicator --}}
                                    <div x-show="result.type === 'loyalty'" 
                                        class="absolute -bottom-0.5 -right-0.5 w-5 h-5 rounded-full bg-primary-500 
                                            flex items-center justify-center border-2 border-white dark:border-secondary-900">
                                        <x-ui.icon icon="coins" class="w-2.5 h-2.5 text-white" />
                                    </div>
                                    <div x-show="result.type === 'stamp'" 
                                        class="absolute -bottom-0.5 -right-0.5 w-5 h-5 rounded-full bg-emerald-500 
                                            flex items-center justify-center border-2 border-white dark:border-secondary-900">
                                        <x-ui.icon icon="stamp" class="w-2.5 h-2.5 text-white" />
                                    </div>
                                    <div x-show="result.type === 'voucher'" 
                                        class="absolute -bottom-0.5 -right-0.5 w-5 h-5 rounded-full bg-purple-500 
                                            flex items-center justify-center border-2 border-white dark:border-secondary-900">
                                        <x-ui.icon icon="ticket" class="w-2.5 h-2.5 text-white" />
                                    </div>
                                </div>
                                
                                {{-- Member Info --}}
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-secondary-900 dark:text-white truncate" x-text="result.name"></p>
                                    <p class="text-sm text-secondary-500 dark:text-secondary-400 truncate" x-text="result.email"></p>
                                </div>
                                
                                {{-- Last Interaction Time --}}
                                <div class="flex-shrink-0 text-right">
                                    <p class="text-xs text-secondary-400 dark:text-secondary-500" x-text="result.last_interaction"></p>
                                </div>
                                
                                {{-- Arrow --}}
                                <x-ui.icon icon="chevron-right" class="w-5 h-5 text-secondary-300 dark:text-secondary-600 flex-shrink-0" />
                            </a>
                        </template>
                    </div>
                </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════════════════════
            QR CODE / CAMERA ACTION - Simplified, Large Tap Target
        ═══════════════════════════════════════════════════════════════════════ --}}
        <section class="mb-8 animate-fade-in" style="animation-delay: 160ms;">
            <a href="{{ route('staff.qr.scanner') }}"
                class="group flex items-center gap-5 p-5 md:p-6
                    bg-primary-600 hover:bg-primary-500
                    rounded-2xl shadow-sm hover:shadow-md
                    transition-all duration-300 active:scale-[0.98]">
                
                {{-- Camera Icon - Large --}}
                <div class="flex-shrink-0 w-14 h-14 md:w-16 md:h-16 rounded-2xl bg-white/20 backdrop-blur-sm
                    flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                    <x-ui.icon icon="scan" class="w-7 h-7 md:w-8 md:h-8 text-white" />
                </div>
                
                {{-- Text --}}
                <div class="flex-1 min-w-0">
                    <h3 class="text-lg md:text-xl font-bold text-white mb-0.5">
                        {{ trans('common.scan_customer_qr') }}
                    </h3>
                    <p class="text-sm text-white/80">
                        {{ trans('common.tap_to_open_scanner') }}
                    </p>
                </div>
                
                {{-- Arrow --}}
                <x-ui.icon icon="arrow-right" class="w-6 h-6 text-white/80 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform duration-300 flex-shrink-0" />
            </a>
        </section>

        {{-- ═══════════════════════════════════════════════════════════════════════
            RECENT INTERACTIONS - Large Cards, Clear Visual Hierarchy
        ═══════════════════════════════════════════════════════════════════════ --}}
        <section class="animate-fade-in" style="animation-delay: 240ms;">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-lg font-bold text-secondary-900 dark:text-white">{{ trans('common.recent_interactions') }}</h2>
                <a href="{{ route('staff.data.list', ['name' => 'members']) }}" 
                    class="text-sm text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-300 transition-colors">
                    {{ trans('common.view_all') }}
                </a>
            </div>

            @if(!empty($dashboardData['recentMembers']) && $dashboardData['recentMembers']->count() > 0)
                <div class="space-y-3">
                @foreach($dashboardData['recentMembers'] as $index => $interaction)
                    @php
                        $member = $interaction['member'];
                        $type = $interaction['type'];
                        $timeAgo = $interaction['date']->diffForHumans();
                        
                        // Color coding based on action type
                        [$iconBg, $iconColor, $icon, $actionText, $actionColorClass] = match($type) {
                            'loyalty' => [
                                'bg-primary-500',
                                'text-white',
                                'coins',
                                match($interaction['event']) {
                                    'staff_redeemed_points_for_reward' => trans('common.redeemed_reward'),
                                    'points_earned_purchase' => trans('common.earned_points'),
                                    default => trans('common.loyalty_transaction'),
                                },
                                $interaction['event'] === 'staff_redeemed_points_for_reward' 
                                    ? 'text-amber-600 dark:text-amber-400' 
                                    : 'text-emerald-600 dark:text-emerald-400'
                            ],
                            'stamp' => [
                                'bg-emerald-500',
                                'text-white',
                                'stamp',
                                match($interaction['event']) {
                                    'reward_redeemed' => trans('common.claimed_reward'),
                                    'stamp_earned' => trans('common.earned_stamp'),
                                    default => trans('common.stamp_transaction'),
                                },
                                $interaction['event'] === 'reward_redeemed' 
                                    ? 'text-amber-600 dark:text-amber-400' 
                                    : 'text-emerald-600 dark:text-emerald-400'
                            ],
                            'voucher' => [
                                'bg-purple-500',
                                'text-white',
                                'ticket',
                                trans('common.redeemed_voucher'),
                                'text-purple-600 dark:text-purple-400'
                            ],
                            default => ['bg-secondary-400', 'text-white', 'circle', trans('common.transaction'), 'text-secondary-600']
                        };
                    @endphp
                    <a href="{{ $interaction['url'] }}"
                        class="group flex items-center gap-4 p-4 md:p-5
                            bg-white dark:bg-secondary-900 
                            rounded-2xl md:rounded-3xl 
                            border border-secondary-100 dark:border-secondary-800
                            hover:border-secondary-200 dark:hover:border-secondary-700
                            shadow-sm hover:shadow-lg hover:shadow-secondary-900/[0.03] dark:hover:shadow-black/10
                            transition-all duration-300 active:scale-[0.98]"
                        style="animation-delay: {{ 240 + ($index * 60) }}ms;">
                        
                        {{-- Avatar with Type Badge --}}
                        <div class="flex-shrink-0 relative">
                            @if($member->avatar)
                                <img src="{{ $member->avatar }}" alt="{{ $member->name }}" 
                                    class="w-14 h-14 md:w-16 md:h-16 rounded-full object-cover ring-2 ring-white dark:ring-secondary-800">
                            @else
                                <div class="w-14 h-14 md:w-16 md:h-16 rounded-full 
                                    bg-gradient-to-br from-secondary-100 to-secondary-200 dark:from-secondary-700 dark:to-secondary-800 
                                    flex items-center justify-center text-secondary-600 dark:text-secondary-300 
                                    font-bold text-lg md:text-xl
                                    ring-2 ring-white dark:ring-secondary-800">
                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                </div>
                            @endif
                            {{-- Type Badge --}}
                            <div class="absolute -bottom-1 -right-1 {{ $iconBg }} {{ $iconColor }} 
                                rounded-full p-1.5 md:p-2 
                                border-2 border-white dark:border-secondary-900 
                                shadow-sm group-hover:scale-110 transition-transform duration-300">
                                <x-ui.icon :icon="$icon" class="w-3 h-3 md:w-3.5 md:h-3.5" />
                            </div>
                        </div>

                        {{-- Member Info - Clear Hierarchy --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-bold text-secondary-900 dark:text-white truncate text-base md:text-lg">
                                    {{ $member->name }}
                                </h3>
                                {{-- Timestamp - Top Right, Small --}}
                                <span class="text-[11px] text-secondary-400 dark:text-secondary-500 whitespace-nowrap flex-shrink-0 mt-0.5">
                                    {{ $timeAgo }}
                                </span>
                            </div>
                            {{-- Action - Color Coded --}}
                            <p class="text-sm font-medium {{ $actionColorClass }} mt-1">
                                {{ $actionText }}
                            </p>
                        </div>

                        {{-- Arrow - Tap Indicator --}}
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-secondary-50 dark:bg-secondary-800 
                            flex items-center justify-center
                            group-hover:bg-secondary-100 dark:group-hover:bg-secondary-700 
                            transition-colors duration-300">
                            <x-ui.icon icon="chevron-right" 
                                class="w-5 h-5 text-secondary-400 group-hover:text-secondary-600 dark:group-hover:text-secondary-300 
                                    group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 transition-all duration-300" />
                        </div>
                    </a>
                @endforeach
                </div>
            @else
                {{-- Friendly Empty State for New Staff --}}
                <div class="bg-white dark:bg-secondary-900 rounded-3xl 
                    border border-secondary-100 dark:border-secondary-800 
                    shadow-sm p-10 md:p-14 text-center">
                    <div class="max-w-sm mx-auto">
                        {{-- Friendly illustration with icons --}}
                        <div class="mb-8 flex items-center justify-center gap-3">
                            <div class="w-14 h-14 md:w-16 md:h-16 rounded-2xl bg-primary-100 dark:bg-primary-500/10 text-primary-600 dark:text-primary-400 flex items-center justify-center">
                                <x-ui.icon icon="coins" class="w-7 h-7 md:w-8 md:h-8" />
                            </div>
                            <div class="w-14 h-14 md:w-16 md:h-16 rounded-2xl bg-emerald-100 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                <x-ui.icon icon="stamp" class="w-7 h-7 md:w-8 md:h-8" />
                            </div>
                            <div class="w-14 h-14 md:w-16 md:h-16 rounded-2xl bg-purple-100 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                                <x-ui.icon icon="ticket" class="w-7 h-7 md:w-8 md:h-8" />
                            </div>
                        </div>
                        
                        {{-- Welcoming message --}}
                        <h3 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white mb-3">
                            {{ trans('common.welcome_new_staff') }}
                        </h3>
                        <p class="text-secondary-500 dark:text-secondary-400 leading-relaxed">
                            {{ trans('common.your_recent_interactions_will_appear_here') }}
                        </p>
                    </div>
                </div>
            @endif
        </section>
    </div>
</div>

<script>
function staffDashboard() {
    return {
        init() {
            // Optional: Add any initialization logic
        }
    };
}

/**
 * Member Search Component
 * iOS x Revolut Standard: Elegant loading, smooth transitions, keyboard navigation
 */
function memberSearch() {
    return {
        searchQuery: '',
        results: [],
        showResults: false,
        showNoResults: false,
        highlightedIndex: -1,
        isSearching: false,
        focused: false,

        async search() {
            if (this.searchQuery.length < 2) {
                this.results = [];
                this.showResults = false;
                this.showNoResults = false;
                return;
            }

            this.isSearching = true;
            this.showNoResults = false;

            try {
                const response = await fetch(`{{ route('staff.api.search-members') }}?q=${encodeURIComponent(this.searchQuery)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.results = data.members || [];
                    this.showResults = this.results.length > 0;
                    this.showNoResults = this.results.length === 0;
                    this.highlightedIndex = this.results.length > 0 ? 0 : -1;
                    
                    // Re-initialize Lucide icons after Alpine renders new results
                    this.$nextTick(() => {
                        document.dispatchEvent(new CustomEvent('reinit-lucide'));
                        // Scroll to highlighted item
                        this.scrollToHighlighted();
                    });
                }
            } catch (error) {
                console.error('Search error:', error);
                this.results = [];
                this.showResults = false;
                this.showNoResults = true;
            } finally {
                this.isSearching = false;
            }
        },

        clearSearch() {
            this.searchQuery = '';
            this.results = [];
            this.showResults = false;
            this.showNoResults = false;
            this.highlightedIndex = -1;
        },

        highlightNext() {
            if (this.results.length === 0) return;
            this.highlightedIndex = (this.highlightedIndex + 1) % this.results.length;
            this.scrollToHighlighted();
        },

        highlightPrev() {
            if (this.results.length === 0) return;
            this.highlightedIndex = this.highlightedIndex <= 0 
                ? this.results.length - 1 
                : this.highlightedIndex - 1;
            this.scrollToHighlighted();
        },

        selectHighlighted() {
            if (this.highlightedIndex >= 0 && this.results[this.highlightedIndex]) {
                window.location.href = this.results[this.highlightedIndex].url;
            }
        },

        scrollToHighlighted() {
            this.$nextTick(() => {
                const items = this.$el.querySelectorAll('[x-ref="resultItem"]');
                if (items[this.highlightedIndex]) {
                    items[this.highlightedIndex].scrollIntoView({ 
                        block: 'nearest', 
                        behavior: 'smooth' 
                    });
                }
            });
        }
    };
}
</script>

<style>
    /* Fade-in Animation */
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-fade-in {
        animation: fade-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        opacity: 0;
    }

    /* Shimmer Loading Animation */
    @keyframes shimmer-slide {
        0% {
            transform: translateX(-100%);
            width: 30%;
        }
        50% {
            width: 50%;
        }
        100% {
            transform: translateX(400%);
            width: 30%;
        }
    }
    
    .animate-shimmer-slide {
        animation: shimmer-slide 1.5s ease-in-out infinite;
    }


</style>
@stop
