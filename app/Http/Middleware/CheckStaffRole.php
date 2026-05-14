<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Class CheckStaffRole
 *
 * Middleware to check if the authenticated staff has one of the specified roles.
 */
class CheckStaffRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next, ?int $role1 = null, ?int $role2 = null, ?int $role3 = null)
    {
        $auth = Auth::guard('staff');

        // Check if the user is authenticated and has one of the specified roles
        if ($auth->check() && $auth->user()->hasRole([$role1, $role2, $role3])) {
            return $next($request);
        }

        // Return an appropriate error response
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        abort(403);
    }
}
