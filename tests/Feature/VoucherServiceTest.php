<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

use App\Models\Club;
use App\Models\Member;
use App\Models\Staff;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(VoucherService::class);
});

// ═════════════════════════════════════════════════════════════════════════
// VALIDATION TESTS
// ═════════════════════════════════════════════════════════════════════════

test('service validates voucher code successfully', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();
    $voucher = Voucher::factory()->create([
        'club_id' => $club->id,
        'code' => 'VALID20',
        'type' => 'percentage',
        'value' => 2000, // Minor units: 2000 = 20%
    ]);

    $result = $this->service->validate('VALID20', $member, $club->id, 10000);

    expect($result['valid'])->toBeTrue()
        ->and($result['voucher']->id)->toBe($voucher->id)
        ->and($result['discount_amount'])->toBe(2000); // 20% of $100
});

test('service rejects invalid voucher code', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();

    $result = $this->service->validate('INVALID', $member, $club->id);

    expect($result['valid'])->toBeFalse()
        ->and($result['error_code'])->toBe('invalid_code');
});

test('service rejects inactive voucher', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();
    Voucher::factory()->inactive()->create([
        'club_id' => $club->id,
        'code' => 'INACTIVE',
    ]);

    $result = $this->service->validate('INACTIVE', $member, $club->id);

    expect($result['valid'])->toBeFalse()
        ->and($result['error_code'])->toBe('inactive');
});

test('service rejects expired voucher', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();
    Voucher::factory()->expired()->create([
        'club_id' => $club->id,
        'code' => 'EXPIRED',
    ]);

    $result = $this->service->validate('EXPIRED', $member, $club->id);

    expect($result['valid'])->toBeFalse()
        ->and($result['error_code'])->toBe('expired');
});

test('service rejects voucher not yet valid', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();
    Voucher::factory()->notYetValid()->create([
        'club_id' => $club->id,
        'code' => 'FUTURE',
    ]);

    $result = $this->service->validate('FUTURE', $member, $club->id);

    expect($result['valid'])->toBeFalse()
        ->and($result['error_code'])->toBe('not_yet_valid');
});

test('service rejects exhausted voucher', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();
    Voucher::factory()->exhausted()->create([
        'club_id' => $club->id,
        'code' => 'EXHAUSTED',
    ]);

    $result = $this->service->validate('EXHAUSTED', $member, $club->id);

    expect($result['valid'])->toBeFalse()
        ->and($result['error_code'])->toBe('exhausted');
});

test('service rejects when member limit reached', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();
    $voucher = Voucher::factory()->create([
        'club_id' => $club->id,
        'code' => 'LIMITED',
        'max_uses_per_member' => 2,
    ]);

    // Create 2 redemptions
    VoucherRedemption::factory()->count(2)->create([
        'voucher_id' => $voucher->id,
        'member_id' => $member->id,
        'status' => 'completed',
    ]);

    $result = $this->service->validate('LIMITED', $member, $club->id);

    expect($result['valid'])->toBeFalse()
        ->and($result['error_code'])->toBe('member_limit_reached');
});

test('service rejects when minimum purchase not met', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();
    Voucher::factory()->withMinimumPurchase(5000)->create([
        'club_id' => $club->id,
        'code' => 'MINIMUM50',
    ]);

    $result = $this->service->validate('MINIMUM50', $member, $club->id, 3000); // $30 order

    expect($result['valid'])->toBeFalse()
        ->and($result['error_code'])->toBe('minimum_not_met');
});

test('service accepts when minimum purchase is met', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();
    Voucher::factory()->withMinimumPurchase(5000)->create([
        'club_id' => $club->id,
        'code' => 'MINIMUM50',
        'type' => 'percentage',
        'value' => 1000, // Minor units: 1000 = 10%
    ]);

    $result = $this->service->validate('MINIMUM50', $member, $club->id, 6000); // $60 order

    expect($result['valid'])->toBeTrue();
});

// ═════════════════════════════════════════════════════════════════════════
// REDEMPTION TESTS
// ═════════════════════════════════════════════════════════════════════════

test('service redeems voucher successfully', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();
    $voucher = Voucher::factory()->create([
        'club_id' => $club->id,
        'type' => 'percentage',
        'value' => 2000, // Minor units: 2000 = 20%
    ]);

    $result = $this->service->redeem(
        voucher: $voucher,
        member: $member,
        orderAmount: 10000, // $100.00
        orderReference: 'ORD-12345'
    );

    expect($result['success'])->toBeTrue()
        ->and($result['discount_amount'])->toBe(2000)
        ->and($result['redemption'])->toBeInstanceOf(VoucherRedemption::class);

    // Verify database
    $this->assertDatabaseHas('voucher_redemptions', [
        'voucher_id' => $voucher->id,
        'member_id' => $member->id,
        'discount_amount' => 2000,
        'status' => 'completed',
    ]);

    // Verify counters updated
    expect($voucher->fresh()->times_used)->toBe(1)
        ->and($voucher->fresh()->total_discount_given)->toBe(2000)
        ->and($voucher->fresh()->unique_members_used)->toBe(1);
});

test('service prevents race condition in redemption', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();
    $voucher = Voucher::factory()->create([
        'club_id' => $club->id,
        'max_uses_total' => 1,
        'type' => 'percentage',
        'value' => 1000, // Minor units: 1000 = 10%
    ]);

    // First redemption succeeds
    $result1 = $this->service->redeem($voucher, $member, 10000);
    expect($result1['success'])->toBeTrue();

    // Second redemption fails (voucher exhausted)
    $result2 = $this->service->redeem($voucher, Member::factory()->create(), 10000);
    expect($result2['success'])->toBeFalse();
});

