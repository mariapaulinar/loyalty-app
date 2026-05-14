<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API: Admin platform-wide member access (read-only).
 *
 * Provides admin-level member search and details across all partners.
 * This is strictly read-only — member lifecycle is managed through
 * partner transactions (auto-enrollment) or member self-service.
 *
 * Endpoints:
 * - GET /admin/members          → List/search all members
 * - GET /admin/members/{id}     → Show member details with card balances
 *
 * @see RewardLoyalty-100d-phase4-advanced.md §1.2
 */

namespace App\Http\Controllers\Api\Agent\Admin;

use App\Http\Controllers\Api\Agent\BaseAgentController;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AgentAdminMemberController extends BaseAgentController
{
    #[OA\Get(
        path: '/admin/members',
        operationId: 'admin_list_members',
        summary: 'List all members platform-wide',
        description: 'Returns paginated members across all partners. Supports search and active filtering. Admin-only.',
        security: [['AgentKey' => []]],
        tags: ['Admin / Members'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 25, minimum: 1, maximum: 100)),
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Search by name, email, or unique identifier', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of members', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/MemberSummary')),
                new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
            ])),
            new OA\Response(response: 403, description: 'Insufficient scope', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read:members']],
    )]
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->requireAdminScope($request, 'read:members')) {
            return $denied;
        }

        $query = Member::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('unique_identifier', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $members = $query->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());

        $data = $members->getCollection()->map(fn (Member $m) => $this->serializeMember($m));

        return $this->jsonSuccess([
            'data' => $data,
            'pagination' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/admin/members/{id}',
        operationId: 'admin_get_member',
        summary: 'Get member details with card balances',
        description: 'Returns member details with their card enrollments and point balances. Admin-only.',
        security: [['AgentKey' => []]],
        tags: ['Admin / Members'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Member details with card balances', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/AdminMemberDetail'),
            ])),
            new OA\Response(response: 404, description: 'Member not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read:members']],
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireAdminScope($request, 'read:members')) {
            return $denied;
        }

        $member = Member::find($id);
        if (! $member) {
            return $this->jsonNotFound('Member');
        }

        $cardBalances = $member->cards()
            ->with('club:id,name')
            ->get()
            ->map(fn ($card) => [
                'card_id' => $card->id,
                'card_title' => $card->title,
                'club_name' => $card->club?->name,
                'balance' => $card->getMemberBalance($member),
                'currency' => $card->currency,
            ]);

        return $this->jsonSuccess([
            'data' => array_merge(
                $this->serializeMember($member),
                ['card_balances' => $cardBalances],
            ),
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ═════════════════════════════════════════════════════════════════════════

    private function requireAdminScope(Request $request, string $scope): ?JsonResponse
    {
        $agentKey = $request->attributes->get('agent_key');

        if (! $agentKey || ! $agentKey->hasAnyScope([$scope])) {
            return $this->jsonScopeError($scope);
        }

        return null;
    }

    private function serializeMember(Member $member): array
    {
        return [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'unique_identifier' => $member->unique_identifier,
            'locale' => $member->locale,
            'is_active' => (bool) $member->is_active,
            'is_anonymous' => $member->email === null,
            'created_at' => $member->created_at,
        ];
    }
}
