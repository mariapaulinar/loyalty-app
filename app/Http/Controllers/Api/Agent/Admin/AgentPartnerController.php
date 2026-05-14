<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API: Admin partner management.
 *
 * Manages partners (business owners) at the platform level.
 * Mirrors the AdminPartnerController but uses the agent auth pipeline.
 *
 * Endpoints:
 * - GET    /admin/partners              → List all partners (paginated, filterable)
 * - GET    /admin/partners/{id}         → Show partner details + permissions
 * - PATCH  /admin/partners/{id}/permissions → Update partner permissions & limits
 * - POST   /admin/partners/{id}/activate   → Reactivate a partner
 * - POST   /admin/partners/{id}/deactivate → Deactivate a partner
 *
 * Intentionally omitting create/update/delete — partners self-register
 * or are created via the admin dashboard. Bulk destructive operations
 * should not be exposed via agent keys.
 *
 * @see App\Http\Controllers\Api\AdminPartnerController (mirror source)
 * @see RewardLoyalty-100d-phase4-advanced.md §1
 */

namespace App\Http\Controllers\Api\Agent\Admin;

use App\Http\Controllers\Api\Agent\BaseAgentController;
use App\Models\AgentKey;
use App\Models\Card;
use App\Models\Member;
use App\Models\Partner;
use App\Models\StampCard;
use App\Models\Transaction;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AgentPartnerController extends BaseAgentController
{
    #[OA\Get(
        path: '/admin/partners',
        operationId: 'admin_list_partners',
        summary: 'List all partners',
        description: 'Returns paginated partners with optional filters. Admin-only endpoint.',
        security: [['AgentKey' => []]],
        tags: ['Admin / Partners'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 25, minimum: 1, maximum: 100)),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Search by name or email', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of partners', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PartnerSummary')),
                new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
            ])),
            new OA\Response(response: 403, description: 'Insufficient scope', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read:partners']],
    )]
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->requireAdminScope($request, 'read:partners')) {
            return $denied;
        }

        $query = Partner::query();

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $partners = $query->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());

        $data = $partners->getCollection()->map(fn (Partner $p) => $this->serializePartner($p));

        return $this->jsonSuccess([
            'data' => $data,
            'pagination' => [
                'current_page' => $partners->currentPage(),
                'last_page' => $partners->lastPage(),
                'per_page' => $partners->perPage(),
                'total' => $partners->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/admin/partners/{id}',
        operationId: 'admin_get_partner',
        summary: 'Get a specific partner with permissions and usage',
        description: 'Returns partner details including permissions, limits, and usage counts.',
        security: [['AgentKey' => []]],
        tags: ['Admin / Partners'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Partner details with permissions and usage', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/PartnerDetail'),
            ])),
            new OA\Response(response: 404, description: 'Partner not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read:partners']],
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireAdminScope($request, 'read:partners')) {
            return $denied;
        }

        $partner = Partner::find($id);
        if (! $partner) {
            return $this->jsonNotFound('Partner');
        }

        return $this->jsonSuccess([
            'data' => array_merge(
                $this->serializePartner($partner),
                ['permissions' => $this->extractPermissions($partner)],
                ['usage' => $this->getUsageCounts($partner)],
            ),
        ]);
    }

    #[OA\Patch(
        path: '/admin/partners/{id}/permissions',
        operationId: 'admin_update_partner_permissions',
        summary: 'Update partner permissions and limits',
        description: 'Updates feature flags and resource limits for a partner.',
        security: [['AgentKey' => []]],
        tags: ['Admin / Partners'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'loyalty_cards_permission', type: 'boolean'),
                new OA\Property(property: 'loyalty_cards_limit', type: 'integer', minimum: -1, description: '-1 = unlimited'),
                new OA\Property(property: 'stamp_cards_permission', type: 'boolean'),
                new OA\Property(property: 'stamp_cards_limit', type: 'integer', minimum: -1),
                new OA\Property(property: 'vouchers_permission', type: 'boolean'),
                new OA\Property(property: 'voucher_batches_permission', type: 'boolean'),
                new OA\Property(property: 'vouchers_limit', type: 'integer', minimum: -1),
                new OA\Property(property: 'rewards_limit', type: 'integer', minimum: -1),
                new OA\Property(property: 'staff_members_limit', type: 'integer', minimum: -1),
                new OA\Property(property: 'email_campaigns_permission', type: 'boolean'),
                new OA\Property(property: 'activity_permission', type: 'boolean'),
                new OA\Property(property: 'agent_api_permission', type: 'boolean'),
                new OA\Property(property: 'agent_keys_limit', type: 'integer', minimum: -1),
                new OA\Property(property: 'cards_on_homepage', type: 'boolean'),
            ]),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Permissions updated — returns the full permission set after save', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/PartnerPermissions'),
            ])),
            new OA\Response(response: 404, description: 'Partner not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
            new OA\Response(response: 422, description: 'Validation error or empty body', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:partners']],
    )]
    public function updatePermissions(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireAdminScope($request, 'write:partners')) {
            return $denied;
        }

        $partner = Partner::find($id);
        if (! $partner) {
            return $this->jsonNotFound('Partner');
        }

        $rules = [
            'loyalty_cards_permission' => 'nullable|boolean',
            'loyalty_cards_limit' => 'nullable|integer|min:-1',
            'stamp_cards_permission' => 'nullable|boolean',
            'stamp_cards_limit' => 'nullable|integer|min:-1',
            'vouchers_permission' => 'nullable|boolean',
            'voucher_batches_permission' => 'nullable|boolean',
            'vouchers_limit' => 'nullable|integer|min:-1',
            'rewards_limit' => 'nullable|integer|min:-1',
            'staff_members_limit' => 'nullable|integer|min:-1',
            'email_campaigns_permission' => 'nullable|boolean',
            'activity_permission' => 'nullable|boolean',
            'agent_api_permission' => 'nullable|boolean',
            'agent_keys_limit' => 'nullable|integer|min:-1',
            'cards_on_homepage' => 'nullable|boolean',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->jsonValidationError($validator->errors()->toArray());
        }

        $validated = $validator->validated();

        // Filter out null values — only non-null fields are actual changes
        $changes = array_filter($validated, fn ($v) => $v !== null);

        if (empty($changes)) {
            return $this->jsonValidationError([
                'body' => ['At least one permission field with a non-null value is required.'],
            ]);
        }

        $meta = $partner->meta ?? [];

        foreach ($changes as $key => $value) {
            $meta[$key] = $value;
        }

        $partner->meta = $meta;
        $partner->save();

        return $this->jsonSuccess([
            'data' => $this->extractPermissions($partner),
        ]);
    }

    #[OA\Post(
        path: '/admin/partners/{id}/activate',
        operationId: 'admin_activate_partner',
        summary: 'Activate a partner',
        description: 'Reactivates a previously deactivated partner.',
        security: [['AgentKey' => []]],
        tags: ['Admin / Partners'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Partner activated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 404, description: 'Partner not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:partners']],
    )]
    public function activate(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireAdminScope($request, 'write:partners')) {
            return $denied;
        }

        $partner = Partner::find($id);
        if (! $partner) {
            return $this->jsonNotFound('Partner');
        }

        $partner->is_active = true;
        $partner->save();

        return $this->jsonSuccess([
            'data' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'is_active' => true,
                'message' => 'Partner activated.',
            ],
        ]);
    }

    #[OA\Post(
        path: '/admin/partners/{id}/deactivate',
        operationId: 'admin_deactivate_partner',
        summary: 'Deactivate a partner',
        description: 'Deactivates a partner. All associated agent keys become invalid.',
        security: [['AgentKey' => []]],
        tags: ['Admin / Partners'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Partner deactivated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 404, description: 'Partner not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:partners']],
    )]
    public function deactivate(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireAdminScope($request, 'write:partners')) {
            return $denied;
        }

        $partner = Partner::find($id);
        if (! $partner) {
            return $this->jsonNotFound('Partner');
        }

        $partner->is_active = false;
        $partner->save();

        return $this->jsonSuccess([
            'data' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'is_active' => false,
                'message' => 'Partner deactivated. All associated agent keys are now invalid.',
            ],
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // SCOPE ENFORCEMENT
    // ═════════════════════════════════════════════════════════════════════════

    private function requireAdminScope(Request $request, string $scope): ?JsonResponse
    {
        $agentKey = $request->attributes->get('agent_key');

        if (! $agentKey || ! $agentKey->hasAnyScope([$scope])) {
            return $this->jsonScopeError($scope);
        }

        return null;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // SERIALIZERS
    // ═════════════════════════════════════════════════════════════════════════

    private function serializePartner(Partner $partner): array
    {
        return [
            'id' => $partner->id,
            'name' => $partner->name,
            'email' => $partner->email,
            'locale' => $partner->locale,
            'currency' => $partner->currency,
            'time_zone' => $partner->time_zone,
            'is_active' => (bool) $partner->is_active,
            'avatar' => $partner->avatar,
            'created_at' => $partner->created_at,
        ];
    }

    private function extractPermissions(Partner $partner): array
    {
        $meta = $partner->meta ?? [];

        return [
            'loyalty_cards_permission' => $meta['loyalty_cards_permission'] ?? true,
            'loyalty_cards_limit' => $meta['loyalty_cards_limit'] ?? -1,
            'stamp_cards_permission' => $meta['stamp_cards_permission'] ?? true,
            'stamp_cards_limit' => $meta['stamp_cards_limit'] ?? -1,
            'vouchers_permission' => $meta['vouchers_permission'] ?? true,
            'voucher_batches_permission' => $meta['voucher_batches_permission'] ?? true,
            'vouchers_limit' => $meta['vouchers_limit'] ?? -1,
            'rewards_limit' => $meta['rewards_limit'] ?? -1,
            'staff_members_limit' => $meta['staff_members_limit'] ?? -1,
            'email_campaigns_permission' => $meta['email_campaigns_permission'] ?? true,
            'activity_permission' => $meta['activity_permission'] ?? true,
            'agent_api_permission' => $meta['agent_api_permission'] ?? false,
            'agent_keys_limit' => $meta['agent_keys_limit'] ?? 5,
            'cards_on_homepage' => $meta['cards_on_homepage'] ?? false,
        ];
    }

    private function getUsageCounts(Partner $partner): array
    {
        return [
            'loyalty_cards' => $partner->cards()->count(),
            'stamp_cards' => StampCard::where('created_by', $partner->id)->count(),
            'vouchers' => Voucher::where('created_by', $partner->id)->count(),
            'rewards' => $partner->rewards()->count(),
            'staff_members' => $partner->staff()->count(),
        ];
    }
}
