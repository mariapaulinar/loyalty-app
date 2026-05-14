<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace Database\Factories;

use App\Models\Club;
use App\Models\Partner;
use App\Models\StampCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StampCard>
 */
class StampCardFactory extends Factory
{
    protected $model = StampCard::class;

    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'name' => fake()->words(3, true).' Card',
            'title' => ['en' => fake()->sentence(3)],
            'description' => ['en' => fake()->sentence()],
            'reward_title' => ['en' => 'Free '.fake()->word()],
            'reward_description' => ['en' => fake()->sentence()],
            'stamps_required' => 10,
            'stamps_per_purchase' => 1,
            'is_active' => true,
            'is_visible_by_default' => false,
            'is_undeletable' => false,
            'requires_physical_claim' => false,
            'show_monetary_value' => false,
            'bg_color' => '#1F2937',
            'bg_color_opacity' => 75,
            'text_color' => '#FFFFFF',
            'stamp_color' => '#10B981',
            'empty_stamp_color' => '#4B5563',
            'stamp_icon' => '☕',
            'total_stamps_issued' => 0,
            'total_completions' => 0,
            'total_redemptions' => 0,
            'created_by' => Partner::factory(),
        ];
    }

    /**
     * Card with a specific number of required stamps.
     */
    public function stampsRequired(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'stamps_required' => $count,
        ]);
    }

    /**
     * Inactive card.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Card visible on homepage.
     */
    public function visible(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible_by_default' => true,
        ]);
    }

    /**
     * Card with auto-enrollment enabled.
     *
     * Note: is_auto_enroll column is planned but not yet in the migration.
     * Currently, StampService always auto-enrolls on first stamp.
     * This state is a no-op placeholder for when the column is added.
     */
    public function autoEnroll(): static
    {
        return $this->state(fn (array $attributes) => [
            // is_auto_enroll column pending migration - no-op for now
        ]);
    }

    /**
     * Expired card.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_from' => now()->subDays(60),
            'valid_until' => now()->subDays(30),
        ]);
    }

    /**
     * Card with daily stamp limit.
     */
    public function withDailyLimit(int $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'max_stamps_per_day' => $limit,
        ]);
    }

    /**
     * Card with per-transaction stamp limit.
     */
    public function withTransactionLimit(int $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'max_stamps_per_transaction' => $limit,
        ]);
    }

    /**
     * Card with minimum purchase requirement.
     */
    public function withMinimumPurchase(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'min_purchase_amount' => $amount,
        ]);
    }

    /**
     * Card requiring staff confirmation for redemption.
     */
    public function requiresStaff(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_physical_claim' => true,
        ]);
    }

    /**
     * Card with stamp expiration.
     */
    public function withExpiration(int $days = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'stamps_expire_days' => $days,
        ]);
    }
}
