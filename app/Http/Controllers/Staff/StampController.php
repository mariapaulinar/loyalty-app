<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Staff interface for stamp card operations - adding stamps and redeeming rewards.
 * Simple, fast, optimized for in-store operations.
 *
 * Design Tenets:
 * - **Speed**: Optimized for fast in-person transactions
 * - **Mobile-first**: Perfect on phones/tablets
 * - **Error-proof**: Clear validation and feedback
 * - **Accessible**: Works offline with sync
 */

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\StampCard;
use App\Services\StampService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StampController extends Controller
{
    public function __construct(
        private readonly StampService $stampService
    ) {}

    /**
     * Show stamp transactions history for a member and stamp card.
     *
     * GET /{locale}/staff/stamps/{member_identifier}/{stamp_card_id}
     */
    public function showStampTransactions(string $locale, string $member_identifier, string $stamp_card_id)
    {
        // Find member
        $member = Member::where('unique_identifier', $member_identifier)->first();

        // Find stamp card
        $card = StampCard::find($stamp_card_id);

        // Check if card belongs to staff's club
        $staff = auth('staff')->user();
        if ($card && $card->club_id !== $staff->club_id) {
            $card = null;
        }

        // Get enrollment if exists
        $enrollment = null;
        if ($member && $card) {
            $enrollment = $card->enrollments()
                ->where('member_id', $member->id)
                ->first();
        }

        return view('staff.stamps.history', compact('member', 'card', 'enrollment'));
    }

    /**
     * Show stamp adding page for staff.
     *
     * GET /{locale}/staff/stamps/add/{member_identifier}/{stamp_card_id}
     */
    public function showAddStamps(string $locale, string $member_identifier, string $stamp_card_id)
    {
        // Find member by unique_identifier only
        $member = Member::where('unique_identifier', $member_identifier)->first();

        // Find stamp card
        $card = StampCard::find($stamp_card_id);

        // Check if card belongs to staff's club
        $staff = auth('staff')->user();
        if ($card && ! $card->isAccessibleByStaffClub($staff->club_id)) {
            $card = null;
        }

        // Get member's enrollment
        $enrollment = null;
        $eligibility = ['eligible' => false, 'reason' => trans('common.member_or_card_not_found')];

        if ($member && $card) {
            $enrollment = $card->enrollments()
                ->where('member_id', $member->id)
                ->first();

            // Check eligibility
            $eligibility = $this->stampService->checkEarningEligibility(
                card: $card,
                member: $member,
                purchaseAmount: 0
            );
        }

        return view('staff.stamps.add', compact('member', 'card', 'enrollment', 'eligibility'));
    }

    /**
     * Add stamps to a member's card.
     *
     * POST /{locale}/staff/stamps/add
     */
    public function addStamps(string $locale, Request $request)
    {
        // Find stamp card first for validation
        $card = StampCard::find($request->stamp_card_id);

        // Build validation rules
        $rules = [
            'member_identifier' => 'required|string',
            'stamp_card_id' => 'required|exists:stamp_cards,id',
            'stamps' => 'required|integer|min:1',
            'purchase_amount' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:51200',
            'note' => 'nullable|string|max:500',
        ];

        // Add maximum stamps per transaction validation if card has limit
        if ($card && $card->max_stamps_per_transaction) {
            $rules['stamps'] = [
                'required',
                'integer',
                'min:1',
                'max:'.$card->max_stamps_per_transaction,
            ];
        }

        // Add minimum purchase validation if card requires it
        if ($card && $card->min_purchase_amount && $card->min_purchase_amount > 0) {
            $rules['purchase_amount'] = [
                'required',
                'numeric',
                'min:'.$card->min_purchase_amount,
            ];
        }

        $validator = Validator::make($request->all(), $rules, [
            'purchase_amount.required' => trans('common.minimum_purchase_required', ['amount' => moneyFormat((float) ($card->min_purchase_amount ?? 0), $card->currency ?? 'USD')]),
            'purchase_amount.min' => trans('common.minimum_purchase_required', ['amount' => moneyFormat((float) ($card->min_purchase_amount ?? 0), $card->currency ?? 'USD')]),
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Find member
            $member = Member::where('unique_identifier', $request->member_identifier)->firstOrFail();

            // Reload stamp card
            $card = StampCard::findOrFail($request->stamp_card_id);

            // Check if card belongs to staff's club
            $staff = auth('staff')->user();
            if (! $card->isAccessibleByStaffClub($staff->club_id)) {
                return back()->with('error', trans('common.stamp_card_not_in_your_club'))->withInput();
            }

            // Check eligibility (now using decimal currency units, not cents)
            $eligibility = $this->stampService->checkEarningEligibility(
                card: $card,
                member: $member,
                purchaseAmount: $request->purchase_amount ? (float) $request->purchase_amount : null
            );

            if (! $eligibility['eligible']) {
                return back()->with('error', $eligibility['reason'])->withInput();
            }

            // Add stamp (now using decimal currency units, not cents)
            $result = $this->stampService->addStamp(
                card: $card,
                member: $member,
                staff: $staff,
                stamps: (int) $request->stamps,
                purchaseAmount: $request->purchase_amount ? (float) $request->purchase_amount : null,
                image: $request->file('image'),
                note: $request->note
            );

            if (! $result['success']) {
                return back()->with('error', $result['error'] ?? trans('common.failed_to_add_stamps'))->withInput();
            }

            // Success - redirect to staff stamp transactions page
            session()->flash('success', trans('common.stamp_added_successfully'));

            return redirect()->route('staff.stamp.transactions', [
                'member_identifier' => $member->unique_identifier,
                'stamp_card_id' => $card->id,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->with('error', trans('common.member_or_stamp_card_not_found'))->withInput();
        } catch (\Exception $e) {
            return back()->with('error', trans('common.error_adding_stamps'))->withInput();
        }
    }

    /**
     * Show physical reward claim page for staff.
     *
     * GET /{locale}/staff/stamps/claim/{member_identifier}/{stamp_card_id}
     */
    public function showClaimReward(string $locale, string $member_identifier, string $stamp_card_id)
    {
        // Find member
        $member = Member::where('unique_identifier', $member_identifier)->first();

        // Find stamp card
        $card = StampCard::find($stamp_card_id);

        // Check if card belongs to staff's club
        $staff = auth('staff')->user();
        if ($card && $card->club_id !== $staff->club_id) {
            abort(403, 'This stamp card does not belong to your club');
        }

        // Get enrollment if exists
        $enrollment = null;
        if ($member && $card) {
            $enrollment = $card->enrollments()
                ->where('member_id', $member->id)
                ->first();
        }

        return view('staff.stamps.claim', compact('member', 'card', 'enrollment'));
    }

    /**
     * Claim physical reward.
     *
     * POST /staff/stamps/claim
     */
    public function claimReward(string $locale, Request $request): \Illuminate\Http\RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'member_identifier' => 'required|string',
            'stamp_card_id' => 'required|exists:stamp_cards,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:51200',
            'note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            // Find member
            $member = Member::where('unique_identifier', $request->member_identifier)->firstOrFail();

            // Find stamp card
            $card = StampCard::findOrFail($request->stamp_card_id);

            // Check if card belongs to staff's club
            $staff = auth('staff')->user();
            if ($card->club_id !== $staff->club_id) {
                session()->flash('error', trans('common.stamp_card_not_in_your_club'));

                return redirect()->back();
            }

            // Redeem reward
            $result = $this->stampService->redeemReward(
                card: $card,
                member: $member,
                staff: $staff,
                image: $request->file('image'),
                note: $request->note
            );

            if (! $result['success']) {
                session()->flash('error', $result['error'] ?? trans('common.failed_to_redeem_reward'));

                return redirect()->back();
            }

            session()->flash('success', trans('common.reward_claimed_successfully'));

            return redirect()->route('staff.stamp.transactions', [
                'member_identifier' => $member->unique_identifier,
                'stamp_card_id' => $card->id,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            session()->flash('error', trans('common.member_or_stamp_card_not_found'));

            return redirect()->back();
        } catch (\Exception $e) {
            session()->flash('error', trans('common.error_claiming_reward'));

            return redirect()->back();
        }
    }

    /**
     * Redeem a stamp card reward.
     *
     * POST /staff/stamps/redeem
     */
    public function redeemReward(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'member_identifier' => 'required|string',
            'stamp_card_id' => 'required|exists:stamp_cards,id',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Find member
            $member = Member::where('unique_identifier', $request->member_identifier)->firstOrFail();

            // Find stamp card
            $card = StampCard::findOrFail($request->stamp_card_id);

            // Check if card belongs to staff's club
            $staff = auth('staff')->user();
            if ($card->club_id !== $staff->club_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This stamp card does not belong to your club',
                ], 403);
            }

            // Redeem reward
            $result = $this->stampService->redeemReward(
                card: $card,
                member: $member,
                staff: $staff
            );

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Failed to redeem reward',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reward redeemed successfully!',
                'data' => [
                    'reward_title' => $result['reward_title'],
                    'reward_value' => $result['reward_value'],
                    'remaining_rewards' => $result['remaining_rewards'],
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Member or stamp card not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while redeeming reward',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get member's stamp card status.
     *
     * GET /{locale}/staff/stamps/member/{identifier}
     */
    public function getMemberStatus(string $locale, string $identifier): JsonResponse
    {
        try {
            $member = Member::where('unique_identifier', $identifier)->firstOrFail();

            $staff = auth('staff')->user();

            // Get member's stamp cards for this staff's club
            $stampCards = $this->stampService->getMemberStampCards($member, $staff->club);

            $stampCardsData = $stampCards->map(function ($card) use ($member) {
                $enrollment = $card->enrollments()
                    ->where('member_id', $member->id)
                    ->first();

                return [
                    'id' => $card->id,
                    'title' => $card->title,
                    'current_stamps' => $enrollment->current_stamps ?? 0,
                    'stamps_required' => $card->stamps_required,
                    'progress_percentage' => $enrollment
                        ? round(($enrollment->current_stamps / $card->stamps_required) * 100, 1)
                        : 0,
                    'pending_rewards' => $enrollment->pending_rewards ?? 0,
                    'completed_count' => $enrollment->completed_count ?? 0,
                    'reward_title' => $card->reward_title,
                    'stamp_icon' => $card->stamp_icon,
                    'colors' => [
                        'bg' => $card->bg_color,
                        'text' => $card->text_color,
                        'stamp' => $card->stamp_color,
                        'empty_stamp' => $card->empty_stamp_color,
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'member' => [
                        'id' => $member->id,
                        'name' => $member->name,
                        'identifier' => $member->unique_identifier,
                    ],
                    'stamp_cards' => $stampCardsData,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Member not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
