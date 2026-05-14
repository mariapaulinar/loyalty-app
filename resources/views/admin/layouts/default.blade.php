{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Admin Default Layout
Base layout for admin pages including authentication and dashboard
--}}
@php
    $languages = $languages ?? ['all' => [], 'current' => []];
    $routeName = request()->route() ? request()->route()->getName() : null;
    $routeDataDefinition = $dataDefinition->name ?? null;
    $isAuthPage = $authPage ?? false;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ trans('config.dir') }}" class="h-full">
<head>
    <meta charset="utf-8">
    <script>
        // Prevent flash
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>@yield('page_title')</title>
    <script src="{{ route('javascript.include.language') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/core.js', 'resources/js/admin.js'])
    <meta name="robots" content="{{ isset($robots) && $robots === false ? 'noindex, nofollow' : 'noindex, nofollow' }}" />
    <x-meta.generic />
    <x-meta.favicons />
    <x-ui.brand-styles />
</head>
<body
    class="antialiased bg-secondary-50 dark:bg-secondary-950 text-secondary-900 dark:text-secondary-50 h-full selection:bg-primary-500 selection:text-white"
    x-data="{ mobileMenuOpen: false, showLanguageModal: false }">
    
    @if($isAuthPage)
        <!-- Auth Page (No Sidebar) -->
        @yield('content')
    @else
        @auth('admin')
            <!-- Desktop Sidebar -->
            <aside
                class="fixed inset-y-0 left-0 z-50 hidden w-[280px] bg-white dark:bg-secondary-900 border-r border-secondary-200 dark:border-secondary-800 md:flex flex-col transition-all duration-300">
                <!-- Logo -->
                <div class="flex items-center justify-center h-16 border-b border-secondary-200 dark:border-secondary-800">
                    <a href="{{ route('admin.index') }}" class="flex items-center gap-3">
                        <x-ui.app-logo class="h-8 w-auto" />
                    </a>
                </div>
                
                <!-- Nav -->
                <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1.5">
                    {{-- Dashboard --}}
                    <x-ui.nav-link :href="route('admin.index')" :active="$routeName == 'admin.index'" icon="home">
                        {{ trans('common.dashboard') }}
                    </x-ui.nav-link>
                    
                    {{-- Divider --}}
                    <div class="py-2">
                        <div class="h-px bg-secondary-200 dark:bg-secondary-800"></div>
                    </div>
                    
                    {{-- Non-admin: Account Settings --}}
                    @if(auth('admin')->user()->role != 1)
                        <x-ui.nav-link :href="route('admin.data.list', ['name' => 'account'])" :active="$routeDataDefinition == 'account'" icon="user-circle">
                            {{ trans('common.account_settings') }}
                        </x-ui.nav-link>
                    @endif
                    
                    {{-- Partners --}}
                    <x-ui.nav-link :href="route('admin.data.list', ['name' => 'partners'])"
                        :active="$routeDataDefinition == 'partners'" icon="store">
                        {{ trans('common.partners') }}
                    </x-ui.nav-link>
                    
                    @if(auth('admin')->user()->role == 1)
                        {{-- Networks --}}
                        <x-ui.nav-link :href="route('admin.data.list', ['name' => 'networks'])"
                            :active="$routeDataDefinition == 'networks'" icon="network">
                            {{ trans('common.networks') }}
                        </x-ui.nav-link>
                        
                        {{-- Administrators --}}
                        <x-ui.nav-link :href="route('admin.data.list', ['name' => 'admins'])"
                            :active="$routeDataDefinition == 'admins'" icon="shield">
                            {{ trans('common.administrators') }}
                        </x-ui.nav-link>
                        
                        {{-- Divider --}}
                        <div class="py-2">
                            <div class="h-px bg-secondary-200 dark:bg-secondary-800"></div>
                        </div>
                        
                        {{-- Members --}}
                        <x-ui.nav-link :href="route('admin.data.list', ['name' => 'members'])"
                            :active="$routeDataDefinition == 'members'" icon="users">
                            {{ trans('common.members') }}
                        </x-ui.nav-link>
                        
                        {{-- Transaction History --}}
                        <x-ui.nav-link :href="route('admin.data.list', ['name' => 'transactions'])"
                            :active="$routeDataDefinition == 'transactions'" icon="scroll-text">
                            {{ trans('common.transactions') }}
                        </x-ui.nav-link>
                        
                        {{-- Stamp Transaction History --}}
                        <x-ui.nav-link :href="route('admin.data.list', ['name' => 'stamp-transactions'])"
                            :active="$routeDataDefinition == 'stamp-transactions'" icon="stamp">
                            {{ trans('common.stamp_transactions') }}
                        </x-ui.nav-link>
                        
                        {{-- Voucher Redemption History --}}
                        <x-ui.nav-link :href="route('admin.data.list', ['name' => 'voucher-redemptions'])"
                            :active="$routeDataDefinition == 'voucher-redemptions'" icon="scroll-text">
                            {{ trans('common.voucher_redemption_history') }}
                        </x-ui.nav-link>
                        
                        {{-- Divider --}}
                        <div class="py-2">
                            <div class="h-px bg-secondary-200 dark:bg-secondary-800"></div>
                        </div>
                        
                        {{-- Analytics --}}
                        <x-ui.nav-link :href="route('admin.activity-logs.analytics')"
                            :active="$routeName == 'admin.activity-logs.analytics'" icon="bar-chart-2">
                            {{ trans('common.analytics') }}
                        </x-ui.nav-link>
                        
                        {{-- SaaS Overview --}}
                        <x-ui.nav-link :href="route('admin.saas.index')"
                            :active="Str::startsWith($routeName, 'admin.saas')" icon="line-chart">
                            {{ trans('common.saas_overview') }}
                        </x-ui.nav-link>
                        
                        {{-- Activity Logs --}}
                        <x-ui.nav-link :href="route('admin.data.list', ['name' => 'activity-logs'])"
                            :active="$routeDataDefinition == 'activity-logs'" icon="activity">
                            {{ trans('common.activity_logs') }}
                        </x-ui.nav-link>
                        
                        {{-- Agent Keys --}}
                        @if(config('default.feature_agent_api'))
                        <x-ui.nav-link :href="route('admin.data.list', ['name' => 'agent-keys'])"
                            :active="$routeDataDefinition == 'agent-keys'" icon="bot">
                            {{ trans('agent.agent_keys') }}
                        </x-ui.nav-link>
                        @endif
                        
                        {{-- Divider --}}
                        <div class="py-2">
                            <div class="h-px bg-secondary-200 dark:bg-secondary-800"></div>
                        </div>
                        
                        {{-- License & Updates --}}
                        <x-ui.nav-link :href="route('admin.license.index')"
                            :active="Str::startsWith($routeName, 'admin.license')" icon="shield-check">
                            {{ trans('common.license.title') }}
                        </x-ui.nav-link>
                        
                        {{-- Health Center --}}
                        <x-ui.nav-link :href="route('admin.health.index')"
                            :active="Str::startsWith($routeName, 'admin.health')" icon="heart-pulse">
                            {{ trans('common.health.title') }}
                        </x-ui.nav-link>
                        
                        {{-- Settings --}}
                        <x-ui.nav-link :href="route('admin.settings.index')"
                            :active="Str::startsWith($routeName, 'admin.settings')" icon="cog">
                            {{ trans('common.settings') }}
                        </x-ui.nav-link>
                    @endif
                </nav>
                
                <!-- User Profile -->
                <div class="p-3 border-t border-secondary-200 dark:border-secondary-800 relative"
                    x-data="{ userMenuOpen: false }">
                    <button @click="userMenuOpen = !userMenuOpen" @click.away="userMenuOpen = false"
                        class="cursor-pointer w-full flex items-center gap-3 p-2.5 rounded-xl hover:bg-secondary-50 dark:hover:bg-secondary-800/50 transition-colors duration-200 group text-left">
                        @if(auth('admin')->user()->avatar ?? false)
                            <img class="w-9 h-9 rounded-full object-cover ring-2 ring-white dark:ring-secondary-800"
                                src="{{ auth('admin')->user()->avatar }}">
                        @else
                            <div class="w-9 h-9 rounded-full bg-secondary-200 dark:bg-secondary-700 flex items-center justify-center text-secondary-500 dark:text-secondary-400">
                                <x-ui.icon icon="user" class="w-4 h-4" />
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-secondary-900 dark:text-white truncate">
                                {{ auth('admin')->user()->name ?? trans('common.admin') }}
                            </p>
                            <p class="text-xs text-secondary-500 dark:text-secondary-400 truncate">
                                {{ auth('admin')->user()->email }}
                            </p>
                        </div>
                        <x-ui.icon icon="ellipsis"
                            class="w-4 h-4 text-secondary-400 transition-opacity duration-200"
                            ::class="{ 'opacity-70': userMenuOpen }" />
                    </button>
                    <!-- User Dropdown -->
                    <div x-show="userMenuOpen" x-cloak x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2"
                        class="absolute bottom-full left-3 right-3 mb-2 bg-white dark:bg-secondary-800 rounded-xl shadow-lg border border-secondary-200 dark:border-secondary-700 overflow-visible z-50"
                        @click.away="userMenuOpen = false">
                        <div class="py-1 overflow-visible">
                            {{-- Account Settings --}}
                            <a href="{{ route('admin.data.list', ['name' => 'account']) }}"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors duration-200 cursor-pointer">
                                <x-ui.icon icon="user-circle" class="w-4 h-4" />
                                {{ trans('common.account_settings') }}
                            </a>
                            
                            <div class="border-t border-secondary-100 dark:border-secondary-700 my-1"></div>
                            
                            {{-- Language Selector (Opens Modal) --}}
                            @if (count($languages['all'] ?? []) > 1)
                                <button @click.stop="showLanguageModal = true; userMenuOpen = false"
                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors duration-200 cursor-pointer">
                                    <div class="fi-{{ strtolower($languages['current']['countryCode']) }} fis w-4 h-4 rounded-full"></div>
                                    <span class="flex-1 text-left">{{ $languages['current']['languageName'] }}</span>
                                    <x-ui.icon icon="chevron-right" class="w-4 h-4 text-secondary-400" />
                                </button>
                            @endif
                            
                            {{-- Theme Toggle --}}
                            <button type="button" @click.stop="toggleTheme()"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors duration-200 cursor-pointer">
                                <x-ui.icon icon="sun" class="hidden w-4 h-4 dark:block" />
                                <x-ui.icon icon="moon" class="w-4 h-4 dark:hidden" />
                                <span class="flex-1 text-left dark:hidden">{{ trans('common.dark_mode') }}</span>
                                <span class="flex-1 text-left hidden dark:block">{{ trans('common.light_mode') }}</span>
                            </button>
                            
                            <div class="border-t border-secondary-100 dark:border-secondary-700 my-1"></div>
                            
                            {{-- Logout --}}
                            <a href="{{ route('admin.logout') }}"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors duration-200 cursor-pointer">
                                <x-ui.icon icon="log-out" class="w-4 h-4" />
                                {{ trans('common.logout') }}
                            </a>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Mobile Header -->
            <header
                class="md:hidden fixed top-0 left-0 right-0 z-40 bg-white dark:bg-secondary-900 border-b border-secondary-200 dark:border-secondary-800 h-14 flex items-center justify-between px-4"
                x-data="{ mobileProfileOpen: false }">
                <div class="flex items-center gap-3">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button"
                        class="text-secondary-500 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-800 rounded-lg p-2 transition-colors duration-200">
                        <x-ui.icon icon="menu" class="w-5 h-5" x-show="!mobileMenuOpen" />
                        <x-ui.icon icon="x" class="w-5 h-5" x-show="mobileMenuOpen" x-cloak />
                    </button>
                    <a href="{{ route('admin.index') }}" class="flex items-center gap-2">
                        <x-ui.app-logo class="h-7 w-auto" />
                    </a>
                </div>
                {{-- Profile Menu Button --}}
                <div class="relative">
                    <button @click="mobileProfileOpen = !mobileProfileOpen" 
                        class="cursor-pointer flex items-center gap-2 p-1 rounded-full hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors duration-200">
                        @if(auth('admin')->user()->avatar ?? false)
                            <img class="w-8 h-8 rounded-full object-cover ring-2 ring-white dark:ring-secondary-800"
                                src="{{ auth('admin')->user()->avatar }}">
                        @else
                            <div class="w-8 h-8 rounded-full bg-secondary-200 dark:bg-secondary-700 flex items-center justify-center text-secondary-500 dark:text-secondary-400">
                                <x-ui.icon icon="user" class="w-4 h-4" />
                            </div>
                        @endif
                        <x-ui.icon icon="chevron-down" class="w-4 h-4 text-secondary-400 transition-transform" ::class="{ 'rotate-180': mobileProfileOpen }" />
                    </button>
                    {{-- Mobile Profile Dropdown --}}
                    <div x-show="mobileProfileOpen" x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        @click.away="mobileProfileOpen = false"
                        class="absolute right-0 top-full mt-2 w-60 bg-white dark:bg-secondary-800 rounded-xl shadow-lg border border-secondary-200 dark:border-secondary-700 overflow-visible z-50">
                        
                        {{-- User Info Header --}}
                        <div class="px-4 py-3 border-b border-secondary-200 dark:border-secondary-700">
                            <p class="text-sm font-medium text-secondary-900 dark:text-white truncate">
                                {{ auth('admin')->user()->name ?? trans('common.admin') }}
                            </p>
                            <p class="text-xs text-secondary-500 dark:text-secondary-400 truncate">
                                {{ auth('admin')->user()->email }}
                            </p>
                        </div>
                        
                        <div class="py-1">
                            {{-- Account Settings --}}
                            <a href="{{ route('admin.data.list', ['name' => 'account']) }}"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors duration-200 cursor-pointer">
                                <x-ui.icon icon="user-circle" class="w-4 h-4" />
                                {{ trans('common.account_settings') }}
                            </a>
                            
                            <div class="border-t border-secondary-200 dark:border-secondary-700 my-1"></div>
                            
                            {{-- Language Selector (Opens Modal) --}}
                            @if (count($languages['all'] ?? []) > 1)
                                <button @click.stop="showLanguageModal = true; mobileProfileOpen = false"
                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors duration-200 cursor-pointer">
                                    <div class="fi-{{ strtolower($languages['current']['countryCode']) }} fis w-4 h-4 rounded-full"></div>
                                    <span class="flex-1 text-left">{{ $languages['current']['languageName'] }}</span>
                                    <x-ui.icon icon="chevron-right" class="w-4 h-4 text-secondary-400" />
                                </button>
                            @endif
                            
                            {{-- Theme Toggle --}}
                            <button type="button" @click.stop="toggleTheme()"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors duration-200 cursor-pointer">
                                <x-ui.icon icon="sun" class="hidden w-4 h-4 dark:block" />
                                <x-ui.icon icon="moon" class="w-4 h-4 dark:hidden" />
                                <span class="flex-1 text-left dark:hidden">{{ trans('common.dark_mode') }}</span>
                                <span class="flex-1 text-left hidden dark:block">{{ trans('common.light_mode') }}</span>
                            </button>
                            
                            <div class="border-t border-secondary-200 dark:border-secondary-700 my-1"></div>
                            
                            {{-- Logout --}}
                            <a href="{{ route('admin.logout') }}"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors duration-200 cursor-pointer">
                                <x-ui.icon icon="log-out" class="w-4 h-4" />
                                {{ trans('common.logout') }}
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Mobile Menu -->
            <div x-show="mobileMenuOpen" x-cloak @click.away="mobileMenuOpen = false" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="md:hidden fixed inset-0 z-30 bg-secondary-900/50 backdrop-blur-sm pt-14">
                <div x-show="mobileMenuOpen" x-cloak @click.stop x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="h-full w-full bg-white dark:bg-secondary-900 shadow-xl overflow-y-auto">
                    <!-- Mobile Nav -->
                    <nav class="p-4 space-y-1">
                        {{-- Dashboard --}}
                        <x-ui.nav-link :href="route('admin.index')" :active="$routeName == 'admin.index'" icon="home">
                            {{ trans('common.dashboard') }}
                        </x-ui.nav-link>
                        
                        {{-- Divider --}}
                        <div class="py-2">
                            <div class="h-px bg-secondary-200 dark:bg-secondary-800"></div>
                        </div>
                        
                        {{-- Non-admin: Account Settings --}}
                        @if(auth('admin')->user()->role != 1)
                            <x-ui.nav-link :href="route('admin.data.list', ['name' => 'account'])" :active="$routeDataDefinition == 'account'" icon="user-circle">
                                {{ trans('common.account_settings') }}
                            </x-ui.nav-link>
                        @endif
                        
                        {{-- Partners --}}
                        <x-ui.nav-link :href="route('admin.data.list', ['name' => 'partners'])"
                            :active="$routeDataDefinition == 'partners'" icon="store">
                            {{ trans('common.partners') }}
                        </x-ui.nav-link>
                        
                        @if(auth('admin')->user()->role == 1)
                            {{-- Networks --}}
                            <x-ui.nav-link :href="route('admin.data.list', ['name' => 'networks'])"
                                :active="$routeDataDefinition == 'networks'" icon="network">
                                {{ trans('common.networks') }}
                            </x-ui.nav-link>
                            
                            {{-- Administrators --}}
                            <x-ui.nav-link :href="route('admin.data.list', ['name' => 'admins'])"
                                :active="$routeDataDefinition == 'admins'" icon="shield">
                                {{ trans('common.administrators') }}
                            </x-ui.nav-link>
                            
                            {{-- Divider --}}
                            <div class="py-2">
                                <div class="h-px bg-secondary-200 dark:bg-secondary-800"></div>
                            </div>
                            
                            {{-- Members --}}
                            <x-ui.nav-link :href="route('admin.data.list', ['name' => 'members'])"
                                :active="$routeDataDefinition == 'members'" icon="users">
                                {{ trans('common.members') }}
                            </x-ui.nav-link>
                            
                            {{-- Transaction History --}}
                            <x-ui.nav-link :href="route('admin.data.list', ['name' => 'transactions'])"
                                :active="$routeDataDefinition == 'transactions'" icon="scroll-text">
                                {{ trans('common.transactions') }}
                            </x-ui.nav-link>
                            
                            {{-- Stamp Transaction History --}}
                            <x-ui.nav-link :href="route('admin.data.list', ['name' => 'stamp-transactions'])"
                                :active="$routeDataDefinition == 'stamp-transactions'" icon="stamp">
                                {{ trans('common.stamp_transactions') }}
                            </x-ui.nav-link>
                            
                            {{-- Voucher Redemption History --}}
                            <x-ui.nav-link :href="route('admin.data.list', ['name' => 'voucher-redemptions'])"
                                :active="$routeDataDefinition == 'voucher-redemptions'" icon="scroll-text">
                                {{ trans('common.voucher_redemption_history') }}
                            </x-ui.nav-link>
                            
                            {{-- Divider --}}
                            <div class="py-2">
                                <div class="h-px bg-secondary-200 dark:bg-secondary-800"></div>
                            </div>
                            
                            {{-- Analytics --}}
                            <x-ui.nav-link :href="route('admin.activity-logs.analytics')"
                                :active="$routeName == 'admin.activity-logs.analytics'" icon="bar-chart-2">
                                {{ trans('common.analytics') }}
                            </x-ui.nav-link>
                            
                            {{-- SaaS Overview --}}
                            <x-ui.nav-link :href="route('admin.saas.index')"
                                :active="Str::startsWith($routeName, 'admin.saas')" icon="line-chart">
                                {{ trans('common.saas_overview') }}
                            </x-ui.nav-link>
                            
                            {{-- Activity Logs --}}
                            <x-ui.nav-link :href="route('admin.data.list', ['name' => 'activity-logs'])"
                                :active="$routeDataDefinition == 'activity-logs'" icon="activity">
                                {{ trans('common.activity_logs') }}
                            </x-ui.nav-link>
                            
                            {{-- Agent Keys --}}
                            @if(config('default.feature_agent_api'))
                            <x-ui.nav-link :href="route('admin.data.list', ['name' => 'agent-keys'])"
                                :active="$routeDataDefinition == 'agent-keys'" icon="bot">
                                {{ trans('agent.agent_keys') }}
                            </x-ui.nav-link>
                            @endif
                            
                            {{-- Divider --}}
                            <div class="py-2">
                                <div class="h-px bg-secondary-200 dark:bg-secondary-800"></div>
                            </div>
                            
                            {{-- License & Updates --}}
                            <x-ui.nav-link :href="route('admin.license.index')"
                                :active="Str::startsWith($routeName, 'admin.license')" icon="shield-check">
                                {{ trans('common.license.title') }}
                            </x-ui.nav-link>
                            
                            {{-- Health Center --}}
                            <x-ui.nav-link :href="route('admin.health.index')"
                                :active="Str::startsWith($routeName, 'admin.health')" icon="heart-pulse">
                                {{ trans('common.health.title') }}
                            </x-ui.nav-link>
                            
                            {{-- Settings --}}
                            <x-ui.nav-link :href="route('admin.settings.index')"
                                :active="Str::startsWith($routeName, 'admin.settings')" icon="cog">
                                {{ trans('common.settings') }}
                            </x-ui.nav-link>
                        @endif
                    </nav>
                </div>
            </div>

            <!-- Main Content with Sidebar -->
            <main class="md:pl-[280px] pt-14 md:pt-0">
                @yield('content')
            </main>
        @else
            <!-- No Sidebar (Guest) -->
            @yield('content')
            {{-- Theme toggle on auth pages (discrete, bottom-right) --}}
            <x-ui.theme-toggle-fixed />
        @endauth
    @endif

    {{-- Language Selection Modal --}}
    @auth('admin')
        <x-ui.language-modal :languages="$languages ?? []" route-key="adminIndex" />
    @endauth

    {{-- Agent Key One-Time Display Modal --}}
    @include('components.agent-key-modal')

    {{-- Toast Notifications --}}
    <x-ui.toast />

</body>
</html>
