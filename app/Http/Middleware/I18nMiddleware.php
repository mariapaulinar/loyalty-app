<?php

namespace App\Http\Middleware;

use App\Services\I18nService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * I18nMiddleware
 *
 * This middleware handles internationalization (i18n) data for the application.
 * It ensures that appropriate language, currency, and timezone information are available and consistent across the app.
 */
class I18nMiddleware
{
    /**
     * @var I18nService
     */
    protected $i18nService;

    /**
     * I18nMiddleware constructor.
     *
     * @param  I18nService  $i18nService  Service to handle i18n related data.
     */
    public function __construct(I18nService $i18nService)
    {
        $this->i18nService = $i18nService;
    }

    /**
     * Handle the incoming request.
     *
     * This function determines the authenticated user and their associated i18n data.
     * The i18n data is then shared with views and stored in the app instance for global access.
     * The function also adds i18n related headers to the response.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  Closure  $next  The next middleware in the stack.
     * @return Response The HTTP response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the fetchHeadersOnly parameter is set
        if ($request->has('fetchHeadersOnly') && $request->fetchHeadersOnly === 'true') {
            $i18n = $this->getI18nData($this->getAuthenticatedUser(), $request);
            $response = response()->noContent();
            $this->setI18nResponseHeaders($response, $i18n);

            return $response;
        }

        $user = $this->getAuthenticatedUser();
        $i18n = $this->getI18nData($user, $request);

        // Share languages with all views
        View::share('languages', $i18n['language']);

        // Make i18n data available throughout the app (e.g. app()->make('i18n')->time_zone)
        app()->instance('i18n', json_decode(json_encode($i18n), false));

        $response = $next($request);
        $this->setI18nResponseHeaders($response, $i18n);

        return $response;
    }

    /**
     * Add i18n related headers to the response.
     *
     * The headers include locale, language, country, currency, and timezone information.
     *
     * @param  Response  $response  The HTTP response.
     * @param  array  $i18n  The i18n data.
     */
    private function setI18nResponseHeaders(Response $response, array $i18n): void
    {
        $locale = $i18n['language']['current']['locale'] ?? config('app.locale');
        [$language, $country] = explode('_', $locale);

        $response->headers->set('X-App-Locale', $locale);
        $response->headers->set('X-App-Language', $language);
        $response->headers->set('X-App-Country', $country);
        $response->headers->set('X-App-Currency', $i18n['currency']['id']);
        $response->headers->set('X-App-TimeZone', $i18n['time_zone']);
    }

    /**
     * Identify the authenticated user from the possible guards.
     *
     * @return null|\Illuminate\Contracts\Auth\Authenticatable The authenticated user or null if none.
     */
    protected function getAuthenticatedUser()
    {
        // During installation, database tables may not exist yet.
        // Wrap auth checks in try-catch to gracefully handle this.
        try {
            $guards = ['admin', 'partner', 'affiliate', 'staff', 'member'];

            // Get the second URL segment
            $segment = request()->segment(2);

            // If the segment matches a guard name and the user is authenticated under that guard, return the user
            if (! empty($segment) && in_array($segment, $guards) && auth($segment)->check()) {
                return auth($segment)->user();
            }

            // If no match found, no segment exists, or user is not authenticated under the matched guard,
            // check under 'member' guard
            if (auth('member')->check()) {
                return auth('member')->user();
            }
        } catch (\Throwable $e) {
            // Database not ready (e.g., during installation) - return null
            return null;
        }

        return null;
    }

    /**
     * Prepare i18n data for the authenticated user or default settings.
     *
     * @param  null|\Illuminate\Contracts\Auth\Authenticatable  $user  The authenticated user.
     * @param  Request  $request  The incoming HTTP request.
     * @return array The i18n data.
     */
    protected function getI18nData($user, Request $request): array
    {
        // Use user's preferences if available, else use default settings
        $currency_code = $user ? $user->currency : config('default.currency');
        $time_zone = $user ? $user->time_zone : config('default.time_zone');

        return [
            'language' => $this->i18nService->getAllTranslations(null, $request),
            'currency' => $this->i18nService->getCurrencyDetails($currency_code),
            'time_zone' => $time_zone,
        ];
    }
}
