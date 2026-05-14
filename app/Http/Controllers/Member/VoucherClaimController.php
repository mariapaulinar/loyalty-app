<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Member Voucher Claim Controller
 *
 * Purpose:
 * Handles QR code-based voucher claiming for members. When a member scans
 * a batch QR code, they're directed here to claim an unused voucher.
 *
 * Design Tenets:
 * - **Login Required**: Members must authenticate to claim vouchers
 * - **Auto-Assignment**: System automatically assigns next available voucher
 * - **One Per Member**: Prevents duplicate claims from same batch
 * - **Real-time Availability**: Shows remaining vouchers count
 */

namespace App\Http\Controllers\Member;

use App\Events\VoucherClaimed;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VoucherClaimController extends Controller
{
    /**
     * Show claim landing page for a batch.
     * If member already claimed from this batch, show their existing voucher.
     * If batch has no available vouchers, show exhausted message.
     *
     * Flow:
     * - Unauthenticated: Store claim URL in session for redirect after login/signup
     * - Authenticated: Check for existing claim and show claim page
     */
    public function show(string $locale, string $batchId, string $token): View
    {
        $batch = VoucherBatch::where('id', $batchId)
            ->where('claim_token', $token)
            ->with(['vouchers' => function ($query) {
                $query->limit(1); // Load just one voucher for template data
            }])
            ->firstOrFail();

        // Check if batch is active
        if ($batch->status !== 'active') {
            abort(404, trans('common.batch_not_available'));
        }

        $member = auth('member')->user();

        // If not authenticated, store claim URL in session for post-login redirect
        // This matches the pattern used in AuthController for cards (line 42)
        if (! $member) {
            $currentUrl = request()->url();
            session()->put('from.member', $currentUrl);
        }

        // Check if member already claimed from this batch
        $existingVoucher = null;
        if ($member) {
            // Check via pivot table
            $existingVoucher = $member->vouchers()
                ->where('batch_id', $batch->id)
                ->first();
        }

        // Get template voucher for displaying title/description (first voucher in batch)
        $templateVoucher = $batch->vouchers->first();

        return view('member.vouchers.claim', compact('batch', 'existingVoucher', 'templateVoucher'));
    }

    /**
     * Process voucher claim.
     * Assigns next available unclaimed voucher to the authenticated member.
     */
    public function claim(string $locale, string $batchId, string $token): RedirectResponse
    {
        $member = auth('member')->user();

        if (! $member) {
            return redirect()->route('member.login', ['locale' => $locale])
                ->with('info', trans('common.login_to_claim_voucher'));
        }

        $batch = VoucherBatch::where('id', $batchId)
            ->where('claim_token', $token)
            ->firstOrFail();

        // Verify batch is active
        if ($batch->status !== 'active') {
            return back()->with('error', trans('common.batch_not_available'));
        }

        // Check if member already claimed from this batch (via pivot table)
        $existingClaim = $member->vouchers()
            ->where('batch_id', $batch->id)
            ->first();

        if ($existingClaim) {
            // Redirect to the already-claimed voucher
            return redirect()->route('member.voucher', [
                'locale' => $locale,
                'voucher_id' => $existingClaim->id,
            ])->with('info', trans('common.already_claimed_from_batch'));
        }

        // Find next available unclaimed voucher using transaction for thread safety
        try {
            $voucher = DB::transaction(function () use ($batch, $member) {
                // Find voucher that hasn't been claimed by ANY member yet
                $voucher = Voucher::where('batch_id', $batch->id)
                    ->where('is_active', true)
                    ->whereDoesntHave('members') // Not claimed by anyone
                    ->lockForUpdate() // Prevent race conditions
                    ->first();

                if (! $voucher) {
                    throw new \Exception(trans('common.no_vouchers_available'));
                }

                // Attach voucher to member via pivot table
                $member->vouchers()->attach($voucher->id, [
                    'claimed_via' => 'qr_scan',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Also update the voucher columns for backward compatibility
                $voucher->update([
                    'claimed_by_member_id' => $member->id,
                    'claimed_at' => now(),
                    'claimed_via' => 'qr_scan',
                ]);

                return $voucher;
            });

            // Dispatch event to send email notification
            event(new VoucherClaimed($voucher->fresh(), $member));

            // Redirect to the claimed voucher with celebration
            return redirect()->route('member.voucher', [
                'locale' => $locale,
                'voucher_id' => $voucher->id,
            ])
                ->with('voucher_claimed', $voucher->id)
                ->with('success', trans('common.voucher_claimed_successfully'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
