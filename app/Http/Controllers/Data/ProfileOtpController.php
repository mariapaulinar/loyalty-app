<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Handles OTP verification for profile updates across all guards.
 * This controller is used when a DataDefinition has 'editRequiresOtp' enabled.
 *
 * Flow:
 * 1. User initiates profile edit
 * 2. User clicks "Send verification code"
 * 3. OTP is sent to user's email (profile_update purpose)
 * 4. User enters the 6-digit code
 * 5. On successful verification, a token is stored in session
 * 6. Form submission validates the token
 */

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProfileOtpController extends Controller
{
    /**
     * The guard for authentication.
     */
    protected string $guard;

    /**
     * Set the guard based on the route.
     */
    public function __construct()
    {
        // Extract guard from route name (member.profile.otp.send -> member)
        $routeName = request()->route()?->getName() ?? '';
        $this->guard = explode('.', $routeName)[0] ?? 'member';
    }

    /**
     * Send OTP code for profile update verification.
     * 
     * If the user has an existing email, OTP is sent there (verify account ownership).
     * If the user has no email (anonymous member), OTP is sent to the new email from the form.
     */
    public function send(Request $request, OtpService $otpService): JsonResponse
    {
        $user = auth($this->guard)->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => trans('otp.user_not_found'),
            ], 401);
        }

        // Determine which email to send OTP to:
        // - If user has email: verify account ownership via current email
        // - If user has no email: verify new email address from form
        $targetEmail = $user->email;
        
        if (empty($targetEmail)) {
            // Anonymous member adding email for first time - get from request
            $request->validate([
                'email' => ['required', 'email', 'max:120'],
            ]);
            $targetEmail = $request->email;
        }

        $result = $otpService->send(
            identifier: $targetEmail,
            identifierType: 'email',
            purpose: 'profile_update',
            guard: $this->guard
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => trans('otp.code_sent_success', ['email' => $this->maskEmail($targetEmail)]),
                'resend_cooldown' => $result['cooldown_seconds'] ?? 60,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? trans('otp.send_failed'),
        ], 422);
    }

    /**
     * Verify OTP code and return a verification token.
     */
    public function verify(Request $request, OtpService $otpService): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = auth($this->guard)->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => trans('otp.user_not_found'),
            ], 401);
        }

        // Determine which email was used for OTP:
        // - If user has email: it was sent to current email
        // - If user has no email: it was sent to the new email from form
        $targetEmail = $user->email;
        
        if (empty($targetEmail)) {
            // Anonymous member verifying new email - get from request
            $request->validate([
                'email' => ['required', 'email', 'max:120'],
            ]);
            $targetEmail = $request->email;
        }

        $result = $otpService->verify(
            identifier: $targetEmail,
            code: $request->code,
            purpose: 'profile_update',
            guard: $this->guard
        );

        if ($result['success']) {
            // Generate a secure token and store in session
            $token = Str::random(64);
            $sessionKey = "otp_verified_{$this->guard}_profile_update";
            session()->put($sessionKey, $token);

            // Token expires in 10 minutes
            session()->put("{$sessionKey}_expires", now()->addMinutes(10));

            return response()->json([
                'success' => true,
                'message' => trans('otp.verification_success'),
                'token' => $token,
            ]);
        }

        // Check if the OTP is locked (too many failed attempts) or expired
        $isLocked = isset($result['locked']) && $result['locked'];
        $isExpired = isset($result['expired']) && $result['expired'];

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? trans('otp.code_invalid'),
            'locked' => $isLocked,
            'expired' => $isExpired,
        ], 422);
    }

    /**
     * Mask email for display (e.g., j***@example.com).
     */
    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }

        $local = $parts[0];
        $domain = $parts[1];

        if (strlen($local) <= 2) {
            $masked = $local[0].'***';
        } else {
            $masked = $local[0].str_repeat('*', min(strlen($local) - 2, 5)).substr($local, -1);
        }

        return $masked.'@'.$domain;
    }
}
