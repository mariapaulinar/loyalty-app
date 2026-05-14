<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Staff Authentication API Controller
 * Handles login, logout, and profile operations for staff members.
 * Staff members are the front-line operators at points of sale.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Staff Authentication API Controller
 *
 * Provides secure authentication endpoints for staff members to
 * log in via mobile POS apps or other integrations.
 */
class StaffAuthController extends Controller
{
    /**
     * Authenticate a staff member and return an API token.
     *
     * @OA\Post(
     *     path="/{locale}/v1/staff/login",
     *     operationId="staffLogin",
     *     tags={"Staff"},
     *     summary="Staff login",
     *     description="Authenticate a staff member and receive an API token for subsequent requests.",
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", description="Staff email", example="staff@example.com"),
     *             @OA\Property(property="password", type="string", format="password", description="Staff password", example="welcome3210")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Login successful", @OA\JsonContent(
     *         @OA\Property(property="token", type="string", description="API Bearer token", example="1|abc123..."),
     *         @OA\Property(property="staff", ref="#/components/schemas/StaffMember")
     *     )),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login(string $locale, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $staff = Staff::where('email', $validated['email'])
            ->where('is_active', true)
            ->first();

        if (! $staff || ! Hash::check($validated['password'], $staff->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Update login stats
        $staff->number_of_times_logged_in = ($staff->number_of_times_logged_in ?? 0) + 1;
        $staff->last_login_at = now();
        $staff->save();

        // Create API token
        $token = $staff->createToken('staff-api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'staff' => $staff->hideForPublic(),
        ]);
    }

    /**
     * Log out the current staff member.
     *
     * @OA\Post(
     *     path="/{locale}/v1/staff/logout",
     *     operationId="staffLogout",
     *     tags={"Staff"},
     *     summary="Staff logout",
     *     description="Invalidate the current API token and log out.",
     *     security={{"staff_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\Response(response=200, description="Logout successful", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Successfully logged out")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logout(string $locale, Request $request): JsonResponse
    {
        $request->user('staff_api')->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Get the authenticated staff member's profile.
     *
     * @OA\Get(
     *     path="/{locale}/v1/staff",
     *     operationId="getStaffProfile",
     *     tags={"Staff"},
     *     summary="Get staff profile",
     *     description="Retrieve the authenticated staff member's profile including their assigned club.",
     *     security={{"staff_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\Response(response=200, description="Staff profile", @OA\JsonContent(ref="#/components/schemas/StaffMember")),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getStaff(string $locale, Request $request): JsonResponse
    {
        $staff = $request->user('staff_api');

        return response()->json($staff->load('club')->hideForPublic());
    }
}
