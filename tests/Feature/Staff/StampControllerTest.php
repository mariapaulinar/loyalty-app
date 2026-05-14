<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Feature tests for Staff StampController - testing stamp operations
 * via both HTTP routes and service layer.
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
    $this->staff = Staff::factory()->create(['club_id' => $this->club->id]);
    $this->member = Member::factory()->create();

    $this->stampCard = StampCard::factory()->create([
        'club_id' => $this->club->id,
        'stamps_required' => 10,
        'stamps_per_purchase' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($this->staff, 'staff');
});

// ═══════════════════════════════════════════════════════════════════
// HTTP ROUTE TESTS — verify actual controller endpoints
// ═══════════════════════════════════════════════════════════════════

it('allows staff to add stamps to member card', function () {
    $response = $this->post(route('staff.stamps.add', ['locale' => 'en-us']), [
        'member_identifier' => $this->member->unique_identifier,
        'stamp_card_id' => $this->stampCard->id,
        'stamps' => 1,
        'purchase_amount' => 15.50,
        'note' => 'Regular purchase',
    ]);

    // Web controller redirects on success
    $response->assertRedirect();
    $response->assertSessionHas('success');
});

it('returns member stamp card status as JSON via HTTP', function () {
    // Enroll member first
    $stampService = app(StampService::class);
    $stampService->addStamp(
        card: $this->stampCard,
        member: $this->member,
        staff: $this->staff,
        stamps: 3,
    );

    $url = route('staff.stamps.member', [
        'locale' => 'en-us',
        'identifier' => $this->member->unique_identifier,
    ]);

    // Hit the actual HTTP route through all middleware
    $response = $this->getJson($url);

    // The route MUST return JSON, not HTML redirect
    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'member' => ['id', 'name', 'identifier'],
                'stamp_cards',
            ],
        ])
        ->assertJson([
            'success' => true,
            'data' => [
                'member' => [
                    'id' => $this->member->id,
                ],
                'stamp_cards' => [
                    [
                        'id' => $this->stampCard->id,
                        'current_stamps' => 3,
                        'stamps_required' => 10,
                    ],
                ],
            ],
        ]);
});

it('returns 404 JSON for unknown member identifier via HTTP', function () {
    $url = route('staff.stamps.member', [
        'locale' => 'en-us',
        'identifier' => 'nonexistent-identifier',
    ]);

    $response = $this->getJson($url);

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Member not found',
        ]);
});

it('validates required fields for adding stamps', function () {
    $response = $this->post(route('staff.stamps.add', ['locale' => 'en-us']), [
        // Missing required fields
    ]);

    // Web controller redirects back with validation errors
    $response->assertRedirect();
    $response->assertSessionHasErrors(['member_identifier', 'stamp_card_id', 'stamps']);
});

it('handles invalid member identifier gracefully', function () {
    $response = $this->post(route('staff.stamps.add', ['locale' => 'en-us']), [
        'member_identifier' => 'invalid-identifier',
        'stamp_card_id' => $this->stampCard->id,
        'stamps' => 1,
    ]);

    // Web controller redirects back with error flash
    $response->assertRedirect();
});

// ═══════════════════════════════════════════════════════════════════
// SERVICE LAYER TESTS — verify business logic powering the endpoints
// ═══════════════════════════════════════════════════════════════════

it('enrolls member and tracks stamps via service', function () {
    $stampService = app(StampService::class);

    // Before: no enrollment
    expect(StampCardMember::where('member_id', $this->member->id)->count())->toBe(0);

    // Add stamps via service (same path as controller)
    $result = $stampService->addStamp(
        card: $this->stampCard,
        member: $this->member,
        staff: $this->staff,
        stamps: 3,
    );

    // Verify stamps were added
    expect($result['success'])->toBeTrue()
        ->and($result['stamps_added'])->toBe(3)
        ->and($result['current_stamps'])->toBe(3)
        ->and($result['completed'])->toBeFalse();

    // Member is now enrolled
    expect(StampCardMember::where('member_id', $this->member->id)->count())->toBe(1);
});

it('returns member stamp card data via service layer', function () {
    // Add stamps to enroll member
    $stampService = app(StampService::class);
    $stampService->addStamp(
        card: $this->stampCard,
        member: $this->member,
        staff: $this->staff,
        stamps: 1,
    );

    // Get member stamp cards (same call as getMemberStatus controller)
    $stampCards = $stampService->getMemberStampCards($this->member, $this->club);

    expect($stampCards)->toHaveCount(1);

    $card = $stampCards->first();
    expect($card->id)->toBe($this->stampCard->id);

    // Verify enrollment data
    $enrollment = $card->enrollments()
        ->where('member_id', $this->member->id)
        ->first();

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->current_stamps)->toBe(1);
});
