<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * EntitlementServiceTest
 *
 * Tests the centralized EntitlementService that governs all partner
 * plan-based feature gates, resource limits, billing state resolution,
 * and entitlement summaries.
 *
 * @see App\Services\EntitlementService
 */

use App\Models\Card;
use App\Models\Club;
use App\Models\Partner;
use App\Models\Reward;
use App\Models\Staff;
use App\Services\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function entitlementService(): EntitlementService
{
    return new EntitlementService();
}

/** Create a partner with clean meta (no overrides). */
function partnerWithPlan(string $plan): Partner
{
    $partner = Partner::factory()->create(['plan' => $plan]);
    $partner->update(['meta' => []]);

    return $partner->fresh();
}

/*
|--------------------------------------------------------------------------
| Subscription Status
|--------------------------------------------------------------------------
*/

describe('subscriptionStatus', function () {

    it('returns manual when billing_provider is null', function () {
        config(['default.billing_provider' => null]);
        $partner = Partner::factory()->create(['plan' => 'tier3']);

        expect(entitlementService()->subscriptionStatus($partner))
            ->toBe('manual');
    });

    it('returns manual when billing_provider is empty string', function () {
        config(['default.billing_provider' => '']);
        $partner = Partner::factory()->create(['plan' => 'tier3']);

        expect(entitlementService()->subscriptionStatus($partner))
            ->toBe('manual');
    });

    it('returns manual when billing_provider is "null" string', function () {
        config(['default.billing_provider' => 'null']);
        $partner = Partner::factory()->create(['plan' => 'tier3']);

        expect(entitlementService()->subscriptionStatus($partner))
            ->toBe('manual');
    });

    it('returns legacy when billing_provider is stripe but partner has no subscription', function () {
        config(['default.billing_provider' => 'stripe']);
        $partner = Partner::factory()->create(['plan' => 'tier2']);

        expect(entitlementService()->subscriptionStatus($partner))
            ->toBe('legacy');
    });
});

/*
|--------------------------------------------------------------------------
| Access Restriction
|--------------------------------------------------------------------------
*/

describe('isAccessRestricted', function () {

    it('returns false for manual', function () {
        config(['default.billing_provider' => null]);
        $partner = Partner::factory()->create(['plan' => 'tier1']);

        expect(entitlementService()->isAccessRestricted($partner))->toBeFalse();
    });

    it('returns false for legacy', function () {
        config(['default.billing_provider' => 'stripe']);
        $partner = Partner::factory()->create(['plan' => 'tier3']);

        expect(entitlementService()->isAccessRestricted($partner))->toBeFalse();
    });

    it('has past_due in restricted statuses', function () {
        expect(EntitlementService::RESTRICTED_STATUSES)->toContain('past_due');
    });

    it('has cancelled in restricted statuses', function () {
        expect(EntitlementService::RESTRICTED_STATUSES)->toContain('cancelled');
    });

    it('has incomplete in restricted statuses', function () {
        expect(EntitlementService::RESTRICTED_STATUSES)->toContain('incomplete');
    });

    it('has suspended in restricted statuses', function () {
        expect(EntitlementService::RESTRICTED_STATUSES)->toContain('suspended');
    });

    it('does not restrict active statuses', function () {
        expect(EntitlementService::RESTRICTED_STATUSES)
            ->not->toContain('manual')
            ->not->toContain('legacy')
            ->not->toContain('active')
            ->not->toContain('trialing');
    });
});

/*
|--------------------------------------------------------------------------
| Restricted Billing State → withinLimit Enforcement
|--------------------------------------------------------------------------
*/

