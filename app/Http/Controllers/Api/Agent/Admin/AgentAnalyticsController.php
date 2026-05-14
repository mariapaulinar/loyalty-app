<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API: Admin platform analytics.
 *
 * High-level platform metrics for CRM/dashboard integration.
 * These endpoints provide overview stats and per-partner breakdowns
 * without exposing individual records.
 *
 * Endpoints:
 * - GET /admin/analytics/overview        → Platform-wide metrics
 * - GET /admin/analytics/partners/{id}   → Per-partner metrics
 *
 * @see RewardLoyalty-100d-phase4-advanced.md §1.3
 */

namespace App\Http\Controllers\Api\Agent\Admin;

use App\Http\Controllers\Api\Agent\BaseAgentController;
use App\Models\Card;
use App\Models\Member;
use App\Models\Partner;
use App\Models\StampCard;
use App\Models\Transaction;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AgentAnalyticsController extends BaseAgentController
{
    #[OA\Get(
        path: '/admin/analytics/overview',
        operationId: 'admin_analytics_overview',
        summary: 'Get platform-wide analytics',
        description: 'Returns aggregated platform metrics including partner, member, and transaction counts.',
        security: [['AgentKey' => []]],
        tags: ['Admin / Analytics'],
        responses: [
            new OA\Response(response: 200, description: 'Platform analytics', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', ref: '#/components/schemas/AnalyticsOverview'),
            ])),
            new OA\Response(response: 403, description: 'Insufficient scope', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read:analytics']],
    )]
    public function overview(Request $request): JsonResponse
    {
        if ($denied = $this->requireAdminScope($request, 'read:analytics')) {
            return $denied;
        }

        return $this->jsonSuccess([
            'data' => [
                'total_partners' => Partner::count(),
                'active_partners' => Partner::where('is_active', true)->count(),
                'total_members' => Member::count(),
                'total_cards' => Card::count(),
                'total_stamp_cards' => StampCard::count(),
                'total_vouchers' => Voucher::count(),
                'transactions_today' => Transaction::whereDate('created_at', today())->count(),
                'transactions_this_week' => Transaction::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ])->count(),
                'transactions_this_month' => Transaction::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/admin/analytics/partners/{id}',
        operationId: 'admin_partner_metrics',
        summary: 'Get per-partner analytics',
        description: 'Returns usage metrics for a specific partner.',
        security: [['AgentKey' => []]],
        tags: ['Admin / Analytics'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Partner analytics', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 404, description: 'Partner not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read:analytics']],
    )]
    public function partnerMetrics(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->requireAdminScope($request, 'read:analytics')) {
            return $denied;
        }

        $partner = Partner::find($id);
        if (! $partner) {
            return $this->jsonNotFound('Partner');
        }

        return $this->jsonSuccess([
            'data' => [
                'partner_id' => $partner->id,
                'partner_name' => $partner->name,
                'is_active' => (bool) $partner->is_active,
                'loyalty_cards' => $partner->cards()->count(),
                'stamp_cards' => StampCard::where('created_by', $partner->id)->count(),
                'vouchers' => Voucher::where('created_by', $partner->id)->count(),
                'rewards' => $partner->rewards()->count(),
                'staff_members' => $partner->staff()->count(),
                'total_transactions' => Transaction::where('created_by', $partner->id)->count(),
                'transactions_today' => Transaction::where('created_by', $partner->id)
                    ->whereDate('created_at', today())
                    ->count(),
                'total_members_enrolled' => $partner->cards()
                    ->withCount('members')
                    ->get()
                    ->sum('members_count'),
            ],
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════

    private function requireAdminScope(Request $request, string $scope): ?JsonResponse
    {
        $agentKey = $request->attributes->get('agent_key');

        if (! $agentKey || ! $agentKey->hasAnyScope([$scope])) {
            return $this->jsonScopeError($scope);
        }

        return null;
    }
}
