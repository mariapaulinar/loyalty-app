<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Integration Platform Enumeration
 *
 * Defines the supported e-commerce platforms that can be connected to
 * loyalty programs. Each platform has its own integration service,
 * webhook handlers, and configuration requirements.
 *
 * Architecture:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - Each case maps to a lowercase string stored in the database
 * - Platform-specific services are resolved using this enum
 * - New platforms should be added here before implementing their services
 *
 * Adding a New Platform:
 * ─────────────────────────────────────────────────────────────────────────────────
 * 1. Add the case to this enum (e.g., BIGCOMMERCE = 'bigcommerce')
 * 2. Add configuration in config/integrations.php
 * 3. Implement the platform service (App\Services\Integrations\{Platform}Service)
 * 4. Register webhook routes and handlers
 *
 * @see App\Models\ClubIntegration
 * @see config/integrations.php
 */

namespace App\Enums;

enum IntegrationPlatform: string
{
    /*
    |──────────────────────────────────────────────────────────────────────────────
    | Shopify
    |──────────────────────────────────────────────────────────────────────────────
    |
    | Primary supported platform. Full feature support including:
    | - OAuth 2.0 authentication
    | - Order webhook processing (points on purchase)
    | - Discount code generation (reward redemption)
    | - Customer matching via email
    |
    | @see https://shopify.dev/docs/api
    |
    */
    case SHOPIFY = 'shopify';

    /*
    |──────────────────────────────────────────────────────────────────────────────
    | WooCommerce
    |──────────────────────────────────────────────────────────────────────────────
    |
    | WordPress e-commerce platform. Planned for future implementation.
    | Will support similar features to Shopify integration.
    |
    | @see https://woocommerce.github.io/woocommerce-rest-api-docs/
    |
    */
    case WOOCOMMERCE = 'woocommerce';
}
