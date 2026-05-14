<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * BillingFoundationTest
 *
 * Tests the billing provider abstraction, BillingManager resolution,
 * NullBillingProvider behavior, StripeBillingProvider behavior,
 * EntitlementService integration, and migration safety.
 *
 * @see App\Services\Billing\BillingManager
 * @see App\Services\Billing\NullBillingProvider
 * @see App\Services\Billing\StripeBillingProvider
 * @see App\Services\EntitlementService
 */

use App\Contracts\BillingProvider;
use App\Models\Partner;
use App\Services\Billing\BillingManager;
use App\Services\Billing\NullBillingProvider;
use App\Services\Billing\StripeBillingProvider;
use App\Services\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function billingManager(): BillingManager
{
    // Fresh instance each time to avoid cached provider between tests
    $manager = new BillingManager();

    return $manager;
}

/*
|--------------------------------------------------------------------------
| BillingManager — Provider Resolution
|--------------------------------------------------------------------------
*/

describe('BillingManager provider resolution', function () {

    it('resolves NullBillingProvider when billing_provider is null', function () {
        config(['default.billing_provider' => null]);

        expect(billingManager()->provider())
            ->toBeInstanceOf(NullBillingProvider::class);
    });

    it('resolves NullBillingProvider when billing_provider is empty string', function () {
        config(['default.billing_provider' => '']);

        expect(billingManager()->provider())
            ->toBeInstanceOf(NullBillingProvider::class);
    });

    it('resolves NullBillingProvider when billing_provider is "null" string', function () {
        config(['default.billing_provider' => 'null']);

        expect(billingManager()->provider())
            ->toBeInstanceOf(NullBillingProvider::class);
    });

    it('resolves StripeBillingProvider when billing_provider is stripe', function () {
        config(['default.billing_provider' => 'stripe']);

        expect(billingManager()->provider())
            ->toBeInstanceOf(StripeBillingProvider::class);
    });

    it('throws for unknown billing provider', function () {
        config(['default.billing_provider' => 'paypal']);

        billingManager()->provider();
    })->throws(\InvalidArgumentException::class);

    it('reports billing not enabled for null provider', function () {
        config(['default.billing_provider' => null]);

        expect(billingManager()->isEnabled())->toBeFalse();
    });

    it('reports billing enabled for stripe provider', function () {
        config(['default.billing_provider' => 'stripe']);

        expect(billingManager()->isEnabled())->toBeTrue();
    });

    it('reports provider name correctly', function () {
        config(['default.billing_provider' => null]);
        expect(billingManager()->providerName())->toBe('null');

        config(['default.billing_provider' => 'stripe']);
        expect(billingManager()->providerName())->toBe('stripe');
    });

    it('binds BillingProvider contract via service container', function () {
        config(['default.billing_provider' => null]);

        // Reset the singleton so it re-resolves
        app()->forgetInstance(BillingManager::class);

        $provider = app(BillingProvider::class);
        expect($provider)->toBeInstanceOf(NullBillingProvider::class);
    });

    it('allows setting a custom provider for testing', function () {
        $manager = new BillingManager();
        $mock = Mockery::mock(BillingProvider::class);
        $mock->shouldReceive('name')->andReturn('test');

        $manager->setProvider($mock);

        expect($manager->providerName())->toBe('test');
    });
});

/*
|--------------------------------------------------------------------------
| NullBillingProvider
|--------------------------------------------------------------------------
*/

describe('NullBillingProvider', function () {

    it('returns null as name', function () {
        expect((new NullBillingProvider())->name())->toBe('null');
    });

    it('is always configured', function () {
        expect((new NullBillingProvider())->isConfigured())->toBeTrue();
    });

    it('returns manual status for any partner', function () {
        $partner = Partner::factory()->create(['plan' => 'tier3']);

        expect((new NullBillingProvider())->subscriptionStatus($partner))
            ->toBe(EntitlementService::STATUS_MANUAL);
    });

    it('reports no subscription for any partner', function () {
        $partner = Partner::factory()->create(['plan' => 'tier2']);

        expect((new NullBillingProvider())->hasSubscription($partner))->toBeFalse();
    });

    it('returns null for customer creation', function () {
        $partner = Partner::factory()->create();

        expect((new NullBillingProvider())->createOrGetCustomer($partner))->toBeNull();
    });

    it('returns null for billing portal URL', function () {
        $partner = Partner::factory()->create();

        expect((new NullBillingProvider())->billingPortalUrl($partner))->toBeNull();
    });

    it('returns null for checkout URL', function () {
        $partner = Partner::factory()->create();

        expect((new NullBillingProvider())->checkoutUrl($partner, 'tier2'))->toBeNull();
    });
});

