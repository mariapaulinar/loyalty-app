<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\StampCard;
use App\Services\Card\AnalyticsService;
use Illuminate\Http\Request;

/**
 * Class StampCardAnalyticsController
 *
 * Handles stamp card analytics display and data processing
 */
class StampCardAnalyticsController extends Controller
{
    /**
     * Display stamp card analytics overview
     *
     * Shows analytics for all stamp cards with sorting and filtering options
     */
    public function index(Request $request): \Illuminate\Http\Response
    {
        // Define the allowed values for the sort parameter (based on actual stamp_cards columns)
        $allowedSortValues = [
            'views,desc',
            'views,asc',
            'last_view,desc',
            'last_view,asc',
            'name,asc',
            'name,desc',
            'total_stamps_issued,desc',
            'total_stamps_issued,asc',
            'total_completions,desc',
            'total_completions,asc',
            'total_redemptions,desc',
            'total_redemptions,asc',
            'created_at,desc',
            'created_at,asc',
            'updated_at,desc',
            'updated_at,asc',
        ];

        // Extract query parameters or get from cookies if they exist
        $sort = $request->query('sort', $request->cookie('stamp_card_sort', 'views,desc'));
        $active_only = $request->query('active_only', $request->cookie('stamp_card_active_only', 'true'));

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

        // Retrieve stamp cards from the authenticated partner
        $partnerId = auth('partner')->user()->id;
        $query = StampCard::whereHas('club', function ($q) use ($partnerId) {
            $q->where('created_by', $partnerId);
        })->with('club');

        // Apply active filter if requested
        if ($active_only_bool) {
            $query->where('is_active', true);
        }

        // Apply sorting
        $stampCards = $query->orderBy($column, $direction)->get();

        // Prepare view
        $view = view('partner.stamp-card-analytics.index', [
            'stampCards' => $stampCards,
            'sort' => $sort,
            'active_only' => $active_only,
        ]);

        // Create cookies for sort and active_only
        $sortCookie = cookie('stamp_card_sort', $sort, 6 * 24 * 30);
        $activeOnlyCookie = cookie('stamp_card_active_only', $active_only, 6 * 24 * 30);

        // Attach cookies to the response and return it
        return response($view)->withCookie($sortCookie)->withCookie($activeOnlyCookie);
    }

    /**
     * Display detailed analytics for a specific stamp card
     */
    public function show(string $locale, string $stamp_card_id, Request $request, AnalyticsService $analyticsService): \Illuminate\Http\Response
    {
        // Find the stamp card
        $stampCard = StampCard::with('club')->findOrFail($stamp_card_id);

        // Verify ownership
        $partnerId = auth('partner')->user()->id;
        if ($stampCard->club->created_by !== $partnerId) {
            abort(403, 'Unauthorized access to stamp card analytics');
        }

        // Get time range parameter
        $range = $request->query('range', $request->cookie('stamp_card_range', 'week'));

        // Initialize results
        $resultsFound = false;
        $stampCardViews = [];
        $stampCardViewsDifference = '-';

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
        $stampCardViews = match ($period) {
            'day' => $analyticsService->stampCardViewsDay($stamp_card_id, $currentDate),
            'week' => $analyticsService->stampCardViewsWeek($stamp_card_id, $currentDate),
            'month' => $analyticsService->stampCardViewsMonth($stamp_card_id, $currentDate),
            'year' => $analyticsService->stampCardViewsYear($stamp_card_id, $currentDate),
        };

        // Get analytics for the previous period to calculate difference
        $previousDate = now()->addDays(($offset - 1) * match ($period) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            'year' => 365,
        })->format('Y-m-d');

        $previousStampCardViews = match ($period) {
            'day' => $analyticsService->stampCardViewsDay($stamp_card_id, $previousDate),
            'week' => $analyticsService->stampCardViewsWeek($stamp_card_id, $previousDate),
            'month' => $analyticsService->stampCardViewsMonth($stamp_card_id, $previousDate),
            'year' => $analyticsService->stampCardViewsYear($stamp_card_id, $previousDate),
        };

        // Calculate percentage difference
        if ($previousStampCardViews['total'] > 0) {
            $stampCardViewsDifference = round((($stampCardViews['total'] - $previousStampCardViews['total']) / $previousStampCardViews['total']) * 100, 2);
        } elseif ($stampCardViews['total'] > 0) {
            $stampCardViewsDifference = 100;
        } else {
            $stampCardViewsDifference = 0;
        }

        $resultsFound = $stampCardViews['total'] > 0;

        $viewData = [
            'stampCard' => $stampCard,
            'range' => $range,
            'resultsFound' => $resultsFound,
            'stampCardViews' => $stampCardViews,
            'stampCardViewsDifference' => $stampCardViewsDifference,
        ];

        // Create cookie to remember the range preference
        $rangeCookie = cookie('stamp_card_range', $range, 6 * 24 * 30);

        return response()
            ->view('partner.stamp-card-analytics.card', $viewData)
            ->withCookie($rangeCookie);
    }
}
