<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Shopify OAuth Service
 *
 * Orchestrates the complete Shopify OAuth 2.0 authorization flow, from generating
 * the install URL to exchanging codes for tokens and registering webhooks.
 *
 * OAuth Flow Overview:
 * ─────────────────────────────────────────────────────────────────────────────────
 *
 *   Partner Dashboard              This Service                    Shopify
 *         │                              │                            │
 *    1. Click "Connect" ──────────────►  │                            │
 *         │                     generateInstallUrl()                  │
 *         │                              │                            │
 *    2.   │ ◄───── Redirect to Shopify OAuth ────────────────────────►│
 *         │                              │                            │
 *    3.   │        User authorizes app   │ ◄──────────────────────────│
 *         │                              │                            │
 *    4.   │ ◄───── Callback with code ───│                            │
 *         │                     handleCallback()                      │
 *         │                              │                            │
 *    5.   │                    Exchange code for token ──────────────►│
 *         │                              │ ◄──────────────────────────│
 *    6.   │                    Create/update integration              │
 *         │                              │                            │
 *    7.   │                    Verify connection ─────────────────────►│
 *         │                              │ ◄──────────────────────────│
 *    8.   │                    Register webhooks ────────────────────►│
 *         │                              │ ◄──────────────────────────│
 *    9.   │ ◄───── Return to dashboard ──│                            │
 *
 * Security Measures:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - State Parameter: Contains club_id, shop, timestamp, and nonce
 * - Nonce: Single-use, cached for 10 minutes, prevents replay attacks
 * - HMAC Validation: Verifies callback authenticity using Shopify's signature
 * - Encrypted Tokens: Access tokens encrypted at rest via Laravel's encryption
 *
 * Callback Route:
 * ─────────────────────────────────────────────────────────────────────────────────
 * The callback MUST be a public route (no Sanctum auth) because Shopify redirects
 * the user's browser directly. The state parameter cryptographically ties the
 * callback to the original request.
 *
 * @see App\Models\ClubIntegration
 * @see App\Services\Integration\Shopify\ShopifyClient
 * @see https://shopify.dev/docs/apps/auth/oauth
 */

namespace App\Services\Integration\Shopify;

