<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SetLocale
{
    /**
     * Routes that should bypass locale validation.
     *
     * These routes are intentionally locale-agnostic and handle
     * their own locale detection/redirection logic.
     *
     * @var array<int, string>
     */
    protected array $bypassPatterns = [
        'r',        // /r/{code} - Referral short links
        'shopify',  // /shopify/* - OAuth callbacks
    ];

    /**
     * Cookie name for storing locale preference.
     * Must match LocaleController constant.
     */
    public const LOCALE_COOKIE_NAME = 'preferred_locale';

    /**
     * Cookie lifetime in minutes (1 year).
     */
    public const LOCALE_COOKIE_LIFETIME = 60 * 24 * 365;

    /**
     * Handle an incoming request and set the application's locale.
     *
     * Also sets a cookie to remember the user's locale preference for future visits.
     * This is critical for PWA support - when the PWA reopens, it navigates to the
     * root URL which then redirects to the user's preferred locale based on this cookie.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Set locale based on console status, request header or use default locale
        $locale = app()->runningInConsole()
            ? config('app.locale')
            : $request->header('locale', null);

        // Allow locale-agnostic routes to pass through without validation.
        // These routes handle their own locale detection and redirection.
        $firstSegment = $request->segment(1);
        if (! $locale && $firstSegment && $this->shouldBypassLocaleValidation($firstSegment)) {
            app()->setLocale(config('app.locale'));
            \Carbon\Carbon::setLocale(config('app.locale'));

            return $next($request);
        }

        if (! $locale && $firstSegment) {
            // Extract locale from URL segment
            $locales = explode('-', $firstSegment);
            $locale = isset($locales[1])
                ? $locales[0].'_'.strtoupper($locales[1])
                : config('app.locale');

            // Check for existing translation file and compare segment to locale
            // Redirect to 'redir.locale' route if conditions are not met
            if (! File::exists(lang_path().'/'.$locale) || $firstSegment !== strtolower(str_replace('_', '-', $locale))) {
                return redirect()->route('redir.locale');
            } else {
                // Check if the matched locale is active
                if (! $this->localeIsActive($locale)) {
                    return redirect()->route('redir.locale');
                }
            }
        }

        if ($locale) {
            // Verify if locale is active, if not redirect to 'redir.locale' route
            if (! $this->localeIsActive($locale)) {
                return redirect()->route('redir.locale');
            }

            // Set the application's locale
            app()->setLocale($locale);
            \Carbon\Carbon::setLocale($locale);

            // Set a cookie to remember this locale preference for future visits.
            // This is especially important for PWA reopens where the start_url is root (/).
            // Cookie stores URL format (es-es) for consistency with how LocaleController reads it.
            $urlLocale = strtolower(str_replace('_', '-', $locale));
            $response = $next($request);

            // Only set cookie if the response supports it (not a streamed download, etc.)
            if (method_exists($response, 'withCookie')) {
                $response->withCookie(cookie(
                    self::LOCALE_COOKIE_NAME,
                    $urlLocale,
                    self::LOCALE_COOKIE_LIFETIME,
                    '/',           // Path - root so it's available for / redirect
                    null,          // Domain - null uses current domain
                    null,          // Secure - null auto-detects
                    false,         // HttpOnly - false so JS can read if needed
                    false,         // Raw
                    'Lax'          // SameSite - Lax for PWA compatibility
                ));
            }

            return $response;
        }

        return $next($request);
    }

    /**
     * Check if locale is active.
     */
    private function localeIsActive(string $locale): bool
    {
        // If we are running in demo mode, consider all locales active.
        if (config('default.app_demo')) {
            return true;
        }

        if (File::exists(lang_path().'/'.$locale.'/config.php')) {
            $config = require lang_path($locale.'/config.php');

            return array_key_exists('active', $config) && $config['active'];
        }

        return false;
    }

    /**
     * Check if the first URL segment should bypass locale validation.
     *
     * Certain routes are intentionally locale-agnostic (e.g., /r/{code} for
     * referral links) and handle their own locale detection/redirection.
     */
    private function shouldBypassLocaleValidation(string $segment): bool
    {
        return in_array($segment, $this->bypassPatterns, true);
    }
}
