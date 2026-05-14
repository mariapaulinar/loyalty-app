{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Stamp Card 404 Error Page
--}}

@extends('member.layouts.default')

@section('page_title', trans('common.stamp_card_not_found') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh] px-4 py-12">
    <div class="text-center max-w-md mx-auto animate-fade-in-up">
        {{-- Icon --}}
        <div class="w-32 h-32 mx-auto mb-6 rounded-full bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center">
            <x-ui.icon icon="inbox" class="w-16 h-16 text-secondary-400" />
        </div>
        
        {{-- Title --}}
        <h1 class="text-3xl md:text-4xl font-bold text-secondary-900 dark:text-white mb-4">
            {{ trans('common.stamp_card_not_found') }}
        </h1>
        
        {{-- Description --}}
        <p class="text-lg text-secondary-600 dark:text-secondary-400 mb-8">
            {{ trans('common.stamp_card_not_found_description') }}
        </p>
        
        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <x-ui.button href="{{ route('member.index') }}" variant="accent" size="lg">
                <x-ui.icon icon="home" class="w-5 h-5" />
                {{ trans('common.go_home') }}
            </x-ui.button>
            
            @auth('member')
                <x-ui.button href="{{ route('member.cards') }}" variant="secondary" size="lg">
                    <x-ui.icon icon="layout-dashboard" class="w-5 h-5" />
                    {{ trans('common.dashboard') }}
                </x-ui.button>
            @endauth
        </div>
    </div>
</div>
@stop

