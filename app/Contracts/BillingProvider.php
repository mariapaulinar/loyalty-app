<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * BillingProvider Contract
 *
 * Defines the abstraction for billing provider implementations.
 * Each provider (Null, Stripe, future PayPal/Paddle) implements this
 * interface to normalize subscription state, portal URLs, and customer
 * management into a single surface.
 *
 * @see App\Services\Billing\NullBillingProvider
 * @see App\Services\Billing\StripeBillingProvider
 * @see App\Services\Billing\BillingManager
 */

namespace App\Contracts;

use App\Models\Partner;

interface BillingProvider
{
    /**
     * Provider identifier (e.g. 'null', 'stripe').
     */
    public function name(): string;

    /**
     * Whether this provider is properly configured and ready to use.
     */
    public function isConfigured(): bool;

    /**
     * Resolve the subscription status for a partner.
     *
     * Returns one of the EntitlementService::STATUS_* constants.
     */
    public function subscriptionStatus(Partner $partner): string;

    /**
     * Whether the partner has an active subscription with this provider.
     */
    public function hasSubscription(Partner $partner): bool;

    /**
     * Get the Stripe/provider customer ID, creating one if necessary.
     *
     * Returns null if the provider doesn't support customer creation
     * or the partner doesn't need one.
     */
    public function createOrGetCustomer(Partner $partner): ?string;

    /**
     * URL to the provider's self-service billing portal.
     *
     * Returns null if the provider doesn't support a portal.
     */
    public function billingPortalUrl(Partner $partner, ?string $returnUrl = null): ?string;

    /**
     * URL to start a new subscription checkout.
     *
     * Returns null if the provider doesn't support checkout.
     *
     * @param string $plan    Plan key from config/plans.php (e.g. 'tier2')
     * @param string $interval 'monthly' or 'yearly'
     */
    public function checkoutUrl(Partner $partner, string $plan, string $interval = 'monthly'): ?string;
}
