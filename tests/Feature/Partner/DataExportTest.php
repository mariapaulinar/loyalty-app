<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Tests for the CSV Data Export feature (Step 8).
 * Validates partner-scoped transaction export, member export,
 * format options, and partner isolation.
 */

use App\Models\Admin;
use App\Models\Card;
use App\Models\Club;
use App\Models\Member;
use App\Models\Partner;
use App\Models\Staff;
use App\Models\StampCard;
use App\Models\StampTransaction;
use App\Models\Transaction;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

// ─────────────────────────────────────────────────────────────────────────────
// Helper: create a partner with a club, staff, card, member, and transaction
// ─────────────────────────────────────────────────────────────────────────────

function createPartnerWithTransaction(): array
{
    $partner = Partner::factory()->createOne(['role' => 1]);
    $club = Club::factory()->createOne(['created_by' => $partner->id]);
    $staff = Staff::factory()->createOne([
        'club_id' => $club->id,
        'created_by' => $partner->id,
    ]);
    $card = Card::factory()->createOne([
        'club_id' => $club->id,
        'created_by' => $partner->id,
        'currency' => 'USD',
    ]);
    $member = Member::factory()->createOne(['currency' => 'USD']);
    $member->cards()->attach($card->id);

    $transaction = Transaction::query()->create([
        'id' => (string) Str::uuid(),
        'staff_id' => $staff->id,
        'member_id' => $member->id,
        'card_id' => $card->id,
        'partner_name' => $partner->name,
        'partner_email' => $partner->email,
        'staff_name' => $staff->name,
        'staff_email' => $staff->email,
        'card_title' => ['en' => 'Test Card'],
        'currency' => 'USD',
        'purchase_amount' => 1000,
        'points' => 10,
        'points_used' => 0,
        'event' => 'staff_credited_points_for_purchase',
        'expires_at' => now()->addYear(),
        'meta' => [],
        'created_by' => $partner->id,
    ]);

    return compact('partner', 'club', 'staff', 'card', 'member', 'transaction');
}

function createPartnerWithStampTransaction(): array
{
    $partner = Partner::factory()->createOne(['role' => 1]);
    $club = Club::factory()->createOne(['created_by' => $partner->id]);
    $staff = Staff::factory()->createOne([
        'club_id' => $club->id,
        'created_by' => $partner->id,
    ]);
    $stampCard = StampCard::factory()->createOne([
        'club_id' => $club->id,
        'created_by' => $partner->id,
    ]);
    $member = Member::factory()->createOne(['currency' => 'USD']);

    $stampTransaction = StampTransaction::create([
        'id' => (string) Str::uuid(),
        'stamp_card_id' => $stampCard->id,
        'member_id' => $member->id,
        'staff_id' => $staff->id,
        'stamps' => 1,
        'stamps_before' => 0,
        'stamps_after' => 1,
        'event' => 'stamp_earned',
        'purchase_amount' => 500,
        'currency' => 'USD',
        'meta' => [],
    ]);

    return compact('partner', 'club', 'staff', 'stampCard', 'member', 'stampTransaction');
}

// ═══════════════════════════════════════════════════════════════════════════════
// TRANSACTION LIST & EXPORT
// ═══════════════════════════════════════════════════════════════════════════════

it('renders the transaction list page for a partner', function () {
    $data = createPartnerWithTransaction();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $data['partner'];

    actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/transactions')
        ->assertSuccessful();
});

it('exports transactions as CSV', function () {
    $data = createPartnerWithTransaction();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $data['partner'];

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/export/transactions?format=csv');

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->headers->get('content-disposition'))->toContain('.csv');
});

it('exports transactions as TSV', function () {
    $data = createPartnerWithTransaction();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $data['partner'];

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/export/transactions?format=tsv');

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'text/tab-separated-values; charset=UTF-8');
    expect($response->headers->get('content-disposition'))->toContain('.tsv');
});

it('exports transactions as JSON', function () {
    $data = createPartnerWithTransaction();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $data['partner'];

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/export/transactions?format=json');

    $response->assertSuccessful();
    $json = $response->json();

    expect($json)->toHaveKeys(['meta', 'data']);
    expect($json['meta'])->toHaveKeys(['exported_at', 'title', 'total']);
    expect($json['meta']['total'])->toBeGreaterThanOrEqual(1);
});

