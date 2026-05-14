<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Controller for Activity Log Analytics in the Partner dashboard.
 * Provides scoped audit trail insights for partner's own entities.
 *
 * Features:
 * - Summary metrics scoped to partner's data
 * - Event type breakdown charts
 * - Activity timeline visualization
 * - Most active users (staff/members)
 * - Authentication statistics
 */

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\ActivityLogAnalyticsService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivityLogAnalyticsController extends Controller
{
    public function __construct(
        protected ActivityLogAnalyticsService $analyticsService
    ) {}

    /**
     * Display the activity log analytics dashboard for the partner.
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

        // Get scoped query for partner
        $baseQuery = $this->getPartnerScopedQuery();

        // Get all analytics data with partner scoping
        $metrics = $this->analyticsService->getSummaryMetrics(clone $baseQuery);
        $eventBreakdown = $this->analyticsService->getEventBreakdown(clone $baseQuery, $since);
        $logNameBreakdown = $this->analyticsService->getLogNameBreakdown(clone $baseQuery, $since);
        $causerTypeBreakdown = $this->analyticsService->getCauserTypeBreakdown(clone $baseQuery, $since);
        $timeline = $this->analyticsService->getActivityTimeline(clone $baseQuery, $days);
        $eventOverTime = $this->analyticsService->getActivityByEventOverTime(clone $baseQuery, $days);
        $mostActiveUsers = $this->analyticsService->getMostActiveUsers(clone $baseQuery, 10, $since);
        $recentActivities = $this->analyticsService->getRecentActivities(clone $baseQuery, 10);
        $authStats = $this->analyticsService->getAuthStats(clone $baseQuery, $since);

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

        return view('partner.activity-logs.analytics', [
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

    /**
     * Get a query scoped to the partner's data.
     * Only shows activities related to the partner's entities.
     */
    protected function getPartnerScopedQuery(): Builder
    {
        $partner = Auth::guard('partner')->user();

        return Activity::query()->where(function ($q) use ($partner) {
            // Activities caused by the partner
            $q->where(function ($subQuery) use ($partner) {
                $subQuery->where('causer_type', 'App\Models\Partner')
                    ->where('causer_id', $partner->id);
            })
                // Activities on subjects owned by the partner
                ->orWhere(function ($subQuery) use ($partner) {
                    $subQuery->whereIn('subject_type', [
                        'App\Models\Club',
                        'App\Models\Card',
                        'App\Models\Member',
                        'App\Models\Staff',
                        'App\Models\Reward',
                        'App\Models\Transaction',
                    ])
                        ->whereHasMorph(
                            'subject',
                            ['App\Models\Club', 'App\Models\Card', 'App\Models\Member', 'App\Models\Staff', 'App\Models\Reward', 'App\Models\Transaction'],
                            function ($morphQuery, $morphType) use ($partner) {
                                if (in_array($morphType, ['App\Models\Club', 'App\Models\Card', 'App\Models\Reward', 'App\Models\Staff', 'App\Models\Transaction'])) {
                                    $morphQuery->where('created_by', $partner->id);
                                }
                                if ($morphType === 'App\Models\Member') {
                                    $morphQuery->whereHas('cards', function ($cardQuery) use ($partner) {
                                        $cardQuery->where('created_by', $partner->id);
                                    });
                                }
                            }
                        );
                })
                // Activities caused by entities owned by the partner
                ->orWhere(function ($subQuery) use ($partner) {
                    $subQuery->whereIn('causer_type', [
                        'App\Models\Staff',
                        'App\Models\Member',
                    ])
                        ->whereHasMorph(
                            'causer',
                            ['App\Models\Staff', 'App\Models\Member'],
                            function ($morphQuery, $morphType) use ($partner) {
                                if ($morphType === 'App\Models\Staff') {
                                    $morphQuery->where('created_by', $partner->id);
                                }
                                if ($morphType === 'App\Models\Member') {
                                    $morphQuery->whereHas('cards', function ($cardQuery) use ($partner) {
                                        $cardQuery->where('created_by', $partner->id);
                                    });
                                }
                            }
                        );
                });
        });
    }
}
