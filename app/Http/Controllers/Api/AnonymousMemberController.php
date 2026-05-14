<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2026 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * API endpoints for device-bound anonymous member session management.
 * Enables the "Brawl Stars" frictionless authentication model.
 *
 * Endpoints:
 * - POST /api/v1/member/init - Initialize or retrieve session by device UUID
 * - POST /api/v1/member/session/switch - Switch device to different member by code
 * - POST /api/v1/member/session/link-email - Upgrade anonymous to registered
 *
 * @see App\Services\Member\AnonymousMemberService
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\Member\AnonymousMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AnonymousMemberController extends Controller
{
    public function __construct(
        private readonly AnonymousMemberService $service
    ) {}

    /**
     * Initialize or retrieve a member session based on device UUID.
     * Creates a new anonymous member if anonymous mode is enabled
     * and no member exists for this device.
     *
     * POST /api/v1/member/init
     *
     * Request Body:
     * {
     *   "device_uuid": "550e8400-e29b-41d4-a716-446655440000"
     * }
     *
     * Response (200):
     * {
     *   "success": true,
     *   "is_new": false,
     *   "member": {
     *     "id": "uuid",
     *     "code": "4K7X",
     *     "display_name": "Guest 4K7X",
     *     "is_anonymous": true,
     *     "unique_identifier": "abc123def456"
     *   }
     * }
     */
    public function init(Request $request): JsonResponse
    {
        // Check if feature is enabled
        if (! $this->service->isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => trans('common.anonymous_disabled'),
                'requires_login' => true,
            ], 400);
        }

        $request->validate([
            'device_uuid' => 'required|uuid',
        ]);

        $deviceUuid = $request->input('device_uuid');

        // Check if member already exists for this device
        $existing = Member::findByDeviceUuid($deviceUuid);
        $isNew = is_null($existing);

        // Initialize or create session
        $member = $this->service->initSession($deviceUuid);

        return response()->json([
            'success' => true,
            'is_new' => $isNew,
            'member' => [
                'id' => $member->id,
                'code' => $member->device_code,
                'display_name' => $member->getDisplayNameFormatted(),
                'is_anonymous' => $member->isAnonymous(),
                'unique_identifier' => $member->unique_identifier,
            ],
        ]);
    }

    /**
     * Switch device to a different member account.
     * Allows users to transfer their data to a new device
     * by entering their device code.
     *
     * POST /api/v1/member/session/switch
     *
     * Request Body:
     * {
     *   "code": "4K7X",
     *   "device_uuid": "550e8400-e29b-41d4-a716-446655440000"
     * }
     */
    public function switch(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|min:4|max:12',
            'device_uuid' => 'required|uuid',
        ]);

        $result = $this->service->switchToMember(
            $request->input('code'),
            $request->input('device_uuid')
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 404);
        }

        $member = $result['member'];

        return response()->json([
            'success' => true,
            'device_uuid' => $result['device_uuid'],
            'member' => [
                'id' => $member->id,
                'code' => $member->device_code,
                'display_name' => $member->getDisplayNameFormatted(),
                'is_anonymous' => $member->isAnonymous(),
                'unique_identifier' => $member->unique_identifier,
            ],
        ]);
    }

    /**
     * Link email to an anonymous member (upgrade account).
     *
     * POST /api/v1/member/session/link-email
     *
     * Request Body:
     * {
     *   "device_uuid": "550e8400-e29b-41d4-a716-446655440000",
     *   "email": "user@example.com",
     *   "name": "John Doe"
     * }
     */
    public function linkEmail(Request $request): JsonResponse
    {
        $request->validate([
            'device_uuid' => 'required|uuid',
            'email' => 'required|email|max:128',
            'name' => 'nullable|string|max:128',
        ]);

        $member = Member::findByDeviceUuid($request->input('device_uuid'));

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => trans('common.session_not_found'),
            ], 404);
        }

        if ($member->isRegistered()) {
            return response()->json([
                'success' => false,
                'message' => trans('common.already_registered'),
            ], 400);
        }

        try {
            $member = $this->service->linkEmail(
                $member,
                $request->input('email'),
                $request->input('name')
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => trans('validation.unique', ['attribute' => 'email']),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'member' => [
                'id' => $member->id,
                'code' => $member->device_code,
                'email' => $member->email,
                'display_name' => $member->getDisplayNameFormatted(),
                'is_anonymous' => false,
            ],
        ]);
    }

    /**
     * Get current session info for a device.
     *
     * GET /api/v1/member/session
     */
    public function session(Request $request): JsonResponse
    {
        $request->validate([
            'device_uuid' => 'required|uuid',
        ]);

        $member = Member::findByDeviceUuid($request->input('device_uuid'));

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => trans('common.session_not_found'),
                'has_session' => false,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'has_session' => true,
            'member' => [
                'id' => $member->id,
                'code' => $member->device_code,
                'display_name' => $member->getDisplayNameFormatted(),
                'is_anonymous' => $member->isAnonymous(),
                'unique_identifier' => $member->unique_identifier,
                'email' => $member->email,
            ],
        ]);
    }
}
