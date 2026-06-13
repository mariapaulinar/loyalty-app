<?php

declare(strict_types=1);

use App\Models\Club;
use App\Models\Member;
use App\Models\Partner;
use App\Models\Staff;
use App\Models\StampCard;
use App\Models\StampTransaction;
use App\Services\StampExperienceReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(StampExperienceReviewService::class);
    $this->partner = Partner::factory()->create();
    $this->club = Club::factory()->create(['created_by' => $this->partner->id]);
    $this->member = Member::factory()->create();
    $this->staff = Staff::factory()->create(['club_id' => $this->club->id]);
    $this->stampCard = StampCard::factory()->create([
        'club_id' => $this->club->id,
        'created_by' => $this->partner->id,
    ]);
});

it('calculates review summary for partner stamp transactions', function () {
    StampTransaction::create([
        'stamp_card_id' => $this->stampCard->id,
        'member_id' => $this->member->id,
        'staff_id' => $this->staff->id,
        'stamps' => 1,
        'stamps_before' => 0,
        'stamps_after' => 1,
        'event' => StampTransaction::EVENT_STAMP_EARNED,
        'review' => ['rating' => 5, 'submitted_at' => now()->toIso8601String()],
    ]);

    StampTransaction::create([
        'stamp_card_id' => $this->stampCard->id,
        'member_id' => $this->member->id,
        'staff_id' => $this->staff->id,
        'stamps' => 1,
        'stamps_before' => 1,
        'stamps_after' => 2,
        'event' => StampTransaction::EVENT_STAMP_EARNED,
        'review' => ['rating' => 3, 'submitted_at' => now()->toIso8601String()],
    ]);

    StampTransaction::create([
        'stamp_card_id' => $this->stampCard->id,
        'member_id' => $this->member->id,
        'staff_id' => $this->staff->id,
        'stamps' => 1,
        'stamps_before' => 2,
        'stamps_after' => 3,
        'event' => StampTransaction::EVENT_STAMP_EARNED,
        'review' => null,
    ]);

    $summary = $this->service->getSummary($this->partner->id, $this->stampCard->id);

    expect($summary['average_rating'])->toBe(4.0)
        ->and($summary['total_reviews'])->toBe(2)
        ->and($summary['total_eligible'])->toBe(3)
        ->and($summary['response_rate'])->toBe(66.7)
        ->and($summary['distribution'][5])->toBe(1)
        ->and($summary['distribution'][3])->toBe(1);
});

it('ignores stamps not awarded by staff', function () {
    StampTransaction::create([
        'stamp_card_id' => $this->stampCard->id,
        'member_id' => $this->member->id,
        'staff_id' => null,
        'stamps' => 1,
        'stamps_before' => 0,
        'stamps_after' => 1,
        'event' => StampTransaction::EVENT_STAMP_EARNED,
        'review' => ['rating' => 5, 'submitted_at' => now()->toIso8601String()],
    ]);

    $summary = $this->service->getSummary($this->partner->id);

    expect($summary['total_reviews'])->toBe(0)
        ->and($summary['total_eligible'])->toBe(0);
});
