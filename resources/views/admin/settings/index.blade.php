{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

System Settings - Admin Dashboard

Purpose:
Premium settings management interface for super admins. Provides a centralized
hub for configuring branding, compliance, email, and loyalty program settings.

Design Philosophy:
- Linear-inspired aesthetic with clean, minimal design
- Stripe-level polish with subtle gradients and smooth transitions
- Card-based organization for visual hierarchy
- Tab navigation for logical grouping of settings
--}}

@extends('admin.layouts.default')

@section('page_title', trans('common.settings_page.title') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
@php
    // Helper to safely get setting value with fallback to config
    $getSetting = function($category, $key, $default = null) use ($settings) {
        return $settings[$category][$key]['value'] ?? config('default.' . $key, $default);
    };
@endphp

<div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
    {{-- Page Header --}}
    <div class="mb-6">
        <x-ui.page-header
            icon="settings"
            :title="trans('common.settings_page.title')"
            :description="trans('common.settings_page.subtitle')"
        />
    </div>

    {{-- Main Content --}}
    <x-forms.form-open :action="route('admin.settings.update')" method="POST" :files="true" />
        @php
            $tabsArray = [
                trans('common.settings_page.category_branding'),
                trans('common.settings_page.category_homepage'),
                trans('common.settings_page.category_onboarding'),
                trans('common.settings_page.category_compliance'),
                trans('common.settings_page.category_email'),
                trans('common.settings_page.category_loyalty'),
                trans('common.settings_page.category_pwa'),
            ];
        @endphp
        <x-ui.tabs 
            :tabs="$tabsArray"
            :active-tab="session('active_tab', 1)"
            tab-class="settings-tabs"
        >
            {{-- Tab 1: Branding --}}
            <x-slot name="tab1">
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                    <div class="px-6 py-5 border-b border-secondary-200 dark:border-secondary-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                                <x-ui.icon icon="palette" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-secondary-900 dark:text-white">
                                    {{ trans('common.settings_page.category_branding') }}
                                </h2>
                                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                                    {{ trans('common.settings_page.category_branding_desc') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        {{-- Section 1: Basic Settings --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <x-forms.input
                                name="app_name"
                                :label="trans('common.settings_page.app_name')"
                                :value="old('app_name', $getSetting('branding', 'app_name', 'Reward Loyalty'))"
                                :text="trans('common.settings_page.app_name_desc')"
                                icon="type"
                                required
                            />
                            
                            <x-forms.input
                                name="app_url"
                                :label="trans('common.settings_page.app_url')"
                                :value="old('app_url', $getSetting('branding', 'app_url', config('app.url')))"
                                :text="trans('common.settings_page.app_url_desc')"
                                type="url"
                                icon="globe"
                                required
                            />
                        </div>

                        {{-- Section 2: Brand Color --}}
                        <div class="mb-8 pt-6 border-t border-secondary-200 dark:border-secondary-800">
                            <h3 class="text-sm font-semibold text-secondary-900 dark:text-white mb-1">
                                {{ trans('common.settings_page.brand_color') }}
                            </h3>
                            <p class="text-sm text-secondary-500 dark:text-secondary-400 mb-4">
                                {{ trans('common.settings_page.brand_color_desc') }}
                            </p>
                            
                            <div class="max-w-xs">
                                <x-forms.input
                                    type="color"
                                    name="brand_color"
                                    :label="trans('common.settings_page.primary_color')"
                                    :value="old('brand_color', $getSetting('branding', 'brand_color', '#2563EB'))"
                                    icon="palette"
                                />
                            </div>
                        </div>

                        {{-- Section 3: Logo Uploads --}}
                        <div class="pt-6 border-t border-secondary-200 dark:border-secondary-800">
                            <h3 class="text-sm font-semibold text-secondary-900 dark:text-white mb-1">
                                {{ trans('common.settings_page.app_logos') }}
                            </h3>
                            <p class="text-sm text-secondary-500 dark:text-secondary-400 mb-4">
                                {{ trans('common.settings_page.app_logos_desc') }}
                            </p>

                            @php
                                // Fetch logo & favicon media from the brand_color setting record (where branding assets are stored)
                                $brandingSettingRecord = \App\Models\Setting::where('key', 'brand_color')->first();
                                $logoUrl = $brandingSettingRecord?->getFirstMediaUrl('app_logo');
                                $logoDarkUrl = $brandingSettingRecord?->getFirstMediaUrl('app_logo_dark');
                                $faviconUrl = $brandingSettingRecord?->getFirstMediaUrl('app_favicon');
                            @endphp

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-forms.image
                                        name="app_logo"
                                        :label="trans('common.settings_page.app_logo')"
                                        :text="trans('common.settings_page.app_logo_desc')"
                                        :value="$logoUrl ?: null"
                                        accept="image/svg+xml,image/png,image/jpeg,image/webp"
                                        icon="image"
                                        height="min-h-32"
                                        :placeholder="trans('common.settings_page.upload_light_logo')"
                                    />
                                </div>

                                <div>
                                    <x-forms.image
                                        name="app_logo_dark"
                                        :label="trans('common.settings_page.app_logo_dark')"
                                        :text="trans('common.settings_page.app_logo_dark_desc')"
                                        :value="$logoDarkUrl ?: null"
                                        accept="image/svg+xml,image/png,image/jpeg,image/webp"
                                        icon="image"
                                        height="min-h-32"
                                        :placeholder="trans('common.settings_page.upload_dark_logo')"
                                    />
                                </div>
                            </div>

                            {{-- Logo Guidelines --}}
                            <div class="mt-4 rounded-xl bg-secondary-50 dark:bg-secondary-800/50 p-4">
                                <p class="text-sm font-medium text-secondary-900 dark:text-white mb-2">
                                    {{ trans('common.settings_page.logo_guidelines') }}
                                </p>
                                <ul class="text-sm text-secondary-600 dark:text-secondary-400 space-y-1 list-disc list-inside">
                                    <li>{{ trans('common.settings_page.logo_guideline_1') }}</li>
                                    <li>{{ trans('common.settings_page.logo_guideline_2') }}</li>
                                    <li>{{ trans('common.settings_page.logo_guideline_3') }}</li>
                                    <li>{{ trans('common.settings_page.logo_guideline_4') }}</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Section 4: Favicon --}}
                        <div class="mt-8 pt-6 border-t border-secondary-200 dark:border-secondary-800">
                            <h3 class="text-sm font-semibold text-secondary-900 dark:text-white mb-1">
                                {{ trans('common.settings_page.favicon') }}
                            </h3>
                            <p class="text-sm text-secondary-500 dark:text-secondary-400 mb-4">
                                {{ trans('common.settings_page.favicon_desc') }}
                            </p>

                            <div class="max-w-xs">
                                <x-forms.image
                                    name="app_favicon"
                                    :label="trans('common.settings_page.favicon')"
                                    :text="trans('common.settings_page.favicon_format_hint')"
                                    :value="$faviconUrl ?: null"
                                    accept=".ico,.svg,image/x-icon,image/vnd.microsoft.icon,image/svg+xml"
                                    icon="bookmark"
                                    height="min-h-24"
                                    :placeholder="trans('common.settings_page.upload_favicon')"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </x-slot>

            {{-- Tab 2: Homepage --}}
            <x-slot name="tab2">
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                    <div class="px-6 py-5 border-b border-secondary-200 dark:border-secondary-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                                <x-ui.icon icon="layout-dashboard" class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-secondary-900 dark:text-white">
                                    {{ trans('common.settings_page.category_homepage') }}
                                </h2>
                                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                                    {{ trans('common.settings_page.category_homepage_desc') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        @php
                            $homepageLayoutSetting = \App\Models\Setting::where('key', 'homepage_layout')->first();
                            $currentLayout = $homepageLayoutSetting?->value ?? config('default.homepage_layout', 'directory');
                            $heroImageUrl = $homepageLayoutSetting?->getFirstMediaUrl('homepage_hero_image');
                        @endphp

                        {{-- Layout Selection --}}
                        <div class="mb-8">
                            <h3 class="text-sm font-semibold text-secondary-900 dark:text-white mb-1">
                                {{ trans('common.settings_page.homepage_layout') }}
                            </h3>
                            <p class="text-sm text-secondary-500 dark:text-secondary-400 mb-4">
                                {{ trans('common.settings_page.homepage_layout_desc') }}
                            </p>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4" x-data="{ layout: '{{ old('homepage_layout', $currentLayout) }}' }">
                                {{-- Smart Wallet Layout (Default) --}}
                                <label 
                                    class="group relative flex flex-col p-5 rounded-2xl cursor-pointer transition-all duration-300 hover:scale-[1.02]"
                                    :class="layout === 'directory' 
                                        ? 'bg-white dark:bg-secondary-800 ring-2 ring-primary-500 shadow-xl shadow-primary-500/10' 
                                        : 'bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-800 hover:border-secondary-300 dark:hover:border-secondary-700 hover:shadow-lg'"
                                >
                                    <input type="radio" name="homepage_layout" value="directory" 
                                           x-model="layout"
                                           class="sr-only">
                                    
                                    {{-- Icon --}}
                                    <div 
                                        class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-all duration-300"
                                        :class="layout === 'directory' 
                                            ? 'bg-gradient-to-br from-violet-500 to-purple-600 shadow-lg' 
                                            : 'bg-secondary-100 dark:bg-secondary-800 group-hover:bg-secondary-200 dark:group-hover:bg-secondary-700'"
                                    >
                                        <span 
                                            class="transition-colors duration-300"
                                            :class="layout === 'directory' ? 'text-white' : 'text-secondary-500 dark:text-secondary-400'"
                                        >
                                            <x-ui.icon icon="wallet" class="w-6 h-6" />
                                        </span>
                                    </div>
                                    
                                    {{-- Content --}}
                                    <h4 class="font-semibold text-secondary-900 dark:text-white mb-1">
                                        {{ trans('common.settings_page.homepage_layout_smart_wallet') }}
                                    </h4>
                                    <p class="text-sm text-secondary-500 dark:text-secondary-400 leading-relaxed">
                                        {{ trans('common.settings_page.homepage_layout_smart_wallet_desc') }}
                                    </p>

                                    {{-- Selection indicator --}}
                                    <div 
                                        class="absolute top-4 right-4 w-6 h-6 rounded-full flex items-center justify-center transition-all duration-300"
                                        :class="layout === 'directory' 
                                            ? 'bg-primary-500 scale-100' 
                                            : 'bg-secondary-200 dark:bg-secondary-700 scale-75 opacity-0 group-hover:opacity-100'"
                                    >
                                        <x-ui.icon icon="check" class="w-4 h-4 text-white" x-show="layout === 'directory'" />
                                    </div>
                                </label>

                                {{-- Showcase Layout --}}
                                <label 
                                    class="group relative flex flex-col p-5 rounded-2xl cursor-pointer transition-all duration-300 hover:scale-[1.02]"
                                    :class="layout === 'showcase' 
                                        ? 'bg-white dark:bg-secondary-800 ring-2 ring-primary-500 shadow-xl shadow-primary-500/10' 
                                        : 'bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-800 hover:border-secondary-300 dark:hover:border-secondary-700 hover:shadow-lg'"
                                >
                                    <input type="radio" name="homepage_layout" value="showcase" 
                                           x-model="layout"
                                           class="sr-only">
                                    
                                    {{-- Icon --}}
                                    <div 
                                        class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-all duration-300"
                                        :class="layout === 'showcase' 
                                            ? 'bg-gradient-to-br from-emerald-500 to-teal-600 shadow-lg' 
                                            : 'bg-secondary-100 dark:bg-secondary-800 group-hover:bg-secondary-200 dark:group-hover:bg-secondary-700'"
                                    >
                                        <span 
                                            class="transition-colors duration-300"
                                            :class="layout === 'showcase' ? 'text-white' : 'text-secondary-500 dark:text-secondary-400'"
                                        >
                                            <x-ui.icon icon="presentation" class="w-6 h-6" />
                                        </span>
                                    </div>
                                    
                                    {{-- Content --}}
                                    <h4 class="font-semibold text-secondary-900 dark:text-white mb-1">
                                        {{ trans('common.settings_page.homepage_layout_showcase') }}
                                    </h4>
                                    <p class="text-sm text-secondary-500 dark:text-secondary-400 leading-relaxed">
                                        {{ trans('common.settings_page.homepage_layout_showcase_desc') }}
                                    </p>

                                    {{-- Selection indicator --}}
                                    <div 
                                        class="absolute top-4 right-4 w-6 h-6 rounded-full flex items-center justify-center transition-all duration-300"
                                        :class="layout === 'showcase' 
                                            ? 'bg-primary-500 scale-100' 
                                            : 'bg-secondary-200 dark:bg-secondary-700 scale-75 opacity-0 group-hover:opacity-100'"
                                    >
                                        <x-ui.icon icon="check" class="w-4 h-4 text-white" x-show="layout === 'showcase'" />
                                    </div>
                                </label>

                                {{-- Portal Layout --}}
                                <label 
                                    class="group relative flex flex-col p-5 rounded-2xl cursor-pointer transition-all duration-300 hover:scale-[1.02]"
                                    :class="layout === 'portal' 
                                        ? 'bg-white dark:bg-secondary-800 ring-2 ring-primary-500 shadow-xl shadow-primary-500/10' 
                                        : 'bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-800 hover:border-secondary-300 dark:hover:border-secondary-700 hover:shadow-lg'"
                                >
                                    <input type="radio" name="homepage_layout" value="portal" 
                                           x-model="layout"
                                           class="sr-only">
                                    
                                    {{-- Icon --}}
                                    <div 
                                        class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-all duration-300"
                                        :class="layout === 'portal' 
                                            ? 'bg-gradient-to-br from-blue-500 to-cyan-500 shadow-lg' 
                                            : 'bg-secondary-100 dark:bg-secondary-800 group-hover:bg-secondary-200 dark:group-hover:bg-secondary-700'"
                                    >
                                        <span 
                                            class="transition-colors duration-300"
                                            :class="layout === 'portal' ? 'text-white' : 'text-secondary-500 dark:text-secondary-400'"
                                        >
                                            <x-ui.icon icon="log-in" class="w-6 h-6" />
                                        </span>
                                    </div>
                                    
                                    {{-- Content --}}
                                    <h4 class="font-semibold text-secondary-900 dark:text-white mb-1">
                                        {{ trans('common.settings_page.homepage_layout_portal') }}
                                    </h4>
                                    <p class="text-sm text-secondary-500 dark:text-secondary-400 leading-relaxed">
                                        {{ trans('common.settings_page.homepage_layout_portal_desc') }}
                                    </p>

                                    {{-- Selection indicator --}}
                                    <div 
                                        class="absolute top-4 right-4 w-6 h-6 rounded-full flex items-center justify-center transition-all duration-300"
                                        :class="layout === 'portal' 
                                            ? 'bg-primary-500 scale-100' 
                                            : 'bg-secondary-200 dark:bg-secondary-700 scale-75 opacity-0 group-hover:opacity-100'"
                                    >
                                        <x-ui.icon icon="check" class="w-4 h-4 text-white" x-show="layout === 'portal'" />
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Showcase Options (conditional) --}}
                        <div class="pt-6 border-t border-secondary-200 dark:border-secondary-800"
                             x-data="{ showOptions: '{{ old('homepage_layout', $currentLayout) }}' === 'showcase' }"
                             x-show="$root.querySelector('[name=homepage_layout]:checked')?.value === 'showcase'"
                             x-transition>
                            <h3 class="text-sm font-semibold text-secondary-900 dark:text-white mb-1">
                                {{ trans('common.settings_page.homepage_showcase_settings') }}
                            </h3>
                            <p class="text-sm text-secondary-500 dark:text-secondary-400 mb-4">
                                {{ trans('common.settings_page.homepage_showcase_settings_desc') }}
                            </p>

                            <div class="space-y-6">
                                {{-- Hero Background Image --}}
                                <div class="max-w-md">
                                    <x-forms.image
                                        name="homepage_hero_image"
                                        :label="trans('common.settings_page.homepage_hero_image')"
                                        :text="trans('common.settings_page.homepage_hero_image_desc')"
                                        :value="$heroImageUrl ?: null"
                                        accept="image/png,image/jpeg,image/webp"
                                        icon="image"
                                        height="min-h-32"
                                    />
                                </div>

                                {{-- Toggle Options --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="p-4 bg-secondary-50 dark:bg-secondary-800/50 rounded-xl">
                                        <x-forms.checkbox
                                            name="homepage_show_how_it_works"
                                            :label="trans('common.settings_page.homepage_show_how_it_works')"
                                            :text="trans('common.settings_page.homepage_show_how_it_works_desc')"
                                            :checked="old('homepage_show_how_it_works', $getSetting('homepage', 'homepage_show_how_it_works', true))"
                                        />
                                    </div>

                                    <div class="p-4 bg-secondary-50 dark:bg-secondary-800/50 rounded-xl">
                                        <x-forms.checkbox
                                            name="homepage_show_tiers"
                                            :label="trans('common.settings_page.homepage_show_tiers')"
                                            :text="trans('common.settings_page.homepage_show_tiers_desc')"
                                            :checked="old('homepage_show_tiers', $getSetting('homepage', 'homepage_show_tiers', true))"
                                        />
                                    </div>

                                    <div class="p-4 bg-secondary-50 dark:bg-secondary-800/50 rounded-xl">
                                        <x-forms.checkbox
                                            name="homepage_show_member_count"
                                            :label="trans('common.settings_page.homepage_show_member_count')"
                                            :text="trans('common.settings_page.homepage_show_member_count_desc')"
                                            :checked="old('homepage_show_member_count', $getSetting('homepage', 'homepage_show_member_count', false))"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-slot>

            {{-- Tab 3: Onboarding --}}
            <x-slot name="tab3">
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                    <div class="px-6 py-5 border-b border-secondary-200 dark:border-secondary-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center">
                                <x-ui.icon icon="user-plus" class="w-5 h-5 text-violet-600 dark:text-violet-400" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-secondary-900 dark:text-white">
                                    {{ trans('common.settings_page.category_onboarding') }}
                                </h2>
                                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                                    {{ trans('common.settings_page.category_onboarding_desc') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6 space-y-8">
                        {{-- Partner Registration Section --}}
                        <div>
                            <div class="flex items-center gap-2 mb-4">
                                <x-ui.icon icon="building-2" class="w-4 h-4 text-secondary-400" />
                                <p class="text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                    {{ trans('common.settings_page.section_partner_registration') }}
                                </p>
                            </div>
                            
                            {{-- Feature Toggle --}}
                            <div class="p-4 bg-secondary-50 dark:bg-secondary-800/50 rounded-xl border border-secondary-200 dark:border-secondary-700 mb-4">
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                                        <x-ui.icon icon="user-plus" class="w-6 h-6 text-white" />
                                    </div>
                                    <div class="flex-1">
                                        <label class="flex items-start gap-3 cursor-pointer">
                                            <div class="relative inline-flex items-center mt-0.5">
                                                <input 
                                                    type="checkbox" 
                                                    name="partners_can_register" 
                                                    value="1"
                                                    {{ $getSetting('onboarding', 'partners_can_register', config('default.partners_can_register', false)) ? 'checked' : '' }}
                                                    class="sr-only peer"
                                                >
                                                <div class="w-11 h-6 bg-secondary-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-secondary-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-secondary-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-secondary-600 peer-checked:bg-primary-500"></div>
                                            </div>
                                            <div>
                                                <span class="text-sm font-medium text-secondary-900 dark:text-white">
                                                    {{ trans('common.settings_page.partners_can_register') }}
                                                </span>
                                                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                                                    {{ trans('common.settings_page.partners_can_register_desc') }}
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Info Box --}}
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800/50">
                                <div class="flex gap-3">
                                    <x-ui.icon icon="info" class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                                    <div>
                                        <p class="text-sm text-blue-800 dark:text-blue-200 font-medium">
                                            {{ trans('common.settings_page.partners_can_register_info_title') }}
                                        </p>
                                        <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                            {{ trans('common.settings_page.partners_can_register_info_text') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Anonymous Members Section --}}
                        <div x-data="{ 
                            wasEnabled: {{ $getSetting('members', 'anonymous_members_enabled', false) ? 'true' : 'false' }},
                            anonEnabled: {{ $getSetting('members', 'anonymous_members_enabled', false) ? 'true' : 'false' }},
                            logoutOnDisable: false
                        }">
                            <div class="flex items-center gap-2 mb-4">
                                <x-ui.icon icon="smartphone" class="w-4 h-4 text-secondary-400" />
                                <p class="text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                    {{ trans('common.settings_page.section_anonymous_members') }}
                                </p>
                            </div>
                            
                            {{-- Feature Toggle --}}
                            <div class="p-4 bg-secondary-50 dark:bg-secondary-800/50 rounded-xl border border-secondary-200 dark:border-secondary-700 mb-4">
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                                        <x-ui.icon icon="user-x" class="w-6 h-6 text-white" />
                                    </div>
                                    <div class="flex-1">
                                        <label class="flex items-start gap-3 cursor-pointer">
                                            <div class="relative inline-flex items-center mt-0.5">
                                                <input 
                                                    type="checkbox" 
                                                    name="anonymous_members_enabled" 
                                                    value="1"
                                                    x-on:change="anonEnabled = $el.checked"
                                                    {{ $getSetting('members', 'anonymous_members_enabled', false) ? 'checked' : '' }}
                                                    class="sr-only peer"
                                                >
                                                <div class="w-11 h-6 bg-secondary-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-secondary-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-secondary-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-secondary-600 peer-checked:bg-primary-500"></div>
                                            </div>
                                            <div>
                                                <span class="text-sm font-medium text-secondary-900 dark:text-white">
                                                    {{ trans('common.settings_page.anonymous_members_enabled') }}
                                                </span>
                                                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                                                    {{ trans('common.settings_page.anonymous_members_enabled_desc') }}
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Logout Action Option - only visible when anonymous mode WAS enabled AND is being turned OFF --}}
                            <div 
                                x-show="wasEnabled && !anonEnabled" 
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-2"
                                class="p-4 bg-secondary-50 dark:bg-secondary-800/50 rounded-xl border border-secondary-200 dark:border-secondary-700 mb-4"
                            >
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center">
                                        <x-ui.icon icon="log-out" class="w-6 h-6 text-white" />
                                    </div>
                                    <div class="flex-1">
                                        <label class="flex items-start gap-3 cursor-pointer">
                                            <div class="relative inline-flex items-center mt-0.5">
                                                <input 
                                                    type="checkbox" 
                                                    name="anonymous_logout_on_disable" 
                                                    value="1"
                                                    x-model="logoutOnDisable"
                                                    class="sr-only peer"
                                                >
                                                <div class="w-11 h-6 bg-secondary-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-secondary-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-secondary-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-secondary-600 peer-checked:bg-amber-500"></div>
                                            </div>
                                            <div>
                                                <span class="text-sm font-medium text-secondary-900 dark:text-white">
                                                    {{ trans('common.settings_page.anonymous_logout_on_disable') }}
                                                </span>
                                                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                                                    {{ trans('common.settings_page.anonymous_logout_on_disable_desc') }}
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                {{-- Warning - only shows when checkbox is checked --}}
                                <div 
                                    x-show="logoutOnDisable" 
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    class="mt-3 ml-16 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800/50"
                                >
                                    <div class="flex gap-2">
                                        <x-ui.icon icon="alert-triangle" class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                                        <p class="text-xs text-amber-700 dark:text-amber-300">
                                            {{ trans('common.settings_page.anonymous_logout_warning') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Code Length Selector --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @php
                                    $codeLengthOptions = [
                                        '4' => trans('common.settings_page.code_length_4'),
                                        '5' => trans('common.settings_page.code_length_5'),
                                        '6' => trans('common.settings_page.code_length_6'),
                                        '7' => trans('common.settings_page.code_length_7'),
                                        '8' => trans('common.settings_page.code_length_8'),
                                    ];
                                @endphp
                                <x-forms.select
                                    name="anonymous_member_code_length"
                                    :label="trans('common.settings_page.anonymous_member_code_length')"
                                    :options="$codeLengthOptions"
                                    :value="old('anonymous_member_code_length', (string) $getSetting('members', 'anonymous_member_code_length', 6))"
                                    :text="trans('common.settings_page.anonymous_member_code_length_desc')"
                                />
                            </div>
                            
                            {{-- Info Box --}}
                            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800/50">
                                <div class="flex gap-3">
                                    <x-ui.icon icon="info" class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                                    <div>
                                        <p class="text-sm text-blue-800 dark:text-blue-200 font-medium">
                                            {{ trans('common.settings_page.anonymous_members_info_title') }}
                                        </p>
                                        <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                            {{ trans('common.settings_page.anonymous_members_info_text') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-slot>

            {{-- Tab 4: Compliance --}}
            <x-slot name="tab4">
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                    <div class="px-6 py-5 border-b border-secondary-200 dark:border-secondary-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-green-50 dark:bg-green-900/30 flex items-center justify-center">
                                <x-ui.icon icon="shield-check" class="w-5 h-5 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-secondary-900 dark:text-white">
                                    {{ trans('common.settings_page.category_compliance') }}
                                </h2>
                                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                                    {{ trans('common.settings_page.category_compliance_desc') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div class="space-y-6">
                            {{-- Cookie Consent Card --}}
                            <div class="p-4 bg-secondary-50 dark:bg-secondary-800/50 rounded-xl border border-secondary-200 dark:border-secondary-700">
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                        <x-ui.icon icon="cookie" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                                    </div>
                                    <div class="flex-1">
                                        <x-forms.checkbox
                                            name="cookie_consent"
                                            :label="trans('common.settings_page.cookie_consent')"
                                            :text="trans('common.settings_page.cookie_consent_desc')"
                                            :checked="old('cookie_consent', $getSetting('compliance', 'cookie_consent', false))"
                                        />
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800/50">
                                <div class="flex gap-3">
                                    <x-ui.icon icon="info" class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                                    <div>
                                        <p class="text-sm text-blue-800 dark:text-blue-200 font-medium">
                                            {{ trans('common.settings_page.compliance_info_title') }}
                                        </p>
                                        <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                            {{ trans('common.settings_page.compliance_info_text') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-slot>

            {{-- Tab 5: Email Settings --}}
            <x-slot name="tab5">
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                    <div class="px-6 py-5 border-b border-secondary-200 dark:border-secondary-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center">
                                <x-ui.icon icon="mail" class="w-5 h-5 text-violet-600 dark:text-violet-400" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-secondary-900 dark:text-white">
                                    {{ trans('common.settings_page.category_email') }}
                                </h2>
                                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                                    {{ trans('common.settings_page.category_email_desc') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-forms.input
                                name="mail_from_name"
                                :label="trans('common.settings_page.mail_from_name')"
                                :value="old('mail_from_name', $getSetting('email', 'mail_from_name', config('mail.from.name')))"
                                :text="trans('common.settings_page.mail_from_name_desc')"
                                icon="user"
                                required
                            />
                            
                            <x-forms.input
                                name="mail_from_address"
                                :label="trans('common.settings_page.mail_from_address')"
                                :value="old('mail_from_address', $getSetting('email', 'mail_from_address', config('mail.from.address')))"
                                :text="trans('common.settings_page.mail_from_address_desc')"
                                type="email"
                                icon="at-sign"
                                required
                            />
                        </div>
                        
                        {{-- Email Preview --}}
                        @php
                            $fromName = old('mail_from_name', $getSetting('email', 'mail_from_name', config('mail.from.name', 'Reward Loyalty')));
                            $fromAddress = old('mail_from_address', $getSetting('email', 'mail_from_address', config('mail.from.address', 'noreply@example.com')));
                        @endphp
                        <div class="mt-6 p-4 bg-secondary-50 dark:bg-secondary-800/50 rounded-xl">
                            <p class="text-xs font-semibold text-secondary-500 uppercase tracking-wider mb-3">
                                {{ trans('common.settings_page.email_preview') }}
                            </p>
                            <div class="p-4 bg-white dark:bg-secondary-900 rounded-lg border border-secondary-200 dark:border-secondary-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white font-semibold">
                                        {{ mb_substr($fromName ?? 'R', 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ $fromName }}</p>
                                        <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ $fromAddress }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-slot>

            {{-- Tab 6: Loyalty Cards --}}
            <x-slot name="tab6">
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                    <div class="px-6 py-5 border-b border-secondary-200 dark:border-secondary-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                                <x-ui.icon icon="credit-card" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-secondary-900 dark:text-white">
                                    {{ trans('common.settings_page.category_loyalty') }}
                                </h2>
                                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                                    {{ trans('common.settings_page.category_loyalty_desc') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6 space-y-8">
                        {{-- Member Settings Section --}}
                        <div>
                            <div class="flex items-center gap-2 mb-4">
                                <x-ui.icon icon="users" class="w-4 h-4 text-secondary-400" />
                                <p class="text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                    {{ trans('common.settings_page.section_member_settings') }}
                                </p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <x-forms.input
                                    name="max_member_request_links"
                                    :label="trans('common.settings_page.max_member_request_links')"
                                    :value="old('max_member_request_links', $getSetting('loyalty_cards', 'max_member_request_links', 3))"
                                    :text="trans('common.settings_page.max_member_request_links_desc')"
                                    type="number"
                                    icon="link"
                                    min="0"
                                    required
                                />
                            </div>
                        </div>
                        
                        {{-- Redemption Settings Section --}}
                        <div>
                            <div class="flex items-center gap-2 mb-4">
                                <x-ui.icon icon="gift" class="w-4 h-4 text-secondary-400" />
                                <p class="text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                    {{ trans('common.settings_page.section_redemption_settings') }}
                                </p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <x-forms.select
                                    name="reward_claim_qr_valid_minutes"
                                    :label="trans('common.settings_page.reward_claim_qr_valid_minutes')"
                                    :options="$rewardClaimQrOptions"
                                    :value="old('reward_claim_qr_valid_minutes', (string) $getSetting('loyalty_cards', 'reward_claim_qr_valid_minutes', 15))"
                                    :text="trans('common.settings_page.reward_claim_qr_valid_minutes_desc')"
                                    required
                                />
                                
                                <x-forms.select
                                    name="code_to_redeem_points_valid_minutes"
                                    :label="trans('common.settings_page.code_to_redeem_points_valid_minutes')"
                                    :options="$codeValidMinutesOptions"
                                    :value="old('code_to_redeem_points_valid_minutes', (string) $getSetting('loyalty_cards', 'code_to_redeem_points_valid_minutes', 720))"
                                    :text="trans('common.settings_page.code_to_redeem_points_valid_minutes_desc')"
                                    required
                                />
                            </div>
                        </div>
                        
                        {{-- Staff Settings Section --}}
                        <div>
                            <div class="flex items-center gap-2 mb-4">
                                <x-ui.icon icon="briefcase" class="w-4 h-4 text-secondary-400" />
                                <p class="text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                    {{ trans('common.settings_page.section_staff_settings') }}
                                </p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <x-forms.input
                                    name="staff_transaction_days_ago"
                                    :label="trans('common.settings_page.staff_transaction_days_ago')"
                                    :value="old('staff_transaction_days_ago', $getSetting('loyalty_cards', 'staff_transaction_days_ago', 30))"
                                    :text="trans('common.settings_page.staff_transaction_days_ago_desc')"
                                    type="number"
                                    icon="calendar"
                                    min="0"
                                    required
                                />
                            </div>
                            
                            <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800/50">
                                <div class="flex gap-3">
                                    <x-ui.icon icon="alert-triangle" class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                                    <div>
                                        <p class="text-sm text-amber-800 dark:text-amber-200 font-medium">
                                            {{ trans('common.settings_page.staff_privacy_note_title') }}
                                        </p>
                                        <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                                            {{ trans('common.settings_page.staff_privacy_note_text') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-slot>

            {{-- Tab 7: PWA (Progressive Web App) --}}
            <x-slot name="tab7">
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                    <div class="px-6 py-5 border-b border-secondary-200 dark:border-secondary-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                                <x-ui.icon icon="smartphone" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-secondary-900 dark:text-white">
                                    {{ trans('common.settings_page.category_pwa_full') }}
                                </h2>
                                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                                    {{ trans('common.settings_page.category_pwa_desc') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        {{-- HTTPS Requirement Notice --}}
                        <div class="mb-6 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4">
                            <div class="flex gap-3">
                                <div class="flex-shrink-0">
                                    <x-ui.icon icon="info" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-blue-900 dark:text-blue-100">
                                        {{ trans('common.settings_page.pwa_https_required') }}
                                    </p>
                                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                        {{ trans('common.settings_page.pwa_https_notice') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- General PWA Settings --}}
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <x-forms.input
                                    name="pwa_app_name"
                                    :label="trans('common.settings_page.pwa_app_name')"
                                    :value="old('pwa_app_name', $getSetting('pwa', 'pwa_app_name', config('default.pwa_app_name')))"
                                    :text="trans('common.settings_page.pwa_app_name_desc')"
                                    icon="type"
                                    placeholder="{{ config('default.app_name') }}"
                                />
                                
                                <x-forms.input
                                    name="pwa_short_name"
                                    :label="trans('common.settings_page.pwa_short_name')"
                                    :value="old('pwa_short_name', $getSetting('pwa', 'pwa_short_name', 'Rewards'))"
                                    :text="trans('common.settings_page.pwa_short_name_desc')"
                                    icon="tag"
                                    maxlength="12"
                                    required
                                />
                            </div>

                            <x-forms.textarea
                                name="pwa_description"
                                :label="trans('common.settings_page.pwa_description')"
                                :value="old('pwa_description', $getSetting('pwa', 'pwa_description', 'Your digital loyalty cards'))"
                                :text="trans('common.settings_page.pwa_description_desc')"
                                rows="2"
                                maxlength="132"
                                required
                            />

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <x-forms.input
                                    name="pwa_theme_color"
                                    :label="trans('common.settings_page.pwa_theme_color')"
                                    :value="old('pwa_theme_color', $getSetting('pwa', 'pwa_theme_color', '#F39C12'))"
                                    :text="trans('common.settings_page.pwa_theme_color_desc')"
                                    type="color"
                                    icon="palette"
                                    required
                                />
                                
                                <x-forms.input
                                    name="pwa_background_color"
                                    :label="trans('common.settings_page.pwa_background_color')"
                                    :value="old('pwa_background_color', $getSetting('pwa', 'pwa_background_color', '#ffffff'))"
                                    :text="trans('common.settings_page.pwa_background_color_desc')"
                                    type="color"
                                    icon="droplet"
                                    required
                                />
                            </div>

                            {{-- PWA Icons Section --}}
                            <div class="pt-6 border-t border-secondary-200 dark:border-secondary-700">
                                <h3 class="text-base font-semibold text-secondary-900 dark:text-white mb-1">
                                    {{ trans('common.settings_page.pwa_app_icons') }}
                                </h3>
                                <p class="text-sm text-secondary-500 dark:text-secondary-400 mb-6">
                                    {{ trans('common.settings_page.pwa_app_icons_desc') }}
                                </p>

                                @php
                                    $pwaSettingRecord = \App\Models\Setting::where('key', 'pwa_app_name')->first();
                                    $icon192 = $pwaSettingRecord?->getFirstMediaUrl('pwa_icon_192');
                                    $icon512 = $pwaSettingRecord?->getFirstMediaUrl('pwa_icon_512');
                                @endphp

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <x-forms.image
                                            name="pwa_icon_192"
                                            :label="trans('common.settings_page.pwa_icon_192')"
                                            :text="trans('common.settings_page.pwa_icon_192_desc')"
                                            :value="$icon192 ?: asset('icons/pwa-192.png')"
                                            :default="asset('icons/pwa-192.png')"
                                            accept="image/png,image/jpeg,image/webp"
                                            icon="image"
                                        />
                                    </div>

                                    <div>
                                        <x-forms.image
                                            name="pwa_icon_512"
                                            :label="trans('common.settings_page.pwa_icon_512')"
                                            :text="trans('common.settings_page.pwa_icon_512_desc')"
                                            :value="$icon512 ?: asset('icons/pwa-512.png')"
                                            :default="asset('icons/pwa-512.png')"
                                            accept="image/png,image/jpeg,image/webp"
                                            icon="image"
                                        />
                                    </div>
                                </div>

                                {{-- Icon Guidelines --}}
                                <div class="mt-4 rounded-xl bg-secondary-50 dark:bg-secondary-800/50 p-4">
                                    <p class="text-sm font-medium text-secondary-900 dark:text-white mb-2">
                                        {{ trans('common.settings_page.pwa_icon_guidelines') }}
                                    </p>
                                    <ul class="text-sm text-secondary-600 dark:text-secondary-400 space-y-1 list-disc list-inside">
                                        <li>{{ trans('common.settings_page.pwa_icon_guideline_1') }}</li>
                                        <li>{{ trans('common.settings_page.pwa_icon_guideline_2') }}</li>
                                        <li>{{ trans('common.settings_page.pwa_icon_guideline_3') }}</li>
                                        <li>{{ trans('common.settings_page.pwa_icon_guideline_4') }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-slot>
        </x-ui.tabs>

        {{-- Save Button --}}
        <div class="mt-8 flex justify-end">
            <x-forms.button 
                type="submit" 
                :label="trans('common.save_changes')"
                class="w-auto"
            />
        </div>
    <x-forms.form-close />
</div>
@stop
