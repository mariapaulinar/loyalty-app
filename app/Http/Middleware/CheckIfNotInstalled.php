<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Class CheckIfNotInstalled
 *
 * Middleware to check if the application is not installed.
 */
class CheckIfNotInstalled
{
    /**
     * Handle an incoming request.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the application is installed using the 'app_is_installed' configuration value
        if (config('default.app_is_installed')) {
            // Redirect to locale route if installed
            return redirect(route('redir.locale'), 301)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        return $next($request);
    }
}
