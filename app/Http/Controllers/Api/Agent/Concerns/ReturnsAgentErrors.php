<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Standardized agent-friendly error/success responses.
 *
 * Every agent controller uses these methods — one pattern, enforced everywhere.
 * This is the single source of truth for the agent response envelope format.
 *
 * Success envelope: { "error": false, "data": {...}, "pagination": {...} }
 * Error envelope:   { "error": true, "code": "...", "message": "...", "retry_strategy": "...", "details": {...} }
 *
 * Retry strategies tell AI agents how to handle failures:
 * - no_retry: Request is fundamentally invalid (wrong key, wrong scope)
 * - backoff: Retry with exponential backoff (rate limit, server error)
 * - fix_request: Fix the request body and try again (validation error)
 * - contact_support: Requires human intervention (feature disabled, limit reached)
 *
 * @see RewardLoyalty-100-agent.md §16
 * @see RewardLoyalty-101-api-reference.md §1
 */

namespace App\Http\Controllers\Api\Agent\Concerns;

use Illuminate\Http\JsonResponse;

trait ReturnsAgentErrors
{
    /**
     * Return a success response with the standard envelope.
     *
     * @param  array  $data  Payload to merge into the response
     * @param  int  $status  HTTP status code (default 200)
     */
    protected function jsonSuccess(array $data = [], int $status = 200): JsonResponse
    {
        return response()->json(array_merge(['error' => false], $data), $status);
    }

    /**
     * Return a structured error response for agent consumers.
     *
     * @param  string  $code  Machine-readable error code (e.g., AUTH_INVALID_KEY)
     * @param  string  $message  Human-readable message
     * @param  int  $status  HTTP status code
     * @param  string  $retryStrategy  How the agent should handle this error
     * @param  array  $details  Optional additional context
     */
    protected function jsonError(
        string $code,
        string $message,
        int $status = 400,
        string $retryStrategy = 'no_retry',
        array $details = [],
    ): JsonResponse {
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

    /**
     * Return a validation error with the field-level errors.
     */
    protected function jsonValidationError(array $errors): JsonResponse
    {
        return $this->jsonError(
            code: 'VALIDATION_FAILED',
            message: 'The request data did not pass validation.',
            status: 422,
            retryStrategy: 'fix_request',
            details: ['errors' => $errors],
        );
    }

    /**
     * Return a 404 not found for a specific resource type.
     */
    protected function jsonNotFound(string $resource): JsonResponse
    {
        return $this->jsonError(
            code: 'NOT_FOUND',
            message: "{$resource} not found.",
            status: 404,
        );
    }

    /**
     * Return a scope enforcement error.
     */
    protected function jsonScopeError(string $requiredScope): JsonResponse
    {
        return $this->jsonError(
            code: 'AUTH_INSUFFICIENT_SCOPE',
            message: "This action requires the '{$requiredScope}' scope.",
            status: 403,
            details: ['required_scope' => $requiredScope],
        );
    }

    /**
     * Return an internal server error with the standard envelope.
     * Used for unexpected exceptions in try/catch blocks.
     */
    protected function jsonInternalError(string $message = 'An unexpected error occurred.'): JsonResponse
    {
        return $this->jsonError(
            code: 'INTERNAL_ERROR',
            message: $message,
            status: 500,
            retryStrategy: 'backoff',
        );
    }
}
