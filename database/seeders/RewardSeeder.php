<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Seeds demo rewards with realistic data.
 * Each partner gets rewards matching their business type only.
 */

namespace Database\Seeders;

use App\Models\Partner;
use App\Models\Reward;
use Database\Seeders\Support\DemoPartnerConfig;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class RewardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rewardsPerPartner = 6;

        // Get the partners from the database
        $partners = Partner::with('clubs')->get();

        foreach ($partners as $partner) {
            $businessType = DemoPartnerConfig::businessTypeFor($partner->email);

            // Count demo images (skip if directory doesn't exist)
            $imageDirectory = database_path('data/demo-images/'.$businessType.'/rewards/');
            $imageCount = 0;
            if (File::exists($imageDirectory)) {
                foreach (File::files($imageDirectory) as $file) {
                    if (File::extension($file) == 'jpg') {
                        $imageCount++;
                    }
                }
            }

            // Load reward content for this partner's business type
            $directory = database_path('data/demo/'.$businessType.'/rewards/');
            if (! File::exists($directory)) {
                continue;
            }
            $files = File::files($directory);
            $locales = array_map(fn ($file) => pathinfo($file->getFilename(), PATHINFO_FILENAME), $files);

            $rewards = [];
            foreach ($locales as $locale) {
                $jsonFilePath = database_path('data/demo/'.$businessType.'/rewards/'.$locale.'.json');
                $rewards[$locale] = json_decode(file_get_contents($jsonFilePath), true);
            }

            // Track used keys and images for unique selection
            $usedKeys = [];
            $usedImageNumbers = [];
            $availableRewards = count($rewards[$locales[0]]);
            $rewardsToCreate = min($rewardsPerPartner, $availableRewards);

            for ($i = 0; $i < $rewardsToCreate; $i++) {
                $created_at = fake()->dateTimeBetween('-32 week', '-6 week');
                $active_from = fake()->dateTimeBetween('-32 week', '-1 day');
                $expiration_date = fake()->dateTimeBetween('+2 week', '+64 week');

                // Points
                $values = [10, 10, 25, 25, 100, 100, 100, 100, 100, 150, 200, 250, 300, 400, 500, 500, 750, 1000, 1500, 2000, 2500, 3000, 4000, 5000, 7500, 10000];
                $points = $values[array_rand($values)];

                // Generate a unique random key
                if (count($usedKeys) >= $availableRewards) {
                    $usedKeys = [];
                }
                do {
                    $randomKey = array_rand($rewards[$locales[0]]);
                } while (in_array($randomKey, $usedKeys) && count($usedKeys) < $availableRewards);
                $usedKeys[] = $randomKey;

                foreach ($locales as $locale) {
                    $title[$locale] = $rewards[$locale][$randomKey]['title'];
                    $description[$locale] = $rewards[$locale][$randomKey]['description'];
                }

                $reward = Reward::create([
                    'name' => $rewards['en_US'][$randomKey]['title'],
                    'title' => $title,
                    'description' => $description,
                    'max_number_to_redeem' => 0,
                    'points' => $points,
                    'active_from' => $active_from,
                    'expiration_date' => $expiration_date,
                    'is_active' => true,
                    'is_undeletable' => env('APP_IS_UNEDITABLE', true),
                    'is_uneditable' => env('APP_IS_UNEDITABLE', true),
                    'number_of_times_redeemed' => 0,
                    'views' => 0,
                    'created_at' => $created_at,
                    'created_by' => $partner->id,
                ]);

                // Add images if available (truly random unique selection)
                if ($imageCount > 0) {
                    for ($imageSlot = 1; $imageSlot <= 3; $imageSlot++) {
                        $imageNumber = $this->getUniqueRandomNumber($usedImageNumbers, $imageCount);
                        $image = database_path('data/demo-images/'.$businessType.'/rewards/'.$imageNumber.'.jpg');

                        if (File::exists($image)) {
                            $reward
                                ->addMedia($image)
                                ->preservingOriginal()
                                ->sanitizingFileName(function ($fileName) {
                                    return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                                })
                                ->toMediaCollection('image'.$imageSlot, 'files');
                        }
                    }
                }
            }
        }
    }

    /**
     * Get a truly random unique number using cryptographic randomness.
     */
    private function getUniqueRandomNumber(array &$usedNumbers, int $imageCount): int
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
