{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.
--}}

@extends('member.layouts.default')

@section('page_title', trans('common.page_not_found') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 md:px-8 py-12 md:py-20">
    <div class="max-w-2xl w-full text-center space-y-8 animate-fade-in-up">
        {{-- Animated Icon --}}
        <div class="relative inline-block">
            <div class="absolute inset-0 bg-purple-500/20 blur-3xl rounded-full animate-pulse-slow"></div>
            <div class="relative bg-gradient-to-br from-secondary-100 to-secondary-200 dark:from-secondary-800 dark:to-secondary-900 p-12 rounded-3xl shadow-2xl border border-secondary-200 dark:border-secondary-700">
                <x-ui.icon icon="ticket" class="w-32 h-32 text-secondary-400 dark:text-secondary-600 animate-float" />
                <div class="absolute top-4 right-4 w-3 h-3 bg-red-500 rounded-full animate-ping"></div>
                <div class="absolute top-4 right-4 w-3 h-3 bg-red-500 rounded-full"></div>
            </div>
        </div>

        {{-- Heading --}}
        <div class="space-y-3 animate-fade-in delay-100">
            <h1 class="text-5xl md:text-6xl font-bold text-secondary-900 dark:text-white tracking-tight">
                Voucher Not Found
            </h1>
            <p class="text-xl text-secondary-600 dark:text-secondary-400 max-w-md mx-auto leading-relaxed">
                {{ __('common.voucher_not_found') }}. This voucher code doesn't exist or may have expired.
            </p>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 animate-fade-in delay-200">
            <a href="{{ route('member.index') }}"
                class="group inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl shadow-lg shadow-primary-500/20 hover:shadow-primary-500/30 transition-all duration-300 hover:-translate-y-0.5">
                <x-ui.icon icon="home" class="w-5 h-5 group-hover:-translate-x-1 rtl:group-hover:translate-x-1 transition-transform" />
                <span>{{ __('common.back_to_home') }}</span>
            </a>
            <a href="{{ route('member.cards') }}"
                class="group inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-secondary-800 hover:bg-secondary-50 dark:hover:bg-secondary-700 text-secondary-900 dark:text-white font-semibold rounded-xl border border-secondary-200 dark:border-secondary-700 shadow-sm hover:shadow-md transition-all duration-300">
                <x-ui.icon icon="layout-dashboard" class="w-5 h-5 group-hover:scale-110 transition-transform" />
                <span>{{ __('common.view_dashboard') }}</span>
            </a>
        </div>

        {{-- Help Text --}}
        <div class="pt-8 border-t border-secondary-200 dark:border-secondary-800 animate-fade-in delay-300">
            <p class="text-sm text-secondary-500 dark:text-secondary-500">
                {{ __('common.need_help') }} <a href="{{ route('member.contact') }}" class="text-primary-600 dark:text-primary-400 hover:underline font-medium">{{ __('common.contact_support') }}</a>
            </p>
        </div>
    </div>
</div>

<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    .animate-float {
        animation: float 3s ease-in-out infinite;
    }
</style>
@stop
