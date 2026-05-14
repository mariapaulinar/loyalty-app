<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

use App\Events\VoucherExhausted;
use App\Events\VoucherRedeemed;
use App\Events\VoucherVoided;
use App\Models\Club;
use App\Models\Member;
use App\Models\Staff;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(VoucherService::class);
});

// ═════════════════════════════════════════════════════════════════════════
// COMPLETE REDEMPTION FLOW TESTS
// ═════════════════════════════════════════════════════════════════════════

test('complete redemption workflow works end-to-end', function () {
    Event::fake([VoucherRedeemed::class]);

    $club = Club::factory()->create();
    $member = Member::factory()->create();
    $voucher = Voucher::factory()->create([
        'club_id' => $club->id,
        'code' => 'COMPLETE20',
        'type' => 'percentage',
        'value' => 2000, // Minor units: 2000 = 20%
    ]);

    // Step 1: Validate
    $validation = $this->service->validate('COMPLETE20', $member, $club->id, 10000);
    expect($validation['valid'])->toBeTrue();

    // Step 2: Redeem
    $result = $this->service->redeem(
        voucher: $voucher,
        member: $member,
        orderAmount: 10000,
        orderReference: 'ORD-12345'
    );

    expect($result['success'])->toBeTrue()
        ->and($result['discount_amount'])->toBe(2000);

    // Step 3: Verify database state
    $this->assertDatabaseHas('voucher_redemptions', [
        'voucher_id' => $voucher->id,
        'member_id' => $member->id,
        'order_reference' => 'ORD-12345',
        'discount_amount' => 2000,
        'original_amount' => 10000,
        'final_amount' => 8000,
        'status' => 'completed',
    ]);

    // Step 4: Verify events fired
    Event::assertDispatched(VoucherRedeemed::class);
});

test('redemption dispatches exhausted event when limit reached', function () {
    Event::fake([VoucherRedeemed::class, VoucherExhausted::class]);

    $club = Club::factory()->create();
    $member = Member::factory()->create();
    $voucher = Voucher::factory()->create([
        'club_id' => $club->id,
        'max_uses_total' => 1,
        'type' => 'percentage',
        'value' => 1000, // Minor units: 1000 = 10%
    ]);

    $this->service->redeem($voucher, $member, 10000);

    Event::assertDispatched(VoucherRedeemed::class);
    Event::assertDispatched(VoucherExhausted::class);
});

test('voiding redemption dispatches event', function () {
    Event::fake([VoucherVoided::class]);

    $redemption = VoucherRedemption::factory()->create();
    $staff = Staff::factory()->create();

    $this->service->voidRedemption($redemption, 'Test reason', $staff);

    Event::assertDispatched(VoucherVoided::class, function ($event) use ($redemption) {
        return $event->redemption->id === $redemption->id;
    });
});

// ═════════════════════════════════════════════════════════════════════════
// BONUS POINTS INTEGRATION TESTS
// ═════════════════════════════════════════════════════════════════════════

test('bonus points voucher creates points transaction', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();

    // Create loyalty card for member
    $card = \App\Models\Card::factory()->create([
        'club_id' => $club->id,
    ]);
    $member->cards()->attach($card->id);

    $voucher = Voucher::factory()->bonusPoints(100)->create([
        'club_id' => $club->id,
    ]);

    $result = $this->service->redeem($voucher, $member, 10000);

    expect($result['success'])->toBeTrue()
        ->and($result['points_awarded'])->toBe(100);

    // Verify points transaction created
    $this->assertDatabaseHas('transactions', [
        'card_id' => $card->id,
        'member_id' => $member->id,
        'event' => 'voucher_bonus',
        'points' => 100,
    ]);

    // Verify card balance (sum of all transactions)
    $balance = (int) \App\Models\Transaction::where('card_id', $card->id)
        ->where('member_id', $member->id)
        ->sum('points');
    expect($balance)->toBe(100);
});

test('voiding bonus points voucher reverses points', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();

    $card = \App\Models\Card::factory()->create([
        'club_id' => $club->id,
    ]);
    $member->cards()->attach($card->id);

    // Create initial points transaction
    $transaction = \App\Models\Transaction::create([
        'card_id' => $card->id,
        'member_id' => $member->id,
        'event' => 'voucher_bonus',
        'points' => 100,
    ]);

    $redemption = VoucherRedemption::factory()->withPoints(100)->create([
        'member_id' => $member->id,
        'transaction_id' => $transaction->id,
        'voucher_id' => Voucher::factory()->bonusPoints(100)->create(['club_id' => $club->id]),
    ]);

    // Get balance before void
    $balanceBefore = (int) \App\Models\Transaction::where('card_id', $card->id)
        ->where('member_id', $member->id)
        ->sum('points');
    expect($balanceBefore)->toBe(100);

    $this->service->voidRedemption($redemption, 'Test void');

    // Verify reversal transaction created
    $this->assertDatabaseHas('transactions', [
        'card_id' => $card->id,
        'event' => 'voucher_voided',
        'points' => -100,
    ]);

    // Verify points deducted (sum of all transactions)
    $balanceAfter = (int) \App\Models\Transaction::where('card_id', $card->id)
        ->where('member_id', $member->id)
        ->sum('points');
    expect($balanceAfter)->toBe(0);
});

// ═════════════════════════════════════════════════════════════════════════
// EDGE CASES AND ERROR HANDLING
// ═════════════════════════════════════════════════════════════════════════

