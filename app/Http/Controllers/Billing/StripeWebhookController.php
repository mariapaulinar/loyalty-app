<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * StripeWebhookController
 *
 * Handles incoming Stripe webhook events via Laravel Cashier.
 * Extends Cashier's WebhookController for standard event handling
 * and adds custom logic for subscription state changes that need
 * to be reflected in the partner's local plan data.
 *
 * Security:
 * - Rejects all requests when STRIPE_WEBHOOK_SECRET is not configured.
 *   Without the secret, Cashier skips signature verification entirely,
 *   meaning anyone can send fake events. This override ensures the
 *   endpoint is always safe, even if the route is publicly reachable.
 * - When the secret IS set, Cashier's parent handles signature verification.
 *
 * Important: Cashier resolves the billable model via
 * Cashier::$customerModel, which AppServiceProvider sets to Partner::class.
 *
 * @see https://stripe.com/docs/webhooks
 */

namespace App\Http\Controllers\Billing;

use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    /**
     * Handle a Stripe webhook call.
     *
     * Overrides parent to enforce webhook secret presence. Without this,
     * Cashier accepts all requests when cashier.webhook.secret is null.
     */
    public function handleWebhook(Request $request)
    {
        if (empty(config('cashier.webhook.secret'))) {
            abort(403, 'Webhook endpoint is disabled: STRIPE_WEBHOOK_SECRET is not configured.');
        }

        return parent::handleWebhook($request);
    }

    /**
     * Handle customer subscription updated event.
     *
     * Parent returns null for incomplete_expired subscriptions (it deletes
     * the subscription and returns early). We must not declare a return
     * type that conflicts with that behavior.
     */
    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        // Future: sync partner plan state, send notifications, etc.
        // The EntitlementService reads from Cashier's subscription
        // models directly, so no manual sync is needed for access control.

        return $response;
    }

    /**
     * Handle customer subscription deleted event.
     *
     * When a subscription is fully cancelled (past grace period),
     * Cashier marks it as cancelled. The EntitlementService reads
     * this state and restricts creation accordingly.
     */
    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        return parent::handleCustomerSubscriptionDeleted($payload);
    }

    /**
     * Handle a successful webhook call.
     *
     * This is the catch-all for any webhook events that don't have
     * a specific handler method. Returns 200 OK to prevent Stripe
     * from retrying.
     */
    protected function missingMethod($parameters = [])
    {
        return $this->successMethod();
    }
}
