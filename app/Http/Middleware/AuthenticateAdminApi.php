<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AuthenticateAdminApi
{
    /**
     * Handle an incoming request by checking if the user is authenticated
     * and active based on the 'admin_api' guard.
     *
     * If the user is not authenticated, it returns an 'Unauthorized' error.
     * If the user is authenticated but not active, it returns a 'Admin is not active' error.
     * If both checks pass, the request is passed to the next middleware/handler in the stack.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next)
    {
        $guard = Auth::guard('admin_api');

        if (! $guard->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (! $guard->user()->is_active) {
            return response()->json(['error' => 'Admin is not active'], 401);
        }

        return $next($request);
    }
}
