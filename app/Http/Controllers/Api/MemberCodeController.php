<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PointCode;
use App\Services\Card\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Member point-code redemption for native clients.
 */
class MemberCodeController extends Controller
{
    /**
     * Redeem a 4-digit point code for the authenticated member.
     *
     * POST /{locale}/v1/member/codes/redeem
     */
    public function redeem(string $locale, Request $request, TransactionService $transactionService): JsonResponse
    {
        $request->validate([
            'code' => 'required|digits:4',
        ]);

        $member = $request->user('member_api');
        $codeEntry = PointCode::where('code', $request->input('code'))->first();

        if (! $codeEntry) {
            return response()->json(['message' => trans('common.code_does_not_exist_msg')], 404);
        }

        if ($codeEntry->isUsed()) {
            return response()->json(['message' => trans('common.code_used_msg')], 400);
        }

        if ($codeEntry->isExpired()) {
            return response()->json(['message' => trans('common.code_expired_msg')], 400);
        }

        $transactionService->addCodeRedemption($codeEntry, $member);

        $codeEntry->used_by = $member->id;
        $codeEntry->used_at = Carbon::now();
        $codeEntry->expires_at = null;
        $codeEntry->save();

        return response()->json([
            'message' => 'Code redeemed',
            'points' => $codeEntry->points,
            'card_id' => $codeEntry->card_id,
        ]);
    }
}
