@extends('member.layouts.default')

@section('page_title', auth('member')->user()->name . config('default.page_title_delimiter') . trans('common.dashboard') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
{{--
Member Dashboard - Insanely Great Edition

Designed with Steve Jobs & Jony Ive level attention to detail.
Every pixel matters. Every interaction delights.
--}}

<div class="relative">

<div class="max-w-7xl mx-auto px-4 md:px-8 py-8 md:py-12 space-y-8">
    {{-- Welcome Header - Larger & Bolder Greeting --}}
    <div class="animate-fade-in-up">
        <h1 class="text-3xl md:text-5xl font-extrabold text-secondary-900 dark:text-white tracking-tight mb-3">
            <span x-data="{ greeting: '{{ $greeting }}' }" x-init="
                const h = new Date().getHours();
                greeting = h >= 5 && h < 12 ? '{{ trans('common.good_morning') }}'
                    : h < 17 ? '{{ trans('common.good_afternoon') }}'
                    : h < 21 ? '{{ trans('common.good_evening') }}'
                    : '{{ trans('common.good_night') }}';
            " x-text="greeting">{{ $greeting }}</span>
            @if(auth('member')->user()?->email), <span class="text-primary-600 dark:text-primary-400">{{ auth('member')->user()->name }}</span>@endif
            <span class="inline-block origin-[70%_70%] animate-wave text-3xl md:text-5xl">👋</span>
        </h1>
        <p class="text-base md:text-lg text-secondary-500 dark:text-secondary-400 max-w-2xl">
            {!! trans('common.memberDashboardBlocksTitle') !!}
        </p>
    </div>

    {{-- Tier 1 Metrics - Pure White Cards, Subtle Shadow, No Gradients --}}
    @php
        $member = auth('member')->user();
        
        // ═══════════════════════════════════════════════════════════════════════
        // METRIC CALCULATIONS (Mandate 1 & 2: Accurate Data)
        // ═══════════════════════════════════════════════════════════════════════
        
        // Loyalty Cards
        $loyaltyCount = $cards->count();
        $totalPoints = $cards->sum(fn($card) => $card->getMemberBalance($member));
        
        // Stamp Cards - Sum current_stamps from enrollments (NOT enrollment count)
        $hasStampCards = isset($stampCards) && $stampCards->isNotEmpty();
        $stampProgramCount = $hasStampCards ? $stampCards->count() : 0;
        $totalStampsCollected = $hasStampCards 
            ? $member->stampCardEnrollments->sum('current_stamps') 
            : 0;
        
        // Vouchers - Count redeemed vouchers (usage rate)
        $hasVouchers = isset($vouchers) && $vouchers->isNotEmpty();
        $voucherCount = $hasVouchers ? $vouchers->count() : 0;
        $vouchersRedeemed = $member->voucherRedemptions()
            ->where('status', '!=', 'voided')
            ->count();
        
        // Active Programs (consolidated)
        $totalPrograms = $loyaltyCount + $stampProgramCount + $voucherCount;
        
        // ═══════════════════════════════════════════════════════════════════════
        // VISIBILITY CONDITIONS (Mandate 3: Conditional Display)
        // ═══════════════════════════════════════════════════════════════════════
        $showLoyaltyMetrics = $loyaltyCount > 0;
        $showStampMetrics = $hasStampCards;
        $showVoucherMetrics = $hasVouchers;
        $showPoints = $totalPoints > 0 || $showLoyaltyMetrics;
        $showPrograms = $totalPrograms > 0;
        
        // Count visible metrics for grid layout
        $visibleMetricsCount = collect([
            'points' => $showPoints,
            'programs' => $showPrograms,
            'stamps' => $showStampMetrics,
            'vouchers' => $showVoucherMetrics,
        ])->filter()->count();
        
        // ═══════════════════════════════════════════════════════════════════════
        // DYNAMIC GRID (Mandate 4: Responsive Layout)
        // ═══════════════════════════════════════════════════════════════════════
        // Rules: Never use 3-column grid on mobile
        // - 4 items: 2 cols mobile → 4 cols desktop
        // - 3 items: 1 col mobile → 3 cols desktop  
        // - 2 items: 1 col mobile → 2 cols desktop
        // - 1 item:  1 col everywhere
        $gridClass = match($visibleMetricsCount) {
            4 => 'grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4',
            3 => 'grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4',
            2 => 'grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4',
            default => 'grid grid-cols-1 gap-3 md:gap-4',
        };
    @endphp
    
    @if($visibleMetricsCount > 0)
        <div class="animate-fade-in-up delay-100">
            <div class="{{ $gridClass }}">
                
                {{-- Total Points (Show if has loyalty cards or points) --}}
                @if($showPoints)
                <div class="group bg-white dark:bg-secondary-900 rounded-2xl md:rounded-3xl p-4 md:p-5 
                    border border-secondary-100 dark:border-secondary-800
                    shadow-sm hover:shadow-md hover:border-secondary-200 dark:hover:border-secondary-700
                    transition-all duration-300">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 md:w-11 md:h-11 rounded-xl bg-amber-500/10 flex items-center justify-center flex-shrink-0">
                            <x-ui.icon icon="coins" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-2xl md:text-3xl font-bold text-amber-600 dark:text-amber-400 tracking-tight tabular-nums format-number">
                                {{ $totalPoints }}
                            </p>
                            <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5 font-medium">{{ trans('common.total_points') }}</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Active Programs (Show if enrolled in any programs) --}}
                @if($showPrograms)
                <div class="group bg-white dark:bg-secondary-900 rounded-2xl md:rounded-3xl p-4 md:p-5 
                    border border-secondary-100 dark:border-secondary-800
                    shadow-sm hover:shadow-md hover:border-secondary-200 dark:hover:border-secondary-700
                    transition-all duration-300">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 md:w-11 md:h-11 rounded-xl bg-emerald-500/10 flex items-center justify-center flex-shrink-0">
                            <x-ui.icon icon="layers" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-2xl md:text-3xl font-bold text-emerald-600 dark:text-emerald-400 tracking-tight tabular-nums">{{ $totalPrograms }}</p>
                            <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5 font-medium">{{ trans('common.active_programs') }}</p>
                            {{-- Breakdown --}}
                            <p class="text-[10px] text-secondary-400 dark:text-secondary-500 mt-1">
                                @if($loyaltyCount > 0){{ $loyaltyCount }} {{ trans('common.loyalty') }}@endif
                                @if($stampProgramCount > 0)@if($loyaltyCount > 0) · @endif{{ $stampProgramCount }} {{ trans('common.stamp') }}@endif
                                @if($voucherCount > 0)@if($loyaltyCount > 0 || $stampProgramCount > 0) · @endif{{ $voucherCount }} {{ trans('common.voucher') }}@endif
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Vouchers Used (Only show if member has vouchers) --}}
                @if($showVoucherMetrics)
                <div class="group bg-white dark:bg-secondary-900 rounded-2xl md:rounded-3xl p-4 md:p-5 
                    border border-secondary-100 dark:border-secondary-800
                    shadow-sm hover:shadow-md hover:border-secondary-200 dark:hover:border-secondary-700
                    transition-all duration-300">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 md:w-11 md:h-11 rounded-xl bg-purple-500/10 flex items-center justify-center flex-shrink-0">
                            <x-ui.icon icon="ticket" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400 tracking-tight tabular-nums format-number">
                                {{ $vouchersRedeemed }}
                            </p>
                            <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5 font-medium">{{ trans('common.vouchers_used') }}</p>
                        </div>
                    </div>
                </div>
                @endif
                
                {{-- Stamps Collected (Only show if member has stamp cards) --}}
                @if($showStampMetrics)
                <div class="group bg-white dark:bg-secondary-900 rounded-2xl md:rounded-3xl p-4 md:p-5 
                    border border-secondary-100 dark:border-secondary-800
                    shadow-sm hover:shadow-md hover:border-secondary-200 dark:hover:border-secondary-700
                    transition-all duration-300">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 md:w-11 md:h-11 rounded-xl bg-green-500/10 flex items-center justify-center flex-shrink-0">
                            <x-ui.icon icon="stamp" class="w-5 h-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-2xl md:text-3xl font-bold text-green-600 dark:text-green-400 tracking-tight tabular-nums format-number">
                                {{ $totalStampsCollected }}
                            </p>
                            <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5 font-medium">{{ trans('common.stamps_collected') }}</p>
                        </div>
                    </div>
                </div>
                @endif
                
            </div>
        </div>
    @endif

    {{-- Your Stamp Cards Section --}}
    @if(isset($stampCards) && $stampCards->isNotEmpty())
        <div class="animate-fade-in-up delay-300">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-secondary-900 dark:text-white flex items-center gap-2">
                    <x-ui.icon icon="badge-check" class="w-5 h-5 text-green-600 dark:text-green-400" />
                    {{ trans('common.your_stamp_cards') }}
                </h2>
            </div>

            {{-- Stamp Cards Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($stampCards->take(100) as $index => $stampCard)
                    <div class="animate-slide-in-up" style="animation-delay: {{ $index * 80 }}ms;">
                        <x-member.stamp-card 
                            :stamp-card="$stampCard"
                            :member="auth('member')->user()" />
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Your Loyalty Cards Section --}}
    @if($cards->isNotEmpty())
        <div class="animate-fade-in-up delay-200">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-secondary-900 dark:text-white flex items-center gap-2">
                    <x-ui.icon icon="credit-card" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    {{ trans('common.your_loyalty_cards') }}
                </h2>
            </div>

            {{-- Loyalty Cards Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($cards->take(100) as $index => $card)
                    <div class="animate-slide-in-up" style="animation-delay: {{ $index * 80 }}ms;">
                        <x-member.premium-card :card="$card" :flippable="false" :links="true" :show-qr="true" :show-balance="true" />
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Tier Status Section (Below Loyalty Cards) --}}
    @if(auth('member')->check() && isset($memberTiers) && $memberTiers->isNotEmpty())
        <div class="animate-fade-in-up delay-225">
            @if($memberTiers->count() === 1)
                {{-- Single tier --}}
                @php $tierData = $memberTiers->first(); @endphp
                <x-member.tier-status 
                    :memberTier="$tierData['memberTier']" 
                    :club="$tierData['club']"
                    :card="$tierData['card']"
                    :progress="$tierData['progress']" />
            @else
                {{-- Multiple tiers --}}
                <div class="mb-4">
                    <h2 class="text-xl font-bold text-secondary-900 dark:text-white flex items-center gap-2">
                        <x-ui.icon icon="award" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                        {{ trans('common.membership_tiers') }}
                    </h2>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    @foreach($memberTiers as $tierData)
                        <x-member.tier-status 
                            :memberTier="$tierData['memberTier']" 
                            :club="$tierData['club']"
                            :card="$tierData['card']"
                            :progress="$tierData['progress']" />
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Your Vouchers Section --}}
    @if(isset($vouchers) && $vouchers->isNotEmpty())
        <div class="animate-fade-in-up delay-375">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-secondary-900 dark:text-white flex items-center gap-2">
                    <x-ui.icon icon="ticket" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                    {{ trans('common.your_vouchers') }}
                </h2>
            </div>

            {{-- Vouchers Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($vouchers->take(100) as $index => $voucher)
                    <div class="animate-slide-in-up" style="animation-delay: {{ $index * 80 }}ms;">
                        <x-member.voucher-card 
                            :voucher="$voucher"
                            :member="auth('member')->user()" />
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Quick Actions - Mobile Optimized Tap Targets --}}
    <!--<div class="animate-fade-in-up delay-350">
        <h2 class="text-lg font-bold text-secondary-900 dark:text-white mb-4 flex items-center gap-2">
            <x-ui.icon icon="zap" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
            {{ trans('common.quick_actions') }}
        </h2>
        @php
            $colors = ['primary', 'emerald', 'amber', 'violet', 'pink'];
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach($dashboardBlocks as $index => $block)
                @php
                    $color = $colors[$index % count($colors)];
                    $colorConfig = match($color) {
                        'primary' => ['bg' => 'bg-primary-100 dark:bg-primary-900/30', 'text' => 'text-primary-600 dark:text-primary-400'],
                        'emerald' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/30', 'text' => 'text-emerald-600 dark:text-emerald-400'],
                        'amber' => ['bg' => 'bg-amber-100 dark:bg-amber-900/30', 'text' => 'text-amber-600 dark:text-amber-400'],
                        'violet' => ['bg' => 'bg-violet-100 dark:bg-violet-900/30', 'text' => 'text-violet-600 dark:text-violet-400'],
                        'pink' => ['bg' => 'bg-pink-100 dark:bg-pink-900/30', 'text' => 'text-pink-600 dark:text-pink-400'],
                        default => ['bg' => 'bg-primary-100 dark:bg-primary-900/30', 'text' => 'text-primary-600 dark:text-primary-400'],
                    };
                @endphp
                <a href="{{ $block['link'] }}"
                    class="group flex items-center gap-4 min-h-[72px] p-4 bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-100 dark:border-secondary-800 shadow-sm hover:shadow-md hover:border-secondary-200 dark:hover:border-secondary-700 transition-all duration-200 active:scale-[0.98]">
                    
                    {{-- Icon - 44x44 minimum tap target --}}
                    <div class="w-11 h-11 rounded-xl {{ $colorConfig['bg'] }} flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform duration-200">
                        <x-ui.icon :icon="$block['icon']" class="w-5 h-5 {{ $colorConfig['text'] }}" />
                    </div>

                    {{-- Text Content --}}
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-secondary-900 dark:text-white text-sm leading-tight mb-0.5 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                            {!! $block['title'] !!}
                        </h3>
                        <p class="text-xs text-secondary-400 dark:text-secondary-500 leading-snug line-clamp-1">
                            {!! $block['desc'] !!}
                        </p>
                    </div>

                    {{-- Chevron indicator --}}
                    <x-ui.icon icon="chevron-right" class="w-4 h-4 text-secondary-300 dark:text-secondary-600 flex-shrink-0 group-hover:text-primary-500 group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 transition-all duration-200" />
                </a>
            @endforeach
        </div>
    </div> -->
