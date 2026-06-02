@php
    $languages = $languages ?? ['all' => [], 'current' => []];
    $routeName = request()->route() ? request()->route()->getName() : null;
    $routeDataDefinition = $dataDefinition->name ?? null;
    $partnerRegistrationEnabled = \App\Services\Partner\AuthService::isRegistrationEnabled();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ trans('config.dir') }}" class="h-full overflow-x-hidden">

<head>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-PFNX92NT');</script>
    <!-- End Google Tag Manager -->

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
    
    {{-- SEO: Title + Open Graph + Twitter Cards --}}
    <x-seo />
    
    <script src="{{ route('javascript.include.language') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/core.js', 'resources/js/member.js'])
    <meta name="robots" content="{{ isset($robots) && $robots === false ? 'noindex, nofollow' : 'index, follow' }}" />
    <x-meta.generic />
    <x-meta.favicons />
    
    {{-- PWA Support --}}
    <x-pwa-head />
    
    {{-- Dynamic Brand Colors --}}
    <x-ui.brand-styles />
</head>

<body
    class="antialiased bg-secondary-50 dark:bg-secondary-950 text-secondary-900 dark:text-secondary-50 flex flex-col min-h-full selection:bg-primary-500 selection:text-white overflow-x-hidden"
    x-data="{ mobileMenuOpen: false, mobileProfileOpen: false, showLanguageModal: false }"
    @if(!request()->cookie('member_time_zone'))style="visibility: hidden;"@endif>

    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PFNX92NT"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->


    {{-- Skip to Content (M-022 — WCAG 2.4.1) --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[100] focus:px-4 focus:py-2 focus:bg-primary-600 focus:text-white focus:rounded-lg focus:shadow-lg focus:text-sm focus:font-semibold">{{ trans('common.skip_to_content') }}</a>

    {{-- First Visit Loading Screen (Server-side) --}}
    {{-- Shows immediately when timezone cookie is missing, preventing flash of page content --}}
    @if(!request()->cookie('member_time_zone'))
        <div id="first-visit-loader" style="
            position: fixed;
            inset: 0;
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            visibility: visible;
        ">
            <style>
                /* Respect system dark/light preference */
                #first-visit-loader { background: #ffffff; }
                #first-visit-loader .fvl-spinner { border-top-color: #0a0a0a; }
                @media (prefers-color-scheme: dark) {
                    #first-visit-loader { background: #0a0a0a; }
                    #first-visit-loader .fvl-spinner { border-top-color: #ffffff; }
                }
                /* Also respect localStorage theme if set */
                html.dark #first-visit-loader { background: #0a0a0a; }
                html.dark #first-visit-loader .fvl-spinner { border-top-color: #ffffff; }
                html:not(.dark) #first-visit-loader { background: #ffffff; }
                html:not(.dark) #first-visit-loader .fvl-spinner { border-top-color: #0a0a0a; }
                
                .fvl-spinner {
                    width: 32px;
                    height: 32px;
                    border: 3px solid transparent;
                    border-radius: 50%;
                    animation: fvl-spin 0.8s linear infinite;
                }
                @keyframes fvl-spin {
                    to { transform: rotate(360deg); }
                }
            </style>
            <div class="fvl-spinner"></div>
        </div>
    @endif

    {{-- Guest Header - Public Home Style --}}
    @guest('member')
        @if(!($authPage ?? false))
        <header
            class="fixed top-0 left-0 right-0 z-50 bg-white/70 dark:bg-secondary-950/70 backdrop-blur-2xl border-b border-secondary-200/50 dark:border-secondary-800/50 transition-all duration-500"
            x-data="{ scrolled: false, mobileMenuOpen: false }"
            x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 20 })"
            :class="{ 'shadow-lg shadow-secondary-900/5 dark:shadow-black/20': scrolled }">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16 lg:h-20">
                    {{-- Logo --}}
                    <a href="{{ route('member.index') }}" class="flex items-center gap-3 group">
                        <x-ui.app-logo class="h-8 lg:h-10 w-auto transition-transform duration-300 group-hover:scale-105" />
                    </a>

                    <nav class="hidden md:flex items-center gap-4">
                        @if($partnerRegistrationEnabled)
                            {{-- Business nav link (quiet text, not a button — different audience) --}}
                            <a href="{{ route('partner.register') }}"
                                class="text-sm font-medium text-secondary-500 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white transition-colors duration-200">
                                {{ trans('common.for_businesses') }}
                            </a>
                        @endif

                        {{-- Sign In Button (Ghost pill, matches logged-in nav style) --}}
                        <a href="{{ route('member.login') }}"
                            class="px-4 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:text-secondary-900 dark:hover:text-white rounded-full border border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 hover:bg-secondary-50 dark:hover:bg-secondary-800/50 transition-all duration-200">
                            {{ trans('common.sign_in') }}
                        </a>

                        {{-- Get Started Button (Primary Accent/Orange pill) --}}
                        <x-ui.button href="{{ route('member.register') }}" variant="accent" size="md" class="!rounded-full">
                            {{ trans('common.get_started_free') }}
                        </x-ui.button>
                    </nav>

                    {{-- Mobile Menu Button --}}
                    <div class="flex items-center gap-2 md:hidden">
                        <button @click="mobileMenuOpen = !mobileMenuOpen"
                            class="flex items-center justify-center w-9 h-9 rounded-full border border-secondary-200 dark:border-secondary-700 text-secondary-500 hover:text-secondary-900 dark:hover:text-white hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-all cursor-pointer">
                            <x-ui.icon x-show="!mobileMenuOpen" icon="menu" class="w-5 h-5" />
                            <x-ui.icon x-show="mobileMenuOpen" icon="x" class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                {{-- Mobile Menu Overlay - Full Screen for Premium Feel --}}
                <div x-show="mobileMenuOpen" x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-4"
                    class="md:hidden border-t border-secondary-200/50 dark:border-secondary-700/50 py-6 space-y-6">
                    
                    {{-- Primary Actions (Sign In, Get Started) --}}
                    <div class="flex flex-col gap-3 px-4">
                        @if($partnerRegistrationEnabled)
                            {{-- Business nav link (mobile) --}}
                            <a href="{{ route('partner.register') }}"
                                class="w-full px-5 py-4 text-center text-base font-medium text-secondary-500 dark:text-secondary-400 bg-secondary-50 dark:bg-secondary-800/30 rounded-2xl hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-all active:scale-[0.98]">
                                {{ trans('common.for_businesses') }}
                            </a>
                        @endif
                        <a href="{{ route('member.login') }}"
                            class="w-full px-5 py-4 text-center text-base font-semibold text-secondary-700 dark:text-secondary-300 bg-secondary-100 dark:bg-secondary-800 rounded-2xl hover:bg-secondary-200 dark:hover:bg-secondary-700 transition-all active:scale-[0.98]">
                            {{ trans('common.sign_in') }}
                        </a>
                        <x-ui.button href="{{ route('member.register') }}" variant="accent" size="lg" class="w-full justify-center py-4 text-base">
                            {{ trans('common.get_started_free') }}
                        </x-ui.button>
                    </div>

                    {{-- Language Selector (Prominently in Mobile Menu) --}}
                    @if (count($languages['all'] ?? []) > 1)
                        <div class="px-4">
                            <p class="text-xs font-semibold text-secondary-400 dark:text-secondary-500 uppercase tracking-wider mb-3 px-1">
                                {{ trans('common.language') }}
                            </p>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($languages['all'] as $language)
                                    @php 
                                        $currentLocale = $languages['current']['locale'] ?? '';
                                        $langLocale = $language['locale'] ?? '';
                                        $isActive = $currentLocale !== '' && $langLocale !== '' && $currentLocale === $langLocale;
                                    @endphp
                                    <a href="{{ $language['memberIndex'] ?? '#' }}"
                                        class="flex items-center gap-2.5 px-4 py-3 rounded-xl text-sm font-medium transition-all active:scale-[0.98] {{ $isActive ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 ring-2 ring-primary-500/20' : 'text-secondary-600 dark:text-secondary-400 bg-secondary-50 dark:bg-secondary-800/50 hover:bg-secondary-100 dark:hover:bg-secondary-800' }}">
                                        <div class="w-5 h-5 rounded-full fis fi-{{ strtolower($language['countryCode'] ?? 'us') }} shadow-sm"></div>
                                        <span>{{ $language['languageName'] ?? 'Unknown' }}</span>
                                        @if($isActive)
                                            <x-ui.icon icon="check" class="w-4 h-4 text-primary-500 ml-auto" />
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </header>
        @endif
    @endguest

    {{-- ═══════════════════════════════════════════════════════════════════════
        AUTHENTICATED MEMBER HEADER - WALLET STYLE
        
        Clean, modern header matching the public style but with:
        - Home | My Cards navigation
        - Profile dropdown with clear actions
        - No sidebar clutter
    ═══════════════════════════════════════════════════════════════════════ --}}
    @auth('member')
        <header
            class="fixed top-0 left-0 right-0 z-50 bg-white/70 dark:bg-secondary-950/70 backdrop-blur-2xl border-b border-secondary-200/50 dark:border-secondary-800/50 transition-all duration-500"
            x-data="{ scrolled: false }"
            x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 20 })"
            :class="{ 'shadow-lg shadow-secondary-900/5 dark:shadow-black/20': scrolled }">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16 lg:h-18">
                    {{-- Logo --}}
                    <a href="{{ route('member.index') }}" class="flex items-center gap-3 group">
                        <x-ui.app-logo class="h-7 lg:h-9 w-auto transition-transform duration-300 group-hover:scale-105" />
                    </a>

                    {{-- Right Side Actions --}}
                    <div class="flex items-center gap-1 md:gap-2">
                        {{-- My Cards — circle icon on mobile, pill with text on desktop --}}
                        <a href="{{ route('member.cards') }}"
                            class="md:hidden inline-flex items-center justify-center w-9 h-9 rounded-full border transition-all duration-200 
                                {{ request()->routeIs('member.cards') ? 'text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 border-primary-200 dark:border-primary-800' : 'text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 hover:bg-secondary-50 dark:hover:bg-secondary-800/50' }}">
                            <x-ui.icon icon="wallet" class="w-4.5 h-4.5" />
                        </a>
                        <a href="{{ route('member.cards') }}"
                            class="hidden md:inline-flex items-center gap-1.5 h-9 px-3.5 text-sm font-medium rounded-full border transition-all duration-200 
                                {{ request()->routeIs('member.cards') ? 'text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 border-primary-200 dark:border-primary-800' : 'text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 hover:bg-secondary-50 dark:hover:bg-secondary-800/50' }}">
                            <x-ui.icon icon="wallet" class="w-4 h-4" />
                            {{ trans('common.my_cards') }}
                        </a>

                        {{-- Profile Dropdown --}}
                        <div class="relative" x-data="{ profileOpen: false }">
                            <button @click="profileOpen = !profileOpen" 
                                @click.outside="profileOpen = false"
                                class="flex items-center gap-2 px-1.5 py-1 rounded-full border border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 hover:bg-secondary-50 dark:hover:bg-secondary-800/50 transition-all duration-200 cursor-pointer group">
                                @if(auth('member')->user()->avatar ?? false)
                                    <img class="w-7 h-7 rounded-full object-cover"
                                        src="{{ auth('member')->user()->avatar }}">
                                @else
                                    {{-- Platform Primary Blue Avatar (Solid, not gradient) --}}
                                    <div class="w-7 h-7 rounded-full bg-primary-600 flex items-center justify-center text-white text-xs font-semibold">
                                        {{ strtoupper(substr(auth('member')->user()->name, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="hidden md:block text-sm font-medium text-secondary-700 dark:text-secondary-300 pr-0.5">
                                    {{ auth('member')->user()->name }}
                                </span>
                                <x-ui.icon icon="chevron-down" class="w-3.5 h-3.5 text-secondary-400 mr-1 transition-transform duration-200" ::class="{ 'rotate-180': profileOpen }" />
                            </button>

                            {{-- Dropdown Menu --}}
                            <div x-show="profileOpen" x-cloak
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                                class="absolute right-0 top-full mt-2 w-64 bg-white dark:bg-secondary-800 rounded-xl shadow-2xl border border-secondary-100 dark:border-secondary-700 overflow-visible z-50 backdrop-blur-xl">
                                
                                {{-- User Info Header --}}
                                <div class="px-4 py-3 border-b border-secondary-100 dark:border-secondary-700">
                                    <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">{{ auth('member')->user()->name }}</p>
                                    <p class="text-xs text-secondary-500 dark:text-secondary-400 truncate">{{ auth('member')->user()->email }}</p>
                                </div>

                                <div class="py-1.5 overflow-visible">
                                    {{-- Mobile Navigation Links (Home & My Cards) --}}
                                    <div class="md:hidden">
                                        <a href="{{ route('member.index') }}"
                                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-all cursor-pointer">
                                            <x-ui.icon icon="home" class="w-4 h-4" />
                                            {{ trans('common.home') }}
                                        </a>
                                        <a href="{{ route('member.cards') }}"
                                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-all cursor-pointer">
                                            <x-ui.icon icon="wallet" class="w-4 h-4" />
                                            {{ trans('common.my_cards') }}
                                        </a>
                                        <div class="my-1 border-t border-secondary-100 dark:border-secondary-700"></div>
                                    </div>

                                    {{-- My Account --}}
                                    <a href="{{ route('member.data.list', ['name' => 'account']) }}"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-all cursor-pointer">
                                        <x-ui.icon icon="user-circle" class="w-4 h-4" />
                                        {{ trans('common.my_account') }}
                                    </a>

                                    <!-- <div class="my-1 border-t border-secondary-100 dark:border-secondary-700"></div> -->

                                    {{-- Request Points --}}
                                    <!--<a href="{{ route('member.data.list', ['name' => 'request-links']) }}"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-all cursor-pointer">
                                        <x-ui.icon icon="send" class="w-4 h-4" />
                                        {{ trans('common.request_points') }}
                                    </a>

                                    {{-- Enter Code --}}
                                    <a href="{{ route('member.code.enter') }}"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-all cursor-pointer">
                                        <x-ui.icon icon="hash" class="w-4 h-4" />
                                        {{ trans('common.enter_code') }}
                                    </a>

                                    {{-- Referrals --}}
                                    <a href="{{ route('member.referrals') }}"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-all cursor-pointer">
                                        <x-ui.icon icon="user-plus" class="w-4 h-4" />
                                        {{ trans('common.referrals') }}
                                    </a>-->

                                    {{-- Agent Keys (only for registered members when feature is enabled) --}}
                                    @if(config('default.feature_agent_api') && auth('member')->user()?->isRegistered())
                                    <a href="{{ route('member.data.list', ['name' => 'agent-keys']) }}"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-all cursor-pointer">
                                        <x-ui.icon icon="bot" class="w-4 h-4" />
                                        {{ trans('agent.agent_keys') }}
                                    </a>
                                    @endif

                                    <div class="my-1 border-t border-secondary-100 dark:border-secondary-700"></div>

                                    {{-- Logout (only for registered members with email) --}}
                                    @if(auth('member')->user()?->email)
                                        <a href="{{ route('member.logout') }}"
                                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all cursor-pointer">
                                            <x-ui.icon icon="log-out" class="w-4 h-4" />
                                            {{ trans('common.logout') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
    @endauth

    {{-- Main Content Area --}}
    <main id="main-content" class="flex-1 {{ auth('member')->check() ? 'pt-16 lg:pt-18' : (($authPage ?? false) ? '' : 'pt-16 lg:pt-20') }}">
        @yield('content')
    </main>
    {{-- Theme toggle on auth pages (discrete, bottom-right) --}}
    @guest('member')
        @if($authPage ?? false)
            <x-ui.theme-toggle-fixed />
        @endif
    @endguest

    {{-- Footer - Clean iOS-style fine print (hidden on auth pages) --}}
    @if(!($authPage ?? false))
    <footer class="bg-secondary-50/50 dark:bg-secondary-900/50 border-t border-secondary-100 dark:border-secondary-800/50 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            {{-- Utility Row: Language + Theme Toggle --}}
            <div class="flex items-center justify-center gap-3 mb-4" x-data>
                {{-- Language Selector (pill, opens modal) --}}
                @if (count($languages['all'] ?? []) > 1)
                    <button @click="showLanguageModal = true"
                        class="inline-flex items-center gap-2 px-3.5 py-1.5 text-xs font-medium text-secondary-500 dark:text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-300 rounded-full border border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-all cursor-pointer">
                        <div class="fi-{{ strtolower($languages['current']['countryCode'] ?? 'us') }} fis w-4 h-4 rounded-full"></div>
                        <span>{{ $languages['current']['languageName'] ?? '' }}</span>
                    </button>
                @endif

                {{-- Theme Toggle (icon-only pill) --}}
                <button type="button" onclick="toggleTheme()"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full border border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 text-secondary-400 dark:text-secondary-500 hover:text-secondary-700 dark:hover:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-all cursor-pointer">
                    <x-ui.icon icon="sun" class="hidden w-4 h-4 dark:block" />
                    <x-ui.icon icon="moon" class="w-4 h-4 dark:hidden" />
                </button>
            </div>

            {{-- Compact Link Row --}}
            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-[11px] text-secondary-400 dark:text-secondary-500">
                <a href="{{ route('member.about') }}" class="hover:text-secondary-600 dark:hover:text-secondary-400 transition-colors">{{ trans('common.about') }}</a>
                <span class="text-secondary-300 dark:text-secondary-700">·</span>
                <a href="{{ route('member.contact') }}" class="hover:text-secondary-600 dark:hover:text-secondary-400 transition-colors">{{ trans('common.contact') }}</a>
                <span class="text-secondary-300 dark:text-secondary-700">·</span>
                <a href="{{ route('member.faq') }}" class="hover:text-secondary-600 dark:hover:text-secondary-400 transition-colors">{{ trans('common.faq') }}</a>
                <span class="text-secondary-300 dark:text-secondary-700">·</span>
                <a href="{{ route('member.privacy') }}" class="hover:text-secondary-600 dark:hover:text-secondary-400 transition-colors">{{ trans('common.privacy') }}</a>
                <span class="text-secondary-300 dark:text-secondary-700">·</span>
                <a href="{{ route('member.terms') }}" class="hover:text-secondary-600 dark:hover:text-secondary-400 transition-colors">{{ trans('common.terms') }}</a>
                @if($partnerRegistrationEnabled)
                    <span class="text-secondary-300 dark:text-secondary-700">·</span>
                    <a href="{{ route('partner.register') }}" class="hover:text-secondary-600 dark:hover:text-secondary-400 transition-colors">{{ trans('common.for_businesses') }}</a>
                @endif
            </div>

            {{-- Copyright - Minimal --}}
            <p class="mt-4 text-center text-[10px] text-secondary-300 dark:text-secondary-600">
                &copy; {{ date('Y') }} {{ config('default.app_name') }}
            </p>
        </div>
    </footer>
    @endif

    {{-- Language Selection Modal (available for all users) --}}
    <x-ui.language-modal :languages="$languages ?? []" route-key="memberIndex" />

    {{-- Agent Key One-Time Display Modal --}}
    @include('components.agent-key-modal')

    {{-- Toast Notifications --}}
    <x-ui.toast />


    {{-- PWA Offline Indicator --}}
    <x-pwa-offline-indicator />

    {{-- Cookie Consent Banner --}}
    @include('includes.cookie_consent')

</body>
</html>
