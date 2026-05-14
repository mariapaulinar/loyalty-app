<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Seeds demo vouchers with realistic multilanguage data.
 * Each partner gets vouchers matching their business type only.
 * Partners without voucher permission (tier1) are skipped.
 */

namespace Database\Seeders;

use App\Models\Card;
use App\Models\Partner;
use App\Models\Voucher;
use Carbon\Carbon;
use Database\Seeders\Support\DemoPartnerConfig;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class VoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check for seed-all-images mode
        $seedAllImages = env('APP_DEMO_SEED_ALL_IMAGES', false);

        // Voucher type configurations by business type
        // Colors are chosen per business type to evoke the right mood
        $voucherConfigs = [
            'cafes' => [
                ['type' => 'percentage', 'value' => 2000, 'min_purchase' => 1000, 'bg_color' => '#D97706'],
                ['type' => 'free_product', 'value' => 0, 'bg_color' => '#EA580C'],
                ['type' => 'fixed_amount', 'value' => 500, 'min_purchase' => 1500, 'bg_color' => '#059669'],
                ['type' => 'bonus_points', 'value' => 0, 'points' => 100, 'bg_color' => '#7C3AED'],
                ['type' => 'free_product', 'value' => 0, 'bg_color' => '#0EA5E9'],
                ['type' => 'percentage', 'value' => 1500, 'max_discount' => 500, 'bg_color' => '#8B5CF6'],
            ],
            'restaurants' => [
                ['type' => 'percentage', 'value' => 2500, 'min_purchase' => 5000, 'bg_color' => '#059669'],
                ['type' => 'free_product', 'value' => 0, 'bg_color' => '#D97706'],
                ['type' => 'fixed_amount', 'value' => 1000, 'min_purchase' => 3000, 'bg_color' => '#2563EB'],
                ['type' => 'free_product', 'value' => 0, 'bg_color' => '#E11D48'],
                ['type' => 'percentage', 'value' => 3000, 'max_discount' => 3000, 'bg_color' => '#7C3AED'],
                ['type' => 'fixed_amount', 'value' => 2000, 'min_purchase' => 8000, 'bg_color' => '#0891B2'],
            ],
            'beauty' => [
                ['type' => 'free_product', 'value' => 0, 'bg_color' => '#D946EF'],
                ['type' => 'percentage', 'value' => 1500, 'min_purchase' => 5000, 'bg_color' => '#E11D48'],
                ['type' => 'fixed_amount', 'value' => 1500, 'min_purchase' => 5000, 'bg_color' => '#8B5CF6'],
                ['type' => 'bonus_points', 'value' => 0, 'points' => 200, 'bg_color' => '#7C3AED'],
                ['type' => 'percentage', 'value' => 2000, 'max_discount' => 4000, 'bg_color' => '#BE185D'],
                ['type' => 'free_product', 'value' => 0, 'bg_color' => '#D97706'],
            ],
            'fitness' => [
                ['type' => 'free_product', 'value' => 0, 'bg_color' => '#2563EB'],
                ['type' => 'free_product', 'value' => 0, 'bg_color' => '#059669'],
                ['type' => 'percentage', 'value' => 2000, 'min_purchase' => 5000, 'bg_color' => '#0EA5E9'],
                ['type' => 'fixed_amount', 'value' => 5000, 'min_purchase' => 20000, 'bg_color' => '#7C3AED'],
                ['type' => 'free_product', 'value' => 0, 'bg_color' => '#D97706'],
                ['type' => 'percentage', 'value' => 3000, 'max_discount' => 5000, 'bg_color' => '#0891B2'],
            ],
        ];

        // Get all partners with their clubs
        $partners = Partner::with('clubs')->get();

        foreach ($partners as $partner) {
            // Skip partners without voucher permission (e.g. tier1/Bronze)
            $meta = $partner->meta ?? [];
            if (empty($meta['vouchers_permission'])) {
                continue;
            }

            $businessType = DemoPartnerConfig::businessTypeFor($partner->email);

            // Load voucher content from JSON files
            $directory = database_path('data/demo/'.$businessType.'/vouchers/');
            if (! File::exists($directory)) {
                continue;
            }

            $files = File::files($directory);
            $locales = array_map(fn ($file) => pathinfo($file->getFilename(), PATHINFO_FILENAME), $files);

            if (empty($locales)) {
                continue;
            }

            $vouchers = [];
            foreach ($locales as $locale) {
                $jsonFilePath = database_path('data/demo/'.$businessType.'/vouchers/'.$locale.'.json');
                if (! File::exists($jsonFilePath)) {
                    continue 2;
                }
                $vouchers[$locale] = json_decode(file_get_contents($jsonFilePath), true);
            }

            if (empty($vouchers)) {
                continue;
            }

            // Count demo images for this business type
            $imageDirectory = database_path('data/demo-images/'.$businessType.'/cards/');
            $imageCount = 0;
            if (File::exists($imageDirectory)) {
                foreach (File::files($imageDirectory) as $file) {
                    if ($file->getExtension() == 'jpg') {
                        $imageCount++;
                    }
                }
            }

            // In seed-all-images mode, create one voucher per available image
            $vouchersPerPartner = ($seedAllImages && $imageCount > 0) ? $imageCount : 1;
            $usedKeys = [];
            $usedImageNumbers = [];

            $configs = $voucherConfigs[$businessType] ?? $voucherConfigs['restaurants'];

            foreach ($partner->clubs as $club) {
                if ($club->name === 'Archived') {
                    continue;
                }

                for ($voucherCount = 0; $voucherCount < $vouchersPerPartner; $voucherCount++) {
                    // Use curated key for first voucher, random for seed-all-images extras
                    if ($voucherCount === 0 && ! $seedAllImages) {
                        $curatedKey = DemoPartnerConfig::voucherKeyFor($partner->email);
                        $key = $curatedKey ?? 0;
                    } else {
                        $availableVouchers = count($vouchers[$locales[0]]);
                        if (count($usedKeys) >= $availableVouchers) {
                            $usedKeys = [];
                        }
                        do {
                            $key = array_rand($vouchers[$locales[0]]);
                        } while (in_array($key, $usedKeys) && count($usedKeys) < $availableVouchers);
                    }
                    $usedKeys[] = $key;

                    // Get voucher configuration (matched to content key for consistency)
                    $configIndex = $key % count($configs);
                    $config = $configs[$configIndex];

                    // Build translatable fields
                    $title = [];
                    $description = [];
                    $freeProductName = [];
                    $internalName = null;

                    foreach ($locales as $locale) {
                        $title[$locale] = $vouchers[$locale][$key]['title'];
                        $description[$locale] = $vouchers[$locale][$key]['description'];

                        if (isset($vouchers[$locale][$key]['free_product_name'])) {
                            $freeProductName[$locale] = $vouchers[$locale][$key]['free_product_name'];
                        }

                        if ($locale === 'en_US') {
                            $internalName = $vouchers[$locale][$key]['head'];
                        }
                    }

                    // Visibility driven by explicit demo config, not unconditional
                    $is_visible_by_default = DemoPartnerConfig::isVisibleOnHomepage($partner->email);

                    // Generate unique voucher code
                    $codePrefix = strtoupper(substr($businessType, 0, 3));
                    $code = $codePrefix.strtoupper(substr(md5($internalName.time().rand()), 0, 6));

                    // Get first loyalty card for bonus points vouchers
                    $loyaltyCard = Card::where('club_id', $club->id)->first();

                    // Build voucher data
                    $voucherData = [
                        'club_id' => $club->id,
                        'code' => $code,
                        'name' => $internalName,
                        'title' => $title,
                        'description' => $description,
                        'type' => $config['type'],
                        'value' => $config['value'],
                        'currency' => 'USD',
                        'is_active' => true,
                        'is_public' => true,
                        'is_visible_by_default' => $is_visible_by_default,
                        'is_single_use' => $config['type'] === 'free_product',
                        'is_auto_apply' => false,
                        'stackable' => false,
                        'source' => 'manual',
                        'bg_color' => $config['bg_color'],
                        'bg_color_opacity' => rand(79, 88),
                        'text_color' => '#FFFFFF',
                        'created_by' => $partner->id,
                        'created_at' => $seedAllImages ? Carbon::now()->subMonths(2) : Carbon::now()->subDays(rand(0, 90)),
                        'valid_from' => $seedAllImages ? Carbon::now()->subMonth() : Carbon::now()->subDays(rand(1, 30)),
                        'valid_until' => $seedAllImages ? Carbon::now()->addYears(2) : Carbon::now()->addMonths(rand(6, 24)),
                    ];

                    // Type-specific fields
                    if (isset($config['min_purchase'])) {
                        $voucherData['min_purchase_amount'] = $config['min_purchase'];
                    }
                    if (isset($config['max_discount'])) {
                        $voucherData['max_discount_amount'] = $config['max_discount'];
                    }
                    if ($config['type'] === 'free_product' && ! empty($freeProductName)) {
                        $voucherData['free_product_name'] = $freeProductName;
                    }
                    if (isset($config['points'])) {
                        $voucherData['points_value'] = $config['points'];
                        if ($loyaltyCard) {
                            $voucherData['reward_card_id'] = $loyaltyCard->id;
                        }
                    }

                    // Usage limits
                    $voucherData['max_uses_total'] = rand(100, 1000);
                    $voucherData['max_uses_per_member'] = $config['type'] === 'percentage' ? 3 : 1;

                    // Random usage stats for realism (60% of vouchers have usage)
                    if (rand(0, 100) < 60) {
                        $voucherData['times_used'] = rand(1, 50);
                        $voucherData['unique_members_used'] = rand(1, $voucherData['times_used']);
                        $voucherData['total_discount_given'] = $voucherData['times_used'] * rand(500, 3000);
                    }

                    // Create voucher
                    $voucher = Voucher::create($voucherData);

                    // Add background image
                    if ($imageCount > 0) {
                        if ($seedAllImages) {
                            $imageNumber = $voucherCount + 1;
                        } else {
                            $imageNumber = $this->getUniqueRandomNumber($usedImageNumbers, $imageCount);
                        }
                        $backgroundPath = $imageDirectory.$imageNumber.'.jpg';

                        if (File::exists($backgroundPath)) {
                            $voucher
                                ->addMedia($backgroundPath)
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
