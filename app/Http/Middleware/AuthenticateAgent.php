<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API key authentication middleware.
 *
 * This is the gatekeeper for all /api/agent/* routes. It authenticates
 * incoming requests using the X-Agent-Key header, following a
 * prefix-based candidate narrowing + bcrypt verification strategy
 * (no full-table scans, no plaintext storage).
 *
 * Flow:
 * 1. Extract key from X-Agent-Key header
 * 2. Detect prefix length from key format (rl_admin_, rl_agent_, rl_member_)
 * 3. Query agent_keys by key_prefix (indexed, fast)
 * 4. Verify raw key against bcrypt hash (handles prefix collisions)
 * 5. Check key validity (is_active, expires_at)
 * 6. Check owner activity (is_active on Admin/Partner/Member)
 * 7. Defense-in-depth: reject anonymous member keys
 * 8. Bind AgentKey + owner to request for downstream controllers
 * 9. Touch last_used_at (debounced: >5 min intervals, after response)
 *
 * @see App\Models\AgentKey
 * @see App\Http\Middleware\AuthenticatePartnerApi (pattern source)
 * @see RewardLoyalty-100-agent.md §3
 */

namespace App\Http\Middleware;

use App\Models\AgentKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawKey = $request->header('X-Agent-Key');

        if (! $rawKey) {
            return $this->errorResponse(
                code: 'AUTH_MISSING_KEY',
                message: 'Missing X-Agent-Key header.',
                status: 401,
            );
        }

        // 1. Detect prefix length from key format
        $prefixLength = AgentKey::detectPrefixLength($rawKey);
        if (! $prefixLength) {
            return $this->errorResponse(
                code: 'AUTH_INVALID_KEY',
                message: 'Invalid agent key format.',
                status: 401,
            );
        }

        // 2. Narrow candidates by indexed prefix (no full-table scan)
        $prefix = substr($rawKey, 0, $prefixLength);
        $candidates = AgentKey::where('key_prefix', $prefix)->get();

        if ($candidates->isEmpty()) {
            return $this->errorResponse(
                code: 'AUTH_INVALID_KEY',
                message: 'Invalid agent key.',
                status: 401,
            );
        }

        // 3. Verify against bcrypt hash (handles prefix collisions)
        $agentKey = null;
        foreach ($candidates as $candidate) {
            if (Hash::check($rawKey, $candidate->key_hash)) {
                $agentKey = $candidate;
                break;
            }
        }

        if (! $agentKey) {
            return $this->errorResponse(
                code: 'AUTH_INVALID_KEY',
                message: 'Invalid agent key.',
                status: 401,
            );
        }

        // 4. Check key is active (revoked keys remain for audit trail)
        if (! $agentKey->is_active) {
            return $this->errorResponse(
                code: 'AUTH_KEY_REVOKED',
                message: 'This agent key has been revoked.',
                status: 401,
            );
        }

        // 5. Check expiration
        if ($agentKey->expires_at && $agentKey->expires_at->isPast()) {
            return $this->errorResponse(
                code: 'AUTH_KEY_EXPIRED',
                message: 'This agent key has expired.',
                status: 401,
            );
        }

        // 6. Check owner is active (deactivated partner = all keys invalid)
        if (! $agentKey->ownerIsActive()) {
            return $this->errorResponse(
                code: 'AUTH_OWNER_INACTIVE',
                message: 'The account associated with this key is inactive.',
                status: 403,
                retryStrategy: 'contact_support',
            );
        }

        // 7. Defense-in-depth: reject anonymous member keys
        if ($agentKey->owner_type === \App\Models\Member::class) {
            $member = $agentKey->owner;
            if ($member && method_exists($member, 'isAnonymous') && $member->isAnonymous()) {
                return $this->errorResponse(
                    code: 'AUTH_ANONYMOUS_MEMBER',
                    message: 'Anonymous members cannot use agent keys.',
                    status: 403,
                );
            }
        }

        // 8. Bind to request for downstream use
        $request->attributes->set('agent_key', $agentKey);
        $request->attributes->set('agent_owner', $agentKey->owner);

        // 9. Process request first — no DB write on denied requests
        $response = $next($request);

        // 10. Debounced touch after successful response
        $agentKey->touchLastUsed();

        return $response;
    }

    /**
     * Build a standardized agent error response.
     *
     * All agent errors follow the same envelope format so AI agents
     * can parse and react to errors programmatically.
     */
    private function errorResponse(
        string $code,
        string $message,
        int $status,
        string $retryStrategy = 'no_retry',
        array $details = [],
    ): Response {
        $body = [
            'error' => true,
            'code' => $code,
            'message' => $message,
            'retry_strategy' => $retryStrategy,
        ];

        if (! empty($details)) {
            $body['details'] = $details;
        }

        return response()->json($body, $status);
    }
}
