<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API: Member wallet — balance, cards, and transaction history.
 *
 * This is the most consumed member endpoint. The "wallet overview" widget
 * on webshops calls GET /balance to show what the member has across all
 * loyalty programs they participate in.
 *
 * Design:
 * - Members can only see cards they've transacted with (enrolled via card_member pivot)
 * - Card details stay member-safe and do not expose internal club structure
 * - Balance is calculated from active (non-expired) transactions
 * - Transaction history is paginated and filtered by card
 * - Transaction responses are filtered to member-relevant fields only
 *   (no partner emails, internal config values, or admin metadata)
 *
 * Scopes:
 *   read → All endpoints in this controller
 *
 * @see Card::getMemberBalance()
 * @see RewardLoyalty-100d-phase4-advanced.md §2.1
 */

namespace App\Http\Controllers\Api\Agent\Member;

use App\Http\Controllers\Api\Agent\BaseAgentController;
use App\Http\Controllers\Api\Agent\Concerns\EnforcesMemberGates;
use App\Models\Card;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AgentBalanceController extends BaseAgentController
{
    use EnforcesMemberGates;

    #[OA\Get(
        path: '/member/balance',
        operationId: 'member_get_balance',
        summary: 'Get all card balances for this member',
        description: 'Returns the member\'s point balance across all enrolled loyalty cards in one call.',
        security: [['AgentKey' => []]],
        tags: ['Member / Wallet'],
        responses: [
            new OA\Response(response: 200, description: 'Card balances', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'card_id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'card_title', type: 'string'),
                        new OA\Property(property: 'balance', type: 'integer'),
                        new OA\Property(property: 'currency', type: 'string', example: 'points'),
                    ],
                    type: 'object',
                )),
            ])),
        ],
        x: ['agent-scopes' => ['read']],
    )]
    public function balance(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read')) {
            return $denied;
        }

        $member = $this->getMember($request);

        $cards = $member->cards()
            ->get()
            ->map(fn (Card $card) => [
                'card_id' => $card->id,
                'card_title' => $card->title,
                'balance' => $card->getMemberBalance($member),
                'currency' => $card->currency ?? 'points',
            ]);

        return $this->jsonSuccess(['data' => $cards]);
    }

    #[OA\Get(
        path: '/member/cards',
        operationId: 'member_list_cards',
        summary: 'List all enrolled loyalty cards',
        description: 'Returns paginated loyalty cards that the member has transacted with.',
        security: [['AgentKey' => []]],
        tags: ['Member / Wallet'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 25, minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of enrolled cards', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/MemberCard')),
                new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
            ])),
        ],
        x: ['agent-scopes' => ['read']],
    )]
    public function cards(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read')) {
            return $denied;
        }

        $member = $this->getMember($request);

        $cards = $member->cards()->paginate($this->getPerPage());

        $items = $cards->getCollection()->map(function (Card $card) use ($member) {
            return $this->serializeMemberCard($card, $member);
        });

        return $this->jsonSuccess([
            'data' => $items,
            'pagination' => [
                'current_page' => $cards->currentPage(),
                'last_page' => $cards->lastPage(),
                'per_page' => $cards->perPage(),
                'total' => $cards->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/member/cards/{id}',
        operationId: 'member_get_card',
        summary: 'Get a single enrolled loyalty card',
        description: 'Returns a card\'s details and the member\'s balance on it.',
        security: [['AgentKey' => []]],
        tags: ['Member / Wallet'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Card details with balance', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/MemberCard'),
            ])),
            new OA\Response(response: 404, description: 'Card not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read']],
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read')) {
            return $denied;
        }

        $member = $this->getMember($request);

        // Only show cards the member is enrolled in (via card_member pivot)
        $card = $member->cards()
            ->where('cards.id', $id)
            ->first();

        if (! $card) {
            return $this->jsonNotFound('Card');
        }

        return $this->jsonSuccess([
            'data' => $this->serializeMemberCard($card, $member),
        ]);
    }

    #[OA\Get(
        path: '/member/transactions',
        operationId: 'member_list_transactions',
        summary: 'Get transaction history across all cards',
        description: 'Returns paginated transaction history for the authenticated member.',
        security: [['AgentKey' => []]],
        tags: ['Member / Wallet'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 25, minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated transaction history', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/MemberTransaction')),
                new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
            ])),
        ],
        x: ['agent-scopes' => ['read']],
    )]
    public function transactions(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read')) {
            return $denied;
        }

        $member = $this->getMember($request);

        $transactions = Transaction::where('member_id', $member->id)
            ->with(['card:id,title'])
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());

        $items = $transactions->getCollection()->map(
            fn (Transaction $tx) => $this->serializeMemberTransaction($tx)
        );

        return $this->jsonSuccess([
            'data' => $items,
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/member/transactions/{cardId}',
        operationId: 'member_card_transactions',
        summary: 'Get transaction history for a specific card',
        description: 'Returns paginated transaction history for the member on a specific enrolled card.',
        security: [['AgentKey' => []]],
        tags: ['Member / Wallet'],
        parameters: [
            new OA\Parameter(name: 'cardId', in: 'path', required: true, description: 'Loyalty card UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 25, minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated transaction history', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/MemberTransaction')),
                new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
            ])),
            new OA\Response(response: 404, description: 'Card not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read']],
    )]
    public function cardTransactions(Request $request, string $cardId): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read')) {
            return $denied;
        }

        $member = $this->getMember($request);

        // Verify the member is enrolled in this card
        $isEnrolled = $member->cards()->where('cards.id', $cardId)->exists();
        if (! $isEnrolled) {
            return $this->jsonNotFound('Card');
        }

        $transactions = Transaction::where('member_id', $member->id)
            ->where('card_id', $cardId)
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());

        $items = $transactions->getCollection()->map(
            fn (Transaction $tx) => $this->serializeMemberTransaction($tx)
        );

        return $this->jsonSuccess([
            'data' => $items,
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // MEMBER-SAFE SERIALIZERS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Serialize a transaction for member-facing API responses.
     *
     * Only includes fields that a member would see in the dashboard
     * history component. Excludes: partner emails, staff emails,
     * internal config values, admin metadata, created_by, etc.
     *
     * @see resources/views/components/member/history.blade.php
     */
    private function serializeMemberTransaction(Transaction $tx): array
    {
        return [
            'id' => $tx->id,
            'card_id' => $tx->card_id,
            'card_title' => $tx->card?->title,
            'event' => $tx->event,
            'points' => $tx->points,
            'points_used' => $tx->points_used,
            'purchase_amount' => $tx->purchase_amount,
            'currency' => $tx->currency,
            'reward_title' => $tx->reward_title,
            'reward_points' => $tx->reward_points,
            'note' => $tx->note,
            'expires_at' => $tx->expires_at?->toIso8601String(),
            'created_at' => $tx->created_at?->toIso8601String(),
        ];
    }

    /**
     * Serialize a card for member-facing API responses.
     *
     * Includes the member's balance and card display info.
     * Excludes internal admin fields and club ownership metadata.
     */
    private function serializeMemberCard(Card $card, $member): array
    {
        return [
            'id' => $card->id,
            'name' => $card->name,
            'title' => $card->title,
            'description' => $card->description ?? null,
            'currency' => $card->currency ?? 'points',
            'balance' => $card->getMemberBalance($member),
            'bg_color' => $card->bg_color,
            'text_color' => $card->text_color,
            'is_active' => (bool) $card->is_active,
        ];
    }
}
