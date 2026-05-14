<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API audit logging middleware.
 *
 * Automatically logs every authenticated agent API request using the
 * existing ActivityLogService. Runs AFTER the response is generated
 * (terminate phase) so it never adds latency to the API response.
 *
 * What gets logged:
 * - WHO: Agent key owner (Partner, Admin, or Member) as causer
 * - WHAT: HTTP method + endpoint + status code as event/description
 * - WITH: Agent key (as subject), IP, key prefix, scopes, response status
 * - WHEN: Timestamp (automatic via Spatie activity log)
 *
 * Design decisions:
 * - Uses TerminableMiddleware so logging happens AFTER response is sent
 * - Reuses ActivityLogService::logAgentRequest() — no new logging system
 * - Stores agent-specific metadata (key_prefix, scopes) in properties
 * - Does NOT log request/response bodies (security: may contain PII)
 * - Does NOT log failed auth attempts (AuthenticateAgent handles those)
 * - Only logs requests that made it past authentication
 *
 * @see ActivityLogService::logAgentRequest()
 * @see AuthenticateAgent (sets agent_key on request attributes)
 * @see RewardLoyalty-100-agent.md
 */

namespace App\Http\Middleware;

use App\Models\AgentKey;
use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAgentActivity
{
    public function __construct(
        protected ActivityLogService $activityLog,
    ) {}

    /**
     * Handle the request — just pass through.
     * Actual logging happens in terminate().
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Log the agent API request after the response has been sent.
     *
     * This runs in the terminate phase — the client already has their
     * response, so this DB write adds zero latency to the API.
     */
    public function terminate(Request $request, Response $response): void
    {
        /** @var AgentKey|null $agentKey */
        $agentKey = $request->attributes->get('agent_key');

        // No agent key = request didn't pass auth (nothing to log here,
        // AuthenticateAgent already returned an error response)
        if (! $agentKey) {
            return;
        }

        $method = $request->method();
        $path = '/' . ltrim($request->path(), '/');
        $statusCode = $response->getStatusCode();

        $this->activityLog->logAgentRequest(
            endpoint: $path,
            method: $method,
            agentKey: $agentKey,
            statusCode: $statusCode,
            context: [
                'key_prefix' => $agentKey->key_prefix,
                'owner_type' => class_basename($agentKey->owner_type),
                'scopes' => $agentKey->scopes,
            ],
        );
    }
}
