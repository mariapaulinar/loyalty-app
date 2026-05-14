<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

use App\Models\Card;
use App\Models\Club;
use App\Models\Partner;
use App\Services\Card\CardService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('eager loads partner when listing cards for partner', function () {
    $partner = Partner::factory()->create();
    $club = Club::factory()->create();

    Card::factory()->create([
        'club_id' => $club->id,
        'created_by' => $partner->id,
    ]);

    /** @var CardService $service */
    $service = app(CardService::class);

    $cards = $service->findCardsFromPartner($partner->id);

    expect($cards)->not->toBeEmpty();

    $card = $cards->first();
    expect($card->relationLoaded('partner'))->toBeTrue()
        ->and($card->relationLoaded('club'))->toBeTrue();
});
