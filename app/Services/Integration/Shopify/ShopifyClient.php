<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Shopify API Client
 *
 * Low-level HTTP client for communicating with Shopify's Admin API.
 * Handles authentication, retries, rate limiting, and error mapping.
 *
 * API Strategy:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - GraphQL: Used for automatic discounts (modern, flexible)
 * - REST: Used for price rules, discount codes, customers (simpler, well-documented)
 *
 * Retry Strategy:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - Retries: 3 attempts for 429 (rate limit) and 5xx (server errors)
 * - Backoff: 200ms → 400ms → 800ms (exponential)
 * - Non-retryable: 4xx client errors (except 429)
 *
 * Logging:
 * ─────────────────────────────────────────────────────────────────────────────────
 * All requests are logged with:
 * - integration_id: UUID for tracing
 * - store_identifier: mystore.myshopify.com for context
 * - request/response details for debugging
 *
 * @see App\Models\ClubIntegration
 * @see https://shopify.dev/docs/api/admin-rest
 * @see https://shopify.dev/docs/api/admin-graphql
 */

namespace App\Services\Integration\Shopify;

use App\Models\ClubIntegration;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyClient
{
    /**
     * Retry delays in milliseconds (exponential backoff).
     */
    private const RETRY_DELAYS = [200, 400, 800];

    /**
     * HTTP status codes that trigger retry.
     */
    private const RETRYABLE_STATUSES = [429, 500, 502, 503, 504];

    /**
     * The integration this client is operating on behalf of.
     */
    private ClubIntegration $integration;

    /**
     * Shopify API version from config.
     */
    private string $apiVersion;

    /**
     * Base URL for REST API calls.
     */
    private string $restBaseUrl;

    /**
     * Base URL for GraphQL API calls.
     */
    private string $graphqlUrl;

    /**
     * Create a new Shopify client instance.
     *
     * @param  ClubIntegration  $integration  The integration with valid access_token and store_identifier
     *
     * @throws \InvalidArgumentException If integration is missing required credentials
     */
    public function __construct(ClubIntegration $integration)
    {
        if (empty($integration->access_token)) {
            throw new \InvalidArgumentException('Integration is missing access_token');
        }

        if (empty($integration->store_identifier)) {
            throw new \InvalidArgumentException('Integration is missing store_identifier');
        }

        $this->integration = $integration;
        $this->apiVersion = config('integrations.shopify.api_version', '2025-10');

        // Build base URLs
        // store_identifier format: mystore.myshopify.com
        $store = $this->integration->store_identifier;
        $this->restBaseUrl = "https://{$store}/admin/api/{$this->apiVersion}";
        $this->graphqlUrl = "https://{$store}/admin/api/{$this->apiVersion}/graphql.json";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CONNECTION & SHOP
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Verify the connection is working by fetching shop info.
     *
     * @return array{connected: bool, shop_name?: string, error?: string}
     */
    public function verifyConnection(): array
    {
        try {
            $shop = $this->getShop();

            return [
                'connected' => true,
                'shop_name' => $shop['name'] ?? $shop['shop']['name'] ?? 'Unknown',
            ];
        } catch (\Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get shop information.
     *
     * @return array Shop data from Shopify
     *
     * @throws ShopifyApiException On API error
     */
    public function getShop(): array
    {
        $response = $this->restGet('/shop.json');

        return $response['shop'] ?? $response;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AUTOMATIC DISCOUNTS (GraphQL)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create an automatic discount via GraphQL.
     *
     * Automatic discounts apply at checkout without a code. They require
     * Shopify Plus or the Discount Functions API.
     *
     * @param  string  $title  Discount title (internal name)
     * @param  string  $type  Discount type: 'percentage' or 'fixed_amount'
     * @param  float  $value  Discount value (percentage as decimal, or amount in shop currency)
     * @param  \DateTimeInterface|null  $startsAt  When discount becomes active
     * @param  \DateTimeInterface|null  $endsAt  When discount expires (null = no expiry)
     * @return array{kind: string, id: string, title: string, status: string}
     *
     * @throws ShopifyApiException On API error
     */
    public function createAutomaticDiscount(
        string $title,
        string $type,
        float $value,
        ?\DateTimeInterface $startsAt = null,
        ?\DateTimeInterface $endsAt = null
    ): array {
        $startsAt ??= now();

        // Build the discount value input based on type
        $discountValue = $type === 'percentage'
            ? ['percentage' => $value / 100] // Shopify expects 0.10 for 10%
            : ['discountAmount' => ['amount' => $value, 'appliesOnEachItem' => false]];

        $mutation = <<<'GRAPHQL'
            mutation discountAutomaticBasicCreate($automaticBasicDiscount: DiscountAutomaticBasicInput!) {
                discountAutomaticBasicCreate(automaticBasicDiscount: $automaticBasicDiscount) {
                    automaticDiscountNode {
                        id
                        automaticDiscount {
                            ... on DiscountAutomaticBasic {
                                title
                                status
                                startsAt
                                endsAt
                            }
                        }
                    }
                    userErrors {
                        field
                        code
                        message
                    }
                }
            }
        GRAPHQL;

        $variables = [
            'automaticBasicDiscount' => [
                'title' => $title,
                'startsAt' => $startsAt->format(\DateTimeInterface::ATOM),
                'endsAt' => $endsAt?->format(\DateTimeInterface::ATOM),
                'customerGets' => [
                    'value' => $discountValue,
                    'items' => ['all' => true],
                ],
                'minimumRequirement' => ['subtotal' => ['greaterThanOrEqualToSubtotal' => '0']],
            ],
        ];

        $response = $this->graphql($mutation, $variables);
        $result = $response['data']['discountAutomaticBasicCreate'] ?? [];

        // Check for user errors
        if (! empty($result['userErrors'])) {
            $errors = collect($result['userErrors'])->pluck('message')->implode(', ');
            throw new ShopifyApiException("Failed to create automatic discount: {$errors}");
        }

        $discount = $result['automaticDiscountNode'] ?? [];

        return [
            'kind' => 'automatic',
            'id' => $discount['id'] ?? '',
            'title' => $discount['automaticDiscount']['title'] ?? $title,
            'status' => $discount['automaticDiscount']['status'] ?? 'ACTIVE',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DISCOUNT CODES (REST API)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create a discount code via REST API (Price Rules + Discount Codes).
     *
     * Discount codes require manual entry at checkout. Works on all Shopify plans.
     *
     * @param  string  $code  The discount code customers will enter (e.g., 'REWARD-ABC123')
     * @param  string  $type  Discount type: 'percentage' or 'fixed_amount'
     * @param  float  $value  Discount value (negative for discounts, e.g., -10 for $10 off)
     * @param  int  $usageLimit  How many times the code can be used (0 = unlimited)
     * @param  \DateTimeInterface|null  $startsAt  When code becomes active
     * @param  \DateTimeInterface|null  $endsAt  When code expires
     * @return array{kind: string, price_rule_id: int, discount_code_id: int, code: string}
     *
     * @throws ShopifyApiException On API error
     */
    public function createDiscountCode(
        string $code,
        string $type,
        float $value,
        int $usageLimit = 1,
        ?\DateTimeInterface $startsAt = null,
        ?\DateTimeInterface $endsAt = null
    ): array {
        $startsAt ??= now();

        // Step 1: Create Price Rule
        $priceRuleData = [
            'price_rule' => [
                'title' => $code,
                'target_type' => 'line_item',
                'target_selection' => 'all',
                'allocation_method' => 'across',
                'value_type' => $type,
                'value' => $type === 'percentage' ? (string) -abs($value) : (string) -abs($value),
                'customer_selection' => 'all',
                'once_per_customer' => true,
                'usage_limit' => $usageLimit > 0 ? $usageLimit : null,
                'starts_at' => $startsAt->format(\DateTimeInterface::ATOM),
                'ends_at' => $endsAt?->format(\DateTimeInterface::ATOM),
            ],
        ];

        $priceRuleResponse = $this->restPost('/price_rules.json', $priceRuleData);
        $priceRule = $priceRuleResponse['price_rule'] ?? [];

        if (empty($priceRule['id'])) {
            throw new ShopifyApiException('Failed to create price rule: no ID returned');
        }

        $priceRuleId = $priceRule['id'];

        // Step 2: Create Discount Code
        $discountCodeData = [
            'discount_code' => [
                'code' => $code,
            ],
        ];

        $discountCodeResponse = $this->restPost(
            "/price_rules/{$priceRuleId}/discount_codes.json",
            $discountCodeData
        );
        $discountCode = $discountCodeResponse['discount_code'] ?? [];

        if (empty($discountCode['id'])) {
            // Rollback: delete the price rule
            $this->restDelete("/price_rules/{$priceRuleId}.json");
            throw new ShopifyApiException('Failed to create discount code: no ID returned');
        }

        return [
            'kind' => 'code',
            'price_rule_id' => $priceRuleId,
            'discount_code_id' => $discountCode['id'],
            'code' => $discountCode['code'] ?? $code,
        ];
    }

    /**
     * Delete a discount (handles both automatic and code-based).
     *
     * @param  array{kind: string, id?: string, price_rule_id?: int}  $ref  Discount reference from create methods
     * @return bool True if deleted (or already gone), false on error
     */
    public function deleteDiscount(array $ref): bool
    {
        $kind = $ref['kind'] ?? '';

        try {
            if ($kind === 'automatic') {
                // GraphQL mutation to delete automatic discount
                return $this->deleteAutomaticDiscount($ref['id'] ?? '');
            }

            if ($kind === 'code') {
                // REST API to delete price rule (cascades to discount codes)
                return $this->deletePriceRule($ref['price_rule_id'] ?? 0);
            }

            $this->log('warning', 'Unknown discount kind for deletion', ['ref' => $ref]);

            return false;
        } catch (ShopifyApiException $e) {
            // 404 means already deleted — that's fine
            if (str_contains($e->getMessage(), '404') || str_contains($e->getMessage(), 'Not Found')) {
                return true;
            }

            $this->log('error', 'Failed to delete discount', [
                'ref' => $ref,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete an automatic discount via GraphQL.
     */
    private function deleteAutomaticDiscount(string $gid): bool
    {
        if (empty($gid)) {
            return false;
        }

        $mutation = <<<'GRAPHQL'
            mutation discountAutomaticDelete($id: ID!) {
                discountAutomaticDelete(id: $id) {
                    deletedAutomaticDiscountId
                    userErrors {
                        field
                        code
                        message
                    }
                }
            }
        GRAPHQL;

        $response = $this->graphql($mutation, ['id' => $gid]);
        $result = $response['data']['discountAutomaticDelete'] ?? [];

        // User errors that aren't "not found" should throw
        $userErrors = $result['userErrors'] ?? [];
        foreach ($userErrors as $error) {
            if (($error['code'] ?? '') !== 'NOT_FOUND') {
                throw new ShopifyApiException("Failed to delete discount: {$error['message']}");
            }
        }

        return true;
    }

    /**
     * Delete a price rule via REST API.
     */
    private function deletePriceRule(int $priceRuleId): bool
    {
        if ($priceRuleId <= 0) {
            return false;
        }

        $this->restDelete("/price_rules/{$priceRuleId}.json");

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CUSTOMERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get a customer by Shopify ID.
     *
     * @param  int|string  $customerId  Shopify customer ID
     * @return array|null Customer data or null if not found
     *
     * @throws ShopifyApiException On API error (except 404)
     */
    public function getCustomer(int|string $customerId): ?array
    {
        try {
            $response = $this->restGet("/customers/{$customerId}.json");

            return $response['customer'] ?? null;
        } catch (ShopifyApiException $e) {
            if (str_contains($e->getMessage(), '404')) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Find a customer by email address.
     *
     * @param  string  $email  Customer email
     * @return array|null Customer data or null if not found
     *
     * @throws ShopifyApiException On API error
     */
    public function getCustomerByEmail(string $email): ?array
    {
        $response = $this->restGet('/customers/search.json', [
            'query' => "email:{$email}",
            'limit' => 1,
        ]);

        $customers = $response['customers'] ?? [];

        return $customers[0] ?? null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HTTP PRIMITIVES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Make a REST GET request.
     *
     * @param  string  $endpoint  API endpoint (e.g., '/shop.json')
     * @param  array  $query  Query parameters
     * @return array Response data
     *
     * @throws ShopifyApiException On API error
     */
    private function restGet(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $this->restBaseUrl.$endpoint, [
            'query' => $query,
        ]);
    }

    /**
     * Make a REST POST request.
     *
     * @param  string  $endpoint  API endpoint
     * @param  array  $data  Request body
     * @return array Response data
     *
     * @throws ShopifyApiException On API error
     */
    private function restPost(string $endpoint, array $data): array
    {
        return $this->request('POST', $this->restBaseUrl.$endpoint, [
            'json' => $data,
        ]);
    }

    /**
     * Make a REST DELETE request.
     *
     * @param  string  $endpoint  API endpoint
     * @return array Response data (usually empty)
     *
     * @throws ShopifyApiException On API error
     */
    private function restDelete(string $endpoint): array
    {
        return $this->request('DELETE', $this->restBaseUrl.$endpoint);
    }

    /**
     * Make a GraphQL request.
     *
     * @param  string  $query  GraphQL query or mutation
     * @param  array  $variables  Query variables
     * @return array Response data
     *
     * @throws ShopifyApiException On API error
     */
    private function graphql(string $query, array $variables = []): array
    {
        return $this->request('POST', $this->graphqlUrl, [
            'json' => [
                'query' => $query,
                'variables' => $variables,
            ],
        ]);
    }

    /**
     * Make an HTTP request with retry logic.
     *
     * @param  string  $method  HTTP method
     * @param  string  $url  Full URL
     * @param  array  $options  Request options (query, json, etc.)
     * @return array Response data
     *
     * @throws ShopifyApiException On API error after retries exhausted
     */
    private function request(string $method, string $url, array $options = []): array
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < count(self::RETRY_DELAYS) + 1) {
            try {
                $response = $this->buildRequest()
                    ->send($method, $url, $options);

                // Log successful request
                $this->logRequest($method, $url, $options, $response);

                // Check for HTTP errors
                if ($response->failed()) {
                    $this->handleErrorResponse($response, $method, $url);
                }

                return $response->json() ?? [];

            } catch (RequestException $e) {
                $lastException = $e;
                $status = $e->response?->status() ?? 0;

                // Only retry on specific status codes
                if (! in_array($status, self::RETRYABLE_STATUSES, true)) {
                    throw $this->wrapException($e, $method, $url);
                }

                // Wait before retry
                if ($attempt < count(self::RETRY_DELAYS)) {
                    usleep(self::RETRY_DELAYS[$attempt] * 1000);
                }

                $attempt++;

                $this->log('warning', 'Retrying request', [
                    'attempt' => $attempt,
                    'method' => $method,
                    'url' => $url,
                    'status' => $status,
                ]);

            } catch (\Exception $e) {
                throw $this->wrapException($e, $method, $url);
            }
        }

        // All retries exhausted
        throw $this->wrapException(
            $lastException ?? new \RuntimeException('Request failed after retries'),
            $method,
            $url
        );
    }

    /**
     * Build a configured HTTP request.
     */
    private function buildRequest(): PendingRequest
    {
        return Http::withHeaders([
            'X-Shopify-Access-Token' => $this->integration->access_token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(30);
    }

    /**
     * Handle error response and throw appropriate exception.
     */
    private function handleErrorResponse(Response $response, string $method, string $url): never
    {
        $status = $response->status();
        $body = $response->json() ?? [];

        // Extract error message from various Shopify error formats
        $message = $body['errors'] ?? $body['error'] ?? $body['message'] ?? 'Unknown error';
        if (is_array($message)) {
            $message = json_encode($message);
        }

        $this->log('error', 'Shopify API error', [
            'method' => $method,
            'url' => $url,
            'status' => $status,
            'error' => $message,
        ]);

        throw new ShopifyApiException(
            "Shopify API error ({$status}): {$message}",
            $status
        );
    }

    /**
     * Wrap an exception with context.
     */
    private function wrapException(\Throwable $e, string $method, string $url): ShopifyApiException
    {
        $this->log('error', 'Shopify request failed', [
            'method' => $method,
            'url' => $url,
            'error' => $e->getMessage(),
        ]);

        if ($e instanceof ShopifyApiException) {
            return $e;
        }

        return new ShopifyApiException(
            "Shopify request failed: {$e->getMessage()}",
            $e->getCode(),
            $e
        );
    }

    /**
     * Log a request and response.
     */
    private function logRequest(string $method, string $url, array $options, Response $response): void
    {
        $this->log('debug', 'Shopify API request', [
            'method' => $method,
            'url' => $url,
            'status' => $response->status(),
            'duration_ms' => $response->transferStats?->getTransferTime() * 1000,
        ]);
    }

    /**
     * Log a message with integration context.
     */
    private function log(string $level, string $message, array $context = []): void
    {
        Log::log($level, "[Shopify] {$message}", array_merge($context, [
            'integration_id' => $this->integration->id,
            'store_identifier' => $this->integration->store_identifier,
        ]));
    }
}
