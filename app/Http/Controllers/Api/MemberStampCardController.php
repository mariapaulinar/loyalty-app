<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StampCard;
use App\Models\StampCardMember;
use App\Services\StampService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Member Stamp Card API Controller
 *
 * Handles member stamp card operations (following/enrolling).
 */
class MemberStampCardController extends Controller
{
    public function __construct(
        protected StampService $stampService
    ) {}

    /**
     * Get all stamp cards the member is enrolled in.
     *
     * @OA\Get(
     *     path="/{locale}/v1/member/my-stamp-cards",
     *     operationId="getMemberMyStampCards",
     *     tags={"Member"},
     *     summary="Get enrolled stamp cards",
     *     description="Retrieve all stamp cards the member has added to their collection (enrolled).",
     *     security={{"member_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\Response(response=200, description="Stamp cards retrieved", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/StampCard"))),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getMyStampCards(string $locale, Request $request): JsonResponse
    {
        $member = $request->user('member_api');

        $enrollments = StampCardMember::with('stampCard.club')
            ->where('member_id', $member->id)
            ->where('is_active', true)
            ->get();

        $stampCards = $enrollments->map(function ($enrollment) {
            $card = $enrollment->stampCard;
            $card->current_stamps = $enrollment->current_stamps;
            $card->pending_rewards = $enrollment->pending_rewards;
            $card->completed_count = $enrollment->completed_count;
            $card->redeemed_count = $enrollment->redeemed_count;
            $card->enrolled_at = $enrollment->enrolled_at;
            $card->last_stamp_at = $enrollment->last_stamp_at;

            return $card;
        });

        return response()->json($stampCards);
    }

    /**
     * Enroll in a stamp card (add to My Cards).
     *
     * @OA\Post(
     *     path="/{locale}/v1/member/stamp-cards/{stampCardId}/enroll",
     *     operationId="enrollMemberStampCard",
     *     tags={"Member"},
     *     summary="Add stamp card to My Cards",
     *     description="Enroll the member in a stamp card program (add to My Cards). This is equivalent to 'following' the stamp card.",
     *     security={{"member_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="stampCardId", in="path", description="Stamp card ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Successfully enrolled", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Successfully enrolled in stamp card"),
     *         @OA\Property(property="enrollment", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Stamp card not found")
     * )
     */
    public function enroll(string $locale, Request $request, string $stampCardId): JsonResponse
    {
        $member = $request->user('member_api');

        $stampCard = StampCard::where('is_active', true)->find($stampCardId);

        if (! $stampCard) {
            return response()->json(['message' => 'Stamp card not found'], 404);
        }

        $enrollment = $this->stampService->enrollMember($stampCard, $member);

        return response()->json([
            'message' => 'Successfully enrolled in stamp card',
            'enrollment' => [
                'stamp_card_id' => $stampCard->id,
                'current_stamps' => $enrollment->current_stamps,
                'stamps_required' => $stampCard->stamps_required,
                'pending_rewards' => $enrollment->pending_rewards,
                'enrolled_at' => $enrollment->enrolled_at,
            ],
        ]);
    }

    /**
     * Unenroll from a stamp card (remove from My Cards).
     *
     * @OA\Delete(
     *     path="/{locale}/v1/member/stamp-cards/{stampCardId}/enroll",
     *     operationId="unenrollMemberStampCard",
     *     tags={"Member"},
     *     summary="Remove stamp card from My Cards",
     *     description="Unenroll the member from a stamp card program. The enrollment record is marked inactive but not deleted, preserving progress.",
     *     security={{"member_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="stampCardId", in="path", description="Stamp card ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Successfully unenrolled", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Stamp card removed from My Cards")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Stamp card not found or not enrolled")
     * )
     */
    public function unenroll(string $locale, Request $request, string $stampCardId): JsonResponse
    {
        $member = $request->user('member_api');

        $stampCard = StampCard::find($stampCardId);

        if (! $stampCard) {
            return response()->json(['message' => 'Stamp card not found'], 404);
        }

        $enrollment = StampCardMember::where('stamp_card_id', $stampCard->id)
            ->where('member_id', $member->id)
            ->where('is_active', true)
            ->first();

        if (! $enrollment) {
            return response()->json(['message' => 'Not enrolled in this stamp card'], 404);
        }

        // Mark as inactive (preserves progress)
        $enrollment->is_active = false;
        $enrollment->save();

        return response()->json(['message' => 'Stamp card removed from My Cards']);
    }
}