describe('restricted billing state enforcement', function () {

    it('blocks withinLimit for past_due status', function () {
        $partner = partnerWithPlan('tier3');

        // Partial mock: force subscriptionStatus to return past_due
        $service = Mockery::mock(EntitlementService::class)->makePartial();
        $service->shouldReceive('subscriptionStatus')
            ->with($partner)
            ->andReturn(EntitlementService::STATUS_PAST_DUE);

        expect($service->withinLimit($partner, 'cards'))->toBeFalse();
    });

    it('blocks withinLimit for cancelled status', function () {
        $partner = partnerWithPlan('tier3');

        $service = Mockery::mock(EntitlementService::class)->makePartial();
        $service->shouldReceive('subscriptionStatus')
            ->with($partner)
            ->andReturn(EntitlementService::STATUS_CANCELLED);

        expect($service->withinLimit($partner, 'staff'))->toBeFalse();
    });

    it('blocks withinLimit for incomplete status', function () {
        $partner = partnerWithPlan('tier3');

        $service = Mockery::mock(EntitlementService::class)->makePartial();
        $service->shouldReceive('subscriptionStatus')
            ->with($partner)
            ->andReturn(EntitlementService::STATUS_INCOMPLETE);

        expect($service->withinLimit($partner, 'vouchers'))->toBeFalse();
    });

    it('blocks withinLimit for suspended status', function () {
        $partner = partnerWithPlan('tier3');

        $service = Mockery::mock(EntitlementService::class)->makePartial();
        $service->shouldReceive('subscriptionStatus')
            ->with($partner)
            ->andReturn(EntitlementService::STATUS_SUSPENDED);

        expect($service->withinLimit($partner, 'cards'))->toBeFalse();
    });

    it('allows withinLimit for manual status', function () {
        config(['default.billing_provider' => null]);
        $partner = partnerWithPlan('tier3');

        // Gold plan has unlimited cards (-1), manual billing should allow creation
        expect(entitlementService()->withinLimit($partner, 'cards'))->toBeTrue();
    });
});

/*
|--------------------------------------------------------------------------
| Effective Plan
|--------------------------------------------------------------------------
*/

describe('effectivePlan', function () {

    it('returns the partner plan config with key', function () {
        $partner = Partner::factory()->create(['plan' => 'tier3']);

        $plan = entitlementService()->effectivePlan($partner);

        expect($plan)
            ->toHaveKey('key', 'tier3')
            ->toHaveKey('name', 'Gold')
            ->toHaveKey('price_monthly', 7900)
            ->toHaveKey('has_vouchers', true)
            ->toHaveKey('has_email_campaigns', true);
    });

    it('falls back to default plan for unknown plan slug', function () {
        $partner = Partner::factory()->create(['plan' => 'nonexistent']);

        $plan = entitlementService()->effectivePlan($partner);

        expect($plan['key'])->toBe('tier1');
    });
});

/*
|--------------------------------------------------------------------------
| Feature Gates — Bronze
|--------------------------------------------------------------------------
*/

describe('feature gates - tier1 plan', function () {

    it('allows loyalty cards', function () {
        expect(entitlementService()->can(partnerWithPlan('tier1'), 'loyalty_cards'))->toBeTrue();
    });

    it('allows stamp cards', function () {
        expect(entitlementService()->can(partnerWithPlan('tier1'), 'stamp_cards'))->toBeTrue();
    });

    it('denies vouchers', function () {
        expect(entitlementService()->can(partnerWithPlan('tier1'), 'vouchers'))->toBeFalse();
    });

    it('denies email campaigns', function () {
        expect(entitlementService()->can(partnerWithPlan('tier1'), 'email_campaigns'))->toBeFalse();
    });

    it('denies agent api', function () {
        expect(entitlementService()->can(partnerWithPlan('tier1'), 'agent_api'))->toBeFalse();
    });

    it('denies activity log', function () {
        expect(entitlementService()->can(partnerWithPlan('tier1'), 'activity_log'))->toBeFalse();
    });
});

/*
|--------------------------------------------------------------------------
| Feature Gates — Silver
|--------------------------------------------------------------------------
*/

describe('feature gates - tier2 plan', function () {

    it('allows vouchers', function () {
        expect(entitlementService()->can(partnerWithPlan('tier2'), 'vouchers'))->toBeTrue();
    });

    it('denies voucher batches', function () {
        expect(entitlementService()->can(partnerWithPlan('tier2'), 'voucher_batches'))->toBeFalse();
    });

    it('allows activity log', function () {
        expect(entitlementService()->can(partnerWithPlan('tier2'), 'activity_log'))->toBeTrue();
    });

    it('denies email campaigns', function () {
        expect(entitlementService()->can(partnerWithPlan('tier2'), 'email_campaigns'))->toBeFalse();
    });

    it('denies agent api', function () {
        expect(entitlementService()->can(partnerWithPlan('tier2'), 'agent_api'))->toBeFalse();
    });
});

