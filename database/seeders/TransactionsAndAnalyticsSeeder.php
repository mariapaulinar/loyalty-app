<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Seeds demo loyalty card transactions across ALL partners,
 * proving the shared wallet architecture. Members earn points
 * at multiple businesses, not just the primary demo partner.
 *
 * Each member interacts with 2–3 cards chosen from the full
 * card pool (spanning multiple partners). The staff member
 * used for each transaction is resolved per-card from that
 * card's owning partner.
 */

namespace Database\Seeders;

use App\Models\Analytic;
use App\Models\Card;
use App\Models\Member;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TransactionsAndAnalyticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transactionService = app(\App\Services\Card\TransactionService::class);

        // Load ALL cards across ALL partners — not restricted to a single staff/partner.
        // Each card's club is eager-loaded so we can query per-club staff.
        $cards = Card::query()
            ->with(['club', 'rewards'])
            ->get();

        $members = Member::all();

        if ($cards->isEmpty() || $members->isEmpty()) {
            $this->command?->warn('No cards or members found. Skipping transactions seeder.');

            return;
        }

        // Group cards by partner for deterministic cross-partner seeding
        $cardsByPartner = $cards->groupBy('created_by');

        foreach ($members as $index => $member) {
            if ($index === 0) {
                // Primary member (Emma): deterministically pick one card from EACH partner.
                // This guarantees the shared wallet demo contract.
                $cardsToInteract = $cardsByPartner->map(fn ($group) => $group->first())->values();
            } else {
                // Other members: random sample of 2–3 cards (natural analytics spread)
                $numCards = min(mt_rand(2, 3), $cards->count());
                $cardsToInteract = $cards->random($numCards);
            }
            foreach ($cardsToInteract as $card) {
                $club = $card->getRelation('club');
                if (! $club) {
                    continue;
                }

                $partner = $card->created_by;

                // Resolve staff for this club (with partner+club relations to satisfy isRelatedToCard)
                $staff = Staff::with(['partner', 'club'])
                    ->where('club_id', $club->id)
                    ->first();
                if (! $staff) {
                    continue;
                }

                $partnerId = $staff->created_by;

                // ── Card & reward view analytics ─────────────────────────────
                $startDate = fake()->dateTimeBetween('-120 days', '-120 days')->format('Y-m-d H:i:s');
                $endDate = Carbon::now();

                // Card views
                $visits = mt_rand(15, 25);
                for ($i = 0; $i < $visits; $i++) {
                    $interactionDate = fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d H:i:s');
                    Analytic::create([
                        'partner_id' => $partnerId,
                        'member_id' => $member->id,
                        'staff_id' => null,
                        'card_id' => $card->id,
                        'reward_id' => null,
                        'event' => 'card_view',
                        'locale' => $member->locale,
                        'created_at' => $interactionDate,
                    ]);
                    $card->increment('views');
                    $card->where('id', $card->id)->update(['last_view' => Carbon::now('UTC')]);
                }

                // Reward views
                $visits = mt_rand(20, 30);
                for ($i = 0; $i < $visits; $i++) {
                    $rewards = $card->rewards()->inRandomOrder()->limit(4)->get();
                    foreach ($rewards as $reward) {
                        $interactionDate = fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d H:i:s');
                        Analytic::create([
                            'partner_id' => $partnerId,
                            'member_id' => $member->id,
                            'staff_id' => null,
                            'card_id' => $card->id,
                            'reward_id' => $reward->id,
                            'event' => 'reward_view',
                            'locale' => $member->locale,
                            'created_at' => $interactionDate,
                        ]);
                        $reward->increment('views');
                        $reward->where('id', $reward->id)->update(['last_view' => Carbon::now('UTC')]);
                    }
                }

                // ── Historical point-earning (older) ─────────────────────────
                $startDate = fake()->dateTimeBetween('-120 days', '-100 days')->format('Y-m-d H:i:s');
                $endDate = fake()->dateTimeBetween('-7 days', '-7 days')->format('Y-m-d H:i:s');

                $interactions = mt_rand(5, 8);
                for ($i = 0; $i < $interactions; $i++) {
                    $purchase_amount = $this->randomPurchaseAmount(1.5, 200);
                    $interactionDate = ($i == 0) ? $startDate : fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d H:i:s');
                    $transactionService->addPurchase($member->unique_identifier, $card->unique_identifier, $staff, $purchase_amount, null, null, null, false, $interactionDate);
                }

                // ── Recent point-earning (last 7 days) ───────────────────────
                $startDate = fake()->dateTimeBetween('-7 days', '-7 days')->format('Y-m-d H:i:s');
                $endDate = fake()->dateTimeBetween('-1 days', '-1 days')->format('Y-m-d H:i:s');

                $interactions = mt_rand(2, 4);
                for ($i = 0; $i < $interactions; $i++) {
                    $purchase_amount = $this->randomPurchaseAmount(1.5, 100);
                    $interactionDate = ($i == 0) ? $startDate : fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d H:i:s');
                    $transactionService->addPurchase($member->unique_identifier, $card->unique_identifier, $staff, $purchase_amount, null, null, null, false, $interactionDate);
                }

                // ── Current-month point-earning (growth trend) ───────────────
                $currentMonth = Carbon::now()->startOfMonth();
                $yesterday = Carbon::now()->subDay();
                if ($yesterday->lt($currentMonth)) {
                    $yesterday = Carbon::now();
                }

                $recentInteractions = mt_rand(4, 8);
                for ($i = 0; $i < $recentInteractions; $i++) {
                    $purchase_amount = $this->randomPurchaseAmount(5, 250);
                    $interactionDate = fake()->dateTimeBetween($currentMonth, $yesterday)->format('Y-m-d H:i:s');
                    $transactionService->addPurchase($member->unique_identifier, $card->unique_identifier, $staff, $purchase_amount, null, null, null, false, $interactionDate);
                }

                // ── Today's transactions ─────────────────────────────────────
                $todayInteractions = mt_rand(1, 2);
                for ($i = 0; $i < $todayInteractions; $i++) {
                    $purchase_amount = $this->randomPurchaseAmount(10, 150);
                    $todayDate = Carbon::now()->subHours(mt_rand(1, 8))->format('Y-m-d H:i:s');
                    $transactionService->addPurchase($member->unique_identifier, $card->unique_identifier, $staff, $purchase_amount, null, null, null, false, $todayDate);
                }

                // ── Reward claims ────────────────────────────────────────────
                $startDate = fake()->dateTimeBetween('-80 days', '-2 days')->format('Y-m-d H:i:s');
                $endDate = Carbon::now();

                $interactions = mt_rand(1, 2);
                for ($i = 0; $i < $interactions; $i++) {
                    $interactionDate = fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d H:i:s');
                    $reward = $card->rewards()->inRandomOrder()->first();
                    if (! $reward) {
                        continue;
                    }
                    $transactionService->claimReward($card->id, $reward->id, $member->unique_identifier, $staff, null, null, $interactionDate);
                }
            }
        }
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
