<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Admin SaaS Dashboard Tests
 *
 * Verifies access control, data shape, status grouping,
 * legacy partner safety, and empty state rendering.
 */

use App\Models\Admin;
use App\Models\Partner;
use App\Services\EntitlementService;
use App\Services\SaasDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function createSaasAdmin(array $attributes = []): Admin
{
    return Admin::create(array_merge([
        'id' => Str::uuid()->toString(),
        'name' => 'SaaS Admin',
        'email' => 'saas-admin'.Str::random(5).'@test.com',
        'password' => bcrypt('password'),
        'role' => 1,
        'locale' => 'en_US',
        'time_zone' => 'UTC',
        'currency' => 'USD',
        'is_active' => true,
    ], $attributes));
}

beforeEach(function () {
    app(SaasDashboardService::class)->clearCache();
});

// ═══════════════════════════════════════════════════════════════════════════════
// ACCESS CONTROL
// ═══════════════════════════════════════════════════════════════════════════════

test('admin can access saas dashboard', function () {
    $admin = createSaasAdmin();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.saas.index', ['locale' => 'en-us']));

    $response->assertStatus(200);
    $response->assertViewIs('admin.saas.index');
});

test('manager cannot access saas dashboard', function () {
    $manager = createSaasAdmin(['role' => 2]);

    $response = $this->actingAs($manager, 'admin')
        ->get(route('admin.saas.index', ['locale' => 'en-us']));

    $response->assertStatus(403);
});

test('unauthenticated user is redirected from saas dashboard', function () {
    $response = $this->get(route('admin.saas.index', ['locale' => 'en-us']));

    $response->assertRedirect();
});

// ═══════════════════════════════════════════════════════════════════════════════
// VIEW DATA SHAPE
// ═══════════════════════════════════════════════════════════════════════════════

test('saas dashboard provides expected view data', function () {
    $admin = createSaasAdmin();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.saas.index', ['locale' => 'en-us']));

    $response->assertViewHasAll([
        'statusGroups',
        'planDistribution',
        'trialExpiring',
        'pastDuePartners',
        'usageOverview',
        'recentRegistrations',
        'partnerCount',
        'billingEnabled',
        'billingConfigured',
        'providerName',
        'plans',
    ]);
});

test('plan distribution includes all active plans', function () {
    $admin = createSaasAdmin();
    Partner::factory()->create(['plan' => 'tier1']);

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.saas.index', ['locale' => 'en-us']));

    $planDistribution = $response->viewData('planDistribution');
    expect($planDistribution)->toBeArray();

    $activePlans = collect(config('plans'))->filter(fn ($p) => $p['is_active'] ?? true);
    foreach ($activePlans as $key => $plan) {
        expect($planDistribution)->toHaveKey($key);
    }
});

// ═══════════════════════════════════════════════════════════════════════════════
// MANUAL BILLING MODE (default)
// ═══════════════════════════════════════════════════════════════════════════════

test('manual billing mode groups all partners as manual', function () {
    $admin = createSaasAdmin();
    Partner::factory()->count(3)->create(['plan' => 'tier1']);

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.saas.index', ['locale' => 'en-us']));

    $statusGroups = $response->viewData('statusGroups');

    // NullBillingProvider returns STATUS_MANUAL for all partners
    expect($statusGroups)->toHaveKey(EntitlementService::STATUS_MANUAL);
    expect($statusGroups[EntitlementService::STATUS_MANUAL]['count'])->toBe(3);
});

test('dashboard does not expose revenue metrics', function () {
    $admin = createSaasAdmin();
    Partner::factory()->create(['plan' => 'tier1']);

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.saas.index', ['locale' => 'en-us']));

    // revenueMetrics was removed — no invented MRR/ARR
    $response->assertViewMissing('revenueMetrics');
    expect($response->viewData('billingEnabled'))->toBeFalse();
});

// ═══════════════════════════════════════════════════════════════════════════════
// STATUS GROUPING
// ═══════════════════════════════════════════════════════════════════════════════

test('status groups have correct structure', function () {
    $admin = createSaasAdmin();
    Partner::factory()->create(['plan' => 'tier1']);

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.saas.index', ['locale' => 'en-us']));

    $statusGroups = $response->viewData('statusGroups');

    foreach ($statusGroups as $status => $group) {
        expect($group)->toHaveKeys(['count', 'partners']);
        expect($group['count'])->toBeInt();
        expect($group['partners'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
    }
});

test('legacy partners without stripe_id are never classified as cancelled', function () {
    $admin = createSaasAdmin();

    Partner::factory()->count(2)->create([
        'plan' => 'tier2',
        'stripe_id' => null,
    ]);

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.saas.index', ['locale' => 'en-us']));

    $statusGroups = $response->viewData('statusGroups');

    // Cancelled key should not exist at all, or should have 0 count
    $cancelledCount = $statusGroups[EntitlementService::STATUS_CANCELLED]['count'] ?? 0;
    expect($cancelledCount)->toBe(0);

    // These partners should be grouped under manual (NullBillingProvider default)
    expect($statusGroups)->toHaveKey(EntitlementService::STATUS_MANUAL);
    expect($statusGroups[EntitlementService::STATUS_MANUAL]['count'])->toBe(2);
});

// ═══════════════════════════════════════════════════════════════════════════════
// PARTNER LINKS
// ═══════════════════════════════════════════════════════════════════════════════

test('recent registrations include partner data for linking', function () {
    $admin = createSaasAdmin();
    $partner = Partner::factory()->create(['plan' => 'tier1']);

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.saas.index', ['locale' => 'en-us']));

    $registrations = $response->viewData('recentRegistrations');
    expect($registrations)->toHaveCount(1);

    $first = $registrations->first();
    expect($first)->toHaveKeys(['id', 'name', 'email', 'plan', 'created_at', 'time_ago']);
    expect($first['id'])->toBe($partner->id);
});

// ═══════════════════════════════════════════════════════════════════════════════
// EMPTY STATE
// ═══════════════════════════════════════════════════════════════════════════════

test('dashboard renders correctly with zero partners', function () {
    $admin = createSaasAdmin();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.saas.index', ['locale' => 'en-us']));

    $response->assertStatus(200);
    expect($response->viewData('partnerCount'))->toBe(0);
});

// ═══════════════════════════════════════════════════════════════════════════════
// USAGE OVERVIEW
// ═══════════════════════════════════════════════════════════════════════════════

test('usage overview contains expected resource counts', function () {
    $admin = createSaasAdmin();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.saas.index', ['locale' => 'en-us']));

    $usage = $response->viewData('usageOverview');
    expect($usage)->toHaveKeys([
        'total_cards',
        'total_stamp_cards',
        'total_vouchers',
        'total_staff',
        'total_partners',
    ]);
});