/*
|--------------------------------------------------------------------------
| StripeBillingProvider — Configuration
|--------------------------------------------------------------------------
*/

describe('StripeBillingProvider configuration', function () {

    it('returns stripe as name', function () {
        expect((new StripeBillingProvider())->name())->toBe('stripe');
    });

    it('reports not configured without any Stripe keys', function () {
        config(['cashier.key' => null, 'cashier.secret' => null, 'cashier.webhook.secret' => null]);
        config(['services.stripe.key' => null, 'services.stripe.secret' => null]);

        $provider = new StripeBillingProvider();

        expect($provider->isConfigured())->toBeFalse()
            ->and($provider->hasApiKeys())->toBeFalse()
            ->and($provider->hasWebhookSecret())->toBeFalse();
    });

    it('reports not configured with API keys but no webhook secret', function () {
        config(['cashier.key' => 'pk_test_xxx', 'cashier.secret' => 'sk_test_xxx']);
        config(['cashier.webhook.secret' => null]);

        $provider = new StripeBillingProvider();

        expect($provider->hasApiKeys())->toBeTrue()
            ->and($provider->hasWebhookSecret())->toBeFalse()
            ->and($provider->isConfigured())->toBeFalse();
    });

    it('reports configured only with all three credentials', function () {
        config(['cashier.key' => 'pk_test_xxx', 'cashier.secret' => 'sk_test_xxx']);
        config(['cashier.webhook.secret' => 'whsec_test_xxx']);

        $provider = new StripeBillingProvider();

        expect($provider->hasApiKeys())->toBeTrue()
            ->and($provider->hasWebhookSecret())->toBeTrue()
            ->and($provider->isConfigured())->toBeTrue();
    });
});

/*
|--------------------------------------------------------------------------
| StripeBillingProvider — Status Resolution
|--------------------------------------------------------------------------
*/

