<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Class CheckMemberRole
 *
 * Middleware to check if the authenticated member has one of the specified roles.
 */
class CheckMemberRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|null  ...$roles
     * @return mixed
     */
    public function handle($request, Closure $next, ...$roles)
    {
        $auth = Auth::guard('member');

        // Check if the user is authenticated and has one of the specified roles
        if ($auth->check() && $auth->user()->hasRole($roles)) {
            return $next($request);
        }

        // Return an appropriate error response
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        abort(403);
    }
}
