<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Class CheckIfInstalled
 *
 * Middleware to check if the application is installed.
 */
class CheckIfInstalled
{
    /**
     * Handle an incoming request.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the application is installed using the 'app_is_installed' configuration value
        if (! config('default.app_is_installed')) {
            // Redirect to installation if not installed
            return redirect(route('installation.index'), 301)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        return $next($request);
    }
}
