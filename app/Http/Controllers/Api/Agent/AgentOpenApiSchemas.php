<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Shared OpenAPI schemas for the Agent API.
 *
 * This class serves as the OpenAPI "info" block and shared component
 * registry for the Agent API specification. All reusable schemas
 * (Pagination, AgentError, resource models) live here so that
 * endpoint annotations can $ref them instead of duplicating.
 *
 * Generate: php artisan l5-swagger:generate agent
 * Output:   storage/api-docs/agent-api.json
 *
 * @see RewardLoyalty-102-api-agents.md §6.3
 */

namespace App\Http\Controllers\Api\Agent;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        title: 'Lealmi Agent API',
        version: '1.0.0',
        description: 'Machine-to-machine API for AI agents, POS systems, and automation platforms. '
            . 'Authenticate with X-Agent-Key header. ',
    ),
    servers: [
        new OA\Server(url: '/api/agent/v1', description: 'Agent API v1'),
    ],
    security: [['AgentKey' => []]],
)]

#[OA\SecurityScheme(
    securityScheme: 'AgentKey',
    type: 'apiKey',
    name: 'X-Agent-Key',
    in: 'header',
    description: 'Agent API key. Prefix indicates role: rl_agent_* (partner), rl_admin_* (admin), rl_member_* (member).',
)]

// ═══════════════════════════════════════════════════════════════════════════
// COMMON SCHEMAS
// ═══════════════════════════════════════════════════════════════════════════

#[OA\Schema(
    schema: 'Pagination',
    description: 'Pagination metadata included in all list responses.',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 4),
        new OA\Property(property: 'per_page', type: 'integer', example: 25),
        new OA\Property(property: 'total', type: 'integer', example: 81),
    ],
)]

#[OA\Schema(
    schema: 'AgentError',
    description: 'Structured error response with machine-readable retry strategy.',
    required: ['error', 'code', 'message', 'retry_strategy'],
    properties: [
        new OA\Property(property: 'error', type: 'boolean', example: true),
        new OA\Property(property: 'code', type: 'string', example: 'VALIDATION_FAILED'),
        new OA\Property(property: 'message', type: 'string', example: 'The request data did not pass validation.'),
        new OA\Property(
            property: 'retry_strategy',
            type: 'string',
            enum: ['no_retry', 'backoff', 'fix_request', 'contact_support'],
            description: 'no_retry = permanent failure; backoff = retry with delay; fix_request = fix payload; contact_support = human action needed',
        ),
        new OA\Property(property: 'details', type: 'object', nullable: true),
    ],
)]

class AgentOpenApiSchemas {}
