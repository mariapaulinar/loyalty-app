{{-- 
    Reward Loyalty - Proprietary Software
    Copyright (c) 2025 NowSquare. All rights reserved.
    See LICENSE file for terms.

    Shopify Integration Dashboard — Premium Edition
    
    Design Philosophy:
    "The details are not the details. They make the design." — Charles Eames
    
    This page MUST feel like a native part of Reward Loyalty, not a
    technical afterthought. Premium card-based design with tabbed
    navigation for Settings, Rewards, and Activity.
--}}

@extends('partner.layouts.default')

@section('page_title', trans('common.shopify_integration') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="min-h-screen relative" x-data="shopifyIntegration()">
    <div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
        
        {{-- ═══════════════════════════════════════════════════════════════════════════
            PAGE HEADER — Using standard component for consistency
        ═══════════════════════════════════════════════════════════════════════════ --}}
        <x-ui.page-header
            :title="'Shopify'"
            :description="trans('common.shopify_integration_subtitle')"
        >
            {{-- Custom Shopify Icon (replaces default icon) --}}
            <x-slot name="icon">
                <div class="w-10 h-10 rounded-xl bg-[#95BF47] flex items-center justify-center">
                    <img src="{{ asset('assets/img/brands/shopify/shopify_glyph_white.svg') }}" alt="Shopify" class="w-5 h-5" />
                </div>
            </x-slot>

            {{-- Right Side: Demo Badge + Club Selector --}}
            <x-slot name="actions">
                <div class="flex items-center gap-3">
                    {{-- Demo Mode Badge --}}
                    @if(config('default.app_demo'))
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30">
                            <x-ui.icon icon="flask-conical" class="w-3.5 h-3.5" />
                            {{ trans('common.demo_mode_badge') }}
                        </span>
                    @endif

                    {{-- Club Selector --}}
                    @if($clubs->count() > 1)
                        <div class="relative">
                            <select id="club-selector"
                                class="appearance-none bg-white dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 text-secondary-700 dark:text-secondary-300 text-sm rounded-xl px-4 pr-10 py-2.5 shadow-sm hover:border-secondary-300 dark:hover:border-secondary-600 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all cursor-pointer">
                                @foreach($clubs as $c)
                                    <option value="{{ $c->id }}" @if($c->id === $club->id) selected @endif>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <x-ui.icon icon="chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400 pointer-events-none" />
                        </div>
                        <script>
                            document.getElementById('club-selector')?.addEventListener('change', function() {
                                window.location.href = '{{ route("partner.integrations.shopify") }}?club_id=' + this.value;
                            });
                        </script>
                    @endif
                </div>
            </x-slot>

            {{-- Connection Status Badge — Admin dashboard style with animated ping --}}
            @if($integration)
                <div class="flex items-center gap-3">
                    @if($integration->status->value === 'active')
                        <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            {{ trans('common.connected_and_active') }}
                        </span>
                    @elseif($integration->status->value === 'paused')
                        <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full text-xs font-medium bg-amber-500/10 text-amber-600 dark:text-amber-400">
                            <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                            {{ trans('common.integration_paused') }}
                        </span>
                    @elseif($integration->status->value === 'error')
                        <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full text-xs font-medium bg-red-500/10 text-red-600 dark:text-red-400">
                            <x-ui.icon icon="alert-triangle" class="w-3.5 h-3.5" />
                            {{ trans('common.connection_issue') }}
                        </span>
                    @endif
                    <span class="text-xs text-secondary-400 dark:text-secondary-500">{{ $integration->store_identifier }}</span>
                </div>
            @endif
        </x-ui.page-header>

        @if(!$integration)
            {{-- ═══════════════════════════════════════════════════════════════════════════
                NOT CONNECTED — Beautiful onboarding flow
            ═══════════════════════════════════════════════════════════════════════════ --}}
            
            {{-- How It Works — Premium Cards --}}
            <section class="mb-10 animate-fade-in" style="animation-delay: 80ms;">
                <h2 class="text-xl font-bold text-secondary-900 dark:text-white mb-6 flex items-center gap-2">
                    <x-ui.icon icon="sparkles" class="w-5 h-5 text-primary-500" />
                    {{ trans('common.how_it_works') }}
                </h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    {{-- Step 1: Customer Orders --}}
                    <div class="group relative bg-white dark:bg-secondary-900 rounded-3xl p-8 
                        border border-secondary-100 dark:border-secondary-800
                        hover:border-secondary-200 dark:hover:border-secondary-700
                        shadow-sm transition-colors duration-200">
                        <x-ui.icon icon="shopping-cart" class="w-6 h-6 text-secondary-400 dark:text-secondary-500 mb-6" />
                        <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-2">{{ trans('common.customer_orders') }}</h3>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400 leading-relaxed">{{ trans('common.customer_orders_desc') }}</p>
                    </div>
                    
                    {{-- Step 2: Earns Points --}}
                    <div class="group relative bg-white dark:bg-secondary-900 rounded-3xl p-8 
                        border border-secondary-100 dark:border-secondary-800
                        hover:border-secondary-200 dark:hover:border-secondary-700
                        shadow-sm transition-colors duration-200">
                        <x-ui.icon icon="coins" class="w-6 h-6 text-secondary-400 dark:text-secondary-500 mb-6" />
                        <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-2">{{ trans('common.earns_points_auto') }}</h3>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400 leading-relaxed">{{ trans('common.earns_points_auto_desc') }}</p>
                    </div>
                    
                    {{-- Step 3: Redeems Rewards --}}
                    <div class="group relative bg-white dark:bg-secondary-900 rounded-3xl p-8 
                        border border-secondary-100 dark:border-secondary-800
                        hover:border-secondary-200 dark:hover:border-secondary-700
                        shadow-sm transition-colors duration-200">
                        <x-ui.icon icon="gift" class="w-6 h-6 text-secondary-400 dark:text-secondary-500 mb-6" />
                        <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-2">{{ trans('common.redeems_rewards') }}</h3>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400 leading-relaxed">{{ trans('common.redeems_rewards_desc') }}</p>
                    </div>
                </div>
                
                {{-- Info Banner --}}
                <div class="mt-10 flex items-center gap-4 p-5 rounded-2xl bg-primary-500/5 dark:bg-primary-500/10">
                    <x-ui.icon icon="info" class="w-5 h-5 text-secondary-400 dark:text-secondary-500 flex-shrink-0" />
                    <p class="text-sm text-secondary-600 dark:text-secondary-400">{{ trans('common.customers_become_members') }}</p>
                </div>
            </section>
            
            {{-- Connect Card --}}
            <section class="animate-fade-in" style="animation-delay: 160ms;">
                <div class="bg-white dark:bg-secondary-900 rounded-3xl p-10 border border-secondary-100 dark:border-secondary-800 shadow-sm text-center">
                    @if($availableCards->isEmpty())
                        {{-- No Cards: Show warning --}}
                        <x-ui.icon icon="credit-card" class="w-8 h-8 text-secondary-400 dark:text-secondary-500 mx-auto mb-6" />
                        <h3 class="text-2xl font-bold text-secondary-900 dark:text-white mb-3">{{ trans('common.create_card_first_title') }}</h3>
                        <p class="text-secondary-500 dark:text-secondary-400 mb-8 max-w-lg mx-auto">{{ trans('common.create_card_first_desc') }}</p>
                        <a href="{{ route('partner.data.list', ['name' => 'cards']) }}" 
                           class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded-2xl font-medium text-sm
                               bg-secondary-900 dark:bg-white text-white dark:text-secondary-900 
                               hover:bg-secondary-800 dark:hover:bg-secondary-100
                               transition-colors duration-200">
                            <x-ui.icon icon="plus" class="w-4 h-4" />
                            {{ trans('common.create_loyalty_card') }}
                        </a>
                    @else
                        {{-- Ready to Connect --}}
                        <div class="w-16 h-16 mx-auto mb-6 rounded-2xl bg-[#95BF47] flex items-center justify-center">
                            <img src="{{ asset('assets/img/brands/shopify/shopify_glyph_white.svg') }}" alt="Shopify" class="w-8 h-8" />
                        </div>
                        <h3 class="text-2xl font-bold text-secondary-900 dark:text-white mb-3">{{ trans('common.connect_your_store') }}</h3>
                        <p class="text-secondary-500 dark:text-secondary-400 mb-8 max-w-lg mx-auto">{{ trans('common.connect_store_desc') }}</p>
                        
                        {{-- Connect Form --}}
                        <form action="{{ route('partner.integrations.shopify.install') }}" method="GET" class="max-w-md mx-auto text-left space-y-5">
                            <input type="hidden" name="club_id" value="{{ $club->id }}">
                            
                            {{-- Card Selection (Required) --}}
                            <x-forms.select
                                name="card_id"
                                :label="trans('common.loyalty_card')"
                                :options="$availableCards->pluck('name', 'id')->toArray()"
                                :placeholder="trans('common.choose_card')"
                                :text="trans('common.shopify_card_selection_help')"
                                required
                            />
                            
                            {{-- Shop Domain --}}
                            <x-forms.input
                                type="text"
                                name="shop"
                                :label="trans('common.shopify_store_url')"
                                placeholder="your-store"
                                suffix=".myshopify.com"
                                :text="trans('common.shopify_store_url_help')"
                                required
                                pattern="[a-zA-Z0-9\-]+"
                            />
                            
                            <div class="text-center pt-2">
                                <button type="submit"
                                   class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded-2xl font-medium text-sm
                                       bg-[#95BF47] text-white 
                                       hover:bg-[#86AC3F]
                                       transition-colors duration-200">
                                    <x-ui.icon icon="link" class="w-4 h-4" />
                                    {{ trans('common.connect_shopify') }}
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </section>
            
        @else
            {{-- ═══════════════════════════════════════════════════════════════════════════
                CONNECTED — Tabbed Interface for Settings
            ═══════════════════════════════════════════════════════════════════════════ --}}
            
            @php
                $tabsArray = [
                    trans('common.overview'),
                    trans('common.settings'),
                    trans('common.activity'),
                ];
            @endphp
            
            <x-ui.tabs 
                :tabs="$tabsArray"
                :active-tab="session('shopify_active_tab', 1)"
                tab-class="shopify-tabs"
            >
                {{-- ═══════════════════════════════════════════════════════════════════
                    TAB 1: OVERVIEW
                ═══════════════════════════════════════════════════════════════════ --}}
                <x-slot name="tab1">
                    <div class="space-y-8">
                        {{-- Connection Info Cards — Premium card design with icons --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                            {{-- Store --}}
                            <div class="group bg-white dark:bg-secondary-900 rounded-2xl p-6 
                                border border-secondary-100 dark:border-secondary-800
                                shadow-sm transition-colors duration-200">
                                <div class="flex items-start gap-4">
                                    <x-ui.icon icon="store" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 mb-1">{{ trans('common.store') }}</p>
                                        <p class="text-base font-semibold text-secondary-900 dark:text-white truncate">{{ $integration->store_identifier }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Loyalty Card --}}
                            @php
                                $selectedCard = $availableCards->firstWhere('id', $currentSettings['card_id']);
                            @endphp
                            <div class="group bg-white dark:bg-secondary-900 rounded-2xl p-6 
                                border border-secondary-100 dark:border-secondary-800
                                shadow-sm transition-colors duration-200">
                                <div class="flex items-start gap-4">
                                    <x-ui.icon icon="credit-card" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 mb-1">{{ trans('common.loyalty_card') }}</p>
                                        <p class="text-base font-semibold text-secondary-900 dark:text-white truncate">
                                            {{ $selectedCard?->name ?? trans('common.not_selected') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Connected Since --}}
                            <div class="group bg-white dark:bg-secondary-900 rounded-2xl p-6 
                                border border-secondary-100 dark:border-secondary-800
                                shadow-sm transition-colors duration-200">
                                <div class="flex items-start gap-4">
                                    <x-ui.icon icon="calendar-check" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 mb-1">{{ trans('common.connected') }}</p>
                                        <p class="text-base font-semibold text-secondary-900 dark:text-white format-date" data-date="{{ $integration->created_at }}">{{ $integration->created_at->format('M d, Y') }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Recent Activity --}}
                            <div class="group bg-white dark:bg-secondary-900 rounded-2xl p-6 
                                border border-secondary-100 dark:border-secondary-800
                                shadow-sm transition-colors duration-200">
                                <div class="flex items-start gap-4">
                                    <x-ui.icon icon="activity" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-secondary-400 dark:text-secondary-500 mb-1">{{ trans('common.recent_activity') }}</p>
                                        <p class="text-base font-semibold text-secondary-900 dark:text-white">{{ trans('common.webhook_events_from_shopify') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Widget Embed Code — iOS-grade clean design --}}
                        @if($widgetSnippet)
                        <div class="rounded-2xl bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                            {{-- Header --}}
                            <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-[#95BF47]/10 flex items-center justify-center">
                                            <x-ui.icon icon="code-2" class="w-5 h-5 text-[#95BF47]" />
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-secondary-900 dark:text-white">{{ trans('common.widget_embed_code') }}</h3>
                                            <p class="text-sm text-secondary-500 dark:text-secondary-400">{{ trans('common.paste_before_body') }}</p>
                                        </div>
                                    </div>
                                    <button 
                                        type="button"
                                        @click="copySnippet()"
                                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200"
                                        :class="snippetCopied 
                                            ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/25' 
                                            : 'bg-secondary-900 dark:bg-white text-white dark:text-secondary-900 hover:bg-secondary-800 dark:hover:bg-secondary-100 shadow-sm hover:shadow-md'"
                                    >
                                        <x-ui.icon icon="clipboard-copy" class="w-4 h-4" x-show="!snippetCopied" />
                                        <x-ui.icon icon="check" class="w-4 h-4" x-show="snippetCopied" x-cloak />
                                        <span x-text="snippetCopied ? '{{ trans('common.copied') }}' : '{{ trans('common.copy_code') }}'"></span>
                                    </button>
                                </div>
                            </div>
                            
                            {{-- Code Block --}}
                            <div class="bg-secondary-950 dark:bg-black/40 p-5">
                                <pre class="text-xs text-secondary-300 overflow-x-auto font-mono leading-relaxed whitespace-pre-wrap break-all" id="widget-snippet">{{ $widgetSnippet['snippet'] }}</pre>
                            </div>
                            
                            {{-- Installation Steps --}}
                            <div class="px-6 py-5 bg-secondary-50/50 dark:bg-secondary-800/30">
                                <p class="text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider mb-4">{{ trans('common.installation_steps') }}</p>
                                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div class="flex items-start gap-3">
                                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[#95BF47] text-white text-xs font-bold flex items-center justify-center">1</span>
                                        <p class="text-sm text-secondary-600 dark:text-secondary-400 leading-snug">{{ trans('common.install_step_1') }}</p>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[#95BF47] text-white text-xs font-bold flex items-center justify-center">2</span>
                                        <p class="text-sm text-secondary-600 dark:text-secondary-400 leading-snug">{{ trans('common.install_step_2') }}</p>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[#95BF47] text-white text-xs font-bold flex items-center justify-center">3</span>
                                        <p class="text-sm text-secondary-600 dark:text-secondary-400 leading-snug">{{ trans('common.install_step_3') }}</p>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[#95BF47] text-white text-xs font-bold flex items-center justify-center">4</span>
                                        <p class="text-sm text-secondary-600 dark:text-secondary-400 leading-snug">{{ trans('common.install_step_4') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        {{-- Connection Management Card --}}
                        <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-100 dark:border-secondary-800 shadow-sm overflow-hidden">
                            <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center">
                                        <x-ui.icon icon="settings" class="w-5 h-5 text-secondary-500 dark:text-secondary-400" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-secondary-900 dark:text-white">{{ trans('common.connection_management') }}</h3>
                                        <p class="text-sm text-secondary-500 dark:text-secondary-400">{{ trans('common.manage_your_store_connection') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    {{-- Status & Info --}}
                                    <div class="flex items-center gap-3">
                                        @if($integration->status->value === 'active')
                                            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                                <span class="relative flex h-1.5 w-1.5">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                                </span>
                                                {{ trans('common.receiving_webhooks') }}
                                            </span>
                                        @elseif($integration->status->value === 'paused')
                                            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                                <x-ui.icon icon="pause" class="w-3.5 h-3.5" />
                                                {{ trans('common.webhooks_paused') }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    {{-- Actions --}}
                                    <div class="flex items-center gap-2">
                                        @if($integration->status->value === 'active')
                                            <form action="{{ route('partner.integrations.shopify.pause', $integration->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" 
                                                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium 
                                                        text-secondary-700 dark:text-secondary-300 
                                                        bg-secondary-100 dark:bg-secondary-800 
                                                        hover:bg-secondary-200 dark:hover:bg-secondary-700 
                                                        rounded-xl transition-all duration-200">
                                                    <x-ui.icon icon="pause" class="w-4 h-4" />
                                                    {{ trans('common.pause') }}
                                                </button>
                                            </form>
                                        @elseif($integration->status->value === 'paused')
                                            <form action="{{ route('partner.integrations.shopify.resume', $integration->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" 
                                                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium 
                                                        text-white bg-emerald-500 hover:bg-emerald-600
                                                        shadow-sm hover:shadow-md shadow-emerald-500/20
                                                        rounded-xl transition-all duration-200">
                                                    <x-ui.icon icon="play" class="w-4 h-4" />
                                                    {{ trans('common.resume') }}
                                                </button>
                                            </form>
                                        @endif
                                        
                                        <button type="button" @click="showDisconnect = true" 
                                            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium 
                                                text-red-600 dark:text-red-400 
                                                hover:bg-red-50 dark:hover:bg-red-900/20 
                                                rounded-xl transition-all duration-200">
                                            <x-ui.icon icon="unplug" class="w-4 h-4" />
                                            {{ trans('common.disconnect') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-slot>
                
                {{-- ═══════════════════════════════════════════════════════════════════
                    TAB 2: SETTINGS
                ═══════════════════════════════════════════════════════════════════ --}}
                <x-slot name="tab2">
                    <form action="{{ route('partner.integrations.shopify.settings') }}" method="POST">
                        @csrf
                        <input type="hidden" name="integration_id" value="{{ $integration->id }}">
                        
                        <div class="space-y-8">
                            {{-- Loyalty Card Selection --}}
                            <div>
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                                        <x-ui.icon icon="credit-card" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-secondary-900 dark:text-white">{{ trans('common.loyalty_card') }}</h3>
                                        <p class="text-sm text-secondary-500 dark:text-secondary-400">{{ trans('common.shopify_card_selection_help') }}</p>
                                    </div>
                                </div>
                                
                                <x-forms.select
                                    name="settings[card_id]"
                                    :label="trans('common.loyalty_card')"
                                    :value="$currentSettings['card_id']"
                                    :options="$availableCards->pluck('name', 'id')->toArray()"
                                    required
                                />
                            </div>
                            
                            {{-- Divider --}}
                            <div class="h-px bg-secondary-200 dark:bg-secondary-800"></div>
                            
                            {{-- Widget Appearance --}}
                            <div>
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="w-10 h-10 rounded-xl bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center">
                                        <x-ui.icon icon="palette" class="w-5 h-5 text-violet-600 dark:text-violet-400" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-secondary-900 dark:text-white">{{ trans('common.widget_appearance') }}</h3>
                                        <p class="text-sm text-secondary-500 dark:text-secondary-400">{{ trans('common.customize_storefront_widget') }}</p>
                                    </div>
                                </div>
                                
                                <div class="grid md:grid-cols-2 gap-6">
                                    <x-forms.input
                                        type="text"
                                        name="settings[widget_program_name]"
                                        :label="trans('common.program_name')"
                                        :value="$currentSettings['widget_program_name']"
                                        maxlength="50"
                                        placeholder="Rewards"
                                        :text="trans('common.program_name_shown_to_customers')"
                                    />
                                    
                                    <x-forms.input
                                        type="color"
                                        name="settings[widget_primary_color]"
                                        :label="trans('common.brand_color')"
                                        :value="$currentSettings['widget_primary_color']"
                                    />
                                    
                                    <x-forms.select
                                        name="settings[widget_mode]"
                                        :label="trans('common.color_mode')"
                                        :value="$currentSettings['widget_mode']"
                                        :options="[
                                            'auto' => trans('common.auto_match_theme'),
                                            'light' => trans('common.always_light'),
                                            'dark' => trans('common.always_dark'),
                                        ]"
                                    />
                                    
                                    <x-forms.select
                                        name="settings[widget_position]"
                                        :label="trans('common.widget_position')"
                                        :value="$currentSettings['widget_position']"
                                        :options="[
                                            'bottom-right' => trans('common.bottom_right'),
                                            'bottom-left' => trans('common.bottom_left'),
                                            'top-right' => trans('common.top_right'),
                                            'top-left' => trans('common.top_left'),
                                        ]"
                                    />
                                </div>
                            </div>
                            
                            {{-- Save Button --}}
                            <div class="flex justify-end pt-4 border-t border-secondary-200 dark:border-secondary-800">
                                <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-500 text-white text-sm font-medium rounded-xl shadow-sm hover:shadow-md transition-all active:scale-[0.98]">
                                    <x-ui.icon icon="save" class="w-4 h-4" />
                                    {{ trans('common.save_settings') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </x-slot>
                
                {{-- ═══════════════════════════════════════════════════════════════════
                    TAB 3: ACTIVITY
                ═══════════════════════════════════════════════════════════════════ --}}
                <x-slot name="tab3">
                    <div>
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center">
                                    <x-ui.icon icon="activity" class="w-5 h-5 text-secondary-600 dark:text-secondary-400" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-secondary-900 dark:text-white">{{ trans('common.recent_activity') }}</h3>
                                    <p class="text-sm text-secondary-500 dark:text-secondary-400">{{ trans('common.webhook_activity_log') }}</p>
                                </div>
                            </div>
                        </div>
                        
                        @if($recentReceipts->isEmpty())
                            <div class="text-center py-12 bg-secondary-50 dark:bg-secondary-800/50 rounded-2xl">
                                <x-ui.icon icon="inbox" class="w-10 h-10 mx-auto text-secondary-300 dark:text-secondary-600 mb-3" />
                                <p class="text-secondary-500 dark:text-secondary-400">{{ trans('common.no_activity_yet') }}</p>
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach($recentReceipts as $receipt)
                                    @php
                                        $statusConfig = match($receipt->status) {
                                            'processed' => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-600 dark:text-emerald-400', 'icon' => 'check-circle'],
                                            'ignored', 'skipped' => ['bg' => 'bg-secondary-500/10', 'text' => 'text-secondary-500 dark:text-secondary-400', 'icon' => 'minus-circle'],
                                            'failed' => ['bg' => 'bg-red-500/10', 'text' => 'text-red-600 dark:text-red-400', 'icon' => 'x-circle'],
                                            'queued' => ['bg' => 'bg-blue-500/10', 'text' => 'text-blue-600 dark:text-blue-400', 'icon' => 'clock'],
                                            default => ['bg' => 'bg-secondary-500/10', 'text' => 'text-secondary-500', 'icon' => 'circle'],
                                        };
                                    @endphp
                                    <div class="flex items-center gap-4 p-4 rounded-xl bg-secondary-50 dark:bg-secondary-800/50 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors">
                                        <div class="w-8 h-8 rounded-lg {{ $statusConfig['bg'] }} flex items-center justify-center flex-shrink-0">
                                            <x-ui.icon :icon="$statusConfig['icon']" class="w-4 h-4 {{ $statusConfig['text'] }}" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-secondary-900 dark:text-white">{{ $receipt->topic }}</p>
                                            <p class="text-xs text-secondary-500 dark:text-secondary-400">
                                                {{ $receipt->created_at->diffForHumans() }}
                                                @if($receipt->resource_id)
                                                    · #{{ $receipt->resource_id }}
                                                @endif
                                            </p>
                                        </div>
                                        <span class="px-2.5 py-1 text-xs font-medium rounded-lg {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }}">
                                            {{ ucfirst($receipt->status) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </x-slot>
            </x-ui.tabs>
            
            {{-- Disconnect Modal --}}
            <div x-show="showDisconnect" x-cloak 
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click.self="showDisconnect = false">
                <div class="bg-white dark:bg-secondary-900 rounded-3xl shadow-2xl max-w-md w-full p-8" 
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                            <x-ui.icon icon="alert-triangle" class="w-8 h-8 text-red-600 dark:text-red-400" />
                        </div>
                        <h3 class="text-xl font-bold text-secondary-900 dark:text-white mb-2">{{ trans('common.disconnect_store_title') }}</h3>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400">{{ trans('common.disconnect_store_warning') }}</p>
                    </div>
                    <form action="{{ route('partner.integrations.shopify.disconnect', $integration->id) }}" method="POST">
                        @csrf
                        <div class="mb-6">
                            <label for="disconnect-confirm" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">{{ trans('common.type_confirm_to_disconnect') }}</label>
                            <input 
                                type="text" 
                                id="disconnect-confirm"
                                name="confirm" 
                                placeholder="DISCONNECT" 
                                required 
                                autocomplete="off"
                                class="w-full bg-white dark:bg-secondary-800/50 
                                       border border-secondary-200 dark:border-secondary-700 
                                       text-secondary-900 dark:text-white text-sm 
                                       rounded-xl block p-3 
                                       shadow-sm
                                       transition-all duration-200 
                                       placeholder:text-secondary-400 dark:placeholder:text-secondary-500 
                                       focus:outline-none focus:bg-white dark:focus:bg-secondary-800
                                       focus:border-red-500 focus:ring-2 focus:ring-red-500/20 
                                       focus:shadow-md focus:shadow-red-500/5
                                       hover:border-secondary-300 dark:hover:border-secondary-600 hover:shadow-md
                                       uppercase tracking-wider text-center font-mono"
                            >
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="showDisconnect = false" 
                                class="flex-1 px-4 py-3 bg-secondary-100 dark:bg-secondary-800 text-secondary-700 dark:text-secondary-300 font-medium rounded-xl hover:bg-secondary-200 dark:hover:bg-secondary-700 transition-colors">
                                {{ trans('common.cancel') }}
                            </button>
                            <button type="submit" 
                                class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-colors">
                                {{ trans('common.disconnect') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Alpine.js Component --}}
<script>
function shopifyIntegration() {
    return {
        showDisconnect: false,
        snippetCopied: false,
        
        copySnippet() {
            const snippet = document.getElementById('widget-snippet');
            if (!snippet) return;
            
            const text = snippet.textContent;
            
            // Use modern Clipboard API if available (requires HTTPS)
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    this.snippetCopied = true;
                    setTimeout(() => this.snippetCopied = false, 2000);
                }).catch(() => this.fallbackCopy(text));
            } else {
                // Fallback for HTTP or older browsers
                this.fallbackCopy(text);
            }
        },
        
        fallbackCopy(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                this.snippetCopied = true;
                setTimeout(() => this.snippetCopied = false, 2000);
            } catch (e) {
                console.error('Copy failed:', e);
            }
            document.body.removeChild(textarea);
        }
    }
}
</script>

@push('styles')
<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.5s ease-out forwards;
}
</style>
@endpush
@stop
