<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Staff\CodeController
 *
 * Handles the creation (generation) of 4-digit point redemption codes by staff.
 */

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\PointCode;
use App\Services\Card\CardService;
use Illuminate\Http\Request;

class CodeController extends Controller
{
    /**
     * Show the form where staff can enter the number of points to generate a code.
     *
     * @return \Illuminate\View\View
     */
    public function showGenerateCode(
        string $locale,
        string $card_identifier,
        Request $request,
        CardService $cardService
    ) {
        // Find the card
        $card = $cardService->findActiveCardByIdentifier($card_identifier);
        abort_if(! $card, 404, 'Card not found.');

        // Check if staff has access to this card
        $staffUser = auth('staff')->user();
        if (! $staffUser->isRelatedToCard($card)) {
            abort(401, 'Unauthorized');
        }

        return view('staff.code.generate', compact('card'));
    }

    /**
     * Process the form submission to generate a 4-digit code for points redemption.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postGenerateCode(
        string $locale,
        string $card_identifier,
        Request $request,
        CardService $cardService
    ) {
        // Check the card
        $card = $cardService->findActiveCardByIdentifier($card_identifier);
        abort_if(! $card, 404, 'Card not found.');

        // Validate the input
        $request->validate([
            'points' => 'required|integer|min:'.$card->min_points_per_purchase.'|max:'.$card->max_points_per_purchase,
        ]);

        // Ensure staff has access to it
        $staffUser = auth('staff')->user();
        if (! $staffUser->isRelatedToCard($card)) {
            abort(401, 'Unauthorized');
        }

        // Generate a 4-digit code
        $code = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // Create the point code row
        PointCode::create([
            'staff_id' => $staffUser->id,
            'card_id' => $card->id,
            'code' => $code,
            'points' => $request->input('points'),
            'expires_at' => now()->addMinutes((int) config('default.code_to_redeem_points_valid_minutes')),
            'created_by' => $staffUser->id,
            'updated_by' => $staffUser->id,
        ]);

        $msg = trans('common.generated_code', ['code' => $code]);
        $msg .= ' - <a class="underline" href="'.route('staff.data.list', ['name' => 'codes']).'">'.trans('common.view_all_codes').'</a>';

        return redirect()
            ->back()
            ->with('success', $msg);
    }
}