/*
|--------------------------------------------------------------------------
| Feature Gates — Gold
|--------------------------------------------------------------------------
*/

describe('feature gates - tier3 plan', function () {

    it('allows vouchers', function () {
        expect(entitlementService()->can(partnerWithPlan('tier3'), 'vouchers'))->toBeTrue();
    });

    it('allows voucher batches', function () {
        expect(entitlementService()->can(partnerWithPlan('tier3'), 'voucher_batches'))->toBeTrue();
    });

    it('allows email campaigns', function () {
        expect(entitlementService()->can(partnerWithPlan('tier3'), 'email_campaigns'))->toBeTrue();
    });

    it('allows agent api', function () {
        expect(entitlementService()->can(partnerWithPlan('tier3'), 'agent_api'))->toBeTrue();
    });

    it('allows activity log', function () {
        expect(entitlementService()->can(partnerWithPlan('tier3'), 'activity_log'))->toBeTrue();
    });
});

/*
|--------------------------------------------------------------------------
| Feature Gates — Platinum
|--------------------------------------------------------------------------
*/

describe('feature gates - tier4 plan', function () {

    it('allows all features', function () {
        $partner = partnerWithPlan('tier4');
        $service = entitlementService();

        expect($service->can($partner, 'loyalty_cards'))->toBeTrue()
            ->and($service->can($partner, 'stamp_cards'))->toBeTrue()
            ->and($service->can($partner, 'vouchers'))->toBeTrue()
            ->and($service->can($partner, 'voucher_batches'))->toBeTrue()
            ->and($service->can($partner, 'email_campaigns'))->toBeTrue()
            ->and($service->can($partner, 'activity_log'))->toBeTrue()
            ->and($service->can($partner, 'agent_api'))->toBeTrue();
    });
});

/*
|--------------------------------------------------------------------------
| Feature Gates — Unknown features
|--------------------------------------------------------------------------
*/

describe('feature gates - edge cases', function () {

    it('returns false for unknown feature', function () {
        expect(entitlementService()->can(partnerWithPlan('tier4'), 'teleportation'))->toBeFalse();
    });
});

/*
|--------------------------------------------------------------------------
| Meta Overrides
|--------------------------------------------------------------------------
*/

describe('meta overrides', function () {

    it('grants vouchers to tier1 via meta override', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier1',
            'meta' => ['vouchers_permission' => true],
        ]);

        expect(entitlementService()->can($partner, 'vouchers'))->toBeTrue();
    });

    it('denies vouchers for tier3 via meta override', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier3',
            'meta' => ['vouchers_permission' => false],
        ]);

        expect(entitlementService()->can($partner, 'vouchers'))->toBeFalse();
    });

    it('grants email campaigns to tier1 via meta override', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier1',
            'meta' => ['email_campaigns_permission' => true],
        ]);

        expect(entitlementService()->can($partner, 'email_campaigns'))->toBeTrue();
    });

    it('overrides limit via meta', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier1',
            'meta' => ['loyalty_cards_limit' => 10],
        ]);

        expect(entitlementService()->limit($partner, 'cards'))->toBe(10);
    });
});

/*
|--------------------------------------------------------------------------
| Limits — Plan-based
|--------------------------------------------------------------------------
*/

