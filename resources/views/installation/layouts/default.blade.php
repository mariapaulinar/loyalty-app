{{--
Installation Layout - Premium enterprise design
Sidebar uses a rich gradient for visual depth in both light and dark modes.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ trans('config.dir') }}">

<head>
    <meta charset="utf-8">
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>@yield('page_title')</title>
    @vite(['resources/css/app.css', 'resources/js/core.js'])
    <x-meta.favicons />
    <x-ui.brand-styles />
</head>

<body
    class="antialiased bg-secondary-50 dark:bg-secondary-950 text-secondary-900 dark:text-white min-h-screen"
    x-data="{ tab: 1, installing: false, appName: '{{ config('default.app_name') }}' }" x-cloak x-show="true">
    <div class="flex min-h-screen">
        {{-- Sidebar - Refined gradient background --}}
        <aside class="hidden lg:flex w-64 flex-col flex-shrink-0 relative overflow-hidden">
            {{-- Gradient background layers --}}
            <div class="absolute inset-0 bg-gradient-to-b from-secondary-900 via-secondary-900 to-secondary-950"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-primary-900/30 via-transparent to-primary-950/20"></div>
            {{-- Subtle pattern overlay --}}
            <div class="absolute inset-0 opacity-[0.02]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 24px 24px;"></div>
            
            <div class="relative flex flex-col h-full p-6">
                @include('installation.includes.sidebar')
            </div>
        </aside>

        {{-- Main Content --}}
        <main class="flex-1 flex flex-col overflow-y-auto">
            {{-- Mobile Header --}}
            <div class="lg:hidden flex items-center justify-between p-4 bg-white dark:bg-secondary-900 border-b border-secondary-200 dark:border-secondary-800 sticky top-0 z-10">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary-600 to-primary-500 flex items-center justify-center text-white font-bold text-sm shadow-sm transition-all" x-text="(appName || 'R').charAt(0).toUpperCase()">
                    </div>
                    <span class="font-bold text-sm text-secondary-900 dark:text-white transition-all" x-text="appName || '{{ config('default.app_name') }}'"></span>
                </div>
                <div class="flex items-center gap-2">
                    {{-- Mobile step indicator --}}
                    <div class="flex items-center gap-1.5 mr-2">
                        <template x-for="s in [1, 2, 3]" :key="s">
                            <div class="h-1.5 rounded-full transition-all duration-300"
                                :class="tab >= s ? 'w-6 bg-primary-500' : 'w-1.5 bg-secondary-300 dark:bg-secondary-700'"></div>
                        </template>
                    </div>
                    <button type="button" @click="toggleTheme()"
                        class="flex items-center justify-center w-9 h-9 rounded-full border border-secondary-200 dark:border-secondary-700 text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-all cursor-pointer">
                        <x-ui.icon icon="sun" class="w-4 h-4 hidden dark:block" />
                        <x-ui.icon icon="moon" class="w-4 h-4 dark:hidden" />
                    </button>
                </div>
            </div>

            {{-- Content area --}}
            <div class="flex-1 flex items-start justify-center p-6 lg:p-10 lg:pt-8">
                <div class="w-full max-w-2xl">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>
</body>

</html>