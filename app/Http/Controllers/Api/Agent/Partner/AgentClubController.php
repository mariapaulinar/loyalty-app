<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API: Club management for partners.
 * Clubs are organizational units that group cards, staff, and tiers.
 *
 * Mirror source: PartnerClubController + ClubDataDefinition
 * Ownership filter: Club::where('created_by', $partner->id)
 *
 * @see RewardLoyalty-100b-phase2-core-endpoints.md §2.1
 */

namespace App\Http\Controllers\Api\Agent\Partner;

use App\Http\Controllers\Api\Agent\BaseAgentController;
use App\Http\Controllers\Api\Agent\Concerns\EnforcesPartnerGates;
use App\Models\Club;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AgentClubController extends BaseAgentController
{
    use EnforcesPartnerGates;

    /**
     * GET /api/agent/v1/partner/clubs
     * Scope: read
     */
    #[OA\Get(
        path: '/partner/clubs',
        operationId: 'list_clubs',
        summary: 'List all clubs for this partner',
        description: 'Returns paginated clubs owned by the authenticated partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Clubs'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 25, minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of clubs', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Club')),
                new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
            ])),
            new OA\Response(response: 403, description: 'Insufficient scope', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read', 'write:clubs']],
    )]
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read', 'write:clubs')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        $clubs = Club::where('created_by', $partner->id)
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());

        return $this->jsonPaginated($clubs);
    }

    /**
     * GET /api/agent/v1/partner/clubs/{id}
     * Scope: read
     */
    #[OA\Get(
        path: '/partner/clubs/{id}',
        operationId: 'get_club',
        summary: 'Get a specific club',
        description: 'Returns a single club by ID. The club must belong to the authenticated partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Clubs'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Club details', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/Club'),
            ])),
            new OA\Response(response: 404, description: 'Club not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read', 'write:clubs']],
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read', 'write:clubs')) {
            return $denied;
        }

        $partner = $this->getPartner($request);
        $club = Club::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $club) {
            return $this->jsonNotFound('Club');
        }

        return $this->jsonResource($club);
    }

    /**
     * POST /api/agent/v1/partner/clubs
     * Scope: write:clubs
     */
    #[OA\Post(
        path: '/partner/clubs',
        operationId: 'create_club',
        summary: 'Create a new club',
        description: 'Creates a club owned by the authenticated partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Clubs'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 120, description: 'Club name'),
                    new OA\Property(property: 'is_active', type: 'boolean', default: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Club created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/Club'),
            ])),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:clubs']],
    )]
    public function store(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:clubs')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:120',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->jsonValidationError($validator->errors()->toArray());
        }

        $club = Club::create([
            'name' => $request->input('name'),
            'is_active' => $request->input('is_active', true),
            'created_by' => $partner->id,
        ]);

        return $this->jsonResource($club, 201);
    }

    /**
     * PUT /api/agent/v1/partner/clubs/{id}
     * Scope: write:clubs
     */
    #[OA\Put(
        path: '/partner/clubs/{id}',
        operationId: 'update_club',
        summary: 'Update an existing club',
        description: 'Updates a club owned by the authenticated partner. Only provided fields are updated.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Clubs'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string', maxLength: 120),
                new OA\Property(property: 'is_active', type: 'boolean'),
            ]),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Club updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/Club'),
            ])),
            new OA\Response(response: 404, description: 'Club not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:clubs']],
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:clubs')) {
            return $denied;
        }

        $partner = $this->getPartner($request);
        $club = Club::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $club) {
            return $this->jsonNotFound('Club');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:120',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->jsonValidationError($validator->errors()->toArray());
        }

        $club->update(array_filter([
            'name' => $request->input('name'),
            'is_active' => $request->input('is_active'),
            'updated_by' => $partner->id,
        ], fn ($v) => $v !== null));

        return $this->jsonResource($club->fresh());
    }

    /**
     * DELETE /api/agent/v1/partner/clubs/{id}
     * Scope: write:clubs
     */
    #[OA\Delete(
        path: '/partner/clubs/{id}',
        operationId: 'delete_club',
        summary: 'Delete a club',
        description: 'Deletes a club owned by the authenticated partner. Protected clubs cannot be deleted.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Clubs'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Club deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Club deleted.'),
            ])),
            new OA\Response(response: 404, description: 'Club not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
            new OA\Response(response: 422, description: 'Club is protected', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:clubs']],
    )]
    public function destroy(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:clubs')) {
            return $denied;
        }

        $partner = $this->getPartner($request);
        $club = Club::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $club) {
            return $this->jsonNotFound('Club');
        }

        // Prevent deleting undeletable clubs (e.g., the default club)
        if ($club->is_undeletable ?? false) {
            return $this->jsonError(
                code: 'RESOURCE_PROTECTED',
                message: 'This club cannot be deleted.',
                status: 422,
                retryStrategy: 'no_retry',
            );
        }

        $club->delete();

        return $this->jsonSuccess(['message' => 'Club deleted.']);
    }
}
