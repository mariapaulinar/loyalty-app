<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Shopify API Exception
 *
 * Thrown when a Shopify API request fails. Contains the HTTP status code
 * and error message for debugging and user feedback.
 *
 * Common Status Codes:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - 400: Bad Request (invalid parameters)
 * - 401: Unauthorized (invalid or expired access token)
 * - 403: Forbidden (insufficient scopes)
 * - 404: Not Found (resource doesn't exist)
 * - 422: Unprocessable Entity (validation error)
 * - 429: Too Many Requests (rate limited)
 * - 500-504: Server errors (retry may help)
 *
 * @see App\Services\Integration\Shopify\ShopifyClient
 */

namespace App\Services\Integration\Shopify;

use Exception;

class ShopifyApiException extends Exception
{
    /**
     * The HTTP status code from the failed request.
     */
    protected int $httpStatusCode;

    /**
     * Create a new Shopify API exception.
     *
     * @param  string  $message  Error message
     * @param  int  $httpStatusCode  HTTP status code (default 0 for non-HTTP errors)
     * @param  \Throwable|null  $previous  Previous exception for chaining
     */
    public function __construct(string $message, int $httpStatusCode = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $httpStatusCode, $previous);
        $this->httpStatusCode = $httpStatusCode;
    }

    /**
     * Get the HTTP status code.
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * Check if this is a rate limit error.
     */
    public function isRateLimited(): bool
    {
        return $this->httpStatusCode === 429;
    }

    /**
     * Check if this is an authentication error.
     */
    public function isAuthenticationError(): bool
    {
        return in_array($this->httpStatusCode, [401, 403], true);
    }

    /**
     * Check if this is a not found error.
     */
    public function isNotFound(): bool
    {
        return $this->httpStatusCode === 404;
    }

    /**
     * Check if this is a server error (potentially retryable).
     */
    public function isServerError(): bool
    {
        return $this->httpStatusCode >= 500 && $this->httpStatusCode < 600;
    }

    /**
     * Check if this error might be resolved by retrying.
     */
    public function isRetryable(): bool
    {
        return $this->isRateLimited() || $this->isServerError();
    }
}
