<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PartnerAuthController extends Controller
{
    /**
     * Authenticate and log in a partner.
     *
     * @OA\Post(
     *     path="/{locale}/v1/partner/login",
     *     operationId="loginPartner",
     *     tags={"Partner"},
     *     summary="Authenticate a partner",
     *     description="Authenticate using email and password to obtain a bearer token for subsequent API requests. This endpoint does not require authentication.",
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="path",
     *         description="Locale code (e.g., `en-us`, `de-de`)",
     *         required=true,
     *
     *         @OA\Schema(type="string", default="en-us")
     *     ),
     *
     *     @OA\RequestBody(
     *         description="Login credentials",
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *
     *             @OA\Property(property="email", type="string", format="email", description="Partner email address", example="partner@example.com"),
     *             @OA\Property(property="password", type="string", format="password", description="Partner password (6-48 characters)", example="welcome3210")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Authentication successful",
     *
     *         @OA\JsonContent(ref="#/components/schemas/PartnerLoginSuccess")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed or invalid credentials",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:96',
            'password' => 'required|min:6|max:48',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::guard('partner')->attempt($credentials)) {
            $user = Auth::guard('partner')->user();
            $token = $user->createToken('PartnerAPIToken')->plainTextToken;

            return response()->json(['token' => $token], 200);
        } else {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
    }

    /**
     * Log out the authenticated partner.
     *
     * @OA\Post(
     *     path="/{locale}/v1/partner/logout",
     *     operationId="logoutPartner",
     *     tags={"Partner"},
     *     summary="Log out the authenticated partner",
     *     description="Revoke all access tokens for the authenticated partner and log them out.",
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="path",
     *         description="The locale (e.g., `en-us`)",
     *         required=true,
     *
     *         @OA\Schema(
     *           type="string",
     *           default="en-us"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Partner logged out successfully",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *
     *     security={
     *         {"partner_auth_token": {}}
     *     }
     * )
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Retrieve partner
        $partner = $request->user('partner_api');

        // Revoke all tokens
        $partner->tokens()->delete();

        return response()->json(['message' => 'Successfully logged out'], 200);
    }

    /**
     * Retrieve the authenticated partner's data.
     *
     * @OA\Get(
     *     path="/{locale}/v1/partner",
     *     operationId="getPartner",
     *     tags={"Partner"},
     *     summary="Retrieve authenticated partner's data",
     *     description="Retrieve the data of the authenticated partner.",
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="path",
     *         description="The locale (e.g., `en-us`)",
     *         required=true,
     *
     *         @OA\Schema(
     *           type="string",
     *           default="en-us"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Partner data retrieved successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Partner")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")
     *     ),
     *
     *     security={
     *         {"partner_auth_token": {}}
     *     }
     * )
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPartner(Request $request)
    {
        // Retrieve partner
        $partner = $request->user('partner_api');

        // Hide sensitive information before exposing data
        $partner->hideForPublic();

        return response()->json($partner, 200);
    }
}
