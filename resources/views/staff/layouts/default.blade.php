@php
    $languages = $languages ?? ['all' => [], 'current' => []];
    $routeName = request()->route() ? request()->route()->getName() : null;
    $routeDataDefinition = isset($dataDefinition) ? $dataDefinition->name : null;
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
    @vite(['resources/css/app.css', 'resources/js/core.js', 'resources/js/staff.js'])
    <meta name="robots" content="noindex, nofollow" />
    <x-meta.generic />
    <x-meta.favicons />
    <x-ui.brand-styles />
</head>

<body
    class="antialiased bg-secondary-50 dark:bg-secondary-950 text-secondary-900 dark:text-secondary-50 h-full selection:bg-primary-500 selection:text-white"
    x-data="{ mobileMenuOpen: false, showLanguageModal: false }">

    @auth('staff')
        <!-- Desktop Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 hidden w-[280px] bg-white dark:bg-secondary-900 border-r border-secondary-200 dark:border-secondary-800 md:flex flex-col transition-all duration-300">
            <!-- Logo -->
            <div class="flex items-center justify-center h-16 border-b border-secondary-200 dark:border-secondary-800">
                <a href="{{ route('staff.index') }}" class="flex items-center gap-3">
                    <x-ui.app-logo class="h-8 w-auto" />
                </a>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1.5">
                <x-ui.nav-link :href="route('staff.index')" :active="$routeName == 'staff.index'" icon="home">
                    {{ trans('common.dashboard') }}
                </x-ui.nav-link>
                
                {{-- Divider --}}
                <div class="py-2">
                    <div class="h-px bg-secondary-200 dark:bg-secondary-800"></div>
                </div>

                <x-ui.nav-link :href="route('staff.qr.scanner')" :active="$routeName == 'staff.qr.scanner'" icon="scan">
                    {{ trans('common.scan_qr') }}
                </x-ui.nav-link>

                <x-ui.nav-link :href="route('staff.data.list', ['name' => 'members'])"
                    :active="$routeDataDefinition == 'members'" icon="users">
                    {{ trans('common.recent_customers') }}
                </x-ui.nav-link>

                <x-ui.nav-link :href="route('staff.data.list', ['name' => 'codes'])"
                    :active="$routeDataDefinition == 'codes'" icon="ticket">
                    {{ trans('common.redemption_codes') }}
                </x-ui.nav-link>

                {{-- Divider --}}
                <div class="py-2">
                    <div class="h-px bg-secondary-200 dark:bg-secondary-800"></div>
                </div>

            </nav>

            <!-- User Profile -->
            <div class="p-3 border-t border-secondary-200 dark:border-secondary-800 relative"
                x-data="{ userMenuOpen: false }">
                <button @click="userMenuOpen = !userMenuOpen" @click.away="userMenuOpen = false"
                    class="cursor-pointer w-full flex items-center gap-3 p-2.5 rounded-xl hover:bg-secondary-50 dark:hover:bg-secondary-800/50 transition-colors duration-200 group text-left">
                    @if(auth('staff')->user()->avatar ?? false)
                        <img class="w-9 h-9 rounded-full object-cover ring-2 ring-white dark:ring-secondary-800"
                            src="{{ auth('staff')->user()->avatar }}">
                    @else
                        <div class="w-9 h-9 rounded-full bg-secondary-200 dark:bg-secondary-700 flex items-center justify-center text-secondary-500 dark:text-secondary-400">
                            <x-ui.icon icon="user" class="w-4 h-4" />
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-secondary-900 dark:text-white truncate">
                            {{ auth('staff')->user()->name ?? trans('common.staff') }}
                        </p>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400 truncate">
                            {{ auth('staff')->user()->email }}
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
                        <a href="{{ route('staff.data.list', ['name' => 'account']) }}"
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
                        <a href="{{ route('staff.logout') }}"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors duration-200 cursor-pointer">
                            <x-ui.icon icon="log-out" class="w-4 h-4" />
                            {{ trans('common.logout') }}
                        </a>
                    </div>
                </div>
            </div>
        </aside>
    @endauth

    @auth('staff')
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
                <a href="{{ route('staff.index') }}" class="flex items-center gap-2">
                    <x-ui.app-logo class="h-7 w-auto" />
                </a>
            </div>
            {{-- Profile Menu Button --}}
            <div class="relative">
                <button @click="mobileProfileOpen = !mobileProfileOpen" 
                    class="cursor-pointer flex items-center gap-2 p-1 rounded-full hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors duration-200">
                    @if(auth('staff')->user()->avatar ?? false)
                        <img class="w-8 h-8 rounded-full object-cover ring-2 ring-white dark:ring-secondary-800"
                            src="{{ auth('staff')->user()->avatar }}">
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
                            {{ auth('staff')->user()->name ?? trans('common.staff') }}
                        </p>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400 truncate">
                            {{ auth('staff')->user()->email }}
                        </p>
                    </div>
                    
                    <div class="py-1">
                        {{-- Account Settings --}}
                        <a href="{{ route('staff.data.list', ['name' => 'account']) }}"
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
                        <a href="{{ route('staff.logout') }}"
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
                    <x-ui.nav-link :href="route('staff.index')" :active="$routeName == 'staff.index'" icon="home">
                        {{ trans('common.dashboard') }}
                    </x-ui.nav-link>

                    <x-ui.nav-link :href="route('staff.qr.scanner')" :active="$routeName == 'staff.qr.scanner'" icon="scan">
                        {{ trans('common.scan_qr') }}
                    </x-ui.nav-link>

                    <x-ui.nav-link :href="route('staff.data.list', ['name' => 'members'])"
                        :active="$routeDataDefinition == 'members'" icon="users">
                        {{ trans('common.members') }}
                    </x-ui.nav-link>

                    {{-- Divider --}}
                    <div class="py-2">
                        <div class="h-px bg-gradient-to-r from-transparent via-secondary-200 dark:via-secondary-800 to-transparent"></div>
                    </div>

                    {{-- Loyalty Points Group --}}
                    <x-ui.nav-group title="{{ trans('common.loyalty_points') }}" icon="coins" :open="in_array($routeDataDefinition, ['cards', 'codes'])">
                        <x-ui.nav-link :href="route('staff.data.list', ['name' => 'cards'])"
                            :active="$routeDataDefinition == 'cards'" icon="plus-circle">
                            {{ trans('common.generate_code') }}
                        </x-ui.nav-link>

                        <x-ui.nav-link :href="route('staff.data.list', ['name' => 'codes'])"
                            :active="$routeDataDefinition == 'codes'" icon="ticket">
                            {{ trans('common.redemption_codes') }}
                        </x-ui.nav-link>
                    </x-ui.nav-group>
                </nav>
            </div>
        </div>
    @endauth

    <!-- Main Content with Sidebar -->
    <main class="{{ isset($authPage) && $authPage ? '' : 'md:pl-[280px] pt-14 md:pt-0' }}">
        @yield('content')
    </main>
    {{-- Theme toggle on auth pages (discrete, bottom-right) --}}
    @guest('staff')
        <x-ui.theme-toggle-fixed />
    @endguest

    {{-- Language Selection Modal --}}
    @auth('staff')
        @if (count($languages['all'] ?? []) > 1)
            <div x-show="showLanguageModal" 
                 style="display: none;"
                 x-effect="document.body.style.overflow = showLanguageModal ? 'hidden' : ''"
                 @click.self="showLanguageModal = false"
                 @keydown.escape.window="showLanguageModal = false"
                 class="fixed inset-0 z-[60] flex items-center justify-center px-4 bg-black/60 backdrop-blur-sm"
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0">

                <div @click.away="showLanguageModal = false"
                     class="relative bg-white dark:bg-secondary-900 w-full max-w-lg rounded-2xl shadow-2xl transform overflow-hidden"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-90 translate-y-4">
                    
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-secondary-100 dark:border-secondary-800">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                                <x-ui.icon icon="globe" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-secondary-900 dark:text-white">{{ trans('common.language') }}</h3>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ count($languages['all']) }} {{ Str::plural(strtolower(trans('common.language')), count($languages['all'])) }}</p>
                            </div>
                        </div>
                        <button @click="showLanguageModal = false"
                                type="button"
                                class="w-8 h-8 rounded-full bg-secondary-100 dark:bg-secondary-800 
                                       flex items-center justify-center 
                                       hover:bg-secondary-200 dark:hover:bg-secondary-700 
                                       transition-all duration-200 hover:scale-110 cursor-pointer">
                            <x-ui.icon icon="x" class="w-4 h-4 text-secondary-600 dark:text-secondary-400" />
                        </button>
                    </div>
                    
                    {{-- Language Grid --}}
                    <div class="p-4 max-h-[60vh] overflow-y-auto">
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($languages['all'] as $language)
                                @php 
                                    $currentLocale = $languages['current']['locale'] ?? '';
                                    $langLocale = $language['locale'] ?? '';
                                    $isActive = $currentLocale !== '' && $langLocale !== '' && $currentLocale === $langLocale;
                                @endphp
                                <a href="{{ $language['staffIndex'] ?? '#' }}"
                                    class="group flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-medium transition-all duration-200
                                        {{ $isActive 
                                            ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 ring-2 ring-primary-500/20 dark:ring-primary-400/20 shadow-sm' 
                                            : 'text-secondary-700 dark:text-secondary-300 bg-secondary-50 dark:bg-secondary-800/50 hover:bg-secondary-100 dark:hover:bg-secondary-800 hover:shadow-sm active:scale-[0.98]' }}">
                                    <div class="w-7 h-7 rounded-full fis fi-{{ strtolower($language['countryCode'] ?? 'us') }} shadow-md ring-1 ring-black/5 flex-shrink-0"></div>
                                    <span class="flex-1 truncate">{{ $language['languageName'] ?? 'Unknown' }}</span>
                                    @if($isActive)
                                        <div class="w-5 h-5 rounded-full bg-primary-500 flex items-center justify-center flex-shrink-0">
                                            <x-ui.icon icon="check" class="w-3 h-3 text-white" />
                                        </div>
                                    @else
                                        <x-ui.icon icon="chevron-right" class="w-4 h-4 text-secondary-300 dark:text-secondary-600 opacity-0 group-hover:opacity-100 transition-opacity" />
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-6 py-3 border-t border-secondary-100 dark:border-secondary-800 bg-secondary-50/50 dark:bg-secondary-800/30">
                        <p class="text-[11px] text-secondary-400 dark:text-secondary-500 text-center">
                            <x-ui.icon icon="info" class="w-3 h-3 inline -mt-0.5" />
                            {{ $languages['current']['languageName'] ?? '' }} ({{ $languages['current']['countryCode'] ?? '' }})
                        </p>
                    </div>
                </div>
            </div>
        @endif
    @endauth

    <x-ui.toast />
    <x-ui.lightbox />
    @include('includes.demo')

    {{-- ═══════════════════════════════════════════════════════════════════════
        USB / Bluetooth QR Barcode Scanner — Keyboard-Wedge Handler
        
        Many businesses use hardware QR scanners (USB or Bluetooth) instead 
        of camera-based scanning. These devices act as keyboard wedges: they 
        type the QR content as rapid keystrokes followed by Enter or Tab.
        
        This handler captures those rapid bursts, validates the scanned URL 
        (same-origin only, http/https), and auto-navigates. It ignores 
        keystrokes when an input/textarea is focused so it never interferes 
        with normal form typing.
        
        Community contribution
    ═══════════════════════════════════════════════════════════════════════ --}}
    @auth('staff')
    <script>
        (() => {
            if (window.__staffQrScannerActive) return;
            window.__staffQrScannerActive = true;

            let buffer = '';
            let timer = null;
            const MIN_LENGTH = 8;
            const RESET_MS = 350; // Real scanners complete in 50-200ms; 350ms gives Bluetooth margin

            const reset = () => {
                buffer = '';
                clearTimeout(timer);
                timer = null;
            };

            const tryNavigate = (raw) => {
                // Strip control characters and whitespace
                const value = String(raw ?? '')
                    .replace(/[\u0000-\u001F\u007F]/g, '')
                    .trim();

                if (!value || value.length < MIN_LENGTH) return false;

                try {
                    const url = new URL(value, window.location.origin);

                    // Only allow http/https (blocks javascript:, data:, file: schemes)
                    if (url.protocol !== 'http:' && url.protocol !== 'https:') return false;

                    // Only navigate to same-origin URLs (prevents open redirect / phishing)
                    if (url.origin !== window.location.origin) {
                        console.warn('[QR Scanner] Blocked off-origin URL:', value);
                        return false;
                    }

                    window.location.assign(url.toString());
                    return true;
                } catch (e) {
                    return false;
                }
            };

            // Capture phase (true) ensures we see events before other handlers can stop them
            document.addEventListener('keydown', (e) => {
                // Don't intercept when user is typing in a form field
                const el = document.activeElement;
                if (el) {
                    const tag = el.tagName;
                    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable) {
                        reset();
                        return;
                    }
                }

                // Scanner terminator (Enter or Tab)
                if (e.key === 'Enter' || e.key === 'Tab') {
                    if (tryNavigate(buffer)) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    reset();
                    return;
                }

                // Only buffer printable single characters; ignore modifier combos
                if (e.key.length !== 1 || e.ctrlKey || e.altKey || e.metaKey) return;

                buffer += e.key;
                clearTimeout(timer);
                timer = setTimeout(reset, RESET_MS);
            }, true);
        })();
    </script>
    @endauth

    @stack('scripts')

</body>

</html>