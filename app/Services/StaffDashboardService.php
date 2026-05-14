<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Service for preparing all data required for the Staff Dashboard.
 * Designed for young, non-technical workers in retail/hospitality.
 *
 * Design Tenets:
 * - Simple metrics that matter to daily work
 * - Game-like achievements to encourage engagement
 * - Quick access to recent interactions
 */

namespace App\Services;

use App\Models\StampTransaction;
use App\Models\Transaction;
use Carbon\Carbon;

class StaffDashboardService
{
    /**
     * Get all data required for the staff dashboard.
     */
    public function getDashboardData(): array
    {
        $staffId = auth('staff')->id();

        return [
            'metrics' => $this->getMetrics($staffId),
            'recentMembers' => $this->getRecentMembers($staffId, 100), // Show recent members (all searchable anyway)
        ];
    }

    /**
     * Get key metrics for the staff member.
     */
    private function getMetrics(string $staffId): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();

        // Transactions processed today
        $transactionsToday = Transaction::where('staff_id', $staffId)
            ->where('created_at', '>=', $today)
            ->count();

        // Transactions processed this week
        $transactionsThisWeek = Transaction::where('staff_id', $staffId)
            ->where('created_at', '>=', $thisWeek)
            ->count();

        // Points awarded today
        $pointsToday = Transaction::where('staff_id', $staffId)
            ->where('created_at', '>=', $today)
            ->where('points', '>', 0)
            ->sum('points');

        // Rewards redeemed today
        $rewardsToday = Transaction::where('staff_id', $staffId)
            ->where('created_at', '>=', $today)
            ->where('event', 'staff_redeemed_points_for_reward')
            ->count();

        // Stamp transactions today
        $stampsToday = StampTransaction::where('staff_id', $staffId)
            ->where('created_at', '>=', $today)
            ->whereIn('event', [StampTransaction::EVENT_STAMP_EARNED, StampTransaction::EVENT_STAMPS_BONUS])
            ->sum('stamps');

        // Stamp transactions this week
        $stampsThisWeek = StampTransaction::where('staff_id', $staffId)
            ->where('created_at', '>=', $thisWeek)
            ->whereIn('event', [StampTransaction::EVENT_STAMP_EARNED, StampTransaction::EVENT_STAMPS_BONUS])
            ->count();

        // Stamp rewards redeemed today
        $stampRewardsToday = StampTransaction::where('staff_id', $staffId)
            ->where('created_at', '>=', $today)
            ->where('event', StampTransaction::EVENT_REWARD_REDEEMED)
            ->count();

        return [
            'transactions_today' => (int) $transactionsToday,
            'transactions_this_week' => (int) $transactionsThisWeek,
            'points_today' => (int) $pointsToday,
            'rewards_today' => (int) $rewardsToday,
            'stamps_today' => (int) $stampsToday,
            'stamps_this_week' => (int) $stampsThisWeek,
            'stamp_rewards_today' => (int) $stampRewardsToday,
        ];
    }

    /**
     * Get recent members this staff interacted with.
     * Includes loyalty points, stamps, and voucher redemptions.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getRecentMembers(string $staffId, int $limit = 3)
    {
        $daysAgo = config('default.staff_transaction_days_ago', 7);
        $cutoffDate = Carbon::now()->subDays($daysAgo);

        $allInteractions = collect();

        // 1. Loyalty transactions
        $loyaltyTransactions = Transaction::with('member', 'card')
            ->where('staff_id', $staffId)
            ->where('created_at', '>=', $cutoffDate)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($transaction) {
                return [
                    'member' => $transaction->member,
                    'type' => 'loyalty',
                    'date' => $transaction->created_at,
                    'event' => $transaction->event,
                    'url' => route('staff.transactions', [
                        'member_identifier' => $transaction->member->unique_identifier,
                        'card_identifier' => $transaction->card->unique_identifier,
                    ]),
                ];
            });

        // 2. Stamp transactions
        $stampTransactions = \App\Models\StampTransaction::with('member', 'stampCard')
            ->where('staff_id', $staffId)
            ->where('created_at', '>=', $cutoffDate)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($transaction) {
                return [
                    'member' => $transaction->member,
                    'type' => 'stamp',
                    'date' => $transaction->created_at,
                    'event' => $transaction->event,
                    'url' => route('staff.stamp.transactions', [
                        'member_identifier' => $transaction->member->unique_identifier,
                        'stamp_card_id' => $transaction->stamp_card_id,
                    ]),
                ];
            });

        // 3. Voucher redemptions
        $voucherRedemptions = \App\Models\VoucherRedemption::with('member', 'voucher')
            ->where('staff_id', $staffId)
            ->where('redeemed_at', '>=', $cutoffDate)
            ->orderByDesc('redeemed_at')
            ->get()
            ->map(function ($redemption) {
                return [
                    'member' => $redemption->member,
                    'type' => 'voucher',
                    'date' => $redemption->redeemed_at,
                    'event' => 'voucher_redeemed',
                    'url' => route('staff.voucher.transactions', [
                        'member_identifier' => $redemption->member->unique_identifier,
                        'voucher_id' => $redemption->voucher_id,
                    ]),
                ];
            });

        // Merge all interactions, sort by date, and get unique members
        return $loyaltyTransactions
            ->concat($stampTransactions)
            ->concat($voucherRedemptions)
            ->sortByDesc('date')
            ->unique(function ($item) {
                return $item['member']->id;
            })
            ->take($limit);
    }

    /**
     * Get the quick navigation blocks for the dashboard.
     */
    public function getQuickNavigationBlocks(): array
    {
        $dashboardBlocks = [];

        $dashboardBlocks[] = [
            'link' => route('staff.data.list', ['name' => 'cards']),
            'icon' => 'credit-card',
            'title' => trans('common.loyalty_cards'),
            'desc' => trans('common.staffDashboardBlocks.loyalty_cards'),
            'color' => 'indigo',
        ];

        $dashboardBlocks[] = [
            'link' => route('staff.data.list', ['name' => 'codes']),
            'icon' => 'ticket',
            'title' => trans('common.redemption_codes'),
            'desc' => trans('common.staffDashboardBlocks.redemption_codes'),
            'color' => 'amber',
        ];

        $dashboardBlocks[] = [
            'link' => route('staff.data.list', ['name' => 'members']),
            'icon' => 'users',
            'title' => trans('common.members'),
            'desc' => trans('common.staffDashboardBlocks.members'),
            'color' => 'emerald',
        ];

        $dashboardBlocks[] = [
            'link' => route('staff.data.list', ['name' => 'account']),
            'icon' => 'user-circle',
            'title' => trans('common.account_settings'),
            'desc' => trans('common.staffDashboardBlocks.account_settings'),
            'color' => 'violet',
        ];

        return $dashboardBlocks;
    }
}
