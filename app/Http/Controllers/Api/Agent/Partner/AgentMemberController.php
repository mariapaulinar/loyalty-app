<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API: Member read access for partners.
 *
 * READ-ONLY + BALANCE. No member CREATE/UPDATE/DELETE via agent API.
 * Members self-register or are auto-enrolled via transactions.
 * This matches MemberDataDefinition which only supports 'list' and 'export'.
 *
 * @see RewardLoyalty-100b-phase2-core-endpoints.md §2.5
 */

namespace App\Http\Controllers\Api\Agent\Partner;

use App\Http\Controllers\Api\Agent\BaseAgentController;
use App\Http\Controllers\Api\Agent\Concerns\EnforcesPartnerGates;
use App\Models\Card;
use App\Models\Member;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AgentMemberController extends BaseAgentController
{
    use EnforcesPartnerGates;

    #[OA\Get(
        path: '/partner/members',
        operationId: 'list_members',
        summary: 'List members who interacted with this partner',
        description: 'Returns paginated members that have transactions with this partner\'s cards. Searchable by name, email, member number, or unique identifier.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Members'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 25, minimum: 1, maximum: 100)),
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Search by name, email, member number, or unique identifier', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of members', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PartnerMemberSummary')),
                new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
            ])),
            new OA\Response(response: 403, description: 'Insufficient scope', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read']],
    )]
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        $query = Member::whereHas('cards', function ($q) use ($partner) {
            $q->where('cards.created_by', $partner->id);
        });

        // Search
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('member_number', 'like', "%{$search}%")
                    ->orWhere('unique_identifier', 'like', "%{$search}%");
            });
        }

        $members = $query->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());

        return $this->jsonSuccess([
            'data' => $members->getCollection()->map(
                fn (Member $member) => $this->serializePartnerMember($member)
            )->values(),
            'pagination' => $this->paginationMeta($members),
        ]);
    }

    #[OA\Get(
        path: '/partner/members/{id}',
        operationId: 'get_member',
        summary: 'Get a specific member',
        description: 'Returns member details. Accepts UUID as path parameter. Member must have interacted with this partner\'s cards.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Members'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Member UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Member details', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/PartnerMemberSummary'),
            ])),
            new OA\Response(response: 404, description: 'Member not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read']],
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        $member = $this->resolveMember($id);

        if (! $member) {
            return $this->jsonNotFound('Member');
        }

        // Verify member has interacted with this partner's cards (tenant isolation)
        $hasInteraction = $member->cards()
            ->where('cards.created_by', $partner->id)
            ->exists();

        if (! $hasInteraction) {
            return $this->jsonNotFound('Member');
        }

        return $this->jsonSuccess([
            'data' => $this->serializePartnerMember($member),
        ]);
    }

    #[OA\Get(
        path: '/partner/members/{id}/balance/{cardId}',
        operationId: 'get_member_balance',
        summary: 'Get a member\'s balance on a specific card',
        description: 'Returns the member\'s point balance for a specific loyalty card. Both the member and card must belong to this partner.',
        security: [['AgentKey' => []]],
        tags: ['Partner / Members'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Member UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'cardId', in: 'path', required: true, description: 'Loyalty card UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Member balance on card', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'member_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'card_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'balance', type: 'integer'),
                    new OA\Property(property: 'currency', type: 'string'),
                ], type: 'object'),
            ])),
            new OA\Response(response: 404, description: 'Member or card not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read']],
    )]
    public function balance(Request $request, string $id, string $cardId): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read')) {
            return $denied;
        }

        $partner = $this->getPartner($request);

        $member = $this->resolveMember($id);
        if (! $member) {
            return $this->jsonNotFound('Member');
        }

        // Verify member has interacted with this partner's cards (tenant isolation)
        $hasInteraction = $member->cards()
            ->where('cards.created_by', $partner->id)
            ->exists();

        if (! $hasInteraction) {
            return $this->jsonNotFound('Member');
        }

        $card = Card::where('id', $cardId)
            ->where('created_by', $partner->id)
            ->first();

        if (! $card) {
            return $this->jsonNotFound('Card');
        }

        $balance = $card->getMemberBalance($member);

        return $this->jsonSuccess([
            'data' => [
                'member_id' => $member->id,
                'card_id' => $card->id,
                'balance' => $balance,
                'currency' => $card->currency,
            ],
        ]);
    }

    private function serializePartnerMember(Member $member): array
    {
        return [
            'id' => $member->id,
            'unique_identifier' => $member->unique_identifier,
            'name' => $member->name,
            'email' => $member->email,
            'locale' => $member->locale,
            'currency' => $member->currency,
            'time_zone' => $member->time_zone,
            'last_login_at' => $this->serializeDateTime($member->last_login_at),
            'created_at' => $this->serializeDateTime($member->created_at),
            'updated_at' => $this->serializeDateTime($member->updated_at),
            'avatar' => $member->avatar,
            'is_anonymous' => $member->email === null,
        ];
    }
}
