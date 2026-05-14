<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API: Tier CRUD for partners.
 * Tiers are loyalty levels within a club (Bronze, Silver, Gold).
 *
 * @see RewardLoyalty-100b-phase2-core-endpoints.md §2.8
 */

namespace App\Http\Controllers\Api\Agent\Partner;

use App\Http\Controllers\Api\Agent\BaseAgentController;
use App\Http\Controllers\Api\Agent\Concerns\EnforcesPartnerGates;
use App\Models\Tier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AgentTierController extends BaseAgentController
{
    use EnforcesPartnerGates;

    private function storeRules(): array
    {
        return [
            'club_id' => 'required|uuid|exists:clubs,id',
            'name' => 'required',
            'description' => 'nullable',
            'level' => 'required|integer|min:0',
            'points_threshold' => 'nullable|integer|min:0',
            'points_multiplier' => 'nullable|numeric|min:1|max:100',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ];
    }

    #[OA\Get(
        path: '/partner/tiers',
        operationId: 'list_tiers',
        summary: 'List all tiers for this partner',
        description: 'Returns paginated tiers owned by the authenticated partner, ordered by level.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Tiers'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 25, minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of tiers', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Tier')),
                new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
            ])),
            new OA\Response(response: 403, description: 'Insufficient scope', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read', 'write:tiers']],
    )]
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read', 'write:tiers')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        $tiers = Tier::where('created_by', $partner->id)
            ->orderBy('level')
            ->paginate($this->getPerPage());

        return $this->jsonPaginated($tiers);
    }

    #[OA\Get(
        path: '/partner/tiers/{id}',
        operationId: 'get_tier',
        summary: 'Get a specific tier',
        description: 'Returns a single tier by ID.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Tiers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tier details', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/Tier'),
            ])),
            new OA\Response(response: 404, description: 'Tier not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read', 'write:tiers']],
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read', 'write:tiers')) {
            return $denied;
        }

        $partner = $this->getPartner($request);
        $tier = Tier::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $tier) {
            return $this->jsonNotFound('Tier');
        }

        return $this->jsonResource($tier);
    }

    #[OA\Post(
        path: '/partner/tiers',
        operationId: 'create_tier',
        summary: 'Create a new tier',
        description: 'Creates a loyalty tier within a club.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Tiers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['club_id', 'name', 'level'],
                properties: [
                    new OA\Property(property: 'club_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', description: 'Description (translatable)', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]),
                    new OA\Property(property: 'level', type: 'integer', minimum: 0, description: 'Sort order / tier level'),
                    new OA\Property(property: 'points_threshold', type: 'integer', minimum: 0),
                    new OA\Property(property: 'points_multiplier', type: 'number', minimum: 1, maximum: 100),
                    new OA\Property(property: 'color', type: 'string', maxLength: 7, example: '#FFD700'),
                    new OA\Property(property: 'icon', type: 'string', maxLength: 50),
                    new OA\Property(property: 'is_active', type: 'boolean', default: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tier created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/Tier'),
            ])),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:tiers']],
    )]
    public function store(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:tiers')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        $payload = $request->all();

        if ($error = $this->rejectDeprecatedFields($payload, [
            'points_required' => 'points_threshold',
        ])) {
            return $error;
        }

        $validator = Validator::make($payload, $this->storeRules());

        if ($validator->fails()) {
            return $this->jsonValidationError($validator->errors()->toArray());
        }

        $validated = $validator->validated();

        $club = $this->resolveClub($partner, $validated['club_id']);
        if ($club instanceof JsonResponse) {
            return $club;
        }

        try {
            $tier = Tier::create(array_merge(
                $validated,
                ['created_by' => $partner->id],
            ));
        } catch (ValidationException $e) {
            return $this->jsonValidationError($e->errors());
        }

        return $this->jsonResource($tier, 201);
    }

    #[OA\Put(
        path: '/partner/tiers/{id}',
        operationId: 'update_tier',
        summary: 'Update an existing tier',
        description: 'Updates a tier owned by the authenticated partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Tiers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'level', type: 'integer', minimum: 0),
                new OA\Property(property: 'points_threshold', type: 'integer', minimum: 0),
                new OA\Property(property: 'is_active', type: 'boolean'),
            ]),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Tier updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/Tier'),
            ])),
            new OA\Response(response: 404, description: 'Tier not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:tiers']],
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:tiers')) {
            return $denied;
        }

        $partner = $this->getPartner($request);
        $tier = Tier::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $tier) {
            return $this->jsonNotFound('Tier');
        }

        $rules = array_map(fn ($rule) => str_replace('required|', 'nullable|', $rule), $this->storeRules());
        $rules['club_id'] = 'nullable|uuid|exists:clubs,id';
        $payload = $request->all();

        if ($error = $this->rejectDeprecatedFields($payload, [
            'points_required' => 'points_threshold',
        ])) {
            return $error;
        }

        $validator = Validator::make($payload, $rules);

        if ($validator->fails()) {
            return $this->jsonValidationError($validator->errors()->toArray());
        }

        $validated = $validator->validated();

        if (array_key_exists('club_id', $validated)) {
            $club = $this->resolveClub($partner, $validated['club_id']);
            if ($club instanceof JsonResponse) {
                return $club;
            }
        }

        try {
            $tier->update(array_merge(
                array_filter($validated, fn ($v) => $v !== null),
                ['updated_by' => $partner->id],
            ));
        } catch (ValidationException $e) {
            return $this->jsonValidationError($e->errors());
        }

        return $this->jsonResource($tier->fresh());
    }

    #[OA\Delete(
        path: '/partner/tiers/{id}',
        operationId: 'delete_tier',
        summary: 'Delete a tier',
        description: 'Permanently deletes a tier owned by the authenticated partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Tiers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tier deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Tier deleted.'),
            ])),
            new OA\Response(response: 404, description: 'Tier not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:tiers']],
    )]
    public function destroy(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:tiers')) {
            return $denied;
        }

        $partner = $this->getPartner($request);
        $tier = Tier::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $tier) {
            return $this->jsonNotFound('Tier');
        }

        $tier->delete();

        return $this->jsonSuccess(['message' => 'Tier deleted.']);
    }
}
