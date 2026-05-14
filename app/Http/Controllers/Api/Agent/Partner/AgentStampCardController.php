<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API: Stamp Card CRUD + stamp/redeem operations for partners.
 *
 * Uses StampService for all business logic (eligibility, enrollment,
 * completion detection, event dispatching).
 *
 * @see RewardLoyalty-100b-phase2-core-endpoints.md §2.6
 */

namespace App\Http\Controllers\Api\Agent\Partner;

use App\Http\Controllers\Api\Agent\BaseAgentController;
use App\Http\Controllers\Api\Agent\Concerns\EnforcesPartnerGates;
use App\Models\StampCard;
use App\Services\StampService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AgentStampCardController extends BaseAgentController
{
    use EnforcesPartnerGates;

    public function __construct(
        private StampService $stampService,
    ) {}

    private function storeRules(): array
    {
        return [
            'club_id' => 'required|uuid|exists:clubs,id',
            'name' => 'required|string|max:250',
            'head' => 'nullable',
            'title' => 'nullable',
            'description' => 'nullable',
            'reward_title' => 'nullable',
            'stamps_required' => 'required|integer|min:1|max:100',
            'stamps_per_purchase' => 'nullable|integer|min:1|max:100',
            'max_stamps_per_transaction' => 'nullable|integer|min:1',
            'max_stamps_per_day' => 'nullable|integer|min:1',
            'stamps_expire_days' => 'nullable|integer|min:1|max:365',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'requires_physical_claim' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ];
    }

    #[OA\Get(
        path: '/partner/stamp-cards',
        operationId: 'list_stamp_cards',
        summary: 'List all stamp cards for this partner',
        description: 'Returns paginated stamp cards owned by the authenticated partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Stamp Cards'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 25, minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of stamp cards', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StampCard')),
                new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
            ])),
            new OA\Response(response: 403, description: 'Insufficient scope or feature disabled', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read', 'write:stamps'], 'agent-permission' => 'stamp_cards_permission'],
    )]
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read', 'write:stamps')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        if ($error = $this->checkPermission($partner, 'stamp_cards_permission')) {
            return $error;
        }

        $stampCards = StampCard::where('created_by', $partner->id)
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());

        return $this->jsonPaginated($stampCards);
    }

    #[OA\Get(
        path: '/partner/stamp-cards/{id}',
        operationId: 'get_stamp_card',
        summary: 'Get a specific stamp card',
        description: 'Returns a single stamp card by ID.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Stamp Cards'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stamp card details', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/StampCard'),
            ])),
            new OA\Response(response: 404, description: 'Stamp card not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read', 'write:stamps'], 'agent-permission' => 'stamp_cards_permission'],
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read', 'write:stamps')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        if ($error = $this->checkPermission($partner, 'stamp_cards_permission')) {
            return $error;
        }

        $stampCard = StampCard::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $stampCard) {
            return $this->jsonNotFound('Stamp card');
        }

        return $this->jsonResource($stampCard);
    }

    #[OA\Post(
        path: '/partner/stamp-cards',
        operationId: 'create_stamp_card',
        summary: 'Create a new stamp card',
        description: 'Creates a stamp card with the specified configuration. Requires stamp cards feature to be enabled.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Stamp Cards'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['club_id', 'name', 'stamps_required'],
                properties: [
                    new OA\Property(property: 'club_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'name', type: 'string', maxLength: 250),
                    new OA\Property(property: 'head', description: 'Translatable header', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]),
                    new OA\Property(property: 'title', description: 'Display title (translatable)', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]),
                    new OA\Property(property: 'description', description: 'Description (translatable)', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]),
                    new OA\Property(property: 'reward_title', description: 'Reward title (translatable)', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]),
                    new OA\Property(property: 'stamps_required', type: 'integer', minimum: 1, maximum: 100),
                    new OA\Property(property: 'stamps_per_purchase', type: 'integer', minimum: 1, maximum: 100),
                    new OA\Property(property: 'max_stamps_per_transaction', type: 'integer', minimum: 1),
                    new OA\Property(property: 'max_stamps_per_day', type: 'integer', minimum: 1),
                    new OA\Property(property: 'stamps_expire_days', type: 'integer', minimum: 1, maximum: 365),
                    new OA\Property(property: 'min_purchase_amount', type: 'number', minimum: 0),
                    new OA\Property(property: 'currency', type: 'string', minLength: 3, maxLength: 3),
                    new OA\Property(property: 'is_active', type: 'boolean', default: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Stamp card created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/StampCard'),
            ])),
            new OA\Response(response: 422, description: 'Validation error or limit reached', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:stamps'], 'agent-permission' => 'stamp_cards_permission'],
    )]
    public function store(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:stamps')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        if ($error = $this->checkPermission($partner, 'stamp_cards_permission')) {
            return $error;
        }
        if ($error = $this->checkLimit($partner, 'stamp_cards_limit', StampCard::class, 'Stamp cards')) {
            return $error;
        }

        $payload = $request->all();

        if ($error = $this->rejectDeprecatedFields($payload, [
            'require_staff_for_redemption' => 'requires_physical_claim',
            'stamp_expiry_days' => 'stamps_expire_days',
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

        $stampCard = StampCard::create(array_merge(
            $validated,
            ['created_by' => $partner->id],
        ));

        return $this->jsonResource($stampCard, 201);
    }

    #[OA\Put(
        path: '/partner/stamp-cards/{id}',
        operationId: 'update_stamp_card',
        summary: 'Update an existing stamp card',
        description: 'Updates a stamp card owned by the authenticated partner. Only provided fields are updated.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Stamp Cards'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'club_id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'name', type: 'string', maxLength: 250),
                new OA\Property(property: 'stamps_required', type: 'integer', minimum: 1, maximum: 100),
                new OA\Property(property: 'is_active', type: 'boolean'),
            ]),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Stamp card updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/StampCard'),
            ])),
            new OA\Response(response: 404, description: 'Stamp card not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:stamps'], 'agent-permission' => 'stamp_cards_permission'],
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:stamps')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        if ($error = $this->checkPermission($partner, 'stamp_cards_permission')) {
            return $error;
        }

        $stampCard = StampCard::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $stampCard) {
            return $this->jsonNotFound('Stamp card');
        }

        $rules = array_map(fn ($rule) => str_replace('required|', 'nullable|', $rule), $this->storeRules());
        $rules['club_id'] = 'nullable|uuid|exists:clubs,id';
        $payload = $request->all();

        if ($error = $this->rejectDeprecatedFields($payload, [
            'require_staff_for_redemption' => 'requires_physical_claim',
            'stamp_expiry_days' => 'stamps_expire_days',
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

        $stampCard->update(array_merge(
            array_filter($validated, fn ($v) => $v !== null),
            ['updated_by' => $partner->id],
        ));

        return $this->jsonResource($stampCard->fresh());
    }

    #[OA\Delete(
        path: '/partner/stamp-cards/{id}',
        operationId: 'delete_stamp_card',
        summary: 'Delete a stamp card',
        description: 'Permanently deletes a stamp card owned by the authenticated partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Stamp Cards'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stamp card deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Stamp card deleted.'),
            ])),
            new OA\Response(response: 404, description: 'Stamp card not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:stamps'], 'agent-permission' => 'stamp_cards_permission'],
    )]
    public function destroy(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:stamps')) {
            return $denied;
        }

        $partner = $this->getPartner($request);
        $stampCard = StampCard::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $stampCard) {
            return $this->jsonNotFound('Stamp card');
        }

        $stampCard->delete();

        return $this->jsonSuccess(['message' => 'Stamp card deleted.']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // STAMP OPERATIONS (via StampService)
    // ═══════════════════════════════════════════════════════════════════

    #[OA\Post(
        path: '/partner/stamp-cards/{id}/stamps',
        operationId: 'add_stamps',
        summary: 'Add stamps to a member\'s stamp card',
        description: 'Awards stamp(s) to a member on the specified stamp card. If the card is completed, a pending reward is created.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Stamp Cards'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Stamp card UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['member_identifier'],
                properties: [
                    new OA\Property(property: 'member_identifier', type: 'string', description: 'UUID, email, member number, or unique identifier'),
                    new OA\Property(property: 'stamps', type: 'integer', minimum: 1, maximum: 100, default: 1, description: 'Number of stamps to add'),
                    new OA\Property(property: 'purchase_amount', type: 'number', minimum: 0, description: 'Purchase amount (for minimum purchase validation)'),
                    new OA\Property(property: 'note', type: 'string', maxLength: 500),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Stamp(s) added', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'stamps_added', type: 'integer'),
                    new OA\Property(property: 'current_stamps', type: 'integer'),
                    new OA\Property(property: 'stamps_required', type: 'integer'),
                    new OA\Property(property: 'completed', type: 'boolean'),
                    new OA\Property(property: 'pending_rewards', type: 'integer'),
                ], type: 'object'),
            ])),
            new OA\Response(response: 422, description: 'Stamp operation failed', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:stamps'], 'agent-permission' => 'stamp_cards_permission'],
    )]
    public function addStamps(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:stamps')) {
            return $denied;
        }

        $partner = $this->getPartner($request);
        $stampCard = StampCard::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $stampCard) {
            return $this->jsonNotFound('Stamp card');
        }

        $validator = Validator::make($request->all(), [
            'member_identifier' => 'required|string',
            'stamps' => 'nullable|integer|min:1|max:100',
            'purchase_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->jsonValidationError($validator->errors()->toArray());
        }

        $member = $this->resolveMember($request->input('member_identifier'));
        if (! $member) {
            return $this->jsonNotFound('Member');
        }

        $result = $this->stampService->addStamp(
            card: $stampCard,
            member: $member,
            staff: null,
            stamps: $request->input('stamps', 1),
            purchaseAmount: $request->input('purchase_amount') ? (float) $request->input('purchase_amount') : null,
            note: $request->input('note'),
        );

        if (! $result['success']) {
            return $this->jsonError(
                code: 'STAMP_FAILED',
                message: $result['error'] ?? 'Unable to add stamps.',
                status: 422,
            );
        }

        return $this->jsonSuccess([
            'data' => [
                'stamps_added' => $result['stamps_added'],
                'current_stamps' => $result['current_stamps'],
                'stamps_required' => $result['stamps_required'],
                'completed' => $result['completed'],
                'pending_rewards' => $result['pending_rewards'],
            ],
        ], 201);
    }

    #[OA\Post(
        path: '/partner/stamp-cards/{id}/redeem',
        operationId: 'redeem_stamp_reward',
        summary: 'Redeem a pending stamp reward',
        description: 'Redeems one pending reward for a member on the specified stamp card. The member must have a completed card with an unclaimed reward.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Stamp Cards'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Stamp card UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['member_identifier'],
                properties: [
                    new OA\Property(property: 'member_identifier', type: 'string', description: 'UUID, email, member number, or unique identifier'),
                    new OA\Property(property: 'note', type: 'string', maxLength: 500),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Reward redeemed', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'reward_title', type: 'string'),
                    new OA\Property(property: 'reward_value', type: 'string', nullable: true),
                    new OA\Property(property: 'remaining_rewards', type: 'integer'),
                ], type: 'object'),
            ])),
            new OA\Response(response: 422, description: 'No pending reward or redeem failed', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:stamps'], 'agent-permission' => 'stamp_cards_permission'],
    )]
    public function redeemStampReward(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:stamps')) {
            return $denied;
        }

        $partner = $this->getPartner($request);
        $stampCard = StampCard::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $stampCard) {
            return $this->jsonNotFound('Stamp card');
        }

        $validator = Validator::make($request->all(), [
            'member_identifier' => 'required|string',
            'note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->jsonValidationError($validator->errors()->toArray());
        }

        $member = $this->resolveMember($request->input('member_identifier'));
        if (! $member) {
            return $this->jsonNotFound('Member');
        }

        $result = $this->stampService->redeemReward(
            card: $stampCard,
            member: $member,
            staff: null,
            note: $request->input('note'),
        );

        if (! $result['success']) {
            return $this->jsonError(
                code: 'REDEEM_FAILED',
                message: $result['error'] ?? 'Unable to redeem reward.',
                status: 422,
            );
        }

        return $this->jsonSuccess([
            'data' => [
                'reward_title' => $result['reward_title'],
                'reward_value' => $result['reward_value'],
                'remaining_rewards' => $result['remaining_rewards'],
            ],
        ]);
    }
}
