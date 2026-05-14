<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Partner Member Management API Controller
 *
 * Handles CRUD operations for members belonging to the partner's clubs.
 */
class PartnerMemberController extends Controller
{
    /**
     * List all members in the partner's clubs.
     *
     * @OA\Get(
     *     path="/{locale}/v1/partner/members",
     *     operationId="getPartnerMembers",
     *     tags={"Partner"},
     *     summary="List all members",
     *     description="Retrieve all members registered in the partner's clubs.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="page", in="query", description="Page number", @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page (max 100)", @OA\Schema(type="integer", default=25)),
     *
     *     @OA\Response(response=200, description="Members retrieved", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Member"))),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getMembers(string $locale, Request $request): JsonResponse
    {
        $partner = $request->user('partner_api');
        $perPage = min($request->input('per_page', 25), 100);

        $clubIds = $partner->clubs()->pluck('id');

        $members = Member::whereIn('club_id', $clubIds)
            ->paginate($perPage);

        $members->getCollection()->each(fn ($m) => $m->hideForPublic());

        return response()->json($members);
    }

    /**
     * Get a specific member.
     *
     * @OA\Get(
     *     path="/{locale}/v1/partner/members/{memberId}",
     *     operationId="getPartnerMember",
     *     tags={"Partner"},
     *     summary="Get member details",
     *     description="Retrieve details of a specific member by ID.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="memberId", in="path", description="Member ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Member details", @OA\JsonContent(ref="#/components/schemas/Member")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Member not found")
     * )
     */
    public function getMember(string $locale, Request $request, string $memberId): JsonResponse
    {
        $partner = $request->user('partner_api');
        $clubIds = $partner->clubs()->pluck('id');

        $member = Member::whereIn('club_id', $clubIds)->find($memberId);

        if (! $member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        $member->hideForPublic();

        return response()->json($member);
    }

    /**
     * Create a new member.
     *
     * @OA\Post(
     *     path="/{locale}/v1/partner/members",
     *     operationId="createPartnerMember",
     *     tags={"Partner"},
     *     summary="Create a new member",
     *     description="Register a new member in one of the partner's clubs.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "club_id"},
     *             @OA\Property(property="name", type="string", description="Member name", example="Jane Customer"),
     *             @OA\Property(property="email", type="string", format="email", description="Member email", example="jane@example.com"),
     *             @OA\Property(property="password", type="string", format="password", description="Password (optional, auto-generated if not provided)"),
     *             @OA\Property(property="club_id", type="string", format="uuid", description="Club ID", example="019b8d49-1234-7abc-8def-17f2107a8ec0"),
     *             @OA\Property(property="accepts_emails", type="boolean", description="Accepts marketing emails", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Member created", @OA\JsonContent(ref="#/components/schemas/Member")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createMember(string $locale, Request $request): JsonResponse
    {
        $partner = $request->user('partner_api');

        $validated = $request->validate([
            'name' => 'required|string|max:64',
            'email' => 'required|email|max:120|unique:members,email',
            'password' => 'nullable|string|min:6|max:48',
            'club_id' => 'required|exists:clubs,id',
            'accepts_emails' => 'nullable|boolean',
        ]);

        // Verify club belongs to partner
        $club = $partner->clubs()->find($validated['club_id']);
        if (! $club) {
            return response()->json(['message' => 'Club not found'], 404);
        }

        $member = Member::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'] ?? Str::random(12)),
            'club_id' => $validated['club_id'],
            'accepts_emails' => $validated['accepts_emails'] ?? true,
            'locale' => $partner->locale ?? config('app.locale'),
            'currency' => $partner->currency ?? 'USD',
            'time_zone' => $partner->time_zone ?? config('app.timezone'),
        ]);

        $member->hideForPublic();

        return response()->json($member, 201);
    }

    /**
     * Update a member.
     *
     * @OA\Put(
     *     path="/{locale}/v1/partner/members/{memberId}",
     *     operationId="updatePartnerMember",
     *     tags={"Partner"},
     *     summary="Update a member",
     *     description="Update an existing member's details.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="memberId", in="path", description="Member ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Name"),
     *             @OA\Property(property="email", type="string", format="email", example="updated@example.com"),
     *             @OA\Property(property="password", type="string", format="password", description="New password (optional)"),
     *             @OA\Property(property="accepts_emails", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Member updated", @OA\JsonContent(ref="#/components/schemas/Member")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Member not found")
     * )
     */
    public function updateMember(string $locale, Request $request, string $memberId): JsonResponse
    {
        $partner = $request->user('partner_api');
        $clubIds = $partner->clubs()->pluck('id');

        $member = Member::whereIn('club_id', $clubIds)->find($memberId);

        if (! $member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:120|unique:members,email,' . $member->id,
            'password' => 'nullable|string|min:6|max:48',
            'accepts_emails' => 'nullable|boolean',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $member->update(array_filter($validated, fn ($v) => $v !== null));
        $member->hideForPublic();

        return response()->json($member);
    }

    /**
     * Delete a member.
     *
     * @OA\Delete(
     *     path="/{locale}/v1/partner/members/{memberId}",
     *     operationId="deletePartnerMember",
     *     tags={"Partner"},
     *     summary="Delete a member",
     *     description="Delete a member. This action cannot be undone.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="memberId", in="path", description="Member ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Member deleted", @OA\JsonContent(@OA\Property(property="message", type="string", example="Member deleted successfully"))),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Member not found")
     * )
     */
    public function deleteMember(string $locale, Request $request, string $memberId): JsonResponse
    {
        $partner = $request->user('partner_api');
        $clubIds = $partner->clubs()->pluck('id');

        $member = Member::whereIn('club_id', $clubIds)->find($memberId);

        if (! $member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        $member->delete();

        return response()->json(['message' => 'Member deleted successfully']);
    }
}
