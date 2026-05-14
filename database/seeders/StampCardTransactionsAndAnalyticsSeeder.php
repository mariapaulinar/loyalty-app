<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Seeds demo stamp card transactions across ALL partners.
 * Same cross-partner design as TransactionsAndAnalyticsSeeder —
 * members collect stamps at multiple businesses.
 */

namespace Database\Seeders;

use App\Models\Analytic;
use App\Models\Member;
use App\Models\Staff;
use App\Models\StampCard;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class StampCardTransactionsAndAnalyticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stampService = app(\App\Services\StampService::class);

        // Load ALL stamp cards across ALL partners — not restricted to a single staff/partner.
        $stampCards = StampCard::query()
            ->with('club')
            ->get();

        $members = Member::all();

        $this->command?->info("Found {$stampCards->count()} stamp cards and {$members->count()} members");

        if ($stampCards->isEmpty() || $members->isEmpty()) {
            $this->command?->warn('No stamp cards or members found. Skipping.');

            return;
        }

        $totalTransactions = 0;
        $totalViews = 0;

        // Group stamp cards by partner (via club) for deterministic cross-partner seeding
        $cardsByPartner = $stampCards->groupBy(fn ($card) => $card->club?->created_by);

        foreach ($members as $index => $member) {
            if ($index === 0) {
                // Primary member: deterministically pick one stamp card from EACH partner.
                $cardsToInteract = $cardsByPartner->map(fn ($group) => $group->first())->values();
            } else {
                // Other members: random sample of 2–3 cards
                $numCards = min(mt_rand(2, 3), $stampCards->count());
                $cardsToInteract = $stampCards->random($numCards);
            }
            foreach ($cardsToInteract as $stampCard) {
                $club = $stampCard->club;
                if (! $club) {
                    continue;
                }

                // Resolve staff for this club (with partner+club relations for isRelatedToCard)
                $staff = Staff::with(['partner', 'club'])
                    ->where('club_id', $club->id)
                    ->first();
                if (! $staff) {
                    continue;
                }

                $partner = $staff->partner;

                // ── Stamp card view analytics ────────────────────────────────
                $startDate = fake()->dateTimeBetween('-120 days', '-120 days')->format('Y-m-d H:i:s');
                $endDate = Carbon::now();

                $visits = mt_rand(15, 25);
                for ($i = 0; $i < $visits; $i++) {
                    $interactionDate = fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d H:i:s');
                    Analytic::create([
                        'partner_id' => $partner->id,
                        'member_id' => $member->id,
                        'staff_id' => null,
                        'card_id' => null,
                        'reward_id' => null,
                        'stamp_card_id' => $stampCard->id,
                        'event' => 'stamp_card_view',
                        'locale' => $member->locale,
                        'created_at' => $interactionDate,
                    ]);
                    $stampCard->increment('views');
                    $stampCard->where('id', $stampCard->id)->update(['last_view' => Carbon::now('UTC')]);
                    $totalViews++;
                }

                // Recent views (last 7 days)
                $recentViews = mt_rand(5, 10);
                for ($i = 0; $i < $recentViews; $i++) {
                    $recentDate = fake()->dateTimeBetween('-7 days', 'now')->format('Y-m-d H:i:s');
                    Analytic::create([
                        'partner_id' => $partner->id,
                        'member_id' => $member->id,
                        'staff_id' => null,
                        'card_id' => null,
                        'reward_id' => null,
                        'stamp_card_id' => $stampCard->id,
                        'event' => 'stamp_card_view',
                        'locale' => $member->locale,
                        'created_at' => $recentDate,
                    ]);
                    $stampCard->increment('views');
                    $stampCard->where('id', $stampCard->id)->update(['last_view' => Carbon::now('UTC')]);
                    $totalViews++;
                }

                // Today's views
                $todayViews = mt_rand(2, 4);
                for ($i = 0; $i < $todayViews; $i++) {
                    $todayDate = Carbon::now()->subHours(mt_rand(1, 10))->format('Y-m-d H:i:s');
                    Analytic::create([
                        'partner_id' => $partner->id,
                        'member_id' => $member->id,
                        'staff_id' => null,
                        'card_id' => null,
                        'reward_id' => null,
                        'stamp_card_id' => $stampCard->id,
                        'event' => 'stamp_card_view',
                        'locale' => $member->locale,
                        'created_at' => $todayDate,
                    ]);
                    $stampCard->increment('views');
                    $stampCard->where('id', $stampCard->id)->update(['last_view' => Carbon::now('UTC')]);
                    $totalViews++;
                }

                // ── Stamp-earning transactions ───────────────────────────────
                $startDate = fake()->dateTimeBetween('-120 days', '-100 days')->format('Y-m-d H:i:s');
                $endDate = fake()->dateTimeBetween('-7 days', '-7 days')->format('Y-m-d H:i:s');

                $interactions = mt_rand(5, 8);
                for ($i = 0; $i < $interactions; $i++) {
                    $purchase_amount = $this->randomPurchaseAmount(1.5, 200);
                    $interactionDate = ($i == 0) ? $startDate : fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d H:i:s');

                    if ($stampCard->min_purchase_amount === null || $purchase_amount >= $stampCard->min_purchase_amount) {
                        $result = $stampService->addStamp(
                            card: $stampCard,
                            member: $member,
                            staff: $staff,
                            stamps: 1,
                            purchaseAmount: $purchase_amount,
                            note: null,
                            createdAt: $interactionDate
                        );

                        if ($result['success']) {
                            $totalTransactions++;
                        }
                    }
                }

                // Recent stamps (last 7 days)
                $startDate = fake()->dateTimeBetween('-7 days', '-7 days')->format('Y-m-d H:i:s');
                $endDate = fake()->dateTimeBetween('-1 days', '-1 days')->format('Y-m-d H:i:s');

                $interactions = mt_rand(2, 4);
                for ($i = 0; $i < $interactions; $i++) {
                    $purchase_amount = $this->randomPurchaseAmount(1.5, 100);
                    $interactionDate = ($i == 0) ? $startDate : fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d H:i:s');

                    if ($stampCard->min_purchase_amount === null || $purchase_amount >= $stampCard->min_purchase_amount) {
                        $result = $stampService->addStamp(
                            card: $stampCard,
                            member: $member,
                            staff: $staff,
                            stamps: 1,
                            purchaseAmount: $purchase_amount,
                            note: null,
                            createdAt: $interactionDate
                        );

                        if ($result['success']) {
                            $totalTransactions++;
                        }
                    }
                }

                // ── Reward redemptions ────────────────────────────────────────
                $enrollment = $stampService->getMemberProgress($stampCard, $member);

                if ($enrollment && $enrollment->pending_rewards > 0) {
                    $startDate = fake()->dateTimeBetween('-80 days', '-2 days')->format('Y-m-d H:i:s');
                    $endDate = Carbon::now();

                    $redemptions = min(mt_rand(1, 2), $enrollment->pending_rewards);
                    for ($i = 0; $i < $redemptions; $i++) {
                        $interactionDate = fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d H:i:s');

                        $stampService->redeemReward(
                            card: $stampCard,
                            member: $member,
                            staff: $staff,
                            createdAt: $interactionDate
                        );
                    }
                }
            }
        }

        $this->command?->info('Stamp card transactions seeded successfully!');
        $this->command?->info("Total views created: {$totalViews}");
        $this->command?->info("Total transactions created: {$totalTransactions}");
    }

    /**
     * Generate a random purchase amount on a $step grid.
     */
    private function randomPurchaseAmount(float $min, float $max, float $step = 1.5): float
    {
        $minSteps = (int) ceil($min / $step);
        $maxSteps = (int) floor($max / $step);
        $steps = random_int($minSteps, $maxSteps);

        return $steps * $step;
    }
}
