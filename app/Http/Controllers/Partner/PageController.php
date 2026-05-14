<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Services\PartnerDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected PartnerDashboardService $dashboardService
    ) {}

    /**
     * Display the partner dashboard.
     * The "Welcome Home" experience for partners.
     */
    public function index(string $locale, Request $request): View
    {
        $dashboardData = $this->dashboardService->getDashboardData();
        $greeting = $this->dashboardService->getGreeting(auth('partner')->user()->time_zone);
        $quickAccessLinks = $this->dashboardService->getQuickAccessLinks();

        // Legacy support - keep dashboardBlocks for backwards compatibility
        $dashboardBlocks = $this->dashboardService->getQuickNavigationBlocks();

        return view('partner.index', [
            'dashboardData' => $dashboardData,
            'greeting' => $greeting,
            'quickAccessLinks' => $quickAccessLinks,
            'dashboardBlocks' => $dashboardBlocks,
        ]);
    }
}
