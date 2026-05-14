<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Service for preparing all data required for the Partner Dashboard.
 * This centralizes data fetching and processing, keeping the controller lean.
 *
 * Design Philosophy:
 * Inspired by Revolut, Linear, and Stripe dashboards — data-driven,
 * visually compelling, and instantly informative.
 *
 * Design Tenets:
 * - Single Responsibility: Each method focuses on a specific data set.
 * - Contextual Intelligence: Generate smart insights from raw data.
 * - Performance: Efficient queries with minimal N+1 issues.
 * - Readability: Clear separation of concerns for maintainability.
 */

namespace App\Services;

use App\Models\Card;
use App\Models\Member;
use App\Models\StampCard;
use App\Models\StampTransaction;
use App\Models\Transaction;
use App\Models\Voucher;
use App\Services\EntitlementService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PartnerDashboardService
{
    /**
     * Get all data required for the partner dashboard.
     *
     * @param  int  $days  The number of days for analytics.
     */
    public function getDashboardData(int $days = 30): array
    {
        $partnerId = auth('partner')->id();
        $since = now()->subDays($days);

        $metrics = $this->getMetrics($partnerId);
        $timeline = $this->getTransactionTimeline($partnerId, $days);
        $recentActivity = $this->getRecentActivity($partnerId, 8);
        $topMembers = $this->getTopMembers($partnerId, 3);
        $weekSummary = $this->getWeekSummary($partnerId);
        $insights = $this->generateInsights($partnerId, $metrics);

        // Generate sparkline data (normalize to 0-100 for consistent height in UI)
        $sparklineValues = $timeline['values'];
        $maxSparklineValue = max($sparklineValues) > 0 ? max($sparklineValues) : 1;
        $sparkline = Arr::map($sparklineValues, fn ($value) => (int) (($value / $maxSparklineValue) * 100));

        return [
            'metrics' => $metrics,
            'timeline' => array_merge($timeline, ['sparkline' => $sparkline]),
            'recentActivity' => $recentActivity,
            'topMembers' => $topMembers,
            'weekSummary' => $weekSummary,
            'insights' => $insights,
            // Legacy support
            'recentTransactions' => $recentActivity->take(5),
        ];
    }

    /**
     * Generate contextual insights based on partner's current data.
     * These smart messages make the dashboard feel alive and personalized.
     *
     * @return array<int, array{type: string, icon: string, title: string, message: string, color: string}>
     */
    private function generateInsights(string $partnerId, array $metrics): array
    {
        $insights = [];

        // 🎉 Milestone: Member achievements
        $memberMilestones = [10, 25, 50, 100, 250, 500, 1000, 2500, 5000];
        $memberCount = $metrics['total_members'];
        foreach ($memberMilestones as $milestone) {
            if ($memberCount >= $milestone && $memberCount < $milestone * 1.15) {
                $insights[] = [
                    'type' => 'celebration',
                    'icon' => 'trophy',
                    'title' => trans('common.partner_insights.member_milestone'),
                    'message' => trans('common.partner_insights.member_milestone_message', ['count' => number_format($milestone)]),
                    'color' => 'amber',
                ];
                break;
            }
        }

        // 📈 Growth: Strong transaction performance
        if ($metrics['transaction_growth'] >= 15) {
            $insights[] = [
                'type' => 'growth',
                'icon' => 'trending-up',
                'title' => trans('common.partner_insights.strong_month'),
                'message' => trans('common.partner_insights.transaction_growth_message', ['percentage' => abs((int) $metrics['transaction_growth'])]),
                'color' => 'emerald',
            ];
        } elseif ($metrics['points_growth'] >= 20) {
            $insights[] = [
                'type' => 'growth',
                'icon' => 'zap',
                'title' => trans('common.partner_insights.points_surge'),
                'message' => trans('common.partner_insights.points_growth_message', ['percentage' => abs((int) $metrics['points_growth'])]),
                'color' => 'emerald',
            ];
        }

        // 💡 Tips: When things are quiet or new
        if ($memberCount === 0) {
            $insights[] = [
                'type' => 'tip',
                'icon' => 'lightbulb',
                'title' => trans('common.partner_insights.getting_started'),
                'message' => trans('common.partner_insights.no_members_tip'),
                'color' => 'primary',
            ];
        } elseif ($metrics['active_loyalty_cards'] === 0 && $metrics['active_stamp_cards'] === 0) {
            $insights[] = [
                'type' => 'tip',
                'icon' => 'credit-card',
                'title' => trans('common.partner_insights.create_card'),
                'message' => trans('common.partner_insights.no_cards_tip'),
                'color' => 'primary',
            ];
        } elseif (empty($insights) && $metrics['transactions_this_month'] < 5) {
            $tips = [
                ['icon' => 'megaphone', 'message' => trans('common.partner_insights.promote_tip')],
                ['icon' => 'gift', 'message' => trans('common.partner_insights.reward_tip')],
                ['icon' => 'users', 'message' => trans('common.partner_insights.staff_tip')],
            ];
            $tip = $tips[array_rand($tips)];
            $insights[] = [
                'type' => 'tip',
                'icon' => $tip['icon'],
                'title' => trans('common.partner_insights.pro_tip'),
                'message' => $tip['message'],
                'color' => 'violet',
            ];
        }

        // Limit to 2 insights max
        return array_slice($insights, 0, 2);
    }

    /**
     * Get week summary for the "This Week" highlights section.
     */
    private function getWeekSummary(string $partnerId): array
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();

        // Best day this week (by transaction count)
        $bestDay = Transaction::where('created_by', $partnerId)
            ->where('created_at', '>=', $startOfWeek)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderByDesc('count')
            ->first();

        // New members this week
        $newMembersThisWeek = Member::whereHas('transactions', function ($query) use ($partnerId) {
            $query->where('created_by', $partnerId);
        })
            ->where('created_at', '>=', $startOfWeek)
            ->count();

        // Total points issued this week
        $pointsThisWeek = Transaction::where('created_by', $partnerId)
            ->where('created_at', '>=', $startOfWeek)
            ->sum('points');

        // Stamps this week
        $stampsThisWeek = StampTransaction::whereHas('stampCard.club', function ($query) use ($partnerId) {
            $query->where('created_by', $partnerId);
        })
            ->where('created_at', '>=', $startOfWeek)
            ->where('event', 'earned')
            ->sum('stamps');

        return [
            'bestDay' => $bestDay ? [
                'date' => Carbon::parse($bestDay->date),
                'count' => $bestDay->count,
                'dayName' => Carbon::parse($bestDay->date)->translatedFormat('l'),
            ] : null,
            'newMembers' => $newMembersThisWeek,
            'pointsIssued' => (int) $pointsThisWeek,
            'stampsIssued' => (int) $stampsThisWeek,
        ];
    }

    /**
     * Get recent activity combining transactions and stamp transactions.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getRecentActivity(string $partnerId, int $limit = 8)
    {
        // Get recent point transactions
        $pointTransactions = Transaction::with(['member', 'card'])
            ->where('created_by', $partnerId)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($tx) {
                // Normalize points to int — DB may return string for numeric columns
                $points = (int) $tx->points;

                return [
                    'id' => $tx->id,
                    'type' => $points >= 0 ? 'points_earned' : 'points_redeemed',
                    'amount' => abs($points),
                    'member' => $tx->member,
                    'card_name' => $tx->card?->name,
                    'description' => $points >= 0
                        ? trans('common.earned_points', ['points' => number_format(abs($points))])
                        : trans('common.redeemed_points', ['points' => number_format(abs($points))]),
                    'created_at' => $tx->created_at,
                    'icon' => $points >= 0 ? 'plus' : 'gift',
                    'color' => $points >= 0 ? 'emerald' : 'violet',
                ];
            });

        // Get recent stamp transactions
        $stampTransactions = StampTransaction::with(['member', 'stampCard'])
            ->whereHas('stampCard.club', function ($query) use ($partnerId) {
                $query->where('created_by', $partnerId);
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($tx) {
                $isReward = $tx->event === 'redeemed';
                // Normalize stamps to int — DB may return string for numeric columns
                $stamps = (int) $tx->stamps;

                return [
                    'id' => 'stamp_'.$tx->id,
                    'type' => $isReward ? 'reward_claimed' : 'stamp_earned',
                    'amount' => abs($stamps),
                    'member' => $tx->member,
                    'card_name' => $tx->stampCard?->name,
                    'description' => $isReward
                        ? trans('common.claimed_reward')
                        : trans_choice('common.earned_stamps', abs($stamps), ['stamps' => abs($stamps)]),
                    'created_at' => $tx->created_at,
                    'icon' => $isReward ? 'gift' : 'stamp',
                    'color' => $isReward ? 'pink' : 'purple',
                ];
            });

        // Merge and sort by date
        return $pointTransactions->concat($stampTransactions)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();
    }

    /**
     * Get key metrics for the partner.
     */
    private function getMetrics(string $partnerId): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $startOfMonth->copy()->subMonth();
        $endOfLastMonth = $startOfLastMonth->copy()->endOfMonth();

        // Total Members (distinct members who have transacted with this partner's cards or stamp cards)
        $totalMembers = Transaction::where('created_by', $partnerId)
            ->distinct('member_id')
            ->count('member_id');

        // Active Loyalty Cards
        $activeLoyaltyCards = Card::where('created_by', $partnerId)
            ->where('is_active', true)
            ->count();

        // Active Stamp Cards (via clubs)
        $activeStampCards = StampCard::whereHas('club', function ($query) use ($partnerId) {
            $query->where('created_by', $partnerId);
        })
            ->where('is_active', true)
            ->count();

        // Active Vouchers (includes batch vouchers where batch is active)
        $activeVouchers = Voucher::where('created_by', $partnerId)
            ->where('is_active', true)
            ->count();

        // Staff Members
        $staffCount = \App\Models\Staff::where('created_by', $partnerId)->count();

        // Total Active Rewards (across all cards)
        $totalRewards = \App\Models\Reward::whereHas('cards', function ($query) use ($partnerId) {
            $query->where('created_by', $partnerId);
        })->where('is_active', true)->count();

        // Total Transactions this month
        $transactionsThisMonth = Transaction::where('created_by', $partnerId)
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        // Total Transactions last month
        $transactionsLastMonth = Transaction::where('created_by', $partnerId)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();

        // Calculate transaction growth
        $transactionGrowth = $this->calculateGrowth($transactionsThisMonth, $transactionsLastMonth);

        // Points Issued this month (cast to int — sum() may return string)
        $pointsIssuedThisMonth = (int) Transaction::where('created_by', $partnerId)
            ->where('created_at', '>=', $startOfMonth)
            ->sum('points');

        // Points Issued last month
        $pointsIssuedLastMonth = (int) Transaction::where('created_by', $partnerId)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('points');

        // Calculate points growth
        $pointsGrowth = $this->calculateGrowth($pointsIssuedThisMonth, $pointsIssuedLastMonth);

        // Stamps Issued this month (from stamp transactions, cast to int — sum() may return string)
        $stampsIssuedThisMonth = (int) StampTransaction::whereHas('stampCard.club', function ($query) use ($partnerId) {
            $query->where('created_by', $partnerId);
        })
            ->where('created_at', '>=', $startOfMonth)
            ->where('event', 'earned')
            ->sum('stamps');

        // Stamps Issued last month
        $stampsIssuedLastMonth = (int) StampTransaction::whereHas('stampCard.club', function ($query) use ($partnerId) {
            $query->where('created_by', $partnerId);
        })
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->where('event', 'earned')
            ->sum('stamps');

        // Calculate stamps growth
        $stampsGrowth = $this->calculateGrowth($stampsIssuedThisMonth, $stampsIssuedLastMonth);

        return [
            'total_members' => $totalMembers,
            'active_loyalty_cards' => $activeLoyaltyCards,
            'active_stamp_cards' => $activeStampCards,
            'active_vouchers' => $activeVouchers,
            'transactions_this_month' => $transactionsThisMonth,
            'transaction_growth' => $transactionGrowth,
            'points_issued_this_month' => $pointsIssuedThisMonth,
            'points_growth' => $pointsGrowth,
            'stamps_issued_this_month' => $stampsIssuedThisMonth,
            'stamps_growth' => $stampsGrowth,
            'staff_count' => $staffCount,
            'total_rewards' => $totalRewards,
        ];
    }

    /**
     * Calculate percentage growth.
     * Normalizes inputs to float since DB aggregates may return strings.
     *
     * @param  int|float|string  $current
     * @param  int|float|string  $previous
     */
    private function calculateGrowth($current, $previous): float
    {
        // Normalize to float — DB sum() may return string for numeric columns
        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Get transaction timeline for charts.
     */
    private function getTransactionTimeline(string $partnerId, int $days): array
    {
        $since = Carbon::now()->subDays($days)->startOfDay();

        $data = Transaction::where('created_by', $partnerId)
            ->where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
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
            $values[] = $data->get($dateKey)?->count ?? 0;
            $current->addDay();
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'total' => array_sum($values),
        ];
    }

    /**
     * Get top members by transaction count (for leaderboard display).
     *
     * @return \Illuminate\Support\Collection
     */
    private function getTopMembers(string $partnerId, int $limit = 3)
    {
        return Transaction::where('created_by', $partnerId)
            ->select('member_id', DB::raw('count(*) as total_transactions'), DB::raw('sum(points) as total_points'))
            ->with('member')
            ->groupBy('member_id')
            ->orderByDesc('total_points')
            ->limit($limit)
            ->get();
    }

    /**
     * Get essential quick access links for the dashboard.
     * Reduced from 9 to 4 for cleaner UX - only show what matters most.
     *
     * Color coding (matches member my-cards.blade.php):
     * - Loyalty Cards → primary (blue)
     * - Stamp Cards → emerald (green)
     * - Vouchers → purple
     */
    public function getQuickAccessLinks(): array
    {
        $user = auth('partner')->user();
        $entitlements = app(EntitlementService::class);
        $links = [];

        $links[] = [
            'link' => route('partner.data.list', ['name' => 'members']),
            'icon' => 'users',
            'title' => trans('common.members'),
            'desc' => trans('common.partner_quick_access.members'),
            'color' => 'amber',
        ];

        if ($entitlements->can($user, 'loyalty_cards')) {
            $links[] = [
                'link' => route('partner.data.list', ['name' => 'cards']),
                'icon' => 'credit-card',
                'title' => trans('common.loyalty_cards'),
                'desc' => trans('common.partner_quick_access.loyalty_cards'),
                'color' => 'primary',
            ];
        }

        if ($entitlements->can($user, 'stamp_cards')) {
            $links[] = [
                'link' => route('partner.data.list', ['name' => 'stamp-cards']),
                'icon' => 'stamp',
                'title' => trans('common.stamp_cards'),
                'desc' => trans('common.partner_quick_access.stamp_cards'),
                'color' => 'emerald',
            ];
        }

        if ($entitlements->can($user, 'vouchers')) {
            $links[] = [
                'link' => route('partner.data.list', ['name' => 'vouchers']),
                'icon' => 'ticket',
                'title' => trans('common.vouchers'),
                'desc' => trans('common.partner_quick_access.vouchers'),
                'color' => 'purple',
            ];
        }

        return $links;
    }

    /**
     * Get the quick navigation blocks for the dashboard.
     *
     * @deprecated Use getQuickAccessLinks() for the simplified dashboard
     */
    public function getQuickNavigationBlocks(): array
    {
        $dashboardBlocks = [];

        $dashboardBlocks[] = [
            'link' => route('partner.data.list', ['name' => 'clubs']),
            'icon' => 'layers',
            'title' => trans('common.clubs'),
            'desc' => trans('common.partnerDashboardBlocks.clubs'),
            'color' => 'blue',
        ];

        $dashboardBlocks[] = [
            'link' => route('partner.data.list', ['name' => 'members']),
            'icon' => 'users',
            'title' => trans('common.members'),
            'desc' => trans('common.partnerDashboardBlocks.members'),
            'color' => 'emerald',
        ];

        $dashboardBlocks[] = [
            'link' => route('partner.data.list', ['name' => 'staff']),
            'icon' => 'briefcase',
            'title' => trans('common.staff'),
            'desc' => trans('common.partnerDashboardBlocks.staff', ['localeSlug' => '<span class="font-mono text-xs bg-secondary-100 dark:bg-secondary-800 px-1.5 py-0.5 rounded">/'.app()->make('i18n')->language->current->localeSlug.'/staff/</span>']),
            'color' => 'amber',
        ];

        $dashboardBlocks[] = [
            'link' => route('partner.data.list', ['name' => 'cards']),
            'icon' => 'credit-card',
            'title' => trans('common.loyalty_cards'),
            'desc' => trans('common.partnerDashboardBlocks.loyalty_cards'),
            'color' => 'indigo',
        ];

        $dashboardBlocks[] = [
            'link' => route('partner.data.list', ['name' => 'stamp-cards']),
            'icon' => 'stamp',
            'title' => trans('common.stamp_cards'),
            'desc' => trans('common.partnerDashboardBlocks.stamp_cards'),
            'color' => 'purple',
        ];

        $dashboardBlocks[] = [
            'link' => route('partner.data.list', ['name' => 'rewards']),
            'icon' => 'gift',
            'title' => trans('common.rewards'),
            'desc' => trans('common.partnerDashboardBlocks.rewards'),
            'color' => 'pink',
        ];

        $dashboardBlocks[] = [
            'link' => route('partner.data.list', ['name' => 'tiers']),
            'icon' => 'award',
            'title' => trans('common.tiers'),
            'desc' => trans('common.partnerDashboardBlocks.tiers'),
            'color' => 'cyan',
        ];

        $dashboardBlocks[] = [
            'link' => route('partner.analytics'),
            'icon' => 'trending-up',
            'title' => trans('common.loyalty_card_analytics'),
            'desc' => trans('common.partnerDashboardBlocks.loyalty_analytics'),
            'color' => 'violet',
        ];

        $dashboardBlocks[] = [
            'link' => route('partner.stamp-card-analytics'),
            'icon' => 'bar-chart',
            'title' => trans('common.stamp_card_analytics'),
            'desc' => trans('common.partnerDashboardBlocks.stamp_analytics'),
            'color' => 'fuchsia',
        ];

        return $dashboardBlocks;
    }

    /**
     * Get time-based greeting for the dashboard.
     */
    public function getGreeting(?string $timezone = null): string
    {
        return getGreeting($timezone);
    }
}
