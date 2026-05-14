<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace Database\Factories;

use App\Models\Card;
use App\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Card>
 */
class CardFactory extends Factory
{
    protected $model = Card::class;

    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'name' => fake()->words(3, true).' Loyalty Card',
            'type' => 'points',
            'currency' => 'USD',
            'points_per_currency' => 1,
            'min_points_per_purchase' => null,
            'max_points_per_purchase' => null,
            'is_active' => true,
            'is_visible_by_default' => true,
        ];
    }

    public function withInitialPoints(int $points): static
    {
        return $this->state(fn (array $attributes) => [
            'initial_bonus_points' => $points,
        ]);
    }
}
