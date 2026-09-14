<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OtpVerifyRequest;
use App\Models\Member;
use App\Services\Auth\OtpService;
use App\Services\Member\AuthService;
use App\Services\Member\MemberService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Registration OTP flow for native apps — mirrors member.register.post + register/verify web.
 */
class MemberRegisterOtpApiController extends Controller
{
    public function start(
        Request $request,
        AuthService $authService,
        MemberService $memberService,
        OtpService $otpService
    ): JsonResponse {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:96', 'unique:members,email'],
            'name' => ['required', 'max:64'],
            'time_zone' => ['nullable', 'max:48'],
            'consent' => ['required', 'accepted'],
            'accepts_emails' => ['nullable', 'boolean'],
        ]);

        $email = strtolower(trim($validated['email']));

        if ($memberService->findByEmail($email)) {
            return response()->json([
                'success' => false,
                'message' => trans('otp.registration_email_exists'),
            ], 422);
        }

        $member = $authService->registerWithOtp([
            'name' => $validated['name'],
            'email' => $email,
            'time_zone' => $validated['time_zone'] ?? null,
            'accepts_emails' => (bool) ($validated['accepts_emails'] ?? false),
        ]);

        $result = $otpService->send(
            identifier: $email,
            identifierType: 'email',
            purpose: 'verify_email',
            guard: 'member'
        );

        if (! $result['success']) {
            $member->delete();

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'email' => $email,
            'message' => trans('otp.code_sent_success', ['email' => $email]),
        ]);
    }

    public function resend(Request $request, OtpService $otpService, MemberService $memberService): JsonResponse
    {
        $email = strtolower(trim($request->validate(['email' => 'required|email|max:96'])['email']));
        $member = $memberService->findByEmail($email);

        if (! $member || $member->email_verified_at !== null) {
            throw ValidationException::withMessages([
                'email' => [trans('otp.invalid_request')],
            ]);
        }

        $result = $otpService->send(
            identifier: $email,
            identifierType: 'email',
            purpose: 'verify_email',
            guard: 'member'
        );

        $status = $result['success'] ? 200 : 422;

        return response()->json($result, $status);
    }

    public function verify(
        OtpVerifyRequest $request,
        OtpService $otpService,
        AuthService $authService,
        MemberService $memberService
    ): JsonResponse {
        $validated = $request->validated();
        $email = $validated['email'];
        $code = $validated['code'];

        $result = $otpService->verify(
            identifier: $email,
            code: $code,
            purpose: 'verify_email',
            guard: 'member'
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'remaining_attempts' => $result['remaining_attempts'] ?? null,
            ], 422);
        }

        $member = $memberService->findByEmail($email);

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => trans('otp.invalid_request'),
            ], 422);
        }

        $authService->completeOtpRegistration($member);
        $token = $member->createToken('MemberAPIToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
        ]);
    }
}
