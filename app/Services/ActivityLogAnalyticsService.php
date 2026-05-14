<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Analytics service for Activity Logs - provides metrics, charts data,
 * and statistical insights for the audit trail system.
 *
 * Design Tenets:
 * - Type-safe operations with strict typing
 * - Efficient queries with proper indexing
 * - Supports both Admin (global) and Partner (scoped) views
 */

namespace App\Services;

use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ActivityLogAnalyticsService
{
    /**
     * Get summary metrics for activity logs.
     *
     * @param  Builder|null  $baseQuery  Optional scoped query for partners
     */
    public function getSummaryMetrics(?Builder $baseQuery = null): array
    {
        $query = $baseQuery ?? Activity::query();

        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $thisWeek = $now->copy()->startOfWeek();
        $thisMonth = $now->copy()->startOfMonth();

        return [
            'total' => (clone $query)->count(),
            'today' => (clone $query)->where('created_at', '>=', $today)->count(),
            'this_week' => (clone $query)->where('created_at', '>=', $thisWeek)->count(),
            'this_month' => (clone $query)->where('created_at', '>=', $thisMonth)->count(),
            'yesterday' => (clone $query)
                ->whereBetween('created_at', [
                    $today->copy()->subDay(),
                    $today,
                ])->count(),
            'last_week' => (clone $query)
                ->whereBetween('created_at', [
                    $thisWeek->copy()->subWeek(),
                    $thisWeek,
                ])->count(),
            'last_month' => (clone $query)
                ->whereBetween('created_at', [
                    $thisMonth->copy()->subMonth(),
                    $thisMonth,
                ])->count(),
        ];
    }

    /**
     * Get activity breakdown by event type.
     *
     * @param  Builder|null  $baseQuery  Optional scoped query for partners
     */
    public function getEventBreakdown(?Builder $baseQuery = null, ?Carbon $since = null): Collection
    {
        $query = $baseQuery ?? Activity::query();

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        return $query
            ->select('event', DB::raw('COUNT(*) as count'))
            ->whereNotNull('event')
            ->groupBy('event')
            ->orderByDesc('count')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->event => $item->count]);
    }

    /**
     * Get activity breakdown by log name (category).
     *
     * @param  Builder|null  $baseQuery  Optional scoped query for partners
     */
    public function getLogNameBreakdown(?Builder $baseQuery = null, ?Carbon $since = null): Collection
    {
        $query = $baseQuery ?? Activity::query();

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        return $query
            ->select('log_name', DB::raw('COUNT(*) as count'))
            ->groupBy('log_name')
            ->orderByDesc('count')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->log_name => $item->count]);
    }

    /**
     * Get activity breakdown by causer type (user type).
     *
     * @param  Builder|null  $baseQuery  Optional scoped query for partners
     */
    public function getCauserTypeBreakdown(?Builder $baseQuery = null, ?Carbon $since = null): Collection
    {
        $query = $baseQuery ?? Activity::query();

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        return $query
            ->select('causer_type', DB::raw('COUNT(*) as count'))
            ->whereNotNull('causer_type')
            ->groupBy('causer_type')
            ->orderByDesc('count')
            ->get()
            ->mapWithKeys(function ($item) {
                $label = $item->causer_type ? class_basename($item->causer_type) : 'System';

                return [$label => $item->count];
            });
    }

    /**
     * Get activity timeline data for charts (last N days).
     *
     * @param  Builder|null  $baseQuery  Optional scoped query for partners
     * @param  int  $days  Number of days to include
     */
    public function getActivityTimeline(?Builder $baseQuery = null, int $days = 30): array
    {
        $query = $baseQuery ?? Activity::query();
        $since = Carbon::now()->subDays($days)->startOfDay();

        $activities = (clone $query)
            ->where('created_at', '>=', $since)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $values = [];
        $current = $since->copy();
        $end = Carbon::now();

        while ($current <= $end) {
            $dateKey = $current->format('Y-m-d');
            $labels[] = $current->format('M d');
            $values[] = $activities->get($dateKey)?->count ?? 0;
            $current->addDay();
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'total' => array_sum($values),
        ];
    }

    /**
     * Get activity by event type over time (for stacked chart).
     *
     * @param  Builder|null  $baseQuery  Optional scoped query for partners
     * @param  int  $days  Number of days to include
     */
    public function getActivityByEventOverTime(?Builder $baseQuery = null, int $days = 30): array
    {
        $query = $baseQuery ?? Activity::query();
        $since = Carbon::now()->subDays($days)->startOfDay();

        $activities = (clone $query)
            ->where('created_at', '>=', $since)
            ->select(
                DB::raw('DATE(created_at) as date'),
                'event',
                DB::raw('COUNT(*) as count')
            )
            ->whereNotNull('event')
            ->groupBy('date', 'event')
            ->orderBy('date')
            ->get();

        // Get unique events
        $events = $activities->pluck('event')->unique()->values();

        // Build date range
        $labels = [];
        $current = $since->copy();
        $end = Carbon::now();

        while ($current <= $end) {
            $labels[] = $current->format('Y-m-d');
            $current->addDay();
        }

        // Build series data
        $series = [];
        foreach ($events as $event) {
            $data = [];
            foreach ($labels as $date) {
                $count = $activities
                    ->where('date', $date)
                    ->where('event', $event)
                    ->first()?->count ?? 0;
                $data[] = $count;
            }
            $series[] = [
                'name' => ucfirst($event),
                'data' => $data,
            ];
        }

        return [
            'labels' => array_map(fn ($d) => Carbon::parse($d)->format('M d'), $labels),
            'series' => $series,
        ];
    }

    /**
     * Get most active users (causers).
     *
     * @param  Builder|null  $baseQuery  Optional scoped query for partners
     * @param  int  $limit  Number of users to return
     */
    public function getMostActiveUsers(?Builder $baseQuery = null, int $limit = 10, ?Carbon $since = null): Collection
    {
        $query = $baseQuery ?? Activity::query();

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        return $query
            ->select('causer_type', 'causer_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('causer_id')
            ->groupBy('causer_type', 'causer_id')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $causer = null;
                if ($item->causer_type && $item->causer_id) {
                    $causer = $item->causer_type::find($item->causer_id);
                }

                return [
                    'name' => $causer?->name ?? $causer?->email ?? 'Unknown',
                    'type' => class_basename($item->causer_type),
                    'count' => $item->count,
                    'causer' => $causer,
                ];
            });
    }

    /**
     * Get recent activities.
     *
     * @param  Builder|null  $baseQuery  Optional scoped query for partners
     * @param  int  $limit  Number of activities to return
     */
    public function getRecentActivities(?Builder $baseQuery = null, int $limit = 10): Collection
    {
        $query = $baseQuery ?? Activity::query();

        return $query
            ->with(['causer', 'subject'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get authentication stats (logins, logouts, failed).
     *
     * @param  Builder|null  $baseQuery  Optional scoped query for partners
     */
    public function getAuthStats(?Builder $baseQuery = null, ?Carbon $since = null): array
    {
        $query = $baseQuery ?? Activity::query();
        $query->where('log_name', 'authentication');

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        $stats = (clone $query)
            ->select('event', DB::raw('COUNT(*) as count'))
            ->groupBy('event')
            ->get()
            ->keyBy('event');

        return [
            'logins' => $stats->get('login')?->count ?? 0,
            'logouts' => $stats->get('logout')?->count ?? 0,
            'failed_logins' => $stats->get('login_failed')?->count ?? 0,
        ];
    }

    /**
     * Calculate percentage difference between two values.
     */
    public function calculateDifference(int $current, int $previous): string
    {
        if ($previous === 0) {
            return $current > 0 ? '+100' : '0';
        }

        $diff = (($current - $previous) / $previous) * 100;

        return ($diff >= 0 ? '+' : '').number_format($diff, 0);
    }
}