</div>
</div>
@stop

{{-- 🎉 Confetti Celebration for Claimed Vouchers --}}
@if(session('voucher_claimed'))
@push('scripts')
<script type="module">
import confetti from 'canvas-confetti';

// Elegant Celebration - Slower & More Visible
function celebrate() {
    const duration = 5000; // 5 seconds (was 3 seconds)
    const animationEnd = Date.now() + duration;
    const defaults = { 
        startVelocity: 25, // Slower initial velocity (was 30)
        spread: 360, 
        ticks: 80, // Longer lifetime (was 60)
        zIndex: 10000,
        scalar: 1.2, // Slightly larger particles
    };

    function randomInRange(min, max) {
        return Math.random() * (max - min) + min;
    }

    // Burst 1: Initial explosion from center
    setTimeout(() => {
        confetti({
            ...defaults,
            particleCount: 120, // More particles (was 100)
            origin: { x: 0.5, y: 0.5 },
            colors: ['#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'],
        });
    }, 200); // Small delay for page to settle

    // Burst 2: Side bursts (left and right)
    setTimeout(() => {
        confetti({
            ...defaults,
            particleCount: 60,
            angle: 60,
            spread: 55,
            origin: { x: 0, y: 0.6 },
            colors: ['#3b82f6', '#10b981'],
        });
        confetti({
            ...defaults,
            particleCount: 60,
            angle: 120,
            spread: 55,
            origin: { x: 1, y: 0.6 },
            colors: ['#f59e0b', '#ec4899'],
        });
    }, 500);

    // Continuous sparkles raining down (slower intervals)
    const interval = setInterval(function() {
        const timeLeft = animationEnd - Date.now();

        if (timeLeft <= 0) {
            return clearInterval(interval);
        }

        const particleCount = 25 * (timeLeft / duration); // Fewer particles per burst
        
        confetti({
            ...defaults,
            particleCount,
            origin: { x: randomInRange(0.1, 0.9), y: Math.random() - 0.2 },
            colors: ['#3b82f6', '#10b981', '#f59e0b'],
        });
    }, 400); // Slower intervals (was 250ms)
}

// Trigger celebration when page loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', celebrate);
} else {
    celebrate();
}
</script>
@endpush
@endif