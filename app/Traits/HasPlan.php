<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * HasPlan Trait
 *
 * Provides plan-based functionality for models (primarily Partner).
 * Plans are config-based (config/plans.php) - no database table needed.
 *
 * Features:
 * - Plan configuration access
 * - Limit checking (max_clubs, max_members, etc.)
 * - Plan comparison (hasPlanOrHigher)
 * - Pricing utilities
 * - Upgrade options
 */

namespace App\Traits;

trait HasPlan
{
    /**
     * Get current plan config.
     */
    public function getPlanConfig(): array
    {
        $config = config("plans.{$this->plan}", []);

        if (empty($config)) {
            return static::getDefaultPlanConfig();
        }

        return array_merge(['key' => $this->plan], $config);
    }

    /**
     * Get default plan config.
     */
    public static function getDefaultPlanConfig(): array
    {
        foreach (config('plans', []) as $key => $plan) {
            if ($plan['is_default'] ?? false) {
                return array_merge(['key' => $key], $plan);
            }
        }

        return array_merge(['key' => 'tier1'], config('plans.tier1', []));
    }

    /**
     * Get default plan key.
     */
    public static function getDefaultPlan(): string
    {
        foreach (config('plans', []) as $key => $plan) {
            if ($plan['is_default'] ?? false) {
                return $key;
            }
        }

        return 'tier1';
    }

    /**
     * Derive meta permissions/limits from a plan config.
     *
     * This is the single source of truth for plan→meta mapping. All partner
     * creation paths (admin CRUD, self-registration, seeders) must use this
     * method to ensure consistent entitlement enforcement.
     *
     * @param  string  $planSlug  Plan key from config/plans.php (e.g. 'tier1', 'tier3')
     * @param  array   $overrides  Additional meta keys to merge (non-plan keys preserved)
     * @return array   Meta array ready to assign to Partner::meta
     */
    public static function deriveMetaFromPlan(string $planSlug, array $overrides = []): array
    {
        $planConfig = config("plans.{$planSlug}", config('plans.tier1'));

        $meta = [
            'cards_on_homepage' => $planConfig['has_cards_on_homepage'] ?? false,
            'loyalty_cards_permission' => true,
            'loyalty_cards_limit' => $planConfig['max_cards'] ?? 1,
            'rewards_limit' => $planConfig['max_rewards'] ?? 3,
            'stamp_cards_permission' => true,
            'stamp_cards_limit' => $planConfig['max_stamp_cards'] ?? $planConfig['max_cards'] ?? 1,
            'staff_members_limit' => $planConfig['max_staff'] ?? 1,
            'vouchers_permission' => $planConfig['has_vouchers'] ?? false,
            'voucher_batches_permission' => $planConfig['has_voucher_batches'] ?? false,
            'vouchers_limit' => $planConfig['max_vouchers'] ?? (($planConfig['has_vouchers'] ?? false) ? ($planConfig['max_cards'] ?? 1) : 0),
            'email_campaigns_permission' => $planConfig['has_email_campaigns'] ?? false,
            'activity_permission' => $planConfig['has_activity_log'] ?? false,
            'agent_api_permission' => $planConfig['has_agent_api'] ?? false,
            'agent_keys_limit' => $planConfig['max_agent_keys'] ?? 0,
        ];

        return array_merge($meta, $overrides);
    }

    /**
     * Synchronize this partner's meta with their assigned plan.
     *
     * Merges plan-derived values into existing meta. Admin overrides
     * applied after this call take precedence (standard merge order).
     * Saves quietly to avoid recursive events.
     */
    public function syncMetaFromPlan(): void
    {
        $planSlug = $this->plan ?? static::getDefaultPlan();
        $planMeta = static::deriveMetaFromPlan($planSlug);
        $this->meta = array_merge($this->meta ?? [], $planMeta);
        $this->saveQuietly();
    }

    /**
     * Get plan display name.
     */
    public function getPlanName(): string
    {
        return $this->getPlanConfig()['name'] ?? 'Bronze';
    }

    /**
     * Get plan description.
     */
    public function getPlanDescription(): string
    {
        return $this->getPlanConfig()['description'] ?? '';
    }

    /**
     * Get a limit value (-1 = unlimited).
     */
    public function getLimit(string $key): int
    {
        $key = str_starts_with($key, 'max_') ? $key : "max_{$key}";

        return $this->getPlanConfig()[$key] ?? 0;
    }

    /**
     * Check if within limit (-1 = unlimited).
     */
    public function withinLimit(string $key, int $current): bool
    {
        $max = $this->getLimit($key);

        return $max === -1 || $current < $max;
    }

    /**
     * Check if at or over limit.
     */
    public function atLimit(string $key, int $current): bool
    {
        return ! $this->withinLimit($key, $current);
    }

