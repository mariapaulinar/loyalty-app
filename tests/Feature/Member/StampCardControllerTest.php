<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Feature tests for member stamp card operations. These tests validate the
 * service layer that powers member stamp card views (progress, history,
 * completion, enrollment). When the member API is built, these assertions
 * can be reused as integration tests for the JSON endpoints.
 */

use App\Models\Club;
use App\Models\Member;
use App\Models\Staff;
use App\Models\StampCard;
use App\Models\StampCardMember;
use App\Services\StampService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->club = Club::factory()->create();
    $this->member = Member::factory()->create();
    $this->staff = Staff::factory()->create(['club_id' => $this->club->id]);

    $this->stampCard = StampCard::factory()->create([
        'club_id' => $this->club->id,
        'stamps_required' => 10,
        'stamps_per_purchase' => 1,
        'is_active' => true,
        'is_visible_by_default' => true,
    ]);

    $this->service = app(StampService::class);
});

it('returns member stamp cards with correct enrollment data', function () {
    // Enroll member by adding stamps
    $this->service->addStamp(
        card: $this->stampCard,
        member: $this->member,
        stamps: 3,
        staff: $this->staff,
    );

    $stampCards = $this->service->getMemberStampCards($this->member, $this->club);

    expect($stampCards)->toHaveCount(1);

    $card = $stampCards->first();
    expect($card->id)->toBe($this->stampCard->id);

    // Verify enrollment data
    $enrollment = $card->enrollments()
        ->where('member_id', $this->member->id)
        ->first();

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->current_stamps)->toBe(3)
        ->and($card->stamps_required)->toBe(10);
});

it('tracks progress percentage correctly at 50%', function () {
    $this->service->addStamp(
        card: $this->stampCard,
        member: $this->member,
        stamps: 5, // 50% of 10
        staff: $this->staff,
    );

    $progress = $this->stampCard->getMemberProgress($this->member);

    expect($progress)->not->toBeNull()
        ->and($progress->current_stamps)->toBe(5)
        ->and($progress->progress_percentage)->toBe(50.0);
});

it('tracks completion when stamps_required is reached', function () {
    $result = $this->service->addStamp(
        card: $this->stampCard,
        member: $this->member,
        stamps: 10,
        staff: $this->staff,
    );

    // When card completes, stamps reset to 0 (overflow to new cycle)
    expect($result['completed'])->toBeTrue()
        ->and($result['current_stamps'])->toBe(0)
        ->and($result['pending_rewards'])->toBeGreaterThan(0);

    // Progress model reflects completion
    $progress = $this->stampCard->getMemberProgress($this->member);
    // After completion, current_stamps is 0 (ready for next cycle)
    expect($progress->current_stamps)->toBe(0);
});

it('enrolls member on first stamp', function () {
    // Before: not enrolled
    expect($this->stampCard->isMemberEnrolled($this->member))->toBeFalse();

    $this->service->addStamp(
        card: $this->stampCard,
        member: $this->member,
        stamps: 1,
        staff: $this->staff,
    );

    // After: enrolled
    expect($this->stampCard->isMemberEnrolled($this->member))->toBeTrue();
});

it('prevents access to other clubs stamp cards', function () {
    $otherClub = Club::factory()->create();
    $otherCard = StampCard::factory()->create([
        'club_id' => $otherClub->id,
        'stamps_required' => 5,
    ]);

    // Enroll member in the original club's card
    $this->service->addStamp(
        card: $this->stampCard,
        member: $this->member,
        stamps: 1,
        staff: $this->staff,
    );

    // getMemberStampCards scopes by club — other club's cards should not appear
    $stampCards = $this->service->getMemberStampCards($this->member, $this->club);

    $ids = $stampCards->pluck('id')->toArray();
    expect($ids)->toContain($this->stampCard->id)
        ->and($ids)->not->toContain($otherCard->id);
});

it('tracks stamp history via card statistics', function () {
    // Add stamps twice
    $this->service->addStamp(
        card: $this->stampCard,
        member: $this->member,
        stamps: 3,
        staff: $this->staff,
    );

    $this->service->addStamp(
        card: $this->stampCard,
        member: $this->member,
        stamps: 2,
        staff: $this->staff,
    );

    $stats = $this->service->getCardStatistics($this->stampCard);

    // enrollment_count tracks active enrolled members
    expect($stats['enrollment_count'])->toBe(1)
        ->and($stats['avg_stamps_per_member'])->toBe(5.0);
});
