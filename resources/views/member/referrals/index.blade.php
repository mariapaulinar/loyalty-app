@extends('member.layouts.default')

@section('page_title', trans('common.referral.refer_friend'))

@section('content')
{{--
═══════════════════════════════════════════════════════════════════════════════
MEMBER REFERRAL DASHBOARD - THE DISNEY CASTLE MOMENT ✨
═══════════════════════════════════════════════════════════════════════════════

Design Philosophy:
- Walking down Main Street USA, seeing Cinderella's Castle for the first time
- Pure magic, wonder, and delight
- Sophisticated, not childish
- Premium cards floating in space like Apple Card reveal
- Micro-interactions that spark joy
- Every pixel intentional, every animation purposeful

Inspiration:
- Apple Card reveal animation
- Stripe's payment success
- Linear's command palette
- Revolut's card management
- Disney's sense of wonder

This is what happens when Steve Jobs meets Walt Disney.
═══════════════════════════════════════════════════════════════════════════════
--}}

{{-- Ambient Magic Background --}}
<div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
    {{-- Soft gradient wash --}}
    <div class="absolute inset-0 bg-gradient-to-br from-secondary-50 via-primary-50/30 to-violet-50/20 dark:from-secondary-950 dark:via-primary-950/20 dark:to-violet-950/10"></div>
    
    {{-- Floating light orbs - like fireflies at dusk --}}
    <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-primary-400/10 dark:bg-primary-500/5 rounded-full blur-3xl animate-float-orb-1"></div>
    <div class="absolute top-1/2 right-1/3 w-80 h-80 bg-violet-400/10 dark:bg-violet-500/5 rounded-full blur-3xl animate-float-orb-2"></div>
    <div class="absolute bottom-1/4 left-1/2 w-72 h-72 bg-emerald-400/10 dark:bg-emerald-500/5 rounded-full blur-3xl animate-float-orb-3"></div>
    
    {{-- Radial light from top - like sunlight through castle windows --}}
    <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[800px] h-[800px] bg-gradient-radial from-primary-200/20 via-transparent to-transparent dark:from-primary-500/10 blur-3xl"></div>
</div>

