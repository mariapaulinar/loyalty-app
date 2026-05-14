<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Locale Controller
 *
 * Purpose:
 * Redirects users to their preferred locale with smart detection.
 *
 * Locale Detection Hierarchy (in order of priority):
 * 1. Authenticated member's database locale preference
 * 2. Cookie preference (set when user navigates to any locale)
 * 3. Browser Accept-Language header detection
 *
 * This hierarchy ensures:
 * - PWA users get their preferred language when reopening the app
 * - Guests who manually switched languages keep their choice
 * - New visitors get their browser's preferred language
 */

namespace App\Http\Controllers\I18n;

use App\Http\Controllers\Controller;
use App\Services\I18nService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;

class LocaleController extends Controller
{
    /**
     * Cookie name for storing locale preference.
     * Must match SetLocale middleware.
     */
    public const LOCALE_COOKIE_NAME = 'preferred_locale';

    /**
     * Cookie lifetime in minutes (1 year).
     */
    public const LOCALE_COOKIE_LIFETIME = 60 * 24 * 365;

    /**
     * Redirect to the preferred locale.
     *
     * Detection hierarchy:
     * 1. Authenticated member's stored locale preference
     * 2. Cookie preference (persists across sessions)
     * 3. Browser Accept-Language header (fallback)
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  I18nService  $i18nService  The service for handling internationalization.
     * @return \Symfony\Component\HttpFoundation\Response The redirect response to the preferred locale.
     */
    public function redirectToLocale(Request $request, I18nService $i18nService)
    {
        // Check if the intl extension is loaded.
        if (! extension_loaded('intl')) {
            exit('PHP Internationalization (intl) extension is missing. Please install it.');
        }

        // Priority 1: Check authenticated member's database locale preference
        $locale = $this->getAuthenticatedMemberLocale();

        // Priority 2: Check cookie preference
        if (! $locale) {
            $locale = $this->getCookieLocale($request);
        }

        // Priority 3: Fall back to browser Accept-Language detection
        if (! $locale) {
            $locale = $i18nService->getPreferredLocale($parsedForUrl = true);
        }

        if ($locale) {
            // Preserve any query parameters (e.g., ?source=pwa)
            $queryString = $request->getQueryString();
            $url = URL::to($locale) . ($queryString ? '?' . $queryString : '');

            // Return a redirect to the fully qualified URL
            // Use 302 (temporary) instead of 301 because the destination depends on user state
            // No caching as the destination is dynamic based on auth/cookie state
            return redirect($url, 302)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        // If no locale was found, this will result in a 404 error.
        // In production systems this should never happen if locales are properly configured.
        abort(404, 'No translation found.');
    }

    /**
     * Get the authenticated member's preferred locale (if valid).
     *
     * @return string|null URL-formatted locale (e.g., 'es-es') or null
     */
    private function getAuthenticatedMemberLocale(): ?string
    {
        // Check if a member is authenticated via session
        if (! auth('member')->check()) {
            return null;
        }

        /** @var \App\Models\Member $member */
        $member = auth('member')->user();

        // Get the member's stored locale preference
        $locale = $member->locale;

        if (! $locale) {
            return null;
        }

        // Validate that the translation directory exists
        if (! File::exists(lang_path() . '/' . $locale)) {
            return null;
        }

        // Check if the locale is active
        if (! $this->localeIsActive($locale)) {
            return null;
        }

        // Convert to URL format (en_US -> en-us)
        return strtolower(str_replace('_', '-', $locale));
    }

    /**
     * Get the user's preferred locale from cookie (if valid).
     *
     * @return string|null URL-formatted locale (e.g., 'es-es') or null
     */
    private function getCookieLocale(Request $request): ?string
    {
        $cookieLocale = $request->cookie(self::LOCALE_COOKIE_NAME);

        if (! $cookieLocale) {
            return null;
        }

        // Cookie stores URL format (es-es), convert to directory format (es_ES)
        $locales = explode('-', $cookieLocale);
        $locale = isset($locales[1])
            ? $locales[0] . '_' . strtoupper($locales[1])
            : $cookieLocale;

        // Validate that the translation directory exists
        if (! File::exists(lang_path() . '/' . $locale)) {
            return null;
        }

        // Check if the locale is active
        if (! $this->localeIsActive($locale)) {
            return null;
        }

        // Return URL format
        return strtolower(str_replace('_', '-', $locale));
    }

    /**
     * Check if a locale is active.
     *
     * @param  string  $locale  Locale in directory format (e.g., 'es_ES')
     */
    private function localeIsActive(string $locale): bool
    {
        // In demo mode, all locales are active
        if (config('default.app_demo')) {
            return true;
        }

        $configPath = lang_path($locale . '/config.php');

        if (! file_exists($configPath)) {
            // No config file = assume active for backwards compatibility
            return true;
        }

        $config = require $configPath;

        // If no 'active' key exists, default to active (backwards compatibility)
        if (! array_key_exists('active', $config)) {
            return true;
        }

        return (bool) $config['active'];
    }
}
