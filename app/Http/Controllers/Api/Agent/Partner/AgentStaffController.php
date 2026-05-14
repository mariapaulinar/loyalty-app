<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API: Staff management for partners.
 * Staff members operate loyalty cards (scan, award, redeem).
 *
 * @see RewardLoyalty-100b-phase2-core-endpoints.md §2.9
 */

namespace App\Http\Controllers\Api\Agent\Partner;

use App\Http\Controllers\Api\Agent\BaseAgentController;
use App\Http\Controllers\Api\Agent\Concerns\EnforcesPartnerGates;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AgentStaffController extends BaseAgentController
{
    use EnforcesPartnerGates;

    #[OA\Get(
        path: '/partner/staff',
        operationId: 'list_staff',
        summary: 'List all staff members for this partner',
        description: 'Returns paginated staff members owned by the authenticated partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Staff'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 25, minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of staff', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StaffMember')),
                new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
            ])),
            new OA\Response(response: 403, description: 'Insufficient scope', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read', 'write:staff']],
    )]
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read', 'write:staff')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        $staff = Staff::where('created_by', $partner->id)
            ->with('club:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());

        return $this->jsonSuccess([
            'data' => $staff->getCollection()->map(
                fn (Staff $staffMember) => $this->serializePartnerStaff($staffMember)
            )->values(),
            'pagination' => $this->paginationMeta($staff),
        ]);
    }

    #[OA\Get(
        path: '/partner/staff/{id}',
        operationId: 'get_staff_member',
        summary: 'Get a specific staff member',
        description: 'Returns a single staff member by ID.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Staff'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Staff member details', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/StaffMember'),
            ])),
            new OA\Response(response: 404, description: 'Staff member not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read', 'write:staff']],
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read', 'write:staff')) {
            return $denied;
        }

        $partner = $this->getPartner($request);
        $staff = Staff::where('id', $id)
            ->where('created_by', $partner->id)
            ->with('club:id,name')
            ->first();

        if (! $staff) {
            return $this->jsonNotFound('Staff member');
        }

        return $this->jsonSuccess([
            'data' => $this->serializePartnerStaff($staff),
        ]);
    }

    #[OA\Post(
        path: '/partner/staff',
        operationId: 'create_staff_member',
        summary: 'Create a new staff member',
        description: 'Creates a staff member who can perform loyalty operations.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Staff'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 120),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 120),
                    new OA\Property(property: 'password', type: 'string', minLength: 6, maxLength: 48),
                    new OA\Property(property: 'club_id', type: 'string', format: 'uuid', description: 'Assign to a specific club'),
                    new OA\Property(property: 'is_active', type: 'boolean', default: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Staff member created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/StaffMember'),
            ])),
            new OA\Response(response: 422, description: 'Validation error or limit reached', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:staff']],
    )]
    public function store(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:staff')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        if ($error = $this->checkLimit($partner, 'staff_members_limit', Staff::class, 'Staff members')) {
            return $error;
        }

        $payload = $request->all();

        if ($error = $this->rejectDeprecatedFields($payload, [
            'club_ids' => 'club_id',
        ])) {
            return $error;
        }

        $validator = Validator::make($payload, [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:120|unique:staff,email',
            'password' => 'required|string|min:6|max:48',
            'club_id' => 'nullable|uuid',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->jsonValidationError($validator->errors()->toArray());
        }

        // Validate club belongs to partner if provided
        $clubId = $validator->validated()['club_id'] ?? null;
        if ($clubId) {
            $club = $this->resolveClub($partner, $clubId);
            if ($club instanceof JsonResponse) {
                return $club;
            }
        }

        $validated = $validator->validated();

        $staff = Staff::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'club_id' => $clubId,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $partner->id,
        ]);

        return $this->jsonSuccess([
            'data' => $this->serializePartnerStaff($staff->load('club:id,name')),
        ], 201);
    }

    #[OA\Put(
        path: '/partner/staff/{id}',
        operationId: 'update_staff_member',
        summary: 'Update an existing staff member',
        description: 'Updates a staff member owned by the authenticated partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Staff'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string', maxLength: 120),
                new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 120),
                new OA\Property(property: 'password', type: 'string', minLength: 6, maxLength: 48),
                new OA\Property(property: 'club_id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'is_active', type: 'boolean'),
            ]),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Staff member updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/StaffMember'),
            ])),
            new OA\Response(response: 404, description: 'Staff member not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:staff']],
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:staff')) {
            return $denied;
        }

        $partner = $this->getPartner($request);
        $staff = Staff::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $staff) {
            return $this->jsonNotFound('Staff member');
        }

        $payload = $request->all();

        if ($error = $this->rejectDeprecatedFields($payload, [
            'club_ids' => 'club_id',
        ])) {
            return $error;
        }

        $validator = Validator::make($payload, [
            'name' => 'nullable|string|max:120',
            'email' => 'nullable|email|max:120|unique:staff,email,' . $staff->id,
            'password' => 'nullable|string|min:6|max:48',
            'club_id' => 'nullable|uuid',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->jsonValidationError($validator->errors()->toArray());
        }

        $validated = $validator->validated();

        $updateData = array_filter([
            'name' => $validated['name'] ?? null,
            'email' => $validated['email'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
            'updated_by' => $partner->id,
        ], fn ($v) => $v !== null);

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        // Update club assignment if provided
        if (array_key_exists('club_id', $validated)) {
            $clubId = $validated['club_id'];
            if ($clubId) {
                $club = $this->resolveClub($partner, $clubId);
                if ($club instanceof JsonResponse) {
                    return $club;
                }
            }
            $updateData['club_id'] = $clubId;
        }

        $staff->update($updateData);

        return $this->jsonSuccess([
            'data' => $this->serializePartnerStaff($staff->fresh()->load('club:id,name')),
        ]);
    }

    #[OA\Delete(
        path: '/partner/staff/{id}',
        operationId: 'delete_staff_member',
        summary: 'Delete a staff member',
        description: 'Permanently deletes a staff member owned by the authenticated partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Staff'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Staff member deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Staff member deleted.'),
            ])),
            new OA\Response(response: 404, description: 'Staff member not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:staff']],
    )]
    public function destroy(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:staff')) {
            return $denied;
        }

        $partner = $this->getPartner($request);
        $staff = Staff::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $staff) {
            return $this->jsonNotFound('Staff member');
        }

        $staff->delete();

        return $this->jsonSuccess(['message' => 'Staff member deleted.']);
    }

    private function serializePartnerStaff(Staff $staff): array
    {
        return [
            'id' => $staff->id,
            'club_id' => $staff->club_id,
            'club_name' => $staff->club?->name,
            'name' => $staff->name,
            'email' => $staff->email,
            'locale' => $staff->locale,
            'time_zone' => $staff->time_zone,
            'number_of_times_logged_in' => (int) $staff->number_of_times_logged_in,
            'last_login_at' => $staff->last_login_at
                ? Carbon::parse($staff->last_login_at)->toIso8601String()
                : null,
            'created_at' => $this->serializeDateTime($staff->created_at),
            'updated_at' => $this->serializeDateTime($staff->updated_at),
            'avatar' => $staff->avatar,
        ];
    }
}
