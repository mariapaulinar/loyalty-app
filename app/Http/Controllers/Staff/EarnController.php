<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\AddPointsRequest;
use App\Services\Card\CardService;
use App\Services\Card\TransactionService;
use App\Services\I18nService;
use App\Services\Member\MemberService;
use App\Services\SettingsService;
use Illuminate\Http\Request;

/**
 * Class EarnController
 *
 * Handles points earning for members, like via QR code scanning.
 */
class EarnController extends Controller
{
    /**
     * Display the form to earn points.
     */
    public function showEarnPoints(
        string $locale,
        string $member_identifier,
        string $card_identifier,
        Request $request,
        MemberService $memberService,
        CardService $cardService,
        I18nService $i18nService,
        SettingsService $settingsService
    ): \Illuminate\View\View {
        $member = $memberService->findActiveByIdentifier($member_identifier);
        $card = $cardService->findActiveCardByIdentifier($card_identifier);
        if (! $card) {
            abort(404);
        }

        // Check if staff has access to card
        if (! auth('staff')->user()->isRelatedToCard($card)) {
            abort(401);
        }

        $currency = $i18nService->getCurrencyDetails($card->currency);

        // Get tier multiplier if tiers are enabled
        $tierMultiplier = 1.00;
        if ($member && $settingsService->get('tiers.enabled', true)) {
            $club = $card->club;
            if ($club) {
                $memberTier = $member->memberTiers()
                    ->forClub($club)
                    ->active()
                    ->with('tier')
                    ->first();

                if ($memberTier?->tier) {
                    $tierMultiplier = (float) $memberTier->tier->points_multiplier;
                }
            }
        }

        return view('staff.loyalty-cards.add', compact('card', 'member', 'currency', 'tierMultiplier'));
    }

    /**
     * Process the request of earning points and redirect to the transactions list.
     *
     * This method handles the processing of the 'earn points' request. It uses
     * the provided member and card identifiers along with the form request data
     * to add a purchase transaction. Once completed, it redirects to the transactions
     * list for the specified member and card.
     */
    public function postEarnPoints(
        string $locale,
        string $member_identifier,
        string $card_identifier,
        AddPointsRequest $request,
        TransactionService $transactionService
    ): \Illuminate\Http\RedirectResponse {

        $staffUser = auth('staff')->user();

        // Cast request values to expected types
        $purchaseAmount = $request->purchase_amount !== null ? (float) $request->purchase_amount : null;
        $points = $request->points !== null ? (float) $request->points : null;
        $pointsOnly = (bool) $request->points_only;

        // Process the 'add purchase' transaction
        $transaction = $transactionService->addPurchase(
            $member_identifier,
            $card_identifier,
            $staffUser,
            $purchaseAmount,
            $points,
            $request->image,
            $request->note,
            $pointsOnly
        );

        // Redirect to the transactions list with the newly created transaction
        session()->flash('success', trans('common.transaction_added'));

        return redirect()->route('staff.transactions', [
            'member_identifier' => $member_identifier,
            'card_identifier' => $card_identifier,
        ]);
    }
}
