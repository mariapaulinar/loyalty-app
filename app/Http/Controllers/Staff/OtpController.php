<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Handles OTP authentication for the Staff guard.
 * Provides passwordless login via 6-digit email codes.
 * Supports club disambiguation when the same email exists in multiple clubs.
 */

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesOtpAuthentication;
use App\Http\Requests\Auth\CheckEmailRequest;
use App\Http\Requests\Auth\OtpSendRequest;
use App\Http\Requests\Auth\OtpVerifyRequest;
use App\Services\Auth\OtpService;
use App\Services\Staff\StaffService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OtpController extends Controller
{
    use HandlesOtpAuthentication {
        checkEmail as protected traitCheckEmail;
        sendOtp as protected traitSendOtp;
        verifyOtp as protected traitVerifyOtp;
        resendOtp as protected traitResendOtp;
    }

    /**
     * Get the guard name for this controller.
     */
    protected function getGuard(): string
    {
        return 'staff';
    }

    /**
     * Get the route prefix for this guard.
     */
    protected function getRoutePrefix(): string
    {
        return 'staff';
    }

    /**
     * Get the view prefix for this guard.
     */
    protected function getViewPrefix(): string
    {
        return 'staff';
    }

    /**
     * Get the dashboard route name for successful login.
     */
    protected function getDashboardRoute(): string
    {
        return 'staff.index';
    }

    /**
     * Check if user exists and return club options when email spans multiple clubs.
     */
    public function checkEmail(CheckEmailRequest $request, OtpService $otpService): JsonResponse
    {
        $email = $request->validated()['email'];
        $staffService = resolve(StaffService::class);
        $matches = $staffService->findActiveMatchesByEmail($email);

        session()->put('otp_email', $email);
        session()->put('otp_guard', $this->getGuard());

        if ($matches->isEmpty()) {
            session()->forget('staff_login_club_id');

            return response()->json([
                'exists' => false,
                'has_password' => false,
                'multiple_accounts' => false,
                'email' => $email,
            ]);
        }

        $clubs = $matches->map(function ($staff) {
            return [
                'id' => $staff->club_id,
                'name' => $staff->club ? $staff->club->name : trans('common.club'),
                'has_password' => ! empty($staff->password),
            ];
        })->values()->all();

        $multipleAccounts = $matches->count() > 1;

        if ($multipleAccounts) {
            session()->forget('staff_login_club_id');
        } else {
            session()->put('staff_login_club_id', $matches->first()->club_id);
        }

        return response()->json([
            'exists' => true,
            'has_password' => $matches->contains(fn ($staff) => ! empty($staff->password)),
            'multiple_accounts' => $multipleAccounts,
            'club_id' => $multipleAccounts ? null : $matches->first()->club_id,
            'clubs' => $multipleAccounts ? $clubs : [],
            'email' => $email,
        ]);
    }

    /**
     * Send OTP code to user's email (requires club context for staff).
     */
    public function sendOtp(OtpSendRequest $request, OtpService $otpService): JsonResponse|RedirectResponse
    {
        $email = $request->validated()['email'];
        $clubId = $request->input('club_id') ?? session('staff_login_club_id');

        if (! $clubId) {
            throw ValidationException::withMessages([
                'club_id' => trans('otp.step_club_required'),
            ]);
        }

        $staff = resolve(StaffService::class)->findActiveByEmailAndClub($email, $clubId);

        if (! $staff) {
            $message = trans('otp.user_not_found');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'account_exists' => false,
                ], 422);
            }

            return redirect()
                ->back()
                ->with('error', $message)
                ->withInput(['email' => $email]);
        }

        session()->put('otp_email', $email);
        session()->put('staff_login_club_id', $clubId);

        $request->merge(['club_id' => $clubId]);

        return $this->traitSendOtp($request, $otpService);
    }

    /**
     * Verify OTP code and log in the correct staff member for the selected club.
     */
    public function verifyOtp(OtpVerifyRequest $request, OtpService $otpService): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];
        $code = $validated['code'];
        $clubId = session('staff_login_club_id');

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

        if (! $clubId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => trans('otp.step_club_required'),
                ], 422);
            }

            return redirect()
                ->route($this->getLoginRoute())
                ->with('error', trans('otp.step_club_required'));
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

        $staff = resolve(StaffService::class)->findActiveByEmailAndClub($email, $clubId);

        if (! $staff) {
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

        $staff->email_verified_at = $staff->email_verified_at ?? Carbon::now('UTC');
        $staff->number_of_times_logged_in++;
        $staff->last_login_at = Carbon::now('UTC');
        $staff->save();

        Auth::guard($this->getGuard())->login($staff, true);

        session()->forget(['otp_email', 'otp_guard', 'staff_login_club_id']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => trans('otp.login_success'),
                'redirect' => $this->getIntendedRedirect(),
            ]);
        }

        return redirect()->intended($this->getIntendedRedirect());
    }

    /**
     * Resend OTP code (requires club context in session).
     */
    public function resendOtp(Request $request, OtpService $otpService): JsonResponse
    {
        $email = session('otp_email');
        $clubId = session('staff_login_club_id');

        if (! $email || ! $clubId) {
            return response()->json([
                'success' => false,
                'message' => trans('otp.invalid_request'),
            ], 422);
        }

        $staff = resolve(StaffService::class)->findActiveByEmailAndClub($email, $clubId);

        if (! $staff) {
            return response()->json([
                'success' => false,
                'message' => trans('otp.user_not_found'),
                'account_exists' => false,
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
}