test('service tracks unique members correctly', function () {
    $club = Club::factory()->create();
    $member1 = Member::factory()->create();
    $member2 = Member::factory()->create();
    $voucher = Voucher::factory()->create([
        'club_id' => $club->id,
        'type' => 'fixed_amount',
        'value' => 1000,
    ]);

    // Member 1 uses twice
    $this->service->redeem($voucher, $member1, 10000);
    $this->service->redeem($voucher, $member1, 10000);

    // Member 2 uses once
    $this->service->redeem($voucher, $member2, 10000);

    expect($voucher->fresh()->times_used)->toBe(3)
        ->and($voucher->fresh()->unique_members_used)->toBe(2);
});

// ═════════════════════════════════════════════════════════════════════════
// VOID REDEMPTION TESTS
// ═════════════════════════════════════════════════════════════════════════

test('service voids redemption successfully', function () {
    $voucher = Voucher::factory()->used(1, 2000)->create();
    $redemption = VoucherRedemption::factory()->create([
        'voucher_id' => $voucher->id,
        'discount_amount' => 2000,
    ]);
    $staff = Staff::factory()->create();

    $this->service->voidRedemption(
        redemption: $redemption,
        reason: 'Customer refund',
        staff: $staff
    );

    expect($redemption->fresh()->is_voided)->toBeTrue()
        ->and($redemption->fresh()->void_reason)->toBe('Customer refund')
        ->and($voucher->fresh()->times_used)->toBe(0)
        ->and($voucher->fresh()->total_discount_given)->toBe(0);
});

test('service prevents double void', function () {
    $redemption = VoucherRedemption::factory()->voided()->create();

    expect(fn () => $this->service->voidRedemption($redemption, 'Test'))
        ->toThrow(Exception::class, 'already been voided');
});

// ═════════════════════════════════════════════════════════════════════════
// CODE GENERATION TESTS
// ═════════════════════════════════════════════════════════════════════════

test('service generates unique code', function () {
    $club = Club::factory()->create();

    $code = $this->service->generateUniqueCode($club->id, 8);

    expect($code)->toHaveLength(8)
        ->and($code)->toMatch('/^[A-Z0-9]+$/');
});

test('service generates unique code with prefix', function () {
    $club = Club::factory()->create();

    $code = $this->service->generateUniqueCode($club->id, 8, 'SUMMER');

    expect($code)->toStartWith('SUMMER-');
});

test('service avoids code collisions', function () {
    $club = Club::factory()->create();

    $code1 = $this->service->generateUniqueCode($club->id, 8);
    $code2 = $this->service->generateUniqueCode($club->id, 8);

    expect($code1)->not->toBe($code2);
});

test('service generates batch of vouchers', function () {
    $club = Club::factory()->create();

    $vouchers = $this->service->generateBatch(
        club: $club,
        voucherConfig: [
            'name' => 'Summer Batch',
            'type' => 'percentage',
            'value' => 2000, // Minor units: 2000 = 20%
        ],
        quantity: 10,
        codePrefix: 'SUMMER',
        codeLength: 8
    );

    expect($vouchers)->toHaveCount(10)
        ->each->toBeInstanceOf(Voucher::class);

    // Verify all codes are unique
    $codes = $vouchers->pluck('code')->toArray();
    expect($codes)->toHaveCount(count(array_unique($codes)));
});

// ═════════════════════════════════════════════════════════════════════════
// QUERY TESTS
// ═════════════════════════════════════════════════════════════════════════

test('service gets available vouchers for member', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();

    $publicVoucher = Voucher::factory()->public()->create(['club_id' => $club->id]);
    $privateVoucher = Voucher::factory()->create(['club_id' => $club->id]);
    $expiredVoucher = Voucher::factory()->expired()->public()->create(['club_id' => $club->id]);

    $available = $this->service->getAvailableVouchersForMember($member, $club);

    expect($available)->toHaveCount(1)
        ->and($available->first()->id)->toBe($publicVoucher->id);
});

test('service gets member redemption history', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();
    $voucher = Voucher::factory()->create(['club_id' => $club->id]);

    VoucherRedemption::factory()->count(3)->create([
        'voucher_id' => $voucher->id,
        'member_id' => $member->id,
    ]);

    $history = $this->service->getMemberRedemptionHistory($member, $club);

    expect($history)->toHaveCount(3);
});

test('service gets voucher statistics', function () {
    $voucher = Voucher::factory()->create([
        'times_used' => 5,
        'total_discount_given' => 10000,
        'unique_members_used' => 3,
    ]);

    VoucherRedemption::factory()->count(5)->create([
        'voucher_id' => $voucher->id,
        'original_amount' => 15000,
    ]);

    $stats = $this->service->getVoucherStatistics($voucher);

    expect($stats)->toHaveKeys([
        'total_redemptions',
        'total_discount_given',
        'unique_members',
        'average_order_value',
        'remaining_uses',
        'is_exhausted',
        'days_until_expiry',
    ]);
});

test('service finds best auto-apply voucher', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();

    $voucher10 = Voucher::factory()->autoApply()->create([
        'club_id' => $club->id,
        'type' => 'percentage',
        'value' => 1000, // Minor units: 1000 = 10%
    ]);

    $voucher20 = Voucher::factory()->autoApply()->create([
        'club_id' => $club->id,
        'type' => 'percentage',
        'value' => 2000, // Minor units: 2000 = 20%
    ]);

    $bestVoucher = $this->service->checkAutoApplyVouchers($member, $club->id, 10000);

    expect($bestVoucher->id)->toBe($voucher20->id); // 20% is better than 10%
});
