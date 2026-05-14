<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Partner Voucher Management API Controller
 *
 * Handles CRUD operations for vouchers.
 */
class PartnerVoucherController extends Controller
{
    public function __construct(
        protected VoucherService $voucherService
    ) {}

    /**
     * List all vouchers for the partner.
     *
     * @OA\Get(
     *     path="/{locale}/v1/partner/vouchers",
     *     operationId="getPartnerVouchers",
     *     tags={"Partner"},
     *     summary="List all vouchers",
     *     description="Retrieve all vouchers owned by the authenticated partner.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="club_id", in="query", description="Filter by club ID", @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Vouchers retrieved", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Voucher"))),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getVouchers(string $locale, Request $request): JsonResponse
    {
        $partner = $request->user('partner_api');

        $query = $partner->vouchers();

        if ($request->has('club_id')) {
            $query->where('club_id', $request->input('club_id'));
        }

        $vouchers = $query->get();

        return response()->json($vouchers);
    }

    /**
     * Get a specific voucher.
     *
     * @OA\Get(
     *     path="/{locale}/v1/partner/vouchers/{voucherId}",
     *     operationId="getPartnerVoucher",
     *     tags={"Partner"},
     *     summary="Get voucher details",
     *     description="Retrieve details of a specific voucher by ID.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="voucherId", in="path", description="Voucher ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Voucher details", @OA\JsonContent(ref="#/components/schemas/Voucher")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Voucher not found")
     * )
     */
    public function getVoucher(string $locale, Request $request, string $voucherId): JsonResponse
    {
        $partner = $request->user('partner_api');

        $voucher = $partner->vouchers()->find($voucherId);

        if (! $voucher) {
            return response()->json(['message' => 'Voucher not found'], 404);
        }

        return response()->json($voucher);
    }

    /**
     * Create a new voucher.
     *
     * @OA\Post(
     *     path="/{locale}/v1/partner/vouchers",
     *     operationId="createPartnerVoucher",
     *     tags={"Partner"},
     *     summary="Create a new voucher",
     *     description="Create a new voucher for the partner.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "club_id", "type"},
     *             @OA\Property(property="title", type="string", description="Voucher title", example="Summer Sale 20% Off"),
     *             @OA\Property(property="club_id", type="string", format="uuid", description="Club ID", example="019b8d49-1234-7abc-8def-17f2107a8ec0"),
     *             @OA\Property(property="code", type="string", description="Voucher code (auto-generated if not provided)", example="SUMMER20"),
     *             @OA\Property(property="type", type="string", description="Type: percentage, fixed_amount, bonus_points", example="percentage"),
     *             @OA\Property(property="discount_amount", type="integer", description="Discount amount in cents (for fixed) or percentage", example=2000),
     *             @OA\Property(property="min_purchase_amount", type="integer", description="Minimum purchase in cents", example=5000),
     *             @OA\Property(property="max_uses", type="integer", description="Maximum total uses (-1 for unlimited)", example=100),
     *             @OA\Property(property="max_uses_per_member", type="integer", description="Max uses per member (-1 for unlimited)", example=1),
     *             @OA\Property(property="valid_from", type="string", format="date-time", description="Start date"),
     *             @OA\Property(property="valid_until", type="string", format="date-time", description="End date"),
     *             @OA\Property(property="is_active", type="boolean", description="Active status", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Voucher created", @OA\JsonContent(ref="#/components/schemas/Voucher")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createVoucher(string $locale, Request $request): JsonResponse
    {
        $partner = $request->user('partner_api');

        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'club_id' => 'required|exists:clubs,id',
            'code' => 'nullable|string|max:32',
            'type' => 'required|string|in:percentage,fixed_amount,bonus_points',
            'discount_amount' => 'nullable|integer|min:0',
            'min_purchase_amount' => 'nullable|integer|min:0',
            'max_uses' => 'nullable|integer|min:-1',
            'max_uses_per_member' => 'nullable|integer|min:-1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'nullable|boolean',
        ]);

        // Verify club belongs to partner
        $club = $partner->clubs()->find($validated['club_id']);
        if (! $club) {
            return response()->json(['message' => 'Club not found'], 404);
        }

        // Generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = $this->voucherService->generateUniqueCode($validated['club_id']);
        }

        $voucher = Voucher::create([
            'title' => $validated['title'],
            'club_id' => $validated['club_id'],
            'created_by' => $partner->id,
            'code' => strtoupper($validated['code']),
            'type' => $validated['type'],
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'min_purchase_amount' => $validated['min_purchase_amount'] ?? 0,
            'max_uses' => $validated['max_uses'] ?? -1,
            'max_uses_per_member' => $validated['max_uses_per_member'] ?? -1,
            'valid_from' => $validated['valid_from'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json($voucher, 201);
    }

    /**
     * Update a voucher.
     *
     * @OA\Put(
     *     path="/{locale}/v1/partner/vouchers/{voucherId}",
     *     operationId="updatePartnerVoucher",
     *     tags={"Partner"},
     *     summary="Update a voucher",
     *     description="Update an existing voucher's details.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="voucherId", in="path", description="Voucher ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Voucher Title"),
     *             @OA\Property(property="discount_amount", type="integer", example=2500),
     *             @OA\Property(property="min_purchase_amount", type="integer", example=6000),
     *             @OA\Property(property="max_uses", type="integer", example=200),
     *             @OA\Property(property="valid_until", type="string", format="date-time"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Voucher updated", @OA\JsonContent(ref="#/components/schemas/Voucher")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Voucher not found")
     * )
     */
    public function updateVoucher(string $locale, Request $request, string $voucherId): JsonResponse
    {
        $partner = $request->user('partner_api');

        $voucher = $partner->vouchers()->find($voucherId);

        if (! $voucher) {
            return response()->json(['message' => 'Voucher not found'], 404);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:120',
            'discount_amount' => 'nullable|integer|min:0',
            'min_purchase_amount' => 'nullable|integer|min:0',
            'max_uses' => 'nullable|integer|min:-1',
            'max_uses_per_member' => 'nullable|integer|min:-1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $voucher->update(array_filter($validated, fn ($v) => $v !== null));

        return response()->json($voucher);
    }

    /**
     * Delete a voucher.
     *
     * @OA\Delete(
     *     path="/{locale}/v1/partner/vouchers/{voucherId}",
     *     operationId="deletePartnerVoucher",
     *     tags={"Partner"},
     *     summary="Delete a voucher",
     *     description="Delete a voucher. This action cannot be undone.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="voucherId", in="path", description="Voucher ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Voucher deleted", @OA\JsonContent(@OA\Property(property="message", type="string", example="Voucher deleted successfully"))),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Voucher not found")
     * )
     */
    public function deleteVoucher(string $locale, Request $request, string $voucherId): JsonResponse
    {
        $partner = $request->user('partner_api');

        $voucher = $partner->vouchers()->find($voucherId);

        if (! $voucher) {
            return response()->json(['message' => 'Voucher not found'], 404);
        }

        $voucher->delete();

        return response()->json(['message' => 'Voucher deleted successfully']);
    }
}