describe('limit values', function () {

    it('tier1 card limit is 1', function () {
        expect(entitlementService()->limit(partnerWithPlan('tier1'), 'cards'))->toBe(1);
    });

    it('tier2 card limit is 3', function () {
        expect(entitlementService()->limit(partnerWithPlan('tier2'), 'cards'))->toBe(3);
    });

    it('tier3 card limit is 10', function () {
        expect(entitlementService()->limit(partnerWithPlan('tier3'), 'cards'))->toBe(10);
    });

    it('tier4 limits are unlimited', function () {
        $partner = partnerWithPlan('tier4');
        $service = entitlementService();

        expect($service->limit($partner, 'cards'))->toBe(-1)
            ->and($service->limit($partner, 'staff'))->toBe(-1)
            ->and($service->limit($partner, 'clubs'))->toBe(-1)
            ->and($service->limit($partner, 'rewards'))->toBe(-1)
            ->and($service->limit($partner, 'vouchers'))->toBe(-1)
            ->and($service->limit($partner, 'agent_keys'))->toBe(-1);
    });

    it('scales staff limits across tiers', function () {
        expect(entitlementService()->limit(partnerWithPlan('tier1'), 'staff'))->toBe(1)
            ->and(entitlementService()->limit(partnerWithPlan('tier2'), 'staff'))->toBe(5)
            ->and(entitlementService()->limit(partnerWithPlan('tier3'), 'staff'))->toBe(25)
            ->and(entitlementService()->limit(partnerWithPlan('tier4'), 'staff'))->toBe(-1);
    });

    it('returns 0 for unknown resource', function () {
        expect(entitlementService()->limit(partnerWithPlan('tier4'), 'spaceships'))->toBe(0);
    });
});

/*
|--------------------------------------------------------------------------
| Limits — Usage and Enforcement
|--------------------------------------------------------------------------
*/

describe('limit enforcement', function () {

    it('is within limit with zero resources', function () {
        $partner = partnerWithPlan('tier1');

        expect(entitlementService()->withinLimit($partner, 'cards'))->toBeTrue()
            ->and(entitlementService()->remaining($partner, 'cards'))->toBe(1);
    });

    it('blocks creation at limit', function () {
        $partner = partnerWithPlan('tier1');
        $club = Club::factory()->create(['created_by' => $partner->id]);

        Card::factory()->create([
            'created_by' => $partner->id,
            'club_id' => $club->id,
        ]);

        expect(entitlementService()->withinLimit($partner, 'cards'))->toBeFalse()
            ->and(entitlementService()->remaining($partner, 'cards'))->toBe(0);
    });

    it('counts partner resources accurately', function () {
        $partner = partnerWithPlan('tier3');
        $club = Club::factory()->create(['created_by' => $partner->id]);

        Card::factory()->count(3)->create([
            'created_by' => $partner->id,
            'club_id' => $club->id,
        ]);

        expect(entitlementService()->usage($partner, 'cards'))->toBe(3);
    });

    it('isolates counts between partners', function () {
        $partner1 = partnerWithPlan('tier3');
        $partner2 = partnerWithPlan('tier3');
        $club1 = Club::factory()->create(['created_by' => $partner1->id]);
        $club2 = Club::factory()->create(['created_by' => $partner2->id]);

        Card::factory()->count(5)->create(['created_by' => $partner1->id, 'club_id' => $club1->id]);
        Card::factory()->count(2)->create(['created_by' => $partner2->id, 'club_id' => $club2->id]);

        expect(entitlementService()->usage($partner1, 'cards'))->toBe(5)
            ->and(entitlementService()->usage($partner2, 'cards'))->toBe(2);
    });

    it('unlimited always within limit', function () {
        $partner = partnerWithPlan('tier4');

        expect(entitlementService()->withinLimit($partner, 'cards'))->toBeTrue()
            ->and(entitlementService()->remaining($partner, 'cards'))->toBe(-1);
    });

    it('enforces staff limits', function () {
        $partner = partnerWithPlan('tier1');
        $club = Club::factory()->create(['created_by' => $partner->id]);

        expect(entitlementService()->withinLimit($partner, 'staff'))->toBeTrue();

        Staff::factory()->create([
            'created_by' => $partner->id,
            'club_id' => $club->id,
        ]);

        expect(entitlementService()->withinLimit($partner, 'staff'))->toBeFalse();
    });

    it('enforces rewards limits', function () {
        $partner = partnerWithPlan('tier1');

        expect(entitlementService()->limit($partner, 'rewards'))->toBe(3);

        // Create rewards directly (no factory exists)
        for ($i = 0; $i < 3; $i++) {
            Reward::create([
                'name' => "Reward {$i}",
                'title' => json_encode(['en_US' => "Reward {$i}"]),
                'points' => 100,
                'created_by' => $partner->id,
                'is_active' => true,
            ]);
        }

        expect(entitlementService()->withinLimit($partner, 'rewards'))->toBeFalse()
            ->and(entitlementService()->remaining($partner, 'rewards'))->toBe(0);
    });

    it('enforces clubs limits', function () {
        $partner = partnerWithPlan('tier1');

        expect(entitlementService()->withinLimit($partner, 'clubs'))->toBeTrue();

        Club::factory()->create(['created_by' => $partner->id]);

        expect(entitlementService()->withinLimit($partner, 'clubs'))->toBeFalse();
    });

    it('returns zero for unknown resource usage', function () {
        expect(entitlementService()->usage(partnerWithPlan('tier3'), 'spaceships'))->toBe(0);
    });
});

