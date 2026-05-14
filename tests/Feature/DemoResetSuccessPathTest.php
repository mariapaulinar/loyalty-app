<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Tests the demo:reset command's full destructive success path.
 *
 * Uses DatabaseMigrations (not RefreshDatabase) because the command
 * itself calls db:wipe + migrate, which is incompatible with
 * RefreshDatabase's transaction wrapper on in-memory SQLite.
 */

use App\Models\Admin;
use App\Models\Member;
use App\Models\Partner;
use App\Models\Staff;
use Illuminate\Foundation\Testing\DatabaseMigrations;

uses(DatabaseMigrations::class);

/*
|--------------------------------------------------------------------------
| demo:reset Full Success Path
|--------------------------------------------------------------------------
| Exercises the actual destructive path: db:wipe → migrate → seed → cache clear
*/

describe('demo:reset success path', function () {
    it('runs the full reset cycle', function () {
        config(['default.app_demo' => true]);

        // Run the full reset (db:wipe + migrate + seed + cache clear)
        $this->artisan('demo:reset', ['--force' => true])
            ->assertSuccessful();

        // Verify the complete demo state was created
        expect(Admin::where('email', 'admin@example.com')->exists())->toBeTrue()
            ->and(Partner::count())->toBe(4)
            ->and(Staff::where('email', 'staff@example.com')->exists())->toBeTrue()
            ->and(Member::where('email', 'member@example.com')->exists())->toBeTrue();
    });

    it('is repeatable — second run produces identical demo state', function () {
        config(['default.app_demo' => true]);

        // First run
        $this->artisan('demo:reset', ['--force' => true])
            ->assertSuccessful();

        $firstPartnerCount = Partner::count();
        $firstMemberCount = Member::count();

        // Second run (destroys + rebuilds)
        $this->artisan('demo:reset', ['--force' => true])
            ->assertSuccessful();

        expect(Partner::count())->toBe($firstPartnerCount)
            ->and(Member::count())->toBe($firstMemberCount);
    });
});
