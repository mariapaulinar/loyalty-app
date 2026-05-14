<?php

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Third-Party Platform Integrations Configuration
 *
 * This configuration file centralizes settings for external e-commerce platform
 * integrations (Shopify, WooCommerce, etc.). These integrations enable automatic
 * point issuance on purchases, discount code generation for rewards, and real-time
 * sync between loyalty programs and online stores.
 *
 * Architecture Overview:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - Each platform has its own configuration namespace (shopify, woocommerce, etc.)
 * - Credentials are loaded from environment variables for security
 * - Per-integration secrets are stored in the database (ClubIntegration model)
 * - Global secrets here are FALLBACK only — prefer per-integration secrets
 *
 * Security Model:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - OAuth tokens stored encrypted in database (not here)
 * - Webhook secrets are per-integration (stored on ClubIntegration record)
 * - Global webhook secret is legacy/fallback only
 * - API keys never committed to version control
 *
 * @see App\Models\ClubIntegration
 * @see App\Services\Integrations\ShopifyService (future)
 */

return [

    /*
    |──────────────────────────────────────────────────────────────────────────────
    | Shopify Integration
    |──────────────────────────────────────────────────────────────────────────────
    |
    | Shopify is the primary supported e-commerce platform. This integration
    | enables:
    |
    | - Automatic point issuance when customers complete purchases
    | - Discount code generation when members redeem rewards
    | - Customer identification via email matching
    | - Real-time webhook processing for order events
    |
    | Required Shopify App Scopes:
    | - read_customers: Match Shopify customers to loyalty members
    | - read_orders: Process purchase events for point calculation
    | - write_discounts: Create discount codes for reward redemption
    |
    */

    'shopify' => [

        /*
        |──────────────────────────────────────────────────────────────────────────
        | API Version
        |──────────────────────────────────────────────────────────────────────────
        |
        | Shopify API version to use. We target the latest stable version.
        | Update this when migrating to newer API versions.
        |
        | @see https://shopify.dev/docs/api/usage/versioning
        |
        */
        'api_version' => env('SHOPIFY_API_VERSION', '2025-10'),

        /*
        |──────────────────────────────────────────────────────────────────────────
        | OAuth Scopes
        |──────────────────────────────────────────────────────────────────────────
        |
        | Permissions requested during OAuth flow. These determine what data
        | and actions are available to the integration.
        |
        | Current scopes:
        | - read_customers: Access customer data for member matching
        | - read_orders: Access order data for point calculations
        | - write_discounts: Create/manage discount codes for rewards
        |
        */
        'scopes' => env('SHOPIFY_SCOPES', 'read_customers,read_orders,write_discounts'),

        /*
        |──────────────────────────────────────────────────────────────────────────
        | OAuth Application Credentials
        |──────────────────────────────────────────────────────────────────────────
        |
        | Client credentials from your Shopify Partner Dashboard.
        | These are used for the OAuth 2.0 authorization flow.
        |
        | SECURITY: Never commit actual values. Use environment variables.
        |
        */
        'client_id' => env('SHOPIFY_CLIENT_ID'),
        'client_secret' => env('SHOPIFY_CLIENT_SECRET'),

        /*
        |──────────────────────────────────────────────────────────────────────────
        | Application URL
        |──────────────────────────────────────────────────────────────────────────
        |
        | The absolute base URL where this application is hosted. Used for:
        | - OAuth callback URLs
        | - Webhook endpoint registration
        | - Widget asset serving
        |
        | Example: https://app.rewardloyalty.io
        |
        | NOTE: Must include protocol (https://). No trailing slash.
        |
        */
        'app_url' => env('SHOPIFY_APP_URL', env('APP_URL', 'http://localhost')),

        /*
        |──────────────────────────────────────────────────────────────────────────
        | Webhook Processing Mode
        |──────────────────────────────────────────────────────────────────────────
        |
        | When true, incoming webhooks are queued for background processing.
        | When false, webhooks are processed synchronously (faster response
        | to Shopify, but blocks the request).
        |
        | Recommended: true for production (better reliability)
        |
        */
        'queue_webhooks' => env('SHOPIFY_QUEUE_WEBHOOKS', false),

        /*
        |──────────────────────────────────────────────────────────────────────────
        | Global Webhook Secret (LEGACY/FALLBACK)
        |──────────────────────────────────────────────────────────────────────────
        |
        | ⚠️  DEPRECATED: Prefer per-integration webhook_secret stored on the
        |     ClubIntegration model. This global secret is a fallback only.
        |
        | Each ClubIntegration record has its own webhook_secret for HMAC
        | verification. This global secret is only used if:
        | 1. Migration from legacy single-tenant setup
        | 2. Per-integration secret is missing (should not happen)
        |
        | @see App\Models\ClubIntegration::$hidden (webhook_secret)
        |
        */
        'global_webhook_secret' => env('SHOPIFY_WEBHOOK_SECRET'),

    ],

];
