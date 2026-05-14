<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerClubController extends Controller
{
    /**
     * Retrieve all active clubs for the authenticated partner.
     *
     * @OA\Get(
     *     path="/{locale}/v1/partner/clubs",
     *     operationId="getPartnerClubs",
     *     tags={"Partner"},
     *     summary="List all clubs",
     *     description="Retrieve all active clubs owned by the authenticated partner.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\Response(response=200, description="Clubs retrieved", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Club"))),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public function getClubs(string $locale, Request $request): JsonResponse
    {
        $partner = $request->user('partner_api');
        $clubs = $partner->clubs()->where('is_active', 1)->get();
        $clubs->each(fn ($club) => $club->hideForPublic());

        return response()->json($clubs);
    }

    /**
     * Retrieve a specific club.
     *
     * @OA\Get(
     *     path="/{locale}/v1/partner/clubs/{clubId}",
     *     operationId="getPartnerClub",
     *     tags={"Partner"},
     *     summary="Get club details",
     *     description="Retrieve details of a specific club by ID.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="clubId", in="path", description="Club ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Club details", @OA\JsonContent(ref="#/components/schemas/Club")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Club not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse"))
     * )
     */
    public function getClub(string $locale, Request $request, string $clubId): JsonResponse
    {
        $partner = $request->user('partner_api');
        $club = $partner->clubs()->where('is_active', 1)->find($clubId);

        if (! $club) {
            return response()->json(['message' => 'Club not found'], 404);
        }

        $club->hideForPublic();

        return response()->json($club);
    }

    /**
     * Create a new club.
     *
     * @OA\Post(
     *     path="/{locale}/v1/partner/clubs",
     *     operationId="createPartnerClub",
     *     tags={"Partner"},
     *     summary="Create a new club",
     *     description="Create a new loyalty club for the partner.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", description="Club name", example="Coffee Lovers Club"),
     *             @OA\Property(property="is_active", type="boolean", description="Active status", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Club created", @OA\JsonContent(ref="#/components/schemas/Club")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
     * )
     */
    public function createClub(string $locale, Request $request): JsonResponse
    {
        $partner = $request->user('partner_api');

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'is_active' => 'nullable|boolean',
        ]);

        $club = $partner->clubs()->create([
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $club->hideForPublic();

        return response()->json($club, 201);
    }

    /**
     * Update a club.
     *
     * @OA\Put(
     *     path="/{locale}/v1/partner/clubs/{clubId}",
     *     operationId="updatePartnerClub",
     *     tags={"Partner"},
     *     summary="Update a club",
     *     description="Update an existing club's details.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="clubId", in="path", description="Club ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Club Name"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Club updated", @OA\JsonContent(ref="#/components/schemas/Club")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Club not found")
     * )
     */
    public function updateClub(string $locale, Request $request, string $clubId): JsonResponse
    {
        $partner = $request->user('partner_api');
        $club = $partner->clubs()->find($clubId);

        if (! $club) {
            return response()->json(['message' => 'Club not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:120',
            'is_active' => 'nullable|boolean',
        ]);

        $club->update(array_filter($validated, fn ($v) => $v !== null));
        $club->hideForPublic();

        return response()->json($club);
    }

    /**
     * Delete a club.
     *
     * @OA\Delete(
     *     path="/{locale}/v1/partner/clubs/{clubId}",
     *     operationId="deletePartnerClub",
     *     tags={"Partner"},
     *     summary="Delete a club",
     *     description="Delete a club and all associated data. This action cannot be undone.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="clubId", in="path", description="Club ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Club deleted", @OA\JsonContent(@OA\Property(property="message", type="string", example="Club deleted successfully"))),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Club not found")
     * )
     */
    public function deleteClub(string $locale, Request $request, string $clubId): JsonResponse
    {
        $partner = $request->user('partner_api');
        $club = $partner->clubs()->find($clubId);

        if (! $club) {
            return response()->json(['message' => 'Club not found'], 404);
        }

        $club->delete();

        return response()->json(['message' => 'Club deleted successfully']);
    }
}

