<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StampTransaction;
use Illuminate\Support\Collection;

/**
 * Aggregates member experience ratings stored on staff-awarded stamp transactions.
 */
class StampExperienceReviewService
{
    /**
     * Summary stats for a partner, optionally scoped to one stamp card.
     *
     * @return array{
     *     average_rating: float|null,
     *     total_reviews: int,
     *     total_eligible: int,
     *     response_rate: float,
     *     distribution: array<int, int>
     * }
     */
    public function getSummary(string $partnerId, ?string $stampCardId = null): array
    {
        $eligible = $this->eligibleQuery($partnerId, $stampCardId);
        $reviewed = (clone $eligible)->whereNotNull('review');

        $totalEligible = (clone $eligible)->count();
        $totalReviews = (clone $reviewed)->count();

        $distribution = array_fill(1, 5, 0);
        $ratings = [];

        foreach ((clone $reviewed)->get(['review']) as $transaction) {
            $rating = (int) ($transaction->review['rating'] ?? 0);

            if ($rating >= 1 && $rating <= 5) {
                $distribution[$rating]++;
                $ratings[] = $rating;
            }
        }

        $average = count($ratings) > 0
            ? round(array_sum($ratings) / count($ratings), 1)
            : null;

        $responseRate = $totalEligible > 0
            ? round(($totalReviews / $totalEligible) * 100, 1)
            : 0.0;

        return [
            'average_rating' => $average,
            'total_reviews' => $totalReviews,
            'total_eligible' => $totalEligible,
            'response_rate' => $responseRate,
            'distribution' => $distribution,
        ];
    }

    /**
     * Recent submitted reviews for display in the partner panel.
     *
     * @return Collection<int, StampTransaction>
     */
    public function getRecentReviews(string $partnerId, ?string $stampCardId = null, int $limit = 10): Collection
    {
        return $this->eligibleQuery($partnerId, $stampCardId)
            ->whereNotNull('review')
            ->with(['member', 'stampCard', 'staff'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Per-card review summaries for the analytics overview grid.
     *
     * @param  array<int, string>  $stampCardIds
     * @return array<string, array{average_rating: float|null, total_reviews: int}>
     */
    public function getSummariesForCards(string $partnerId, array $stampCardIds): array
    {
        if ($stampCardIds === []) {
            return [];
        }

        $summaries = [];

        foreach ($stampCardIds as $stampCardId) {
            $summary = $this->getSummary($partnerId, $stampCardId);
            $summaries[$stampCardId] = [
                'average_rating' => $summary['average_rating'],
                'total_reviews' => $summary['total_reviews'],
            ];
        }

        return $summaries;
    }

    /**
     * Base query: staff-awarded stamps for this partner's cards.
     */
    private function eligibleQuery(string $partnerId, ?string $stampCardId = null)
    {
        $query = StampTransaction::query()
            ->where('event', StampTransaction::EVENT_STAMP_EARNED)
            ->whereNotNull('staff_id')
            ->whereHas('stampCard', function ($cardQuery) use ($partnerId) {
                $cardQuery->where('created_by', $partnerId);
            });

        if ($stampCardId !== null) {
            $query->where('stamp_card_id', $stampCardId);
        }

        return $query;
    }
}
