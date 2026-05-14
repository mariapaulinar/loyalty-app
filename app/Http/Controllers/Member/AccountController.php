<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Account Controller
 *
 * Handles account-level operations for members, including:
 * - Account switching via member code
 * - Account login via email + OTP verification
 *
 * @package App\Http\Controllers\Member
 */
class AccountController extends Controller
{
    /**
     * Switch the current device to a different member account.
     *
     * Validates the provided member code, authenticates the user as that member,
     * and updates the device_uuid cookie to link this device to the new account.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function switch(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $code = strtoupper(trim($request->input('code')));
        $currentMember = auth('member')->user();

        // Prevent switching to the same account
        if ($currentMember && strtoupper($currentMember->device_code) === $code) {
            return response()->json([
                'success' => false,
                'message' => trans('common.switch_account.same_account_error'),
            ], 400);
        }

        // Find the target member by their device code (e.g., "NXHYGH")
        $targetMember = Member::findByDeviceCode($code);

        if (!$targetMember) {
            return response()->json([
                'success' => false,
                'message' => trans('common.switch_account.code_not_found'),
            ], 404);
        }

        // Check if target member is active
        if (!$targetMember->is_active) {
            return response()->json([
                'success' => false,
                'message' => trans('common.switch_account.account_inactive'),
            ], 403);
        }

        // Log out the current member session
        Auth::guard('member')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Log in as the target member
        Auth::guard('member')->login($targetMember);

        // Get or generate the device UUID for the new member
        $deviceUuid = $targetMember->device_uuid;
        
        if (!$deviceUuid) {
            // Generate a new device UUID if the member doesn't have one
            $deviceUuid = Str::uuid()->toString();
            $targetMember->update(['device_uuid' => $deviceUuid]);
        }

        // Queue the cookie to be set (1 year expiry)
        Cookie::queue('member_device_uuid', $deviceUuid, 60 * 24 * 365, '/', null, false, false);

        return response()->json([
            'success' => true,
            'message' => trans('common.switch_account.success', ['code' => $targetMember->device_code]),
            'device_uuid' => $deviceUuid,
            'member_id' => $targetMember->id,
            'member_code' => $targetMember->device_code,
        ]);
    }

    /**
     * Send an OTP code to an email address for account login.
     *
     * The member enters an email address of an existing account they own.
     * An OTP is sent to that email so they can prove ownership.
     */
    public function sendLoginOtp(Request $request, OtpService $otpService): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:120'],
        ]);

        $email = strtolower(trim($request->input('email')));

        // Check that this email belongs to an existing active member
        $targetMember = Member::where('email', $email)
            ->where('is_active', true)
            ->first();

        if (!$targetMember) {
            return response()->json([
                'success' => false,
                'message' => trans('common.switch_account.email_not_found'),
            ], 404);
        }

        // Prevent login to the same account
        $currentMember = auth('member')->user();
        if ($currentMember && $currentMember->id === $targetMember->id) {
            return response()->json([
                'success' => false,
                'message' => trans('common.switch_account.same_account_error'),
            ], 400);
        }

        // Send OTP to the email
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

    /**
     * Verify the OTP code and log in as the target member.
     *
     * After the member proves ownership of the email via OTP,
     * switch the session to the target account.
     */
    public function verifyLoginOtp(Request $request, OtpService $otpService): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:120'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $email = strtolower(trim($request->input('email')));

        // Verify the OTP
        $result = $otpService->verify(
            identifier: $email,
            code: $request->input('code'),
            purpose: 'login',
            guard: 'member'
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? trans('common.error_occurred'),
                'locked' => $result['locked'] ?? false,
                'expired' => $result['expired'] ?? false,
            ], 422);
        }

        // Find the target member
        $targetMember = Member::where('email', $email)
            ->where('is_active', true)
            ->first();

        if (!$targetMember) {
            return response()->json([
                'success' => false,
                'message' => trans('common.switch_account.email_not_found'),
            ], 404);
        }

        // Switch session to target member
        Auth::guard('member')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Auth::guard('member')->login($targetMember);

        // Get or generate the device UUID
        $deviceUuid = $targetMember->device_uuid;

        if (!$deviceUuid) {
            $deviceUuid = Str::uuid()->toString();
            $targetMember->update(['device_uuid' => $deviceUuid]);
        }

        Cookie::queue('member_device_uuid', $deviceUuid, 60 * 24 * 365, '/', null, false, false);

        return response()->json([
            'success' => true,
            'message' => trans('common.switch_account.email_login_success'),
            'device_uuid' => $deviceUuid,
            'redirect' => route('member.cards'),
        ]);
    }
}