/*
|--------------------------------------------------------------------------
| Deny Reasons
|--------------------------------------------------------------------------
*/

describe('deny reasons', function () {

    it('returns null for allowed feature', function () {
        expect(entitlementService()->denyReason(partnerWithPlan('tier3'), 'vouchers'))->toBeNull();
    });

    it('returns message for denied feature', function () {
        $reason = entitlementService()->denyReason(partnerWithPlan('tier1'), 'vouchers');

        expect($reason)
            ->not->toBeNull()
            ->toContain('Voucher Campaigns')
            ->toContain('Bronze');
    });

    it('returns message for unknown feature', function () {
        $reason = entitlementService()->denyReason(partnerWithPlan('tier3'), 'teleportation');

        expect($reason)
            ->not->toBeNull()
            ->toContain('teleportation');
    });

    it('returns null for limit within bounds', function () {
        expect(entitlementService()->limitDenyReason(partnerWithPlan('tier1'), 'cards'))->toBeNull();
    });

    it('returns message when limit exceeded', function () {
        $partner = partnerWithPlan('tier1');
        $club = Club::factory()->create(['created_by' => $partner->id]);
        Card::factory()->create(['created_by' => $partner->id, 'club_id' => $club->id]);

        $reason = entitlementService()->limitDenyReason($partner, 'cards');

        expect($reason)
            ->not->toBeNull()
            ->toContain('loyalty cards')
            ->toContain('1/1');
    });
});

/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/

describe('summary', function () {

    it('returns complete structure for tier3 plan', function () {
        config(['default.billing_provider' => null]);
        $partner = partnerWithPlan('tier3');

        $summary = entitlementService()->summary($partner);

        // Plan
        expect($summary['plan'])
            ->toHaveKey('key', 'tier3')
            ->toHaveKey('name', 'Gold')
            ->toHaveKey('is_free', false);

        // Status
        expect($summary['status'])->toBe('manual')
            ->and($summary['is_restricted'])->toBeFalse();

        // Features
        expect($summary['features'])
            ->toHaveKey('vouchers', true)
            ->toHaveKey('email_campaigns', true)
            ->toHaveKey('agent_api', true)
            ->toHaveKey('loyalty_cards', true);

        // Limits
        expect($summary['limits']['cards'])
            ->toHaveKey('limit', 10)
            ->toHaveKey('usage', 0)
            ->toHaveKey('remaining', 10)
            ->toHaveKey('unlimited', false)
            ->toHaveKey('exceeded', false);
    });

    it('shows is_free for tier1 plan', function () {
        $summary = entitlementService()->summary(partnerWithPlan('tier1'));

        expect($summary['plan']['is_free'])->toBeTrue();
    });

    it('shows exceeded limits after downgrade', function () {
        $partner = partnerWithPlan('tier1');
        $club = Club::factory()->create(['created_by' => $partner->id]);
        Card::factory()->count(2)->create(['created_by' => $partner->id, 'club_id' => $club->id]);

        $summary = entitlementService()->summary($partner);

        expect($summary['limits']['cards']['exceeded'])->toBeTrue()
            ->and($summary['limits']['cards']['remaining'])->toBe(0);
    });

    it('shows unlimited for tier4', function () {
        $summary = entitlementService()->summary(partnerWithPlan('tier4'));

        expect($summary['limits']['cards']['unlimited'])->toBeTrue()
            ->and($summary['limits']['cards']['remaining'])->toBe(-1);
    });
});
