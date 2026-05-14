<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Dashboard service for Admin panel providing real-time metrics,
 * activity analytics, and system health insights.
 *
 * Design Philosophy:
 * Inspired by Revolut, Linear, and Stripe dashboards — data-driven,
 * visually compelling, and instantly informative.
 *
 * Features:
 * - Entity counts (Partners, Members, Networks, Cards)
 * - Growth trends with percentage changes
 * - Activity sparkline data for mini charts
 * - Recent activity feed
 * - System health indicators
 */

namespace App\Services;

use App\Models\Activity;
use App\Models\Admin;
use App\Models\Card;
use App\Models\Member;
use App\Models\Network;
use App\Models\Partner;
use App\Models\StampCard;
use App\Models\Transaction;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    /**
     * Cache TTL in seconds (5 minutes for dashboard data).
     */
    private const CACHE_TTL = 300;

    /**
     * Get all dashboard data in a single call.
     * Uses caching to prevent excessive database queries.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(): array
    {
        return Cache::remember('admin_dashboard_data', self::CACHE_TTL, function () {
            $stats = $this->getEntityStats();
            $growth = $this->getGrowthMetrics();
            $activity = $this->getActivityMetrics();

            return [
                'stats' => $stats,
                'growth' => $growth,
                'activity' => $activity,
                'sparklines' => $this->getSparklineData(),
                'recentActivity' => $this->getRecentActivity(5),
                'systemHealth' => $this->getSystemHealth(),
                'insights' => $this->generateInsights($stats, $growth, $activity),
                'weekSummary' => $this->getWeekSummary(),
            ];
        });
    }

    /**
     * Generate contextual insights based on current data.
     * These are the "smart" messages that make the dashboard feel alive.
     *
     * @return array<int, array{type: string, icon: string, title: string, message: string, action?: array}>
     */
    private function generateInsights(array $stats, array $growth, array $activity): array
    {
        $insights = [];

        // 🎉 Celebration: Milestone achievements
        $milestones = [10, 25, 50, 100, 250, 500, 1000, 2500, 5000, 10000];
        foreach (['partners', 'members', 'cards'] as $entity) {
            $count = $stats[$entity] ?? 0;
            foreach ($milestones as $milestone) {
                if ($count >= $milestone && $count < $milestone * 1.1) {
                    $insights[] = [
                        'type' => 'celebration',
                        'icon' => 'trophy',
                        'title' => trans('common.insights.milestone_reached'),
                        'message' => trans("common.insights.{$entity}_milestone", ['count' => number_format($milestone)]),
                        'color' => 'amber',
                    ];
                    break;
                }
            }
        }

        // 📈 Growth: Strong performance (prioritize members over partners)
        $memberPct = (int) ltrim($growth['members']['percentage'] ?? '0', '+');
        $partnerPct = (int) ltrim($growth['partners']['percentage'] ?? '0', '+');

        if ($memberPct >= 10 && str_starts_with($growth['members']['percentage'] ?? '', '+')) {
            // Member growth is the priority metric
            $insights[] = [
                'type' => 'growth',
                'icon' => 'trending-up',
                'title' => trans('common.insights.strong_growth'),
                'message' => trans('common.insights.members_growth', ['percentage' => ltrim($growth['members']['percentage'], '+')]),
                'color' => 'emerald',
            ];
        } elseif ($partnerPct >= 20 && str_starts_with($growth['partners']['percentage'] ?? '', '+')) {
            // Fall back to partner growth if member growth isn't significant
            $insights[] = [
                'type' => 'growth',
                'icon' => 'trending-up',
                'title' => trans('common.insights.strong_growth'),
                'message' => trans('common.insights.partners_growth', ['percentage' => ltrim($growth['partners']['percentage'], '+')]),
                'color' => 'emerald',
            ];
        }

        // 🔥 Activity: High engagement day
        if ($activity['today'] > $activity['yesterday'] * 1.5 && $activity['today'] > 10) {
            $insights[] = [
                'type' => 'activity',
                'icon' => 'zap',
                'title' => trans('common.insights.high_activity'),
                'message' => trans('common.insights.busy_day', ['count' => number_format($activity['today'])]),
                'color' => 'violet',
            ];
        }

        // ⚠️ Security: Failed logins
        if (($activity['failedLogins'] ?? 0) > 5) {
            $insights[] = [
                'type' => 'security',
                'icon' => 'shield-alert',
                'title' => trans('common.insights.security_notice'),
                'message' => trans('common.insights.failed_logins', ['count' => $activity['failedLogins']]),
                'color' => 'amber',
                'action' => [
                    'label' => trans('common.view_logs'),
                    'route' => 'admin.activity-logs.analytics',
                ],
            ];
        }

        // 💡 Tip: When things are quiet
        if ($activity['today'] === 0 && empty($insights)) {
            $tips = [
                ['icon' => 'lightbulb', 'message' => trans('common.insights.tip_add_partner')],
                ['icon' => 'sparkles', 'message' => trans('common.insights.tip_review_analytics')],
                ['icon' => 'settings', 'message' => trans('common.insights.tip_check_settings')],
            ];
            $tip = $tips[array_rand($tips)];
            $insights[] = [
                'type' => 'tip',
                'icon' => $tip['icon'],
                'title' => trans('common.insights.pro_tip'),
                'message' => $tip['message'],
                'color' => 'primary',
            ];
        }

        // 🌟 Welcome: First time users or low activity
        if ($stats['partners'] === 0 && $stats['members'] === 0) {
            $insights = [[
                'type' => 'welcome',
                'icon' => 'rocket',
                'title' => trans('common.insights.getting_started'),
                'message' => trans('common.insights.welcome_message'),
                'color' => 'primary',
                'action' => [
                    'label' => trans('common.add_partner'),
                    'route' => 'admin.data.insert',
                    'params' => ['name' => 'partners'],
                ],
            ]];
        }

        return array_slice($insights, 0, 3); // Max 3 insights
    }

    /**
     * Get week summary with highlights.
     *
     * @return array<string, mixed>
     */
    private function getWeekSummary(): array
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();

        // Get best day this week
        $dailyActivity = Activity::where('created_at', '>=', $startOfWeek)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderByDesc('count')
            ->first();

        // Get new entities this week
        $newPartnersThisWeek = Partner::where('created_at', '>=', $startOfWeek)->count();
        $newMembersThisWeek = Member::where('created_at', '>=', $startOfWeek)->count();
        $newTransactionsThisWeek = Transaction::where('created_at', '>=', $startOfWeek)->count();

        return [
            'bestDay' => $dailyActivity ? [
                'date' => Carbon::parse($dailyActivity->date),
                'count' => $dailyActivity->count,
                'dayName' => Carbon::parse($dailyActivity->date)->translatedFormat('l'),
            ] : null,
            'newPartners' => $newPartnersThisWeek,
            'newMembers' => $newMembersThisWeek,
            'newTransactions' => $newTransactionsThisWeek,
            'weekProgress' => min(100, round(($now->dayOfWeek / 7) * 100)),
        ];
    }

    /**
     * Get entity counts for the main stats display.
     *
     * @return array<string, int|array>
     */
    public function getEntityStats(): array
    {
        $loyaltyCards = Card::count();
        $stampCards = StampCard::count();
        $vouchers = Voucher::count();

        return [
            'partners' => Partner::count(),
            'members' => Member::count(),
            'networks' => Network::count(),
            'cards' => $loyaltyCards + $stampCards + $vouchers, // Total for headline
            'loyaltyCards' => $loyaltyCards,
            'stampCards' => $stampCards,
            'vouchers' => $vouchers,
            'admins' => Admin::count(),
            'transactions' => Transaction::count(),
        ];
    }

    /**
     * Get growth metrics comparing current period vs previous.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getGrowthMetrics(): array
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfLastWeek = $startOfWeek->copy()->subWeek();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $startOfMonth->copy()->subMonth();

        return [
            'partners' => $this->calculateGrowth(Partner::class, $startOfMonth, $startOfLastMonth),
            'members' => $this->calculateGrowth(Member::class, $startOfMonth, $startOfLastMonth),
            'cards' => $this->calculateGrowth(Card::class, $startOfMonth, $startOfLastMonth),
            'transactions' => $this->calculateGrowth(Transaction::class, $startOfWeek, $startOfLastWeek),
        ];
    }

    /**
     * Calculate growth percentage for a model.
     *
     * @param  class-string  $model
     * @return array{current: int, previous: int, percentage: string, trend: string}
     */
    private function calculateGrowth(string $model, Carbon $currentStart, Carbon $previousStart): array
    {
        $currentEnd = Carbon::now();
        $previousEnd = $currentStart->copy();

        $current = $model::whereBetween('created_at', [$currentStart, $currentEnd])->count();
        $previous = $model::whereBetween('created_at', [$previousStart, $previousEnd])->count();

        $percentage = $previous > 0
            ? round((($current - $previous) / $previous) * 100, 1)
            : ($current > 0 ? 100 : 0);

        return [
            'current' => $current,
            'previous' => $previous,
            'percentage' => ($percentage >= 0 ? '+' : '').$percentage,
            'trend' => $percentage >= 0 ? 'up' : 'down',
        ];
    }

    /**
     * Get activity metrics for the activity pulse section.
     *
     * @return array<string, mixed>
     */
    public function getActivityMetrics(): array
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $yesterday = $today->copy()->subDay();
        $thisWeek = $now->copy()->startOfWeek();

        $todayCount = Activity::where('created_at', '>=', $today)->count();
        $yesterdayCount = Activity::whereBetween('created_at', [$yesterday, $today])->count();

        return [
            'today' => $todayCount,
            'yesterday' => $yesterdayCount,
            'thisWeek' => Activity::where('created_at', '>=', $thisWeek)->count(),
            'todayDiff' => $this->calculateDiff($todayCount, $yesterdayCount),
            'logins' => Activity::where('log_name', 'auth')
                ->where('event', 'login')
                ->where('created_at', '>=', $today)
                ->count(),
            'failedLogins' => Activity::where('log_name', 'auth')
                ->where('event', 'login_failed')
                ->where('created_at', '>=', $today)
                ->count(),
        ];
    }

    /**
     * Calculate percentage difference between two values.
     */
    private function calculateDiff(int $current, int $previous): string
    {
        if ($previous === 0) {
            return $current > 0 ? '+100' : '0';
        }

        $diff = (($current - $previous) / $previous) * 100;

        return ($diff >= 0 ? '+' : '').number_format($diff, 0);
    }

    /**
     * Get sparkline data for mini charts (last 14 days).
     *
     * @return array<string, array<int>>
     */
    public function getSparklineData(): array
    {
        $days = 14;
        $since = Carbon::now()->subDays($days)->startOfDay();

        return [
            'activity' => $this->getTimelineValues(Activity::class, $days),
            'partners' => $this->getTimelineValues(Partner::class, $days),
            'members' => $this->getTimelineValues(Member::class, $days),
            'transactions' => $this->getTimelineValues(Transaction::class, $days),
        ];
    }

    /**
     * Get daily counts for sparkline visualization.
     *
     * @param  class-string  $model
     * @return array<int>
     */
    private function getTimelineValues(string $model, int $days): array
    {
        $since = Carbon::now()->subDays($days)->startOfDay();

        $data = $model::where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $values = [];
        $current = $since->copy();
        $end = Carbon::now();

        while ($current <= $end) {
            $dateKey = $current->format('Y-m-d');
            $values[] = $data->get($dateKey)?->count ?? 0;
            $current->addDay();
        }

        return $values;
    }

    /**
     * Get recent activity for the activity feed.
     *
     * @return Collection<int, Activity>
     */
    public function getRecentActivity(int $limit = 8): Collection
    {
        return Activity::with(['causer', 'subject'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (Activity $activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'event' => $activity->event,
                    'log_name' => $activity->log_name,
                    'causer_name' => $activity->causer?->name
                        ?? $activity->causer?->email
                        ?? trans('common.system'),
                    'causer_type' => $activity->causer_type
                        ? class_basename($activity->causer_type)
                        : null,
                    'subject_type' => $activity->subject_type
                        ? class_basename($activity->subject_type)
                        : null,
                    'created_at' => $activity->created_at,
                    'time_ago' => $activity->created_at->diffForHumans(),
                ];
            });
    }

    /**
     * Get system health indicators.
     *
     * @return array<string, mixed>
     */
    public function getSystemHealth(): array
    {
        return [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'queue' => $this->checkQueueHealth(),
            'overall' => 'healthy', // Will be calculated based on above
        ];
    }

    /**
     * Check database connectivity and response time.
     */
    private function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'latency' => $latency,
                'message' => $latency < 100 ? 'Excellent' : ($latency < 500 ? 'Good' : 'Slow'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'latency' => null,
                'message' => 'Connection failed',
            ];
        }
    }

    /**
     * Check cache connectivity.
     */
    private function checkCacheHealth(): array
    {
        try {
            $testKey = 'health_check_'.uniqid();
            Cache::put($testKey, true, 10);
            $result = Cache::get($testKey);
            Cache::forget($testKey);

            return [
                'status' => $result ? 'healthy' : 'degraded',
                'message' => $result ? 'Working' : 'Degraded',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Unavailable',
            ];
        }
    }

    /**
     * Check queue health (placeholder for queue monitoring).
     */
    private function checkQueueHealth(): array
    {
        // This could be expanded to check actual queue health
        return [
            'status' => 'healthy',
            'jobs_pending' => 0,
            'message' => 'Operational',
        ];
    }

    /**
     * Clear the dashboard cache (call after significant data changes).
     */
    public function clearCache(): void
    {
        Cache::forget('admin_dashboard_data');
    }

    /**
     * Get time-based greeting for the dashboard.
     */
    public function getGreeting(?string $timezone = null): string
    {
        return getGreeting($timezone);
    }
}
