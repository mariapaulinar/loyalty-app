<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Trait providing OTP authentication methods shared across all guards.
 * Implements the unified two-step login flow with OTP support.
 *
 * Design Tenets:
 * - **DRY**: Single implementation shared by Member, Staff, Partner, Admin
 * - **Flexible**: Supports both password and OTP authentication
 * - **Secure**: Proper rate limiting, session handling, audit logging
 */

namespace App\Http\Controllers\Traits;

use App\Http\Requests\Auth\CheckEmailRequest;
use App\Http\Requests\Auth\OtpSendRequest;
use App\Http\Requests\Auth\OtpVerifyRequest;
use App\Services\Auth\OtpService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

trait HandlesOtpAuthentication
{
    /**
     * Get the guard name for this controller.
     * Must be implemented by the using class.
     */
    abstract protected function getGuard(): string;

    /**
     * Get the route prefix for this guard.
     * Must be implemented by the using class.
     */
    abstract protected function getRoutePrefix(): string;

    /**
     * Get the view prefix for this guard.
     * Must be implemented by the using class.
     */
    abstract protected function getViewPrefix(): string;

    /**
     * Get the dashboard route name for successful login.
     * Must be implemented by the using class.
     */
    abstract protected function getDashboardRoute(): string;

    /**
     * Get the index route name for this guard.
     */
    protected function getIndexRoute(): string
    {
        return $this->getRoutePrefix().'.index';
    }

    /**
     * Get the login route name for this guard.
     */
    protected function getLoginRoute(): string
    {
        return $this->getRoutePrefix().'.login';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 1: CHECK EMAIL
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check if user exists and has password (AJAX).
     */
    public function checkEmail(CheckEmailRequest $request, OtpService $otpService): JsonResponse
    {
        $email = $request->validated()['email'];
        $result = $otpService->checkUser($email, $this->getGuard());

        // Store email in session for subsequent steps
        session()->put('otp_email', $email);
        session()->put('otp_guard', $this->getGuard());

        return response()->json([
            'exists' => $result['exists'],
            'has_password' => $result['has_password'],
            'email' => $email,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 2: SEND OTP
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Send OTP code to user's email.
     */
    public function sendOtp(OtpSendRequest $request, OtpService $otpService): JsonResponse|RedirectResponse
    {
        $email = $request->validated()['email'];

        // Verify email matches session
        if (session('otp_email') !== $email) {
            session()->put('otp_email', $email);
        }

        // Check if user exists before sending OTP
        $userCheck = $otpService->checkUser($email, $this->getGuard());

        if (! $userCheck['exists']) {
            // User doesn't exist - suggest registration (conditionally)
            $guard = $this->getRoutePrefix();
            $registerRoute = null;

            // Determine if we should show a registration link
            if ($guard === 'member') {
                // Members can always register
                $registerRoute = route('member.register', ['email' => $email]);
            } elseif ($guard === 'partner' && config('default.partners_can_register', false)) {
                // Partners can register only if enabled in config
                $registerRoute = route('partner.register', ['email' => $email]);
            }
            // Staff and Admin cannot register via public forms

            // Choose appropriate error message
            if ($registerRoute) {
                // Show registration link
                $message = trans('otp.account_not_found_create', [
                    'email' => $email,
                    'register_url' => $registerRoute,
                ]);
            } else {
                // No registration link (staff/admin or partner registration disabled)
                $message = trans('otp.user_not_found');
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'account_exists' => false,
                    'register_url' => $registerRoute,
                ], 422);
            }

            return redirect()
                ->back()
                ->with('error', $message)
                ->withInput(['email' => $email]);
        }

        // User exists - send OTP
        $result = $otpService->send(
            identifier: $email,
            identifierType: 'email',
            purpose: 'login',
            guard: $this->getGuard()
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()
                ->route($this->getRoutePrefix().'.login.otp.verify')
                ->with('success', trans('otp.code_sent_success', ['email' => $this->maskEmail($email)]));
        }

        return redirect()
            ->back()
            ->with('error', $result['message'])
            ->withInput();
    }

    /**
     * Show OTP verification form.
     */
    public function showOtpVerify(Request $request, OtpService $otpService): View|RedirectResponse
    {
        $email = session('otp_email');

        if (! $email) {
            return redirect()
                ->route($this->getLoginRoute())
                ->with('error', trans('otp.invalid_request'));
        }

        $cooldown = $otpService->getResendCooldown($email, 'login', $this->getGuard());

        return view($this->getViewPrefix().'.auth.otp-verify', [
            'email' => $email,
            'maskedEmail' => $this->maskEmail($email),
            'cooldown' => $cooldown,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 3: VERIFY OTP
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Verify OTP code and log in user.
     */
    public function verifyOtp(OtpVerifyRequest $request, OtpService $otpService): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];
        $code = $validated['code'];

        // Verify email matches session
        if (session('otp_email') !== $email) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => trans('otp.invalid_request'),
                ], 422);
            }

            return redirect()
                ->route($this->getLoginRoute())
                ->with('error', trans('otp.invalid_request'));
        }

