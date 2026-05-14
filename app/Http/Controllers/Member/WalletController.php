<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\StampCard;
use App\Models\Voucher;
use App\Services\Card\CardService;
use Illuminate\Http\Request;

/**
 * Class WalletController
 */
class WalletController extends Controller
{
    /**
     * Handles the request to display the wallet page.
     *
     * This method retrieves the wallet data, applies sorting and filtering
     * options from the request or from cookies, and returns the wallet view
     * with attached cookies for the sorting and hide_expired filter.
     */
    public function showWallet(Request $request, CardService $cardService): \Illuminate\Http\Response
    {
        // Define the allowed values for the sort parameter
        $allowedSortValues = [
            'last_points_claimed_at,desc',
            'last_points_claimed_at,asc',
            'total_amount_purchased,desc',
            'total_amount_purchased,asc',
            'number_of_rewards_claimed,desc',
            'number_of_rewards_claimed,asc',
            'last_reward_claimed_at,desc',
            'last_reward_claimed_at,asc',
        ];

        // Extract query parameters or get from cookies if they exist
        $sort = $request->query('sort', $request->cookie('wallet_sort', 'last_points_claimed_at,desc'));
        $hide_expired = $request->query('hide_expired', $request->cookie('wallet_hide_expired', 'true'));

        // Validate the 'sort' query parameter and reset it to default if it's not in the allowed sort values
        if (! in_array($sort, $allowedSortValues)) {
            $sort = 'last_points_claimed_at,desc';
        }

        // Validate the 'hide_expired' query parameter and reset it to default if it's not 'true' of 'false'
        if (! in_array($hide_expired, ['true', 'false'])) {
            $hide_expired = 'true';
        }

        // Convert hide_expired to a boolean
        $hide_expired = filter_var($hide_expired, FILTER_VALIDATE_BOOLEAN);

        // Extract the column and direction from the sort value
        [$column, $direction] = explode(',', $sort);

        // Retrieve cards from the authenticated member
        $memberId = auth('member')->user()->id;

        $cards = $cardService->findCardsWithMemberTransactions($memberId, $column, $direction, 'is_active', true, $hide_expired);

        // Fetch member's vouchers (public + targeted to member + claimed by member)
        $vouchers = Voucher::where('is_active', true)
            ->where(function ($query) use ($memberId) {
                // Public vouchers OR vouchers targeted to this specific member OR claimed by member
                $query->where('is_visible_by_default', true)
                    ->orWhere('target_member_id', $memberId)
                    ->orWhere('claimed_by_member_id', $memberId); // Include claimed vouchers
            })
            ->where(function ($query) {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query) use ($hide_expired) {
                if ($hide_expired) {
                    $query->where(function ($q) {
                        $q->whereNull('valid_until')
                            ->orWhere('valid_until', '>', now());
                    });
                } else {
                    $query->whereRaw('1=1'); // Include expired if hide_expired is false
                }
            })
            ->with(['club', 'batch'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Fetch enrolled stamp cards for authenticated member
        $stampCards = StampCard::where('is_active', true)
            ->whereHas('enrollments', function ($query) use ($memberId) {
                $query->where('member_id', $memberId)
                    ->where('is_active', true);
            })
            ->with(['club', 'partner', 'enrollments' => function ($query) use ($memberId) {
                $query->where('member_id', $memberId);
            }])
            ->get();

        // Prepare view
        $view = view('member.wallet.wallet', compact('cards', 'sort', 'hide_expired', 'vouchers', 'stampCards'));

        // Convert boolean values back to strings for the cookie
        $hide_expired = $hide_expired ? 'true' : 'false';

        // Create cookies for sort and hide_expired
        $sortCookie = cookie('wallet_sort', $sort, 43200, '/', null, false, true, false, 'lax');
        $hideExpiredCookie = cookie('wallet_hide_expired', $hide_expired, 43200, '/', null, false, true, false, 'lax');

        // Attach cookies to the response and return it
        $response = response($view)->withCookie($sortCookie)->withCookie($hideExpiredCookie);

        return $response;
    }
}