describe('StripeBillingProvider subscription status', function () {

    it('returns legacy for partner without stripe_id', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier2',
            'stripe_id' => null,
        ]);

        expect((new StripeBillingProvider())->subscriptionStatus($partner))
            ->toBe(EntitlementService::STATUS_LEGACY);
    });

    it('returns legacy for partner with stripe_id but no subscription', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier3',
            'stripe_id' => 'cus_test_123',
        ]);

        expect((new StripeBillingProvider())->subscriptionStatus($partner))
            ->toBe(EntitlementService::STATUS_LEGACY);
    });

    it('reports no subscription for partner without stripe_id', function () {
        $partner = Partner::factory()->create(['stripe_id' => null]);

        expect((new StripeBillingProvider())->hasSubscription($partner))->toBeFalse();
    });

    it('returns null for portal URL when partner has no stripe_id', function () {
        $partner = Partner::factory()->create(['stripe_id' => null]);

        expect((new StripeBillingProvider())->billingPortalUrl($partner))->toBeNull();
    });

    it('returns null for checkout URL when not configured', function () {
        config(['cashier.key' => null, 'cashier.secret' => null]);
        config(['services.stripe.key' => null, 'services.stripe.secret' => null]);

        $partner = Partner::factory()->create();

        expect((new StripeBillingProvider())->checkoutUrl($partner, 'tier2'))->toBeNull();
    });

    it('resolves Stripe price IDs from config (not env)', function () {
        config(['default.stripe_prices.tier2.monthly' => 'price_test_tier2_monthly']);
        config(['default.stripe_prices.tier3.yearly' => 'price_test_tier3_yearly']);
        config(['default.stripe_prices.tier1.monthly' => null]);

        $provider = new StripeBillingProvider();

        // Use reflection to test private method
        $reflection = new \ReflectionMethod($provider, 'resolvePriceId');
        $reflection->setAccessible(true);

        expect($reflection->invoke($provider, 'tier2', 'monthly'))->toBe('price_test_tier2_monthly')
            ->and($reflection->invoke($provider, 'tier3', 'yearly'))->toBe('price_test_tier3_yearly')
            ->and($reflection->invoke($provider, 'tier1', 'monthly'))->toBeNull()
            ->and($reflection->invoke($provider, 'tier99', 'monthly'))->toBeNull();
    });

    // ─────────────────────────────────────────────────────────────────────
    // Stripe status → EntitlementService status normalization
    // ─────────────────────────────────────────────────────────────────────

    it('maps stripe unpaid status to suspended (not legacy)', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier2',
            'stripe_id' => 'cus_test_unpaid',
        ]);

        $partner->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_unpaid',
            'stripe_status' => 'unpaid',
            'stripe_price' => 'price_test_tier2_monthly',
            'quantity' => 1,
        ]);

        $status = (new StripeBillingProvider())->subscriptionStatus($partner->fresh());

        expect($status)->toBe(EntitlementService::STATUS_SUSPENDED)
            ->and(in_array($status, EntitlementService::RESTRICTED_STATUSES, true))->toBeTrue();
    });

    it('maps stripe paused status to suspended (not legacy)', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier3',
            'stripe_id' => 'cus_test_paused',
        ]);

        $partner->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_paused',
            'stripe_status' => 'paused',
            'stripe_price' => 'price_test_tier3_monthly',
            'quantity' => 1,
        ]);

        $status = (new StripeBillingProvider())->subscriptionStatus($partner->fresh());

        expect($status)->toBe(EntitlementService::STATUS_SUSPENDED)
            ->and(in_array($status, EntitlementService::RESTRICTED_STATUSES, true))->toBeTrue();
    });

    it('maps stripe incomplete_expired status to cancelled (not legacy)', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier2',
            'stripe_id' => 'cus_test_inc_expired',
        ]);

        $partner->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_inc_expired',
            'stripe_status' => 'incomplete_expired',
            'stripe_price' => 'price_test_tier2_monthly',
            'quantity' => 1,
        ]);

        $status = (new StripeBillingProvider())->subscriptionStatus($partner->fresh());

        expect($status)->toBe(EntitlementService::STATUS_CANCELLED)
            ->and(in_array($status, EntitlementService::RESTRICTED_STATUSES, true))->toBeTrue();
    });

    it('maps all known stripe statuses to explicit entitlement statuses', function () {
        // Verify every Stripe status constant has an explicit mapping
        // and none fall through to legacy
        $partner = Partner::factory()->create([
            'plan' => 'tier2',
            'stripe_id' => 'cus_test_all_statuses',
        ]);

        $expectedMappings = [
            'active'             => EntitlementService::STATUS_ACTIVE,
            'trialing'           => EntitlementService::STATUS_TRIALING,
            'past_due'           => EntitlementService::STATUS_PAST_DUE,
            'incomplete'         => EntitlementService::STATUS_INCOMPLETE,
            'canceled'           => EntitlementService::STATUS_CANCELLED,
            'incomplete_expired' => EntitlementService::STATUS_CANCELLED,
            'paused'             => EntitlementService::STATUS_SUSPENDED,
            'unpaid'             => EntitlementService::STATUS_SUSPENDED,
        ];

        $provider = new StripeBillingProvider();

        foreach ($expectedMappings as $stripeStatus => $expectedEntitlementStatus) {
            // Create or update subscription with the Stripe status
            $partner->subscriptions()->updateOrCreate(
                ['type' => 'default'],
                [
                    'stripe_id' => 'sub_test_all_statuses',
                    'stripe_status' => $stripeStatus,
                    'stripe_price' => 'price_test',
                    'quantity' => 1,
                ]
            );

            $result = $provider->subscriptionStatus($partner->fresh());

            expect($result)->toBe($expectedEntitlementStatus,
                "Stripe status '{$stripeStatus}' should map to '{$expectedEntitlementStatus}', got '{$result}'");
        }
    });

    it('defaults unknown stripe statuses to suspended (restricted, not legacy)', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier2',
            'stripe_id' => 'cus_test_unknown',
        ]);

        $partner->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_unknown',
            'stripe_status' => 'some_future_stripe_status',
            'stripe_price' => 'price_test',
            'quantity' => 1,
        ]);

        $status = (new StripeBillingProvider())->subscriptionStatus($partner->fresh());

        // Must never become legacy (unrestricted) — fail-safe to restricted
        expect($status)->toBe(EntitlementService::STATUS_SUSPENDED)
            ->and($status)->not->toBe(EntitlementService::STATUS_LEGACY)
            ->and(in_array($status, EntitlementService::RESTRICTED_STATUSES, true))->toBeTrue();
    });

    it('restricts access for unpaid and paused partners via EntitlementService', function () {
        config(['default.billing_provider' => 'stripe']);
        app()->forgetInstance(BillingManager::class);

        $service = new EntitlementService();

        foreach (['unpaid', 'paused'] as $stripeStatus) {
            $partner = Partner::factory()->create([
                'plan' => 'tier3',
                'stripe_id' => "cus_test_restrict_{$stripeStatus}",
            ]);

            $partner->subscriptions()->create([
                'type' => 'default',
                'stripe_id' => "sub_test_restrict_{$stripeStatus}",
                'stripe_status' => $stripeStatus,
                'stripe_price' => 'price_test',
                'quantity' => 1,
            ]);

            expect($service->isAccessRestricted($partner->fresh()))->toBeTrue(
                "Partner with Stripe status '{$stripeStatus}' should have restricted access"
            );
        }
    });
});


