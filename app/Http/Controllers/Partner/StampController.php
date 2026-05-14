<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\StampCard;

class StampController extends Controller
{
    /**
     * Show stamp transactions page for a member and stamp card.
     *
     * GET /{locale}/partner/stamp-transactions/{member_identifier}/{stamp_card_id}
     */
    public function showStampTransactions(string $locale, string $member_identifier, string $stamp_card_id)
    {
        // Find member
        $member = Member::where('unique_identifier', $member_identifier)->first();

        // Find stamp card
        $card = StampCard::find($stamp_card_id);

        // Check if card belongs to partner's clubs
        $partner = auth('partner')->user();
        if ($card && ! in_array($card->club_id, $partner->clubs->pluck('id')->toArray())) {
            abort(403, 'This stamp card does not belong to your clubs');
        }

        // Get enrollment if exists
        $enrollment = null;
        if ($member && $card) {
            $enrollment = $card->enrollments()
                ->where('member_id', $member->id)
                ->first();
        }

        return view('partner.stamps.history', compact('member', 'card', 'enrollment'));
    }

    /**
     * Delete the last stamp transaction.
     *
     * GET /{locale}/partner/delete-last-stamp/{member_identifier}/{stamp_card_id}
     */
    public function deleteLastStamp(string $locale, string $member_identifier, string $stamp_card_id)
    {
        // Find member
        $member = Member::where('unique_identifier', $member_identifier)->first();

        // Find stamp card
        $card = StampCard::find($stamp_card_id);

        // Check if card belongs to partner's clubs
        $partner = auth('partner')->user();
        if ($card && ! in_array($card->club_id, $partner->clubs->pluck('id')->toArray())) {
            abort(403, 'This stamp card does not belong to your clubs');
        }

        // Get the last stamp transaction for this member and card
        if ($member && $card) {
            $lastTransaction = \App\Models\StampTransaction::where('stamp_card_id', $card->id)
                ->where('member_id', $member->id)
                ->whereIn('event', [
                    \App\Models\StampTransaction::EVENT_STAMP_EARNED,
                    \App\Models\StampTransaction::EVENT_STAMPS_BONUS,
                    \App\Models\StampTransaction::EVENT_STAMPS_ADJUSTED,
                ])
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastTransaction) {
                // Use StampService to void the transaction
                app(\App\Services\StampService::class)->voidStamps(
                    $lastTransaction,
                    'Deleted by partner: '.$partner->name
                );

                session()->flash('success', 'Last stamp transaction deleted successfully');
            } else {
                session()->flash('error', 'No eligible stamp transaction found to delete');
            }
        }

        return redirect()->route('partner.stamp.transactions', [
            'member_identifier' => $member_identifier,
            'stamp_card_id' => $stamp_card_id,
        ]);
    }
}
