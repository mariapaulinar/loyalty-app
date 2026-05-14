<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Staff API Controller - The operational heart of the loyalty system.
 * Staff members are the front-line operators who process transactions,
 * award points, add stamps, validate vouchers, and redeem rewards.
 * This controller exposes these critical point-of-sale operations
 * via a clean, secure API suitable for mobile POS apps and integrations.
 *
 * Operations:
 * - Loyalty Points: Add purchases/points to member cards
 * - Stamp Cards: Add stamps, redeem stamp rewards
 * - Vouchers: Validate and redeem vouchers
 * - Member Lookup: Find members by identifier
 *
 * Security:
 * - All endpoints require staff authentication
 * - Staff can only operate within their assigned club(s)
 * - All operations are logged for audit trails
 *
 * @see App\Http\Controllers\Staff\* for web equivalents
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Member;
use App\Models\Reward;
use App\Models\StampCard;
use App\Models\Voucher;
use App\Services\Card\CardService;
use App\Services\Card\TransactionService;
use App\Services\Member\MemberService;
use App\Services\StampService;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Staff Operations API Controller
 *
 * Provides API endpoints for staff members to perform point-of-sale
 * operations: processing transactions, awarding points, adding stamps,
 * and redeeming rewards and vouchers.
 */
class StaffController extends Controller
{
    public function __construct(
        protected MemberService $memberService,
        protected CardService $cardService,
        protected TransactionService $transactionService,
        protected StampService $stampService,
        protected VoucherService $voucherService
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // MEMBER LOOKUP
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Find a member by their unique identifier.
     *
     * Staff can search for members using their ID, unique identifier,
     * email, or phone number. Essential for beginning any transaction.
     *
     * @OA\Get(
     *     path="/{locale}/v1/staff/member/{identifier}",
     *     operationId="staffFindMember",
     *     tags={"Staff"},
     *     summary="Find a member",
     *     description="Look up a member by ID, unique identifier, email, or phone. Returns member details and balances.",
     *     security={{"staff_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="identifier", in="path", description="Member ID, unique identifier, email, or phone", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Member found", @OA\JsonContent(ref="#/components/schemas/Member")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Member not found")
     * )
     */
    public function findMember(string $locale, Request $request, string $identifier): JsonResponse
    {
        $staff = $request->user('staff_api');

        $member = $this->memberService->findActiveByIdentifier($identifier);

        if (! $member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        return response()->json($member);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LOYALTY POINTS OPERATIONS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Add a purchase transaction and award points.
     *
     * This is the core transaction for points-based loyalty cards.
     * Staff scans/enters the purchase amount, and points are calculated
     * and awarded based on the card's earning rules.
     *
     * @OA\Post(
     *     path="/{locale}/v1/staff/cards/{cardId}/purchase",
     *     operationId="staffAddPurchase",
     *     tags={"Staff"},
     *     summary="Add purchase and award points",
     *     description="Record a purchase transaction and award points to a member based on the card's earning rules. This is the primary transaction for points-based loyalty programs.",
     *     security={{"staff_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="cardId", in="path", description="Loyalty card ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"member_id", "purchase_amount"},
     *             @OA\Property(property="member_id", type="string", description="Member ID", example="019b8d48-97df-7177-8aa0-47f2107a8eb3"),
     *             @OA\Property(property="purchase_amount", type="number", format="float", description="Purchase amount in currency units", example=25.50),
     *             @OA\Property(property="points", type="integer", description="Override calculated points (optional)", example=null),
     *             @OA\Property(property="points_only", type="boolean", description="Award points without purchase amount", example=false),
     *             @OA\Property(property="note", type="string", description="Transaction note", example="Birthday bonus")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Transaction created", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Purchase recorded successfully"),
     *         @OA\Property(property="transaction", type="object"),
     *         @OA\Property(property="points_earned", type="integer", example=25),
     *         @OA\Property(property="new_balance", type="integer", example=150)
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Staff not authorized for this card"),
     *     @OA\Response(response=404, description="Card or member not found")
     * )
     */
    public function addPurchase(string $locale, Request $request, string $cardId): JsonResponse
    {
        $staff = $request->user('staff_api');

        // Validate request
        $validated = $request->validate([
            'member_id' => 'required|string',
            'purchase_amount' => 'required_without:points_only|numeric|min:0',
            'points' => 'nullable|integer|min:0',
            'points_only' => 'nullable|boolean',
            'note' => 'nullable|string|max:500',
        ]);

        // Find card and verify staff access
        $card = Card::find($cardId);
        if (! $card) {
            return response()->json(['message' => 'Card not found'], 404);
        }

        if (! $staff->isRelatedToCard($card)) {
            return response()->json(['message' => 'Not authorized to operate this card'], 403);
        }

        // Find member
        $member = $this->memberService->findActiveByIdentifier($validated['member_id']);
        if (! $member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        try {
            $transaction = $this->transactionService->addPurchase(
                $validated['member_id'],
                $cardId,
                $staff,
                $validated['purchase_amount'] ?? null,
                $validated['points'] ?? null,
                null, // image
                $validated['note'] ?? null,
                $validated['points_only'] ?? false
            );

            $newBalance = $card->getMemberBalance($member);

            return response()->json([
                'message' => 'Purchase recorded successfully',
                'transaction' => $transaction,
                'points_earned' => $transaction->points ?? 0,
                'new_balance' => $newBalance,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Redeem a loyalty card reward.
     *
     * Deducts points from the member's balance and records the redemption.
     *
     * @OA\Post(
     *     path="/{locale}/v1/staff/cards/{cardId}/rewards/{rewardId}/redeem",
     *     operationId="staffRedeemReward",
     *     tags={"Staff"},
     *     summary="Redeem a loyalty reward",
     *     description="Redeem a reward for a member, deducting the required points from their balance.",
     *     security={{"staff_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="cardId", in="path", description="Loyalty card ID", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="rewardId", in="path", description="Reward ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"member_id"},
     *             @OA\Property(property="member_id", type="string", description="Member ID", example="019b8d48-97df-7177-8aa0-47f2107a8eb3"),
     *             @OA\Property(property="note", type="string", description="Redemption note", example="VIP customer")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Reward redeemed", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Reward redeemed successfully"),
     *         @OA\Property(property="points_deducted", type="integer", example=100),
     *         @OA\Property(property="new_balance", type="integer", example=50)
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Staff not authorized"),
     *     @OA\Response(response=404, description="Card, reward, or member not found"),
     *     @OA\Response(response=422, description="Insufficient points")
     * )
     */
    public function redeemReward(string $locale, Request $request, string $cardId, string $rewardId): JsonResponse
    {
        $staff = $request->user('staff_api');

        $validated = $request->validate([
            'member_id' => 'required|string',
            'note' => 'nullable|string|max:500',
        ]);

        // Find card and verify staff access
        $card = Card::find($cardId);
        if (! $card || ! $staff->isRelatedToCard($card)) {
            return response()->json(['message' => 'Card not found or not authorized'], 404);
        }

        // Find reward
        $reward = Reward::where('id', $rewardId)->first();
        if (! $reward) {
            return response()->json(['message' => 'Reward not found'], 404);
        }

        // Find member
        $member = $this->memberService->findActiveByIdentifier($validated['member_id']);
        if (! $member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        // Check balance
        $balance = $card->getMemberBalance($member);
        if ($balance < $reward->points) {
            return response()->json([
                'message' => 'Insufficient points',
                'required' => $reward->points,
                'available' => $balance,
            ], 422);
        }

        try {
            $transaction = $this->transactionService->claimReward(
                $cardId,
                $rewardId,
                $validated['member_id'],
                $staff,
                null, // image
                $validated['note'] ?? null
            );

            $newBalance = $card->getMemberBalance($member);

            return response()->json([
                'message' => 'Reward redeemed successfully',
                'points_deducted' => $reward->points,
                'new_balance' => $newBalance,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STAMP CARD OPERATIONS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Add stamps to a member's stamp card.
     *
     * @OA\Post(
     *     path="/{locale}/v1/staff/stamp-cards/{stampCardId}/stamps",
     *     operationId="staffAddStamps",
     *     tags={"Staff"},
     *     summary="Add stamps to a card",
     *     description="Add one or more stamps to a member's stamp card. Automatically handles card completion and pending rewards.",
     *     security={{"staff_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="stampCardId", in="path", description="Stamp card ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"member_id"},
     *             @OA\Property(property="member_id", type="string", description="Member ID", example="019b8d48-97df-7177-8aa0-47f2107a8eb3"),
     *             @OA\Property(property="stamps", type="integer", description="Number of stamps to add (default: 1)", example=1),
     *             @OA\Property(property="purchase_amount", type="number", format="float", description="Optional purchase amount", example=12.50),
     *             @OA\Property(property="note", type="string", description="Transaction note", example="Morning coffee")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Stamps added", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Stamps added successfully"),
     *         @OA\Property(property="stamps_added", type="integer", example=1),
     *         @OA\Property(property="current_stamps", type="integer", example=5),
     *         @OA\Property(property="stamps_required", type="integer", example=10),
     *         @OA\Property(property="pending_rewards", type="integer", example=0),
     *         @OA\Property(property="card_completed", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Staff not authorized"),
     *     @OA\Response(response=404, description="Stamp card or member not found")
     * )
     */
    public function addStamps(string $locale, Request $request, string $stampCardId): JsonResponse
    {
        $staff = $request->user('staff_api');

        $validated = $request->validate([
            'member_id' => 'required|string',
            'stamps' => 'nullable|integer|min:1|max:100',
            'purchase_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        // Find stamp card
        $stampCard = StampCard::find($stampCardId);
        if (! $stampCard) {
            return response()->json(['message' => 'Stamp card not found'], 404);
        }

        // Verify staff access
        if (! $staff->isRelatedToClub($stampCard->club)) {
            return response()->json(['message' => 'Not authorized to operate this stamp card'], 403);
        }

        // Find member
        $member = Member::find($validated['member_id']);
        if (! $member) {
            $member = $this->memberService->findActiveByIdentifier($validated['member_id']);
        }
        if (! $member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        try {
            $result = $this->stampService->addStamp(
                card: $stampCard,
                member: $member,
                staff: $staff,
                stamps: $validated['stamps'] ?? 1,
                purchaseAmount: $validated['purchase_amount'] ?? null,
                image: null,
                note: $validated['note'] ?? null
            );

            return response()->json([
                'message' => 'Stamps added successfully',
                'stamps_added' => $result['stamps_added'] ?? ($validated['stamps'] ?? 1),
                'current_stamps' => $result['current_stamps'] ?? 0,
                'stamps_required' => $stampCard->stamps_required,
                'pending_rewards' => $result['pending_rewards'] ?? 0,
                'card_completed' => $result['completed'] ?? false,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Redeem a stamp card reward.
     *
     * @OA\Post(
     *     path="/{locale}/v1/staff/stamp-cards/{stampCardId}/redeem",
     *     operationId="staffRedeemStampReward",
     *     tags={"Staff"},
     *     summary="Redeem a stamp card reward",
     *     description="Redeem a pending reward from a completed stamp card.",
     *     security={{"staff_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="stampCardId", in="path", description="Stamp card ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"member_id"},
     *             @OA\Property(property="member_id", type="string", description="Member ID", example="019b8d48-97df-7177-8aa0-47f2107a8eb3"),
     *             @OA\Property(property="note", type="string", description="Redemption note", example="Free coffee claimed")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Reward redeemed", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Stamp reward redeemed successfully"),
     *         @OA\Property(property="reward_title", type="string", example="Free Coffee"),
     *         @OA\Property(property="remaining_rewards", type="integer", example=0)
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Staff not authorized"),
     *     @OA\Response(response=404, description="Stamp card or member not found"),
     *     @OA\Response(response=422, description="No pending rewards")
     * )
     */
    public function redeemStampReward(string $locale, Request $request, string $stampCardId): JsonResponse
    {
        $staff = $request->user('staff_api');

        $validated = $request->validate([
            'member_id' => 'required|string',
            'note' => 'nullable|string|max:500',
        ]);

        // Find stamp card
        $stampCard = StampCard::find($stampCardId);
        if (! $stampCard) {
            return response()->json(['message' => 'Stamp card not found'], 404);
        }

        // Verify staff access
        if (! $staff->isRelatedToClub($stampCard->club)) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        // Find member
        $member = Member::find($validated['member_id']);
        if (! $member) {
            $member = $this->memberService->findActiveByIdentifier($validated['member_id']);
        }
        if (! $member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        try {
            $result = $this->stampService->redeemReward(
                card: $stampCard,
                member: $member,
                staff: $staff,
                image: null,
                note: $validated['note'] ?? null
            );

            if (! $result['success']) {
                return response()->json(['message' => $result['error'] ?? 'Unable to redeem reward'], 422);
            }

            return response()->json([
                'message' => 'Stamp reward redeemed successfully',
                'reward_title' => $stampCard->reward_title,
                'remaining_rewards' => $result['pending_rewards'] ?? 0,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VOUCHER OPERATIONS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validate a voucher code.
     *
     * Check if a voucher code is valid for a specific member and order.
     *
     * @OA\Post(
     *     path="/{locale}/v1/staff/vouchers/validate",
     *     operationId="staffValidateVoucher",
     *     tags={"Staff"},
     *     summary="Validate a voucher code",
     *     description="Check if a voucher code is valid, not expired, and can be used by the member. Returns discount details if valid.",
     *     security={{"staff_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code", "member_id"},
     *             @OA\Property(property="code", type="string", description="Voucher code", example="SUMMER20"),
     *             @OA\Property(property="member_id", type="string", description="Member ID", example="019b8d48-97df-7177-8aa0-47f2107a8eb3"),
     *             @OA\Property(property="order_amount", type="integer", description="Order amount in cents (for min purchase check)", example=5000)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Voucher validation result", @OA\JsonContent(
     *         @OA\Property(property="valid", type="boolean", example=true),
     *         @OA\Property(property="voucher", type="object"),
     *         @OA\Property(property="discount_type", type="string", example="percentage"),
     *         @OA\Property(property="discount_amount", type="integer", example=20),
     *         @OA\Property(property="message", type="string", example="Voucher is valid")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function validateVoucher(string $locale, Request $request): JsonResponse
    {
        $staff = $request->user('staff_api');

        $validated = $request->validate([
            'code' => 'required|string',
            'member_id' => 'required|string',
            'order_amount' => 'nullable|integer|min:0',
        ]);

        $member = Member::find($validated['member_id']);
        if (! $member) {
            $member = $this->memberService->findActiveByIdentifier($validated['member_id']);
        }
        if (! $member) {
            return response()->json([
                'valid' => false,
                'message' => 'Member not found',
            ]);
        }

        $result = $this->voucherService->validate(
            code: $validated['code'],
            member: $member,
            clubId: $staff->club_id,
            orderAmount: $validated['order_amount'] ?? null
        );

        return response()->json($result);
    }

    /**
     * Redeem a voucher.
     *
     * @OA\Post(
     *     path="/{locale}/v1/staff/vouchers/{voucherId}/redeem",
     *     operationId="staffRedeemVoucher",
     *     tags={"Staff"},
     *     summary="Redeem a voucher",
     *     description="Apply a voucher to a transaction. Records the redemption and returns the discount applied.",
     *     security={{"staff_auth_token": {}}},
     *
     *     @OA\Parameter(name="locale", in="path", description="Locale code", required=true, @OA\Schema(type="string", default="en-us")),
     *     @OA\Parameter(name="voucherId", in="path", description="Voucher ID", required=true, @OA\Schema(type="string")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"member_id"},
     *             @OA\Property(property="member_id", type="string", description="Member ID", example="019b8d48-97df-7177-8aa0-47f2107a8eb3"),
     *             @OA\Property(property="order_amount", type="integer", description="Order amount in cents", example=5000),
     *             @OA\Property(property="order_reference", type="string", description="Order/receipt reference", example="INV-2026-001")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Voucher redeemed", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string", example="Voucher redeemed successfully"),
     *         @OA\Property(property="discount_applied", type="integer", example=1000),
     *         @OA\Property(property="redemption_id", type="string", example="019b8d49-1234-7abc-8def-17f2107a8ec0")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Voucher or member not found"),
     *     @OA\Response(response=422, description="Voucher cannot be redeemed")
     * )
     */
    public function redeemVoucher(string $locale, Request $request, string $voucherId): JsonResponse
    {
        $staff = $request->user('staff_api');

        $validated = $request->validate([
            'member_id' => 'required|string',
            'order_amount' => 'nullable|integer|min:0',
            'order_reference' => 'nullable|string|max:255',
        ]);

        // Find voucher
        $voucher = Voucher::find($voucherId);
        if (! $voucher) {
            return response()->json(['message' => 'Voucher not found'], 404);
        }

        // Find member
        $member = Member::find($validated['member_id']);
        if (! $member) {
            $member = $this->memberService->findActiveByIdentifier($validated['member_id']);
        }
        if (! $member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        try {
            $result = $this->voucherService->redeem(
                voucher: $voucher,
                member: $member,
                orderAmount: $validated['order_amount'] ?? null,
                orderReference: $validated['order_reference'] ?? null,
                staff: $staff,
                image: null
            );

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Unable to redeem voucher',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Voucher redeemed successfully',
                'discount_applied' => $result['discount_amount'] ?? 0,
                'redemption_id' => $result['redemption']->id ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
