<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Member;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function __construct(
        protected VoucherService $voucherService
    ) {}

    /**
     * Show voucher redemption form
     */
    public function showRedeemForm(?string $member_identifier = null): View
    {
        $member = null;
        $club = null;

        if ($member_identifier) {
            $member = Member::where('unique_identifier', $member_identifier)
                ->orWhere('id', $member_identifier)
                ->firstOrFail();
        }

        // Get staff's club
        $staff = auth('staff')->user();
        if ($staff->club_id) {
            $club = Club::find($staff->club_id);
        }

        return view('staff.vouchers.redeem', compact('member', 'club'));
    }

    /**
     * Show redemption form with pre-filled voucher (from QR code)
     */
    public function showRedeemWithVoucher(string $locale, string $member_identifier, string $voucher_id): View
    {
        $member = Member::where('unique_identifier', $member_identifier)
            ->orWhere('id', $member_identifier)
            ->firstOrFail();

        $voucher = Voucher::with('club')
            ->where('id', $voucher_id)
            ->orWhere('unique_identifier', $voucher_id)
            ->orWhere('code', $voucher_id)
            ->firstOrFail();

        // Get staff's club
        $staff = auth('staff')->user();
        $club = null;
        if ($staff->club_id) {
            $club = Club::find($staff->club_id);
        }

        return view('staff.vouchers.redeem', compact('member', 'club', 'voucher'));
    }

    /**
     * Validate voucher (AJAX)
     */
    public function validateVoucher(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'member_id' => 'required|exists:members,id',
            'club_id' => 'nullable|exists:clubs,id',
            'order_amount' => 'nullable|integer|min:0', // in cents (optional)
        ]);

        $member = Member::findOrFail($request->member_id);

        $result = $this->voucherService->validate(
            code: $request->code,
            member: $member,
            clubId: $request->club_id,
            orderAmount: $request->order_amount
        );

        return response()->json($result);
    }

    /**
     * Redeem voucher
     */
    public function redeem(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'voucher_id' => 'required|exists:vouchers,id',
            'member_id' => 'required|exists:members,id',
            'order_amount' => 'nullable|integer|min:0', // in cents (optional)
            'order_reference' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:51200', // 50MB max
        ]);

        $voucher = Voucher::findOrFail($request->voucher_id);
        $member = Member::findOrFail($request->member_id);
        $staff = auth('staff')->user();

        // Cast order_amount to int (FormData sends strings)
        $orderAmount = $request->order_amount ? (int) $request->order_amount : null;

        $result = $this->voucherService->redeem(
            voucher: $voucher,
            member: $member,
            orderAmount: $orderAmount,
            orderReference: $request->order_reference,
            staff: $staff,
            image: $request->file('image')
        );

        // Handle AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => __('common.voucher_redeemed'),
                    'redemption' => $result['redemption'],
                    'discount_amount' => $result['discount_amount'],
                    'redirect_url' => route('staff.voucher.transactions', [
                        'member_identifier' => $member->unique_identifier,
                        'voucher_id' => $voucher->id,
                    ]),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? __('common.error_occurred'),
            ], 422);
        }

        // Handle regular form submissions
        if ($result['success']) {
            session()->flash('success', __('common.voucher_redeemed_successfully'));

            return redirect()->route('staff.voucher.transactions', [
                'member_identifier' => $member->unique_identifier,
                'voucher_id' => $voucher->id,
            ]);
        }

        return back()
            ->with('error', $result['error'] ?? __('common.error_occurred'))
            ->withInput();
    }

    /**
     * Show voucher transaction history for a specific member and voucher
     */
    public function showMemberTransactions(string $locale, string $member_identifier, string $voucher_id): View
    {
        $member = Member::where('unique_identifier', $member_identifier)
            ->orWhere('id', $member_identifier)
            ->firstOrFail();

        $voucher = Voucher::findOrFail($voucher_id);

        // Verify voucher belongs to staff's club
        $staff = auth('staff')->user();
        if ($voucher->club_id !== $staff->club_id) {
            abort(403);
        }

        return view('staff.vouchers.member-transactions', compact('member', 'voucher'));
    }
}
