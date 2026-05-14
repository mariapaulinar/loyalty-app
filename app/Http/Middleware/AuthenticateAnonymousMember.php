<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2026 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Middleware for anonymous member authentication.
 * Enables two authentication modes:
 * 
 * 1. TRADITIONAL: Laravel session-based auth (email/password login)
 * 2. ANONYMOUS: Device-bound auth via UUID cookie (no login required)
 *
 * Flow:
 * - Check if already authenticated via session → continue
 * - Check for device UUID cookie → auto-login anonymous member
 * - If anonymous mode enabled and new visitor → create anonymous member
 * - If anonymous mode disabled → redirect to login
 *
 * This implements the "Brawl Stars" authentication model:
 * Play first, register later.
 */

namespace App\Http\Middleware;

use App\Models\Member;
use App\Services\Member\AnonymousMemberService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAnonymousMember
{
    public function __construct(
        private readonly AnonymousMemberService $anonymousService
    ) {}

    /**
     * Handle an incoming request.
     *
     * Priority order:
     * 1. Session auth (registered member logged in)
     * 2. Cookie auth (anonymous member with device_uuid)
     * 3. Create new anonymous member (if enabled)
     * 4. Redirect to login (if anonymous disabled)
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ─────────────────────────────────────────────────────────────
        // PRIORITY 1: Traditional session authentication
        // ─────────────────────────────────────────────────────────────
        if (Auth::guard('member')->check()) {
            $member = Auth::guard('member')->user();
            
            // Enforce active status
            if (!$member->is_active) {
                Auth::guard('member')->logout();
                return redirect()->route('member.login');
            }
            
            return $next($request);
        }

        // ─────────────────────────────────────────────────────────────
        // PRIORITY 2: Device UUID cookie authentication
        // ─────────────────────────────────────────────────────────────
        $deviceUuid = $request->cookie('member_device_uuid');
        
        if ($deviceUuid && $this->anonymousService->isEnabled()) {
            $member = Member::findByDeviceUuid($deviceUuid);
            
            if ($member && $member->is_active) {
                // Auto-login the anonymous member for this request
                // Note: We don't use Auth::login() to avoid session creation
                // Instead, we bind the member to the request for downstream use
                $request->merge(['_anonymous_member' => $member]);
                
                // Also set it in the auth guard for compatibility
                Auth::guard('member')->setUser($member);
                
                return $next($request);
            }
        }

        // ─────────────────────────────────────────────────────────────
        // PRIORITY 3: Redirect to login (or show guest content)
        // ─────────────────────────────────────────────────────────────
        // For protected routes, redirect to login
        // For public routes with optional auth, continue as guest
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => trans('common.session_not_found'),
                'requires_login' => !$this->anonymousService->isEnabled(),
            ], 401);
        }

        return redirect()->guest(route('member.login'));
    }
}
