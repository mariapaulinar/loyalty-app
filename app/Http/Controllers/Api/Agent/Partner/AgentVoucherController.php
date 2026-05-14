<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API: Voucher CRUD + validate/redeem for partners.
 *
 * Uses VoucherService for validation and redemption business logic.
 * CRUD uses the canonical voucher schema (type, value, valid_from, valid_until,
 * max_uses_total, max_uses_per_member, code).
 *
 * @see VoucherService::validate()
 * @see VoucherService::redeem()
 */

namespace App\Http\Controllers\Api\Agent\Partner;

use App\Http\Controllers\Api\Agent\BaseAgentController;
use App\Http\Controllers\Api\Agent\Concerns\EnforcesPartnerGates;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AgentVoucherController extends BaseAgentController
{
    use EnforcesPartnerGates;

    public function __construct(
        private VoucherService $voucherService,
    ) {}

    /**
     * Validation rules for creating a voucher using canonical field names.
     */
    private function storeRules(): array
    {
        return [
            'club_id' => 'required|uuid|exists:clubs,id',
            'code' => 'nullable|string|max:32',
            'name' => 'required|string|max:128',
            'title' => 'nullable',
            'description' => 'nullable',
            'type' => 'required|string|in:percentage,fixed_amount,free_product,bonus_points',
            'value' => 'required_unless:type,free_product,bonus_points|integer|min:0',
            'currency' => 'nullable|string|size:3',
            'points_value' => 'nullable|required_if:type,bonus_points|integer|min:1',
            'min_purchase_amount' => 'nullable|integer|min:0',
            'max_discount_amount' => 'nullable|integer|min:0',
            'max_uses_total' => 'nullable|integer|min:1',
            'max_uses_per_member' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'is_active' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'is_single_use' => 'nullable|boolean',
        ];
    }

    #[OA\Get(
        path: '/partner/vouchers',
        operationId: 'list_vouchers',
        summary: 'List all vouchers for this partner',
        description: 'Returns paginated vouchers owned by the authenticated partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Vouchers'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 25, minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of vouchers', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Voucher')),
                new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
            ])),
            new OA\Response(response: 403, description: 'Insufficient scope or feature disabled', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read', 'write:vouchers'], 'agent-permission' => 'vouchers_permission'],
    )]
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read', 'write:vouchers')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        if ($error = $this->checkPermission($partner, 'vouchers_permission')) {
            return $error;
        }

        $vouchers = Voucher::where('created_by', $partner->id)
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());

        return $this->jsonPaginated($vouchers);
    }

    #[OA\Get(
        path: '/partner/vouchers/{id}',
        operationId: 'get_voucher',
        summary: 'Get a specific voucher',
        description: 'Returns a single voucher by ID.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Vouchers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Voucher details', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/Voucher'),
            ])),
            new OA\Response(response: 404, description: 'Voucher not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read', 'write:vouchers'], 'agent-permission' => 'vouchers_permission'],
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read', 'write:vouchers')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        if ($error = $this->checkPermission($partner, 'vouchers_permission')) {
            return $error;
        }

        $voucher = Voucher::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $voucher) {
            return $this->jsonNotFound('Voucher');
        }

        return $this->jsonResource($voucher);
    }

    #[OA\Post(
        path: '/partner/vouchers',
        operationId: 'create_voucher',
        summary: 'Create a new voucher',
        description: 'Creates a voucher with the specified type and value. If no code is provided, one is generated automatically.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Vouchers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['club_id', 'name', 'type'],
                properties: [
                    new OA\Property(property: 'club_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'name', type: 'string', maxLength: 128),
                    new OA\Property(property: 'title', description: 'Display title (translatable)', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]),
                    new OA\Property(property: 'description', description: 'Description (translatable)', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]),
                    new OA\Property(property: 'code', type: 'string', maxLength: 32, description: 'Voucher code (auto-generated if omitted)'),
                    new OA\Property(property: 'type', type: 'string', enum: ['percentage', 'fixed_amount', 'free_product', 'bonus_points']),
                    new OA\Property(property: 'value', type: 'integer', minimum: 0, description: 'Discount value (required for percentage/fixed_amount)'),
                    new OA\Property(property: 'currency', type: 'string', minLength: 3, maxLength: 3),
                    new OA\Property(property: 'points_value', type: 'integer', minimum: 1, description: 'Points to award (for bonus_points type)'),
                    new OA\Property(property: 'max_uses_total', type: 'integer', minimum: 1),
                    new OA\Property(property: 'max_uses_per_member', type: 'integer', minimum: 1),
                    new OA\Property(property: 'valid_from', type: 'string', format: 'date'),
                    new OA\Property(property: 'valid_until', type: 'string', format: 'date'),
                    new OA\Property(property: 'is_active', type: 'boolean', default: true),
                    new OA\Property(property: 'is_public', type: 'boolean', default: false),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Voucher created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/Voucher'),
            ])),
            new OA\Response(response: 422, description: 'Validation error or limit reached', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:vouchers'], 'agent-permission' => 'vouchers_permission'],
    )]
    public function store(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:vouchers')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        if ($error = $this->checkPermission($partner, 'vouchers_permission')) {
            return $error;
        }
        if ($error = $this->checkLimit($partner, 'vouchers_limit', Voucher::class, 'Vouchers')) {
            return $error;
        }

        $payload = $request->all();

        if ($error = $this->rejectDeprecatedFields($payload, [
            'discount_type' => 'type',
            'discount_value' => 'value',
            'max_uses' => 'max_uses_total',
            'issue_date' => 'valid_from',
            'expiration_date' => 'valid_until',
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

        // Generate unique code if not provided
        $code = $validated['code'] ?? null;
        if (! $code) {
            $code = $this->voucherService->generateUniqueCode($club->id);
        }

        $validated['code'] = $code;
        $validated['created_by'] = $partner->id;
        $validated['source'] = 'api';

        $voucher = Voucher::create($validated);

        return $this->jsonResource($voucher, 201);
    }

    #[OA\Put(
        path: '/partner/vouchers/{id}',
        operationId: 'update_voucher',
        summary: 'Update an existing voucher',
        description: 'Updates a voucher owned by the authenticated partner. Only provided fields are updated.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Vouchers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string', maxLength: 128),
                new OA\Property(property: 'type', type: 'string', enum: ['percentage', 'fixed_amount', 'free_product', 'bonus_points']),
                new OA\Property(property: 'value', type: 'integer', minimum: 0),
                new OA\Property(property: 'valid_until', type: 'string', format: 'date'),
                new OA\Property(property: 'is_active', type: 'boolean'),
            ]),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Voucher updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/Voucher'),
            ])),
            new OA\Response(response: 404, description: 'Voucher not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:vouchers'], 'agent-permission' => 'vouchers_permission'],
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:vouchers')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        if ($error = $this->checkPermission($partner, 'vouchers_permission')) {
            return $error;
        }

        $voucher = Voucher::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $voucher) {
            return $this->jsonNotFound('Voucher');
        }

        // Update rules: everything optional except immutable fields
        $rules = [
            'name' => 'nullable|string|max:128',
            'title' => 'nullable',
            'description' => 'nullable',
            'type' => 'nullable|string|in:percentage,fixed_amount,free_product,bonus_points',
            'value' => 'nullable|integer|min:0',
            'currency' => 'nullable|string|size:3',
            'points_value' => 'nullable|integer|min:1',
            'min_purchase_amount' => 'nullable|integer|min:0',
            'max_discount_amount' => 'nullable|integer|min:0',
            'max_uses_total' => 'nullable|integer|min:1',
            'max_uses_per_member' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'is_active' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'is_single_use' => 'nullable|boolean',
        ];

        $payload = $request->all();

        if ($error = $this->rejectDeprecatedFields($payload, [
            'discount_type' => 'type',
            'discount_value' => 'value',
            'max_uses' => 'max_uses_total',
            'issue_date' => 'valid_from',
            'expiration_date' => 'valid_until',
        ])) {
            return $error;
        }

        $validator = Validator::make($payload, $rules);

        if ($validator->fails()) {
            return $this->jsonValidationError($validator->errors()->toArray());
        }

        $updateData = array_filter($validator->validated(), fn ($v) => $v !== null);
        $updateData['updated_by'] = $partner->id;

        $voucher->update($updateData);

        return $this->jsonResource($voucher->fresh());
    }

    #[OA\Delete(
        path: '/partner/vouchers/{id}',
        operationId: 'delete_voucher',
        summary: 'Delete a voucher',
        description: 'Soft-deletes a voucher owned by the authenticated partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Vouchers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Voucher deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Voucher deleted.'),
            ])),
            new OA\Response(response: 404, description: 'Voucher not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:vouchers'], 'agent-permission' => 'vouchers_permission'],
    )]
    public function destroy(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:vouchers')) {
            return $denied;
        }

        $partner = $this->getPartner($request);
        $voucher = Voucher::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $voucher) {
            return $this->jsonNotFound('Voucher');
        }

        $voucher->update(['deleted_by' => $partner->id]);
        $voucher->delete();

        return $this->jsonSuccess(['message' => 'Voucher deleted.']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // VOUCHER OPERATIONS (via VoucherService)
    // ═══════════════════════════════════════════════════════════════════

    #[OA\Post(
        path: '/partner/vouchers/validate',
        operationId: 'validate_voucher',
        summary: 'Validate a voucher code without redeeming',
        description: 'Checks if a voucher code is valid for a member and optionally calculates the discount for a given order amount.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Vouchers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code', 'member_identifier', 'club_id'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', description: 'Voucher code to validate'),
                    new OA\Property(property: 'member_identifier', type: 'string', description: 'UUID, email, member number, or unique identifier'),
                    new OA\Property(property: 'club_id', type: 'string', format: 'uuid', description: 'Club the voucher belongs to'),
                    new OA\Property(property: 'order_amount', type: 'integer', minimum: 0, description: 'Order amount in cents for discount calculation'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Voucher is valid', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'valid', type: 'boolean', example: true),
                    new OA\Property(property: 'voucher_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'code', type: 'string'),
                    new OA\Property(property: 'type', type: 'string'),
                    new OA\Property(property: 'value', type: 'integer'),
                    new OA\Property(property: 'discount_amount', type: 'integer'),
                    new OA\Property(property: 'final_amount', type: 'integer'),
                ], type: 'object'),
            ])),
            new OA\Response(response: 422, description: 'Voucher invalid', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:vouchers'], 'agent-permission' => 'vouchers_permission'],
    )]
    public function validateVoucher(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:vouchers')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'member_identifier' => 'required|string',
            'club_id' => 'required|uuid',
            'order_amount' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->jsonValidationError($validator->errors()->toArray());
        }

        $club = $this->resolveClub($partner, $request->input('club_id'));
        if ($club instanceof JsonResponse) {
            return $club;
        }

        $member = $this->resolveMember($request->input('member_identifier'));
        if (! $member) {
            return $this->jsonNotFound('Member');
        }

        $result = $this->voucherService->validate(
            code: $request->input('code'),
            member: $member,
            clubId: $club->id,
            orderAmount: $request->input('order_amount') ? (int) $request->input('order_amount') : null,
        );

        if (! $result['valid']) {
            return $this->jsonError(
                code: 'VOUCHER_INVALID',
                message: $result['error_message'],
                status: 422,
                retryStrategy: 'no_retry',
                details: ['error_code' => $result['error_code']],
            );
        }

        $voucher = $result['voucher'];

        return $this->jsonSuccess([
            'data' => [
                'valid' => true,
                'voucher_id' => $voucher->id,
                'code' => $voucher->code,
                'name' => $voucher->name,
                'type' => $voucher->type,
                'value' => $voucher->value,
                'currency' => $voucher->currency,
                'discount_amount' => $result['discount_amount'],
                'capped' => $result['capped'] ?? false,
                'original_amount' => $result['original_amount'],
                'final_amount' => $result['final_amount'],
                'times_used' => $voucher->times_used,
                'remaining_uses' => $voucher->remaining_uses,
                'valid_until' => $voucher->valid_until?->toIso8601String(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/partner/vouchers/{id}/redeem',
        operationId: 'redeem_voucher',
        summary: 'Redeem a voucher for a member',
        description: 'Redeems a voucher for a member, applying the discount/bonus and recording the usage. Uses transactional locking to prevent race conditions.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Vouchers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Voucher UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['member_identifier'],
                properties: [
                    new OA\Property(property: 'member_identifier', type: 'string', description: 'UUID, email, member number, or unique identifier'),
                    new OA\Property(property: 'order_amount', type: 'integer', minimum: 0, description: 'Order amount in cents'),
                    new OA\Property(property: 'order_reference', type: 'string', maxLength: 64, description: 'External order reference'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Voucher redeemed', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'voucher_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'code', type: 'string'),
                    new OA\Property(property: 'type', type: 'string'),
                    new OA\Property(property: 'member_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'discount_amount', type: 'integer'),
                    new OA\Property(property: 'points_awarded', type: 'integer', nullable: true),
                    new OA\Property(property: 'remaining_uses', type: 'integer', nullable: true),
                    new OA\Property(property: 'redemption_id', type: 'string', format: 'uuid'),
                ], type: 'object'),
            ])),
            new OA\Response(response: 422, description: 'Voucher redeem failed', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:vouchers'], 'agent-permission' => 'vouchers_permission'],
    )]
    public function redeemVoucher(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:vouchers')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        $voucher = Voucher::where('id', $id)
            ->where('created_by', $partner->id)
            ->first();

        if (! $voucher) {
            return $this->jsonNotFound('Voucher');
        }

        $validator = Validator::make($request->all(), [
            'member_identifier' => 'required|string',
            'order_amount' => 'nullable|integer|min:0',
            'order_reference' => 'nullable|string|max:64',
        ]);

        if ($validator->fails()) {
            return $this->jsonValidationError($validator->errors()->toArray());
        }

        $member = $this->resolveMember($request->input('member_identifier'));
        if (! $member) {
            return $this->jsonNotFound('Member');
        }

        try {
            $result = $this->voucherService->redeem(
                voucher: $voucher,
                member: $member,
                orderAmount: $request->input('order_amount') ? (int) $request->input('order_amount') : null,
                orderReference: $request->input('order_reference'),
            );

            if (! $result['success']) {
                return $this->jsonError(
                    code: 'VOUCHER_REDEEM_FAILED',
                    message: $result['error'] ?? 'Unable to redeem voucher.',
                    status: 422,
                );
            }

            return $this->jsonSuccess([
                'data' => [
                    'voucher_id' => $voucher->id,
                    'code' => $voucher->code,
                    'type' => $voucher->type,
                    'member_id' => $member->id,
                    'discount_amount' => $result['discount_amount'],
                    'points_awarded' => $result['points_awarded'],
                    'remaining_uses' => $result['voucher_remaining_uses'],
                    'redemption_id' => $result['redemption']?->id,
                ],
            ]);
        } catch (\Exception $e) {
            report($e);

            return $this->jsonError(
                code: 'INTERNAL_ERROR',
                message: 'Unable to process voucher redemption.',
                status: 500,
                retryStrategy: 'retry_later',
            );
        }
    }
}
