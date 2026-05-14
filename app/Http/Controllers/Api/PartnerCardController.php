<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Services\Card\CardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PartnerCardController extends Controller
{
    /**
     * Retrieve all active cards for the authenticated partner.
     *
     * @OA\Get(
     *     path="/{locale}/v1/partner/cards",
     *     operationId="getPartnerCards",
     *     tags={"Partner"},
     *     summary="List all loyalty cards",
     *     description="Retrieve all active loyalty cards owned by the authenticated partner.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\Response(response=200, description="Cards retrieved", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Card"))),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public function getCards(string $locale, Request $request, CardService $cardService): Response
    {
        $partner = $request->user('partner_api');
        $cards = $cardService->findActiveCardsFromPartner($partner->id, $hideColumnsForPublic = true);

        $cards->each(fn ($card) => $card->balance = -1);

        return response()->json($cards);
    }

    /**
     * Retrieve a specific card.
     *
     * @OA\Get(
     *     path="/{locale}/v1/partner/cards/{cardId}",
     *     operationId="getCard",
     *     tags={"Partner"},
     *     summary="Get card details",
     *     description="Retrieve details of a specific loyalty card by ID.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="cardId", in="path", description="Card ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Card details", @OA\JsonContent(ref="#/components/schemas/Card")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Card not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse"))
     * )
     */
    public function getCard(string $locale, string $cardId, Request $request, CardService $cardService): Response
    {
        $partner = $request->user('partner_api');
        $card = $cardService->findActiveCard($cardId, $authUserIsOwner = true, $guardUserIsOwner = 'partner_api', $hideColumnsForPublic = true);

        if (! $card) {
            return response()->json(['message' => 'Card not found'], 404);
        }

        $card->balance = -1;

        return response()->json($card);
    }

    /**
     * Create a new loyalty card.
     *
     * @OA\Post(
     *     path="/{locale}/v1/partner/cards",
     *     operationId="createPartnerCard",
     *     tags={"Partner"},
     *     summary="Create a new loyalty card",
     *     description="Create a new points-based loyalty card for the partner.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "club_id"},
     *             @OA\Property(property="name", type="string", description="Card name", example="Coffee Rewards"),
     *             @OA\Property(property="club_id", type="string", format="uuid", description="Club ID", example="019b8d49-1234-7abc-8def-17f2107a8ec0"),
     *             @OA\Property(property="head", type="string", description="Card headline", example="Earn points with every purchase"),
     *             @OA\Property(property="currency", type="string", description="Currency code", example="USD"),
     *             @OA\Property(property="initial_bonus_points", type="integer", description="Signup bonus points", example=100),
     *             @OA\Property(property="points_per_currency", type="number", description="Points earned per currency unit", example=1.5),
     *             @OA\Property(property="point_value", type="number", description="Value per point in cents", example=1),
     *             @OA\Property(property="is_active", type="boolean", description="Active status", example=true),
     *             @OA\Property(property="is_visible_homepage", type="boolean", description="Show on homepage", example=true),
     *             @OA\Property(property="is_visible_by_link", type="boolean", description="Accessible by link", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Card created", @OA\JsonContent(ref="#/components/schemas/Card")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
     * )
     */
    public function createCard(string $locale, Request $request, CardService $cardService): JsonResponse
    {
        $partner = $request->user('partner_api');

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'club_id' => 'required|exists:clubs,id',
            'head' => 'nullable|string|max:200',
            'currency' => 'nullable|string|size:3',
            'initial_bonus_points' => 'nullable|integer|min:0',
            'points_per_currency' => 'nullable|numeric|min:0',
            'point_value' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'is_visible_homepage' => 'nullable|boolean',
            'is_visible_by_link' => 'nullable|boolean',
        ]);

        // Verify club belongs to partner
        $club = $partner->clubs()->find($validated['club_id']);
        if (! $club) {
            return response()->json(['message' => 'Club not found'], 404);
        }

        $card = Card::create([
            'name' => $validated['name'],
            'club_id' => $validated['club_id'],
            'created_by' => $partner->id,
            'head' => $validated['head'] ?? null,
            'currency' => $validated['currency'] ?? $partner->currency ?? 'USD',
            'initial_bonus_points' => $validated['initial_bonus_points'] ?? 0,
            'points_per_currency' => $validated['points_per_currency'] ?? 1,
            'point_value' => $validated['point_value'] ?? 1,
            'is_active' => $validated['is_active'] ?? true,
            'is_visible_homepage' => $validated['is_visible_homepage'] ?? true,
            'is_visible_by_link' => $validated['is_visible_by_link'] ?? true,
        ]);

        $card->hideForPublic();

        return response()->json($card, 201);
    }

    /**
     * Update a loyalty card.
     *
     * @OA\Put(
     *     path="/{locale}/v1/partner/cards/{cardId}",
     *     operationId="updatePartnerCard",
     *     tags={"Partner"},
     *     summary="Update a loyalty card",
     *     description="Update an existing loyalty card's details.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="cardId", in="path", description="Card ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Card Name"),
     *             @OA\Property(property="head", type="string", example="New headline"),
     *             @OA\Property(property="initial_bonus_points", type="integer", example=150),
     *             @OA\Property(property="points_per_currency", type="number", example=2),
     *             @OA\Property(property="point_value", type="number", example=1),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="is_visible_homepage", type="boolean", example=true),
     *             @OA\Property(property="is_visible_by_link", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Card updated", @OA\JsonContent(ref="#/components/schemas/Card")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Card not found")
     * )
     */
    public function updateCard(string $locale, string $cardId, Request $request, CardService $cardService): JsonResponse
    {
        $partner = $request->user('partner_api');

        // Find card owned by partner
        $card = Card::where('created_by', $partner->id)->find($cardId);

        if (! $card) {
            return response()->json(['message' => 'Card not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:120',
            'head' => 'nullable|string|max:200',
            'initial_bonus_points' => 'nullable|integer|min:0',
            'points_per_currency' => 'nullable|numeric|min:0',
            'point_value' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'is_visible_homepage' => 'nullable|boolean',
            'is_visible_by_link' => 'nullable|boolean',
        ]);

        $card->update(array_filter($validated, fn ($v) => $v !== null));
        $card->hideForPublic();

        return response()->json($card);
    }

    /**
     * Delete a loyalty card.
     *
     * @OA\Delete(
     *     path="/{locale}/v1/partner/cards/{cardId}",
     *     operationId="deletePartnerCard",
     *     tags={"Partner"},
     *     summary="Delete a loyalty card",
     *     description="Delete a loyalty card and all associated data. This action cannot be undone.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="cardId", in="path", description="Card ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Card deleted", @OA\JsonContent(@OA\Property(property="message", type="string", example="Card deleted successfully"))),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Card not found")
     * )
     */
    public function deleteCard(string $locale, string $cardId, Request $request): JsonResponse
    {
        $partner = $request->user('partner_api');

        $card = Card::where('created_by', $partner->id)->find($cardId);

        if (! $card) {
            return response()->json(['message' => 'Card not found'], 404);
        }

        $card->delete();

        return response()->json(['message' => 'Card deleted successfully']);
    }
}

