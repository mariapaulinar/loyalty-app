<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API: Reward management for partners.
 *
 * Rewards are redeemable items that members exchange points for.
 * Permission gate: loyalty_cards_permission (rewards require cards).
 *
 * Mirror source: RewardDataDefinition
 * Ownership filter: Reward::where('created_by', $partner->id)
 *
 * @see RewardLoyalty-100b-phase2-core-endpoints.md §2.3
 */

namespace App\Http\Controllers\Api\Agent\Partner;

use App\Http\Controllers\Api\Agent\BaseAgentController;
use App\Http\Controllers\Api\Agent\Concerns\EnforcesPartnerGates;
use App\Models\Reward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AgentRewardController extends BaseAgentController
{
    use EnforcesPartnerGates;

    private function storeRules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'title' => 'nullable',
            'description' => 'nullable',
            'points' => 'required|numeric|min:0|max:10000000',
            'active_from' => 'nullable|date',
            'expiration_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ];
    }

    #[OA\Get(
        path: '/partner/rewards',
        operationId: 'list_rewards',
        summary: 'List all rewards for this partner',
        description: 'Returns paginated rewards owned by the authenticated partner. Requires loyalty cards feature to be enabled.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Rewards'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 25, minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of rewards', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Reward')),
                new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
            ])),
            new OA\Response(response: 403, description: 'Insufficient scope or feature disabled', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read', 'write:rewards'], 'agent-permission' => 'loyalty_cards_permission'],
    )]
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read', 'write:rewards')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        if ($error = $this->checkPermission($partner, 'loyalty_cards_permission')) {
            return $error;
        }

        $rewards = Reward::where('created_by', $partner->id)
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());

        return $this->jsonPaginated($rewards);
    }

    #[OA\Get(
        path: '/partner/rewards/{id}',
        operationId: 'get_reward',
        summary: 'Get a specific reward',
        description: 'Returns a single reward by ID. The reward must belong to the authenticated partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Rewards'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reward details', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/Reward'),
            ])),
            new OA\Response(response: 404, description: 'Reward not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read', 'write:rewards'], 'agent-permission' => 'loyalty_cards_permission'],
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read', 'write:rewards')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        if ($error = $this->checkPermission($partner, 'loyalty_cards_permission')) {
            return $error;
        }

        $reward = Reward::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $reward) {
            return $this->jsonNotFound('Reward');
        }

        return $this->jsonResource($reward);
    }

    #[OA\Post(
        path: '/partner/rewards',
        operationId: 'create_reward',
        summary: 'Create a new reward',
        description: 'Creates a reward that members can claim by spending loyalty points.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Rewards'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'points'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 120, description: 'Internal reward name'),
                    new OA\Property(property: 'title', description: 'Display title (translatable)', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]),
                    new OA\Property(property: 'description', description: 'Description (translatable)', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]),
                    new OA\Property(property: 'points', type: 'number', minimum: 0, maximum: 10000000, description: 'Points required to claim this reward'),
                    new OA\Property(property: 'active_from', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'expiration_date', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'is_active', type: 'boolean', default: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Reward created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/Reward'),
            ])),
            new OA\Response(response: 422, description: 'Validation error or limit reached', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:rewards'], 'agent-permission' => 'loyalty_cards_permission'],
    )]
    public function store(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:rewards')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        if ($error = $this->checkPermission($partner, 'loyalty_cards_permission')) {
            return $error;
        }
        if ($error = $this->checkLimit($partner, 'rewards_limit', Reward::class, 'Rewards')) {
            return $error;
        }

        $validator = Validator::make($request->all(), $this->storeRules());

        if ($validator->fails()) {
            return $this->jsonValidationError($validator->errors()->toArray());
        }

        $reward = Reward::create(array_merge(
            $validator->validated(),
            ['created_by' => $partner->id],
        ));

        return $this->jsonResource($reward, 201);
    }

    #[OA\Put(
        path: '/partner/rewards/{id}',
        operationId: 'update_reward',
        summary: 'Update an existing reward',
        description: 'Updates a reward owned by the authenticated partner. Only provided fields are updated.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Rewards'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string', maxLength: 120),
                new OA\Property(property: 'title', description: 'Display title (translatable)', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]),
                new OA\Property(property: 'description', description: 'Description (translatable)', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]),
                new OA\Property(property: 'points', type: 'number', minimum: 0, maximum: 10000000),
                new OA\Property(property: 'active_from', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'expiration_date', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'is_active', type: 'boolean'),
            ]),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Reward updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/Reward'),
            ])),
            new OA\Response(response: 404, description: 'Reward not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:rewards'], 'agent-permission' => 'loyalty_cards_permission'],
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:rewards')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        if ($error = $this->checkPermission($partner, 'loyalty_cards_permission')) {
            return $error;
        }

        $reward = Reward::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $reward) {
            return $this->jsonNotFound('Reward');
        }

        $rules = array_map(fn ($rule) => str_replace('required|', 'nullable|', $rule), $this->storeRules());
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->jsonValidationError($validator->errors()->toArray());
        }

        $reward->update(array_merge(
            array_filter($validator->validated(), fn ($v) => $v !== null),
            ['updated_by' => $partner->id],
        ));

        return $this->jsonResource($reward->fresh());
    }

    #[OA\Delete(
        path: '/partner/rewards/{id}',
        operationId: 'delete_reward',
        summary: 'Delete a reward',
        description: 'Permanently deletes a reward owned by the authenticated partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Rewards'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reward deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Reward deleted.'),
            ])),
            new OA\Response(response: 404, description: 'Reward not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:rewards'], 'agent-permission' => 'loyalty_cards_permission'],
    )]
    public function destroy(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:rewards')) {
            return $denied;
        }

        $partner = $this->getPartner($request);
        $reward = Reward::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $reward) {
            return $this->jsonNotFound('Reward');
        }

        $reward->delete();

        return $this->jsonSuccess(['message' => 'Reward deleted.']);
    }
}
