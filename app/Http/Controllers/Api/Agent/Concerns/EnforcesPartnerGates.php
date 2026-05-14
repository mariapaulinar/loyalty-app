<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Shared partner permission + limit enforcement for agent controllers.
 *
 * This trait extracts the common patterns that every partner agent controller
 * needs: getting the partner from the agent key, checking feature permissions,
 * and enforcing resource limits. Gate checks delegate to the centralized
 * EntitlementService for consistent plan and meta resolution.
 *
 * Covers permission gates:
 * - loyalty_cards_permission, stamp_cards_permission, vouchers_permission
 * - voucher_batches_permission, email_campaigns_permission, activity_permission
 *
 * Covers limit gates:
 * - loyalty_cards_limit, stamp_cards_limit, vouchers_limit
 * - staff_members_limit, rewards_limit, agent_keys_limit
 *
 * @see App\Services\EntitlementService
 * @see App\Models\Partner (permission/limit attributes via meta JSON)
 */

namespace App\Http\Controllers\Api\Agent\Concerns;

use App\Models\AgentKey;
use App\Models\Club;
use App\Models\Partner;
use App\Services\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait EnforcesPartnerGates
{
    /**
     * Get the authenticated partner from the agent key.
     *
     */
    protected function getPartner(Request $request): Partner
    {
        return $request->attributes->get('agent_key')->getPartner();
    }

    /**
     * Get the AgentKey model instance from the request.
     */
    protected function getAgentKey(Request $request): AgentKey
    {
        return $request->attributes->get('agent_key');
    }

    /**
     * Check a partner feature permission via EntitlementService.
     *
     * Returns null on success, JsonResponse on failure.
     * This pattern allows callers to do:
     *   if ($error = $this->checkPermission($partner, 'loyalty_cards_permission')) return $error;
     *
     * Accepts either the raw meta key ('loyalty_cards_permission') or the
     * EntitlementService feature key ('loyalty_cards'). Both work.
     */
    protected function checkPermission(Partner $partner, string $permission): ?JsonResponse
    {
        // Map meta-style keys to EntitlementService feature keys.
        // Most follow the pattern: strip '_permission' suffix.
        // Exceptions are mapped explicitly below.
        $keyMap = [
            'activity_permission' => 'activity_log',
        ];
        $featureKey = $keyMap[$permission] ?? str_replace('_permission', '', $permission);

        $entitlements = app(EntitlementService::class);

        if (! $entitlements->can($partner, $featureKey)) {
            return $this->jsonError(
                code: 'FEATURE_DISABLED',
                message: "The '{$permission}' feature is not enabled for this partner.",
                status: 403,
                retryStrategy: 'contact_support',
                details: [
                    'permission' => $permission,
                    'reason' => $entitlements->denyReason($partner, $featureKey),
                ],
            );
        }

        return null;
    }

    /**
     * Check a partner resource limit via EntitlementService.
     *
     * Returns null on success, JsonResponse on failure.
     *
     * @param  Partner  $partner  The partner to check
     * @param  string  $limitAttribute  Partner attribute name (e.g., 'loyalty_cards_limit')
     * @param  string  $modelClass  Model class (kept for API compatibility, not used)
     * @param  string  $resourceName  Human-readable name for error message
     */
    protected function checkLimit(
        Partner $partner,
        string $limitAttribute,
        string $modelClass,
        string $resourceName,
    ): ?JsonResponse {
        // Map meta-style keys to EntitlementService resource keys
        $resourceKeyMap = [
            'loyalty_cards_limit'  => 'cards',
            'stamp_cards_limit'    => 'stamp_cards',
            'vouchers_limit'       => 'vouchers',
            'staff_members_limit'  => 'staff',
            'rewards_limit'        => 'rewards',
            'agent_keys_limit'     => 'agent_keys',
        ];

        $resourceKey = $resourceKeyMap[$limitAttribute] ?? $limitAttribute;
        $entitlements = app(EntitlementService::class);

        if (! $entitlements->withinLimit($partner, $resourceKey)) {
            $current = $entitlements->usage($partner, $resourceKey);
            $limit = $entitlements->limit($partner, $resourceKey);

            return $this->jsonError(
                code: 'LIMIT_REACHED',
                message: "{$resourceName} limit reached ({$current}/{$limit}).",
                status: 422,
                retryStrategy: 'contact_support',
                details: [
                    'resource' => $resourceName,
                    'current' => $current,
                    'limit' => $limit,
                ],
            );
        }

        return null;
    }

    /**
     * Resolve and validate club ownership.
     *
     * Returns the Club on success, or a JsonResponse error on failure.
     * Use like:
     *   $club = $this->resolveClub($partner, $request->input('club_id'));
     *   if ($club instanceof JsonResponse) return $club;
     */
    protected function resolveClub(Partner $partner, ?string $clubId): Club|JsonResponse
    {
        if (! $clubId) {
            return $this->jsonError(
                code: 'VALIDATION_FAILED',
                message: 'club_id is required.',
                status: 422,
                retryStrategy: 'fix_request',
            );
        }

        $club = Club::where('id', $clubId)
            ->where('created_by', $partner->id)
            ->first();

        if (! $club) {
            return $this->jsonNotFound('Club');
        }

        return $club;
    }

    /**
     * Require one of the specified scopes on the agent key.
     *
     * Returns null on success, JsonResponse on failure.
     * Pass multiple scopes for OR-logic: any one grants access.
     *
     * Usage:
     *   if ($denied = $this->requireScope($request, 'write:cards')) return $denied;
     *   if ($denied = $this->requireScope($request, 'read', 'write:cards')) return $denied;
     */
    protected function requireScope(Request $request, string ...$scopes): ?JsonResponse
    {
        $agentKey = $this->getAgentKey($request);

        if (! $agentKey->hasAnyScope($scopes)) {
            return $this->jsonScopeError($scopes[0]);
        }

        return null;
    }
}
