<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Partner Billing Page
 *
 * Assembles the partner's plan, subscription, feature, and limit data
 * into a single billing overview. Rendering adapts to the active billing
 * provider: manual mode shows plan info and limits, Stripe mode adds
 * subscription status, portal links, and upgrade options.
 */

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Services\Billing\BillingManager;
use App\Services\EntitlementService;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(
        protected EntitlementService $entitlements,
        protected BillingManager $billing,
    ) {}

    /**
     * Display the partner billing page.
     */
    public function index(string $locale): View
    {
        $partner = auth('partner')->user();
        $summary = $this->entitlements->summary($partner);
        $provider = $this->billing->provider();

        // Collect all active plans for the comparison section
        $plans = collect(config('plans', []))
            ->filter(fn ($plan) => $plan['is_active'] ?? true)
            ->sortBy('sort_order');

        // Resolve portal URL (null for manual/null provider)
        $portalUrl = $provider->billingPortalUrl($partner);

        // Determine if the partner can self-service upgrade
        // Only when Stripe is configured AND partner has a stripe_id
        $canUpgrade = $this->billing->isEnabled()
            && $this->billing->isConfigured()
            && ! empty($partner->stripe_id);

        return view('partner.billing.index', [
            'summary' => $summary,
            'billingEnabled' => $this->billing->isEnabled(),
            'billingConfigured' => $this->billing->isConfigured(),
            'providerName' => $this->billing->providerName(),
            'portalUrl' => $portalUrl,
            'canUpgrade' => $canUpgrade,
            'plans' => $plans,
            'partner' => $partner,
        ]);
    }
}