use App\Enums\IntegrationPlatform;
use App\Enums\IntegrationStatus;
use App\Models\Club;
use App\Models\ClubIntegration;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OAuthService
{
    /**
     * Webhooks to register after successful connection.
     *
     * Each topic maps to our WebhookProcessor handlers.
     * Format: Shopify topic => handler method
     */
    private const WEBHOOK_TOPICS = [
        'orders/paid',
        'refunds/create',
        'customers/create',
        'customers/update',
        'app/uninstalled',
    ];

    /**
     * Nonce cache TTL in seconds (10 minutes).
     */
    private const NONCE_TTL = 600;

    /**
     * Nonce cache key prefix.
     */
    private const NONCE_CACHE_PREFIX = 'shopify_oauth_nonce:';

    public function __construct(
        private readonly ActivityLogService $activityLog
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // INSTALLATION URL GENERATION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate the Shopify OAuth authorization URL.
     *
     * This URL redirects the partner to Shopify to authorize the app.
     * The state parameter is cryptographically signed and contains
     * all information needed to complete the flow.
     *
     * @param  Club  $club  The club initiating the connection
     * @param  string  $shop  Shopify store domain (e.g., 'mystore.myshopify.com')
     * @return array{url: string, nonce: string, state: string}
     *
     * @throws \InvalidArgumentException If shop domain is invalid
     */
    public function generateInstallUrl(Club $club, string $shop): array
    {
        // Validate and normalize shop domain
        $shop = $this->normalizeShopDomain($shop);

        // Generate cryptographically secure nonce
        $nonce = Str::random(32);

        // Build state with all context needed for callback
        $state = $this->buildState([
            'club_id' => $club->id,
            'shop' => $shop,
            'ts' => time(),
            'nonce' => $nonce,
        ]);

        // Store nonce in cache (single-use, expires in 10 minutes)
        $this->storeNonce($nonce, $club->id);

        // Build authorization URL
        $clientId = config('integrations.shopify.client_id');
        $scopes = config('integrations.shopify.scopes');
        $redirectUri = $this->getCallbackUrl();

        $params = http_build_query([
            'client_id' => $clientId,
            'scope' => $scopes,
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        $url = "https://{$shop}/admin/oauth/authorize?{$params}";

        $this->log('info', 'Generated install URL', [
            'club_id' => $club->id,
            'shop' => $shop,
        ]);

        return [
            'url' => $url,
            'nonce' => $nonce,
            'state' => $state,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CALLBACK HANDLING
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Handle the OAuth callback from Shopify.
     *
     * This method is called by the public callback route after Shopify
     * redirects the user back with an authorization code.
     *
     * Process:
     * 1. Validate HMAC signature
     * 2. Parse and validate state (including nonce)
     * 3. Exchange code for access token
     * 4. Create or update ClubIntegration
     * 5. Verify connection works
     * 6. Register webhooks
     * 7. Audit the connection
     *
     * @param  array  $params  Query parameters from Shopify callback
     * @return ClubIntegration The created or updated integration
     *
     * @throws ShopifyOAuthException On validation or API failure
     */
    public function handleCallback(array $params): ClubIntegration
    {
        // Extract required parameters
        $code = $params['code'] ?? null;
        $shop = $params['shop'] ?? null;
        $state = $params['state'] ?? null;
        $hmac = $params['hmac'] ?? null;

        // Validate required parameters
        if (empty($code) || empty($shop) || empty($state)) {
            throw new ShopifyOAuthException('Missing required OAuth parameters');
        }

        // Validate HMAC signature
        if (! $this->validateHmac($params)) {
            throw new ShopifyOAuthException('Invalid HMAC signature');
        }

        // Parse and validate state
        $stateData = $this->parseState($state);
        if (! $stateData) {
            throw new ShopifyOAuthException('Invalid state parameter');
        }

        // Validate nonce (single-use)
        if (! $this->validateAndConsumeNonce($stateData['nonce'], $stateData['club_id'])) {
            throw new ShopifyOAuthException('Invalid or expired nonce');
        }

        // Validate shop matches state
        $normalizedShop = $this->normalizeShopDomain($shop);
        if ($normalizedShop !== $stateData['shop']) {
            throw new ShopifyOAuthException('Shop mismatch in state');
        }

        // Validate timestamp (prevent very old callbacks)
        $maxAge = 3600; // 1 hour
        if (time() - $stateData['ts'] > $maxAge) {
            throw new ShopifyOAuthException('OAuth state has expired');
        }

        // Get the club
        $club = Club::find($stateData['club_id']);
        if (! $club) {
            throw new ShopifyOAuthException('Club not found');
        }

        // Exchange authorization code for access token
        $accessToken = $this->exchangeCodeForToken($shop, $code);

        // Create or update the integration
        $integration = $this->createOrUpdateIntegration($club, $shop, $accessToken);

        try {
            // Verify the connection works
            $client = new ShopifyClient($integration);
            $connectionResult = $client->verifyConnection();

            if (! $connectionResult['connected']) {
                $integration->markError($connectionResult['error'] ?? 'Connection verification failed');
                throw new ShopifyOAuthException(
                    'Failed to verify Shopify connection: '.($connectionResult['error'] ?? 'Unknown error')
                );
            }

            // Mark as active
            $integration->markActive();

            // Update last sync
            $integration->last_sync_at = now();
            $integration->save();

            // Register webhooks
            $this->registerWebhooks($integration);

            // Audit successful connection
            $this->activityLog->log(
                "Shopify store connected: {$shop}",
                $integration,
                'integration.shopify.connected',
                [
                    'club_id' => $club->id,
                    'shop' => $shop,
                    'shop_name' => $connectionResult['shop_name'] ?? null,
                ]
            );

            $this->log('info', 'Shopify OAuth completed successfully', [
                'integration_id' => $integration->id,
                'shop' => $shop,
            ]);

            return $integration;

        } catch (ShopifyOAuthException $e) {
            throw $e;
        } catch (\Exception $e) {
            $integration->markError($e->getMessage());

            throw new ShopifyOAuthException(
                'Failed to complete Shopify connection: '.$e->getMessage(),
                previous: $e
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TOKEN EXCHANGE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Exchange authorization code for access token.
     *
     * @param  string  $shop  Shopify store domain
     * @param  string  $code  Authorization code from callback
     * @return string Access token
     *
     * @throws ShopifyOAuthException On API failure
     */
    private function exchangeCodeForToken(string $shop, string $code): string
    {
        $clientId = config('integrations.shopify.client_id');
        $clientSecret = config('integrations.shopify.client_secret');

        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
        ]);

        if (! $response->successful()) {
            $error = $response->json('error_description') ?? $response->json('error') ?? 'Unknown error';

            $this->log('error', 'Token exchange failed', [
                'shop' => $shop,
                'status' => $response->status(),
                'error' => $error,
            ]);

            throw new ShopifyOAuthException("Failed to exchange code for token: {$error}");
        }

        $accessToken = $response->json('access_token');

        if (empty($accessToken)) {
            throw new ShopifyOAuthException('No access token in response');
        }

        return $accessToken;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INTEGRATION MANAGEMENT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create or update a ClubIntegration record.
     *
     * If an integration already exists for this club/shop combination,
     * it will be updated with the new access token.
     *
     * @param  Club  $club  The club being connected
     * @param  string  $shop  Shopify store domain
     * @param  string  $accessToken  OAuth access token
     * @return ClubIntegration The created or updated integration
     */
    private function createOrUpdateIntegration(Club $club, string $shop, string $accessToken): ClubIntegration
    {
        $integration = ClubIntegration::withTrashed()
            ->where('club_id', $club->id)
            ->where('platform', IntegrationPlatform::SHOPIFY->value)
            ->where('store_identifier', $shop)
            ->first();

        if ($integration) {
            // Restore if soft-deleted
            if ($integration->trashed()) {
                $integration->restore();
            }

            // Update credentials
            $integration->access_token = $accessToken;
            $integration->status = IntegrationStatus::PENDING;
            $integration->last_error = null;
            $integration->last_error_at = null;
            $integration->save();

            $this->log('info', 'Updated existing integration', [
                'integration_id' => $integration->id,
                'shop' => $shop,
            ]);

            return $integration;
        }

        // Create new integration
        $integration = ClubIntegration::create([
            'club_id' => $club->id,
            'platform' => IntegrationPlatform::SHOPIFY->value,
            'status' => IntegrationStatus::PENDING->value,
            'store_identifier' => $shop,
            'access_token' => $accessToken,
        ]);

        $this->log('info', 'Created new integration', [
            'integration_id' => $integration->id,
            'shop' => $shop,
        ]);

        return $integration;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WEBHOOK REGISTRATION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Register webhooks for all supported topics.
     *
     * Webhooks are registered with our fixed endpoint format:
     * {app_url}/api/webhooks/shopify/{integration_id}/{topic}
     *
     * @param  ClubIntegration  $integration  The integration to register webhooks for
     *
     * @throws ShopifyOAuthException On registration failure
     */
    private function registerWebhooks(ClubIntegration $integration): void
    {
        $appUrl = config('integrations.shopify.app_url');
        $apiVersion = config('integrations.shopify.api_version', '2025-10');

        if (empty($appUrl)) {
            $this->log('warning', 'SHOPIFY_APP_URL not configured, skipping webhook registration', [
                'integration_id' => $integration->id,
            ]);

            return;
        }

        $shop = $integration->store_identifier;
        $accessToken = $integration->access_token;

        foreach (self::WEBHOOK_TOPICS as $topic) {
            $this->registerWebhook($integration, $topic, $appUrl, $apiVersion, $shop, $accessToken);
        }

        $this->log('info', 'Registered all webhooks', [
            'integration_id' => $integration->id,
            'topics' => self::WEBHOOK_TOPICS,
        ]);
    }

    /**
     * Register a single webhook topic.
     *
     * @param  ClubIntegration  $integration  The integration
     * @param  string  $topic  Webhook topic (e.g., 'orders/paid')
     * @param  string  $appUrl  Base application URL
     * @param  string  $apiVersion  Shopify API version
     * @param  string  $shop  Shop domain
     * @param  string  $accessToken  OAuth access token
     */
    private function registerWebhook(
        ClubIntegration $integration,
        string $topic,
        string $appUrl,
        string $apiVersion,
        string $shop,
        string $accessToken
    ): void {
        // Build webhook endpoint URL
        // Format: {app_url}/api/webhooks/shopify/{integration_id}/{topic}
        $topicSlug = str_replace('/', '-', $topic);
        $address = "{$appUrl}/api/webhooks/shopify/{$integration->id}/{$topicSlug}";

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
            'Content-Type' => 'application/json',
        ])->post("https://{$shop}/admin/api/{$apiVersion}/webhooks.json", [
            'webhook' => [
                'topic' => $topic,
                'address' => $address,
                'format' => 'json',
            ],
        ]);

        if ($response->successful()) {
            $this->log('debug', "Registered webhook: {$topic}", [
                'integration_id' => $integration->id,
                'address' => $address,
            ]);

            return;
        }

        // Handle already registered (Shopify returns 422 for duplicate)
        if ($response->status() === 422) {
            $errors = $response->json('errors') ?? [];
            $isAlreadyRegistered = collect($errors)->flatten()->contains(
                fn ($error) => str_contains(strtolower((string) $error), 'already')
            );

            if ($isAlreadyRegistered) {
                $this->log('debug', "Webhook already registered: {$topic}", [
                    'integration_id' => $integration->id,
                ]);

                return;
            }
        }

        // Log non-critical failure (don't throw, connection still valid)
        $this->log('warning', "Failed to register webhook: {$topic}", [
            'integration_id' => $integration->id,
            'status' => $response->status(),
            'errors' => $response->json('errors'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STATE MANAGEMENT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build encrypted state parameter.
     *
     * The state contains all context needed to process the callback,
     * encrypted to prevent tampering.
     *
     * @param  array{club_id: string, shop: string, ts: int, nonce: string}  $data
     * @return string Encrypted state string
     */
    private function buildState(array $data): string
    {
        return Crypt::encryptString(json_encode($data));
    }

    /**
     * Parse and decrypt state parameter.
     *
     * @param  string  $state  Encrypted state string
     * @return array{club_id: string, shop: string, ts: int, nonce: string}|null Parsed data or null if invalid
     */
    private function parseState(string $state): ?array
    {
        try {
            $decrypted = Crypt::decryptString($state);
            $data = json_decode($decrypted, true);

            // Validate required fields
            if (
                empty($data['club_id']) ||
                empty($data['shop']) ||
                empty($data['ts']) ||
                empty($data['nonce'])
            ) {
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            $this->log('warning', 'Failed to parse OAuth state', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NONCE MANAGEMENT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Store nonce in cache for single-use validation.
     *
     * @param  string  $nonce  The nonce to store
     * @param  string  $clubId  The club ID for namespacing
     */
    private function storeNonce(string $nonce, string $clubId): void
    {
        $cacheKey = self::NONCE_CACHE_PREFIX.$nonce;

        Cache::put($cacheKey, [
            'club_id' => $clubId,
            'created_at' => time(),
        ], self::NONCE_TTL);
    }

    /**
     * Validate and consume a nonce (single-use).
     *
     * @param  string  $nonce  The nonce to validate
     * @param  string  $clubId  Expected club ID
     * @return bool True if valid and consumed, false otherwise
     */
    private function validateAndConsumeNonce(string $nonce, string $clubId): bool
    {
        $cacheKey = self::NONCE_CACHE_PREFIX.$nonce;
        $data = Cache::get($cacheKey);

        if (! $data) {
            $this->log('warning', 'Nonce not found or expired', [
                'nonce' => substr($nonce, 0, 8).'...',
            ]);

            return false;
        }

        // Validate club_id matches
        if ($data['club_id'] !== $clubId) {
            $this->log('warning', 'Nonce club_id mismatch', [
                'expected' => $clubId,
                'actual' => $data['club_id'],
            ]);

            return false;
        }

        // Consume nonce (delete from cache to prevent reuse)
        Cache::forget($cacheKey);

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HMAC VALIDATION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validate Shopify's HMAC signature on the callback.
     *
     * Shopify signs callbacks using the client secret. This verification
     * ensures the callback originated from Shopify.
     *
     * @param  array  $params  All query parameters from the callback
     * @return bool True if signature is valid
     */
    private function validateHmac(array $params): bool
    {
        $hmac = $params['hmac'] ?? null;
        if (empty($hmac)) {
            return false;
        }

        // Remove hmac from params for signature calculation
        $signatureParams = $params;
        unset($signatureParams['hmac']);

        // Sort params alphabetically and build query string
        ksort($signatureParams);
        $queryString = http_build_query($signatureParams);

        // Calculate expected HMAC
        $secret = config('integrations.shopify.client_secret');
        $expectedHmac = hash_hmac('sha256', $queryString, $secret);

        return hash_equals($expectedHmac, $hmac);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Normalize shop domain to the canonical format.
     *
     * Accepts various formats and returns: mystore.myshopify.com
     *
     * @param  string  $shop  Shop domain in any format
     * @return string Normalized shop domain
     *
     * @throws \InvalidArgumentException If domain is invalid
     */
    private function normalizeShopDomain(string $shop): string
    {
        // Remove protocol if present
        $shop = preg_replace('#^https?://#', '', trim($shop));

        // Remove trailing slash
        $shop = rtrim($shop, '/');

        // Remove /admin or other paths
        $shop = explode('/', $shop)[0];

        // Add .myshopify.com if not present
        if (! str_ends_with($shop, '.myshopify.com')) {
            $shop .= '.myshopify.com';
        }

        // Validate format
        if (! preg_match('/^[a-z0-9-]+\.myshopify\.com$/i', $shop)) {
            throw new \InvalidArgumentException("Invalid Shopify domain: {$shop}");
        }

        return strtolower($shop);
    }

    /**
     * Get the OAuth callback URL.
     *
     * @return string Callback URL
     */
    private function getCallbackUrl(): string
    {
        $appUrl = config('integrations.shopify.app_url');

        return "{$appUrl}/shopify/callback";
    }

    /**
     * Log a message with service context.
     */
    private function log(string $level, string $message, array $context = []): void
    {
        Log::log($level, "[Shopify OAuth] {$message}", $context);
    }
}
