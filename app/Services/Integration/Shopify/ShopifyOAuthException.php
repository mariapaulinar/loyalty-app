<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Shopify OAuth Exception
 *
 * Thrown when OAuth authorization flow fails at any stage:
 * - Invalid/expired state parameter
 * - Nonce validation failure (replay attack prevention)
 * - Token exchange failure
 * - Connection verification failure
 *
 * Error Categories:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - State Errors: Invalid state, expired state, shop mismatch
 * - Nonce Errors: Missing nonce, expired nonce, already used nonce
 * - Token Errors: Code exchange failure, invalid credentials
 * - Connection Errors: API verification failure, network issues
 *
 * Handling Strategy:
 * ─────────────────────────────────────────────────────────────────────────────────
 * Controllers should catch this exception and redirect the user back to the
 * partner dashboard with an appropriate error message. Do NOT expose internal
 * details to users — log them instead.
 *
 * @see App\Services\Integration\Shopify\OAuthService
 */

namespace App\Services\Integration\Shopify;

use Exception;

class ShopifyOAuthException extends Exception
{
    /**
     * User-friendly error messages for common scenarios.
     *
     * @var array<string, string>
     */
    private const USER_MESSAGES = [
        'Missing required OAuth parameters' => 'The authorization request was incomplete. Please try again.',
        'Invalid HMAC signature' => 'The authorization request could not be verified. Please try again.',
        'Invalid state parameter' => 'The authorization session was invalid. Please try again.',
        'Invalid or expired nonce' => 'The authorization session has expired. Please try again.',
        'Shop mismatch in state' => 'The shop domain did not match the original request. Please try again.',
        'OAuth state has expired' => 'The authorization session has expired. Please try again.',
        'Club not found' => 'The loyalty program could not be found. Please contact support.',
    ];

    /**
     * Create a new OAuth exception.
     *
     * @param  string  $message  Internal error message (logged)
     * @param  int  $code  Error code
     * @param  \Throwable|null  $previous  Previous exception for chaining
     */
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get a user-friendly error message.
     *
     * Returns a sanitized message safe to display to users,
     * without exposing internal system details.
     *
     * @return string User-friendly message
     */
    public function getUserMessage(): string
    {
        // Check for known error patterns
        foreach (self::USER_MESSAGES as $pattern => $userMessage) {
            if (str_contains($this->getMessage(), $pattern)) {
                return $userMessage;
            }
        }

        // Default user-friendly message
        return 'Unable to connect to Shopify. Please try again or contact support if the problem persists.';
    }

    /**
     * Check if this error is due to an expired session.
     */
    public function isSessionExpired(): bool
    {
        return str_contains($this->getMessage(), 'expired') ||
               str_contains($this->getMessage(), 'nonce');
    }

    /**
     * Check if this error is due to invalid credentials.
     */
    public function isCredentialError(): bool
    {
        return str_contains($this->getMessage(), 'token') ||
               str_contains($this->getMessage(), 'code');
    }

    /**
     * Check if this error might be resolved by retrying.
     */
    public function isRetryable(): bool
    {
        return $this->isSessionExpired() ||
               str_contains($this->getMessage(), 'network') ||
               str_contains($this->getMessage(), 'timeout');
    }
}
