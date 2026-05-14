@extends('member.layouts.default')

@section('page_title', trans('common.page_not_found') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 md:px-8 py-12 md:py-20">
    <div class="max-w-2xl w-full text-center space-y-8 animate-fade-in-up">
        {{-- Animated Icon --}}
        <div class="relative inline-block">
            <div
                class="absolute inset-0 bg-gradient-to-r from-amber-500/20 to-orange-500/20 blur-3xl rounded-full animate-pulse-slow">
            </div>
            <div
                class="relative bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 p-12 rounded-3xl shadow-2xl border border-amber-200 dark:border-amber-800/50">
                <x-ui.icon icon="gift" class="w-32 h-32 text-amber-500 dark:text-amber-400 animate-bounce-slow" />
                <div class="absolute -top-2 -right-2 flex gap-1">
                    <div class="w-2 h-2 bg-amber-500 rounded-full animate-ping"></div>
                    <div class="w-2 h-2 bg-orange-500 rounded-full animate-ping" style="animation-delay: 0.2s"></div>
                </div>
            </div>
        </div>

        {{-- Heading --}}
        <div class="space-y-3 animate-fade-in delay-100">
            <h1 class="text-5xl md:text-6xl font-bold text-secondary-900 dark:text-white tracking-tight">
                Reward Not Found
            </h1>
            <p class="text-xl text-secondary-600 dark:text-secondary-400 max-w-md mx-auto leading-relaxed">
                {{ trans('common.no_reward_found') }}. This reward may no longer be available or has been removed.
            </p>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 animate-fade-in delay-200">
            <a href="{{ route('member.cards') }}"
                class="group inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-semibold rounded-xl shadow-lg shadow-amber-500/30 hover:shadow-amber-500/50 transition-all duration-300 hover:-translate-y-0.5">
                <x-ui.icon icon="gift" class="w-5 h-5 group-hover:rotate-12 transition-transform" />
                <span>Browse Rewards</span>
            </a>
            <a href="{{ route('member.index') }}"
                class="group inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-secondary-800 hover:bg-secondary-50 dark:hover:bg-secondary-700 text-secondary-900 dark:text-white font-semibold rounded-xl border border-secondary-200 dark:border-secondary-700 shadow-sm hover:shadow-md transition-all duration-300">
                <x-ui.icon icon="home" class="w-5 h-5 group-hover:-translate-x-1 rtl:group-hover:translate-x-1 transition-transform" />
                <span>Back to Home</span>
            </a>
        </div>

        {{-- Help Text --}}
        <div class="pt-8 border-t border-secondary-200 dark:border-secondary-800 animate-fade-in delay-300">
            <p class="text-sm text-secondary-500 dark:text-secondary-500">
                Looking for something specific? <a href="{{ route('member.contact') }}"
                    class="text-amber-600 dark:text-amber-400 hover:underline font-medium">Contact support</a>
            </p>
        </div>
    </div>
</div>

<style>
    @keyframes bounce-slow {

        0%,
        100% {
            transform: translateY(0) scale(1);
        }

        50% {
            transform: translateY(-15px) scale(1.05);
        }
    }

    .animate-bounce-slow {
        animation: bounce-slow 2s ease-in-out infinite;
    }
</style>
@stop