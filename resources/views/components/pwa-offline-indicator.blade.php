{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

PWA Offline Indicator

Purpose:
Shows a banner when the user goes offline, auto-dismisses after 5 seconds.
Add this component before closing </body> tag.

Usage:
<x-pwa-offline-indicator />
--}}

<div 
    x-data="{ 
        show: false, 
        dismissed: false,
        timer: null,
        init() {
            this.verifyConnectivity();
            window.addEventListener('online', () => this.handleOnline());
            window.addEventListener('offline', () => this.verifyConnectivity());
        },
        async verifyConnectivity() {
            // navigator.onLine is unreliable — it can return false even when
            // the device is connected (browser quirks, service worker cache
            // interactions, DNS issues). Always verify with a real request.
            if (navigator.onLine) return;

            try {
                // HEAD request to the current page — guaranteed to return 200.
                // Avoids /favicon.ico which may 404 and pollute the console.
                // Cache-busting query prevents any HTTP cache from masking the result.
                const response = await fetch(window.location.href + (window.location.href.includes('?') ? '&' : '?') + '_offline_check=' + Date.now(), {
                    method: 'HEAD',
                    cache: 'no-store',
                    mode: 'no-cors'
                });
                // If we get any response, we're online — navigator.onLine lied.
                return;
            } catch (e) {
                // Fetch genuinely failed — we're truly offline.
            }

            this.handleOffline();
        },
        handleOffline() {
            if (this.dismissed) return;
            this.show = true;
            this.startTimer();
        },
        handleOnline() {
            this.show = false;
            this.dismissed = false;
            if (this.timer) {
                clearTimeout(this.timer);
                this.timer = null;
            }
        },
        dismiss() {
            this.show = false;
            this.dismissed = true;
            if (this.timer) {
                clearTimeout(this.timer);
                this.timer = null;
            }
        },
        startTimer() {
            if (this.timer) clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                this.show = false;
            }, 5000);
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-2"
    style="display: none;"
    class="fixed bottom-4 left-4 right-4 z-50 md:left-auto md:right-4 md:max-w-md"
    role="alert"
    aria-live="polite">
    
    <div class="bg-amber-500 text-white px-4 py-3 rounded-xl shadow-2xl flex items-center gap-3">
        {{-- Icon --}}
        <div class="flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414" />
            </svg>
        </div>

        {{-- Message --}}
        <div class="flex-1 text-sm font-medium">
            You're offline - showing cached data
        </div>

        {{-- Dismiss Button --}}
        <button 
            @click="dismiss()"
            class="flex-shrink-0 p-1 hover:bg-amber-600 rounded-lg transition-colors"
            aria-label="Dismiss">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>
