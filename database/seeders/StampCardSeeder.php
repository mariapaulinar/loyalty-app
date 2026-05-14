<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Seeds demo stamp card programs with realistic data.
 * Each partner gets stamp cards matching their business type only.
 */

namespace Database\Seeders;

use App\Models\Card;
use App\Models\Partner;
use App\Models\StampCard;
use Carbon\Carbon;
use Database\Seeders\Support\DemoPartnerConfig;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class StampCardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check for seed-all-images mode (creates one stamp card per image for testing backgrounds)
        $seedAllImages = env('APP_DEMO_SEED_ALL_IMAGES', false);

        // Stamp icons by business type
        $stampIcons = [
            'restaurants' => ['🍕', '🍔', '🍜', '🥗', '🍱', '🍴'],
            'cinema' => ['🎬', '🍿', '🎭', '🎫', '⭐', '🎥'],
            'beauty' => ['💅', '💄', '✨', '💎', '🌸', '💫'],
            'fitness' => ['💪', '⚡', '🏆', '⭐', '🎯', '❤️'],
            'bakery' => ['🥐', '🍩', '🧁', '🍪', '🎂', '🥖'],
            'cafes' => ['☕', '🫖', '🧋', '🍵', '✨', '💫'],
            'electronics' => ['📱', '💻', '🎧', '⌚', '🎮', '⚡'],
            'fashion' => ['👗', '👠', '👜', '💎', '✨', '🛍️'],
            'travel' => ['✈️', '🌴', '🧳', '🌍', '⛱️', '⭐'],
            'grocery' => ['🛒', '🍎', '🥕', '🥬', '🍞', '🧺'],
        ];

        // Premium Colors - Vibrant, Screenshot-Ready Palette
        $colorValues = [
            '#2563EB', '#DC2626', '#059669', '#D97706', '#7C3AED', '#0EA5E9',
            '#EA580C', '#8B5CF6', '#0891B2', '#E11D48', '#16A34A', '#D946EF',
        ];

        // Stamp color schemes coordinated with background
        $stampColorSchemes = [
            '#2563EB' => ['stamp' => '#93C5FD', 'empty' => '#DBEAFE'],
            '#DC2626' => ['stamp' => '#FCA5A5', 'empty' => '#FEE2E2'],
            '#059669' => ['stamp' => '#6EE7B7', 'empty' => '#D1FAE5'],
            '#D97706' => ['stamp' => '#FCD34D', 'empty' => '#FEF3C7'],
            '#7C3AED' => ['stamp' => '#C4B5FD', 'empty' => '#EDE9FE'],
            '#0EA5E9' => ['stamp' => '#7DD3FC', 'empty' => '#E0F2FE'],
            '#EA580C' => ['stamp' => '#FDBA74', 'empty' => '#FFEDD5'],
            '#8B5CF6' => ['stamp' => '#C4B5FD', 'empty' => '#EDE9FE'],
            '#0891B2' => ['stamp' => '#67E8F9', 'empty' => '#CFFAFE'],
            '#E11D48' => ['stamp' => '#FDA4AF', 'empty' => '#FFE4E6'],
            '#16A34A' => ['stamp' => '#86EFAC', 'empty' => '#DCFCE7'],
            '#D946EF' => ['stamp' => '#F0ABFC', 'empty' => '#FAE8FF'],
        ];

        // Get the partners from the database
        $partners = Partner::with('clubs')->get();
        $colorCounter = 0;
        $globalStampCounter = 0;

        foreach ($partners as $partnerIndex => $partner) {
            $businessType = DemoPartnerConfig::businessTypeFor($partner->email);

            // Load stamp card content (falls back to cards/ if no stamp-cards/ directory)
            $directory = database_path('data/demo/'.$businessType.'/stamp-cards/');
            if (! File::exists($directory)) {
                $directory = database_path('data/demo/'.$businessType.'/cards/');
            }
            if (! File::exists($directory)) {
                continue;
            }
            $files = File::files($directory);
            $locales = array_map(fn ($file) => pathinfo($file->getFilename(), PATHINFO_FILENAME), $files);

            $stampCards = [];
            foreach ($locales as $locale) {
                $jsonFilePath = $directory.'/'.$locale.'.json';
                $stampCards[$locale] = json_decode(file_get_contents($jsonFilePath), true);
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

            // In seed-all-images mode, create one stamp card per available image
            $stampCardsPerPartner = ($seedAllImages && $imageCount > 0) ? $imageCount : 1;
            $usedKeys = [];
            $usedImageNumbers = [];

            foreach ($partner->clubs as $club) {
                for ($stampCardCount = 0; $stampCardCount < $stampCardsPerPartner; $stampCardCount++) {
                    // Dates
                    if ($seedAllImages) {
                        $created_at = Carbon::now()->subMonths(2)->setTimezone('UTC');
                        $valid_from = Carbon::now()->subMonth()->setTimezone('UTC');
                        $valid_until = Carbon::now()->addYears(5)->setTimezone('UTC');
                    } else {
                        $created_at = Carbon::parse(fake()->dateTimeBetween('-14 month', '-4 month'))->setTimezone('UTC');
                        $valid_from = Carbon::parse(fake()->dateTimeBetween('-4 month', '-3 month'))->setTimezone('UTC');
                        $valid_until = Carbon::parse(fake()->dateTimeBetween('+1 year', '+7 year'))->setTimezone('UTC');
                    }

                    // Color
                    $bg_color = $colorValues[$colorCounter % count($colorValues)];
                    $stamp_color = $stampColorSchemes[$bg_color]['stamp'] ?? '#10B981';
                    $empty_stamp_color = $stampColorSchemes[$bg_color]['empty'] ?? '#E5E7EB';
                    $colorCounter++;

                    // Stamp configuration
                    if ($seedAllImages) {
                        $stampCountOptions = [5, 6, 8, 9, 10, 12];
                        $stamps_required = $stampCountOptions[$globalStampCounter % count($stampCountOptions)];
                        $globalStampCounter++;
                    } else {
                        $stamps_required_options = [6, 8, 9, 10, 12];
                        $stamps_required = $stamps_required_options[array_rand($stamps_required_options)];
                    }

                    $min_purchase = (rand(0, 100) < 50) ? [2.00, 3.00, 5.00, 7.50, 10.00][array_rand([2.00, 3.00, 5.00, 7.50, 10.00])] : null;
                    $max_stamps_per_day = (rand(0, 100) < 30) ? [1, 2, 3][array_rand([1, 2, 3])] : null;
                    // Deterministic icon: first icon for the business type
                    $stamp_icon = $stampIcons[$businessType][0] ?? '⭐';

                    // Reward points (70% get points reward)
                    $reward_points = null;
                    $reward_card_id = null;
                    if (rand(0, 100) < 70) {
                        $loyaltyCard = Card::where('club_id', $club->id)->inRandomOrder()->first();
                        if ($loyaltyCard) {
                            $reward_card_id = $loyaltyCard->id;
                            $reward_points_options = [100, 250, 500, 1000, 1500];
                            $reward_points = $reward_points_options[array_rand($reward_points_options)];
                        }
                    }

                    $requires_physical_claim = ($reward_points === null && rand(0, 100) < 50);

                    // Use curated key for first stamp card, random for seed-all-images extras
                    if ($stampCardCount === 0 && ! $seedAllImages) {
                        $stampKey = DemoPartnerConfig::stampKeyFor($partner->email);
                    } else {
                        $availableStampCards = count($stampCards[$locales[0]]);
                        if (count($usedKeys) >= $availableStampCards) {
                            $usedKeys = [];
                        }
                        do {
                            $stampKey = array_rand($stampCards[$locales[0]]);
                        } while (isset($usedKeys[$stampKey]) && count($usedKeys) < $availableStampCards);
                    }
                    $usedKeys[$stampKey] = true;

                    // Build translatable fields
                    $name = [];
                    $title = [];
                    $description = [];
                    $reward_title = [];
                    $reward_description = [];

                    foreach ($locales as $locale) {
                        $name[$locale] = $stampCards[$locale][$stampKey]['head'].' '.trans('common.stamp_card', [], $locale);
                        $title[$locale] = $stampCards[$locale][$stampKey]['title'];
                        $description[$locale] = $stampCards[$locale][$stampKey]['description'];

                        if ($requires_physical_claim) {
                            $reward_title[$locale] = trans('common.free', [], $locale).' '.$stampCards[$locale][$stampKey]['head'];
                            $reward_description[$locale] = trans('common.get_free_item_after_completion', [], $locale);
                        } elseif ($reward_points) {
                            $reward_title[$locale] = number_format($reward_points).' '.trans('common.points', [], $locale);
                            $reward_description[$locale] = trans('common.bonus_points_reward', [], $locale);
                        } else {
                            $reward_title[$locale] = trans('common.special_reward', [], $locale);
                            $reward_description[$locale] = trans('common.ask_staff_for_details', [], $locale);
                        }
                    }

                    // Visibility driven by explicit demo config, not query order
                    $is_visible_by_default = $seedAllImages
                        ? true
                        : ($club->name != 'Archived' && DemoPartnerConfig::isVisibleOnHomepage($partner->email));

                    $stampCard = StampCard::create([
                        'club_id' => $club->id,
                        'name' => $stampCards['en_US'][$stampKey]['head'].' Stamp Card',
                        'title' => $title,
                        'description' => $description,
                        'stamp_icon' => $stamp_icon,
                        'stamps_required' => $stamps_required,
                        'min_purchase_amount' => $min_purchase,
                        'max_stamps_per_transaction' => 1,
                        'max_stamps_per_day' => $max_stamps_per_day,
                        'reward_points' => $reward_points,
                        'reward_card_id' => $reward_card_id,
                        'reward_title' => $reward_title,
                        'reward_description' => $reward_description,
                        'requires_physical_claim' => $requires_physical_claim,
                        'valid_from' => $valid_from,
                        'valid_until' => $valid_until,
                        'is_active' => true,
                        'is_visible_by_default' => $is_visible_by_default,
                        'is_undeletable' => env('APP_IS_UNEDITABLE', true),
                        'bg_color' => $bg_color,
                        'bg_color_opacity' => rand(79, 88),
                        'text_color' => '#ffffff',
                        'stamp_color' => $stamp_color,
                        'empty_stamp_color' => $empty_stamp_color,
                        'currency' => 'USD',
                        'created_at' => $created_at,
                        'created_by' => $partner->id,
                    ]);

                    // Add background image if available
                    if ($imageCount > 0) {
                        if ($seedAllImages) {
                            $imageNumber = $stampCardCount + 1;
                        } else {
                            $imageNumber = $this->getUniqueRandomNumber($usedImageNumbers, $imageCount);
                        }
                        $background = database_path('data/demo-images/'.$businessType.'/cards/'.$imageNumber.'.jpg');

                        if (File::exists($background)) {
                            $stampCard
                                ->addMedia($background)
                                ->preservingOriginal()
                                ->sanitizingFileName(function ($fileName) {
                                    return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                                })
                                ->toMediaCollection('background', 'files');
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
