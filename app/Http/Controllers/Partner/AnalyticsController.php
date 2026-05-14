<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Services\Card\AnalyticsService;
use App\Services\Card\CardService;
use Illuminate\Http\Request;

/**
 * Class AnalyticsController
 */
class AnalyticsController extends Controller
{
    /**
     * Handles the request to display the analytics page.
     *
     * This method retrieves the analytics data, applies sorting and filtering
     * options from the request or from cookies, and returns the analytics view
     * with attached cookies for the sorting and active_only filter.
     */
    public function showAnalytics(Request $request, CardService $cardService): \Illuminate\Http\Response
    {

        // Define the allowed values for the sort parameter
        $allowedSortValues = [
            'views,desc',
            'views,asc',
            'last_view,desc',
            'last_view,asc',
            'total_amount_purchased,desc',
            'total_amount_purchased,asc',
            'number_of_points_issued,desc',
            'number_of_points_issued,asc',
            'number_of_points_redeemed,desc',
            'number_of_points_redeemed,asc',
            'number_of_rewards_redeemed,desc',
            'number_of_rewards_redeemed,asc',
            'last_points_issued_at,desc',
            'last_points_issued_at,asc',
            'last_reward_redeemed_at,desc',
            'last_reward_redeemed_at,asc',
        ];

        // Extract query parameters or get from cookies if they exist
        $sort = $request->query('sort', $request->cookie('sort', 'views,desc'));
        $active_only = $request->query('active_only', $request->cookie('active_only', 'true'));

        // Validate the 'sort' query parameter and reset it to default if it's not in the allowed sort values
        if (! in_array($sort, $allowedSortValues)) {
            $sort = 'views,desc';
        }

        // Validate the 'active_only' query parameter and reset it to default if it's not 'true' or 'false'
        if (! in_array($active_only, ['true', 'false'])) {
            $active_only = 'true';
        }

        // Convert active_only to a boolean
        $active_only = filter_var($active_only, FILTER_VALIDATE_BOOLEAN);

        // Extract the column and direction from the sort value
        [$column, $direction] = explode(',', $sort);

        // Retrieve cards from the authenticated partner
        $partnerId = auth('partner')->user()->id;
        $cards = $cardService->findCardsFromPartner($partnerId, $column, $direction, $active_only ? 'is_active' : null, $active_only);

        // Prepare view
        $view = view('partner.loyalty-card-analytics.index', compact('cards', 'sort', 'active_only'));

        // Convert boolean values back to strings for the cookie
        $active_only = $active_only ? 'true' : 'false';

        // Create cookies for sort and active_only
        $sortCookie = cookie('sort', $sort, 6 * 24 * 30);
        $activeOnlyCookie = cookie('active_only', $active_only, 6 * 24 * 30);

        // Attach cookies to the response and return it
        return response($view)->withCookie($sortCookie)->withCookie($activeOnlyCookie);
    }

    /**
     * Display detailed analytics data for a specific loyalty card.
     *
     * This method handles the retrieval and processing of various analytics metrics including:
     * - Card and reward views
     * - Points issued and redeemed
     * - Rewards claimed
     * - Point transfer requests (sent and received)
     *
     * The data can be viewed in different time periods (day/week/month/year) with support
     * for comparing current period with previous period metrics.
     *
     * @param  string  $locale  Current locale for translations
     * @param  string  $card_id  ID of the card to analyze
     * @param  Request  $request  HTTP request containing query parameters
     * @param  AnalyticsService  $analyticsService  Service for analytics calculations
     * @param  CardService  $cardService  Service for card operations
     * @return \Illuminate\Http\Response View response with analytics data
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException When card is not found
     */
    public function showCardAnalytics(
        string $locale,
        string $card_id,
        Request $request,
        AnalyticsService $analyticsService,
        CardService $cardService
    ): \Illuminate\Http\Response {
        // Extract and validate time range parameters
        [$range, $rangePeriod, $offset, $date, $datePreviousPeriod] = $this->processTimeRangeParameters($request);

        // Find and validate the card
        $card = $cardService->findCard($card_id);
        if (! $card) {
            abort(404, 'Card not found');
        }

        // Fetch analytics data for the selected time period
        $analytics = $this->fetchAnalyticsData($analyticsService, $card, $rangePeriod, $date, $datePreviousPeriod);

        // Calculate percentage differences between current and previous period
        $differences = $this->calculatePeriodDifferences($analytics);

        // Determine if any results were found
        $resultsFound = $this->checkForResults($analytics);

        // Prepare and return the view with analytics data
        $viewData = array_merge([
            'card' => $card,
            'range' => $range,
            'resultsFound' => $resultsFound,
        ], $this->flattenAnalyticsData($analytics, $differences));

        // Prepare and return the view with analytics data
        return $this->prepareAnalyticsResponse(
            'partner.loyalty-card-analytics.card',
            $viewData,
            $range
        );
    }

