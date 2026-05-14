<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Per-agent-key rate limiting middleware.
 *
 * Unlike standard IP-based throttling, this uses the agent key ID
 * as the rate limiter key. Each key has its own configurable rate_limit
 * (requests per minute, default: 60). This means:
 * - Two keys owned by the same partner have separate limits
 * - A partner with a 120 RPM key can burst higher than a 30 RPM key
 * - Rate limits are configurable per-key via the dashboard
 *
 * Response headers on every request:
 *   X-RateLimit-Limit:     max requests per window
 *   X-RateLimit-Remaining: requests left in current window
 *   X-RateLimit-Reset:     Unix timestamp when the window resets
 *
 * On 429 (too many requests), additionally:
 *   Retry-After:           seconds until the window resets
 *
 * Placement in the middleware stack: AFTER agent.auth (needs the key),
 * BEFORE controller dispatch. Runs synchronously — must block the
 * request before business logic executes.
 *
 * @see App\Models\AgentKey::$rate_limit
 * @see RewardLoyalty-100c-phase3-polish.md §1
 */

namespace App\Http\Middleware;

use App\Models\AgentKey;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentRateLimiter
{
    public function __construct(
        protected RateLimiter $limiter,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var AgentKey|null $agentKey */
        $agentKey = $request->attributes->get('agent_key');

        // No agent key = request didn't pass auth. Let the auth
        // middleware handle the error; we just pass through.
        if (! $agentKey) {
            return $next($request);
        }

        $rateLimiterKey = 'agent_rate:' . $agentKey->id;
        $maxAttempts = $agentKey->rate_limit ?? 60;
        $decaySeconds = 60; // 1-minute fixed window

        if ($this->limiter->tooManyAttempts($rateLimiterKey, $maxAttempts)) {
            return $this->rateLimitedResponse($rateLimiterKey, $maxAttempts);
        }

        $this->limiter->hit($rateLimiterKey, $decaySeconds);

        $response = $next($request);

        return $this->addRateLimitHeaders($response, $rateLimiterKey, $maxAttempts);
    }

    /**
     * Build the 429 response with agent error envelope + retry headers.
     */
    private function rateLimitedResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'error' => true,
            'code' => 'RATE_LIMITED',
            'message' => 'Too many requests. Please slow down.',
            'retry_strategy' => 'backoff',
            'details' => [
                'limit' => $maxAttempts,
                'window_seconds' => 60,
                'retry_after_seconds' => $retryAfter,
            ],
        ], 429)->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->getTimestamp(),
            'Retry-After' => $retryAfter,
        ]);
    }

    /**
     * Add rate limit headers to a successful response.
     *
     * These headers appear on EVERY response so clients can
     * proactively pace their requests without hitting 429.
     */
    private function addRateLimitHeaders(Response $response, string $key, int $maxAttempts): Response
    {
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) $this->limiter->remaining($key, $maxAttempts));
        $response->headers->set('X-RateLimit-Reset', (string) now()->addMinute()->getTimestamp());

        return $response;
    }
}