it('isolates partner transaction exports — partner B cannot see partner A transactions', function () {
    $dataA = createPartnerWithTransaction();
    $dataB = createPartnerWithTransaction();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartnerB */
    $authPartnerB = $dataB['partner'];

    // Partner B exports JSON to inspect data
    $response = actingAs($authPartnerB, 'partner')
        ->get('/en-us/partner/manage/export/transactions?format=json');

    $response->assertSuccessful();
    $json = $response->json();

    // Partner B should only see their own transaction
    $transactionIds = collect($json['data'])->pluck('id')->toArray();
    expect($transactionIds)->not->toContain($dataA['transaction']->id);
    expect($transactionIds)->toContain($dataB['transaction']->id);
});

it('requires authentication for transaction export', function () {
    $response = $this->get('/en-us/partner/manage/export/transactions?format=csv');

    $response->assertRedirect();
});

// ═══════════════════════════════════════════════════════════════════════════════
// STAMP TRANSACTION LIST & EXPORT
// ═══════════════════════════════════════════════════════════════════════════════

it('renders the stamp transaction list page for a partner', function () {
    $data = createPartnerWithStampTransaction();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $data['partner'];

    actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/stamp-transactions')
        ->assertSuccessful();
});

it('exports stamp transactions as CSV', function () {
    $data = createPartnerWithStampTransaction();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $data['partner'];

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/export/stamp-transactions?format=csv');

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->headers->get('content-disposition'))->toContain('.csv');
});

it('exports stamp transactions as JSON with correct structure', function () {
    $data = createPartnerWithStampTransaction();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $data['partner'];

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/export/stamp-transactions?format=json');

    $response->assertSuccessful();
    $json = $response->json();

    expect($json)->toHaveKeys(['meta', 'data']);
    expect($json['meta']['total'])->toBeGreaterThanOrEqual(1);
});

it('isolates partner stamp transaction exports', function () {
    $dataA = createPartnerWithStampTransaction();
    $dataB = createPartnerWithStampTransaction();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartnerB */
    $authPartnerB = $dataB['partner'];

    $response = actingAs($authPartnerB, 'partner')
        ->get('/en-us/partner/manage/export/stamp-transactions?format=json');

    $response->assertSuccessful();
    $json = $response->json();

    $ids = collect($json['data'])->pluck('id')->toArray();
    expect($ids)->not->toContain($dataA['stampTransaction']->id);
    expect($ids)->toContain($dataB['stampTransaction']->id);
});

// ═══════════════════════════════════════════════════════════════════════════════
// MEMBER EXPORT (existing DD — verify it still works with export)
// ═══════════════════════════════════════════════════════════════════════════════

