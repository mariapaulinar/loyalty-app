<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Admin Health Center controller. Provides a diagnostics dashboard
 * showing system environment, database, storage, and service status.
 *
 * Access:
 * Admin-only (role:1). Exposes system internals — not for managers.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class HealthController extends Controller
{
    public function __construct(
        private readonly HealthService $healthService
    ) {}

    /**
     * Display the Health Center page.
     *
     * GET /{locale}/admin/health
     */
    public function index(string $locale): View
    {
        $results = $this->healthService->runChecks();

        // Group checks by category for display
        $categories = [];
        foreach ($results['checks'] as $check) {
            $categories[$check['category']][] = $check;
        }

        return view('admin.health.index', [
            'categories' => $categories,
            'summary' => $results['summary'],
        ]);
    }

    /**
     * Return health check results as JSON.
     *
     * GET /{locale}/admin/health/json
     *
     * Useful for external monitoring or AJAX refresh.
     */
    public function json(string $locale): JsonResponse
    {
        $results = $this->healthService->runChecks();

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
