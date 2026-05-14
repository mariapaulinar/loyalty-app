<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\Card\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class VoucherAnalyticsController
 *
 * Handles voucher analytics display and data processing
 */
class VoucherAnalyticsController extends Controller
{
    /**
     * Display voucher analytics overview
     *
     * Shows analytics for all vouchers with sorting and filtering options
     */
    public function index(Request $request): Response
    {
        // Define the allowed values for the sort parameter (based on actual vouchers columns)
        $allowedSortValues = [
            'views,desc',
            'views,asc',
            'last_view,desc',
            'last_view,asc',
            'name,asc',
            'name,desc',
            'times_used,desc',
            'times_used,asc',
            'total_discount_given,desc',
            'total_discount_given,asc',
            'unique_members_used,desc',
            'unique_members_used,asc',
            'created_at,desc',
            'created_at,asc',
            'updated_at,desc',
            'updated_at,asc',
        ];

        // Extract query parameters or get from cookies if they exist
        $sort = $request->query('sort', $request->cookie('voucher_sort', 'views,desc'));
        $active_only = $request->query('active_only', $request->cookie('voucher_active_only', 'true'));

        // Validate the 'sort' query parameter
        if (! in_array($sort, $allowedSortValues)) {
            $sort = 'views,desc';
        }

        // Validate the 'active_only' query parameter
        if (! in_array($active_only, ['true', 'false'])) {
            $active_only = 'true';
        }

        // Convert active_only to a boolean
        $active_only_bool = filter_var($active_only, FILTER_VALIDATE_BOOLEAN);

        // Extract the column and direction from the sort value
        [$column, $direction] = explode(',', $sort);

        // Retrieve vouchers from the authenticated partner
        // NOTE: Exclude batch-generated vouchers (batch_id IS NOT NULL)
        // Analytics are only for manually created vouchers (templates)
        $partnerId = auth('partner')->user()->id;
        $query = Voucher::whereHas('club', function ($q) use ($partnerId) {
            $q->where('created_by', $partnerId);
        })
            ->whereNull('batch_id') // Exclude batch vouchers
            ->with('club');

        // Apply active filter if requested
        if ($active_only_bool) {
            $query->where('is_active', true);
        }

        // Apply sorting
        $vouchers = $query->orderBy($column, $direction)->get();

        // Prepare view
        $view = view('partner.voucher-analytics.index', [
            'vouchers' => $vouchers,
            'sort' => $sort,
            'active_only' => $active_only,
        ]);

        // Create cookies for sort and active_only
        $sortCookie = cookie('voucher_sort', $sort, 6 * 24 * 30);
        $activeOnlyCookie = cookie('voucher_active_only', $active_only, 6 * 24 * 30);

        // Attach cookies to the response and return it
        return response($view)->withCookie($sortCookie)->withCookie($activeOnlyCookie);
    }

    /**
     * Display detailed analytics for a specific voucher
     */
    public function show(string $locale, string $voucher_id, Request $request, AnalyticsService $analyticsService): Response
    {
        // Find the voucher
        $voucher = Voucher::with('club')->findOrFail($voucher_id);

        // Verify ownership
        $partnerId = auth('partner')->user()->id;
        if ($voucher->club->created_by !== $partnerId) {
            abort(403, 'Unauthorized access to voucher analytics');
        }

        // Get time range parameter
        $range = $request->query('range', $request->cookie('voucher_range', 'week'));

        // Initialize results
        $resultsFound = false;
        $voucherViews = [];
        $voucherViewsDifference = '-';

        // Get current period analytics based on range
        [$period, $offset] = explode(',', $range.',0');
        $offset = (int) $offset;

        $currentDate = now()->addDays($offset * match ($period) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            'year' => 365,
        })->format('Y-m-d');

        // Get analytics for the current period
        $voucherViews = match ($period) {
            'day' => $analyticsService->voucherViewsDay($voucher_id, $currentDate),
            'week' => $analyticsService->voucherViewsWeek($voucher_id, $currentDate),
            'month' => $analyticsService->voucherViewsMonth($voucher_id, $currentDate),
            'year' => $analyticsService->voucherViewsYear($voucher_id, $currentDate),
        };

        // Get analytics for the previous period to calculate difference
        $previousDate = now()->addDays(($offset - 1) * match ($period) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            'year' => 365,
        })->format('Y-m-d');

        $previousVoucherViews = match ($period) {
            'day' => $analyticsService->voucherViewsDay($voucher_id, $previousDate),
            'week' => $analyticsService->voucherViewsWeek($voucher_id, $previousDate),
            'month' => $analyticsService->voucherViewsMonth($voucher_id, $previousDate),
            'year' => $analyticsService->voucherViewsYear($voucher_id, $previousDate),
        };

        // Calculate percentage difference
        if ($previousVoucherViews['total'] > 0) {
            $voucherViewsDifference = round((($voucherViews['total'] - $previousVoucherViews['total']) / $previousVoucherViews['total']) * 100, 2);
        } elseif ($voucherViews['total'] > 0) {
            $voucherViewsDifference = 100;
        } else {
            $voucherViewsDifference = 0;
        }

        $resultsFound = $voucherViews['total'] > 0;

        $viewData = [
            'voucher' => $voucher,
            'range' => $range,
            'resultsFound' => $resultsFound,
            'voucherViews' => $voucherViews,
            'voucherViewsDifference' => $voucherViewsDifference,
        ];

        // Create cookie to remember the range preference
        $rangeCookie = cookie('voucher_range', $range, 6 * 24 * 30);

        return response()
            ->view('partner.voucher-analytics.show', $viewData)
            ->withCookie($rangeCookie);
    }
}
