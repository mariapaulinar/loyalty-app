<?php

declare(strict_types=1);

use App\Events\Broadcasting\StaffStampEarned;
use App\Events\StampEarned;
use App\Listeners\BroadcastStaffStampEarned;
use App\Models\Club;
use App\Models\Member;
use App\Models\Staff;
use App\Models\StampCard;
use App\Models\StampTransaction;
use App\Services\StampService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['default.app_is_installed' => true]);

    $this->club = Club::factory()->create();
    $this->member = Member::factory()->create();
    $this->staff = Staff::factory()->create(['club_id' => $this->club->id]);

    $this->stampCard = StampCard::factory()->create([
        'club_id' => $this->club->id,
        'stamps_required' => 10,
        'is_active' => true,
    ]);

    $this->stampService = app(StampService::class);
});

it('broadcasts staff stamp earned to the member channel', function () {
    Broadcast::fake();

    $result = $this->stampService->addStamp(
        card: $this->stampCard,
        member: $this->member,
        staff: $this->staff,
        stamps: 1,
    );

    $transaction = $result['transaction'];

    $listener = new BroadcastStaffStampEarned();
    $listener->handle(new StampEarned(
        card: $this->stampCard,
        member: $this->member,
        stamps: 1,
        currentTotal: 1,
        transaction: $transaction,
    ));

    Broadcast::assertBroadcasted(StaffStampEarned::class, function (StaffStampEarned $event) use ($transaction) {
        return $event->transaction->id === $transaction->id
            && $event->member->id === $this->member->id;
    });
});

it('does not broadcast when stamp was not awarded by staff', function () {
    Broadcast::fake();

    $result = $this->stampService->addStamp(
        card: $this->stampCard,
        member: $this->member,
        staff: null,
        stamps: 1,
    );

    $listener = new BroadcastStaffStampEarned();
    $listener->handle(new StampEarned(
        card: $this->stampCard,
        member: $this->member,
        stamps: 1,
        currentTotal: 1,
        transaction: $result['transaction'],
    ));

    Broadcast::assertNothingBroadcasted();
});

it('stores stamp experience review for staff-awarded transactions', function () {
    $transaction = StampTransaction::create([
        'stamp_card_id' => $this->stampCard->id,
        'member_id' => $this->member->id,
        'staff_id' => $this->staff->id,
        'stamps' => 1,
        'stamps_before' => 0,
        'stamps_after' => 1,
        'event' => StampTransaction::EVENT_STAMP_EARNED,
    ]);

    $response = $this->actingAs($this->member, 'member')
        ->postJson(route('member.stamp.review', [
            'locale' => 'en-us',
            'transactionId' => $transaction->id,
        ]), [
            'rating' => 4,
        ]);

    $response->assertOk()
        ->assertJson(['success' => true]);

    $transaction->refresh();

    expect($transaction->review)
        ->toBeArray()
        ->and($transaction->review['rating'])->toBe(4)
        ->and($transaction->review['submitted_at'])->not->toBeEmpty();
});

it('rejects duplicate stamp experience reviews', function () {
    $transaction = StampTransaction::create([
        'stamp_card_id' => $this->stampCard->id,
        'member_id' => $this->member->id,
        'staff_id' => $this->staff->id,
        'stamps' => 1,
        'stamps_before' => 0,
        'stamps_after' => 1,
        'event' => StampTransaction::EVENT_STAMP_EARNED,
        'review' => [
            'rating' => 5,
            'submitted_at' => now()->toIso8601String(),
        ],
    ]);

    $response = $this->actingAs($this->member, 'member')
        ->postJson(route('member.stamp.review', [
            'locale' => 'en-us',
            'transactionId' => $transaction->id,
        ]), [
            'rating' => 3,
        ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false]);
});
