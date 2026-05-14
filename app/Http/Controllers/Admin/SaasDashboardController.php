<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Admin SaaS Dashboard
 *
 * Surfaces partner subscription health, plan distribution,
 * and operational attention items.
 * Admin-only — managers cannot access this page.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Billing\BillingManager;
use App\Services\SaasDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaasDashboardController extends Controller
{
    public function __construct(
        protected SaasDashboardService $dashboardService,
        protected BillingManager $billing,
    ) {}

    /**
     * Display the admin SaaS dashboard.
     */
    public function index(string $locale, Request $request): View
    {
        $data = $this->dashboardService->getDashboardData();

        // Collect all active plans for display names
        $plans = collect(config('plans', []))
            ->filter(fn ($plan) => $plan['is_active'] ?? true)
            ->sortBy('sort_order');

        return view('admin.saas.index', [
            'statusGroups' => $data['statusGroups'],
            'planDistribution' => $data['planDistribution'],
            'trialExpiring' => $data['trialExpiring'],
            'pastDuePartners' => $data['pastDuePartners'],
            'usageOverview' => $data['usageOverview'],
            'recentRegistrations' => $data['recentRegistrations'],
            'partnerCount' => $data['partnerCount'],
            'billingEnabled' => $this->billing->isEnabled(),
            'billingConfigured' => $this->billing->isConfigured(),
            'providerName' => $this->billing->providerName(),
            'plans' => $plans,
        ]);
    }
}
