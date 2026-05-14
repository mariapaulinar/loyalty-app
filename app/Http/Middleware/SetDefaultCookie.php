<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetDefaultCookie
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $isCookieConsentEnabled = config('default.cookie_consent');
        if (! $request->hasCookie('cookie_consent') && $isCookieConsentEnabled) {
            return $response->withCookie(cookie('cookie_consent', '-1', 60 * 24 * 30)); // 30 days
        }

        return $response;
    }
}