    /**
     * Get remaining quota (-1 = unlimited).
     */
    public function getRemainingQuota(string $key, int $current): int
    {
        $max = $this->getLimit($key);

        return $max === -1 ? -1 : max(0, $max - $current);
    }

    /**
     * Check if on free plan.
     */
    public function isFreePlan(): bool
    {
        return ($this->getPlanConfig()['price_monthly'] ?? 0) === 0;
    }

    /**
     * Check if on paid plan.
     */
    public function isPaidPlan(): bool
    {
        return ! $this->isFreePlan();
    }

    /**
     * Check if plan is at least a certain level.
     */
    public function hasPlanOrHigher(string $plan): bool
    {
        $currentOrder = $this->getPlanConfig()['sort_order'] ?? 0;
        $targetOrder = config("plans.{$plan}.sort_order", 0);

        return $currentOrder >= $targetOrder;
    }

    /**
     * Check if current plan matches given plan.
     */
    public function hasPlan(string $plan): bool
    {
        return $this->plan === $plan;
    }

    /**
     * Get all visible plans for pricing display.
     */
    public static function getVisiblePlans(): array
    {
        return collect(config('plans', []))
            ->filter(fn ($plan) => ($plan['is_active'] ?? true) && ($plan['is_visible'] ?? true))
            ->sortBy('sort_order')
            ->map(fn ($plan, $key) => array_merge(['key' => $key], $plan))
            ->values()
            ->all();
    }

    /**
     * Get all active plans.
     */
    public static function getActivePlans(): array
    {
        return collect(config('plans', []))
            ->filter(fn ($plan) => $plan['is_active'] ?? true)
            ->sortBy('sort_order')
            ->map(fn ($plan, $key) => array_merge(['key' => $key], $plan))
            ->values()
            ->all();
    }

    /**
     * Get popular plan (for highlighting).
     */
    public static function getPopularPlan(): ?array
    {
        foreach (config('plans', []) as $key => $plan) {
            if ($plan['is_popular'] ?? false) {
                return array_merge(['key' => $key], $plan);
            }
        }

        return null;
    }

    /**
     * Format price for display.
     */
    public function formatPrice(string $type = 'monthly'): string
    {
        $config = $this->getPlanConfig();
        $price = $type === 'yearly' ? ($config['price_yearly'] ?? 0) : ($config['price_monthly'] ?? 0);
        $currency = $config['currency'] ?? 'USD';

        if ($price === 0) {
            return trans('common.free');
        }

        return number_format($price / 100, 2).' '.$currency;
    }

    /**
     * Get monthly price in cents.
     */
    public function getMonthlyPrice(): int
    {
        return $this->getPlanConfig()['price_monthly'] ?? 0;
    }

    /**
     * Get yearly price in cents.
     */
    public function getYearlyPrice(): int
    {
        return $this->getPlanConfig()['price_yearly'] ?? 0;
    }

    /**
     * Get currency code.
     */
    public function getPlanCurrency(): string
    {
        return $this->getPlanConfig()['currency'] ?? 'USD';
    }

    /**
     * Get upgrade options (plans higher than current).
     */
    public function getUpgradeOptions(): array
    {
        $currentOrder = $this->getPlanConfig()['sort_order'] ?? 0;

        return collect(config('plans', []))
            ->filter(fn ($plan) => ($plan['is_active'] ?? true) && ($plan['sort_order'] ?? 0) > $currentOrder)
            ->sortBy('sort_order')
            ->map(fn ($plan, $key) => array_merge(['key' => $key], $plan))
            ->values()
            ->all();
    }

    /**
     * Check if upgrade is available.
     */
    public function canUpgrade(): bool
    {
        return ! empty($this->getUpgradeOptions());
    }

    /**
     * Get downgrade options (plans lower than current).
     */
    public function getDowngradeOptions(): array
    {
        $currentOrder = $this->getPlanConfig()['sort_order'] ?? 0;

        return collect(config('plans', []))
            ->filter(fn ($plan) => ($plan['is_active'] ?? true) && ($plan['sort_order'] ?? 0) < $currentOrder)
            ->sortBy('sort_order')
            ->map(fn ($plan, $key) => array_merge(['key' => $key], $plan))
            ->values()
            ->all();
    }

    /**
     * Check if downgrade is available.
     */
    public function canDowngrade(): bool
    {
        return ! empty($this->getDowngradeOptions());
    }

    /**
     * Get a specific plan config by key.
     */
    public static function getPlanByKey(string $key): ?array
    {
        $plan = config("plans.{$key}");

        if (empty($plan)) {
            return null;
        }

        return array_merge(['key' => $key], $plan);
    }

    /**
     * Check if a plan key is valid.
     */
    public static function isValidPlan(string $key): bool
    {
        return ! empty(config("plans.{$key}"));
    }
}
