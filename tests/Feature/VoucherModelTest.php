<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

use App\Models\Club;
use App\Models\Member;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('voucher can be created with required fields', function () {
    $club = Club::factory()->create();

    $voucher = Voucher::create([
        'club_id' => $club->id,
        'code' => 'TEST20',
        'name' => 'Test Voucher',
        'type' => 'percentage',
        'value' => 2000, // Minor units: 2000 = 20%
    ]);

    expect($voucher)->toBeInstanceOf(Voucher::class)
        ->and($voucher->code)->toBe('TEST20')
        ->and($voucher->type)->toBe('percentage')
        ->and($voucher->value)->toBe(2000); // Minor units: 2000 = 20%
});

test('voucher code is automatically uppercased', function () {
    $voucher = Voucher::factory()->create([
        'code' => 'test20',
    ]);

    expect($voucher->code)->toBe('TEST20');
});

test('voucher generates unique identifier on creation', function () {
    $voucher = Voucher::factory()->create();

    expect($voucher->unique_identifier)->not->toBeNull()
        ->and(strlen($voucher->unique_identifier))->toBeGreaterThan(0);
});

test('percentage voucher calculates discount correctly', function () {
    $voucher = Voucher::factory()->percentage(20)->create();

    $result = $voucher->calculateDiscount(10000); // $100.00

    expect($result['discount_amount'])->toBe(2000) // $20.00
        ->and($result['original_amount'])->toBe(10000)
        ->and($result['final_amount'])->toBe(8000)
        ->and($result['capped'])->toBeFalse();
});

test('percentage voucher respects maximum discount cap', function () {
    $voucher = Voucher::factory()->create([
        'type' => 'percentage',
        'value' => 5000, // Minor units: 5000 = 50%
        'max_discount_amount' => 2000, // $20.00 cap in cents
    ]);

    $result = $voucher->calculateDiscount(10000); // $100.00 order

    expect($result['discount_amount'])->toBe(2000) // Capped at $20.00
        ->and($result['capped'])->toBeTrue();
});

test('fixed amount voucher calculates discount correctly', function () {
    $voucher = Voucher::factory()->fixedAmount(1000)->create(); // $10.00 off

    $result = $voucher->calculateDiscount(5000); // $50.00 order

    expect($result['discount_amount'])->toBe(1000)
        ->and($result['final_amount'])->toBe(4000);
});

test('fixed amount voucher never exceeds order amount', function () {
    $voucher = Voucher::factory()->fixedAmount(5000)->create(); // $50.00 off

    $result = $voucher->calculateDiscount(3000); // $30.00 order

    expect($result['discount_amount'])->toBe(3000) // Limited to order amount
        ->and($result['final_amount'])->toBe(0);
});

test('voucher code can be generated with random characters', function () {
    $code = Voucher::generateCode(8);

    expect($code)->toHaveLength(8)
        ->and($code)->toMatch('/^[A-Z0-9]+$/');
});

test('voucher code can be generated with prefix', function () {
    $code = Voucher::generateCode(8, 'SUMMER');

    expect($code)->toStartWith('SUMMER-')
        ->and(strlen($code))->toBe(15); // SUMMER- (7) + 8 chars
});

test('voucher tracks if it is expired', function () {
    $expiredVoucher = Voucher::factory()->expired()->create();
    $activeVoucher = Voucher::factory()->create([
        'valid_until' => now()->addDays(30),
    ]);

    expect($expiredVoucher->is_expired)->toBeTrue()
        ->and($activeVoucher->is_expired)->toBeFalse();
});

test('voucher tracks if it is not yet valid', function () {
    $futureVoucher = Voucher::factory()->notYetValid()->create();
    $activeVoucher = Voucher::factory()->create([
        'valid_from' => now()->subDays(1),
    ]);

    expect($futureVoucher->is_not_yet_valid)->toBeTrue()
        ->and($activeVoucher->is_not_yet_valid)->toBeFalse();
});

