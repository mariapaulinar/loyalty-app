<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\StaffDashboardService;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * The StaffDashboardService instance.
     */
    protected StaffDashboardService $dashboardService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(StaffDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display the staff index page.
     */
    public function index(string $locale, Request $request): \Illuminate\View\View
    {
        $dashboardData = $this->dashboardService->getDashboardData();
        $dashboardBlocks = $this->dashboardService->getQuickNavigationBlocks();
        $greeting = getGreeting(auth('staff')->user()->time_zone);

        return view('staff.index', compact('dashboardData', 'dashboardBlocks', 'greeting'));
    }

    /**
     * Display the QR scanner.
     */
    public function showQrScanner(string $locale, Request $request): \Illuminate\View\View
    {
        return view('staff.qr.scanner');
    }

    /**
     * Search members for autocomplete.
     *
     * Returns members this staff has interacted with in the last X days.
     * Searches by name, email, or device code (for anonymous members).
     * Includes loyalty points, stamp cards, and voucher redemptions.
     */
    public function searchMembers(string $locale, Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $request->input('q', '');
        $staffId = auth('staff')->id();
        $daysAgo = config('default.staff_transaction_days_ago', 7);
        $cutoffDate = \Carbon\Carbon::now()->subDays($daysAgo);

        if (strlen($query) < 2) {
            return response()->json(['members' => []]);
        }

        $results = collect();

        // Build the member search closure - includes device_code and unique_identifier for anonymous members
        $memberSearchClosure = function ($q) use ($query) {
            $q->where('name', 'LIKE', "%{$query}%")
                ->orWhere('email', 'LIKE', "%{$query}%")
                ->orWhere('device_code', 'LIKE', "%{$query}%")
                ->orWhere('unique_identifier', 'LIKE', "%{$query}%");
        };

        // 1. Get loyalty card transactions
        $loyaltyTransactions = \App\Models\Transaction::with('member', 'card')
            ->where('staff_id', $staffId)
            ->where('created_at', '>=', $cutoffDate)
            ->whereHas('member', $memberSearchClosure)
            ->get()
            ->map(function ($transaction) {
                return [
                    'member' => $transaction->member,
                    'type' => 'loyalty',
                    'date' => $transaction->created_at,
                    'url' => route('staff.transactions', [
                        'member_identifier' => $transaction->member->unique_identifier,
                        'card_identifier' => $transaction->card->unique_identifier,
                    ]),
                ];
            });

        // 2. Get stamp card transactions
        $stampTransactions = \App\Models\StampTransaction::with('member', 'stampCard')
            ->where('staff_id', $staffId)
            ->where('created_at', '>=', $cutoffDate)
            ->whereHas('member', $memberSearchClosure)
            ->get()
            ->map(function ($transaction) {
                return [
                    'member' => $transaction->member,
                    'type' => 'stamp',
                    'date' => $transaction->created_at,
                    'url' => route('staff.stamp.transactions', [
                        'member_identifier' => $transaction->member->unique_identifier,
                        'stamp_card_id' => $transaction->stamp_card_id,
                    ]),
                ];
            });

        // 3. Get voucher redemptions
        $voucherRedemptions = \App\Models\VoucherRedemption::with('member', 'voucher')
            ->where('staff_id', $staffId)
            ->where('redeemed_at', '>=', $cutoffDate)
            ->whereHas('member', $memberSearchClosure)
            ->get()
            ->map(function ($redemption) {
                return [
                    'member' => $redemption->member,
                    'type' => 'voucher',
                    'date' => $redemption->redeemed_at,
                    'url' => route('staff.voucher.transactions', [
                        'member_identifier' => $redemption->member->unique_identifier,
                        'voucher_id' => $redemption->voucher_id,
                    ]),
                ];
            });

        // Merge all transactions
        $results = $loyaltyTransactions
            ->concat($stampTransactions)
            ->concat($voucherRedemptions)
            ->sortByDesc('date')
            ->unique(function ($item) {
                return $item['member']->id;
            })
            ->take(8)
            ->map(function ($item) {
                $member = $item['member'];
                $email = $member->email;

                // For anonymous members (null email), show identifier as secondary info
                // If device_code equals name, show unique_identifier to avoid redundancy
                $secondaryInfo = $email
                    ? hideEmailAddress($email)
                    : ($member->device_code === $member->name
                        ? $member->unique_identifier
                        : $member->device_code);

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $secondaryInfo,
                    'initials' => substr($member->name, 0, 1),
                    'type' => $item['type'],
                    'last_interaction' => $item['date']->diffForHumans(),
                    'url' => $item['url'],
                ];
            })
            ->values();

        return response()->json(['members' => $results]);
    }
}
