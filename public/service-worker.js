'use strict';

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * PWA Service Worker
 *
 * Purpose:
 * Provides offline functionality for the Reward Loyalty member portal.
 * Works for both authenticated and non-authenticated users.
 *
 * Strategy:
 * - Navigation: Network-first with cache fallback, then offline page
 * - Static assets: Cache-first with network fallback
 * - API/POST: Skip caching (let them fail naturally)
 * 
 * LOCALE HANDLING:
 * All routes require a locale prefix (e.g., /en-us/, /nl-nl/).
 * The locale is dynamically extracted from URLs - no hardcoding.
 */

const CACHE_NAME = 'loyalty-v7';

// Pattern to match locale in URL path (e.g., "en-us", "nl-nl", "de-de")
const LOCALE_PATTERN = /^\/([a-z]{2}-[a-z]{2})(\/|$|\?)/;

/**
 * Extract locale from URL path
 */
function extractLocale(url) {
    try {
        const urlObj = typeof url === 'string' ? new URL(url, self.location.origin) : url;
        const match = urlObj.pathname.match(LOCALE_PATTERN);
        return match ? match[1] : null;
    } catch (e) {
        return null;
    }
}

/**
 * Build offline URL for a given locale
 */
function buildOfflineUrl(locale) {
    return `/${locale}/offline`;
}

/**
 * Safely cache a response
 */
async function cacheResponse(request, response) {
    try {
        if (!response || !response.ok) {
            return;
        }
        const responseToCache = response.clone();
        const cache = await caches.open(CACHE_NAME);
        await cache.put(request, responseToCache);
    } catch (error) {
        // Silent fail
    }
}

/**
 * Safely cache a URL
 */
async function cacheUrl(url) {
    try {
        const cache = await caches.open(CACHE_NAME);
        const existing = await cache.match(url);
        if (existing) {
            return;
        }
        const response = await fetch(url);
        if (response.ok) {
            await cache.put(url, response);
        }
    } catch (error) {
        // Silent fail
    }
}

// ─────────────────────────────────────────────────────────────────
// INSTALL - Skip waiting to activate immediately
// ─────────────────────────────────────────────────────────────────
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

// ─────────────────────────────────────────────────────────────────
// ACTIVATE - Clean old caches and claim clients
// ─────────────────────────────────────────────────────────────────
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => {
                return Promise.all(
                    keys
                        .filter((key) => key !== CACHE_NAME)
                        .map((key) => caches.delete(key))
                );
            })
            .then(() => self.clients.claim())
    );
});

// ─────────────────────────────────────────────────────────────────
// FETCH - Handle requests
// ─────────────────────────────────────────────────────────────────
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Only handle GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Only handle same-origin requests
    if (url.origin !== self.location.origin) {
        return;
    }
    
    // Skip API routes
    if (url.pathname.startsWith('/api/')) {
        return;
    }
    
    // Skip admin/staff/partner dashboard routes (not part of PWA)
    if (url.pathname.match(/^\/[a-z]{2}-[a-z]{2}\/(admin|staff|partner)\//)) {
        return;
    }
    
    // Skip installation routes
    if (url.pathname.includes('/install')) {
        return;
    }
    
    // Skip hot module replacement (Vite dev)
    if (url.pathname.includes('/@vite') || url.pathname.includes('/__vite')) {
        return;
    }

    // NAVIGATION REQUESTS
    if (request.mode === 'navigate') {
        event.respondWith(handleNavigation(request, url));
        return;
    }

    // STATIC ASSETS (CSS, JS, images, fonts)
    if (isStaticAsset(url.pathname)) {
        event.respondWith(handleStaticAsset(request, url));
        return;
    }

    // OTHER REQUESTS - Network first, cache fallback
    event.respondWith(handleOtherRequest(request));
});

/**
 * Handle navigation requests (HTML pages)
 * Strategy: Network first → Cache → Offline page
 */
async function handleNavigation(request, url) {
    const locale = extractLocale(url);
    
    try {
        // Try network first
        const response = await fetch(request);
        
        if (response.ok) {
            // Cache the successful response
            await cacheResponse(request, response);
            
            // Also cache the offline page for this locale
            if (locale) {
                const offlineUrl = buildOfflineUrl(locale);
                cacheUrl(offlineUrl);
            }
        }
        
        return response;
    } catch (error) {
        // Try cache - first exact match
        let cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Try matching by URL without query string
        const urlWithoutQuery = url.origin + url.pathname;
        cachedResponse = await caches.match(urlWithoutQuery);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Try to find a matching cached page by pathname
        const cache = await caches.open(CACHE_NAME);
        const keys = await cache.keys();
        for (const key of keys) {
            const keyUrl = new URL(key.url);
            if (keyUrl.pathname === url.pathname) {
                return cache.match(key);
            }
        }
        
        // Try offline page for this locale
        if (locale) {
            const offlineUrl = buildOfflineUrl(locale);
            const offlineResponse = await caches.match(offlineUrl);
            if (offlineResponse) {
                return offlineResponse;
            }
        }
        
        // Try to find ANY cached offline page
        for (const key of keys) {
            if (key.url.includes('/offline')) {
                return cache.match(key);
            }
        }
        
        // Last resort - inline offline response
        return createOfflineResponse();
    }
}

/**
 * Handle static assets (CSS, JS, images, fonts)
 * Strategy: Cache first → Network
 */
async function handleStaticAsset(request, url) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const response = await fetch(request);
        if (response.ok) {
            await cacheResponse(request, response);
        }
        return response;
    } catch (error) {
        return new Response('', { status: 408, statusText: 'Offline' });
    }
}

/**
 * Handle other requests
 * Strategy: Network first → Cache
 */
async function handleOtherRequest(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            await cacheResponse(request, response);
        }
        return response;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        return cachedResponse || new Response('', { status: 408, statusText: 'Offline' });
    }
}

/**
 * Check if URL is a static asset
 */
function isStaticAsset(pathname) {
    return /\.(css|js|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot|ico|webp|json)(\?.*)?$/.test(pathname);
}

/**
 * Create an inline offline response when no cached offline page exists
 */
function createOfflineResponse() {
    const html = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9fafb;
            color: #111827;
            padding: 2rem;
        }
        @media (prefers-color-scheme: dark) {
            body { background: #0f172a; color: #f1f5f9; }
        }
        .container { text-align: center; max-width: 400px; }
        .icon { font-size: 4rem; margin-bottom: 1.5rem; }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        p { color: #6b7280; margin-bottom: 2rem; }
        @media (prefers-color-scheme: dark) { p { color: #94a3b8; } }
        button {
            padding: 0.75rem 1.5rem;
            background: #4F46E5;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            cursor: pointer;
        }
        button:hover { background: #4338ca; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📱</div>
        <h1>You're Offline</h1>
        <p>Please check your internet connection.</p>
        <button onclick="location.reload()">Try Again</button>
    </div>
    <script>
        window.addEventListener('online', () => location.reload());
    </script>
</body>
</html>`;
    
    return new Response(html, {
        status: 200,
        headers: { 'Content-Type': 'text/html; charset=utf-8' }
    });
}
