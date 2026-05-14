<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Seeds demo partner accounts with realistic business profiles
 * and plan diversity for v4 SaaS demonstration.
 *
 * Meta limits are derived from config/plans.php to prevent contract drift.
 * Any intentional override (e.g. demo partner needing extra headroom)
 * must be documented inline.
 */

namespace Database\Seeders;

use App\Models\Partner;
use App\Services\SettingsService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class PartnerSeeder extends Seeder
{
    /**
     * Build meta permissions from config/plans.php for a given plan slug.
     *
     * Maps plan limits AND feature flags to the meta JSON keys that the UI reads.
     * All values are read directly from the plan config — nothing is derived
     * from sort_order or other heuristics.
     */
    private function metaFromPlan(string $plan, array $overrides = []): array
    {
        return Partner::deriveMetaFromPlan($plan, $overrides);
    }

    /**
     * Demo partner definitions.
     *
     * Each partner represents a realistic local business with a specific plan.
     * Business profile fields satisfy the demo checklist requirement for
     * realistic businesses (coffee shop, restaurant, salon, etc.).
     *
     * The `logo` field points to a square business logo (not a person photo)
     * that is displayed on the loyalty card tile on the homepage.
     */
    private function getDemoPartners(): array
    {
        return [
            // ─── Partner 1: Gold plan, primary demo, coffee shop ──────────────
            [
                'name' => 'The Daily Grind',
                'email' => 'partner@example.com',
                'plan' => 'tier3',
                'is_primary' => true,
                'business_name' => 'The Daily Grind',
                'tagline' => 'Specialty coffee & fresh pastries since 2019',
                'brand_color' => '#D97706',
                'description' => 'Neighborhood specialty coffee bar serving single-origin pour-overs, house-made pastries, and seasonal drinks. Two locations in downtown Portland.',
                'address_line_1' => '742 Evergreen Terrace',
                'city' => 'Portland',
                'state' => 'OR',
                'postal_code' => '97201',
                'website' => 'https://thedailygrind.example.com',
                'logo' => 'logos/coffee-shop.png',
                'meta_overrides' => ['cards_on_homepage' => true],
            ],
            // ─── Partner 2: Silver plan, restaurant ──────────────────────────
            [
                'name' => 'Basil & Thyme Kitchen',
                'email' => 'partner2@example.com',
                'plan' => 'tier2',
                'is_primary' => false,
                'created_ago' => '-40 weeks',
                'logins' => 28,
                'business_name' => 'Basil & Thyme Kitchen',
                'tagline' => 'Farm-to-table lunch & dinner',
                'brand_color' => '#059669',
                'description' => 'Modern bistro focusing on locally sourced ingredients and seasonal menus. Known for our weekend brunch and craft cocktails.',
                'address_line_1' => '1200 Market Street',
                'city' => 'San Francisco',
                'state' => 'CA',
                'postal_code' => '94103',
                'website' => 'https://basilandthyme.example.com',
                'logo' => 'logos/restaurant.png',
                'meta_overrides' => ['cards_on_homepage' => true],
            ],
            // ─── Partner 3: Tier 1 (free) plan, newer salon ─────────────────
            [
                'name' => 'Glow Studio',
                'email' => 'partner3@example.com',
                'plan' => 'tier1',
                'is_primary' => false,
                'created_ago' => '-8 weeks',
                'logins' => 6,
                'business_name' => 'Glow Studio',
                'tagline' => 'Skin care & beauty treatments',
                'brand_color' => '#D946EF',
                'description' => 'Boutique beauty studio offering facials, lash extensions, and nail art. Just opened in the Pearl District.',
                'address_line_1' => '55 NW 11th Avenue',
                'city' => 'Portland',
                'state' => 'OR',
                'postal_code' => '97209',
                'website' => null,
                'logo' => 'logos/beauty-salon.png',
                'meta_overrides' => ['cards_on_homepage' => true, 'vouchers_permission' => true, 'vouchers_limit' => 1],
            ],
            // ─── Partner 4: Tier 4 plan, agency-style, fitness chain ───────
            [
                'name' => 'Ironclad Fitness',
                'email' => 'partner4@example.com',
                'plan' => 'tier4',
                'is_primary' => false,
                'created_ago' => '-52 weeks',
                'logins' => 142,
                'business_name' => 'Ironclad Fitness',
                'tagline' => 'Train hard. Recover smart.',
                'brand_color' => '#2563EB',
                'description' => 'Multi-location gym and recovery center with personal training, group classes, and cryotherapy. Operating across three cities.',
                'address_line_1' => '800 Congress Avenue',
                'address_line_2' => 'Suite 200',
                'city' => 'Austin',
                'state' => 'TX',
                'postal_code' => '78701',
                'website' => 'https://ironcladfitness.example.com',
                'logo' => 'logos/gym.png',
                'meta_overrides' => [],
            ],
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Enable partner self-registration in demo mode so testers
        // can access and test the /partner/register page
        if (config('default.app_demo')) {
            app(SettingsService::class)->set(
                'partners_can_register',
                true,
                null,
                'boolean'
            );
        }

        foreach ($this->getDemoPartners() as $partnerData) {
            $isPrimary = $partnerData['is_primary'];
            $createdAt = $isPrimary
                ? Carbon::now('UTC')
                : Carbon::parse($partnerData['created_ago'], 'UTC');

            $partner = Partner::create([
                'name' => $partnerData['name'],
                'email' => $partnerData['email'],
                'password' => bcrypt(env('APP_DEMO_PASSWORD', 'welcome3210')),
                'role' => 1,
                'plan' => $partnerData['plan'],
                'email_verified_at' => $createdAt,
                'is_active' => true,
                'is_undeletable' => env('APP_IS_UNEDITABLE', true),
                'is_uneditable' => env('APP_IS_UNEDITABLE', true),
                'number_of_times_logged_in' => $partnerData['logins'] ?? 0,
                'last_login_at' => $isPrimary ? null : fake()->dateTimeBetween('-7 days', '-1 day'),
                'created_at' => $createdAt,
                'locale' => config('app.locale'),
                'currency' => config('default.currency'),
                'time_zone' => config('default.time_zone'),
                'meta' => $this->metaFromPlan($partnerData['plan'], $partnerData['meta_overrides'] ?? []),
                // Business profile fields
                'business_name' => $partnerData['business_name'],
                'tagline' => $partnerData['tagline'] ?? null,
                'brand_color' => $partnerData['brand_color'] ?? '#10B981',
                'description' => $partnerData['description'] ?? null,
                'address_line_1' => $partnerData['address_line_1'] ?? null,
                'address_line_2' => $partnerData['address_line_2'] ?? null,
                'city' => $partnerData['city'] ?? null,
                'state' => $partnerData['state'] ?? null,
                'postal_code' => $partnerData['postal_code'] ?? null,
                'website' => $partnerData['website'] ?? null,
            ]);

            // Add business logo as partner avatar in demo mode
            if (config('default.app_demo') && ! empty($partnerData['logo'])) {
                $logo = database_path('data/demo-images/'.$partnerData['logo']);
                if (File::exists($logo)) {
                    $partner
                        ->addMedia($logo)
                        ->preservingOriginal()
                        ->sanitizingFileName(function ($fileName) {
                            return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                        })
                        ->toMediaCollection('avatar', 'files');
                }
            }
        }
    }
}