    /**
     * Process and validate time range parameters from the request.
     *
     * @return array [range, rangePeriod, offset, date, datePreviousPeriod]
     */
    private function processTimeRangeParameters(Request $request): array
    {
        $range = $request->query('range', $request->cookie('range', 'week'));
        $offset = 0;
        $rangePeriod = $range;

        // Parse range and offset if provided in format "period,offset"
        if (strpos($range, ',') !== false) {
            [$rangePeriod, $offset] = explode(',', $range);
        }

        // Validate offset
        $offset = (is_numeric($offset) && $offset <= 1) ? (int) $offset : 1;

        // Determine current date based on range period
        $currentDate = match ($rangePeriod) {
            'day', 'week' => date('Y-m-d'),
            'month' => date('Y-m-01'),
            default => date('Y-01-01')
        };

        // Calculate current and previous period dates
        $date = (new \DateTime($currentDate))
            ->modify("$offset $rangePeriod")
            ->format('Y-m-d');

        $datePreviousPeriod = (new \DateTime($currentDate))
            ->modify(($offset - 1)." $rangePeriod")
            ->format('Y-m-d');

        return [$range, $rangePeriod, $offset, $date, $datePreviousPeriod];
    }

    /**
     * Fetch analytics data for the specified time period.
     */
    private function fetchAnalyticsData(
        AnalyticsService $analyticsService,
        Card $card,
        string $rangePeriod,
        string $date,
        string $datePreviousPeriod
    ): array {
        // Determine which analytics method to call based on the range period
        $methodSuffix = match ($rangePeriod) {
            'day' => 'Day',
            'week' => 'Week',
            'month' => 'Month',
            default => 'Year'
        };

        // Define metrics to fetch
        $metrics = [
            'cardViews',
            'rewardViews',
            'pointsIssued',
            'pointsRedeemed',
            'rewardsClaimed',
            'pointRequests',
        ];

        $currentPeriod = [];
        $previousPeriod = [];

        // Fetch current period metrics
        foreach ($metrics as $metric) {
            if ($metric === 'pointRequests') {
                // Handle point requests separately as they have received/sent variants
                $currentPeriod['pointRequestsReceived'] = $analyticsService->{"pointRequests$methodSuffix"}(
                    $card->id,
                    'received',
                    $date
                );
                $currentPeriod['pointRequestsSent'] = $analyticsService->{"pointRequests$methodSuffix"}(
                    $card->id,
                    'sent',
                    $date
                );
            } else {
                $method = $metric.$methodSuffix;
                $currentPeriod[$metric] = $analyticsService->$method($card->id, $date);
            }
        }

        // Fetch previous period metrics for comparison
        foreach ($metrics as $metric) {
            if ($metric === 'pointRequests') {
                $previousPeriod['pointRequestsReceived'] = $analyticsService->{"pointRequests$methodSuffix"}(
                    $card->id,
                    'received',
                    $datePreviousPeriod
                );
                $previousPeriod['pointRequestsSent'] = $analyticsService->{"pointRequests$methodSuffix"}(
                    $card->id,
                    'sent',
                    $datePreviousPeriod
                );
            } else {
                $method = $metric.$methodSuffix;
                $previousPeriod[$metric] = $analyticsService->$method($card->id, $datePreviousPeriod);
            }
        }

        return [
            'current' => $currentPeriod,
            'previous' => $previousPeriod,
        ];
    }

    /**
     * Calculate percentage differences between current and previous period metrics.
     */
    private function calculatePeriodDifferences(array $analytics): array
    {
        $differences = [];
        $metrics = [
            'cardViews',
            'rewardViews',
            'pointsIssued',
            'pointsRedeemed',
            'rewardsClaimed',
            'pointRequestsReceived',
            'pointRequestsSent',
        ];

        foreach ($metrics as $metric) {
            $current = $analytics['current'][$metric]['total'] ?? 0;
            $previous = $analytics['previous'][$metric]['total'] ?? 0;

            $differences[$metric.'Difference'] = $this->calculatePercentageDifference($current, $previous);
        }

        return $differences;
    }

    /**
     * Calculate percentage difference between two values.
     *
     * @return string Formatted percentage difference or '-' if no comparison possible
     */
    private function calculatePercentageDifference(int|float $current, int|float $previous): string
    {
        if ($previous != 0) {
            return number_format((($current - $previous) / $previous) * 100, 0);
        }

        return $current > 0 ? '100' : '-';
    }

    /**
     * Check if any analytics results were found.
     */
    private function checkForResults(array $analytics): bool
    {
        $current = $analytics['current'];

        return array_sum(array_map(fn ($metric) => $metric['total'] ?? 0, $current)) > 0;
    }

    /**
     * Prepare the HTTP response with analytics data and cookie.
     */
    private function prepareAnalyticsResponse(string $view, array $data, string $range): \Illuminate\Http\Response
    {
        // Create cookie to remember the range preference (6 months expiration)
        $rangeCookie = cookie('range', $range, 6 * 24 * 30);

        return response()
            ->view($view, $data)
            ->withCookie($rangeCookie);
    }

    /**
     * Flatten analytics data for view consumption.
     */
    private function flattenAnalyticsData(array $analytics, array $differences): array
    {
        $flatData = [];

        // Flatten current period metrics
        foreach ($analytics['current'] as $metric => $data) {
            $flatData[$metric] = $data;
        }

        // Add percentage differences
        foreach ($differences as $key => $value) {
            $flatData[$key] = $value;
        }

        return $flatData;
    }
}
