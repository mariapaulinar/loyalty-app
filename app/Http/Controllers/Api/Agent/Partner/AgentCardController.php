<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API: Loyalty Card management for partners.
 *
 * Cards define the points economy: currency, earning rate, limits, expiry.
 * This controller handles CRUD — transaction operations live in
 * AgentTransactionController which uses TransactionService.
 *
 * Mirror source: PartnerCardController + CardDataDefinition
 * Ownership filter: Card::where('created_by', $partner->id)
 *
 * @see RewardLoyalty-100b-phase2-core-endpoints.md §2.2
 */

namespace App\Http\Controllers\Api\Agent\Partner;

use App\Http\Controllers\Api\Agent\BaseAgentController;
use App\Http\Controllers\Api\Agent\Concerns\EnforcesPartnerGates;
use App\Models\Card;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AgentCardController extends BaseAgentController
{
    use EnforcesPartnerGates;

    /**
     * Validation rules derived from CardDataDefinition fields.
     * Translatable fields accept both JSON objects (multi-locale) and strings.
     */
    private function storeRules(): array
    {
        return [
            'club_id' => 'required|uuid|exists:clubs,id',
            'name' => 'required|string|max:250',
            'head' => 'nullable',
            'title' => 'nullable',
            'description' => 'nullable',
            'currency' => 'required|string|size:3',
            'points_per_currency' => 'required|numeric|min:0|max:100000',
            'currency_unit_amount' => 'required|numeric|min:1|max:1000000',
            'min_points_per_purchase' => 'required|numeric|min:0|max:10000000',
            'max_points_per_purchase' => 'required|numeric|min:0|max:10000000',
            'initial_bonus_points' => 'nullable|numeric|min:0|max:10000000',
            'points_expiration_months' => 'required|numeric|min:1|max:1200',
            'issue_date' => 'nullable|date',
            'expiration_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
            'is_visible_by_default' => 'nullable|boolean',
            'bg_color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'bg_color_opacity' => 'nullable|numeric|min:0|max:100',
            'text_color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        ];
    }

    /**
     * GET /api/agent/v1/partner/cards
     * Scope: read
     */
    #[OA\Get(
        path: '/partner/cards',
        operationId: 'list_loyalty_cards',
        summary: 'List all loyalty cards for this partner',
        description: 'Returns paginated loyalty cards owned by the authenticated partner. Supports pagination via page and per_page query parameters.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Loyalty Cards'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 25, minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of loyalty cards', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/LoyaltyCard')),
                new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
            ])),
            new OA\Response(response: 403, description: 'Insufficient scope or feature disabled', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read', 'write:cards'], 'agent-permission' => 'loyalty_cards_permission'],
    )]
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read', 'write:cards')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        if ($error = $this->checkPermission($partner, 'loyalty_cards_permission')) {
            return $error;
        }

        $cards = Card::where('created_by', $partner->id)
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());

        return $this->jsonPaginated($cards);
    }

    /**
     * GET /api/agent/v1/partner/cards/{id}
     * Scope: read
     */
    #[OA\Get(
        path: '/partner/cards/{id}',
        operationId: 'get_loyalty_card',
        summary: 'Get a specific loyalty card',
        description: 'Returns a single loyalty card by ID. The card must belong to the authenticated partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Loyalty Cards'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Loyalty card details', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/LoyaltyCard'),
            ])),
            new OA\Response(response: 404, description: 'Card not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read', 'write:cards'], 'agent-permission' => 'loyalty_cards_permission'],
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read', 'write:cards')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        if ($error = $this->checkPermission($partner, 'loyalty_cards_permission')) {
            return $error;
        }

        $card = Card::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $card) {
            return $this->jsonNotFound('Card');
        }

        return $this->jsonResource($card);
    }

    /**
     * POST /api/agent/v1/partner/cards
     * Scope: write:cards
     */
    #[OA\Post(
        path: '/partner/cards',
        operationId: 'create_loyalty_card',
        summary: 'Create a new loyalty card',
        description: 'Creates a loyalty card with the specified points economy configuration. Requires the club_id of an existing club owned by this partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Loyalty Cards'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['club_id', 'name', 'currency', 'points_per_currency', 'currency_unit_amount', 'min_points_per_purchase', 'max_points_per_purchase', 'points_expiration_months'],
                properties: [
                    new OA\Property(property: 'club_id', type: 'string', format: 'uuid', description: 'Club this card belongs to'),
                    new OA\Property(property: 'name', type: 'string', maxLength: 250, description: 'Internal name (not shown to members)'),
                    new OA\Property(property: 'head', description: 'Translatable header text', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]),
                    new OA\Property(property: 'title', description: 'Display title (translatable)', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]),
                    new OA\Property(property: 'description', description: 'Description (translatable)', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]),
                    new OA\Property(property: 'currency', type: 'string', minLength: 3, maxLength: 3, example: 'EUR', description: 'ISO 4217 currency code'),
                    new OA\Property(property: 'points_per_currency', type: 'number', minimum: 0, maximum: 100000),
                    new OA\Property(property: 'currency_unit_amount', type: 'number', minimum: 1, maximum: 1000000),
                    new OA\Property(property: 'min_points_per_purchase', type: 'number', minimum: 0, maximum: 10000000),
                    new OA\Property(property: 'max_points_per_purchase', type: 'number', minimum: 0, maximum: 10000000),
                    new OA\Property(property: 'initial_bonus_points', type: 'number', minimum: 0, maximum: 10000000),
                    new OA\Property(property: 'points_expiration_months', type: 'number', minimum: 1, maximum: 1200),
                    new OA\Property(property: 'is_active', type: 'boolean', default: true),
                    new OA\Property(property: 'bg_color', type: 'string', pattern: '^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$', example: '#4F46E5'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Card created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/LoyaltyCard'),
            ])),
            new OA\Response(response: 422, description: 'Validation error or limit reached', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:cards'], 'agent-permission' => 'loyalty_cards_permission'],
    )]
    public function store(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:cards')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        if ($error = $this->checkPermission($partner, 'loyalty_cards_permission')) {
            return $error;
        }
        if ($error = $this->checkLimit($partner, 'loyalty_cards_limit', Card::class, 'Loyalty cards')) {
            return $error;
        }

        $validator = Validator::make($request->all(), $this->storeRules());

        if ($validator->fails()) {
            return $this->jsonValidationError($validator->errors()->toArray());
        }

        // Verify club ownership
        $club = $this->resolveClub($partner, $request->input('club_id'));
        if ($club instanceof JsonResponse) {
            return $club;
        }

        $card = Card::create(array_merge(
            $validator->validated(),
            ['created_by' => $partner->id],
        ));

        return $this->jsonResource($card, 201);
    }

    /**
     * PUT /api/agent/v1/partner/cards/{id}
     * Scope: write:cards
     */
    #[OA\Put(
        path: '/partner/cards/{id}',
        operationId: 'update_loyalty_card',
        summary: 'Update an existing loyalty card',
        description: 'Updates a loyalty card owned by the authenticated partner. Only provided fields are updated.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Loyalty Cards'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'club_id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'name', type: 'string', maxLength: 250),
                new OA\Property(property: 'head', description: 'Translatable header text', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]),
                new OA\Property(property: 'title', description: 'Display title (translatable)', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]),
                new OA\Property(property: 'description', description: 'Description (translatable)', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]),
                new OA\Property(property: 'currency', type: 'string', minLength: 3, maxLength: 3),
                new OA\Property(property: 'points_per_currency', type: 'number', minimum: 0, maximum: 100000),
                new OA\Property(property: 'currency_unit_amount', type: 'number', minimum: 1, maximum: 1000000),
                new OA\Property(property: 'min_points_per_purchase', type: 'number', minimum: 0, maximum: 10000000),
                new OA\Property(property: 'max_points_per_purchase', type: 'number', minimum: 0, maximum: 10000000),
                new OA\Property(property: 'initial_bonus_points', type: 'number', minimum: 0, maximum: 10000000),
                new OA\Property(property: 'points_expiration_months', type: 'number', minimum: 1, maximum: 1200),
                new OA\Property(property: 'is_active', type: 'boolean'),
                new OA\Property(property: 'bg_color', type: 'string', pattern: '^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$'),
            ]),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Card updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/LoyaltyCard'),
            ])),
            new OA\Response(response: 404, description: 'Card not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:cards'], 'agent-permission' => 'loyalty_cards_permission'],
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:cards')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        if ($error = $this->checkPermission($partner, 'loyalty_cards_permission')) {
            return $error;
        }

        $card = Card::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $card) {
            return $this->jsonNotFound('Card');
        }

        // All fields optional on update
        $rules = array_map(fn ($rule) => str_replace('required|', 'nullable|', $rule), $this->storeRules());
        $rules['club_id'] = 'nullable|uuid|exists:clubs,id';

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->jsonValidationError($validator->errors()->toArray());
        }

        // If club_id is being changed, verify ownership
        if ($request->has('club_id')) {
            $club = $this->resolveClub($partner, $request->input('club_id'));
            if ($club instanceof JsonResponse) {
                return $club;
            }
        }

        $card->update(array_merge(
            array_filter($validator->validated(), fn ($v) => $v !== null),
            ['updated_by' => $partner->id],
        ));

        return $this->jsonResource($card->fresh());
    }

    /**
     * DELETE /api/agent/v1/partner/cards/{id}
     * Scope: write:cards
     */
    #[OA\Delete(
        path: '/partner/cards/{id}',
        operationId: 'delete_loyalty_card',
        summary: 'Delete a loyalty card',
        description: 'Permanently deletes a loyalty card owned by the authenticated partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Loyalty Cards'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Card deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Card deleted.'),
            ])),
            new OA\Response(response: 404, description: 'Card not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:cards'], 'agent-permission' => 'loyalty_cards_permission'],
    )]
    public function destroy(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:cards')) {
            return $denied;
        }

        $partner = $this->getPartner($request);
        $card = Card::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $card) {
            return $this->jsonNotFound('Card');
        }

        $card->delete();

        return $this->jsonSuccess(['message' => 'Card deleted.']);
    }
}
