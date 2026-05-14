<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * EntitlementService
 *
 * The single authoritative service for all partner plan, feature, limit,
 * and billing-state decisions. Controllers, views, APIs, and jobs must
 * ask this service instead of reading meta attributes or plan config
 * directly when making access control decisions.
 *
 * Resolution order:
 * 1. partner->meta (admin overrides)
 * 2. config("plans.{$partner->plan}") (plan defaults)
 * 3. Default plan fallback
 *
 * @see config/plans.php
 */

namespace App\Services;

use App\Models\AgentKey;
use App\Models\Card;
use App\Models\Club;
use App\Models\Partner;
use App\Models\Reward;
use App\Models\Staff;
use App\Models\StampCard;
use App\Models\Voucher;
use App\Services\Billing\BillingManager;

class EntitlementService
{
    // ─────────────────────────────────────────────────────────────────────────
    // Subscription status constants
    // ─────────────────────────────────────────────────────────────────────────

    /** Billing is off for the installation. All partners get plan-based access. */
    public const STATUS_BILLING_DISABLED = 'billing_disabled';

    /** Partner has an existing local plan and no billing provider subscription. */
    public const STATUS_LEGACY = 'legacy';

    /** Admin manages billing outside the app. Treated as active. */
    public const STATUS_MANUAL = 'manual';

    /** Partner has an active paid subscription. */
    public const STATUS_ACTIVE = 'active';

    /** Partner is in trial period. */
    public const STATUS_TRIALING = 'trialing';

    /** Payment failed or requires action. Read-only access. */
    public const STATUS_PAST_DUE = 'past_due';

    /** Subscription ended. Read-only access. */
    public const STATUS_CANCELLED = 'cancelled';

    /** Subscription started but not usable. Read-only access. */
    public const STATUS_INCOMPLETE = 'incomplete';

    /** Admin blocked access. */
    public const STATUS_SUSPENDED = 'suspended';

