@extends('member.layouts.default')

@section('page_title', trans('common.referral.landing_title', ['card' => $refereeCard->head]))

@section('content')
{{--
═══════════════════════════════════════════════════════════════════════════════
REFERRAL LANDING PAGE - THE CINDERELLA CASTLE REVEAL ✨🏰
═══════════════════════════════════════════════════════════════════════════════

This is THE moment. The first glimpse of magic.

Design Philosophy:
- That feeling when you first see Cinderella's Castle at the end of Main Street
- Pure wonder, excitement, warmth, and happiness
- Sophisticated celebration, not childish gimmicks
- Progressive reveal that builds anticipation
- Every element tells the story: "You're invited to something special"

Emotional Journey:
1. Curiosity → "What's this?"
2. Recognition → "Oh, [Friend] invited me!"
3. Understanding → "I get rewards for joining?"
4. Excitement → "This is amazing!"
5. Action → "I want this!"

This is Pixar-level storytelling through UI.
═══════════════════════════════════════════════════════════════════════════════
--}}

{{-- Magical Atmosphere - The Castle Approach --}}
<div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
    {{-- Sky gradient - dawn breaking over the castle --}}
    <div class="absolute inset-0 bg-gradient-to-b from-violet-100 via-primary-50 to-amber-50 dark:from-violet-950 dark:via-primary-950 dark:to-amber-950/50"></div>
    
    {{-- Floating sparkles - magic in the air --}}
    <div class="absolute top-[10%] left-[15%] w-2 h-2 bg-amber-400/60 rounded-full animate-sparkle-float" style="animation-delay: 0s;"></div>
    <div class="absolute top-[25%] right-[20%] w-1.5 h-1.5 bg-primary-400/50 rounded-full animate-sparkle-float" style="animation-delay: 2s;"></div>
    <div class="absolute top-[40%] left-[25%] w-1 h-1 bg-violet-400/60 rounded-full animate-sparkle-float" style="animation-delay: 4s;"></div>
    <div class="absolute top-[60%] right-[30%] w-2 h-2 bg-emerald-400/50 rounded-full animate-sparkle-float" style="animation-delay: 1s;"></div>
    <div class="absolute top-[75%] left-[35%] w-1.5 h-1.5 bg-amber-400/60 rounded-full animate-sparkle-float" style="animation-delay: 3s;"></div>
    <div class="absolute top-[15%] right-[40%] w-1 h-1 bg-primary-400/50 rounded-full animate-sparkle-float" style="animation-delay: 5s;"></div>
    
    {{-- Radiant light rays from top - sunlight streaming through --}}
    <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[1000px] h-[1000px] bg-gradient-radial from-amber-200/30 via-primary-200/20 to-transparent dark:from-amber-500/15 dark:via-primary-500/10 blur-3xl animate-pulse-glow-slow"></div>
    
    {{-- Soft side glows - magical aura --}}
    <div class="absolute top-1/4 -left-40 w-96 h-96 bg-gradient-radial from-violet-300/20 via-transparent to-transparent dark:from-violet-500/10 blur-3xl"></div>
    <div class="absolute top-1/2 -right-40 w-96 h-96 bg-gradient-radial from-primary-300/20 via-transparent to-transparent dark:from-primary-500/10 blur-3xl"></div>
