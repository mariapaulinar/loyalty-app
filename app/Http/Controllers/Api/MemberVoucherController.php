<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Member Voucher API Controller
 *
 * Handles member voucher operations (saving/following).
 */
class MemberVoucherController extends Controller
{
    /**
     * Get all vouchers the member has saved.
     *
     * @OA\Get(
     *     path="/{locale}/v1/member/my-vouchers",
     *     operationId="getMemberMyVouchers",
     *     tags={"Member"},
     *     summary="Get saved vouchers",
     *     description="Retrieve all vouchers the member has saved to their collection (My Cards).",
     *     security={{"member_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\Response(response=200, description="Vouchers retrieved", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Voucher"))),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getMyVouchers(string $locale, Request $request): JsonResponse
    {
        $member = $request->user('member_api');

        $vouchers = $member->vouchers()
            ->with('club')
            ->get();

        return response()->json($vouchers);
    }

    /**
     * Save a voucher to My Cards.
     *
     * @OA\Post(
     *     path="/{locale}/v1/member/vouchers/{voucherId}/save",
     *     operationId="saveMemberVoucher",
     *     tags={"Member"},
     *     summary="Add voucher to My Cards",
     *     description="Save a voucher to the member's collection (add to My Cards). This is equivalent to 'following' the voucher.",
     *     security={{"member_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="voucherId", in="path", description="Voucher ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Voucher saved", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Voucher added to My Cards")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Voucher not found"),
     *     @OA\Response(response=409, description="Voucher already saved")
     * )
     */
    public function save(string $locale, Request $request, string $voucherId): JsonResponse
    {
        $member = $request->user('member_api');

        $voucher = Voucher::where('is_active', true)->find($voucherId);

        if (! $voucher) {
            return response()->json(['message' => 'Voucher not found'], 404);
        }

        // Check if already saved
        $alreadySaved = $member->vouchers()
            ->where('vouchers.id', $voucher->id)
            ->wherePivot('claimed_via', null)
            ->exists();

        if ($alreadySaved) {
            return response()->json(['message' => 'Voucher already saved'], 409);
        }

        // Attach voucher to member
        $member->vouchers()->attach($voucher->id, [
            'claimed_via' => null, // Manual save
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Voucher added to My Cards']);
    }

    /**
     * Remove a voucher from My Cards.
     *
     * @OA\Delete(
     *     path="/{locale}/v1/member/vouchers/{voucherId}/save",
     *     operationId="unsaveMemberVoucher",
     *     tags={"Member"},
     *     summary="Remove voucher from My Cards",
     *     description="Remove a saved voucher from the member's collection. Only manually saved vouchers can be removed (not claimed via QR).",
     *     security={{"member_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="voucherId", in="path", description="Voucher ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Voucher removed", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Voucher removed from My Cards")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Voucher not found or not saved")
     * )
     */
    public function unsave(string $locale, Request $request, string $voucherId): JsonResponse
    {
        $member = $request->user('member_api');

        $voucher = Voucher::find($voucherId);

        if (! $voucher) {
            return response()->json(['message' => 'Voucher not found'], 404);
        }

        // Only remove manually saved vouchers (not claimed via QR)
        $detached = $member->vouchers()
            ->wherePivot('claimed_via', null)
            ->detach($voucher->id);

        if ($detached === 0) {
            return response()->json(['message' => 'Voucher not saved or cannot be removed'], 404);
        }

        return response()->json(['message' => 'Voucher removed from My Cards']);
    }
}