    /**
     * Statuses where creating new resources is blocked.
     */
    public const RESTRICTED_STATUSES = [
        self::STATUS_PAST_DUE,
        self::STATUS_CANCELLED,
        self::STATUS_INCOMPLETE,
        self::STATUS_SUSPENDED,
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Feature key → meta key mapping
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Maps public feature keys to the partner meta attribute names
     * and the plan config keys used for default resolution.
     *
     * Format: feature_key => [meta_key, plan_config_key, default_value]
     */
    private const FEATURE_MAP = [
        'loyalty_cards'    => ['loyalty_cards_permission',    null,                    true],
        'stamp_cards'      => ['stamp_cards_permission',      null,                    true],
        'vouchers'         => ['vouchers_permission',         'has_vouchers',          false],
        'voucher_batches'  => ['voucher_batches_permission',  'has_voucher_batches',   false],
        'email_campaigns'  => ['email_campaigns_permission',  'has_email_campaigns',   false],
        'activity_log'     => ['activity_permission',         'has_activity_log',      false],
        'agent_api'        => ['agent_api_permission',        'has_agent_api',         false],
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Limit key → meta/config/model mapping
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Maps public limit keys to the meta attribute, plan config key,
     * model class for counting, and default limit value.
     *
     * Format: limit_key => [meta_key, plan_config_key, model_class, default]
     */
    private const LIMIT_MAP = [
        'cards'       => ['loyalty_cards_limit',   'max_cards',       Card::class,      1],
        'stamp_cards' => ['stamp_cards_limit',     'max_stamp_cards', StampCard::class,  1],
        'vouchers'    => ['vouchers_limit',        'max_vouchers',    Voucher::class,    0],
        'staff'       => ['staff_members_limit',   'max_staff',       Staff::class,      1],
        'rewards'     => ['rewards_limit',         'max_rewards',     Reward::class,     3],
        'clubs'       => [null,                    'max_clubs',       Club::class,       1],
        'agent_keys'  => ['agent_keys_limit',      'max_agent_keys',  AgentKey::class,   0],
    ];

    // ═════════════════════════════════════════════════════════════════════════
    // PLAN RESOLUTION
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Get the effective plan configuration for a partner.
     *
     * This merges the partner's plan config with the plan key.
     * Does not include meta overrides — those are resolved per-check.
     */
    public function effectivePlan(Partner $partner): array
    {
        return $partner->getPlanConfig();
    }

    // ═════════════════════════════════════════════════════════════════════════
    // BILLING STATE
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Determine the partner's subscription status.
     *
     * Delegates to the active BillingProvider via BillingManager.
     * The provider normalizes its native state to one of the
     * STATUS_* constants defined above.
     */
    public function subscriptionStatus(Partner $partner): string
    {
        return $this->billingManager()->provider()->subscriptionStatus($partner);
    }

    /**
     * Check if creating new resources is blocked due to billing state.
     *
     * Returns true for past_due, cancelled, incomplete, and suspended statuses.
     * Returns false for all v4.0 Step 3 statuses (billing_disabled, legacy, manual).
     */
    public function isAccessRestricted(Partner $partner): bool
    {
        return in_array($this->subscriptionStatus($partner), self::RESTRICTED_STATUSES, true);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // FEATURE GATES
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Check if a partner has access to a specific feature.
     *
     * Resolution order:
     * 1. partner->meta override (if key exists)
     * 2. Plan config flag
     * 3. Default value
     *
     * This checks feature visibility only. For creation gating,
     * use withinLimit() which also checks billing state.
     */
    public function can(Partner $partner, string $feature): bool
    {
        if (! isset(self::FEATURE_MAP[$feature])) {
            return false;
        }

        [$metaKey, $planConfigKey, $default] = self::FEATURE_MAP[$feature];

        // Check meta override first
        $meta = $partner->meta ?? [];
        if (is_array($meta) && array_key_exists($metaKey, $meta)) {
            return (bool) $meta[$metaKey];
        }

        // Check plan config
        if ($planConfigKey !== null) {
            $planConfig = $this->effectivePlan($partner);

            return (bool) ($planConfig[$planConfigKey] ?? $default);
        }

        return $default;
    }

    /**
     * Get a human-readable reason why a feature is denied.
     *
     * Returns null if the feature is allowed.
     */
    public function denyReason(Partner $partner, string $feature): ?string
    {
        if ($this->can($partner, $feature)) {
            return null;
        }

        if (! isset(self::FEATURE_MAP[$feature])) {
            return trans('common.entitlement.unknown_feature', ['feature' => $feature]);
        }

        $planConfig = $this->effectivePlan($partner);
        $planName = $planConfig['name'] ?? 'current';

        // Find which plans include this feature
        $upgradePlans = $this->plansWithFeature($feature);

        if (! empty($upgradePlans)) {
            $planNames = implode(', ', array_column($upgradePlans, 'name'));

            return trans('common.entitlement.feature_requires_upgrade', [
                'feature' => $this->featureDisplayName($feature),
                'plan' => $planName,
                'available_plans' => $planNames,
            ]);
        }

        return trans('common.entitlement.feature_not_available', [
            'feature' => $this->featureDisplayName($feature),
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // LIMIT GATES
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Get the maximum allowed count for a resource.
     *
     * Returns -1 for unlimited.
     */
    public function limit(Partner $partner, string $resource): int
    {
        if (! isset(self::LIMIT_MAP[$resource])) {
            return 0;
        }

        [$metaKey, $planConfigKey, , $default] = self::LIMIT_MAP[$resource];

        // Check meta override first
        if ($metaKey !== null) {
            $meta = $partner->meta ?? [];
            if (is_array($meta) && array_key_exists($metaKey, $meta)) {
                return (int) $meta[$metaKey];
            }
        }

        // Check plan config
        $planConfig = $this->effectivePlan($partner);

        return (int) ($planConfig[$planConfigKey] ?? $default);
    }

    /**
     * Get the current usage count for a resource.
     */
    public function usage(Partner $partner, string $resource): int
    {
        if (! isset(self::LIMIT_MAP[$resource])) {
            return 0;
        }

        [, , $modelClass] = self::LIMIT_MAP[$resource];

        return $modelClass::where('created_by', $partner->id)->count();
    }

    /**
     * Check if a partner is within their limit for a resource.
     *
     * Returns true if the partner can create another resource of this type.
     * Returns false if the limit is reached or exceeded.
     */
    public function withinLimit(Partner $partner, string $resource): bool
    {
        // Restricted billing states block all creation
        if ($this->isAccessRestricted($partner)) {
            return false;
        }

        $max = $this->limit($partner, $resource);

        // -1 = unlimited
        if ($max === -1) {
            return true;
        }

        return $this->usage($partner, $resource) < $max;
    }

    /**
     * Get the remaining quota for a resource.
     *
     * Returns -1 for unlimited. Returns 0 or positive for limited plans.
     */
    public function remaining(Partner $partner, string $resource): int
    {
        $max = $this->limit($partner, $resource);

        if ($max === -1) {
            return -1;
        }

        return max(0, $max - $this->usage($partner, $resource));
    }

    /**
     * Get a human-readable reason why a limit blocks creation.
     *
     * Returns null if within limit.
     */
    public function limitDenyReason(Partner $partner, string $resource): ?string
    {
        if ($this->withinLimit($partner, $resource)) {
            return null;
        }

        $max = $this->limit($partner, $resource);
        $current = $this->usage($partner, $resource);
        $resourceName = $this->resourceDisplayName($resource);

        return trans('common.entitlement.limit_reached', [
            'resource' => $resourceName,
            'current' => $current,
            'limit' => $max,
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // SUMMARY
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Get a structured entitlement summary for a partner.
     *
     * Used by the partner billing page and admin dashboard.
     */
    public function summary(Partner $partner): array
    {
        $plan = $this->effectivePlan($partner);
        $status = $this->subscriptionStatus($partner);

        $features = [];
        foreach (self::FEATURE_MAP as $key => $_) {
            $features[$key] = $this->can($partner, $key);
        }

        $limits = [];
        foreach (self::LIMIT_MAP as $key => $_) {
            $max = $this->limit($partner, $key);
            $current = $this->usage($partner, $key);
            $limits[$key] = [
                'limit' => $max,
                'usage' => $current,
                'remaining' => $max === -1 ? -1 : max(0, $max - $current),
                'unlimited' => $max === -1,
                'exceeded' => $max !== -1 && $current >= $max,
            ];
        }

        return [
            'plan' => [
                'key' => $plan['key'] ?? 'tier1',
                'name' => isset($plan['name_key']) ? trans($plan['name_key']) : ($plan['name'] ?? 'Bronze'),
                'description' => isset($plan['description_key']) ? trans($plan['description_key']) : ($plan['description'] ?? ''),
                'is_free' => ($plan['price_monthly'] ?? 0) === 0,
            ],
            'status' => $status,
            'is_restricted' => $this->isAccessRestricted($partner),
            'features' => $features,
            'limits' => $limits,
        ];
    }

    // ═════════════════════════════════════════════════════════════════════════
    // HELPERS (PRIVATE)
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Get the BillingManager instance.
     */
    private function billingManager(): BillingManager
    {
        return app(BillingManager::class);
    }

    /**
     * Find all active plans that include a given feature.
     */
    private function plansWithFeature(string $feature): array
    {
        if (! isset(self::FEATURE_MAP[$feature])) {
            return [];
        }

        [, $planConfigKey, $default] = self::FEATURE_MAP[$feature];

        // Features without a plan config key are always-on (loyalty_cards, stamp_cards)
        if ($planConfigKey === null) {
            return [];
        }

        $plans = config('plans', []);
        $result = [];

        foreach ($plans as $key => $plan) {
            if (! ($plan['is_active'] ?? true)) {
                continue;
            }

            if ($plan[$planConfigKey] ?? $default) {
                $result[] = array_merge(['key' => $key], $plan);
            }
        }

        // Sort by sort_order
        usort($result, fn ($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

        return $result;
    }

    /**
     * Get a human-readable display name for a feature key.
     */
    private function featureDisplayName(string $feature): string
    {
        $names = [
            'loyalty_cards'   => trans('common.entitlement.features.loyalty_cards'),
            'stamp_cards'     => trans('common.entitlement.features.stamp_cards'),
            'vouchers'        => trans('common.entitlement.features.vouchers'),
            'voucher_batches' => trans('common.entitlement.features.voucher_batches'),
            'email_campaigns' => trans('common.entitlement.features.email_campaigns'),
            'activity_log'    => trans('common.entitlement.features.activity_log'),
            'agent_api'       => trans('common.entitlement.features.agent_api'),
        ];

        return $names[$feature] ?? $feature;
    }

    /**
     * Get a human-readable display name for a resource key.
     */
    private function resourceDisplayName(string $resource): string
    {
        $names = [
            'cards'       => trans('common.entitlement.resources.cards'),
            'stamp_cards' => trans('common.entitlement.resources.stamp_cards'),
            'vouchers'    => trans('common.entitlement.resources.vouchers'),
            'staff'       => trans('common.entitlement.resources.staff'),
            'rewards'     => trans('common.entitlement.resources.rewards'),
            'clubs'       => trans('common.entitlement.resources.clubs'),
            'agent_keys'  => trans('common.entitlement.resources.agent_keys'),
        ];

        return $names[$resource] ?? $resource;
    }
}