test('redemption fails gracefully when voucher becomes invalid', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();
    $voucher = Voucher::factory()->create([
        'club_id' => $club->id,
        'is_active' => false, // Inactive
    ]);

    $result = $this->service->redeem($voucher, $member, 10000);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->not->toBeNull();
});

test('voucher redemption creates audit trail', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();
    $staff = Staff::factory()->create();
    $voucher = Voucher::factory()->create([
        'club_id' => $club->id,
        'type' => 'percentage',
        'value' => 2000, // Minor units: 2000 = 20%
    ]);

    $result = $this->service->redeem(
        voucher: $voucher,
        member: $member,
        orderAmount: 10000,
        orderReference: 'ORD-12345',
        staff: $staff
    );

    $redemption = $result['redemption'];

    expect($redemption->voucher_id)->toBe($voucher->id)
        ->and($redemption->member_id)->toBe($member->id)
        ->and($redemption->staff_id)->toBe($staff->id)
        ->and($redemption->order_reference)->toBe('ORD-12345')
        ->and($redemption->status)->toBe('completed')
        ->and($redemption->redeemed_at)->not->toBeNull();
});

test('voiding updates redemption with complete information', function () {
    $redemption = VoucherRedemption::factory()->create();
    $staff = Staff::factory()->create();
    $reason = 'Customer requested refund';

    $this->service->voidRedemption($redemption, $reason, $staff);

    $redemption->refresh();

    expect($redemption->status)->toBe('voided')
        ->and($redemption->voided_at)->not->toBeNull()
        ->and($redemption->voided_by)->toBe($staff->id)
        ->and($redemption->void_reason)->toBe($reason);
});

// ═════════════════════════════════════════════════════════════════════════
// MEMBER TARGETING TESTS
// ═════════════════════════════════════════════════════════════════════════

test('member-specific voucher only works for target member', function () {
    $club = Club::factory()->create();
    $targetMember = Member::factory()->create();
    $otherMember = Member::factory()->create();

    $voucher = Voucher::factory()->forMember($targetMember)->create([
        'club_id' => $club->id,
        'code' => 'PERSONAL',
        'type' => 'percentage',
        'value' => 2000, // Minor units: 2000 = 20%
    ]);

    // Target member can use it
    $result1 = $this->service->validate('PERSONAL', $targetMember, $club->id);
    expect($result1['valid'])->toBeTrue();

    // Other member cannot
    $result2 = $this->service->validate('PERSONAL', $otherMember, $club->id);
    expect($result2['valid'])->toBeFalse()
        ->and($result2['error_code'])->toBe('not_for_member');
});

test('new members only voucher works correctly', function () {
    $club = Club::factory()->create();

    $newMember = Member::factory()->create([
        'created_at' => now()->subDays(5),
    ]);

    $oldMember = Member::factory()->create([
        'created_at' => now()->subDays(60),
    ]);

    $voucher = Voucher::factory()->newMembersOnly(30)->create([
        'club_id' => $club->id,
        'code' => 'WELCOME',
    ]);

    // New member can use it
    $result1 = $this->service->validate('WELCOME', $newMember, $club->id);
    expect($result1['valid'])->toBeTrue();

    // Old member cannot
    $result2 = $this->service->validate('WELCOME', $oldMember, $club->id);
    expect($result2['valid'])->toBeFalse()
        ->and($result2['error_code'])->toBe('new_members_only');
});

test('first order only voucher works correctly', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();

    $voucher = Voucher::factory()->firstOrderOnly()->create([
        'club_id' => $club->id,
        'code' => 'FIRST',
        'type' => 'percentage',
        'value' => 1500, // Minor units: 1500 = 15%
    ]);

    // First order succeeds
    $result1 = $this->service->validate('FIRST', $member, $club->id);
    expect($result1['valid'])->toBeTrue();

    // Create a redemption
    VoucherRedemption::factory()->create([
        'voucher_id' => Voucher::factory()->create(['club_id' => $club->id]),
        'member_id' => $member->id,
    ]);

    // Second order fails
    $result2 = $this->service->validate('FIRST', $member, $club->id);
    expect($result2['valid'])->toBeFalse()
        ->and($result2['error_code'])->toBe('first_order_only');
});

// ═════════════════════════════════════════════════════════════════════════
// MULTIPLE VOUCHERS TESTS
// ═════════════════════════════════════════════════════════════════════════

test('member can use different vouchers', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();

    $voucher1 = Voucher::factory()->create([
        'club_id' => $club->id,
        'code' => 'CODE1',
        'type' => 'percentage',
        'value' => 1000, // Minor units: 1000 = 10%
    ]);

    $voucher2 = Voucher::factory()->create([
        'club_id' => $club->id,
        'code' => 'CODE2',
        'type' => 'percentage',
        'value' => 1500, // Minor units: 1500 = 15%
    ]);

    $result1 = $this->service->redeem($voucher1, $member, 10000);
    $result2 = $this->service->redeem($voucher2, $member, 10000);

    expect($result1['success'])->toBeTrue()
        ->and($result2['success'])->toBeTrue();
});

test('single use voucher prevents multiple redemptions by same member', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create();

    $voucher = Voucher::factory()->singleUse()->create([
        'club_id' => $club->id,
        'code' => 'SINGLE',
        'type' => 'percentage',
        'value' => 2000, // Minor units: 2000 = 20%
    ]);

    // First use succeeds
    $result1 = $this->service->redeem($voucher, $member, 10000);
    expect($result1['success'])->toBeTrue();

    // Second use fails
    $result2 = $this->service->redeem($voucher, $member, 10000);
    expect($result2['success'])->toBeFalse();
});
