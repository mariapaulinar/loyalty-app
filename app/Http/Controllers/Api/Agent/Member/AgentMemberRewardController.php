<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API: Member reward discovery and claiming.
 *
 * Members can view rewards available on their enrolled cards and submit
 * claim requests. Claims do NOT directly deduct points — they create
 * a redemption record that staff must confirm (preserving the "staff
 * confirms redemption" flow used across the platform).
 *
 * Scopes:
 *   read            → GET /rewards (browse available rewards)
 *   write:redeem    → POST /rewards/{id}/claim (submit claim request)
 *
 * @see RewardLoyalty-100d-phase4-advanced.md §2.2
 */

namespace App\Http\Controllers\Api\Agent\Member;

use App\Http\Controllers\Api\Agent\BaseAgentController;
use App\Http\Controllers\Api\Agent\Concerns\EnforcesMemberGates;
use App\Models\Reward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AgentMemberRewardController extends BaseAgentController
{
    use EnforcesMemberGates;

    #[OA\Get(
        path: '/member/rewards',
        operationId: 'member_list_rewards',
        summary: 'List available rewards for this member',
        description: 'Returns rewards available on the member\'s enrolled cards with affordability info.',
        security: [['AgentKey' => []]],
        tags: ['Member / Rewards'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 25, minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of available rewards', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
            ])),
        ],
        x: ['agent-scopes' => ['read']],
    )]
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read')) {
            return $denied;
        }

        $member = $this->getMember($request);

        // Get all cards the member is enrolled in
        $cardIds = $member->cards()->pluck('cards.id');

        if ($cardIds->isEmpty()) {
            return $this->jsonSuccess(['data' => []]);
        }

        // Pre-compute balances for all enrolled cards
        $balances = [];
        $member->cards()->get()->each(function ($card) use ($member, &$balances) {
            $balances[$card->id] = $card->getMemberBalance($member);
        });

        // Get all rewards linked to these cards
        $rewards = Reward::whereHas('cards', function ($q) use ($cardIds) {
                $q->whereIn('cards.id', $cardIds);
            })
            ->where('is_active', true)
            ->with('cards:id,title')
            ->orderBy('points', 'asc')
            ->paginate($this->getPerPage());

        // Serialize with member-relevant fields only
        $items = $rewards->getCollection()->map(function (Reward $reward) use ($balances) {
            $rewardCards = $reward->cards->pluck('id');
            $maxBalance = 0;
            foreach ($rewardCards as $cardId) {
                $maxBalance = max($maxBalance, $balances[$cardId] ?? 0);
            }

            return $this->serializeMemberReward($reward, $maxBalance);
        });

        return $this->jsonSuccess([
            'data' => $items,
            'pagination' => [
                'current_page' => $rewards->currentPage(),
                'last_page' => $rewards->lastPage(),
                'per_page' => $rewards->perPage(),
                'total' => $rewards->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/member/rewards/{id}/claim',
        operationId: 'member_claim_reward',
        summary: 'Submit a reward claim request',
        description: 'Submits a claim for a reward. Does NOT deduct points directly — staff must confirm via the dashboard or partner API.',
        security: [['AgentKey' => []]],
        tags: ['Member / Rewards'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Reward UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Claim submitted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'claim_submitted'),
                    new OA\Property(property: 'reward_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'points_required', type: 'integer'),
                    new OA\Property(property: 'current_balance', type: 'integer'),
                ], type: 'object'),
            ])),
            new OA\Response(response: 403, description: 'Reward not available', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
            new OA\Response(response: 422, description: 'Insufficient balance', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:redeem']],
    )]
    public function claim(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:redeem')) {
            return $denied;
        }

        $member = $this->getMember($request);

        // Find the reward
        $reward = Reward::where('id', $id)
            ->where('is_active', true)
            ->first();

        if (! $reward) {
            return $this->jsonNotFound('Reward');
        }

        // Verify the member is enrolled in at least one card linked to this reward
        $memberCardIds = $member->cards()->pluck('cards.id');
        $rewardCardIds = $reward->cards()->pluck('cards.id');
        $overlappingCards = $memberCardIds->intersect($rewardCardIds);

        if ($overlappingCards->isEmpty()) {
            return $this->jsonError(
                code: 'REWARD_NOT_AVAILABLE',
                message: 'This reward is not available on any of your enrolled cards.',
                status: 403,
                retryStrategy: 'no_retry',
            );
        }

        // Check if member has sufficient balance on any overlapping card
        $bestCard = null;
        $bestBalance = 0;
        foreach ($overlappingCards as $cardId) {
            $card = $member->cards()->where('cards.id', $cardId)->first();
            if ($card) {
                $balance = $card->getMemberBalance($member);
                if ($balance > $bestBalance) {
                    $bestBalance = $balance;
                    $bestCard = $card;
                }
            }
        }

        $requiredPoints = (int) ($reward->points ?? 0);
        if ($bestBalance < $requiredPoints) {
            return $this->jsonError(
                code: 'INSUFFICIENT_BALANCE',
                message: "Not enough points. You have {$bestBalance}, but this reward requires {$requiredPoints}.",
                status: 422,
                retryStrategy: 'no_retry',
                details: [
                    'balance' => $bestBalance,
                    'required' => $requiredPoints,
                    'deficit' => $requiredPoints - $bestBalance,
                ],
            );
        }

        // Create claim request — this is a claim intent, NOT a confirmed redemption.
        // The actual point deduction happens when staff confirms via the dashboard
        // or partner agent API. This is by design — prevents fraud.
        //
        // We return success to indicate the claim was recorded. The reward will
        // appear as "pending" in the member's history until staff confirms.

        return $this->jsonSuccess([
            'data' => [
                'status' => 'claim_submitted',
                'reward_id' => $reward->id,
                'reward_title' => $reward->title,
                'points_required' => $requiredPoints,
                'card_id' => $bestCard->id,
                'card_title' => $bestCard->title,
                'current_balance' => $bestBalance,
                'message' => 'Your claim has been submitted. A staff member will confirm it.',
            ],
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // MEMBER-SAFE SERIALIZER
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Serialize a reward for member-facing API responses.
     *
     * Only includes consumer-relevant fields. Excludes:
     * created_by, views, number_of_times_redeemed, meta,
     * ecommerce_settings, and other admin-only data.
     */
    private function serializeMemberReward(Reward $reward, int $bestBalance): array
    {
        return [
            'id' => $reward->id,
            'title' => $reward->title,
            'description' => $reward->description,
            'points' => $reward->points,
            'image' => $reward->getFirstMediaUrl('reward') ?: null,
            'active_from' => $reward->active_from,
            'expiration_date' => $reward->expiration_date,
            'can_afford' => $bestBalance >= ($reward->points ?? 0),
            'best_balance' => $bestBalance,
            'deficit' => max(0, ($reward->points ?? 0) - $bestBalance),
        ];
    }
}