        $result = $otpService->verify(
            identifier: $email,
            code: $code,
            purpose: 'login',
            guard: $this->getGuard()
        );

        if (! $result['success']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'remaining_attempts' => $result['remaining_attempts'] ?? null,
                ], 422);
            }

            return redirect()
                ->back()
                ->with('error', $result['message'])
                ->withInput(['email' => $email]);
        }

        // Login successful - authenticate user
        $user = $result['user'];

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => trans('otp.user_not_found'),
                ], 422);
            }

            return redirect()
                ->route($this->getLoginRoute())
                ->with('error', trans('otp.user_not_found'));
        }

        // Update login stats
        $user->email_verified_at = $user->email_verified_at ?? Carbon::now('UTC');
        $user->number_of_times_logged_in++;
        $user->last_login_at = Carbon::now('UTC');
        $user->save();

        // Authenticate the user
        Auth::guard($this->getGuard())->login($user, true);

        // Clear OTP session data
        session()->forget(['otp_email', 'otp_guard']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => trans('otp.login_success'),
                'redirect' => $this->getIntendedRedirect(),
            ]);
        }

        return redirect()->intended($this->getIntendedRedirect());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RESEND OTP
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Resend OTP code (AJAX).
     */
    public function resendOtp(Request $request, OtpService $otpService): JsonResponse
    {
        $email = session('otp_email');

        if (! $email) {
            return response()->json([
                'success' => false,
                'message' => trans('otp.invalid_request'),
            ], 422);
        }

        // Check if user exists before resending OTP
        $userCheck = $otpService->checkUser($email, $this->getGuard());

        if (! $userCheck['exists']) {
            $guard = $this->getRoutePrefix();
            $registerRoute = null;

            // Determine if we should show a registration link
            if ($guard === 'member') {
                $registerRoute = route('member.register', ['email' => $email]);
            } elseif ($guard === 'partner' && config('default.partners_can_register', false)) {
                $registerRoute = route('partner.register', ['email' => $email]);
            }

            // Choose appropriate error message
            $message = $registerRoute
                ? trans('otp.account_not_found_create', [
                    'email' => $email,
                    'register_url' => $registerRoute,
                ])
                : trans('otp.user_not_found');

            return response()->json([
                'success' => false,
                'message' => $message,
                'account_exists' => false,
                'register_url' => $registerRoute,
            ], 422);
        }

        $result = $otpService->send(
            identifier: $email,
            identifierType: 'email',
            purpose: 'login',
            guard: $this->getGuard()
        );

        return response()->json($result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Mask email for display (u***@example.com).
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
            $masked = $local[0].str_repeat('*', min(strlen($local) - 2, 3)).substr($local, -1);
        }

        return $masked.'@'.$domain;
    }

    /**
     * Get the intended redirect URL after login.
     * Also executes any pending "Add to My Cards" actions for member guard.
     */
    protected function getIntendedRedirect(): string
    {
        // Execute pending card actions for member guard
        if ($this->getGuard() === 'member' && auth('member')->check()) {
            $pendingActionService = resolve(\App\Services\Member\PendingCardActionService::class);
            $actionExecuted = $pendingActionService->execute(auth('member')->user());

            if ($actionExecuted) {
                // Flash success toast for the executed action
                session()->flash('toast', [
                    'type' => 'success',
                    'text' => trans('common.card_added'),
                ]);
            }
        }

        $sessionFrom = session()->pull('from.'.$this->getGuard());

        return $sessionFrom ?? route($this->getDashboardRoute());
    }
}
