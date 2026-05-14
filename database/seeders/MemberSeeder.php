<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Seeds demo member accounts. Members are global (shared wallet) — they
 * are not partner-scoped. Enrollment with specific partners happens through
 * transactions in the TransactionsAndAnalyticsSeeder.
 */

namespace Database\Seeders;

use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Primary demo member — logs in via demo credentials
        Member::create([
            'name' => 'Emma',
            'email' => 'member@example.com',
            'password' => bcrypt(env('APP_DEMO_PASSWORD', 'welcome3210')),
            'role' => 1,
            'email_verified_at' => Carbon::now('UTC'),
            'is_active' => true,
            'accepts_emails' => true,
            'is_undeletable' => env('APP_IS_UNEDITABLE', true),
            'is_uneditable' => env('APP_IS_UNEDITABLE', true),
            'created_at' => Carbon::now('UTC'),
            'locale' => config('app.locale'),
            'currency' => config('default.currency'),
            'time_zone' => config('default.time_zone'),
        ]);

        // Additional demo members for realistic data
        // 12 random members — enough for multi-partner transaction diversity
        for ($i = 0; $i < 12; $i++) {
            $created_at = fake()->dateTimeBetween('-78 week', '-6 week');
            Member::create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => bcrypt(env('APP_DEMO_PASSWORD', 'welcome3210')),
                'role' => 1,
                'email_verified_at' => $created_at,
                'is_active' => true,
                'accepts_emails' => true,
                'is_undeletable' => env('APP_IS_UNEDITABLE', true),
                'is_uneditable' => env('APP_IS_UNEDITABLE', true),
                'created_at' => $created_at,
                'locale' => config('app.locale'),
                'currency' => config('default.currency'),
                'time_zone' => config('default.time_zone'),
            ]);
        }
    }
}
