<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

use App\Models\Activity;
use App\Models\Partner;

use function Pest\Laravel\actingAs;

it('renders partner activity logs list without lazy-loading causer/subject', function () {
    $partner = Partner::factory()->createOne([
        'role' => 1,
        'meta' => ['activity_permission' => true],
    ]);

    Activity::factory()->create([
        'log_name' => 'default',
        'causer_type' => Partner::class,
        'causer_id' => $partner->id,
        'subject_type' => Partner::class,
        'subject_id' => $partner->id,
    ]);

    /** @var \Illuminate\Contracts\Auth\Authenticatable $authPartner */
    $authPartner = $partner;

    actingAs($authPartner, 'partner')
        ->get('/en-us/partner/manage/activity-logs')
        ->assertSuccessful();
});
