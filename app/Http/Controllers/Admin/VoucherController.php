<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VoucherController extends Controller
{
    /**
     * Show voucher transactions page for a member and voucher.
     *
     * GET /{locale}/admin/voucher-transactions/{member_identifier}/{voucher_id}
     */
    public function showVoucherTransactions(string $locale, string $member_identifier, string $voucher_id): View
    {
        // Find member
        $member = Member::where('unique_identifier', $member_identifier)
            ->orWhere('id', $member_identifier)
            ->firstOrFail();

        // Find voucher
        $voucher = Voucher::findOrFail($voucher_id);

        return view('admin.vouchers.history', compact('member', 'voucher'));
    }

    /**
     * Delete the last voucher redemption.
     *
     * GET /{locale}/admin/delete-last-voucher-redemption/{member_identifier}/{voucher_id}
     */
    public function deleteLastRedemption(string $locale, string $member_identifier, string $voucher_id): RedirectResponse
    {
        // Find member
        $member = Member::where('unique_identifier', $member_identifier)
            ->orWhere('id', $member_identifier)
            ->first();

        // Find voucher
        $voucher = Voucher::find($voucher_id);

        // Get the last voucher redemption for this member and voucher
        if ($member && $voucher) {
            $lastRedemption = VoucherRedemption::where('voucher_id', $voucher->id)
                ->where('member_id', $member->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastRedemption) {
                // Delete the redemption
                $lastRedemption->delete();

                session()->flash('success', 'Last voucher redemption deleted successfully');
            } else {
                session()->flash('error', 'No voucher redemption found to delete');
            }
        }

        return redirect()->route('admin.voucher.transactions', [
            'member_identifier' => $member_identifier,
            'voucher_id' => $voucher_id,
        ]);
    }
}
