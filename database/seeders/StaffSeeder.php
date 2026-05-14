<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace Database\Seeders;

use App\Models\Partner;
use App\Models\Staff;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This function seeds the 'staff' table with test data.
     * For each partner, two staff members are created.
     */
    public function run(): void
    {
        // Get a faker instance
        $faker = Faker::create();

        // Get all partners
        $partners = Partner::query()->with('clubs')->get();

        // Create two staff members for each partner
        $count = 0;
        foreach ($partners as $partner) {
            $clubId = $partner->clubs->first()?->id;
            if (! $clubId) {
                continue;
            }

            for ($i = 0; $i < 2; $i++) {
                if ($count == 0) {
                    Staff::create([
                        'club_id' => $clubId,
                        'name' => 'Sam',
                        'email' => 'staff@example.com',
                        'password' => bcrypt(env('APP_DEMO_PASSWORD', 'welcome3210')),
                        'role' => 1, // 1 = user
                        'email_verified_at' => Carbon::now('UTC'),
                        'is_active' => true,
                        'is_undeletable' => env('APP_IS_UNEDITABLE', true),
                        'is_uneditable' => env('APP_IS_UNEDITABLE', true),
                        'created_at' => Carbon::now('UTC'),
                        'locale' => config('app.locale'),
                        'currency' => config('default.currency'),
                        'time_zone' => config('default.time_zone'),
                        'created_by' => $partner->id,
                    ]);
                } else {
                    Staff::create([
                        'club_id' => $clubId,
                        'name' => $faker->name(),
                        'email' => $faker->unique()->safeEmail(),
                        'password' => bcrypt(env('APP_DEMO_PASSWORD', 'welcome3210')),
                        'role' => 1, // 1 = user
                        'email_verified_at' => Carbon::now(),
                        'is_active' => true,
                        'is_undeletable' => true,
                        'is_uneditable' => true,
                        'locale' => config('app.locale'),
                        'currency' => config('default.currency'),
                        'time_zone' => config('default.time_zone'),
                        'created_by' => $partner->id,
                    ]);
                }
                $count++;
            }
        }
    }
}
