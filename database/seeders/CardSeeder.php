<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Seeds demo loyalty card programs with realistic data.
 * Each partner gets cards matching their business type only.
 */

namespace Database\Seeders;

use App\Models\Card;
use App\Models\Partner;
use Database\Seeders\Support\DemoPartnerConfig;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check for seed-all-images mode (creates one card per image for testing backgrounds)
        $seedAllImages = env('APP_DEMO_SEED_ALL_IMAGES', false);

        $rewardsPerCard = 4;

        // Premium Colors - Vibrant, Screenshot-Ready Palette
        // Every color must pop against white text and look great in marketing materials.
        $colorValues = [
            '#2563EB', // 1. Platform Blue
            '#DC2626', // 2. Vivid Red
            '#059669', // 3. Emerald Green
            '#D97706', // 4. Warm Amber
            '#7C3AED', // 5. Vibrant Violet
            '#0EA5E9', // 6. Sky Blue
            '#EA580C', // 7. Burnt Orange
            '#8B5CF6', // 8. Soft Violet
            '#0891B2', // 9. Cyan/Teal
            '#E11D48', // 10. Rose Red
            '#16A34A', // 11. Grass Green
            '#D946EF', // 12. Fuchsia
        ];

        // Get the partners from the database
        $partners = Partner::with(['clubs', 'rewards'])->get();
        $colorCounter = 0;

        foreach ($partners as $partnerIndex => $partner) {
            $businessType = DemoPartnerConfig::businessTypeFor($partner->email);

            // Load card content for this partner's business type
            $directory = database_path('data/demo/'.$businessType.'/cards/');
            if (! File::exists($directory)) {
                continue;
            }
            $files = File::files($directory);
            $locales = array_map(fn ($file) => pathinfo($file->getFilename(), PATHINFO_FILENAME), $files);

            $cards = [];
            foreach ($locales as $locale) {
                $jsonFilePath = database_path('data/demo/'.$businessType.'/cards/'.$locale.'.json');
                $cards[$locale] = json_decode(file_get_contents($jsonFilePath), true);
            }

            // Count demo images
            $imageDirectory = database_path('data/demo-images/'.$businessType.'/cards/');
            $imageCount = 0;
            if (File::exists($imageDirectory)) {
                foreach (File::files($imageDirectory) as $file) {
                    if ($file->getExtension() == 'jpg') {
                        $imageCount++;
                    }
                }
            }

            // In seed-all-images mode, create one card per available image
            $cardsPerPartner = ($seedAllImages && $imageCount > 0) ? $imageCount : 1;
            $usedKeys = [];
            $usedImageNumbers = [];

            foreach ($partner->clubs as $club) {
                for ($cardCount = 0; $cardCount < $cardsPerPartner; $cardCount++) {
                    // In seed-all-images mode, use fixed valid dates to ensure all cards are active
                    if ($seedAllImages) {
                        $created_at = now()->subMonths(2);
                        $issue_date = now()->subMonth();
                        $expiration_date = now()->addYears(5);
                    } else {
                        $created_at = fake()->dateTimeBetween('-14 month', '-4 month');
                        $issue_date = fake()->dateTimeBetween('-4 month', '-3 month');
                        $expiration_date = fake()->dateTimeBetween('+1 year', '+7 year');
                    }

                    // Color
                    $bg_color = $colorValues[$colorCounter % count($colorValues)];
                    $colorCounter++;

                    // Numbers
                    $values = [10, 20, 50, 100, 100, 100, 100, 100, 150, 200, 250];
                    $initial_bonus_points = $values[array_rand($values)];

                    $values = [6, 8, 10, 11];
                    $points_expiration_months = $values[array_rand($values)];

                    $values = [1, 5, 10];
                    $currency_unit_amount = $values[array_rand($values)];

                    $values = [5, 10, 50];
                    $points_per_currency = $values[array_rand($values)] * $currency_unit_amount;

                    $values = [1, 10, 50, 100];
                    $min_points_per_purchase = $values[array_rand($values)];

                    $values = [10000, 50000, 100000, 1000000, 1000000, 1000000, 1000000];
                    $max_points_per_purchase = $values[array_rand($values)];

                    // Use curated key for first card, random for seed-all-images extras
                    if ($cardCount === 0 && ! $seedAllImages) {
                        $cardKey = DemoPartnerConfig::cardKeyFor($partner->email);
                    } else {
                        $availableCards = count($cards[$locales[0]]);
                        if (count($usedKeys) >= $availableCards) {
                            $usedKeys = [];
                        }
                        do {
                            $cardKey = array_rand($cards[$locales[0]]);
                        } while (isset($usedKeys[$cardKey]) && count($usedKeys) < $availableCards);
                    }
                    $usedKeys[$cardKey] = true;

                    foreach ($locales as $locale) {
                        $head[$locale] = $cards[$locale][$cardKey]['head'];
                        $title[$locale] = $cards[$locale][$cardKey]['title'];
                        $description[$locale] = $cards[$locale][$cardKey]['description'];
                    }

                    // Visibility driven by explicit demo config, not query order
                    $is_visible_by_default = $seedAllImages
                        ? true
                        : ($club->name != 'Archived' && DemoPartnerConfig::isVisibleOnHomepage($partner->email));

                    $card = Card::create([
                        'club_id' => $club->id,
                        'name' => $cards['en_US'][$cardKey]['head'],
                        'head' => $head,
                        'title' => $title,
                        'description' => $description,
                        'issue_date' => $issue_date,
                        'expiration_date' => $expiration_date,
                        'bg_color' => $bg_color,
                        'bg_color_opacity' => rand(79, 88),
                        'text_color' => '#ffffff',
                        'text_label_color' => '#ffffff',
                        'qr_color_light' => '#ffffff',
                        'qr_color_dark' => $bg_color,
                        'currency' => 'USD',
                        'initial_bonus_points' => $initial_bonus_points,
                        'points_expiration_months' => $points_expiration_months,
                        'currency_unit_amount' => $currency_unit_amount,
                        'points_per_currency' => $points_per_currency,
                        'point_value' => 0,
                        'min_points_per_purchase' => $min_points_per_purchase,
                        'max_points_per_purchase' => $max_points_per_purchase,
                        'min_points_per_redemption' => 0,
                        'max_points_per_redemption' => 0,
                        'is_active' => true,
                        'is_undeletable' => env('APP_IS_UNEDITABLE', true),
                        'is_uneditable' => env('APP_IS_UNEDITABLE', true),
                        'is_visible_by_default' => $is_visible_by_default,
                        'is_visible_when_logged_in' => false,
                        'created_at' => $created_at,
                        'created_by' => $partner->id,
                    ]);

                    // Add background image if available
                    if ($imageCount > 0) {
                        if ($seedAllImages) {
                            $imageNumber = $cardCount + 1;
                        } else {
                            $imageNumber = $this->getUniqueRandomNumber($usedImageNumbers, $imageCount);
                        }
                        $background = database_path('data/demo-images/'.$businessType.'/cards/'.$imageNumber.'.jpg');

                        if (File::exists($background)) {
                            $card
                                ->addMedia($background)
                                ->preservingOriginal()
                                ->sanitizingFileName(function ($fileName) {
                                    return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                                })
                                ->toMediaCollection('background', 'files');
                        }
                    }

                    // Attach rewards (pick up to $rewardsPerCard from this partner's rewards)
                    $rewardCount = min($rewardsPerCard, $partner->rewards->count());
                    if ($rewardCount > 0) {
                        $rewards = $partner->rewards->random($rewardCount);
                        $card->rewards()->attach($rewards);
                    }
                }
            }
        }
    }

    /**
     * Get a truly random unique number using cryptographic randomness.
     */
    public function getUniqueRandomNumber(array &$usedNumbers, int $imageCount): int
    {
        if (count($usedNumbers) >= $imageCount) {
            $usedNumbers = [];
        }

        do {
            $number = random_int(1, $imageCount);
        } while (in_array($number, $usedNumbers, true));

        $usedNumbers[] = $number;

        return $number;
    }
}
