<?php

namespace App\Http\Controllers\Cookie;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CookieController
 *
 * Handles tasks related to user's cookie consent.
 */
class CookieController extends Controller
{
    /**
     * Update the user's cookie consent preference.
     *
     * This method sets the 'cookie_consent' cookie based on the user's choice.
     * The cookie will persist for a duration of 30 days.
     *
     * @param  string  $locale  The current locale.
     * @param  string  $value  The consent value ('true' or 'false').
     */
    public function setConsentCookie(string $locale, string $value): Response
    {
        return response()->json(['success' => true])->withCookie(cookie('cookie_consent', $value, 60 * 24 * 30)); // 30 days
    }

    /**
     * Determine if the user consents to cookies.
     */
    public static function userConsentsToCookies(): bool
    {
        if (! Config::get('default.cookie_consent')) {
            return true;
        }

        $cookieValue = request()->cookie('cookie_consent');

        if (is_null($cookieValue) || $cookieValue == '-1' || $cookieValue == '0') {
            return false;
        }

        return $cookieValue == '1';
    }
}