/*
|--------------------------------------------------------------------------
| EntitlementService — BillingManager Integration
|--------------------------------------------------------------------------
*/

describe('EntitlementService billing integration', function () {

    it('returns manual when billing is disabled', function () {
        config(['default.billing_provider' => null]);
        app()->forgetInstance(BillingManager::class);

        $partner = Partner::factory()->create(['plan' => 'tier3']);

        expect((new EntitlementService())->subscriptionStatus($partner))
            ->toBe('manual');
    });

    it('returns legacy when billing is stripe but partner has no stripe_id', function () {
        config(['default.billing_provider' => 'stripe']);
        app()->forgetInstance(BillingManager::class);

        $partner = Partner::factory()->create([
            'plan' => 'tier2',
            'stripe_id' => null,
        ]);

        expect((new EntitlementService())->subscriptionStatus($partner))
            ->toBe('legacy');
    });

    it('does not restrict access for manual status', function () {
        config(['default.billing_provider' => null]);
        app()->forgetInstance(BillingManager::class);

        $partner = Partner::factory()->create(['plan' => 'tier1']);

        expect((new EntitlementService())->isAccessRestricted($partner))->toBeFalse();
    });

    it('does not restrict access for legacy status', function () {
        config(['default.billing_provider' => 'stripe']);
        app()->forgetInstance(BillingManager::class);

        $partner = Partner::factory()->create([
            'plan' => 'tier3',
            'stripe_id' => null,
        ]);

        expect((new EntitlementService())->isAccessRestricted($partner))->toBeFalse();
    });

    it('preserves feature gates after billing integration', function () {
        config(['default.billing_provider' => null]);
        app()->forgetInstance(BillingManager::class);

        $partner = Partner::factory()->create(['plan' => 'tier1']);
        $partner->update(['meta' => []]);
        $partner = $partner->fresh();

        $service = new EntitlementService();

        expect($service->can($partner, 'loyalty_cards'))->toBeTrue()
            ->and($service->can($partner, 'vouchers'))->toBeFalse();
    });

    it('preserves limit enforcement after billing integration', function () {
        config(['default.billing_provider' => 'stripe']);
        app()->forgetInstance(BillingManager::class);

        $partner = Partner::factory()->create([
            'plan' => 'tier1',
            'stripe_id' => null,
        ]);
        $partner->update(['meta' => []]);
        $partner = $partner->fresh();

        $service = new EntitlementService();

        // Legacy partner still gets plan-based limits
        expect($service->limit($partner, 'cards'))->toBe(1)
            ->and($service->withinLimit($partner, 'cards'))->toBeTrue();
    });

    it('summary includes correct status from billing provider', function () {
        config(['default.billing_provider' => null]);
        app()->forgetInstance(BillingManager::class);

        $partner = Partner::factory()->create(['plan' => 'tier3']);
        $partner->update(['meta' => []]);
        $partner = $partner->fresh();

        $summary = (new EntitlementService())->summary($partner);

        expect($summary['status'])->toBe('manual')
            ->and($summary['is_restricted'])->toBeFalse()
            ->and($summary['plan']['key'])->toBe('tier3');
    });

    it('summary includes correct status for stripe legacy partner', function () {
        config(['default.billing_provider' => 'stripe']);
        app()->forgetInstance(BillingManager::class);

        $partner = Partner::factory()->create([
            'plan' => 'tier2',
            'stripe_id' => null,
        ]);
        $partner->update(['meta' => []]);
        $partner = $partner->fresh();

        $summary = (new EntitlementService())->summary($partner);

        expect($summary['status'])->toBe('legacy')
            ->and($summary['is_restricted'])->toBeFalse();
    });
});

