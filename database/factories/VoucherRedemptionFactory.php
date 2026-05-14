<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace Database\Factories;

use App\Models\Member;
use App\Models\Staff;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VoucherRedemption>
 */
class VoucherRedemptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VoucherRedemption::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalAmount = fake()->numberBetween(5000, 20000); // $50-$200
        $discountAmount = fake()->numberBetween(500, 5000); // $5-$50
        $finalAmount = max(0, $originalAmount - $discountAmount);

        return [
            'voucher_id' => Voucher::factory(),
            'member_id' => Member::factory(),
            'staff_id' => null, // Self-service by default
            'location_id' => null,
            'order_id' => null,
            'order_reference' => 'ORD-'.strtoupper(fake()->bothify('??????##')),
            'discount_amount' => $discountAmount,
            'original_amount' => $originalAmount,
            'final_amount' => $finalAmount,
            'currency' => 'USD',
            'points_awarded' => null,
            'transaction_id' => null,
            'status' => 'completed',
            'redeemed_at' => now(),
        ];
    }

    /**
     * Indicate that the redemption was processed by staff.
     */
    public function byStaff(?Staff $staff = null): static
    {
        return $this->state(fn (array $attributes) => [
            'staff_id' => $staff?->id ?? Staff::factory(),
        ]);
    }

    /**
     * Indicate that the redemption awarded points.
     */
    public function withPoints(int $points = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'points_awarded' => $points,
        ]);
    }

    /**
     * Indicate that the redemption is voided.
     */
    public function voided(string $reason = 'Customer refund', ?Staff $staff = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'voided',
            'voided_at' => now(),
            'voided_by' => $staff?->id ?? Staff::factory(),
            'void_reason' => $reason,
        ]);
    }

    /**
     * Indicate that the redemption is applied but not completed.
     */
    public function applied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'applied',
        ]);
    }

    /**
     * Indicate specific discount amount.
     */
    public function withDiscount(int $discountInCents): static
    {
        return $this->state(function (array $attributes) use ($discountInCents) {
            $originalAmount = $attributes['original_amount'] ?? 10000;

            return [
                'discount_amount' => $discountInCents,
                'final_amount' => max(0, $originalAmount - $discountInCents),
            ];
        });
    }

    /**
     * Indicate specific order amount.
     */
    public function withOrderAmount(int $amountInCents): static
    {
        return $this->state(function (array $attributes) use ($amountInCents) {
            $discountAmount = $attributes['discount_amount'] ?? 1000;

            return [
                'original_amount' => $amountInCents,
                'final_amount' => max(0, $amountInCents - $discountAmount),
            ];
        });
    }

    /**
     * Indicate redemption from a specific date.
     */
    public function redeemedAt(\DateTimeInterface $date): static
    {
        return $this->state(fn (array $attributes) => [
            'redeemed_at' => $date,
            'created_at' => $date,
        ]);
    }
}
