<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * BillingManager
 *
 * Resolves the active billing provider based on the BILLING_PROVIDER
 * environment variable. Acts as the single entry point for all billing
 * operations. Registered as a singleton in the service container.
 *
 * Provider resolution:
 * - null / '' / 'null'  → NullBillingProvider (manual/offline billing)
 * - 'stripe'            → StripeBillingProvider (Laravel Cashier)
 * - other               → InvalidArgumentException
 *
 * @see App\Contracts\BillingProvider
 */

namespace App\Services\Billing;

use App\Contracts\BillingProvider;
use InvalidArgumentException;

class BillingManager
{
    private ?BillingProvider $provider = null;

    /**
     * Get the active billing provider.
     *
     * Lazily resolved on first access and cached for the request lifecycle.
     */
    public function provider(): BillingProvider
    {
        if ($this->provider === null) {
            $this->provider = $this->resolveProvider();
        }

        return $this->provider;
    }

    /**
     * Get the configured billing provider name.
     *
     * Returns the normalized provider name string.
     */
    public function providerName(): string
    {
        return $this->provider()->name();
    }

    /**
     * Check if billing is enabled (any provider other than null).
     */
    public function isEnabled(): bool
    {
        return $this->provider()->name() !== 'null';
    }

    /**
     * Check if the active provider is properly configured.
     */
    public function isConfigured(): bool
    {
        return $this->provider()->isConfigured();
    }

    /**
     * Resolve the billing provider from configuration.
     *
     * @throws InvalidArgumentException if an unknown provider is configured
     */
    private function resolveProvider(): BillingProvider
    {
        $provider = config('default.billing_provider');

        // Normalize null-ish values
        if ($provider === null || $provider === '' || $provider === 'null') {
            return new NullBillingProvider();
        }

        return match ($provider) {
            'stripe' => new StripeBillingProvider(),
            default => throw new InvalidArgumentException(
                "Unknown billing provider '{$provider}'. Supported: null, stripe."
            ),
        };
    }

    /**
     * Force a specific provider (useful for testing).
     */
    public function setProvider(BillingProvider $provider): void
    {
        $this->provider = $provider;
    }
}