/*
|--------------------------------------------------------------------------
| Migration Safety — Cashier Columns
|--------------------------------------------------------------------------
*/

describe('Cashier column migration safety', function () {

    it('partner has stripe_id column', function () {
        $partner = Partner::factory()->create();

        expect($partner->stripe_id)->toBeNull();
    });

    it('partner has pm_type column', function () {
        $partner = Partner::factory()->create();

        expect($partner->pm_type)->toBeNull();
    });

    it('partner has pm_last_four column', function () {
        $partner = Partner::factory()->create();

        expect($partner->pm_last_four)->toBeNull();
    });

    it('partner has trial_ends_at column', function () {
        $partner = Partner::factory()->create();

        expect($partner->trial_ends_at)->toBeNull();
    });

    it('existing partner data is preserved with new columns', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier3',
            'name' => 'Test Partner',
            'email' => 'billing-test@example.com',
        ]);

        $fresh = Partner::find($partner->id);

        expect($fresh->plan)->toBe('tier3')
            ->and($fresh->name)->toBe('Test Partner')
            ->and($fresh->email)->toBe('billing-test@example.com')
            ->and($fresh->stripe_id)->toBeNull();
    });

    it('stripe_id can be set and retrieved', function () {
        $partner = Partner::factory()->create(['stripe_id' => 'cus_test_abc123']);

        expect(Partner::find($partner->id)->stripe_id)->toBe('cus_test_abc123');
    });

    it('Billable trait is available on Partner', function () {
        $partner = Partner::factory()->create();

        expect(method_exists($partner, 'subscription'))->toBeTrue()
            ->and(method_exists($partner, 'subscriptions'))->toBeTrue()
            ->and(method_exists($partner, 'createOrGetStripeCustomer'))->toBeTrue();
    });
});

/*
|--------------------------------------------------------------------------
| Health Center — Billing Check
|--------------------------------------------------------------------------
*/

