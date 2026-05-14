<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Enforce that the authenticating agent key belongs to a specific owner type.
 *
 * Applied as route middleware to restrict endpoint groups to their intended role.
 * For example, partner endpoints require a partner-owned agent key.
 *
 * Usage in routes:
 *   ->middleware('agent.role:partner')
 *   ->middleware('agent.role:admin')
 *   ->middleware('agent.role:member')
 *
 * @see App\Models\AgentKey::PREFIXES
 * @see RewardLoyalty-100-agent.md §3
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceAgentRole
{
    /**
     * Role name → owner_type class mapping.
     * Must match AgentKey::PREFIXES keys.
     */
    private const ROLE_MAP = [
        'admin'   => \App\Models\Admin::class,
        'partner' => \App\Models\Partner::class,
        'member'  => \App\Models\Member::class,
    ];

    public function handle(Request $request, Closure $next, string $role): Response
    {
        $agentKey = $request->attributes->get('agent_key');

        if (! $agentKey) {
            return response()->json([
                'error' => true,
                'code' => 'AUTH_MISSING_KEY',
                'message' => 'Agent authentication required.',
                'retry_strategy' => 'no_retry',
            ], 401);
        }

        $expectedOwnerType = self::ROLE_MAP[$role] ?? null;

        if (! $expectedOwnerType || $agentKey->owner_type !== $expectedOwnerType) {
            return response()->json([
                'error' => true,
                'code' => 'AUTH_WRONG_ROLE',
                'message' => "This endpoint requires a {$role} agent key.",
                'retry_strategy' => 'no_retry',
                'details' => [
                    'required_role' => $role,
                    'provided_role' => array_search($agentKey->owner_type, self::ROLE_MAP) ?: 'unknown',
                ],
            ], 403);
        }

        return $next($request);
    }
}
