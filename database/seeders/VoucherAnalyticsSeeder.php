<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Seeds demo voucher analytics with realistic view data AND redemptions.
 * Uses same design approach as stamp cards for consistency.
 */

namespace Database\Seeders;

use App\Models\Analytic;
use App\Models\Member;
use App\Models\Staff;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class VoucherAnalyticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vouchers = Voucher::with('club.partner')->get();
        $members = Member::all();

        $this->command->info("Found {$vouchers->count()} vouchers and {$members->count()} members");

        if ($vouchers->isEmpty()) {
            $this->command->warn('No vouchers found. Skipping voucher analytics seeder.');

            return;
        }

        if ($members->isEmpty()) {
            $this->command->warn('No members found. Skipping voucher analytics seeder.');

            return;
        }

        $totalViews = 0;
        $totalRedemptions = 0;

        // Get demo staff for redemptions
        $demoStaff = Staff::query()->where('email', 'staff@example.com')->first();

        // Each member views only 2-3 random vouchers (not all vouchers!)
        foreach ($members as $member) {
            // Randomly select 2-3 vouchers for this member to view (or fewer if not enough vouchers)
            $numVouchers = min(mt_rand(2, 3), $vouchers->count());
            $vouchersToView = $vouchers->random($numVouchers);

            foreach ($vouchersToView as $voucher) {
                // Voucher partner (accessed through club)
                $partner = $voucher->club?->partner;

                // Skip if no partner
                if (! $partner) {
                    $this->command->warn("Voucher {$voucher->name} has no partner. Skipping.");

                    continue;
                }

                // Voucher views - 15-30 views over the last 120 days (increased for more activity)
                $startDate = fake()->dateTimeBetween('-120 days', '-120 days')->format('Y-m-d H:i:s');
                $endDate = Carbon::now();

                $visits = mt_rand(15, 30);
                for ($i = 0; $i < $visits; $i++) {
                    $interactionDate = fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d H:i:s');
                    Analytic::create([
                        'partner_id' => $partner->id,
                        'member_id' => $member->id,
                        'staff_id' => null,
                        'card_id' => null,
                        'reward_id' => null,
                        'stamp_card_id' => null,
                        'voucher_id' => $voucher->id,
                        'event' => 'voucher_view',
                        'locale' => $member->locale,
                        'created_at' => $interactionDate,
                    ]);
                    $voucher->increment('views');
                    $voucher->where('id', $voucher->id)->update(['last_view' => Carbon::now('UTC')]);
                    $totalViews++;
                }

                // Add RECENT views (last 7 days) so charts show with "This Week" range
                $recentViews = mt_rand(5, 10);
                for ($i = 0; $i < $recentViews; $i++) {
                    $recentDate = fake()->dateTimeBetween('-7 days', 'now')->format('Y-m-d H:i:s');
                    Analytic::create([
                        'partner_id' => $partner->id,
                        'member_id' => $member->id,
                        'staff_id' => null,
                        'card_id' => null,
                        'reward_id' => null,
                        'stamp_card_id' => null,
                        'voucher_id' => $voucher->id,
                        'event' => 'voucher_view',
                        'locale' => $member->locale,
                        'created_at' => $recentDate,
                    ]);
                    $voucher->increment('views');
                    $voucher->where('id', $voucher->id)->update(['last_view' => Carbon::now('UTC')]);
                    $totalViews++;
                }

                // Add views for TODAY to keep analytics alive (even on Sundays!)
                $todayViews = mt_rand(2, 4);
                for ($i = 0; $i < $todayViews; $i++) {
                    $todayDate = Carbon::now()->subHours(mt_rand(1, 10))->format('Y-m-d H:i:s');
                    Analytic::create([
                        'partner_id' => $partner->id,
                        'member_id' => $member->id,
                        'staff_id' => null,
                        'card_id' => null,
                        'reward_id' => null,
                        'stamp_card_id' => null,
                        'voucher_id' => $voucher->id,
                        'event' => 'voucher_view',
                        'locale' => $member->locale,
                        'created_at' => $todayDate,
                    ]);
                    $voucher->increment('views');
                    $voucher->where('id', $voucher->id)->update(['last_view' => Carbon::now('UTC')]);
                    $totalViews++;
                }

                // Voucher REDEMPTIONS - Some members redeem vouchers!
                // Not all views convert to redemptions (realistic conversion rate)
                $shouldRedeem = mt_rand(1, 100) <= 30; // 30% chance of redemption
                
                // Skip if max_uses_per_member is explicitly set to 0 (no redemptions allowed)
                if ($shouldRedeem && $voucher->max_uses_per_member !== 0) {
                    $staff = $demoStaff;
                    if (! $staff) {
                        $staff = $partner->staff()->inRandomOrder()->first();
                    }
                    
                    if ($staff) {
                        // Redemptions happen across different time periods for variety
                        // Limit redemptions to max_uses_per_member (or 3 if unlimited/null)
                        $maxAllowed = $voucher->max_uses_per_member ?? 3;
                        $maxRedemptions = min(mt_rand(1, 3), $maxAllowed);
                        
                        for ($i = 0; $i < $maxRedemptions; $i++) {
                            // Spread redemptions over last 90 days with more recent activity
                            $redemptionDate = fake()->dateTimeBetween('-90 days', 'now');
                            
                            // Determine discount/points based on voucher type
                            $discountAmount = 0;
                            $pointsAwarded = 0;
                            $orderAmount = 0;
                            
                            if ($voucher->type === 'percentage' && $voucher->value) {
                                // Simulate order amount for percentage discounts
                                $orderAmount = mt_rand(2000, 15000); // $20-$150
                                $discountAmount = (int) round(($voucher->value / 100) * $orderAmount);
                            } elseif ($voucher->type === 'fixed_amount' && $voucher->value) {
                                $orderAmount = mt_rand(5000, 20000); // $50-$200
                                $discountAmount = $voucher->value;
                            } elseif ($voucher->type === 'bonus_points' && $voucher->points_value) {
                                $pointsAwarded = $voucher->points_value;
                            }
                            
                            $finalAmount = $orderAmount > 0 ? max(0, $orderAmount - $discountAmount) : null;
                            
                            // Create redemption record with backdated timestamp
                            VoucherRedemption::create([
                                'voucher_id' => $voucher->id,
                                'member_id' => $member->id,
                                'staff_id' => $staff->id,
                                'location_id' => null,
                                'discount_amount' => $discountAmount,
                                'points_awarded' => $pointsAwarded,
                                'original_amount' => $orderAmount > 0 ? $orderAmount : null,
                                'final_amount' => $finalAmount,
                                'order_reference' => 'DEMO-' . strtoupper(substr(md5((string) rand()), 0, 8)),
                                'currency' => $voucher->currency ?? $partner->currency ?? config('default.currency'),
                                'status' => 'completed',
                                'redeemed_at' => $redemptionDate,
                                'created_at' => $redemptionDate,
                                'updated_at' => $redemptionDate,
                            ]);
                            
                            $totalRedemptions++;
                        }
                        
                        // Update voucher redemption count
                        $voucher->increment('times_used', $maxRedemptions);
                    }
                }
            }
        }

        $this->command->info("Created {$totalViews} voucher views and {$totalRedemptions} redemptions");
    }
}

