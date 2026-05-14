<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

use App\Models\Partner;
use App\Models\Voucher;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

it('redirects to batches page after generating a voucher batch', function () {
    $partner = Partner::factory()->createOne([
        'role' => 1,
    ]);

    $club = \App\Models\Club::factory()->createOne([
        'created_by' => $partner->id,
    ]);

    $template = Voucher::factory()->createOne([
        'created_by' => $partner->id,
        'club_id' => $club->id,
        'batch_id' => null,
        'is_active' => true,
        'valid_until' => now()->addDays(30),
    ]);

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    actingAs($authPartner, 'partner');

    post('/en-us/partner/vouchers/batch/generate', [
        'template_id' => $template->id,
        'club_id' => $club->id,
        'batch_name' => 'Test Batch',
        'quantity' => 2,
        'code_prefix' => 'TEST',
    ])
        ->assertRedirect('/en-us/partner/vouchers/batches')
        ->assertSessionHas('created_batch_id');
});
