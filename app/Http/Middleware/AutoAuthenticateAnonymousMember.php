<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2026 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Middleware: Auto-Authenticate Anonymous Members
 *
 * Purpose:
 * Automatically creates and authenticates anonymous members for new visitors.
 * This is the heart of the "Brawl Stars" authentication model.
 *
 * Flow:
 * 1. Already logged in? → Continue (registered or anonymous)
 * 2. Has device_uuid cookie? → Find member & login
 * 3. Anonymous mode enabled + has timezone cookie? → Create new anonymous member & login
 * 4. No timezone cookie? → Continue as guest (bot protection)
 * 5. Anonymous mode disabled? → Continue as guest
 *
 * Bot Protection:
 * New member creation requires the member_time_zone cookie, which is set by
 * JavaScript on page load. Since bots don't execute JavaScript, they cannot
 * set this cookie and therefore cannot create fake member accounts.
 *
 * Key Principle:
 * Every HUMAN visitor becomes an authenticated member immediately.
 * No more guest vs. logged-in distinction — everyone has a wallet.
 *
 * @see App\Services\Member\AnonymousMemberService
 * @see App\Models\Member
 */

namespace App\Http\Middleware;

use App\Models\Member;
use App\Services\Member\AnonymousMemberService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class AutoAuthenticateAnonymousMember
{
    public function __construct(
        private readonly AnonymousMemberService $anonymousService
    ) {}

    /**
     * Handle an incoming request.
     *
     * This middleware ensures every visitor is authenticated:
     * - Registered members via traditional session
     * - Anonymous members via device UUID cookie
     * - New visitors get a fresh anonymous account
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ─────────────────────────────────────────────────────────────
        // STEP 1: Already authenticated via session?
        // ─────────────────────────────────────────────────────────────
        if (Auth::guard('member')->check()) {
            $member = Auth::guard('member')->user();
            
            // Enforce active status
            if (!$member->is_active) {
                Auth::guard('member')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('member.login');
            }
            
            // Check for forced logout of anonymous members
            // This is triggered when admin disables anonymous mode with "log out anonymous members" checked
            // The flag is cleared when anonymous mode is re-enabled
            $forcedLogoutAt = $this->anonymousService->getForcedLogoutTimestamp();
            if ($forcedLogoutAt && empty($member->email)) {
                // This is an anonymous member and forced logout is active → log them out
                Auth::guard('member')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return $next($request); // Continue as guest
            }
            
            return $next($request);
        }

        // ─────────────────────────────────────────────────────────────
        // STEP 2: Check if anonymous mode is enabled
        // ─────────────────────────────────────────────────────────────
        if (!$this->anonymousService->isEnabled()) {
            // Anonymous mode disabled — continue as guest (no auto-authentication)
            // Protected routes will use 'member.auth' middleware to require login
            return $next($request);
        }

        // ─────────────────────────────────────────────────────────────
        // STEP 3: Check for existing device UUID cookie
        // ─────────────────────────────────────────────────────────────
        $deviceUuid = $request->cookie('member_device_uuid');
        $timeZone = $request->cookie('member_time_zone'); // Set by JS: Intl.DateTimeFormat().resolvedOptions().timeZone
        $member = null;
        $isNewMember = false;

        if ($deviceUuid) {
            $member = Member::findByDeviceUuid($deviceUuid);
            
            // Validate member exists and is active
            if ($member && !$member->is_active) {
                $member = null; // Will create new member
            }

            // Update timezone if member exists and tz changed
            if ($member && $timeZone && $member->time_zone !== $timeZone) {
                $member->update(['time_zone' => $timeZone]);
            }
        }

        // ─────────────────────────────────────────────────────────────
        // STEP 4: Create new anonymous member if needed
        // ─────────────────────────────────────────────────────────────
        if (!$member) {
            // ─────────────────────────────────────────────────────────
            // BOT PROTECTION: Require JavaScript confirmation
            // ─────────────────────────────────────────────────────────
            // The member_time_zone cookie is set by JavaScript on page load.
            // Bots don't execute JavaScript, so they won't have this cookie.
            // This prevents bots from creating millions of fake member accounts.
            //
            // On first visit: Page loads with loading screen → JS sets cookie → 
            // reload → member created. The loading screen makes this seamless.
            if (!$timeZone) {
                // No timezone cookie = likely a bot or first page load before JS runs
                // Continue as guest; real users will see a brief loader then reload
                return $next($request);
            }
            
            // Generate new device UUID
            $deviceUuid = (string) Str::uuid();
            
            // Create anonymous member with browser timezone
            $member = $this->anonymousService->initSession($deviceUuid, $timeZone);
            $isNewMember = true;
        }

        // ─────────────────────────────────────────────────────────────
        // STEP 5: Log the member in
        // ─────────────────────────────────────────────────────────────
        Auth::guard('member')->login($member, remember: true);

        // Process the request
        $response = $next($request);

        // ─────────────────────────────────────────────────────────────
        // STEP 6: Set/refresh the device UUID cookie on every visit
        // ─────────────────────────────────────────────────────────────
        // Cookie expires 1 year from now (rolling expiry on each visit)
        $cookie = Cookie::create('member_device_uuid')
            ->withValue($deviceUuid)
            ->withExpires(time() + (365 * 24 * 60 * 60))
            ->withPath('/')
            ->withSecure($request->isSecure())
            ->withHttpOnly(true)
            ->withSameSite('Lax');

        $response->headers->setCookie($cookie);

        return $response;
    }
}
