<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\StampTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StampReviewController extends Controller
{
    /**
     * Store a member's experience rating for a staff-awarded stamp.
     *
     * POST /{locale}/stamp-transactions/{transactionId}/review
     */
    public function store(string $locale, string $transactionId, Request $request): JsonResponse
    {
        $member = auth('member')->user();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $transaction = StampTransaction::query()
            ->where('id', $transactionId)
            ->where('member_id', $member->id)
            ->where('event', StampTransaction::EVENT_STAMP_EARNED)
            ->whereNotNull('staff_id')
            ->firstOrFail();

        if ($transaction->review !== null) {
            return response()->json([
                'success' => false,
                'message' => trans('common.stamp_review_already_submitted'),
            ], 422);
        }

        $transaction->update([
            'review' => [
                'rating' => (int) $validated['rating'],
                'submitted_at' => now()->toIso8601String(),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => trans('common.stamp_review_thank_you'),
        ]);
    }
}
