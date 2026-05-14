<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * SaasDashboardService
 *
 * Provides SaaS-specific metrics for the admin dashboard:
 * partner subscription status groups, plan distribution,
 * trial expiry warnings, past-due attention list,
 * and aggregate usage overview.
 *
 * All subscription status resolution goes through EntitlementService.
 */

namespace App\Services;

use App\Models\Card;
use App\Models\Partner;
use App\Models\Staff;
use App\Models\StampCard;
use App\Models\Voucher;
use App\Services\Billing\BillingManager;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SaasDashboardService
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    public function __construct(
        protected EntitlementService $entitlements,
        protected BillingManager $billing,
    ) {}

    /**
     * Get all SaaS dashboard data in a single call.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(): array
    {
        return Cache::remember('admin_saas_dashboard_data', self::CACHE_TTL, function () {
            $partners = Partner::all();

            return [
                'statusGroups' => $this->getPartnerStatusGroups($partners),
                'planDistribution' => $this->getPlanDistribution($partners),
                'trialExpiring' => $this->getTrialExpiringSoon(),
                'pastDuePartners' => $this->getPastDuePartners($partners),
                'usageOverview' => $this->getUsageOverview(),
                'recentRegistrations' => $this->getRecentRegistrations(),
                'partnerCount' => $partners->count(),
            ];
        });
    }

    /**
     * Group partners by their subscription status.
     *
     * Uses EntitlementService::subscriptionStatus() for each partner
     * to ensure consistent status resolution across the platform.
     *
     * @return array<string, array{count: int, partners: Collection}>
     */
    public function getPartnerStatusGroups(?Collection $partners = null): array
    {
        $partners = $partners ?? Partner::all();

        $groups = [];

        foreach ($partners as $partner) {
            $status = $this->entitlements->subscriptionStatus($partner);

            if (! isset($groups[$status])) {
                $groups[$status] = [
                    'count' => 0,
                    'partners' => collect(),
                ];
            }

            $groups[$status]['count']++;
            $groups[$status]['partners']->push([
                'id' => $partner->id,
                'name' => $partner->name,
                'email' => $partner->email,
                'business_name' => $partner->business_name,
                'plan' => $partner->plan ?? 'tier1',
                'created_at' => $partner->created_at,
            ]);
        }

        // Sort groups in priority order
        $orderedStatuses = [
            EntitlementService::STATUS_ACTIVE,
            EntitlementService::STATUS_TRIALING,
            EntitlementService::STATUS_PAST_DUE,
            EntitlementService::STATUS_INCOMPLETE,
            EntitlementService::STATUS_CANCELLED,
            EntitlementService::STATUS_SUSPENDED,
            EntitlementService::STATUS_MANUAL,
            EntitlementService::STATUS_LEGACY,
            EntitlementService::STATUS_BILLING_DISABLED,
        ];

        $ordered = [];
        foreach ($orderedStatuses as $status) {
            if (isset($groups[$status])) {
                $ordered[$status] = $groups[$status];
            }
        }

        return $ordered;
    }

    /**
     * Get the distribution of partners across plan tiers.
     *
     * @return array<string, array{name: string, count: int, percentage: float}>
     */
    public function getPlanDistribution(?Collection $partners = null): array
    {
        $partners = $partners ?? Partner::all();
        $total = $partners->count();
        $plans = config('plans', []);

        $distribution = [];

        foreach ($plans as $key => $plan) {
            if (! ($plan['is_active'] ?? true)) {
                continue;
            }

            $count = $partners->where('plan', $key)->count();

            $distribution[$key] = [
                'name' => isset($plan['name_key']) ? trans($plan['name_key']) : ($plan['name'] ?? $key),
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
                'color' => $this->planColor($key),
            ];
        }

        // Count partners with unknown/missing plans
        $knownPlanKeys = array_keys($plans);
        $unknownCount = $partners->filter(fn ($p) => ! in_array($p->plan, $knownPlanKeys, true))->count();

        if ($unknownCount > 0) {
            $distribution['unknown'] = [
                'name' => trans('common.saas_legacy'),
                'count' => $unknownCount,
                'percentage' => $total > 0 ? round(($unknownCount / $total) * 100, 1) : 0,
                'color' => 'secondary',
            ];
        }

        return $distribution;
    }

    /**
     * Get partners with trials expiring within the given number of days.
     */
    public function getTrialExpiringSoon(int $days = 7): Collection
    {
        if (! $this->billing->isEnabled()) {
            return collect();
        }

        return Partner::whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', Carbon::now())
            ->where('trial_ends_at', '<=', Carbon::now()->addDays($days))
            ->orderBy('trial_ends_at')
            ->get()
            ->map(fn (Partner $partner) => [
                'id' => $partner->id,
                'name' => $partner->name,
                'email' => $partner->email,
                'business_name' => $partner->business_name,
                'plan' => $partner->plan ?? 'tier1',
                'trial_ends_at' => $partner->trial_ends_at,
                'days_remaining' => (int) Carbon::now()->diffInDays($partner->trial_ends_at, false),
            ]);
    }

    /**
     * Get partners with past_due subscription status.
     *
     * @return Collection<int, array>
     */
    public function getPastDuePartners(?Collection $partners = null): Collection
    {
        $partners = $partners ?? Partner::all();

        return $partners->filter(function (Partner $partner) {
            return $this->entitlements->subscriptionStatus($partner) === EntitlementService::STATUS_PAST_DUE;
        })->map(fn (Partner $partner) => [
            'id' => $partner->id,
            'name' => $partner->name,
            'email' => $partner->email,
            'business_name' => $partner->business_name,
            'plan' => $partner->plan ?? 'tier1',
        ])->values();
    }

    /**
     * Get aggregate resource usage across all partners.
     *
     * @return array<string, int>
     */
    public function getUsageOverview(): array
    {
        return [
            'total_cards' => Card::count(),
            'total_stamp_cards' => StampCard::count(),
            'total_vouchers' => Voucher::count(),
            'total_staff' => Staff::count(),
            'total_partners' => Partner::count(),
        ];
    }

    /**
     * Get the most recent partner registrations.
     *
     * @return Collection<int, array>
     */
    public function getRecentRegistrations(int $limit = 5): Collection
    {
        return Partner::orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Partner $partner) => [
                'id' => $partner->id,
                'name' => $partner->name,
                'email' => $partner->email,
                'business_name' => $partner->business_name,
                'plan' => $partner->plan ?? 'tier1',
                'created_at' => $partner->created_at,
                'time_ago' => $partner->created_at?->diffForHumans(),
            ]);
    }

    /**
     * Clear the SaaS dashboard cache.
     */
    public function clearCache(): void
    {
        Cache::forget('admin_saas_dashboard_data');
    }

    /**
     * Map plan tier keys to display colors.
     */
    private function planColor(string $planKey): string
    {
        return match ($planKey) {
            'tier1' => 'secondary',
            'tier2' => 'primary',
            'tier3' => 'amber',
            'tier4' => 'violet',
            default => 'secondary',
        };
    }
}
