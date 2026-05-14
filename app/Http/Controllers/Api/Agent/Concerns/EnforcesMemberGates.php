<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Shared member permission + scope enforcement for agent controllers.
 *
 * This trait extracts the common patterns that every member agent controller
 * needs: getting the member from the agent key, requiring scopes, and
 * resolving resources that belong to the member.
 *
 * Design: Member agent endpoints are mostly READ-only, with limited write
 * capabilities (profile update, reward claim). The member is always the
 * key owner — no cross-member access is possible.
 *
 * @see App\Models\Member
 * @see App\Models\AgentKey::getMember()
 * @see RewardLoyalty-100d-phase4-advanced.md §2
 */

namespace App\Http\Controllers\Api\Agent\Concerns;

use App\Models\AgentKey;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait EnforcesMemberGates
{
    /**
     * Get the authenticated member from the agent key.
     */
    protected function getMember(Request $request): Member
    {
        return $request->attributes->get('agent_key')->getMember();
    }

    /**
     * Get the AgentKey model instance from the request.
     */
    protected function getAgentKey(Request $request): AgentKey
    {
        return $request->attributes->get('agent_key');
    }

    /**
     * Require one of the specified scopes on the agent key.
     *
     * Returns null on success, JsonResponse on failure.
     * Pass multiple scopes for OR-logic: any one grants access.
     *
     * Usage:
     *   if ($denied = $this->requireScope($request, 'read')) return $denied;
     *   if ($denied = $this->requireScope($request, 'read', 'write:profile')) return $denied;
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
