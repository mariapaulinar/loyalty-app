<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Factory for creating Activity model instances for testing.
 */

namespace Database\Factories;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Activity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $events = ['created', 'updated', 'deleted', 'login', 'logout'];
        $logNames = ['default', 'authentication', 'cards', 'members', 'partners', 'staff'];

        return [
            'id' => Str::uuid()->toString(),
            'log_name' => $this->faker->randomElement($logNames),
            'description' => $this->faker->sentence(),
            'event' => $this->faker->randomElement($events),
            'properties' => [],
            'batch_uuid' => null,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the activity is an authentication event.
     */
    public function authentication(): static
    {
        return $this->state(fn (array $attributes) => [
            'log_name' => 'authentication',
            'event' => $this->faker->randomElement(['login', 'logout', 'login_failed']),
        ]);
    }

    /**
     * Indicate that the activity is a model creation event.
     */
    public function created(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'created',
        ]);
    }

    /**
     * Indicate that the activity is a model update event.
     */
    public function updated(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'updated',
            'properties' => [
                'old' => ['name' => 'Old Value'],
                'attributes' => ['name' => 'New Value'],
            ],
        ]);
    }

    /**
     * Indicate that the activity is old (for cleanup testing).
     */
    public function old(int $days = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subDays($days),
            'updated_at' => now()->subDays($days),
        ]);
    }
}
