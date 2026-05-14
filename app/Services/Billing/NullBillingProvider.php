<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * NullBillingProvider
 *
 * The default billing provider for installations where billing is disabled
 * or managed manually (invoiced outside the app). This is the safe default:
 * all partners get full plan-based access without any payment gateway.
 *
 * Used when BILLING_PROVIDER is null, empty, or 'null' in .env.
 */

namespace App\Services\Billing;

use App\Contracts\BillingProvider;
use App\Models\Partner;
use App\Services\EntitlementService;

class NullBillingProvider implements BillingProvider
{
    public function name(): string
    {
        return 'null';
    }

    public function isConfigured(): bool
    {
        // Null provider is always "configured" — nothing to set up.
        return true;
    }

    public function subscriptionStatus(Partner $partner): string
    {
        return EntitlementService::STATUS_MANUAL;
    }

    public function hasSubscription(Partner $partner): bool
    {
        return false;
    }

    public function createOrGetCustomer(Partner $partner): ?string
    {
        return null;
    }

    public function billingPortalUrl(Partner $partner, ?string $returnUrl = null): ?string
    {
        return null;
    }

    public function checkoutUrl(Partner $partner, string $plan, string $interval = 'monthly'): ?string
    {
        return null;
    }
}