</div>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl w-full" 
         x-data="{ mounted: false }" 
         x-init="setTimeout(() => mounted = true, 100)">
        
        {{-- Main Card - The Castle --}}
        <div class="bg-white dark:bg-secondary-900 rounded-3xl shadow-2xl shadow-secondary-900/10 dark:shadow-black/40 border border-secondary-100 dark:border-secondary-800 overflow-hidden backdrop-blur-sm"
             x-show="mounted"
             x-transition:enter="transition ease-out duration-700"
             x-transition:enter-start="opacity-0 scale-95 translate-y-8"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0">
            
            <div class="grid grid-cols-1 lg:grid-cols-2">
                
                {{-- LEFT: The Invitation --}}
                <div class="p-10 lg:p-14 flex flex-col justify-center space-y-8">
                    
                    {{-- Invitation Badge - "You're Invited!" --}}
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gradient-to-r from-primary-100 to-violet-100 dark:from-primary-900/30 dark:to-violet-900/30 border border-primary-200 dark:border-primary-800 w-fit mx-auto lg:mx-0">
                        <x-ui.icon icon="mail-open" class="w-4 h-4 text-primary-600 dark:text-primary-400 animate-bounce-gentle" />
                        <span class="text-sm font-bold text-primary-700 dark:text-primary-300">{{ trans('common.referral.youre_invited') }}</span>
                    </div>

                    {{-- Hero Message --}}
                    <div class="text-center lg:text-left space-y-4">
                        <h1 class="text-4xl lg:text-5xl font-black bg-gradient-to-r from-secondary-900 via-primary-600 to-violet-600 dark:from-white dark:via-primary-400 dark:to-violet-400 bg-clip-text text-transparent tracking-tight leading-[1.1]">
                            {!! trans('common.referral.landing_title', ['card' => '<span class="block mt-2">' . e($refereeCard->head) . '</span>']) !!}
                        </h1>
                        
                        <p class="text-lg text-secondary-600 dark:text-secondary-400 leading-relaxed">
                            {{ trans('common.referral.landing_subtitle', ['referrer' => $referrer->name, 'card' => $refereeCard->head]) }}
                        </p>
                    </div>

                    {{-- The Gift - What You're Getting --}}
                    <div class="relative group/gift overflow-hidden p-8 rounded-2xl bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 dark:from-emerald-950/30 dark:via-teal-950/20 dark:to-cyan-950/10 border-2 border-emerald-200 dark:border-emerald-800/50 shadow-xl shadow-emerald-500/10">
                        {{-- Shimmer effect --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full group-hover/gift:translate-x-full transition-transform duration-1000"></div>
                        
                        <div class="relative flex items-center gap-5">
                            {{-- Gift Icon --}}
                            <div class="flex-shrink-0 w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/40 transform group-hover/gift:scale-110 group-hover/gift:rotate-12 transition-transform duration-300">
                                <x-ui.icon icon="gift" class="w-8 h-8 text-white" />
                            </div>
                            
                            {{-- Gift Details --}}
                            <div class="flex-1">
                                <div class="text-sm font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 mb-1">
                                    {{ trans('common.referral.your_welcome_gift') }}
                                </div>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-4xl font-black text-emerald-900 dark:text-emerald-100 tabular-nums format-number">{{ $refereePoints ?? 0 }}</span>
                                    <span class="text-lg font-bold text-emerald-700 dark:text-emerald-400">{{ trans('common.referral.points') }}</span>
                                </div>
                                <p class="text-sm text-emerald-700/80 dark:text-emerald-400/80 mt-1">
                                    {{ trans('common.referral.instant_on_signup') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- CTA Buttons --}}
                    <div class="flex flex-col sm:flex-row gap-4">
                        <x-ui.button 
                            :href="route('member.register')" 
                            variant="primary" 
                            size="lg" 
                            class="group/cta relative overflow-hidden flex-1 px-8 py-4 text-lg font-bold shadow-xl shadow-primary-500/30 hover:shadow-2xl hover:shadow-primary-500/40"
                        >
                            {{-- Shimmer --}}
                            <div class="absolute inset-0 -translate-x-full group-hover/cta:translate-x-full transition-transform duration-1000 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                            
                            <span class="relative flex items-center justify-center gap-2">
                                <x-ui.icon icon="sparkles" class="w-5 h-5 animate-pulse" />
                                {{ trans('common.referral.accept_invite') }}
                                <x-ui.icon icon="arrow-right" class="w-5 h-5 transition-transform group-hover/cta:translate-x-1" />
                            </span>
                        </x-ui.button>
                    </div>

                    {{-- Trust Signal --}}
                    <div class="flex items-center gap-3 text-sm text-secondary-500 dark:text-secondary-400 justify-center lg:justify-start">
                        <x-ui.icon icon="shield-check" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        <span>{{ trans('common.referral.free_to_join') }}</span>
                    </div>
                </div>

                {{-- RIGHT: How It Works --}}
                <div class="p-10 lg:p-14 bg-gradient-to-br from-secondary-50 to-primary-50/30 dark:from-secondary-950/50 dark:to-primary-950/20 border-t lg:border-t-0 lg:border-l border-secondary-100 dark:border-secondary-800 flex flex-col justify-center space-y-8">
                    
                    <div class="text-center lg:text-left">
                        <h2 class="text-2xl font-bold text-secondary-900 dark:text-white mb-2">{{ trans('common.referral.how_it_works') }}</h2>
                        <p class="text-sm text-secondary-600 dark:text-secondary-400">{{ trans('common.referral.simple_steps') }}</p>
                    </div>

                    <div class="space-y-6">
                        {{-- Step 1 --}}
                        <div class="flex gap-5 group/step">
                            <div class="flex-shrink-0 relative">
                                {{-- Number Badge --}}
                                <div class="w-12 h-12 rounded-xl bg-white dark:bg-secondary-900 border-2 border-primary-200 dark:border-primary-800 flex items-center justify-center shadow-lg z-10 relative group-hover/step:scale-110 transition-transform duration-300">
                                    <span class="text-lg font-black text-primary-600 dark:text-primary-400">1</span>
                                </div>
                                {{-- Connector Line --}}
                                <div class="absolute top-12 left-1/2 -translate-x-1/2 w-0.5 h-10 bg-gradient-to-b from-primary-200 to-transparent dark:from-primary-800"></div>
                            </div>
                            <div class="flex-1 pt-2">
                                <h3 class="text-lg font-bold text-secondary-900 dark:text-white mb-1">{{ trans('common.referral.step_1') }}</h3>
                                <p class="text-sm text-secondary-600 dark:text-secondary-400 leading-relaxed">{{ trans('common.referral.step_1_desc') }}</p>
                            </div>
                        </div>

                        {{-- Step 2 --}}
                        <div class="flex gap-5 group/step">
                            <div class="flex-shrink-0 relative">
                                <div class="w-12 h-12 rounded-xl bg-white dark:bg-secondary-900 border-2 border-violet-200 dark:border-violet-800 flex items-center justify-center shadow-lg z-10 relative group-hover/step:scale-110 transition-transform duration-300">
                                    <span class="text-lg font-black text-violet-600 dark:text-violet-400">2</span>
                                </div>
                                <div class="absolute top-12 left-1/2 -translate-x-1/2 w-0.5 h-10 bg-gradient-to-b from-violet-200 to-transparent dark:from-violet-800"></div>
                            </div>
                            <div class="flex-1 pt-2">
                                <h3 class="text-lg font-bold text-secondary-900 dark:text-white mb-1">{{ trans('common.referral.step_2') }}</h3>
                                <p class="text-sm text-secondary-600 dark:text-secondary-400 leading-relaxed">{{ trans('common.referral.step_2_desc') }}</p>
                            </div>
                        </div>

                        {{-- Step 3 - Success! --}}
                        <div class="flex gap-5 group/step">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/40 group-hover/step:scale-110 group-hover/step:rotate-12 transition-all duration-300">
                                    <x-ui.icon icon="check" class="w-6 h-6 text-white" />
                                </div>
                            </div>
                            <div class="flex-1 pt-2">
                                <h3 class="text-lg font-bold text-emerald-900 dark:text-emerald-100 mb-1">{{ trans('common.referral.step_3') }}</h3>
                                <p class="text-sm text-emerald-700 dark:text-emerald-400 leading-relaxed font-medium">{{ trans('common.referral.step_3_desc') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Bonus: Referrer Gets Rewarded Too --}}
                    <div class="p-5 rounded-xl bg-white/60 dark:bg-secondary-900/60 border border-secondary-200 dark:border-secondary-800 backdrop-blur-sm">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                                <x-ui.icon icon="users" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-bold text-secondary-900 dark:text-white mb-1">{{ trans('common.referral.everyone_wins') }}</div>
                                <p class="text-xs text-secondary-600 dark:text-secondary-400 leading-relaxed">
                                    {{ trans('common.referral.referrer_also_gets', ['name' => $referrer->name, 'points' => $referrerPoints ?? 0]) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer Note --}}
        <div class="mt-8 text-center text-sm text-secondary-500 dark:text-secondary-400"
             x-show="mounted"
             x-transition:enter="transition ease-out duration-700 delay-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0">
            <p>© {{ date('Y') }} {{ $refereeCard->club->name }}. {{ trans('common.all_rights_reserved') }}</p>
        </div>
    </div>
</div>

{{-- Magical Animations --}}
@push('styles')
<style>
/* Sparkle floating animation - like fireflies */
@keyframes sparkle-float {
    0%, 100% { 
        transform: translate(0, 0) scale(1);
        opacity: 0.4;
    }
    25% { 
        transform: translate(20px, -30px) scale(1.3);
        opacity: 0.8;
    }
    50% { 
        transform: translate(-15px, -50px) scale(0.8);
        opacity: 1;
    }
    75% { 
        transform: translate(10px, -35px) scale(1.1);
        opacity: 0.6;
    }
}

.animate-sparkle-float {
    animation: sparkle-float 8s ease-in-out infinite;
}

/* Gentle bounce for invitation badge */
@keyframes bounce-gentle {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}

.animate-bounce-gentle {
    animation: bounce-gentle 2s ease-in-out infinite;
}

/* Slow pulsing glow */
@keyframes pulse-glow-slow {
    0%, 100% { 
        opacity: 0.3;
        transform: scale(1);
    }
    50% { 
        opacity: 0.5;
        transform: scale(1.05);
    }
}

.animate-pulse-glow-slow {
    animation: pulse-glow-slow 6s ease-in-out infinite;
}

/* Radial gradient utility */
.bg-gradient-radial {
    background: radial-gradient(circle, var(--tw-gradient-stops));
}
</style>
@endpush

@endsection