<div class="min-h-screen relative">
    <div class="max-w-7xl mx-auto px-4 md:px-8 py-12 md:py-16 space-y-12">
        
        {{-- Hero Header - Grand Entrance --}}
        <div class="text-center space-y-4 animate-fade-in-up">
            <span class="text-xs font-semibold uppercase tracking-[0.2em] text-secondary-400 dark:text-secondary-500 block">{{ trans('common.refer_earn') }}</span>
            
            <h1 class="text-4xl md:text-6xl font-bold bg-gradient-to-r from-secondary-900 via-primary-600 to-violet-600 dark:from-white dark:via-primary-400 dark:to-violet-400 bg-clip-text text-transparent tracking-tight leading-tight">
                {{ trans('common.share_love') }}
            </h1>
            
            <p class="text-lg md:text-xl text-secondary-600 dark:text-secondary-400 max-w-2xl mx-auto leading-relaxed">
                {{ trans('common.share_code_desc') }}
            </p>
        </div>

        {{-- Programs Grid - The Magic Happens Here --}}
        <div class="space-y-12">
            @forelse($programs as $index => $program)
                <div class="animate-slide-in-up" style="animation-delay: {{ $index * 100 }}ms;">
                    {{-- Premium Container - Floating Card Effect --}}
                <div class="group relative bg-white dark:bg-secondary-900 rounded-3xl shadow-xl shadow-secondary-900/5 dark:shadow-black/20 border border-secondary-100 dark:border-secondary-800 overflow-hidden transition-all duration-500 hover:shadow-2xl hover:shadow-secondary-900/10 dark:hover:shadow-black/40 hover:-translate-y-1 mb-12">
                    
                    {{-- Subtle gradient overlay on hover --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-primary-500/0 to-violet-500/0 group-hover:from-primary-500/5 group-hover:to-violet-500/5 transition-all duration-700 pointer-events-none"></div>
                    
                    <div class="relative grid grid-cols-1 lg:grid-cols-12 gap-0">
                        
                        {{-- LEFT: The Loyalty Cards (Premium Card Display) --}}
                        <div class="lg:col-span-7 p-8 lg:p-12 space-y-8">
                            
                            {{-- Section Header --}}
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                                    <x-ui.icon icon="credit-card" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                                </div>
                                <h2 class="text-xl font-bold text-secondary-900 dark:text-white">{{ trans('common.your_rewards') }}</h2>
                            </div>

                            {{-- Cards Grid - Show the actual loyalty cards! --}}
                            @php
                                // Get the cards associated with this club's referral program
                                $referrerCard = $program['settings']->referrerCard ?? null;
                                $refereeCard = $program['settings']->refereeCard ?? null;
                                
                                // Get unique cards (in case they're the same)
                                $cards = collect([$referrerCard, $refereeCard])->filter()->unique('id');
                            @endphp

                            @if($cards->isNotEmpty())
                                <div class="grid grid-cols-1 @if($cards->count() > 1) md:grid-cols-2 @endif gap-6">
                                    @foreach($cards as $card)
                                        <div class="transform transition-all duration-500 hover:scale-105">
                                            <x-member.premium-card 
                                                :card="$card" 
                                                :flippable="false" 
                                                :links="true" 
                                                :show-qr="false" 
                                                :show-balance="true" 
                                            />
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                {{-- Fallback if no cards configured --}}
                                <div class="p-8 rounded-2xl bg-gradient-to-br from-primary-50 to-violet-50 dark:from-primary-950/20 dark:to-violet-950/10 border border-primary-200 dark:border-primary-800/50 text-center">
                                    <x-ui.icon icon="gift" class="w-12 h-12 text-primary-600 dark:text-primary-400 mx-auto mb-3" />
                                    <p class="text-sm text-secondary-600 dark:text-secondary-400">{{ trans('common.referral_rewards_configured') }}</p>
                                </div>
                            @endif

                            {{-- Reward Breakdown - Crystal Clear --}}
                            <div class="grid grid-cols-2 gap-4">
                                {{-- You Get --}}
                                <div class="group/reward relative overflow-hidden p-6 rounded-2xl bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-950/30 dark:to-teal-950/20 border border-emerald-200 dark:border-emerald-800/50 transition-all duration-300 hover:scale-105">
                                    <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-400/10 dark:bg-emerald-400/5 rounded-full blur-2xl"></div>
                                    <div class="relative">
                                        <div class="text-xs uppercase tracking-widest text-emerald-700 dark:text-emerald-400 font-bold mb-2">{{ trans('common.referral.you_get') }}</div>
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-4xl font-black text-emerald-900 dark:text-emerald-100 tabular-nums">{{ $program['settings']->referrer_points ?? 0 }}</span>
                                            <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">{{ trans('common.referral.points') }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- They Get --}}
                                <div class="group/reward relative overflow-hidden p-6 rounded-2xl bg-gradient-to-br from-violet-50 to-purple-50 dark:from-violet-950/30 dark:to-purple-950/20 border border-violet-200 dark:border-violet-800/50 transition-all duration-300 hover:scale-105">
                                    <div class="absolute top-0 right-0 w-20 h-20 bg-violet-400/10 dark:bg-violet-400/5 rounded-full blur-2xl"></div>
                                    <div class="relative">
                                        <div class="text-xs uppercase tracking-widest text-violet-700 dark:text-violet-400 font-bold mb-2">{{ trans('common.referral.they_get') }}</div>
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-4xl font-black text-violet-900 dark:text-violet-100 tabular-nums">{{ $program['settings']->referee_points ?? 0 }}</span>
                                            <span class="text-sm font-semibold text-violet-700 dark:text-violet-400">{{ trans('common.referral.points') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- RIGHT: Share & Stats --}}
                        <div class="lg:col-span-5 p-8 lg:p-12 bg-secondary-50 dark:bg-secondary-950/50 border-t lg:border-t-0 lg:border-l border-secondary-100 dark:border-secondary-800 space-y-8">
                            
                            {{-- Share Section - The Main Event --}}
                            <div class="space-y-4" x-data="{
                                copied: false,
                                async copyLink() {
                                    const input = document.getElementById('ref-link-{{ $program['campaign']->id }}');
                                    try {
                                        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                                            await navigator.clipboard.writeText(input.value);
                                        } else {
                                            input.select();
                                            document.execCommand('copy');
                                        }
                                        this.copied = true;
                                        setTimeout(() => this.copied = false, 2500);
                                    } catch (err) {
                                        console.error('Copy failed:', err);
                                    }
                                }
                            }">
                                <h3 class="text-sm font-bold uppercase tracking-wider text-secondary-500 dark:text-secondary-400">{{ trans('common.referral.share_link') }}</h3>
                                
                                {{-- Link Display - Premium Input --}}
                                <div class="relative group/link">
                                    <input 
                                        type="text" 
                                        readonly 
                                        value="{{ $program['share_url'] }}" 
                                        id="ref-link-{{ $program['campaign']->id }}"
                                        @click="copyLink()"
                                        class="w-full px-4 py-4 bg-white dark:bg-secondary-900 border-2 border-secondary-200 dark:border-secondary-700 rounded-xl text-sm font-mono text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200 pr-12 cursor-pointer"
                                    >
                                    {{-- Copy indicator --}}
                                    <div class="absolute right-3 top-1/2 -translate-y-1/2 opacity-100 transition-opacity pointer-events-none">
                                        <x-ui.icon icon="copy" class="w-5 h-5 text-secondary-400" />
                                    </div>
                                </div>

                                {{-- Copy Button - Delightful Interaction --}}
                                <button 
                                    @click="copyLink()"
                                    :class="copied && 'scale-95'"
                                    class="w-full group/btn relative overflow-hidden px-6 py-4 bg-gradient-to-r from-primary-600 to-violet-600 hover:from-primary-500 hover:to-violet-500 text-white font-bold rounded-xl shadow-lg shadow-primary-500/30 hover:shadow-xl hover:shadow-primary-500/40 transition-all duration-300 active:scale-95"
                                >
                                    {{-- Shimmer effect --}}
                                    <div class="absolute inset-0 -translate-x-full group-hover/btn:translate-x-full transition-transform duration-1000 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                                    
                                    <span class="relative flex items-center justify-center gap-2">
                                        <x-ui.icon x-show="!copied" icon="link" class="w-5 h-5" />
                                        <x-ui.icon x-cloak x-show="copied" icon="check" class="w-5 h-5" />
                                        <span x-text="copied ? '{{ trans('common.referral.copied') }}!' : '{{ trans('common.referral.copy_link') }}'"></span>
                                    </span>
                                </button>
                            </div>

                            {{-- Stats - Clean & Minimal --}}
                            <div class="space-y-4">
                                <h3 class="text-sm font-bold uppercase tracking-wider text-secondary-500 dark:text-secondary-400">{{ trans('common.referral.your_stats') }}</h3>
                                
                                <div class="grid grid-cols-3 gap-3">
                                    {{-- Total Referrals --}}
                                    <div class="p-4 rounded-xl bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-800">
                                        <div class="text-2xl font-black text-secondary-900 dark:text-white tabular-nums">{{ $program['stats']['referral_count'] ?? 0 }}</div>
                                        <div class="text-[10px] uppercase tracking-wider text-secondary-500 dark:text-secondary-400 mt-1">{{ trans('common.referral.total') }}</div>
                                    </div>

                                    {{-- Successful --}}
                                    <div class="p-4 rounded-xl bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-800">
                                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums">{{ $program['stats']['successful_count'] ?? 0 }}</div>
                                        <div class="text-[10px] uppercase tracking-wider text-secondary-500 dark:text-secondary-400 mt-1">{{ trans('common.referral.active') }}</div>
                                    </div>

                                    {{-- Points Earned --}}
                                    <div class="p-4 rounded-xl bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-800">
                                        <div class="text-2xl font-black text-amber-600 dark:text-amber-400 tabular-nums">{{ $program['stats']['points_earned'] ?? 0 }}</div>
                                        <div class="text-[10px] uppercase tracking-wider text-secondary-500 dark:text-secondary-400 mt-1">{{ trans('common.referral.earned') }}</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Recent Activity - Elegant List --}}
                            @if($program['referrals']->isNotEmpty())
                                <div class="space-y-3">
                                    <h3 class="text-sm font-bold uppercase tracking-wider text-secondary-500 dark:text-secondary-400">{{ trans('common.referral.recent_activity') }}</h3>
                                    
                                    <div class="space-y-2 max-h-64 overflow-y-auto custom-scrollbar">
                                        @foreach($program['referrals']->take(5) as $referral)
                                            <div class="flex items-center justify-between p-3 rounded-xl bg-white dark:bg-secondary-900 border border-secondary-100 dark:border-secondary-800 transition-all duration-200 hover:border-primary-300 dark:hover:border-primary-700">
                                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                                    {{-- Avatar --}}
                                                    @if($referral->referee->avatar)
                                                        <img src="{{ $referral->referee->avatar }}" alt="{{ $referral->referee->name }}" class="w-9 h-9 rounded-full object-cover flex-shrink-0 shadow-md ring-2 ring-white dark:ring-secondary-800">
                                                    @else
                                                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-primary-500 to-violet-500 flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-md">
                                                            {{ substr($referral->referee->name ?? '?', 0, 1) }}
                                                        </div>
                                                    @endif
                                                    {{-- Info --}}
                                                    <div class="min-w-0 flex-1">
                                                        <div class="text-sm font-semibold text-secondary-900 dark:text-white truncate">{{ $referral->referee->name }}</div>
                                                        <div class="text-xs text-secondary-500 dark:text-secondary-400">{{ $referral->created_at->diffForHumans() }}</div>
                                                    </div>
                                                </div>
                                                {{-- Status Badge --}}
                                                @if($referral->status === 'completed')
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                                        <x-ui.icon icon="check" class="w-3 h-3 mr-1" />
                                                        {{ trans('common.referral.completed') }}
                                                    </span>
                                                @elseif($referral->status === 'pending')
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                                        <x-ui.icon icon="clock" class="w-3 h-3 mr-1" />
                                                        {{ trans('common.referral.status_pending') }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="p-8 rounded-2xl bg-secondary-100 dark:bg-secondary-900 border-2 border-dashed border-secondary-300 dark:border-secondary-700 text-center">
                                    <x-ui.icon icon="users" class="w-12 h-12 text-secondary-400 dark:text-secondary-600 mx-auto mb-3" />
                                    <p class="text-sm font-medium text-secondary-600 dark:text-secondary-400">{{ trans('common.referral.no_referrals_yet') }}</p>
                                    <p class="text-xs text-secondary-500 dark:text-secondary-500 mt-1">{{ trans('common.referral.start_sharing') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
        @empty
            {{-- ═══════════════════════════════════════════════════════════════════
                 EMPTY STATE - A Masterpiece of UI/UX Design
                 ═══════════════════════════════════════════════════════════════════ --}}
            <div class="relative py-24 px-4 overflow-hidden">
                <div class="absolute inset-0 -z-10">
                    <div class="absolute inset-0 bg-gradient-radial from-primary-100/40 via-transparent to-transparent dark:from-primary-900/20"></div>
                    <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-primary-300/20 dark:bg-primary-600/10 rounded-full blur-3xl animate-float-slow"></div>
                    <div class="absolute bottom-1/4 right-1/4 w-48 h-48 bg-violet-300/20 dark:bg-violet-600/10 rounded-full blur-3xl animate-float-slower"></div>
                </div>

                <div class="max-w-2xl mx-auto text-center space-y-8">
                    <div class="relative inline-block">
                        {{-- Clean icon container - no animations --}}
                        <div class="w-32 h-32 mx-auto rounded-3xl bg-gradient-to-br from-white to-secondary-50 dark:from-secondary-800 dark:to-secondary-900 shadow-xl shadow-primary-500/10 dark:shadow-primary-500/5 flex items-center justify-center">
                            <x-ui.icon icon="user-plus" class="w-16 h-16 stroke-primary-600 dark:stroke-primary-400" />
                        </div>
                    </div>


                    <div class="space-y-3">
                        <h2 class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-secondary-900 via-primary-800 to-violet-800 dark:from-white dark:via-primary-200 dark:to-violet-200 bg-clip-text text-transparent leading-tight">
                            {{ trans("common.no_programs_available") }}
                        </h2>
                        <p class="text-lg md:text-xl text-secondary-600 dark:text-secondary-400 max-w-lg mx-auto leading-relaxed">
                            {{ trans("common.join_program_desc") }}
                        </p>
                    </div>

                    <div class="pt-4">
                        <a href="{{ route("member.cards") }}" class="group inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 dark:from-primary-500 dark:to-primary-600 dark:hover:from-primary-600 dark:hover:to-primary-700 text-white rounded-2xl shadow-lg shadow-primary-500/30 hover:shadow-xl hover:shadow-primary-500/40 transform hover:scale-105 transition-all duration-300">
                            <x-ui.icon icon="layers" class="w-5 h-5 group-hover:rotate-12 transition-transform duration-300" />
                            <span class="font-semibold text-lg">{{ trans("common.explore_programs") }}</span>
                            <x-ui.icon icon="arrow-right" class="w-5 h-5 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform duration-300" />
                        </a>
                    </div>

                </div>
            </div>

            </div>
        @endforelse
        </div>

        {{-- Programs Where You're Earning Points (As Referee) --}}
        @if(isset($refereePrograms) && $refereePrograms->isNotEmpty())
            <div class="mt-16 animate-fade-in-up" style="animation-delay: {{ count($programs) * 100 + 200 }}ms;">
                {{-- Section Header --}}
                <div class="text-center space-y-3 mb-10">
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-secondary-400 dark:text-secondary-500 block">{{ trans('common.referral.your_invitations') }}</span>
                    
                    <h2 class="text-3xl font-bold text-secondary-900 dark:text-white">
                        {{ trans('common.referral.earn_your_rewards') }}
                    </h2>
                    
                    <p class="text-secondary-600 dark:text-secondary-400 max-w-2xl mx-auto">
                        {{ trans('common.referral.complete_purchase_desc') }}
                    </p>
                </div>

                {{-- Referee Cards Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($refereePrograms as $index => $program)
                        <div class="animate-slide-in-up" style="animation-delay: {{ $index * 100 }}ms;">
                            <div class="group relative bg-white dark:bg-secondary-900 rounded-2xl shadow-lg shadow-secondary-900/5 dark:shadow-black/20 border border-secondary-100 dark:border-secondary-800 overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                                
                                {{-- Status Badge --}}
                                <div class="absolute top-4 right-4 z-10">
                                    @if($program['status'] === 'pending')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-100 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 text-xs font-semibold text-amber-700 dark:text-amber-300">
                                            <x-ui.icon icon="clock" class="w-3.5 h-3.5" />
                                            {{ trans('common.referral.status_pending') }}
                                        </span>
                                    @elseif($program['status'] === 'completed')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                            <x-ui.icon icon="check-circle" class="w-3.5 h-3.5" />
                                            {{ trans('common.referral.status_completed') }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Card Display --}}
                                <div class="p-6">
                                    {{-- Make card clickable --}}
                                    <a href="{{ route('member.card', ['locale' => app()->getLocale(), 'card_id' => $program['card']->id]) }}" class="block transform transition-all duration-500 hover:scale-105">
                                        <x-member.premium-card 
                                            :card="$program['card']" 
                                            :show-follow="false"
                                        />
                                    </a>

                                    {{-- Points Info (No Campaign Name) --}}
                                    <div class="mt-6 text-center space-y-2">
                                        @if($program['status'] === 'pending')
                                            <p class="text-sm text-secondary-600 dark:text-secondary-400">
                                                {{ trans('common.referral.make_purchase_to_unlock', ['points' => $program['campaign']->referee_points]) }}
                                            </p>
                                        @else
                                            <p class="text-sm text-emerald-600 dark:text-emerald-400 font-medium">
                                                {{ trans('common.referral.earned_points', ['points' => $program['campaign']->referee_points]) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Bottom Spacing --}}
<div class="h-16"></div>

{{-- Custom Animations & Styles --}}
@push('styles')
<style>
/* Floating orbs - gentle, mesmerizing movement */
@keyframes float-orb-1 {
    0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.3; }
    33% { transform: translate(30px, -40px) scale(1.1); opacity: 0.4; }
    66% { transform: translate(-20px, -60px) scale(0.9); opacity: 0.5; }
}

@keyframes float-orb-2 {
    0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.25; }
    33% { transform: translate(-40px, 30px) scale(1.15); opacity: 0.35; }
    66% { transform: translate(25px, -45px) scale(0.85); opacity: 0.45; }
}

@keyframes float-orb-3 {
    0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.2; }
    33% { transform: translate(35px, 50px) scale(1.05); opacity: 0.3; }
    66% { transform: translate(-30px, 20px) scale(0.95); opacity: 0.4; }
}

.animate-float-orb-1 { animation: float-orb-1 20s ease-in-out infinite; }
.animate-float-orb-2 { animation: float-orb-2 25s ease-in-out infinite; animation-delay: 3s; }
.animate-float-orb-3 { animation: float-orb-3 22s ease-in-out infinite; animation-delay: 6s; }

/* Radial gradient */
.bg-gradient-radial {
    background: radial-gradient(circle, var(--tw-gradient-stops));
}

/* Custom scrollbar for activity list */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgb(203 213 225 / 0.5);
    border-radius: 3px;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgb(71 85 105 / 0.5);
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgb(148 163 184 / 0.7);
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgb(100 116 139 / 0.7);
}
</style>
@endpush

@stop

