<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

use App\Models\Card;
use App\Models\Member;
use App\Models\Partner;
use App\Models\Staff;
use App\Models\Transaction;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

it('renders partner members list without missing attribute exceptions', function () {
    $partner = Partner::factory()->createOne([
        'role' => 1,
    ]);

    $club = \App\Models\Club::factory()->createOne([
        'created_by' => $partner->id,
    ]);

    $staff = Staff::factory()->createOne([
        'club_id' => $club->id,
        'created_by' => $partner->id,
    ]);

    $card = Card::factory()->createOne([
        'club_id' => $club->id,
        'created_by' => $partner->id,
        'currency' => 'USD',
    ]);

    $member = Member::factory()->createOne([
        'currency' => 'USD',
    ]);

    $member->cards()->attach($card->id);

    Transaction::query()->create([
        'id' => (string) Str::uuid(),
        'staff_id' => $staff->id,
        'member_id' => $member->id,
        'card_id' => $card->id,
        'partner_name' => $partner->name,
        'partner_email' => $partner->email,
        'staff_name' => $staff->name,
        'staff_email' => $staff->email,
        'card_title' => ['en' => $card->name],
        'currency' => 'USD',
        'purchase_amount' => 0,
        'points' => 0,
        'points_used' => 0,
        'event' => 'issued',
        'expires_at' => now()->addYear(),
        'meta' => [],
        'created_by' => $partner->id,
    ]);

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/members')
        ->assertSuccessful();
});