describe('Health Center billing check', function () {

    it('reports ok for manual billing', function () {
        config(['default.billing_provider' => null]);
        app()->forgetInstance(BillingManager::class);

        $health = app(\App\Services\HealthService::class)->runChecks();
        $billingCheck = collect($health['checks'])->firstWhere('label', 'Billing Provider');

        expect($billingCheck['status'])->toBe('ok')
            ->and($billingCheck['value'])->toBe('Manual / Offline');
    });

    it('reports warning for unconfigured stripe (no API keys)', function () {
        config(['default.billing_provider' => 'stripe']);
        config(['cashier.key' => null, 'cashier.secret' => null, 'cashier.webhook.secret' => null]);
        config(['services.stripe.key' => null, 'services.stripe.secret' => null]);
        app()->forgetInstance(BillingManager::class);

        $health = app(\App\Services\HealthService::class)->runChecks();
        $billingCheck = collect($health['checks'])->firstWhere('label', 'Billing Provider');

        expect($billingCheck['status'])->toBe('warning')
            ->and($billingCheck['value'])->toContain('not configured');
    });

    it('reports critical for stripe with API keys but no webhook secret', function () {
        config(['default.billing_provider' => 'stripe']);
        config(['cashier.key' => 'pk_test_xxx', 'cashier.secret' => 'sk_test_xxx']);
        config(['cashier.webhook.secret' => null]);
        app()->forgetInstance(BillingManager::class);

        $health = app(\App\Services\HealthService::class)->runChecks();
        $billingCheck = collect($health['checks'])->firstWhere('label', 'Billing Provider');

        expect($billingCheck['status'])->toBe('critical')
            ->and($billingCheck['value'])->toContain('webhook insecure');
    });

    it('reports ok for fully configured stripe', function () {
        config(['default.billing_provider' => 'stripe']);
        config(['cashier.key' => 'pk_test_xxx', 'cashier.secret' => 'sk_test_xxx']);
        config(['cashier.webhook.secret' => 'whsec_test_xxx']);
        app()->forgetInstance(BillingManager::class);

        $health = app(\App\Services\HealthService::class)->runChecks();
        $billingCheck = collect($health['checks'])->firstWhere('label', 'Billing Provider');

        expect($billingCheck['status'])->toBe('ok')
            ->and($billingCheck['value'])->toBe('Stripe');
    });
});

/*
|--------------------------------------------------------------------------
| Webhook Route
|--------------------------------------------------------------------------
*/

describe('Stripe webhook route', function () {

    it('webhook route exists', function () {
        $route = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
            ->first(fn ($r) => $r->getName() === 'cashier.webhook');

        expect($route)->not->toBeNull()
            ->and($route->methods())->toContain('POST')
            ->and($route->uri())->toContain('stripe/webhook');
    });

    it('returns 403 with guard message when webhook secret is not configured', function () {
        config(['cashier.webhook.secret' => null]);

        $response = $this->postJson('/api/stripe/webhook', [
            'type' => 'customer.subscription.created',
            'data' => ['object' => ['id' => 'sub_fake']],
        ]);

        $response->assertStatus(403);

        // Verify our specific guard message (not Cashier's signature error)
        $content = $response->getContent();
        expect($content)->toContain('STRIPE_WEBHOOK_SECRET is not configured');
    });

    it('does not pass through our guard when webhook secret is configured', function () {
        config(['cashier.webhook.secret' => 'whsec_test_xxx']);

        // With a secret set, our guard passes. Cashier's VerifyWebhookSignature
        // middleware then rejects because the payload has no valid Stripe-Signature
        // header. Both return 403, but with different messages:
        // - Our guard:    "Webhook endpoint is disabled: STRIPE_WEBHOOK_SECRET is not configured."
        // - Cashier:      Stripe signature error from the Stripe SDK
        $response = $this->postJson('/api/stripe/webhook', [
            'type' => 'customer.subscription.created',
            'data' => ['object' => ['id' => 'sub_fake']],
        ]);

        // Should be 403 from Cashier's signature check (NOT our guard)
        $response->assertStatus(403);

        // Our guard message should NOT appear — Cashier's signature message should
        $content = $response->getContent();
        expect($content)->not->toContain('STRIPE_WEBHOOK_SECRET is not configured');
    });
});

/*
|--------------------------------------------------------------------------
| Cashier Customer Model Registration
|--------------------------------------------------------------------------
*/

