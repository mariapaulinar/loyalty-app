<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * GET /api/agent/v1/tools
 *
 * Self-describing tool discovery endpoint. Returns machine-readable
 * tool definitions scoped to the authenticated key's role and scopes.
 *
 * An agent calls this once on startup to learn what it can do, then
 * passes the tool list to its LLM for function-calling / tool-use.
 *
 * This endpoint is NOT included in its own tool list — a tool that
 * lists tools is meta-noise for agents.
 *
 * Security:
 * - This route sits outside the partner-only middleware group because
 *   it serves all roles (partner, admin, member). But partner keys
 *   are still subject to the agent_api_permission gate: if a partner's
 *   API access has been revoked, /tools returns 403 FEATURE_DISABLED
 *   — the same response the EnsurePartnerAgentApiEnabled middleware
 *   would return on any partner endpoint.
 *
 * Caching:
 * - Key: agent-tools:{spec_version}:{role}:{scopes_hash}:{perm_hash}:{format}
 * - TTL: 24 hours (tool defs are static)
 * - Invalidation: automatic — spec version (filemtime) is part of the
 *   cache key, so regenerating the spec instantly busts old entries.
 *   Permissions hash ensures different partner plans get different sets.
 *
 * @see App\Services\Agent\AgentToolService
 * @see App\Http\Middleware\EnsurePartnerAgentApiEnabled
 * @see RewardLoyalty-102-api-agents.md §6.5
 */

namespace App\Http\Controllers\Api\Agent;

use App\Services\Agent\AgentToolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

class AgentToolsController extends BaseAgentController
{
    #[OA\Get(
        path: '/tools',
        operationId: 'discover_tools',
        summary: 'Discover available API tools for this key',
        description: 'Returns tool definitions scoped to the authenticated key\'s role and permissions. '
            . 'Use the format parameter to get definitions compatible with your agent framework. '
            . 'This endpoint is excluded from its own tool list.',
        security: [['AgentKey' => []]],
        tags: ['Tools'],
        parameters: [
            new OA\Parameter(
                name: 'format',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['openai', 'anthropic', 'mcp', 'generic'], default: 'generic'),
                description: 'Output format for tool definitions',
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tool definitions for the authenticated key', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'tools', type: 'array', items: new OA\Items(type: 'object'), description: 'Framework-specific tool definitions'),
                new OA\Property(property: 'meta', properties: [
                    new OA\Property(property: 'role', type: 'string'),
                    new OA\Property(property: 'format', type: 'string'),
                    new OA\Property(property: 'tool_count', type: 'integer'),
                    new OA\Property(property: 'cached', type: 'boolean'),
                ], type: 'object'),
            ])),
            new OA\Response(response: 403, description: 'Partner API access revoked (agent_api_permission disabled)', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
            new OA\Response(response: 422, description: 'Invalid format parameter', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
            new OA\Response(response: 503, description: 'Tool definitions not yet available', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => []],
    )]
    public function __invoke(Request $request, AgentToolService $service): JsonResponse
    {
        $agentKey = $request->attributes->get('agent_key');
        $format = $request->input('format', 'generic');

        // Validate format
        if (! in_array($format, AgentToolService::supportedFormats(), true)) {
            return $this->jsonError(
                code: 'INVALID_FORMAT',
                message: "Unsupported format: {$format}. Use: " . implode(', ', AgentToolService::supportedFormats()) . '.',
                status: 422,
                retryStrategy: 'fix_request',
            );
        }

        // Check spec exists
        if (! $service->specExists()) {
            return $this->jsonError(
                code: 'TOOLS_UNAVAILABLE',
                message: 'Tool definitions are not yet available. Contact the platform administrator.',
                status: 503,
                retryStrategy: 'backoff',
            );
        }

        // ─────────────────────────────────────────────────────────────────
        // Top-level partner API gate.
        // This route is outside the partner middleware group (it serves all
        // roles), but partner keys are still subject to agent_api_permission.
        // If a partner's API access has been revoked, /tools must deny
        // access — otherwise a revoked partner can still discover its tools.
        // This mirrors the EnsurePartnerAgentApiEnabled middleware behavior.
        // ─────────────────────────────────────────────────────────────────
        $disabledPermissions = [];
        $role = $agentKey->getRoleName();

        if ($role === 'partner') {
            $partner = $agentKey->getPartner();

            if (! $partner || ! $partner->agent_api_permission) {
                return $this->jsonError(
                    code: 'FEATURE_DISABLED',
                    message: 'Agent API access has been revoked for this partner.',
                    status: 403,
                    retryStrategy: 'contact_support',
                    details: ['permission' => 'agent_api_permission'],
                );
            }

            // Sub-feature permission filtering.
            // Partner endpoints gated by feature flags (loyalty_cards_permission,
            // stamp_cards_permission, vouchers_permission) are excluded from the
            // tool list when the flag is false — the agent would get 403 anyway.
            $featureFlags = [
                'loyalty_cards_permission',
                'stamp_cards_permission',
                'vouchers_permission',
            ];
            foreach ($featureFlags as $flag) {
                if (! $partner->$flag) {
                    $disabledPermissions[] = $flag;
                }
            }
        }

        // Cache key includes spec version (filemtime) so regenerating
        // agent-api.json automatically busts stale tool definitions.
        // Also includes permissions hash so partners with different plans
        // get different tool sets.
        $keyScopes = $agentKey->scopes ?? [];
        $scopesHash = md5(json_encode(collect($keyScopes)->sort()->values()->all()));
        $permHash = md5(json_encode($disabledPermissions));
        $specVersion = $service->specVersion();
        $cacheKey = "agent-tools:{$specVersion}:{$role}:{$scopesHash}:{$permHash}:{$format}";

        $cached = Cache::has($cacheKey);
        $output = Cache::remember($cacheKey, now()->addHours(24), function () use ($service, $role, $keyScopes, $format, $disabledPermissions) {
            $hasAdminScope = in_array('admin', $keyScopes, true);
            $tools = $service->extractTools($role, $keyScopes, $hasAdminScope, $disabledPermissions);

            return [
                'formatted' => $service->formatTools($tools, $format),
                'count'     => count($tools),
            ];
        });

        return $this->jsonSuccess([
            'tools' => $output['formatted'],
            'meta'  => [
                'role'       => $role,
                'format'     => $format,
                'tool_count' => $output['count'],
                'cached'     => $cached,
            ],
        ]);
    }
}
