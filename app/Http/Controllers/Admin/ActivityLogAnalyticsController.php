<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Controller for Activity Log Analytics in the Admin dashboard.
 * Provides comprehensive system-wide audit trail insights.
 *
 * Features:
 * - Summary metrics (total, today, week, month)
 * - Event type breakdown charts
 * - Activity timeline visualization
 * - Most active users leaderboard
 * - Authentication statistics
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogAnalyticsController extends Controller
{
    public function __construct(
        protected ActivityLogAnalyticsService $analyticsService
    ) {}

    /**
     * Display the activity log analytics dashboard.
     */
    public function index(Request $request): View
    {
        // Get range from request or cookie
        $range = $request->query('range', $request->cookie('range', '30'));

        // Parse days from range
        $days = match ($range) {
            '7' => 7,
            '14' => 14,
            '30' => 30,
            '90' => 90,
            '365' => 365,
            default => 30,
        };

        $since = Carbon::now()->subDays($days);

        // Get all analytics data (no base query = all data for admin)
        $metrics = $this->analyticsService->getSummaryMetrics();
        $eventBreakdown = $this->analyticsService->getEventBreakdown(null, $since);
        $logNameBreakdown = $this->analyticsService->getLogNameBreakdown(null, $since);
        $causerTypeBreakdown = $this->analyticsService->getCauserTypeBreakdown(null, $since);
        $timeline = $this->analyticsService->getActivityTimeline(null, $days);
        $eventOverTime = $this->analyticsService->getActivityByEventOverTime(null, $days);
        $mostActiveUsers = $this->analyticsService->getMostActiveUsers(null, 10, $since);
        $recentActivities = $this->analyticsService->getRecentActivities(null, 10);
        $authStats = $this->analyticsService->getAuthStats(null, $since);

        // Calculate percentage differences
        $todayDiff = $this->analyticsService->calculateDifference(
            $metrics['today'],
            $metrics['yesterday']
        );
        $weekDiff = $this->analyticsService->calculateDifference(
            $metrics['this_week'],
            $metrics['last_week']
        );
        $monthDiff = $this->analyticsService->calculateDifference(
            $metrics['this_month'],
            $metrics['last_month']
        );

        return view('admin.activity-logs.analytics', [
            'range' => $range,
            'days' => $days,
            'metrics' => $metrics,
            'todayDiff' => $todayDiff,
            'weekDiff' => $weekDiff,
            'monthDiff' => $monthDiff,
            'eventBreakdown' => $eventBreakdown,
            'logNameBreakdown' => $logNameBreakdown,
            'causerTypeBreakdown' => $causerTypeBreakdown,
            'timeline' => $timeline,
            'eventOverTime' => $eventOverTime,
            'mostActiveUsers' => $mostActiveUsers,
            'recentActivities' => $recentActivities,
            'authStats' => $authStats,
        ]);
    }
}