describe('Cashier customer model registration', function () {

    it('registers Partner as Cashier customer model', function () {
        expect(\Laravel\Cashier\Cashier::$customerModel)->toBe(\App\Models\Partner::class);
    });

    it('resolves getUserByStripeId to Partner model', function () {
        $partner = Partner::factory()->create(['stripe_id' => 'cus_test_resolve_123']);

        // Use Cashier's static resolution method
        $resolved = \Laravel\Cashier\Cashier::findBillable('cus_test_resolve_123');

        expect($resolved)->not->toBeNull()
            ->and($resolved->id)->toBe($partner->id)
            ->and($resolved)->toBeInstanceOf(Partner::class);
    });

    it('Cashier does not auto-register its own routes', function () {
        // Should only have our custom webhook route, not Cashier's default
        $cashierDefaultRoute = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => $r->uri() === 'stripe/webhook') // Cashier's default (no api/ prefix)
            ->count();

        expect($cashierDefaultRoute)->toBe(0);
    });
});

/*
|--------------------------------------------------------------------------
| Webhook Event Handling
|--------------------------------------------------------------------------
| Tests that webhook events are routed correctly through the controller.
| These test the DB-level subscription lifecycle without calling Stripe.
*/

describe('Webhook event handling', function () {

    it('handles customer.subscription.created for a known partner', function () {
        $partner = Partner::factory()->create([
            'stripe_id' => 'cus_webhook_test_created',
        ]);

        $payload = [
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => 'sub_test_created_001',
                    'customer' => 'cus_webhook_test_created',
                    'status' => 'active',
                    'items' => [
                        'data' => [
                            [
                                'id' => 'si_test_001',
                                'price' => ['id' => 'price_test_tier2_monthly', 'product' => 'prod_test_tier2'],
                                'quantity' => 1,
                            ],
                        ],
                    ],
                    'metadata' => ['type' => 'default'],
                ],
            ],
        ];

        $controller = new \App\Http\Controllers\Billing\StripeWebhookController();
        $reflection = new \ReflectionMethod($controller, 'handleCustomerSubscriptionCreated');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($controller, $payload);

        // Subscription should be created in the DB
        $subscription = $partner->fresh()->subscriptions()->where('stripe_id', 'sub_test_created_001')->first();

        expect($subscription)->not->toBeNull()
            ->and($subscription->stripe_status)->toBe('active')
            ->and($subscription->stripe_price)->toBe('price_test_tier2_monthly');
    });

    it('handles customer.subscription.updated for an existing subscription', function () {
        $partner = Partner::factory()->create([
            'stripe_id' => 'cus_webhook_test_updated',
        ]);

        // Create the subscription first
        $partner->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_updated_001',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test_tier2_monthly',
            'quantity' => 1,
        ]);

        $payload = [
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_test_updated_001',
                    'customer' => 'cus_webhook_test_updated',
                    'status' => 'past_due',
                    'items' => [
                        'data' => [
                            [
                                'id' => 'si_test_002',
                                'price' => ['id' => 'price_test_tier2_monthly', 'product' => 'prod_test_tier2'],
                                'quantity' => 1,
                            ],
                        ],
                    ],
                    'metadata' => ['type' => 'default'],
                ],
            ],
        ];

        $controller = new \App\Http\Controllers\Billing\StripeWebhookController();
        $reflection = new \ReflectionMethod($controller, 'handleCustomerSubscriptionUpdated');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($controller, $payload);

        $subscription = $partner->fresh()->subscriptions()->where('stripe_id', 'sub_test_updated_001')->first();

        expect($subscription)->not->toBeNull()
            ->and($subscription->stripe_status)->toBe('past_due');
    });

    it('handles customer.subscription.updated with incomplete_expired by deleting subscription', function () {
        $partner = Partner::factory()->create([
            'stripe_id' => 'cus_webhook_test_expired',
        ]);

        $subscription = $partner->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_expired_001',
            'stripe_status' => 'incomplete',
            'stripe_price' => 'price_test_tier2_monthly',
            'quantity' => 1,
        ]);

        $payload = [
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_test_expired_001',
                    'customer' => 'cus_webhook_test_expired',
                    'status' => 'incomplete_expired',
                    'items' => [
                        'data' => [
                            [
                                'id' => 'si_test_003',
                                'price' => ['id' => 'price_test_tier2_monthly', 'product' => 'prod_test_tier2'],
                                'quantity' => 1,
                            ],
                        ],
                    ],
                    'metadata' => ['type' => 'default'],
                ],
            ],
        ];

        $controller = new \App\Http\Controllers\Billing\StripeWebhookController();
        $reflection = new \ReflectionMethod($controller, 'handleCustomerSubscriptionUpdated');
        $reflection->setAccessible(true);

        // Should return null (not Response) for incomplete_expired — this is the bug
        // that was caught by review: our old return type declaration would have crashed here.
        $result = $reflection->invoke($controller, $payload);

        // Parent returns null for incomplete_expired
        expect($result)->toBeNull();

        // Subscription should be deleted
        $exists = $partner->fresh()->subscriptions()->where('stripe_id', 'sub_test_expired_001')->exists();
        expect($exists)->toBeFalse();
    });

    it('handles customer.subscription.deleted by marking as cancelled', function () {
        $partner = Partner::factory()->create([
            'stripe_id' => 'cus_webhook_test_deleted',
        ]);

        $partner->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_deleted_001',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test_tier2_monthly',
            'quantity' => 1,
        ]);

        $payload = [
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_test_deleted_001',
                    'customer' => 'cus_webhook_test_deleted',
                    'status' => 'canceled',
                ],
            ],
        ];

        $controller = new \App\Http\Controllers\Billing\StripeWebhookController();
        $reflection = new \ReflectionMethod($controller, 'handleCustomerSubscriptionDeleted');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($controller, $payload);

        $subscription = $partner->fresh()->subscriptions()->where('stripe_id', 'sub_test_deleted_001')->first();

        // Cashier marks it as cancelled (sets ends_at)
        expect($subscription)->not->toBeNull()
            ->and($subscription->ends_at)->not->toBeNull();
    });

    it('handles duplicate subscription.created idempotently', function () {
        $partner = Partner::factory()->create([
            'stripe_id' => 'cus_webhook_test_idempotent',
        ]);

        $payload = [
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => 'sub_test_idempotent_001',
                    'customer' => 'cus_webhook_test_idempotent',
                    'status' => 'active',
                    'items' => [
                        'data' => [
                            [
                                'id' => 'si_test_idem',
                                'price' => ['id' => 'price_test_tier2_monthly', 'product' => 'prod_test_tier2'],
                                'quantity' => 1,
                            ],
                        ],
                    ],
                    'metadata' => ['type' => 'default'],
                ],
            ],
        ];

        $controller = new \App\Http\Controllers\Billing\StripeWebhookController();
        $reflection = new \ReflectionMethod($controller, 'handleCustomerSubscriptionCreated');
        $reflection->setAccessible(true);

        // Send twice — should not crash or create duplicates
        $reflection->invoke($controller, $payload);
        $reflection->invoke($controller, $payload);

        $count = $partner->fresh()->subscriptions()->where('stripe_id', 'sub_test_idempotent_001')->count();

        expect($count)->toBe(1);
    });

    it('ignores subscription events for unknown customers', function () {
        $payload = [
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => 'sub_test_unknown_001',
                    'customer' => 'cus_does_not_exist_999',
                    'status' => 'active',
                    'items' => [
                        'data' => [
                            [
                                'id' => 'si_test_unknown',
                                'price' => ['id' => 'price_test_tier2_monthly', 'product' => 'prod_test_tier2'],
                                'quantity' => 1,
                            ],
                        ],
                    ],
                    'metadata' => ['type' => 'default'],
                ],
            ],
        ];

        $controller = new \App\Http\Controllers\Billing\StripeWebhookController();
        $reflection = new \ReflectionMethod($controller, 'handleCustomerSubscriptionCreated');
        $reflection->setAccessible(true);

        // Should not throw — just silently skip
        $result = $reflection->invoke($controller, $payload);

        // Cashier returns a success response even for unknown customers
        expect($result)->not->toBeNull();
    });

    it('returns 200 for unhandled event types via missingMethod', function () {
        $controller = new \App\Http\Controllers\Billing\StripeWebhookController();
        $reflection = new \ReflectionMethod($controller, 'missingMethod');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($controller, []);

        expect($result->getStatusCode())->toBe(200);
    });
});
