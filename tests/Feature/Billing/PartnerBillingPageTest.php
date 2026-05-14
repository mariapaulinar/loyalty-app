<?php

declare(strict_types=1);

/**
 * Partner Billing Page Tests
 *
 * Tests the partner billing page route, data shape, and rendering
 * across manual and Stripe billing modes.
 */

use App\Models\Partner;
use App\Services\Billing\BillingManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->partner = Partner::factory()->create([
        'plan' => 'tier1',
        'role' => 1,
    ]);
    $this->billingUrl = route('partner.billing', ['locale' => 'en-us']);
});

describe('Partner billing page', function () {

    it('is accessible to authenticated partners', function () {
        $this->actingAs($this->partner, 'partner')
            ->get($this->billingUrl)
            ->assertStatus(200);
    });

    it('returns redirect for unauthenticated requests', function () {
        $this->get($this->billingUrl)->assertRedirect();
    });

    it('passes summary with expected shape', function () {
        $response = $this->actingAs($this->partner, 'partner')
            ->get($this->billingUrl);

        $summary = $response->viewData('summary');

        expect($summary)->toHaveKeys(['plan', 'status', 'is_restricted', 'features', 'limits'])
            ->and($summary['plan'])->toHaveKeys(['key', 'name', 'description', 'is_free'])
            ->and($summary['features'])->toBeArray()
            ->and($summary['limits'])->toBeArray();
    });

    it('passes billing configuration flags', function () {
        $this->actingAs($this->partner, 'partner')
            ->get($this->billingUrl)
            ->assertViewHas('billingEnabled')
            ->assertViewHas('billingConfigured')
            ->assertViewHas('providerName');
    });

    it('passes plans collection', function () {
        $response = $this->actingAs($this->partner, 'partner')
            ->get($this->billingUrl);

        $plans = $response->viewData('plans');
        expect($plans)->toBeInstanceOf(\Illuminate\Support\Collection::class)
            ->and($plans->count())->toBeGreaterThanOrEqual(1);
    });
});

describe('Manual billing mode', function () {

    it('reports manual mode when billing disabled', function () {
        config(['default.billing_provider' => null]);
        app()->singleton(BillingManager::class);

        $response = $this->actingAs($this->partner, 'partner')
            ->get($this->billingUrl);

        $response->assertViewHas('billingEnabled', false)
            ->assertViewHas('portalUrl', null)
            ->assertViewHas('canUpgrade', false);
    });
});

describe('Stripe billing mode', function () {

    it('shows stripe provider when configured', function () {
        config([
            'default.billing_provider' => 'stripe',
            'cashier.key' => 'pk_test_xxx',
            'cashier.secret' => 'sk_test_xxx',
            'cashier.webhook.secret' => 'whsec_test_xxx',
        ]);
        app()->singleton(BillingManager::class);

        $response = $this->actingAs($this->partner, 'partner')
            ->get($this->billingUrl);

        $response->assertViewHas('billingEnabled', true)
            ->assertViewHas('billingConfigured', true)
            ->assertViewHas('providerName', 'stripe');
    });

    it('reports legacy for partner without stripe_id', function () {
        config([
            'default.billing_provider' => 'stripe',
            'cashier.key' => 'pk_test_xxx',
            'cashier.secret' => 'sk_test_xxx',
            'cashier.webhook.secret' => 'whsec_test_xxx',
        ]);
        app()->singleton(BillingManager::class);

        $summary = $this->actingAs($this->partner, 'partner')
            ->get($this->billingUrl)
            ->viewData('summary');

        expect($summary['status'])->toBe('legacy');
    });

    it('blocks upgrade for partners without stripe_id', function () {
        config([
            'default.billing_provider' => 'stripe',
            'cashier.key' => 'pk_test_xxx',
            'cashier.secret' => 'sk_test_xxx',
            'cashier.webhook.secret' => 'whsec_test_xxx',
        ]);
        app()->singleton(BillingManager::class);

        $this->actingAs($this->partner, 'partner')
            ->get($this->billingUrl)
            ->assertViewHas('canUpgrade', false);
    });
});

describe('Feature and limit data', function () {

    it('includes all feature gates', function () {
        $features = $this->actingAs($this->partner, 'partner')
            ->get($this->billingUrl)
            ->viewData('summary')['features'];

        expect($features)->toHaveKeys([
            'loyalty_cards', 'stamp_cards', 'vouchers',
            'voucher_batches', 'email_campaigns', 'activity_log', 'agent_api',
        ]);
    });

    it('includes all resource limits with correct shape', function () {
        $limits = $this->actingAs($this->partner, 'partner')
            ->get($this->billingUrl)
            ->viewData('summary')['limits'];

        expect($limits)->toHaveKeys([
            'cards', 'stamp_cards', 'vouchers', 'staff', 'rewards', 'clubs', 'agent_keys',
        ]);

        foreach ($limits as $limit) {
            expect($limit)->toHaveKeys(['limit', 'usage', 'remaining', 'unlimited', 'exceeded']);
        }
    });

    it('shows tier1 as free plan', function () {
        $plan = $this->actingAs($this->partner, 'partner')
            ->get($this->billingUrl)
            ->viewData('summary')['plan'];

        expect($plan['key'])->toBe('tier1')
            ->and($plan['is_free'])->toBeTrue();
    });

    it('shows tier3 as paid plan', function () {
        $partner = Partner::factory()->create(['plan' => 'tier3', 'role' => 1]);

        $plan = $this->actingAs($partner, 'partner')
            ->get(route('partner.billing', ['locale' => 'en-us']))
            ->viewData('summary')['plan'];

        expect($plan['key'])->toBe('tier3')
            ->and($plan['is_free'])->toBeFalse();
    });
});
