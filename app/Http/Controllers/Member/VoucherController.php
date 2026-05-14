<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use App\Services\Card\AnalyticsService;
use Illuminate\View\View;

class VoucherController extends Controller
{
    /**
     * Show voucher detail page
     *
     * @param  string  $locale  The locale code
     * @param  string  $voucher_id  The voucher ID
     */
    public function show(string $locale, string $voucher_id, AnalyticsService $analyticsService): View
    {
        // Find voucher by ID or unique_identifier
        $voucher = Voucher::with(['club'])
            ->where(function ($query) use ($voucher_id) {
                $query->where('id', $voucher_id)
                    ->orWhere('unique_identifier', $voucher_id);
            })
            ->where('is_active', true)
            ->whereHas('creator', function ($query) {
                $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('network_id')
                            ->orWhereHas('network', fn ($nq) => $nq->where('is_active', true));
                    });
            })
            ->firstOrFail();

        // Track view (cookie-based, once per visitor)
        $analyticsService->incrementViews($voucher);

        // Check if member is authenticated
        $member = auth('member')->user();
        $isMemberVoucher = false;
        $redemptions = collect();

        if ($member) {
            // Check if this voucher is targeted to the member
            $isMemberVoucher = $voucher->target_member_id === $member->id;

            // Get member's redemption history for this voucher
            $redemptions = VoucherRedemption::where('voucher_id', $voucher->id)
                ->where('member_id', $member->id)
                ->with(['staff', 'voucher.club'])
                ->orderBy('redeemed_at', 'desc')
                ->get();
        }

        // Check various states
        $isExpired = $voucher->is_expired;
        $isExhausted = $voucher->is_exhausted;
        $isExpiringSoon = $voucher->is_expiring_soon;

        return view('member.voucher.index', compact(
            'voucher',
            'member',
            'isMemberVoucher',
            'redemptions',
            'isExpired',
            'isExhausted',
            'isExpiringSoon'
        ));
    }

    /**
     * Save voucher to member's wallet
     *
     * Similar to following a loyalty card. Creates relationship via member_voucher pivot.
     */
    public function save(string $locale, string $voucher_id): \Illuminate\Http\RedirectResponse
    {
        // If the member is not authenticated, store pending action and redirect to login
        if (! auth('member')->check()) {
            // Store pending action - will be executed after login
            $pendingActionService = resolve(\App\Services\Member\PendingCardActionService::class);
            $pendingActionService->store('voucher', $voucher_id);

            // Set redirect target to My Cards (action will add the voucher automatically)
            session()->put('from.member', route('member.cards'));

            return redirect()->route('member.login');
        }

        $member = auth('member')->user();

        // Find voucher (any active voucher can be saved to My Cards)
        $voucher = Voucher::where(function ($query) use ($voucher_id) {
            $query->where('id', $voucher_id)
                ->orWhere('unique_identifier', $voucher_id);
        })
            ->where('is_active', true)
            ->whereHas('creator', function ($query) {
                $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('network_id')
                            ->orWhereHas('network', fn ($nq) => $nq->where('is_active', true));
                    });
            })
            ->firstOrFail();

        // ── Eligibility checks ──────────────────────────────────────────
        // Prevent wallet saves that would lead to failed redemptions.

        // Check if voucher is still valid (not expired, not exhausted)
        if (! $voucher->is_valid) {
            return redirect()
                ->route('member.voucher', ['voucher_id' => $voucher->id])
                ->with('toast', [
                    'type' => 'error',
                    'text' => trans('common.voucher_no_longer_available'),
                ]);
        }

        // Check target member restriction
        if ($voucher->target_member_id && $voucher->target_member_id !== $member->id) {
            return redirect()
                ->route('member.voucher', ['voucher_id' => $voucher->id])
                ->with('toast', [
                    'type' => 'error',
                    'text' => trans('common.voucher_not_available_to_you'),
                ]);
        }

        // For limited-use vouchers, prevent more members from saving than
        // can ever redeem. This stops multiple people from saving a single-use
        // batch voucher code and showing up at the store expecting to use it.
        if ($voucher->max_uses_total !== null) {
            $existingClaimCount = $voucher->members()->count();
            if ($existingClaimCount >= $voucher->max_uses_total) {
                return redirect()
                    ->route('member.voucher', ['voucher_id' => $voucher->id])
                    ->with('toast', [
                        'type' => 'error',
                        'text' => trans('common.voucher_fully_claimed'),
                    ]);
            }
        }

        // ── Save to wallet ──────────────────────────────────────────────

        // Check if already manually saved (claimed_via = null)
        $alreadySaved = $member->vouchers()
            ->where('vouchers.id', $voucher->id)
            ->wherePivot('claimed_via', null)
            ->exists();

        if (! $alreadySaved) {
            // Attach voucher to member via pivot table with claimed_via = null (manual save)
            $member->vouchers()->attach($voucher->id, [
                'claimed_via' => null, // Manual "Add to My Cards" action
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Redirect to My Cards page with success toast
        return redirect()
            ->route('member.cards')
            ->with('toast', [
                'type' => 'success',
                'text' => trans('common.card_added'),
            ]);
    }

    /**
     * Remove voucher from member's wallet
     */
    public function unsave(string $locale, string $voucher_id): \Illuminate\Http\RedirectResponse
    {
        // Require authentication
        if (! auth('member')->check()) {
            return redirect()->route('member.login');
        }

        $member = auth('member')->user();

        // Find voucher
        $voucher = Voucher::where(function ($query) use ($voucher_id) {
            $query->where('id', $voucher_id)
                ->orWhere('unique_identifier', $voucher_id);
        })
            ->where('is_active', true)
            ->whereHas('creator', function ($query) {
                $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('network_id')
                            ->orWhereHas('network', fn ($nq) => $nq->where('is_active', true));
                    });
            })
            ->firstOrFail();

        // Detach from member (only if not claimed via QR/other method)
        $member->vouchers()
            ->wherePivot('claimed_via', null) // Only remove saved vouchers, not claimed ones
            ->detach($voucher->id);

        return redirect()
            ->route('member.voucher', ['voucher_id' => $voucher->id])
            ->with('toast', [
                'type' => 'success',
                'text' => trans('common.card_removed'),
            ]);
    }
}
