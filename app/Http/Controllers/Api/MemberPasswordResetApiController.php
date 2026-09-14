<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Member\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Password reset for native apps — mirrors member forgot/reset password web flow.
 */
class MemberPasswordResetApiController extends Controller
{
    public function forgot(Request $request, AuthService $authService): JsonResponse
    {
        $email = strtolower(trim($request->validate([
            'email' => ['required', 'email', 'max:96'],
        ])['email']));

        $member = $authService->sendResetPasswordLink($email);

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => trans('common.user_not_found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => trans('common.reset_link_has_been_sent_to_email', ['email' => $email]),
            'email' => $email,
        ]);
    }

    public function reset(Request $request, AuthService $authService): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:96'],
            'password' => ['required', 'string', 'min:6', 'max:48'],
            'expires' => ['required'],
            'signature' => ['required', 'string'],
        ]);

        $email = strtolower(trim($validated['email']));

        if (! $this->hasValidResetSignature($email, $validated['expires'], $validated['signature'])) {
            return response()->json([
                'success' => false,
                'message' => trans('common.unknown_error'),
            ], 422);
        }

        $updated = $authService->updatePassword($email, $validated['password']);

        if (! $updated) {
            return response()->json([
                'success' => false,
                'message' => trans('common.unknown_error'),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => trans('common.login_with_new_password'),
            'email' => $email,
        ]);
    }

    private function hasValidResetSignature(string $email, string $expires, string $signature): bool
    {
        $query = http_build_query([
            'email' => $email,
            'expires' => $expires,
            'signature' => $signature,
        ]);

        $check = Request::create(
            route('member.reset_password', [], true).'?'.$query,
            'GET'
        );

        return $check->hasValidSignature();
    }
}
