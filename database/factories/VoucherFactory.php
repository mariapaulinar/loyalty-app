<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace Database\Factories;

use App\Models\Club;
use App\Models\Member;
use App\Models\Partner;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Voucher>
 */
class VoucherFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Voucher::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'code' => strtoupper(fake()->bothify('????####')),
            'name' => fake()->words(3, true).' Voucher',
            'title' => ['en' => fake()->sentence(3)],
            'description' => ['en' => fake()->sentence()],
            'type' => 'percentage',
            'value' => fake()->numberBetween(10, 50) * 100, // Minor units: 1000-5000 = 10%-50%
            'currency' => 'USD',
            'min_purchase_amount' => null,
            'max_discount_amount' => null,
            'valid_from' => now(),
            'valid_until' => now()->addDays(30),
            'max_uses_total' => null,
            'max_uses_per_member' => null,
            'is_active' => true,
            'is_public' => false,
            'is_single_use' => false,
            'is_auto_apply' => false,
            'stackable' => false,
            'source' => 'manual',
            'times_used' => 0,
            'total_discount_given' => 0,
            'unique_members_used' => 0,
            'bg_color' => '#7C3AED',
            'text_color' => '#FFFFFF',
            'created_by' => Partner::factory(),
        ];
    }

    /**
     * Indicate that the voucher is a percentage discount.
     *
     * @param  int  $value  Percentage as whole number (20 = 20%). Stored as minor units (20 → 2000).
     */
    public function percentage(int $value = 20): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'percentage',
            'value' => $value * 100, // Convert to minor units: 20% → 2000
        ]);
    }

    /**
     * Indicate that the voucher is a fixed amount discount.
     */
    public function fixedAmount(int $amountInCents = 1000): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed_amount',
            'value' => $amountInCents,
        ]);
    }

    /**
     * Indicate that the voucher awards bonus points.
     */
    public function bonusPoints(int $points = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bonus_points',
            'value' => 0,
            'points_value' => $points,
        ]);
    }

    /**
     * Indicate that the voucher provides free shipping.
     */
    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'free_shipping',
            'value' => 0,
        ]);
    }

    /**
     * Indicate that the voucher provides a free product.
     */
    public function freeProduct(string $productName = 'Free Coffee'): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'free_product',
            'value' => 0,
            'free_product_name' => $productName,
        ]);
    }

    /**
     * Indicate that the voucher is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Indicate that the voucher is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the voucher is single use.
     */
    public function singleUse(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_single_use' => true,
            'max_uses_per_member' => 1,
        ]);
    }

    /**
     * Indicate that the voucher has a usage limit.
     */
    public function withUsageLimit(int $total, ?int $perMember = null): static
    {
        return $this->state(fn (array $attributes) => [
            'max_uses_total' => $total,
            'max_uses_per_member' => $perMember,
        ]);
    }

    /**
     * Indicate that the voucher has a minimum purchase requirement.
     */
    public function withMinimumPurchase(int $amountInCents): static
    {
        return $this->state(fn (array $attributes) => [
            'min_purchase_amount' => $amountInCents,
        ]);
    }

    /**
     * Indicate that the voucher has a maximum discount cap.
     */
    public function withDiscountCap(int $capInCents): static
    {
        return $this->state(fn (array $attributes) => [
            'max_discount_amount' => $capInCents,
        ]);
    }

    /**
     * Indicate that the voucher is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_from' => now()->subDays(60),
            'valid_until' => now()->subDays(30),
        ]);
    }

    /**
     * Indicate that the voucher is not yet valid.
     */
    public function notYetValid(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_from' => now()->addDays(7),
            'valid_until' => now()->addDays(30),
        ]);
    }

    /**
     * Indicate that the voucher is exhausted (reached max uses).
     */
    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_uses_total' => 100,
            'times_used' => 100,
        ]);
    }

    /**
     * Indicate that the voucher targets a specific member.
     */
    public function forMember(Member $member): static
    {
        return $this->state(fn (array $attributes) => [
            'target_member_id' => $member->id,
        ]);
    }

    /**
     * Indicate that the voucher is for first orders only.
     */
    public function firstOrderOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_order_only' => true,
        ]);
    }

    /**
     * Indicate that the voucher is for new members only.
     */
    public function newMembersOnly(int $days = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'new_members_only' => true,
            'new_members_days' => $days,
        ]);
    }

    /**
     * Indicate that the voucher auto-applies.
     */
    public function autoApply(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_auto_apply' => true,
        ]);
    }

    /**
     * Indicate that the voucher is stackable.
     */
    public function stackable(): static
    {
        return $this->state(fn (array $attributes) => [
            'stackable' => true,
        ]);
    }

    /**
     * Indicate that the voucher has been used N times.
     */
    public function used(int $times = 1, int $discountGiven = 1000): static
    {
        return $this->state(fn (array $attributes) => [
            'times_used' => $times,
            'total_discount_given' => $discountGiven * $times,
            'unique_members_used' => min($times, $attributes['max_uses_per_member'] ?? $times),
        ]);
    }
}
