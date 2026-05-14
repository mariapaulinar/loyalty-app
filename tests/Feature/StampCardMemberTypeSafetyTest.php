<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

use App\Models\Club;
use App\Models\Member;
use App\Models\StampCard;
use App\Models\StampCardMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('does not throw when stamps_required is zero', function () {
    $club = Club::factory()->create();

    $member = Member::factory()->create([
        'is_active' => true,
    ]);

    $stampCard = StampCard::create([
        'club_id' => $club->id,
        'name' => 'Test Card',
        'title' => ['en' => 'Test'],
        'stamps_required' => 0,
        'stamps_per_purchase' => 1,
        'is_active' => true,
        'is_visible_by_default' => true,
        'created_by' => $club->created_by,
    ]);

    $enrollment = StampCardMember::create([
        'stamp_card_id' => $stampCard->id,
        'member_id' => $member->id,
        'current_stamps' => 0,
        'lifetime_stamps' => 0,
        'completed_count' => 0,
        'redeemed_count' => 0,
        'pending_rewards' => 0,
        'is_active' => true,
        'enrolled_at' => now(),
    ]);

    $enrollment->load('stampCard');

    $result = $enrollment->addStamps(3);

    expect($result)->toBeArray()
        ->and($result['completed'])->toBeFalse()
        ->and($result['completions'])->toBe(0)
        ->and($result['overflow'])->toBe(0);

    $enrollment->refresh();
    // Stamps are tracked in lifetime_stamps, but current_stamps stays 0
    // because stamps_required=0 means the card can never cycle/complete.
    expect($enrollment->current_stamps)->toBe(0)
        ->and($enrollment->lifetime_stamps)->toBe(3);
});