it('exports members as CSV', function () {
    $data = createPartnerWithTransaction();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $data['partner'];

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/export/members?format=csv');

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('exports members as JSON', function () {
    $data = createPartnerWithTransaction();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $data['partner'];

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/export/members?format=json');

    $response->assertSuccessful();
    $json = $response->json();

    expect($json)->toHaveKeys(['meta', 'data']);
});

// ═══════════════════════════════════════════════════════════════════════════════
// FORMAT FALLBACK
// ═══════════════════════════════════════════════════════════════════════════════

it('defaults to CSV when format is invalid', function () {
    $data = createPartnerWithTransaction();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $data['partner'];

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/export/transactions?format=xml');

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('defaults to CSV when no format is specified', function () {
    $data = createPartnerWithTransaction();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $data['partner'];

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/export/transactions');

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

// ═══════════════════════════════════════════════════════════════════════════════
// EVENT FIDELITY — raw event values preserved in export
// ═══════════════════════════════════════════════════════════════════════════════

it('preserves raw event values in JSON export (no silent drop)', function () {
    $data = createPartnerWithTransaction();
    $partner = $data['partner'];

    // Create a transaction with a non-standard event type
    Transaction::query()->create([
        'id' => (string) Str::uuid(),
        'staff_id' => $data['staff']->id,
        'member_id' => $data['member']->id,
        'card_id' => $data['card']->id,
        'partner_name' => $partner->name,
        'partner_email' => $partner->email,
        'staff_name' => $data['staff']->name,
        'staff_email' => $data['staff']->email,
        'card_title' => ['en' => 'Test Card'],
        'currency' => 'USD',
        'purchase_amount' => 0,
        'points' => 5,
        'points_used' => 0,
        'event' => 'member_redeemed_code_for_points',
        'expires_at' => now()->addYear(),
        'meta' => [],
        'created_by' => $partner->id,
    ]);

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/export/transactions?format=json');

    $response->assertSuccessful();
    $json = $response->json();

    // Export uses event_code (raw slug), not localized event label
    $events = collect($json['data'])->pluck('event_code')->toArray();
    expect($events)->toContain('member_redeemed_code_for_points');
    expect($events)->toContain('staff_credited_points_for_purchase');
});

// ═══════════════════════════════════════════════════════════════════════════════
// ADMIN AGGREGATE EXPORT
// ═══════════════════════════════════════════════════════════════════════════════

it('renders the admin transaction list page', function () {
    $data = createPartnerWithTransaction();
    $admin = createExportAdmin();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authAdmin */
    $authAdmin = $admin;

    actingAs($authAdmin, 'admin')
        ->get('/en-us/admin/manage/transactions')
        ->assertSuccessful();
});

it('exports admin transactions as CSV', function () {
    $data = createPartnerWithTransaction();
    $admin = createExportAdmin();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authAdmin */
    $authAdmin = $admin;

    $response = actingAs($authAdmin, 'admin')
        ->get('/en-us/admin/manage/export/transactions?format=csv');

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('exports admin transactions as JSON with aggregate data', function () {
    // Create two partners with transactions
    $dataA = createPartnerWithTransaction();
    $dataB = createPartnerWithTransaction();
    $admin = createExportAdmin();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authAdmin */
    $authAdmin = $admin;

    $response = actingAs($authAdmin, 'admin')
        ->get('/en-us/admin/manage/export/transactions?format=json');

    $response->assertSuccessful();
    $json = $response->json();

    expect($json['meta']['total'])->toBeGreaterThanOrEqual(2);

    // Admin export should contain transactions from both partners
    $ids = collect($json['data'])->pluck('id')->toArray();
    expect($ids)->toContain($dataA['transaction']->id);
    expect($ids)->toContain($dataB['transaction']->id);

    // With only 2 rows (well under 50k limit), no truncation metadata should be present
    expect($json['meta'])->not->toHaveKey('truncated');
    expect($json['meta'])->not->toHaveKey('total_available');
});

it('renders the admin stamp transaction list page', function () {
    $data = createPartnerWithStampTransaction();
    $admin = createExportAdmin();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authAdmin */
    $authAdmin = $admin;

    actingAs($authAdmin, 'admin')
        ->get('/en-us/admin/manage/stamp-transactions')
        ->assertSuccessful();
});

it('exports admin stamp transactions as JSON with aggregate data', function () {
    $dataA = createPartnerWithStampTransaction();
    $dataB = createPartnerWithStampTransaction();
    $admin = createExportAdmin();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authAdmin */
    $authAdmin = $admin;

    $response = actingAs($authAdmin, 'admin')
        ->get('/en-us/admin/manage/export/stamp-transactions?format=json');

    $response->assertSuccessful();
    $json = $response->json();

    expect($json['meta']['total'])->toBeGreaterThanOrEqual(2);

    $ids = collect($json['data'])->pluck('id')->toArray();
    expect($ids)->toContain($dataA['stampTransaction']->id);
    expect($ids)->toContain($dataB['stampTransaction']->id);

    // With only 2 rows, no truncation metadata
    expect($json['meta'])->not->toHaveKey('truncated');
});

it('requires admin authentication for admin export', function () {
    $response = $this->get('/en-us/admin/manage/export/transactions?format=csv');

    $response->assertRedirect();
});

// ═══════════════════════════════════════════════════════════════════════════════
// TRUNCATION VISIBILITY — admin export surfaces limit metadata
// ═══════════════════════════════════════════════════════════════════════════════

it('all transaction DDs define a 50k export limit', function () {
    // Verify the export limit is explicitly defined across all 4 DDs
    expect(\App\DataDefinitions\Models\Admin\TransactionDataDefinition::$exportLimit)->toBe(50000);
    expect(\App\DataDefinitions\Models\Admin\StampTransactionDataDefinition::$exportLimit)->toBe(50000);
    expect(\App\DataDefinitions\Models\Partner\TransactionDataDefinition::$exportLimit)->toBe(50000);
    expect(\App\DataDefinitions\Models\Partner\StampTransactionDataDefinition::$exportLimit)->toBe(50000);
});

it('admin JSON export omits truncation metadata when under limit', function () {
    // With only 2 rows (well under 50k), truncation metadata must not appear
    $dataA = createPartnerWithTransaction();
    $dataB = createPartnerWithTransaction();
    $admin = createExportAdmin();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authAdmin */
    $authAdmin = $admin;

    $response = actingAs($authAdmin, 'admin')
        ->get('/en-us/admin/manage/export/transactions?format=json');

    $response->assertSuccessful();
    $json = $response->json();

    // These keys only appear when the limit is exceeded
    expect($json['meta'])->not->toHaveKey('truncated');
    expect($json['meta'])->not->toHaveKey('total_available');
    expect($json['meta'])->not->toHaveKey('export_limit');

    // Standard meta keys should always be present
    expect($json['meta'])->toHaveKeys(['exported_at', 'title', 'total']);
});

it('partner JSON export omits truncation metadata when under limit', function () {
    $data = createPartnerWithTransaction();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $data['partner'];

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/export/transactions?format=json');

    $response->assertSuccessful();
    $json = $response->json();

    // Under limit — no truncation keys
    expect($json['meta'])->not->toHaveKey('truncated');
    expect($json['meta'])->not->toHaveKey('total_available');
    expect($json['meta'])->not->toHaveKey('export_limit');

    // Standard meta keys present
    expect($json['meta'])->toHaveKeys(['exported_at', 'title', 'total']);
});

// ═══════════════════════════════════════════════════════════════════════════════
// CAP-PATH — verify truncation metadata and CSV footer when limit is exceeded
// ═══════════════════════════════════════════════════════════════════════════════

it('partner JSON export surfaces truncation metadata when limit is exceeded', function () {
    // Create 2 transactions for the same partner
    $data = createPartnerWithTransaction();
    $partner = $data['partner'];
    $card = $data['card'];
    $staff = $data['staff'];

    Transaction::query()->create([
        'id' => (string) Str::uuid(),
        'staff_id' => $staff->id,
        'member_id' => $data['member']->id,
        'card_id' => $card->id,
        'partner_name' => $partner->name,
        'partner_email' => $partner->email,
        'staff_name' => $staff->name,
        'staff_email' => $staff->email,
        'card_title' => ['en' => 'Test Card'],
        'currency' => 'USD',
        'purchase_amount' => 2000,
        'points' => 20,
        'points_used' => 0,
        'event' => 'staff_credited_points_for_purchase',
        'expires_at' => now()->addYear(),
        'meta' => [],
        'created_by' => $partner->id,
    ]);

    // Temporarily lower the export limit to 1 so 2 rows triggers truncation
    $original = \App\DataDefinitions\Models\Partner\TransactionDataDefinition::$exportLimit;
    \App\DataDefinitions\Models\Partner\TransactionDataDefinition::$exportLimit = 1;

    try {
        /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
        $authPartner = $partner;

        $response = actingAs($authPartner, 'partner')
            ->get('/en-us/partner/manage/export/transactions?format=json');

        $response->assertSuccessful();
        $json = $response->json();

        // Truncation metadata must be present
        expect($json['meta']['truncated'])->toBeTrue();
        expect($json['meta']['total_available'])->toBe(2);
        expect($json['meta']['export_limit'])->toBe(1);

        // Only 1 data row returned (the limit)
        expect(count($json['data']))->toBe(1);
    } finally {
        // Always restore the original limit
        \App\DataDefinitions\Models\Partner\TransactionDataDefinition::$exportLimit = $original;
    }
});

it('partner CSV export appends footer row when limit is exceeded', function () {
    $data = createPartnerWithTransaction();
    $partner = $data['partner'];

    // Add a second transaction
    Transaction::query()->create([
        'id' => (string) Str::uuid(),
        'staff_id' => $data['staff']->id,
        'member_id' => $data['member']->id,
        'card_id' => $data['card']->id,
        'partner_name' => $partner->name,
        'partner_email' => $partner->email,
        'staff_name' => $data['staff']->name,
        'staff_email' => $data['staff']->email,
        'card_title' => ['en' => 'Test Card'],
        'currency' => 'USD',
        'purchase_amount' => 3000,
        'points' => 30,
        'points_used' => 0,
        'event' => 'staff_credited_points_for_purchase',
        'expires_at' => now()->addYear(),
        'meta' => [],
        'created_by' => $partner->id,
    ]);

    $original = \App\DataDefinitions\Models\Partner\TransactionDataDefinition::$exportLimit;
    \App\DataDefinitions\Models\Partner\TransactionDataDefinition::$exportLimit = 1;

    try {
        /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
        $authPartner = $partner;

        $response = actingAs($authPartner, 'partner')
            ->get('/en-us/partner/manage/export/transactions?format=csv');

        $response->assertSuccessful();

        // Capture streamed response content
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        // Footer note must be present in the CSV
        expect($csv)->not->toBeFalse();
        $this->assertStringContainsString('Export limited to', $csv);
        $this->assertStringContainsString('total records available', $csv);
    } finally {
        \App\DataDefinitions\Models\Partner\TransactionDataDefinition::$exportLimit = $original;
    }
});

it('admin JSON export surfaces truncation metadata when limit is exceeded', function () {
    $dataA = createPartnerWithTransaction();
    $dataB = createPartnerWithTransaction();
    $admin = createExportAdmin();

    $original = \App\DataDefinitions\Models\Admin\TransactionDataDefinition::$exportLimit;
    \App\DataDefinitions\Models\Admin\TransactionDataDefinition::$exportLimit = 1;

    try {
        /** @var \Illuminate\Contracts\Auth\Authenticatable $authAdmin */
        $authAdmin = $admin;

        $response = actingAs($authAdmin, 'admin')
            ->get('/en-us/admin/manage/export/transactions?format=json');

        $response->assertSuccessful();
        $json = $response->json();

        // Limit is 1, more rows exist → truncation metadata must be present
        expect($json['meta']['truncated'])->toBeTrue();
        expect($json['meta']['total_available'])->toBeGreaterThan(1);
        expect($json['meta']['export_limit'])->toBe(1);
        expect(count($json['data']))->toBe(1);
    } finally {
        \App\DataDefinitions\Models\Admin\TransactionDataDefinition::$exportLimit = $original;
    }
});

// ═══════════════════════════════════════════════════════════════════════════════
// Helper: create a partner with a voucher redemption
// ═══════════════════════════════════════════════════════════════════════════════

function createPartnerWithVoucherRedemption(?Staff $existingStaff = null): array
{
    $partner = Partner::factory()->createOne(['role' => 1]);
    $club = Club::factory()->createOne(['created_by' => $partner->id]);
    $staff = $existingStaff ?? Staff::factory()->createOne([
        'club_id' => $club->id,
        'created_by' => $partner->id,
    ]);
    $voucher = Voucher::factory()->percentage(20)->createOne([
        'club_id' => $club->id,
        'created_by' => $partner->id,
        'currency' => 'USD',
    ]);
    $member = Member::factory()->createOne(['currency' => 'USD']);

    $redemption = VoucherRedemption::create([
        'voucher_id' => $voucher->id,
        'member_id' => $member->id,
        'staff_id' => $staff->id,
        'status' => VoucherRedemption::STATUS_COMPLETED,
        'discount_amount' => 2000,
        'original_amount' => 10000,
        'final_amount' => 8000,
        'currency' => 'USD',
        'points_awarded' => 0,
        'order_reference' => 'ORD-001',
        'redeemed_at' => now(),
        'meta' => [],
    ]);

    return compact('partner', 'club', 'staff', 'voucher', 'member', 'redemption');
}

// ═══════════════════════════════════════════════════════════════════════════════
// VOUCHER REDEMPTION HISTORY TESTS
// ═══════════════════════════════════════════════════════════════════════════════

it('partner can list voucher redemptions', function () {
    $data = createPartnerWithVoucherRedemption();
    $partner = $data['partner'];

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/voucher-redemptions');

    $response->assertSuccessful();
    $response->assertSee('Voucher Redemption History');
});

it('partner voucher redemption CSV export contains expected columns', function () {
    $data = createPartnerWithVoucherRedemption();
    $partner = $data['partner'];
    $voucher = $data['voucher'];
    $member = $data['member'];

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/export/voucher-redemptions?format=csv');

    $response->assertSuccessful();

    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();

    expect($csv)->not->toBeFalse();

    // Verify key data values are in the CSV
    $this->assertStringContainsString($voucher->code, $csv);
    $this->assertStringContainsString($member->name, $csv);
    $this->assertStringContainsString('completed', $csv);
    $this->assertStringContainsString('ORD-001', $csv);
});

it('partner voucher redemption JSON export contains correct structure', function () {
    $data = createPartnerWithVoucherRedemption();
    $partner = $data['partner'];

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/export/voucher-redemptions?format=json');

    $response->assertSuccessful();
    $json = $response->json();

    expect($json)->toHaveKey('meta');
    expect($json)->toHaveKey('data');
    expect($json['meta']['total'])->toBe(1);
    expect(count($json['data']))->toBe(1);

    $record = $json['data'][0];
    expect($record)->toHaveKey('voucher_code');
    expect($record)->toHaveKey('member_name');
    expect($record)->toHaveKey('status');
    expect($record)->toHaveKey('discount_amount');
    expect($record)->toHaveKey('redeemed_at');
});

it('partner voucher redemption export is scoped to own vouchers only', function () {
    // Partner A with a redemption
    $dataA = createPartnerWithVoucherRedemption();
    // Partner B with a redemption
    $dataB = createPartnerWithVoucherRedemption();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartnerA */
    $authPartnerA = $dataA['partner'];

    $response = actingAs($authPartnerA, 'partner')
        ->get('/en-us/partner/manage/export/voucher-redemptions?format=json');

    $response->assertSuccessful();
    $json = $response->json();

    // Partner A should only see their own redemption
    expect(count($json['data']))->toBe(1);
    expect($json['data'][0]['voucher_code'])->toBe($dataA['voucher']->code);
});

it('admin voucher redemption export shows all partners', function () {
    $dataA = createPartnerWithVoucherRedemption();
    $dataB = createPartnerWithVoucherRedemption();
    $admin = createExportAdmin();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authAdmin */
    $authAdmin = $admin;

    $response = actingAs($authAdmin, 'admin')
        ->get('/en-us/admin/manage/export/voucher-redemptions?format=json');

    $response->assertSuccessful();
    $json = $response->json();

    // Admin sees redemptions from both partners
    expect(count($json['data']))->toBeGreaterThanOrEqual(2);
});

it('voucher redemption export surfaces truncation metadata when limit is exceeded', function () {
    $dataA = createPartnerWithVoucherRedemption();
    $dataB = createPartnerWithVoucherRedemption();
    $admin = createExportAdmin();

    $original = \App\DataDefinitions\Models\Admin\VoucherRedemptionDataDefinition::$exportLimit;
    \App\DataDefinitions\Models\Admin\VoucherRedemptionDataDefinition::$exportLimit = 1;

    try {
        /** @var \Illuminate\Contracts\Auth\Authenticatable $authAdmin */
        $authAdmin = $admin;

        $response = actingAs($authAdmin, 'admin')
            ->get('/en-us/admin/manage/export/voucher-redemptions?format=json');

        $response->assertSuccessful();
        $json = $response->json();

        expect($json['meta']['truncated'])->toBeTrue();
        expect($json['meta']['total_available'])->toBeGreaterThan(1);
        expect($json['meta']['export_limit'])->toBe(1);
        expect(count($json['data']))->toBe(1);
    } finally {
        \App\DataDefinitions\Models\Admin\VoucherRedemptionDataDefinition::$exportLimit = $original;
    }
});

// ═══════════════════════════════════════════════════════════════════════════════
// SEARCH — relation field search via SQL subqueries
// ═══════════════════════════════════════════════════════════════════════════════

it('partner transaction search by member name returns matching rows', function () {
    $data = createPartnerWithTransaction();
    $partner = $data['partner'];
    $memberName = $data['member']->name;

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    // Search with a substring of the member name
    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/transactions?search=' . urlencode(substr($memberName, 0, 5)));

    $response->assertSuccessful();
    $response->assertSee($memberName);
});

it('partner transaction search by member email returns matching rows', function () {
    $data = createPartnerWithTransaction();
    $partner = $data['partner'];
    $memberEmail = $data['member']->email;

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/transactions?search=' . urlencode($memberEmail));

    $response->assertSuccessful();
});

it('partner transaction search by non-existent member returns empty', function () {
    $data = createPartnerWithTransaction();
    $partner = $data['partner'];

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/export/transactions?format=json&search=nonexistent-member-xyz999');

    $response->assertSuccessful();
    $json = $response->json();

    // No rows should match a non-existent member
    expect(count($json['data']))->toBe(0);
});

it('partner stamp transaction search by stamp card name returns matching rows', function () {
    $data = createPartnerWithStampTransaction();
    $partner = $data['partner'];
    $stampCardName = $data['stampCard']->name;

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/stamp-transactions?search=' . urlencode(substr($stampCardName, 0, 5)));

    $response->assertSuccessful();
    $response->assertSee($stampCardName);
});

it('partner stamp transaction search by member name returns matching rows', function () {
    $data = createPartnerWithStampTransaction();
    $partner = $data['partner'];
    $memberName = $data['member']->name;

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/stamp-transactions?search=' . urlencode(substr($memberName, 0, 5)));

    $response->assertSuccessful();
    $response->assertSee($memberName);
});

it('partner voucher redemption search by voucher code returns matching rows', function () {
    $data = createPartnerWithVoucherRedemption();
    $partner = $data['partner'];
    $voucherCode = $data['voucher']->code;

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/voucher-redemptions?search=' . urlencode($voucherCode));

    $response->assertSuccessful();
    $response->assertSee($voucherCode);
});

it('partner voucher redemption search by member name returns matching rows', function () {
    $data = createPartnerWithVoucherRedemption();
    $partner = $data['partner'];
    $memberName = $data['member']->name;

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/voucher-redemptions?search=' . urlencode(substr($memberName, 0, 5)));

    $response->assertSuccessful();
    $response->assertSee($memberName);
});

it('partner voucher redemption search by order reference returns matching rows', function () {
    $data = createPartnerWithVoucherRedemption();
    $partner = $data['partner'];

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/voucher-redemptions?search=ORD-001');

    $response->assertSuccessful();
});

it('partner voucher redemption search by status returns matching rows', function () {
    $data = createPartnerWithVoucherRedemption();
    $partner = $data['partner'];

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/voucher-redemptions?search=completed');

    $response->assertSuccessful();
    $response->assertSee($data['voucher']->code);
});

it('partner transaction search respects partner isolation', function () {
    $dataA = createPartnerWithTransaction();
    $dataB = createPartnerWithTransaction();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartnerB */
    $authPartnerB = $dataB['partner'];

    // Partner B searches for Partner A's member name via JSON export — should return 0 results
    $response = actingAs($authPartnerB, 'partner')
        ->get('/en-us/partner/manage/export/transactions?format=json&search=' . urlencode($dataA['member']->name));

    $response->assertSuccessful();
    $json = $response->json();

    // Partner B should see zero results when searching for Partner A's member
    $memberNames = collect($json['data'])->pluck('member_name')->toArray();
    expect($memberNames)->not->toContain($dataA['member']->name);
});

it('admin transaction search by member name crosses partner boundaries', function () {
    $dataA = createPartnerWithTransaction();
    $admin = createExportAdmin();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authAdmin */
    $authAdmin = $admin;

    // Admin searches for Partner A's member name — should find it
    $response = actingAs($authAdmin, 'admin')
        ->get('/en-us/admin/manage/transactions?search=' . urlencode(substr($dataA['member']->name, 0, 5)));

    $response->assertSuccessful();
    $response->assertSee($dataA['member']->name);
});

// ═══════════════════════════════════════════════════════════════════════════════
// TRANSLATED SEARCH — search_map bridges translated labels to raw DB slugs
// ═══════════════════════════════════════════════════════════════════════════════

it('partner transaction search by translated event label returns matching rows', function () {
    $partner = Partner::factory()->createOne(['role' => 1]);
    $club = Club::factory()->createOne(['created_by' => $partner->id]);
    $staff = Staff::factory()->createOne(['created_by' => $partner->id]);
    $card = Card::factory()->createOne([
        'club_id' => $club->id,
        'created_by' => $partner->id,
        'currency' => 'USD',
    ]);
    $member = Member::factory()->createOne(['currency' => 'USD']);
    $member->cards()->attach($card->id);

    // Use an event slug that has a real translation key
    Transaction::query()->create([
        'id' => (string) Str::uuid(),
        'staff_id' => $staff->id,
        'member_id' => $member->id,
        'card_id' => $card->id,
        'partner_name' => $partner->name,
        'partner_email' => $partner->email,
        'staff_name' => $staff->name,
        'staff_email' => $staff->email,
        'card_title' => ['en' => 'Translated Card'],
        'currency' => 'USD',
        'purchase_amount' => 500,
        'points' => 5,
        'points_used' => 0,
        'event' => 'points_credited',
        'expires_at' => now()->addYear(),
        'meta' => [],
        'created_by' => $partner->id,
    ]);

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    // Search for the translated label "Points Credited" — should match the raw slug
    $translatedLabel = trans('common.points_credited');
    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/export/transactions?format=json&search=' . urlencode($translatedLabel));

    $response->assertSuccessful();
    $json = $response->json();

    expect(count($json['data']))->toBeGreaterThan(0);
});

it('search_map returns empty array for non-matching term', function () {
    $result = \App\DataDefinitions\Models\Partner\TransactionDataDefinition::matchEventSlugs('zzz-nonexistent-xyz');
    expect($result)->toBeArray()->toBeEmpty();
});

it('search_map matches translated event label', function () {
    // "Points Credited" is trans('common.points_credited')
    $result = \App\DataDefinitions\Models\Partner\TransactionDataDefinition::matchEventSlugs('Points Credited');
    expect($result)->toContain('points_credited');
});

it('search_map matches staff_credited_points_for_purchase via translated label', function () {
    // trans('common.staff_credited_points_for_purchase') = 'Points Credited (Purchase)'
    $result = \App\DataDefinitions\Models\Partner\TransactionDataDefinition::matchEventSlugs('Points Credited (Purchase)');
    expect($result)->toContain('staff_credited_points_for_purchase');
});

it('partner transaction search by real event slug returns matching rows', function () {
    // createPartnerWithTransaction() uses event = 'staff_credited_points_for_purchase'
    $data = createPartnerWithTransaction();
    $partner = $data['partner'];

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    // Search by the translated label — should find the transaction via search_map
    $translatedLabel = trans('common.staff_credited_points_for_purchase');
    $response = actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/export/transactions?format=json&search=' . urlencode($translatedLabel));

    $response->assertSuccessful();
    $json = $response->json();

    expect(count($json['data']))->toBeGreaterThan(0);
});

// ═══════════════════════════════════════════════════════════════════════════════
// SUGGESTION LABELS — autocomplete returns multi-field labels
// ═══════════════════════════════════════════════════════════════════════════════

it('transaction suggestions return event · card · member labels', function () {
    $data = createPartnerWithTransaction();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $data['partner'];

    // Search by partial member name to get suggestions
    $response = actingAs($authPartner, 'partner')
        ->getJson('/en-us/partner/manage/transactions/suggest?q=' . urlencode(substr($data['member']->name, 0, 4)));

    $response->assertSuccessful();
    $json = $response->json();

    expect($json['data'])->not->toBeEmpty();

    // Each suggestion label should contain the separator · indicating multi-field composition
    $label = $json['data'][0]['label'] ?? '';
    expect($label)->toContain('·');
    // Label should contain the member name we searched for
    expect($label)->toContain($data['member']->name);
});

it('stamp transaction suggestions return event · card · member labels', function () {
    $data = createPartnerWithStampTransaction();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $data['partner'];

    $response = actingAs($authPartner, 'partner')
        ->getJson('/en-us/partner/manage/stamp-transactions/suggest?q=' . urlencode(substr($data['member']->name, 0, 4)));

    $response->assertSuccessful();
    $json = $response->json();

    expect($json['data'])->not->toBeEmpty();

    $label = $json['data'][0]['label'] ?? '';
    expect($label)->toContain('·');
    expect($label)->toContain($data['member']->name);
});

it('voucher redemption suggestions return code · name · member labels', function () {
    $data = createPartnerWithVoucherRedemption();

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $data['partner'];

    $response = actingAs($authPartner, 'partner')
        ->getJson('/en-us/partner/manage/voucher-redemptions/suggest?q=' . urlencode(substr($data['member']->name, 0, 4)));

    $response->assertSuccessful();
    $json = $response->json();

    expect($json['data'])->not->toBeEmpty();

    $label = $json['data'][0]['label'] ?? '';
    expect($label)->toContain('·');
    expect($label)->toContain($data['member']->name);
});

// ═══════════════════════════════════════════════════════════════════════════════
// Helper: create admin user
// ═══════════════════════════════════════════════════════════════════════════════

function createExportAdmin(array $attributes = []): Admin
{
    return Admin::create(array_merge([
        'id' => Str::uuid()->toString(),
        'name' => 'Export Admin',
        'email' => 'export-admin' . Str::random(5) . '@test.com',
        'password' => bcrypt('password'),
        'role' => 1,
        'locale' => 'en_US',
        'time_zone' => 'UTC',
        'currency' => 'USD',
        'is_active' => true,
    ], $attributes));
}
