<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Tests for CreditPointsOnCompletion listener.
 * Verifies staff attribution is inherited from the source stamp transaction
 * into the generated point transaction and bridge stamp transaction.
 */

use App\Events\StampCardCompleted;
use App\Models\Card;
use App\Models\Club;
use App\Models\Member;
use App\Models\Partner;
use App\Models\Staff;
use App\Models\StampCard;
use App\Models\StampTransaction;
use App\Models\Transaction;
use Illuminate\Support\Str;

// ═══════════════════════════════════════════════════════════════════════════════
// HELPER: create the full setup for a stamp card with point reward on completion
// ═══════════════════════════════════════════════════════════════════════════════

function createStampCompletionSetup(?Staff $staff = null): array
{
    $partner = Partner::factory()->createOne(['role' => 1]);
    $club = Club::factory()->createOne(['created_by' => $partner->id]);

    if ($staff === null) {
        $staff = Staff::factory()->createOne([
            'club_id' => $club->id,
            'created_by' => $partner->id,
        ]);
    }

    // Loyalty card that receives the reward points
    $loyaltyCard = Card::factory()->createOne([
        'club_id' => $club->id,
        'created_by' => $partner->id,
        'currency' => 'USD',
        'points_per_currency' => 1,
        'points_expiration_months' => 12,
    ]);

    // Stamp card that awards points on completion
    $stampCard = StampCard::factory()->createOne([
        'club_id' => $club->id,
        'created_by' => $partner->id,
        'stamps_required' => 5,
        'reward_points' => 100,
        'reward_card_id' => $loyaltyCard->id,
    ]);

    $member = Member::factory()->createOne(['currency' => 'USD']);

    return compact('partner', 'club', 'staff', 'loyaltyCard', 'stampCard', 'member');
}

// ═══════════════════════════════════════════════════════════════════════════════
// TESTS
// ═══════════════════════════════════════════════════════════════════════════════

it('carries staff attribution into the point transaction when completion is staff-driven', function () {
    $data = createStampCompletionSetup();
    $staff = $data['staff'];
    $member = $data['member'];
    $stampCard = $data['stampCard'];

    // Create the source stamp transaction with staff attribution
    $sourceTransaction = StampTransaction::create([
        'id' => (string) Str::uuid(),
        'stamp_card_id' => $stampCard->id,
        'member_id' => $member->id,
        'staff_id' => $staff->id,
        'stamps' => 5,
        'stamps_before' => 0,
        'stamps_after' => 5,
        'event' => StampTransaction::EVENT_STAMP_EARNED,
    ]);

    // Fire the completion event (simulating what StampService::addStamp does)
    event(new StampCardCompleted(
        card: $stampCard,
        member: $member,
        completionCount: 1,
        transaction: $sourceTransaction
    ));

    // The listener should have created a point Transaction with the staff's attribution
    $pointTransaction = Transaction::where('event', 'stamp_card_completion')
        ->where('member_id', $member->id)
        ->where('card_id', $data['loyaltyCard']->id)
        ->first();

    expect($pointTransaction)->not->toBeNull();
    expect($pointTransaction->staff_id)->toBe($staff->id);
    expect($pointTransaction->staff_name)->toBe($staff->name);
    expect($pointTransaction->staff_email)->toBe($staff->email);
});

it('carries staff attribution into the bridge stamp transaction when completion is staff-driven', function () {
    $data = createStampCompletionSetup();
    $staff = $data['staff'];
    $member = $data['member'];
    $stampCard = $data['stampCard'];

    $sourceTransaction = StampTransaction::create([
        'id' => (string) Str::uuid(),
        'stamp_card_id' => $stampCard->id,
        'member_id' => $member->id,
        'staff_id' => $staff->id,
        'stamps' => 5,
        'stamps_before' => 0,
        'stamps_after' => 5,
        'event' => StampTransaction::EVENT_STAMP_EARNED,
    ]);

    event(new StampCardCompleted(
        card: $stampCard,
        member: $member,
        completionCount: 1,
        transaction: $sourceTransaction
    ));

    // The bridge stamp transaction (points_rewarded) should inherit staff
    $bridgeTransaction = StampTransaction::where('event', StampTransaction::EVENT_POINTS_REWARDED)
        ->where('member_id', $member->id)
        ->where('stamp_card_id', $stampCard->id)
        ->first();

    expect($bridgeTransaction)->not->toBeNull();
    expect($bridgeTransaction->staff_id)->toBe($staff->id);
});

it('uses System/null attribution when completion has no staff', function () {
    $data = createStampCompletionSetup();
    $member = $data['member'];
    $stampCard = $data['stampCard'];

    // Create a source transaction WITHOUT staff (system/automated)
    $sourceTransaction = StampTransaction::create([
        'id' => (string) Str::uuid(),
        'stamp_card_id' => $stampCard->id,
        'member_id' => $member->id,
        'staff_id' => null,
        'stamps' => 5,
        'stamps_before' => 0,
        'stamps_after' => 5,
        'event' => StampTransaction::EVENT_STAMP_EARNED,
    ]);

    event(new StampCardCompleted(
        card: $stampCard,
        member: $member,
        completionCount: 1,
        transaction: $sourceTransaction
    ));

    // Point transaction should have null staff_id and 'System' staff_name
    $pointTransaction = Transaction::where('event', 'stamp_card_completion')
        ->where('member_id', $member->id)
        ->first();

    expect($pointTransaction)->not->toBeNull();
    expect($pointTransaction->staff_id)->toBeNull();
    expect($pointTransaction->staff_name)->toBe('System');
    expect($pointTransaction->staff_email)->toBeNull();

    // Bridge stamp transaction should have null staff_id
    $bridgeTransaction = StampTransaction::where('event', StampTransaction::EVENT_POINTS_REWARDED)
        ->where('member_id', $member->id)
        ->first();

    expect($bridgeTransaction)->not->toBeNull();
    expect($bridgeTransaction->staff_id)->toBeNull();
});