test('voucher tracks if it is exhausted', function () {
    $exhaustedVoucher = Voucher::factory()->exhausted()->create();
    $activeVoucher = Voucher::factory()->withUsageLimit(100)->create();

    expect($exhaustedVoucher->is_exhausted)->toBeTrue()
        ->and($activeVoucher->is_exhausted)->toBeFalse();
});

test('voucher calculates remaining uses correctly', function () {
    $voucher = Voucher::factory()->create([
        'max_uses_total' => 100,
        'times_used' => 25,
    ]);

    expect($voucher->remaining_uses)->toBe(75);
});

test('voucher with unlimited uses returns null for remaining uses', function () {
    $voucher = Voucher::factory()->create([
        'max_uses_total' => null,
    ]);

    expect($voucher->remaining_uses)->toBeNull();
});

test('voucher tracks member usage count', function () {
    $voucher = Voucher::factory()->create();
    $member = Member::factory()->create();

    // Create redemptions
    \App\Models\VoucherRedemption::factory()->count(3)->create([
        'voucher_id' => $voucher->id,
        'member_id' => $member->id,
        'status' => 'completed',
    ]);

    expect($voucher->getMemberUsageCount($member))->toBe(3);
});

test('voucher ignores voided redemptions in usage count', function () {
    $voucher = Voucher::factory()->create();
    $member = Member::factory()->create();

    \App\Models\VoucherRedemption::factory()->count(2)->create([
        'voucher_id' => $voucher->id,
        'member_id' => $member->id,
        'status' => 'completed',
    ]);

    \App\Models\VoucherRedemption::factory()->voided()->create([
        'voucher_id' => $voucher->id,
        'member_id' => $member->id,
    ]);

    expect($voucher->getMemberUsageCount($member))->toBe(2);
});

test('voucher calculates remaining uses for member', function () {
    $voucher = Voucher::factory()->create([
        'max_uses_per_member' => 5,
    ]);
    $member = Member::factory()->create();

    \App\Models\VoucherRedemption::factory()->count(2)->create([
        'voucher_id' => $voucher->id,
        'member_id' => $member->id,
        'status' => 'completed',
    ]);

    expect($voucher->getRemainingUsesForMember($member))->toBe(3);
});

test('voucher checks if member can use it', function () {
    $voucher = Voucher::factory()->create([
        'is_active' => true,
        'max_uses_per_member' => 1,
    ]);
    $member = Member::factory()->create();

    expect($voucher->canBeUsedBy($member))->toBeTrue();

    // After one use
    \App\Models\VoucherRedemption::factory()->create([
        'voucher_id' => $voucher->id,
        'member_id' => $member->id,
        'status' => 'completed',
    ]);

    expect($voucher->canBeUsedBy($member))->toBeFalse();
});

test('voucher formatted value returns correct string', function () {
    $percentageVoucher = Voucher::factory()->percentage(20)->create();
    $fixedVoucher = Voucher::factory()->fixedAmount(1000)->create();
    $pointsVoucher = Voucher::factory()->bonusPoints(100)->create();
    $freeShippingVoucher = Voucher::factory()->freeShipping()->create();

    expect($percentageVoucher->formatted_value)->toBe('20%')
        ->and($fixedVoucher->formatted_value)->toBe('$10.00')
        ->and($pointsVoucher->formatted_value)->toBe(trans('common.amount_points', ['points' => '100']))
        ->and($freeShippingVoucher->formatted_value)->toBe(trans('common.free_shipping'));
});

test('voucher scopes work correctly', function () {
    $activeCountBefore = Voucher::active()->count();
    $publicCountBefore = Voucher::public()->count();

    $activeVoucher = Voucher::factory()->create(['is_active' => true]);
    $inactiveVoucher = Voucher::factory()->inactive()->create();
    $publicVoucher = Voucher::factory()->public()->create();

    // activeVoucher and publicVoucher are both active (is_active = true)
    expect(Voucher::active()->count())->toBe($activeCountBefore + 2)
        ->and(Voucher::public()->count())->toBe($publicCountBefore + 1);
});
