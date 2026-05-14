<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Handles OTP authentication for the Partner guard.
 * Provides passwordless login and registration via 6-digit email codes.
 *
 * Login Routes:
 * - POST /login/check     - Check if email exists (AJAX)
 * - POST /login/otp/send  - Send OTP code to email
 * - GET  /login/otp/verify - Show OTP verification form
 * - POST /login/otp/verify - Verify OTP and log in
 * - POST /login/otp/resend - Resend OTP code (AJAX)
 *
 * Registration Routes (when config('default.partners_can_register') is true):
 * - POST /register           - Submit registration and send OTP
 * - GET  /register/verify    - Show registration OTP verification form
 * - POST /register/verify    - Verify OTP and complete registration
 * - POST /register/resend    - Resend registration OTP (AJAX)
 */

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesOtpAuthentication;
use App\Http\Requests\Auth\OtpVerifyRequest;
use App\Services\Auth\OtpService;
use App\Services\Partner\AuthService;
use App\Services\Partner\PartnerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OtpController extends Controller
{
    use HandlesOtpAuthentication;

    /**
     * Get the guard name for this controller.
     */
    protected function getGuard(): string
    {
        return 'partner';
    }

    /**
     * Get the route prefix for this guard.
     */
    protected function getRoutePrefix(): string
    {
        return 'partner';
    }

    /**
     * Get the view prefix for this guard.
     */
    protected function getViewPrefix(): string
    {
        return 'partner';
    }

    /**
     * Get the dashboard route name for successful login.
     */
    protected function getDashboardRoute(): string
    {
        return 'partner.index';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRATION WITH OTP (when partners_can_register is enabled)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Show the registration OTP verification form.
     */
    public function showRegisterVerify(OtpService $otpService): View|RedirectResponse
    {
        // Check if registration is enabled
        if (! AuthService::isRegistrationEnabled()) {
            return redirect()->route('partner.login')
                ->with('error', trans('common.registration_disabled'));
        }

        $registration = session('otp_registration');

        if (! $registration || ! isset($registration['email'])) {
            return redirect()->route('partner.register');
        }

        $email = $registration['email'];
        $cooldown = $otpService->getResendCooldown($email, 'registration', 'partner');

        return view('partner.auth.register-verify', [
            'email' => $email,
            'maskedEmail' => $this->maskEmail($email),
            'cooldown' => $cooldown,
        ]);
    }

    /**
     * Verify the registration OTP and complete signup.
     */
    public function verifyRegisterOtp(
        OtpVerifyRequest $request,
        OtpService $otpService,
        AuthService $authService,
        PartnerService $partnerService
    ): RedirectResponse {
        // Check if registration is enabled
        if (! AuthService::isRegistrationEnabled()) {
            return redirect()->route('partner.login')
                ->with('error', trans('common.registration_disabled'));
        }

        $registration = session('otp_registration');

        if (! $registration || ! isset($registration['partner_id'])) {
            return redirect()
                ->route('partner.register')
                ->with('error', trans('otp.invalid_request'));
        }

        $email = $registration['email'];
        $code = $request->validated('code');

        // Verify OTP
        $result = $otpService->verify(
            identifier: $email,
            code: $code,
            purpose: 'registration',
            guard: 'partner'
        );

        if (! $result['success']) {
            return redirect()
                ->route('partner.register.otp.verify')
                ->with('error', $result['message']);
        }

        // Find the partner and complete registration
        $partner = $partnerService->find($registration['partner_id']);

        if (! $partner) {
            session()->forget('otp_registration');

            return redirect()
                ->route('partner.register')
                ->with('error', trans('otp.invalid_request'));
        }

        // Complete registration (verify email)
        $authService->completeOtpRegistration($partner);

        // Clear session data
        session()->forget('otp_registration');

        // Redirect to partner dashboard
        return redirect()->route('partner.index')
            ->with('success', trans('otp.registration_success'));
    }

    /**
     * Resend the registration OTP (AJAX).
     */
    public function resendRegisterOtp(Request $request, OtpService $otpService): JsonResponse
    {
        // Check if registration is enabled
        if (! AuthService::isRegistrationEnabled()) {
            return response()->json([
                'success' => false,
                'message' => trans('common.registration_disabled'),
            ], 403);
        }

        $registration = session('otp_registration');

        if (! $registration || ! isset($registration['email'])) {
            return response()->json([
                'success' => false,
                'message' => trans('otp.invalid_request'),
            ], 400);
        }

        $result = $otpService->send(
            identifier: $registration['email'],
            identifierType: 'email',
            purpose: 'registration',
            guard: 'partner'
        );

        return response()->json($result);
    }
}
