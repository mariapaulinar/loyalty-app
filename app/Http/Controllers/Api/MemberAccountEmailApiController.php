<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\Auth\OtpService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Switch-account email OTP for native apps — mirrors member.account.login-email.* web routes.
 */
class MemberAccountEmailApiController extends Controller
{
    public function sendLoginOtp(Request $request, OtpService $otpService): JsonResponse
    {
        $email = strtolower(trim($request->validate([
            'email' => ['required', 'email', 'max:120'],
        ])['email']));

        $targetMember = Member::where('email', $email)->where('is_active', true)->first();

        if (! $targetMember) {
            return response()->json([
                'success' => false,
                'message' => trans('common.switch_account.email_not_found'),
            ], 404);
        }

        $currentMember = $request->user('member_api');

        if ($currentMember && $currentMember->id === $targetMember->id) {
            return response()->json([
                'success' => false,
                'message' => trans('common.switch_account.same_account_error'),
            ], 400);
        }

        $result = $otpService->send(
            identifier: $email,
            identifierType: 'email',
            purpose: 'login',
            guard: 'member'
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => trans('common.switch_account.email_otp_sent'),
                'resend_cooldown' => $result['cooldown_seconds'] ?? 60,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? trans('common.error_occurred'),
        ], 422);
    }

    public function verifyLoginOtp(Request $request, OtpService $otpService): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:120'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $email = strtolower(trim($validated['email']));

        $result = $otpService->verify(
            identifier: $email,
            code: $validated['code'],
            purpose: 'login',
            guard: 'member'
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? trans('common.error_occurred'),
            ], 422);
        }

        $targetMember = Member::where('email', $email)->where('is_active', true)->first();

        if (! $targetMember) {
            return response()->json([
                'success' => false,
                'message' => trans('common.switch_account.email_not_found'),
            ], 404);
        }

        $currentMember = $request->user('member_api');
        $currentMember?->tokens()->delete();

        $targetMember->email_verified_at = $targetMember->email_verified_at ?? Carbon::now('UTC');
        $targetMember->number_of_times_logged_in++;
        $targetMember->last_login_at = Carbon::now('UTC');
        $targetMember->save();

        $token = $targetMember->createToken('MemberAPIToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => trans('common.switch_account.email_login_success'),
            'token' => $token,
            'device_uuid' => $targetMember->device_uuid,
            'member_code' => $targetMember->device_code,
        ]);
    }
}
