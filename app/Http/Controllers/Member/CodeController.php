<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\PointCode;
use App\Services\Card\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Member\CodeController
 *
 * Handles the redemption of 4-digit codes by logged-in members.
 */
class CodeController extends Controller
{
    /**
     * Show the form where a member can enter a 4-digit code to redeem points.
     *
     * @return \Illuminate\View\View
     */
    public function showRedeemCode()
    {
        return view('member.code.redeem');
    }

    /**
     * Process the code redemption, validate it, and award points via TransactionService.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postRedeemCode(Request $request, TransactionService $transactionService)
    {
        $request->validate([
            'code' => 'required|digits:4',
        ]);

        $codeEntry = PointCode::where('code', $request->input('code'))->first();

        if (! $codeEntry) {
            return back()->with('error', trans('common.code_does_not_exist_msg'));
        }
        if ($codeEntry->isUsed()) {
            return back()->with('error', trans('common.code_used_msg'));
        }
        if ($codeEntry->isExpired()) {
            return back()->with('error', trans('common.code_expired_msg'));
        }

        $member = auth('member')->user();

        // Use the TransactionService to record the code redemption
        $transactionService->addCodeRedemption($codeEntry, $member);

        // Mark the code as used
        $codeEntry->used_by = $member->id;
        $codeEntry->used_at = Carbon::now();
        $codeEntry->expires_at = null;
        $codeEntry->save();

        // Link to card
        $cardLink = route('member.card', ['card_id' => $codeEntry->card->id]);

        return redirect()
            ->route('member.code.enter')
            ->with('success', trans('common.code_redeemed_msg', ['points' => $codeEntry->points, 'card' => '<a class="underline" href="'.$cardLink.'">'.$codeEntry->card->head.'</a>']));
    }
}
