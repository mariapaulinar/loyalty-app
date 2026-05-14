<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    /**
     * Authenticate an administrator or manager.
     *
     * @OA\Post(
     *     path="/{locale}/v1/admin/login",
     *     operationId="loginAdmin",
     *     tags={"Admin"},
     *     summary="Authenticate an administrator or manager",
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
     *             @OA\Property(property="email", type="string", format="email", description="Admin email address", example="admin@example.com"),
     *             @OA\Property(property="password", type="string", format="password", description="Admin password (6-48 characters)", example="welcome3210")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Authentication successful",
     *
     *         @OA\JsonContent(ref="#/components/schemas/AdminLoginSuccess")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed or invalid credentials",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     *
     * @param  string  $locale  Locale setting (e.g., 'en-us')
     * @param  Request  $request  The incoming HTTP request instance.
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(string $locale, Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:96',
            'password' => 'required|min:6|max:48',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {
            $user = Auth::guard('admin')->user();
            $token = $user->createToken('AdminAPIToken')->plainTextToken;

            return response()->json(['token' => $token], 200);
        } else {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
    }

    /**
     * Deauthenticate an administrator or manager.
     *
     * The endpoint ("/{locale}/v1/admin/logout") revokes all access tokens for the authenticated admin.
     *
     * @OA\Post(
     *     path="/{locale}/v1/admin/logout",
     *     operationId="logoutAdmin",
     *     tags={"Admin"},
     *     summary="Deauthenticate an administrator or manager",
     *     description="Revokes all access tokens for the authenticated administrator or manager.",
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
     *         description="Admin logged out successfully",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *
     *     security={
     *         {"admin_auth_token": {}}
     *     }
     * )
     *
     * @param  string  $locale  Locale setting (e.g., 'en-us')
     * @param  Request  $request  The incoming HTTP request instance.
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(string $locale, Request $request)
    {
        // Retrieve admin
        $admin = $request->user('admin_api');

        // Revoke all tokens
        $admin->tokens()->delete();

        return response()->json(['message' => 'Successfully logged out'], 200);
    }

    /**
     * Retrieve authenticated administrator or manager data.
     *
     * @OA\Get(
     *     path="/{locale}/v1/admin",
     *     operationId="getAdmin",
     *     tags={"Admin"},
     *     summary="Retrieve authenticated administrator or manager data",
     *     description="Fetches data of the authenticated administrator or manager.",
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
     *         description="Admin data retrieved successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Admin")
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
     *         {"admin_auth_token": {}}
     *     }
     * )
     *
     * @param  string  $locale  Locale setting (e.g., 'en-us')
     * @param  Request  $request  The incoming HTTP request instance.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAdmin(string $locale, Request $request)
    {
        // Retrieve admin
        $admin = $request->user('admin_api');

        // Hide sensitive information before exposing data
        $admin->hideForPublic();

        return response()->json($admin, 200);
    }
}
