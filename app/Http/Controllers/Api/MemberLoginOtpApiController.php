<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CheckEmailRequest;
use App\Http\Requests\Auth\OtpSendRequest;
use App\Http\Requests\Auth\OtpVerifyRequest;
use App\Services\Auth\OtpService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

/**
 * Stateless OTP login for native apps — mirrors member web login/check + OTP flow.
 */
class MemberLoginOtpApiController extends Controller
{
    public function checkEmail(CheckEmailRequest $request, OtpService $otpService): JsonResponse
    {
        $email = $request->validated()['email'];
        $result = $otpService->checkUser($email, 'member');

        if (! $result['exists']) {
            return response()->json([
                'exists' => false,
                'has_password' => false,
                'email' => $email,
                'register_url' => route('member.login.register-redirect', ['email' => $email]),
            ]);
        }

        return response()->json([
            'exists' => true,
            'has_password' => $result['has_password'],
            'email' => $email,
        ]);
    }

    public function sendOtp(OtpSendRequest $request, OtpService $otpService): JsonResponse
    {
        $email = $request->validated()['email'];
        $check = $otpService->checkUser($email, 'member');

        if (! $check['exists']) {
            return response()->json([
                'success' => false,
                'message' => trans('otp.email_not_found_register'),
            ], 404);
        }

        $result = $otpService->send(
            identifier: $email,
            identifierType: 'email',
            purpose: 'login',
            guard: 'member'
        );

        $status = $result['success'] ? 200 : 422;

        return response()->json($result, $status);
    }

    public function verifyOtp(OtpVerifyRequest $request, OtpService $otpService): JsonResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];
        $code = $validated['code'];

        $result = $otpService->verify(
            identifier: $email,
            code: $code,
            purpose: 'login',
            guard: 'member'
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'remaining_attempts' => $result['remaining_attempts'] ?? null,
            ], 422);
        }

        $user = $result['user'] ?? null;

        if (! $user || (int) $user->is_active !== 1) {
            return response()->json([
                'success' => false,
                'message' => trans('otp.user_not_found'),
            ], 422);
        }

        $user->email_verified_at = $user->email_verified_at ?? Carbon::now('UTC');
        $user->number_of_times_logged_in++;
        $user->last_login_at = Carbon::now('UTC');
        $user->save();

        $token = $user->createToken('MemberAPIToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
        ]);
    }
}
