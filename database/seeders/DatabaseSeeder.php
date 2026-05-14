<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Prevent emails and notifications during demo seeding
        if (config('default.app_demo')) {
            Mail::fake();
            Notification::fake();
        }

        $this->call([
            // IsoSeeder::class,
            AdminSeeder::class,
            RewardLoyaltyLicenseSeeder::class,
        ]);

        if (config('default.app_demo')) {
            $this->call([
                PartnerSeeder::class,
                ClubSeeder::class,
                TierSeeder::class, // Create default tiers for demo clubs
                RewardSeeder::class,
                CardSeeder::class,
                StampCardSeeder::class,
                VoucherSeeder::class,
            ]);
        }

        $this->call([
            NetworkSeeder::class,
        ]);

        if (config('default.app_demo')) {
            $this->call([
                StaffSeeder::class,
                MemberSeeder::class,
                TransactionsAndAnalyticsSeeder::class,
                StampCardTransactionsAndAnalyticsSeeder::class,
                VoucherAnalyticsSeeder::class,
            ]);
        }
    }
}
