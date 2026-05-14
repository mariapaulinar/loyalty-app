<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PartnerStaffController extends Controller
{
    /**
     * Retrieve all active staff members for the authenticated partner.
     *
     * @OA\Get(
     *     path="/{locale}/v1/partner/staff",
     *     operationId="getPartnerStaffMembers",
     *     tags={"Partner"},
     *     summary="List all staff members",
     *     description="Retrieve all active staff members for the authenticated partner.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\Response(response=200, description="Staff retrieved", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/StaffMember"))),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public function getStaff(string $locale, Request $request): JsonResponse
    {
        $partner = $request->user('partner_api');

        $staff = $partner->staff()
            ->where('is_active', 1)
            ->whereHas('club', fn ($q) => $q->where('is_active', 1))
            ->get();

        $staff->each(fn ($s) => $s->hideForPublic());

        return response()->json($staff);
    }

    /**
     * Retrieve a specific staff member.
     *
     * @OA\Get(
     *     path="/{locale}/v1/partner/staff/{staffId}",
     *     operationId="getPartnerStaffMember",
     *     tags={"Partner"},
     *     summary="Get staff member details",
     *     description="Retrieve details of a specific staff member by ID.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="staffId", in="path", description="Staff ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Staff details", @OA\JsonContent(ref="#/components/schemas/StaffMember")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Staff not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse"))
     * )
     */
    public function getStaffMember(string $locale, Request $request, string $staffId): JsonResponse
    {
        $partner = $request->user('partner_api');

        $staffMember = $partner->staff()
            ->where('is_active', 1)
            ->whereHas('club', fn ($q) => $q->where('is_active', 1))
            ->find($staffId);

        if (! $staffMember) {
            return response()->json(['message' => 'Staff member not found'], 404);
        }

        $staffMember->hideForPublic();

        return response()->json($staffMember);
    }

    /**
     * Create a new staff member.
     *
     * @OA\Post(
     *     path="/{locale}/v1/partner/staff",
     *     operationId="createPartnerStaff",
     *     tags={"Partner"},
     *     summary="Create a new staff member",
     *     description="Create a new staff member for the partner.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "club_id"},
     *             @OA\Property(property="name", type="string", description="Staff name", example="John Smith"),
     *             @OA\Property(property="email", type="string", format="email", description="Staff email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", description="Password (6-48 chars)", example="securepass123"),
     *             @OA\Property(property="club_id", type="string", format="uuid", description="Club ID", example="019b8d49-1234-7abc-8def-17f2107a8ec0"),
     *             @OA\Property(property="role", type="integer", description="Role: 1=Manager, 2=Staff", example=2),
     *             @OA\Property(property="is_active", type="boolean", description="Active status", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Staff created", @OA\JsonContent(ref="#/components/schemas/StaffMember")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
     * )
     */
    public function createStaff(string $locale, Request $request): JsonResponse
    {
        $partner = $request->user('partner_api');

        $validated = $request->validate([
            'name' => 'required|string|max:64',
            'email' => 'required|email|max:120|unique:staff,email',
            'password' => 'required|string|min:6|max:48',
            'club_id' => 'required|exists:clubs,id',
            'role' => 'nullable|integer|in:1,2',
            'is_active' => 'nullable|boolean',
        ]);

        // Verify club belongs to partner
        $club = $partner->clubs()->find($validated['club_id']);
        if (! $club) {
            return response()->json(['message' => 'Club not found'], 404);
        }

        $staff = Staff::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'club_id' => $validated['club_id'],
            'created_by' => $partner->id,
            'role' => $validated['role'] ?? 2,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $staff->hideForPublic();

        return response()->json($staff, 201);
    }

    /**
     * Update a staff member.
     *
     * @OA\Put(
     *     path="/{locale}/v1/partner/staff/{staffId}",
     *     operationId="updatePartnerStaff",
     *     tags={"Partner"},
     *     summary="Update a staff member",
     *     description="Update an existing staff member's details.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="staffId", in="path", description="Staff ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Name"),
     *             @OA\Property(property="email", type="string", format="email", example="updated@example.com"),
     *             @OA\Property(property="password", type="string", format="password", description="New password (optional)"),
     *             @OA\Property(property="role", type="integer", example=2),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Staff updated", @OA\JsonContent(ref="#/components/schemas/StaffMember")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Staff not found")
     * )
     */
    public function updateStaff(string $locale, Request $request, string $staffId): JsonResponse
    {
        $partner = $request->user('partner_api');

        $staff = $partner->staff()->find($staffId);

        if (! $staff) {
            return response()->json(['message' => 'Staff member not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:120|unique:staff,email,' . $staff->id,
            'password' => 'nullable|string|min:6|max:48',
            'role' => 'nullable|integer|in:1,2',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle password separately
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $staff->update(array_filter($validated, fn ($v) => $v !== null));
        $staff->hideForPublic();

        return response()->json($staff);
    }

    /**
     * Delete a staff member.
     *
     * @OA\Delete(
     *     path="/{locale}/v1/partner/staff/{staffId}",
     *     operationId="deletePartnerStaff",
     *     tags={"Partner"},
     *     summary="Delete a staff member",
     *     description="Delete a staff member. This action cannot be undone.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="staffId", in="path", description="Staff ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Staff deleted", @OA\JsonContent(@OA\Property(property="message", type="string", example="Staff member deleted successfully"))),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Staff not found")
     * )
     */
    public function deleteStaff(string $locale, Request $request, string $staffId): JsonResponse
    {
        $partner = $request->user('partner_api');

        $staff = $partner->staff()->find($staffId);

        if (! $staff) {
            return response()->json(['message' => 'Staff member not found'], 404);
        }

        $staff->delete();

        return response()->json(['message' => 'Staff member deleted successfully']);
    }
}

