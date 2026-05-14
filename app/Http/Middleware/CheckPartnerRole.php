<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Class CheckPartnerRole
 *
 * Middleware to check if the authenticated partner has the required role(s).
 */
class CheckPartnerRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|null  $role1
     * @param  int|null  $role2
     * @param  int|null  $role3
     * @return mixed
     */
    public function handle($request, Closure $next, $role1 = null, $role2 = null, $role3 = null)
    {
        $auth = Auth::guard('partner');
        
        // Check if the user is authenticated and has the required role(s)
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
