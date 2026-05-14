<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\Card\CardService;
use App\Services\I18nService;
use App\Services\Member\MemberService;
use Illuminate\Http\Request;

/**
 * Class TransactionController
 */
class TransactionController extends Controller
{
    /**
     * Display the form to earn points.
     */
    public function showTransactions(
        string $locale,
        string $member_identifier,
        string $card_identifier,
        Request $request,
        MemberService $memberService,
        CardService $cardService,
        I18nService $i18nService
    ): \Illuminate\View\View {
        $member = $memberService->findActiveByIdentifier($member_identifier);
        $card = $cardService->findActiveCardByIdentifier($card_identifier);
        if (! $card) {
            abort(404);
        }

        $currency = $i18nService->getCurrencyDetails($card->currency);

        return view('staff.loyalty-cards.history', compact('card', 'member', 'currency'));
    }
}
