<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StampCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Partner Stamp Card Management API Controller
 *
 * Handles CRUD operations for stamp cards.
 */
class PartnerStampCardController extends Controller
{
    /**
     * List all stamp cards for the partner.
     *
     * @OA\Get(
     *     path="/{locale}/v1/partner/stamp-cards",
     *     operationId="getPartnerStampCards",
     *     tags={"Partner"},
     *     summary="List all stamp cards",
     *     description="Retrieve all stamp cards owned by the authenticated partner.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\Response(response=200, description="Stamp cards retrieved", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/StampCard"))),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getStampCards(string $locale, Request $request): JsonResponse
    {
        $partner = $request->user('partner_api');

        $stampCards = $partner->stampCards()->with('club')->get();

        return response()->json($stampCards);
    }

    /**
     * Get a specific stamp card.
     *
     * @OA\Get(
     *     path="/{locale}/v1/partner/stamp-cards/{stampCardId}",
     *     operationId="getPartnerStampCard",
     *     tags={"Partner"},
     *     summary="Get stamp card details",
     *     description="Retrieve details of a specific stamp card by ID.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="stampCardId", in="path", description="Stamp card ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Stamp card details", @OA\JsonContent(ref="#/components/schemas/StampCard")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Stamp card not found")
     * )
     */
    public function getStampCard(string $locale, Request $request, string $stampCardId): JsonResponse
    {
        $partner = $request->user('partner_api');

        $stampCard = $partner->stampCards()->with('club')->find($stampCardId);

        if (! $stampCard) {
            return response()->json(['message' => 'Stamp card not found'], 404);
        }

        return response()->json($stampCard);
    }

    /**
     * Create a new stamp card.
     *
     * @OA\Post(
     *     path="/{locale}/v1/partner/stamp-cards",
     *     operationId="createPartnerStampCard",
     *     tags={"Partner"},
     *     summary="Create a new stamp card",
     *     description="Create a new stamp-based loyalty card for the partner.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "club_id", "stamps_required"},
     *             @OA\Property(property="title", type="string", description="Card title", example="Coffee Stamp Card"),
     *             @OA\Property(property="club_id", type="string", format="uuid", description="Club ID", example="019b8d49-1234-7abc-8def-17f2107a8ec0"),
     *             @OA\Property(property="description", type="string", description="Card description", example="Collect stamps with every purchase"),
     *             @OA\Property(property="stamps_required", type="integer", description="Stamps needed for reward", example=10),
     *             @OA\Property(property="reward_title", type="string", description="Reward title", example="Free Coffee"),
     *             @OA\Property(property="reward_description", type="string", description="Reward description", example="Get a free coffee of your choice"),
     *             @OA\Property(property="is_active", type="boolean", description="Active status", example=true),
     *             @OA\Property(property="is_visible_homepage", type="boolean", description="Show on homepage", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Stamp card created", @OA\JsonContent(ref="#/components/schemas/StampCard")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createStampCard(string $locale, Request $request): JsonResponse
    {
        $partner = $request->user('partner_api');

        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'club_id' => 'required|exists:clubs,id',
            'description' => 'nullable|string|max:500',
            'stamps_required' => 'required|integer|min:1|max:50',
            'reward_title' => 'nullable|string|max:120',
            'reward_description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'is_visible_homepage' => 'nullable|boolean',
        ]);

        // Verify club belongs to partner
        $club = $partner->clubs()->find($validated['club_id']);
        if (! $club) {
            return response()->json(['message' => 'Club not found'], 404);
        }

        $stampCard = StampCard::create([
            'title' => $validated['title'],
            'club_id' => $validated['club_id'],
            'created_by' => $partner->id,
            'description' => $validated['description'] ?? null,
            'stamps_required' => $validated['stamps_required'],
            'reward_title' => $validated['reward_title'] ?? null,
            'reward_description' => $validated['reward_description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'is_visible_homepage' => $validated['is_visible_homepage'] ?? true,
        ]);

        return response()->json($stampCard, 201);
    }

    /**
     * Update a stamp card.
     *
     * @OA\Put(
     *     path="/{locale}/v1/partner/stamp-cards/{stampCardId}",
     *     operationId="updatePartnerStampCard",
     *     tags={"Partner"},
     *     summary="Update a stamp card",
     *     description="Update an existing stamp card's details.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="stampCardId", in="path", description="Stamp card ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Stamp Card"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="stamps_required", type="integer", example=12),
     *             @OA\Property(property="reward_title", type="string", example="Free Item"),
     *             @OA\Property(property="reward_description", type="string", example="Get a free item"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="is_visible_homepage", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Stamp card updated", @OA\JsonContent(ref="#/components/schemas/StampCard")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Stamp card not found")
     * )
     */
    public function updateStampCard(string $locale, Request $request, string $stampCardId): JsonResponse
    {
        $partner = $request->user('partner_api');

        $stampCard = $partner->stampCards()->find($stampCardId);

        if (! $stampCard) {
            return response()->json(['message' => 'Stamp card not found'], 404);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:500',
            'stamps_required' => 'nullable|integer|min:1|max:50',
            'reward_title' => 'nullable|string|max:120',
            'reward_description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'is_visible_homepage' => 'nullable|boolean',
        ]);

        $stampCard->update(array_filter($validated, fn ($v) => $v !== null));

        return response()->json($stampCard);
    }

    /**
     * Delete a stamp card.
     *
     * @OA\Delete(
     *     path="/{locale}/v1/partner/stamp-cards/{stampCardId}",
     *     operationId="deletePartnerStampCard",
     *     tags={"Partner"},
     *     summary="Delete a stamp card",
     *     description="Delete a stamp card. This action cannot be undone.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="stampCardId", in="path", description="Stamp card ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Stamp card deleted", @OA\JsonContent(@OA\Property(property="message", type="string", example="Stamp card deleted successfully"))),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Stamp card not found")
     * )
     */
    public function deleteStampCard(string $locale, Request $request, string $stampCardId): JsonResponse
    {
        $partner = $request->user('partner_api');

        $stampCard = $partner->stampCards()->find($stampCardId);

        if (! $stampCard) {
            return response()->json(['message' => 'Stamp card not found'], 404);
        }

        $stampCard->delete();

        return response()->json(['message' => 'Stamp card deleted successfully']);
    }
}
