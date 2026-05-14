@php
    $languages = $languages ?? ['all' => [], 'current' => []];
    $routeName = request()->route() ? request()->route()->getName() : null;
    $routeDataDefinition = $dataDefinition->name ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ trans('config.dir') }}" class="h-full overflow-x-hidden">
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
    {{-- Partner bundle loads FIRST: TipTap registers Alpine.data() which must happen before Alpine.start() in core.js --}}
    @vite(['resources/css/app.css', 'resources/js/partner.js', 'resources/js/core.js'])
    <meta name="robots" content="noindex, nofollow" />
    <x-meta.generic />
    <x-meta.favicons />
    <x-ui.brand-styles />
</head>
<body
    class="antialiased bg-secondary-50 dark:bg-secondary-950 text-secondary-900 dark:text-secondary-50 h-full selection:bg-primary-500 selection:text-white overflow-x-hidden"
    x-data="{ mobileMenuOpen: false, showLanguageModal: false }">
    @auth('partner')
        @php
            $entitlements = app(\App\Services\EntitlementService::class);
            $partner = auth('partner')->user();
        @endphp
        <!-- Desktop Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 hidden w-[280px] bg-white dark:bg-secondary-900 border-r border-secondary-200 dark:border-secondary-800 md:flex flex-col transition-all duration-300">
            <!-- Logo -->
            <div class="flex items-center justify-center h-16 border-b border-secondary-200 dark:border-secondary-800">
                <a href="{{ route('partner.index') }}" class="flex items-center gap-3">
                    <x-ui.app-logo class="h-8 w-auto" />
                </a>
            </div>
            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1.5">
                {{-- Dashboard --}}
                <x-ui.nav-link :href="route('partner.index')" :active="$routeName == 'partner.index'" icon="home">
                    {{ trans('common.dashboard') }}
                </x-ui.nav-link>
                {{-- Divider --}}
                <div class="py-2">
                    <div class="h-px bg-secondary-200 dark:bg-secondary-800"></div>
                </div>
                {{-- Loyalty Programs --}}
                @if($entitlements->can($partner, 'loyalty_cards'))
                <x-ui.nav-group :title="trans('common.loyalty_programs')" icon="coins">
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'cards'])"
                        :active="$routeDataDefinition == 'cards'" icon="credit-card">
                        {{ trans('common.loyalty_cards') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'rewards'])"
                        :active="$routeDataDefinition == 'rewards'" icon="gift">
                        {{ trans('common.rewards') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'tiers'])"
                        :active="$routeDataDefinition == 'tiers'" icon="award">
                        {{ trans('common.tiers') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'referrals'])"
                        :active="$routeDataDefinition == 'referrals'" icon="user-plus">
                        {{ trans('common.refer_earn') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.analytics')" :active="Str::startsWith($routeName, 'partner.analytics')"
                        icon="trending-up">
                        {{ trans('common.analytics') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'transactions'])"
                        :active="$routeDataDefinition == 'transactions'" icon="scroll-text">
                        {{ trans('common.transaction_history') }}
                    </x-ui.nav-link>
                </x-ui.nav-group>
                @endif
                {{-- Stamp Cards --}}
                @if($entitlements->can($partner, 'stamp_cards'))
                <x-ui.nav-group :title="trans('common.stamp_cards')" icon="stamp">
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'stamp-cards'])"
                        :active="$routeDataDefinition == 'stamp-cards'" icon="stamp">
                        {{ trans('common.stamp_cards') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.stamp-card-analytics')" :active="Str::startsWith($routeName, 'partner.stamp-card-analytics')"
                        icon="bar-chart">
                        {{ trans('common.analytics') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'stamp-transactions'])"
                        :active="$routeDataDefinition == 'stamp-transactions'" icon="scroll-text">
                        {{ trans('common.transaction_history') }}
                    </x-ui.nav-link>
                </x-ui.nav-group>
                @endif
                {{-- Vouchers --}}
                @if($entitlements->can($partner, 'vouchers'))
                <x-ui.nav-group title="{{ trans('common.vouchers') }}" icon="ticket">
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'vouchers'])"
                        :active="$routeDataDefinition == 'vouchers'" icon="tag">
                        {{ trans('common.vouchers') }}
                    </x-ui.nav-link>
                    @if($entitlements->can($partner, 'voucher_batches'))
                    <x-ui.nav-link :href="route('partner.vouchers.batches')"
                        :active="Str::startsWith($routeName, 'partner.vouchers.batch') || $routeDataDefinition == 'batch-vouchers'" icon="layers">
                        {{ trans('common.batches') }}
                    </x-ui.nav-link>
                    @endif
                    <x-ui.nav-link :href="route('partner.voucher-analytics')" :active="Str::startsWith($routeName, 'partner.voucher-analytics')"
                        icon="bar-chart">
                        {{ trans('common.analytics') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'voucher-redemptions'])"
                        :active="$routeDataDefinition == 'voucher-redemptions'" icon="scroll-text">
                        {{ trans('common.voucher_redemption_history') }}
                    </x-ui.nav-link>
                </x-ui.nav-group>
                @endif
                {{-- Divider --}}
                <div class="py-2">
                    <div class="h-px bg-secondary-200 dark:bg-secondary-800"></div>
                </div>
                {{-- Team & Members --}}
                <x-ui.nav-group title="{{ trans('common.team_and_members') }}" icon="users">
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'clubs'])"
                        :active="$routeDataDefinition == 'clubs'" icon="layers">
                        {{ trans('common.clubs') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'staff'])"
                        :active="$routeDataDefinition == 'staff'" icon="briefcase">
                        {{ trans('common.staff') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'members'])"
                        :active="$routeDataDefinition == 'members'" icon="user-check">
                        {{ trans('common.members') }}
                    </x-ui.nav-link>
                </x-ui.nav-group>
                {{-- Email Campaigns --}}
                @if($entitlements->can($partner, 'email_campaigns'))
                <x-ui.nav-group :title="trans('common.email_campaigns')" icon="megaphone">
                    <x-ui.nav-link :href="route('partner.email-campaigns.compose')"
                        :active="$routeName == 'partner.email-campaigns.compose'" icon="mail-plus">
                        {{ trans('common.email_campaign.compose') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.email-campaigns.index')"
                        :active="Str::startsWith($routeName, 'partner.email-campaigns') && $routeName != 'partner.email-campaigns.compose'" icon="mails">
                        {{ trans('common.email_campaign.list') }}
                    </x-ui.nav-link>
                </x-ui.nav-group>
                @endif
                {{-- Activity --}}
                @if($entitlements->can($partner, 'activity_log'))
                <x-ui.nav-group :title="trans('common.activity')" icon="activity">
                    <x-ui.nav-link :href="route('partner.activity-logs.analytics')"
                        :active="$routeName == 'partner.activity-logs.analytics'" icon="pie-chart">
                        {{ trans('common.activity_log_analytics') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'activity-logs'])"
                        :active="$routeDataDefinition == 'activity-logs'" icon="list">
                        {{ trans('common.activity_logs') }}
                    </x-ui.nav-link>
                </x-ui.nav-group>
                @endif
                {{-- Integrations (Shopify, Agent Keys, etc.) --}}
                @if(config('default.feature_shopify') || (config('default.feature_agent_api') && $entitlements->can($partner, 'agent_api')))
                <x-ui.nav-group :title="trans('common.integrations')" icon="plug">
                    @if(config('default.feature_shopify'))
                    <x-ui.nav-link :href="route('partner.integrations.shopify')"
                        :active="Str::startsWith($routeName, 'partner.integrations.shopify')" icon="shopping-bag">
                        Shopify
                    </x-ui.nav-link>
                    @endif
                    @if(config('default.feature_agent_api') && $entitlements->can($partner, 'agent_api'))
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'agent-keys'])"
                        :active="$routeDataDefinition == 'agent-keys'" icon="bot">
                        {{ trans('agent.agent_keys') }}
                    </x-ui.nav-link>
                    @endif
                </x-ui.nav-group>
                @endif
                {{-- Billing / My Plan --}}
                <div class="py-2">
                    <div class="h-px bg-secondary-200 dark:bg-secondary-800"></div>
                </div>
                <x-ui.nav-link :href="route('partner.billing')" :active="$routeName == 'partner.billing'" icon="wallet">
                    {{ trans('common.billing_my_plan') }}
                </x-ui.nav-link>
            </nav>
            <!-- User Profile -->
            <div class="p-3 border-t border-secondary-200 dark:border-secondary-800 relative"
                x-data="{ userMenuOpen: false }">
                <button @click="userMenuOpen = !userMenuOpen" @click.away="userMenuOpen = false"
                    class="cursor-pointer w-full flex items-center gap-3 p-2.5 rounded-xl hover:bg-secondary-50 dark:hover:bg-secondary-800/50 transition-colors duration-200 group text-left">
                    @if(auth('partner')->user()->avatar)
                        <img class="w-9 h-9 rounded-full object-cover ring-2 ring-white dark:ring-secondary-800"
                            src="{{ auth('partner')->user()->avatar }}">
                    @else
                        <div class="w-9 h-9 rounded-full bg-secondary-200 dark:bg-secondary-700 flex items-center justify-center text-secondary-500 dark:text-secondary-400">
                            <x-ui.icon icon="user" class="w-4 h-4" />
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-secondary-900 dark:text-white truncate">
                            {{ auth('partner')->user()->name }}
                        </p>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400 truncate">
                            {{ auth('partner')->user()->email }}
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
                        {{-- Profile --}}
                        <a href="{{ route('partner.data.list', ['name' => 'account']) }}"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors duration-200 cursor-pointer">
                            <x-ui.icon icon="user-circle" class="w-4 h-4" />
                            {{ trans('common.account_settings') }}
                        </a>
                        {{-- Business Settings --}}
                        <a href="{{ route('partner.data.list', ['name' => 'business-settings']) }}"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors duration-200 cursor-pointer">
                            <x-ui.icon icon="store" class="w-4 h-4" />
                            {{ trans('common.business_settings') }}
                        </a>
                        {{-- My Plan --}}
                        <a href="{{ route('partner.billing') }}"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors duration-200 cursor-pointer">
                            <x-ui.icon icon="wallet" class="w-4 h-4" />
                            {{ trans('common.billing_my_plan') }}
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
                        <a href="{{ route('partner.logout') }}"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors duration-200 cursor-pointer">
                            <x-ui.icon icon="log-out" class="w-4 h-4" />
                            {{ trans('common.logout') }}
                        </a>
                    </div>
                </div>
            </div>
        </aside>
    @endauth
    @auth('partner')
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
                <a href="{{ route('partner.index') }}" class="flex items-center gap-2">
                    <x-ui.app-logo class="h-7 w-auto" />
                </a>
            </div>
            {{-- Profile Menu Button --}}
            <div class="relative">
                <button @click="mobileProfileOpen = !mobileProfileOpen" 
                    class="cursor-pointer flex items-center gap-2 p-1 rounded-full hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors duration-200">
                    @if(auth('partner')->user()->avatar)
                        <img class="w-8 h-8 rounded-full object-cover ring-2 ring-white dark:ring-secondary-800"
                            src="{{ auth('partner')->user()->avatar }}">
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
                            {{ auth('partner')->user()->name }}
                        </p>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400 truncate">
                            {{ auth('partner')->user()->email }}
                        </p>
                    </div>
                    
                    <div class="py-1">
                        {{-- Account Settings --}}
                        <a href="{{ route('partner.data.list', ['name' => 'account']) }}"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors duration-200 cursor-pointer">
                            <x-ui.icon icon="user-circle" class="w-4 h-4" />
                            {{ trans('common.account_settings') }}
                        </a>
                        {{-- Business Settings --}}
                        <a href="{{ route('partner.data.list', ['name' => 'business-settings']) }}"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors duration-200 cursor-pointer">
                            <x-ui.icon icon="store" class="w-4 h-4" />
                            {{ trans('common.business_settings') }}
                        </a>
                        {{-- My Plan --}}
                        <a href="{{ route('partner.billing') }}"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors duration-200 cursor-pointer">
                            <x-ui.icon icon="wallet" class="w-4 h-4" />
                            {{ trans('common.billing_my_plan') }}
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
                        <a href="{{ route('partner.logout') }}"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors duration-200 cursor-pointer">
                            <x-ui.icon icon="log-out" class="w-4 h-4" />
                            {{ trans('common.logout') }}
                        </a>
                    </div>
                </div>
            </div>
        </header>
        <!-- Mobile Menu Overlay -->
        <div x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="md:hidden fixed inset-0 z-30 bg-secondary-50 dark:bg-secondary-950 pt-16 pb-6 px-4 overflow-y-auto"
            x-cloak>
            <nav class="space-y-1">
                {{-- Dashboard --}}
                <x-ui.nav-link :href="route('partner.index')" :active="$routeName == 'partner.index'" icon="home">
                    {{ trans('common.dashboard') }}
                </x-ui.nav-link>
                {{-- Divider --}}
                <div class="py-2">
                    <div class="h-px bg-secondary-200 dark:bg-secondary-800"></div>
                </div>
                {{-- Loyalty Programs --}}
                @if($entitlements->can($partner, 'loyalty_cards'))
                <x-ui.nav-group :title="trans('common.loyalty_programs')" icon="coins">
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'cards'])"
                        :active="$routeDataDefinition == 'cards'" icon="credit-card">
                        {{ trans('common.loyalty_cards') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'rewards'])"
                        :active="$routeDataDefinition == 'rewards'" icon="gift">
                        {{ trans('common.rewards') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'tiers'])"
                        :active="$routeDataDefinition == 'tiers'" icon="award">
                        {{ trans('common.tiers') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'referrals'])"
                        :active="$routeDataDefinition == 'referrals'" icon="user-plus">
                        {{ trans('common.refer_earn') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.analytics')" :active="Str::startsWith($routeName, 'partner.analytics')"
                        icon="trending-up">
                        {{ trans('common.analytics') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'transactions'])"
                        :active="$routeDataDefinition == 'transactions'" icon="scroll-text">
                        {{ trans('common.transaction_history') }}
                    </x-ui.nav-link>
                </x-ui.nav-group>
                @endif
                {{-- Stamp Cards --}}
                @if($entitlements->can($partner, 'stamp_cards'))
                <x-ui.nav-group :title="trans('common.stamp_cards')" icon="stamp">
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'stamp-cards'])"
                        :active="$routeDataDefinition == 'stamp-cards'" icon="stamp">
                        {{ trans('common.stamp_cards') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.stamp-card-analytics')" :active="Str::startsWith($routeName, 'partner.stamp-card-analytics')"
                        icon="bar-chart">
                        {{ trans('common.analytics') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'stamp-transactions'])"
                        :active="$routeDataDefinition == 'stamp-transactions'" icon="scroll-text">
                        {{ trans('common.transaction_history') }}
                    </x-ui.nav-link>
                </x-ui.nav-group>
                @endif
                {{-- Vouchers --}}
                @if($entitlements->can($partner, 'vouchers'))
                <x-ui.nav-group title="{{ trans('common.vouchers') }}" icon="ticket">
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'vouchers'])"
                        :active="$routeDataDefinition == 'vouchers'" icon="tag">
                        {{ trans('common.vouchers') }}
                    </x-ui.nav-link>
                    @if($entitlements->can($partner, 'voucher_batches'))
                    <x-ui.nav-link :href="route('partner.vouchers.batches')"
                        :active="Str::startsWith($routeName, 'partner.vouchers.batch') || $routeDataDefinition == 'batch-vouchers'" icon="layers">
                        {{ trans('common.batches') }}
                    </x-ui.nav-link>
                    @endif
                    <x-ui.nav-link :href="route('partner.voucher-analytics')" :active="Str::startsWith($routeName, 'partner.voucher-analytics')"
                        icon="bar-chart">
                        {{ trans('common.analytics') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'voucher-redemptions'])"
                        :active="$routeDataDefinition == 'voucher-redemptions'" icon="scroll-text">
                        {{ trans('common.voucher_redemption_history') }}
                    </x-ui.nav-link>
                </x-ui.nav-group>
                @endif
                {{-- Members --}}
                <x-ui.nav-group title="{{ trans('common.members') }}" icon="users">
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'clubs'])"
                        :active="$routeDataDefinition == 'clubs'" icon="layers">
                        {{ trans('common.clubs') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'staff'])"
                        :active="$routeDataDefinition == 'staff'" icon="briefcase">
                        {{ trans('common.staff') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'members'])"
                        :active="$routeDataDefinition == 'members'" icon="user-check">
                        {{ trans('common.members') }}
                    </x-ui.nav-link>
                </x-ui.nav-group>
                {{-- Email Campaigns --}}
                @if($entitlements->can($partner, 'email_campaigns'))
                <x-ui.nav-group :title="trans('common.email_campaigns')" icon="megaphone">
                    <x-ui.nav-link :href="route('partner.email-campaigns.compose')"
                        :active="$routeName == 'partner.email-campaigns.compose'" icon="mail-plus">
                        {{ trans('common.email_campaign.compose') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.email-campaigns.index')"
                        :active="Str::startsWith($routeName, 'partner.email-campaigns') && $routeName != 'partner.email-campaigns.compose'" icon="mails">
                        {{ trans('common.email_campaign.list') }}
                    </x-ui.nav-link>
                </x-ui.nav-group>
                @endif
                {{-- Activity --}}
                @if($entitlements->can($partner, 'activity_log'))
                <x-ui.nav-group :title="trans('common.activity')" icon="activity">
                    <x-ui.nav-link :href="route('partner.activity-logs.analytics')"
                        :active="$routeName == 'partner.activity-logs.analytics'" icon="pie-chart">
                        {{ trans('common.activity_log_analytics') }}
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'activity-logs'])"
                        :active="$routeDataDefinition == 'activity-logs'" icon="list">
                        {{ trans('common.activity_logs') }}
                    </x-ui.nav-link>
                </x-ui.nav-group>
                @endif
                {{-- Integrations (Shopify, Agent Keys, etc.) --}}
                @if(config('default.feature_shopify') || (config('default.feature_agent_api') && $entitlements->can($partner, 'agent_api')))
                <x-ui.nav-group :title="trans('common.integrations')" icon="plug">
                    @if(config('default.feature_shopify'))
                    <x-ui.nav-link :href="route('partner.integrations.shopify')"
                        :active="Str::startsWith($routeName, 'partner.integrations.shopify')" icon="shopping-bag">
                        Shopify
                    </x-ui.nav-link>
                    @endif
                    @if(config('default.feature_agent_api') && $entitlements->can($partner, 'agent_api'))
                    <x-ui.nav-link :href="route('partner.data.list', ['name' => 'agent-keys'])"
                        :active="$routeDataDefinition == 'agent-keys'" icon="bot">
                        {{ trans('agent.agent_keys') }}
                    </x-ui.nav-link>
                    @endif
                </x-ui.nav-group>
                @endif
                
                <div class="h-4"></div>
                
                <a href="{{ route('partner.data.list', ['name' => 'account']) }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-secondary-600 dark:text-secondary-400 hover:bg-white dark:hover:bg-secondary-800 transition-colors duration-200 {{ $routeDataDefinition == 'account' ? 'bg-white dark:bg-secondary-800 text-primary-600 dark:text-primary-400 shadow-sm' : '' }}">
                    <x-ui.icon icon="user-circle" class="w-5 h-5" />
                    <span class="font-medium">{{ trans('common.account_settings') }}</span>
                </a>
                
                <a href="{{ route('partner.data.list', ['name' => 'business-settings']) }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-secondary-600 dark:text-secondary-400 hover:bg-white dark:hover:bg-secondary-800 transition-colors duration-200 {{ $routeDataDefinition == 'business-settings' ? 'bg-white dark:bg-secondary-800 text-primary-600 dark:text-primary-400 shadow-sm' : '' }}">
                    <x-ui.icon icon="store" class="w-5 h-5" />
                    <span class="font-medium">{{ trans('common.business_settings') }}</span>
                </a>
                
                <a href="{{ route('partner.billing') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-secondary-600 dark:text-secondary-400 hover:bg-white dark:hover:bg-secondary-800 transition-colors duration-200 {{ $routeName == 'partner.billing' ? 'bg-white dark:bg-secondary-800 text-primary-600 dark:text-primary-400 shadow-sm' : '' }}">
                    <x-ui.icon icon="wallet" class="w-5 h-5" />
                    <span class="font-medium">{{ trans('common.billing_my_plan') }}</span>
                </a>
                
                <div class="pt-4 pb-2">
                    <p class="px-3 text-xs font-medium text-secondary-400 uppercase tracking-wide">
                        {{ trans('common.preferences') }}
                    </p>
                </div>
                
                <!-- Language Selector (Opens Modal) -->
                @if (count($languages['all'] ?? []) > 1)
                    <button type="button" @click="showLanguageModal = true"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-secondary-600 dark:text-secondary-400 hover:bg-white dark:hover:bg-secondary-800 transition-colors duration-200 cursor-pointer">
                        <div class="fi-{{ strtolower($languages['current']['countryCode']) }} fis w-5 h-5 rounded-full"></div>
                        <span class="flex-1 text-left">{{ $languages['current']['languageName'] }}</span>
                        <x-ui.icon icon="chevron-right" class="w-4 h-4 text-secondary-400" />
                    </button>
                @endif
                
                <!-- Theme Toggle -->
                <button id="theme-toggle-mobile" type="button"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-secondary-600 dark:text-secondary-400 hover:bg-white dark:hover:bg-secondary-800 transition-colors duration-200">
                    <x-ui.icon icon="sun" class="hidden w-5 h-5 dark:block" id="theme-toggle-light-icon-mobile" />
                    <x-ui.icon icon="moon" class="w-5 h-5 dark:hidden" id="theme-toggle-dark-icon-mobile" />
                    <span class="flex-1 text-left">{{ trans('common.theme') }}</span>
                </button>
                
                <div class="pt-4 mt-4 border-t border-secondary-200 dark:border-secondary-800">
                    <a href="{{ route('partner.logout') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors duration-200">
                        <x-ui.icon icon="log-out" class="w-5 h-5" />
                        <span class="font-medium">{{ trans('common.logout') }}</span>
                    </a>
                </div>
            </nav>
        </div>
    @endauth
    <!-- Main Content -->
    <main class="@auth('partner') md:pl-[280px] pt-14 md:pt-0 pb-safe md:pb-0 @endauth min-h-screen transition-all duration-300">
        @yield('content')
    </main>
    {{-- Theme toggle on auth pages (discrete, bottom-right) --}}
    @guest('partner')
        <x-ui.theme-toggle-fixed />
    @endguest
    {{-- Language Selection Modal (available for auth + registration) --}}
    <x-ui.language-modal :languages="$languages ?? []" route-key="partnerIndex" />

    {{-- Agent Key One-Time Display Modal --}}
    @include('components.agent-key-modal')

    <x-ui.toast />
    <x-ui.lightbox />
    <x-ui.qr-modal-data />
    @include('includes.demo')
    @stack('scripts')
</body>
</html>