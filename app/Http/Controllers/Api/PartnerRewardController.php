<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Partner Reward Management API Controller
 *
 * Handles CRUD operations for rewards.
 */
class PartnerRewardController extends Controller
{
    /**
     * List all rewards for the partner.
     *
     * @OA\Get(
     *     path="/{locale}/v1/partner/rewards",
     *     operationId="getPartnerRewards",
     *     tags={"Partner"},
     *     summary="List all rewards",
     *     description="Retrieve all rewards owned by the authenticated partner.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="card_id", in="query", description="Filter by card ID", @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Rewards retrieved", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Reward"))),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getRewards(string $locale, Request $request): JsonResponse
    {
        $partner = $request->user('partner_api');

        $query = $partner->rewards();

        if ($request->has('card_id')) {
            $query->where('card_id', $request->input('card_id'));
        }

        $rewards = $query->get();

        return response()->json($rewards);
    }

    /**
     * Get a specific reward.
     *
     * @OA\Get(
     *     path="/{locale}/v1/partner/rewards/{rewardId}",
     *     operationId="getPartnerReward",
     *     tags={"Partner"},
     *     summary="Get reward details",
     *     description="Retrieve details of a specific reward by ID.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="rewardId", in="path", description="Reward ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Reward details", @OA\JsonContent(ref="#/components/schemas/Reward")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Reward not found")
     * )
     */
    public function getReward(string $locale, Request $request, string $rewardId): JsonResponse
    {
        $partner = $request->user('partner_api');

        $reward = $partner->rewards()->find($rewardId);

        if (! $reward) {
            return response()->json(['message' => 'Reward not found'], 404);
        }

        return response()->json($reward);
    }

    /**
     * Create a new reward.
     *
     * @OA\Post(
     *     path="/{locale}/v1/partner/rewards",
     *     operationId="createPartnerReward",
     *     tags={"Partner"},
     *     summary="Create a new reward",
     *     description="Create a new reward for a loyalty card.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "card_id", "points_required"},
     *             @OA\Property(property="title", type="string", description="Reward title", example="Free Coffee"),
     *             @OA\Property(property="card_id", type="string", format="uuid", description="Card ID", example="019b8d49-5678-7abc-8def-27f2107a8ec1"),
     *             @OA\Property(property="description", type="string", description="Reward description", example="Enjoy a free coffee of your choice"),
     *             @OA\Property(property="points_required", type="integer", description="Points needed to redeem", example=100),
     *             @OA\Property(property="is_active", type="boolean", description="Active status", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Reward created", @OA\JsonContent(ref="#/components/schemas/Reward")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createReward(string $locale, Request $request): JsonResponse
    {
        $partner = $request->user('partner_api');

        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'card_id' => 'required|exists:cards,id',
            'description' => 'nullable|string|max:500',
            'points_required' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        // Verify card belongs to partner
        $card = $partner->cards()->find($validated['card_id']);
        if (! $card) {
            return response()->json(['message' => 'Card not found'], 404);
        }

        $reward = Reward::create([
            'title' => $validated['title'],
            'card_id' => $validated['card_id'],
            'created_by' => $partner->id,
            'description' => $validated['description'] ?? null,
            'points_required' => $validated['points_required'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json($reward, 201);
    }

    /**
     * Update a reward.
     *
     * @OA\Put(
     *     path="/{locale}/v1/partner/rewards/{rewardId}",
     *     operationId="updatePartnerReward",
     *     tags={"Partner"},
     *     summary="Update a reward",
     *     description="Update an existing reward's details.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="rewardId", in="path", description="Reward ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Reward Title"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="points_required", type="integer", example=150),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Reward updated", @OA\JsonContent(ref="#/components/schemas/Reward")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Reward not found")
     * )
     */
    public function updateReward(string $locale, Request $request, string $rewardId): JsonResponse
    {
        $partner = $request->user('partner_api');

        $reward = $partner->rewards()->find($rewardId);

        if (! $reward) {
            return response()->json(['message' => 'Reward not found'], 404);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:500',
            'points_required' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $reward->update(array_filter($validated, fn ($v) => $v !== null));

        return response()->json($reward);
    }

    /**
     * Delete a reward.
     *
     * @OA\Delete(
     *     path="/{locale}/v1/partner/rewards/{rewardId}",
     *     operationId="deletePartnerReward",
     *     tags={"Partner"},
     *     summary="Delete a reward",
     *     description="Delete a reward. This action cannot be undone.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="rewardId", in="path", description="Reward ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Reward deleted", @OA\JsonContent(@OA\Property(property="message", type="string", example="Reward deleted successfully"))),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Reward not found")
     * )
     */
    public function deleteReward(string $locale, Request $request, string $rewardId): JsonResponse
    {
        $partner = $request->user('partner_api');

        $reward = $partner->rewards()->find($rewardId);

        if (! $reward) {
            return response()->json(['message' => 'Reward not found'], 404);
        }

        $reward->delete();

        return response()->json(['message' => 'Reward deleted successfully']);
    }
}
