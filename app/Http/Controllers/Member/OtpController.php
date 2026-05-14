<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Handles OTP authentication for the Member guard.
 * Provides passwordless login and registration via 6-digit email codes.
 *
 * Login Routes:
 * - POST /login/check     - Check if email exists (AJAX)
 * - POST /login/otp/send  - Send OTP code to email
 * - GET  /login/otp/verify - Show OTP verification form
 * - POST /login/otp/verify - Verify OTP and log in
 * - POST /login/otp/resend - Resend OTP code (AJAX)
 *
 * Registration Routes:
 * - POST /register           - Submit registration and send OTP
 * - GET  /register/verify    - Show registration OTP verification form
 * - POST /register/verify    - Verify OTP and complete registration
 * - POST /register/resend    - Resend registration OTP (AJAX)
 */

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesOtpAuthentication;
use App\Http\Requests\Auth\OtpVerifyRequest;
use App\Http\Requests\Member\RegistrationRequest;
use App\Services\Auth\OtpService;
use App\Services\Member\AuthService;
use App\Services\Member\MemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OtpController extends Controller
{
    use HandlesOtpAuthentication;

    /**
     * Get the guard name for this controller.
     */
    protected function getGuard(): string
    {
        return 'member';
    }

    /**
     * Get the route prefix for this guard.
     */
    protected function getRoutePrefix(): string
    {
        return 'member';
    }

    /**
     * Get the view prefix for this guard.
     */
    protected function getViewPrefix(): string
    {
        return 'member';
    }

    /**
     * Get the dashboard route name for successful login.
     */
    protected function getDashboardRoute(): string
    {
        return 'member.cards';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRATION WITH OTP
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Handle registration form submission and send OTP.
     */
    public function postRegister(
        RegistrationRequest $request,
        AuthService $authService,
        MemberService $memberService,
        OtpService $otpService
    ): RedirectResponse {
        $input = $request->validated();
        $email = strtolower(trim($input['email']));

        // Check if email already exists
        $existingMember = $memberService->findByEmail($email);
        if ($existingMember) {
            // If already registered, redirect to login
            return redirect()
                ->route('member.login')
                ->with('info', trans('otp.registration_email_exists'));
        }

        // Create member without password
        $member = $authService->registerWithOtp($input);

        // Store registration data in session
        session([
            'otp_registration' => [
                'member_id' => $member->id,
                'email' => $email,
                'from' => $input['from'] ?? null,
            ],
        ]);

        // Send OTP for email verification
        $result = $otpService->send(
            identifier: $email,
            identifierType: 'email',
            purpose: 'verify_email',
            guard: 'member'
        );

        if (! $result['success']) {
            // Cleanup: delete the member if OTP send fails
            $member->delete();
            session()->forget('otp_registration');

            return redirect()
                ->route('member.register')
                ->with('error', $result['message']);
        }

        return redirect()->route('member.register.otp.verify');
    }

    /**
     * Show the registration OTP verification form.
     */
    public function showRegisterVerify(OtpService $otpService): View|RedirectResponse
    {
        $registration = session('otp_registration');

        if (! $registration || ! isset($registration['email'])) {
            return redirect()->route('member.register');
        }

        $email = $registration['email'];
        $cooldown = $otpService->getResendCooldown($email, 'verify_email', 'member');

        return view('member.auth.register-verify', [
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
        MemberService $memberService
    ): RedirectResponse {
        $registration = session('otp_registration');

        if (! $registration || ! isset($registration['member_id'])) {
            return redirect()
                ->route('member.register')
                ->with('error', trans('otp.invalid_request'));
        }

        $email = $registration['email'];
        $code = $request->validated('code');

        // Verify OTP
        $result = $otpService->verify(
            identifier: $email,
            code: $code,
            purpose: 'verify_email',
            guard: 'member'
        );

        if (! $result['success']) {
            return redirect()
                ->route('member.register.otp.verify')
                ->with('error', $result['message']);
        }

        // Find the member and complete registration
        $member = $memberService->find($registration['member_id']);

        if (! $member) {
            session()->forget('otp_registration');

            return redirect()
                ->route('member.register')
                ->with('error', trans('otp.invalid_request'));
        }

        // Complete registration (verify email, set login stats)
        $authService->completeOtpRegistration($member);

        // Handle Referral (if user came from referral link)
        if (session()->has('referral_code_id')) {
            try {
                $referralCode = \App\Models\MemberReferralCode::find(session('referral_code_id'));

                if ($referralCode) {
                    // Create pending referral
                    $referralService = app(\App\Services\ReferralService::class);
                    $referralService->createPendingReferral($referralCode, $member);

                    // Auto-follow the referee card
                    if (session()->has('referral_referee_card_id')) {
                        $refereeCardId = session('referral_referee_card_id');
                        $card = \App\Models\Card::find($refereeCardId);

                        if ($card) {
                            // Use DB::table directly to insert into pivot table
                            \DB::table('card_member')->insert([
                                'card_id' => $card->id,
                                'member_id' => $member->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    // Clear referral session data
                    session()->forget(['referral_code_id', 'referral_campaign_id', 'referral_referee_card_id']);
                }
            } catch (\Exception $e) {
                // Silently fail to not disrupt registration flow
            }
        }

        // Clear session data
        $from = $registration['from'] ?? null;
        session()->forget('otp_registration');

        // Log the member in
        Auth::guard('member')->login($member, true);

        // Execute any pending "Add to My Cards" actions
        $pendingActionService = resolve(\App\Services\Member\PendingCardActionService::class);
        $actionExecuted = $pendingActionService->execute($member);

        // Determine redirect URL - if action executed, go to My Cards
        $redirectUrl = $actionExecuted ? route('member.cards') : ($from ?: route('member.cards'));

        // If action was executed, show card_added toast, otherwise show registration success
        if ($actionExecuted) {
            return redirect($redirectUrl)
                ->with('toast', [
                    'type' => 'success',
                    'text' => trans('common.card_added'),
                ]);
        }

        return redirect($redirectUrl)
            ->with('success', trans('otp.registration_success'));
    }

    /**
     * Resend the registration OTP (AJAX).
     */
    public function resendRegisterOtp(Request $request, OtpService $otpService): \Illuminate\Http\JsonResponse
    {
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
            purpose: 'verify_email',
            guard: 'member'
        );

        return response()->json($result);
    }
}
