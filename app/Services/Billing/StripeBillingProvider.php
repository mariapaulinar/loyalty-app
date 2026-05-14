<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * StripeBillingProvider
 *
 * Billing provider backed by Laravel Cashier (Stripe). Resolves partner
 * subscription state from Cashier's subscription model and normalizes
 * it to EntitlementService status constants.
 *
 * This provider is active when BILLING_PROVIDER=stripe in .env.
 * Partners without a stripe_id are treated as 'legacy' (full plan access,
 * no automated billing).
 *
 * @see \Laravel\Cashier\Billable
 */

namespace App\Services\Billing;

use App\Contracts\BillingProvider;
use App\Models\Partner;
use App\Services\EntitlementService;

class StripeBillingProvider implements BillingProvider
{
    /**
     * The Cashier subscription name used for plan subscriptions.
     */
    public const SUBSCRIPTION_NAME = 'default';

    public function name(): string
    {
        return 'stripe';
    }

    public function isConfigured(): bool
    {
        return $this->hasApiKeys() && $this->hasWebhookSecret();
    }

    /**
     * Check whether Stripe API keys are present.
     */
    public function hasApiKeys(): bool
    {
        $key = config('cashier.key', config('services.stripe.key'));
        $secret = config('cashier.secret', config('services.stripe.secret'));

        return ! empty($key) && ! empty($secret);
    }

    /**
     * Check whether the webhook signing secret is set.
     *
     * Without this, Cashier skips signature verification and accepts
     * unsigned requests — a security vulnerability.
     */
    public function hasWebhookSecret(): bool
    {
        $secret = config('cashier.webhook.secret');

        return ! empty($secret);
    }

    public function subscriptionStatus(Partner $partner): string
    {
        // Partners without a stripe_id are legacy — they were assigned
        // a plan before Stripe was enabled. They keep full access.
        if (empty($partner->stripe_id)) {
            return EntitlementService::STATUS_LEGACY;
        }

        $subscription = $partner->subscription(self::SUBSCRIPTION_NAME);

        // Has a Stripe customer but no subscription record
        if (! $subscription) {
            return EntitlementService::STATUS_LEGACY;
        }

        // ─────────────────────────────────────────────────────────────────
        // Map all Stripe subscription statuses explicitly.
        //
        // Stripe statuses (as of API 2024+):
        //   active, trialing, past_due, canceled, incomplete,
        //   incomplete_expired, paused, unpaid
        //
        // Cashier provides convenience methods for some, but we read
        // stripe_status directly for completeness.
        // ─────────────────────────────────────────────────────────────────

        $stripeStatus = $subscription->stripe_status;

        return match ($stripeStatus) {
            // Active states
            \Stripe\Subscription::STATUS_ACTIVE    => $this->resolveActiveStatus($subscription),
            \Stripe\Subscription::STATUS_TRIALING  => EntitlementService::STATUS_TRIALING,

            // Payment problem states (restricted)
            \Stripe\Subscription::STATUS_PAST_DUE  => EntitlementService::STATUS_PAST_DUE,
            \Stripe\Subscription::STATUS_INCOMPLETE => EntitlementService::STATUS_INCOMPLETE,

            // Ended states (restricted)
            \Stripe\Subscription::STATUS_CANCELED            => EntitlementService::STATUS_CANCELLED,
            \Stripe\Subscription::STATUS_INCOMPLETE_EXPIRED  => EntitlementService::STATUS_CANCELLED,

            // Suspended states (restricted)
            \Stripe\Subscription::STATUS_PAUSED  => EntitlementService::STATUS_SUSPENDED,
            \Stripe\Subscription::STATUS_UNPAID  => EntitlementService::STATUS_SUSPENDED,

            // Unknown future Stripe status — restrict access by default
            default => EntitlementService::STATUS_SUSPENDED,
        };
    }

    /**
     * Resolve between active and cancelled-on-grace-period.
     *
     * A subscription can be `active` in Stripe while also being
     * cancelled in Cashier (grace period until the billing cycle ends).
     */
    private function resolveActiveStatus(\Laravel\Cashier\Subscription $subscription): string
    {
        if ($subscription->canceled()) {
            return EntitlementService::STATUS_CANCELLED;
        }

        return EntitlementService::STATUS_ACTIVE;
    }

    public function hasSubscription(Partner $partner): bool
    {
        if (empty($partner->stripe_id)) {
            return false;
        }

        $subscription = $partner->subscription(self::SUBSCRIPTION_NAME);

        return $subscription !== null && $subscription->valid();
    }

    public function createOrGetCustomer(Partner $partner): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        // createOrGetStripeCustomer is a Cashier Billable method
        try {
            $customer = $partner->createOrGetStripeCustomer([
                'name' => $partner->name ?? $partner->business_name,
                'email' => $partner->email,
            ]);

            return $customer->id;
        } catch (\Exception $e) {
            report($e);

            return null;
        }
    }

    public function billingPortalUrl(Partner $partner, ?string $returnUrl = null): ?string
    {
        if (! $this->isConfigured() || empty($partner->stripe_id)) {
            return null;
        }

        try {
            return $partner->billingPortalUrl($returnUrl ?? config('app.url'));
        } catch (\Exception $e) {
            report($e);

            return null;
        }
    }

    public function checkoutUrl(Partner $partner, string $plan, string $interval = 'monthly'): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        // Resolve the Stripe price ID from env
        $priceId = $this->resolvePriceId($plan, $interval);

        if (! $priceId) {
            return null;
        }

        try {
            $checkout = $partner->newSubscription(self::SUBSCRIPTION_NAME, $priceId)
                ->checkout([
                    'success_url' => config('app.url').'/partner/billing?checkout=success',
                    'cancel_url' => config('app.url').'/partner/billing?checkout=cancelled',
                ]);

            return $checkout->url;
        } catch (\Exception $e) {
            report($e);

            return null;
        }
    }

    /**
     * Resolve a Stripe Price ID from config.
     *
     * Price IDs are mapped in config/default.php via STRIPE_PRICE_* env vars.
     * This uses config() (not env()) so it works with config caching.
     *
     * Convention: config('default.stripe_prices.{plan}.{interval}')
     * Example: config('default.stripe_prices.tier2.monthly')
     */
    private function resolvePriceId(string $plan, string $interval): ?string
    {
        $priceId = config('default.stripe_prices.'.strtolower($plan).'.'.strtolower($interval));

        return ! empty($priceId) ? $priceId : null;
    }
}
