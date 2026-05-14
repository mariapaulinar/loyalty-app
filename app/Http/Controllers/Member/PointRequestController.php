<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\PointRequest;
use App\Services\Card\CardService;
use App\Services\Card\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class PointRequestController
 *
 * Handles the creation of request links for receiving points and the redemption
 * of these links by other members.
 */
class PointRequestController extends Controller
{
    protected $cardService;

    protected $transactionService;

    /**
     * Constructor.
     */
    public function __construct(CardService $cardService, TransactionService $transactionService)
    {
        $this->cardService = $cardService;
        $this->transactionService = $transactionService;
    }

    /**
     * Display the form for generating a points request link.
     *
     * If an optional card_identifier is provided, it preselects that card.
     *
     * @return \Illuminate\View\View
     */
    public function showGenerateRequest(string $locale, Request $request, ?string $card_identifier = null)
    {
        $member = auth('member')->user();

        $selectedCard = null;
        if ($card_identifier) {
            $selectedCard = $this->cardService->findActiveCardByIdentifier($card_identifier);
        }

        // Retrieve only the cards where the member has at least one transaction.
        $cards = $this->cardService->findCardsWithMemberTransactions($member->id);

        // Transform the collection so that the keys are strings.
        // This prevents the numeric keys from being re‑indexed when merging.
        $cardOptions = [];
        foreach ($cards->pluck('head', 'id')->toArray() as $id => $head) {
            $cardOptions[(string) $id] = $head;
        }

        // Sort $cardOptions alphabetically by value while maintaining key associations
        asort($cardOptions);

        // Prepend a wildcard option
        $options = ['wildcard' => trans('common.select_card_wildcard')] + $cardOptions;

        return view('member.point_request.generate', compact('member', 'options', 'selectedCard'));
    }

    /**
     * Process the form submission for generating a points request link.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postGenerateRequest(string $locale, Request $request)
    {
        $member = auth('member')->user();

        $allowedCardIds = $this->cardService->findCardsWithMemberTransactions($member->id)->pluck('id')->map(function ($id) {
            return (string) $id; // cast to string for consistency
        })->toArray();
        $allowedCardIds[] = 'wildcard';

        // Validate only the optional card_id.
        $validated = $request->validate([
            'card_id' => 'required|in:'.implode(',', $allowedCardIds),
        ]);

        // Check if max request links limit is reached.
        $maxMemberRequestLinks = config('default.max_member_request_links');
        if ($member->pointRequests()->count() >= $maxMemberRequestLinks) {
            return redirect()
                ->back()
                ->with('error', trans('common.max_request_links_reached', ['maxLinks' => $maxMemberRequestLinks]));
        }

        // If the card_id is 'wildcard', set it to null.
        $card_id = ($validated['card_id'] == 'wildcard') ? null : $validated['card_id'];

        // Create a new points request with default values for other fields.
        $pointRequest = PointRequest::create([
            'card_id' => $card_id,
            'is_active' => true,
            'max_uses' => null,
            'per_member_limit' => 0,
            'expires_at' => null,
            'updated_by' => $member->id,
            'created_by' => $member->id,
        ]);

        // Build the URL for the generated request link.
        $url = route('member.request.points.send', ['request_identifier' => $pointRequest->unique_identifier]);

        return redirect($url);

        return redirect()
            ->back()
            ->with('success', trans('common.request_link_generated', ['url' => "<a href=\"$url\" class=\"underline\">$url</a>"]));
    }

    /**
     * Display the form for sending points using a request link.
     *
     * This method handles the logic for point transfer requests between members,
     * including validation of the request link and checking member balances.
     *
     * @param  string  $locale  Current locale for internationalization
     * @param  Request  $request  HTTP request instance
     * @param  string  $request_identifier  Unique identifier for the point request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException When request link is invalid or expired
     */
    public function showSendPoints(string $locale, Request $request, string $request_identifier)
    {
        // Validate the point request exists and is active
        $pointRequest = PointRequest::where('unique_identifier', $request_identifier)
            ->where('is_active', true)
            ->first();
        if (! $pointRequest) {
            abort(404, 'Request link not found.');
        }

        // Check if the request link has expired
        if ($pointRequest->expires_at && Carbon::now()->greaterThan($pointRequest->expires_at)) {
            abort(404, 'This request link has expired.');
        }

        // ─────────────────────────────────────────────────────────────
        // AUTH GATE: Redirect unauthenticated visitors to login
        // ─────────────────────────────────────────────────────────────
        // This route is in the public group because request links are
        // shareable URLs. But the member must be logged in to view
        // balances and send points. Store the intended URL so they
        // return here after login.
        if (! auth('member')->check()) {
            session()->put('url.intended', $request->fullUrl());

            return redirect()->route('member.login');
        }

        // Get both members involved in the transaction
        $requester = $pointRequest->member;          // Member requesting the points
        $member = auth('member')->user();            // Currently authenticated member

        // Prevent members from sending points to themselves
        $memberOwnsRequestLink = $requester->id === $member->id;

        // Initialize variables for card selection and point validation
        $memberCards = $this->cardService->findCardsWithMemberTransactions($member->id);
        $memberCardsOptions = [];
        $memberHasPoints = false;
        $cardBalances = [];

        // Process card selection and point balance only if member isn't the requester
        if (! $memberOwnsRequestLink) {
            // Filter to specific card if request is card-specific
            if ($pointRequest->card_id) {
                $memberCards = $memberCards->where('id', $pointRequest->card_id);
            }

            // Calculate balance for each available card
            $memberCards = $memberCards->map(function ($card) use ($member) {
                $card->balance = $card->getMemberBalance($member);

                return $card;
            });

            // Create options array for card selection dropdown
            $memberCardsOptions = $memberCards->map(function ($card) {
                return [
                    'id' => $card->id,
                    'head' => $card->head.' ('.number_format($card->balance).' points)',
                ];
            })->pluck('head', 'id')->toArray();

            // Determine if member has sufficient points
            $memberHasPoints = $pointRequest->card_id
                ? $memberCards->contains('id', $pointRequest->card_id)
                : $memberCards->some(fn ($card) => $card->balance > 0);

            // Map card IDs to balances for easier access in the view
            $cardBalances = $memberCards->mapWithKeys(fn ($card) => [
                $card->id => $card->balance,
            ])->toArray();
        }

        // Generate shareable request link URL
        $requestLink = route('member.request.points.send', ['request_identifier' => $pointRequest->unique_identifier]);

        // Return view with all necessary data
        return view('member.point_request.send', compact(
            'memberOwnsRequestLink',
            'requester',
            'requestLink',
            'pointRequest',
            'memberCards',
            'memberCardsOptions',
            'memberHasPoints',
            'cardBalances'
        ));
    }

    /**
     * Process the form submission for sending points using a request link.
     *
     * This method validates input, calls the transaction service to perform the points transfer,
     * and updates the usage count on the request.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postSendPoints(string $locale, Request $request, string $request_identifier)
    {
        $pointRequest = PointRequest::where('unique_identifier', $request_identifier)
            ->where('is_active', true)
            ->first();

        if (! $pointRequest) {
            abort(404, trans('common.request_link_not_found'));
        }

        if ($pointRequest->expires_at && Carbon::now()->greaterThan($pointRequest->expires_at)) {
            abort(404, trans('common.request_link_expired'));
        }

        // Require authentication for the POST action
        if (! auth('member')->check()) {
            session()->put('url.intended', url()->previous());

            return redirect()->route('member.login');
        }

        $sender = auth('member')->user();

        // Basic validation
        $validated = $request->validate([
            'card_id' => 'required|exists:cards,id',
            'points' => 'required|integer|min:1',
            'confirm' => 'required|accepted',
        ]);

        // Additional validations
        $card = Card::find($validated['card_id']);

        // Validate card belongs to sender and has transactions
        $senderCards = $this->cardService->findCardsWithMemberTransactions($sender->id);
        if (! $senderCards->contains('id', $validated['card_id'])) {
            return redirect()->back()->withErrors(['card_id' => 'Invalid card selected.']);
        }

        // If point request specifies a card, validate it matches
        if ($pointRequest->card_id && (string) $pointRequest->card_id !== $validated['card_id']) {
            return redirect()->back()->withErrors(['card_id' => 'Invalid card selected for this request.']);
        }

        // Check sender's balance
        $senderBalance = $card->getMemberBalance($sender);
        if ($senderBalance < $validated['points']) {
            return redirect()->back()->withErrors(['points' => trans('common.insufficient_balance')]);
        }

        // Check per_member_limit if set
        if ($pointRequest->per_member_limit > 0) {
            $previousUses = $pointRequest->transactions()
                ->where('sender_id', $sender->id)
                ->count();
            if ($previousUses >= $pointRequest->per_member_limit) {
                return redirect()->back()->with('error', trans('common.request_link_max_uses_reached'));
            }
        }

        try {
            // Call the transaction service to handle the point request redemption
            $this->transactionService->addPointRequest($pointRequest, $sender, $pointRequest->member, $validated['card_id'], $validated['points']);

            // Update columns in a single query
            $pointRequest->increment('usage_count', 1, [
                'points_received' => DB::raw('points_received + '.$validated['points']),
                'last_transaction_at' => Carbon::now('UTC'),
            ]);

            // If max_uses is set and reached, mark the request as inactive
            if ($pointRequest->max_uses && $pointRequest->usage_count >= $pointRequest->max_uses) {
                $pointRequest->is_active = false;
                $pointRequest->save();
            }

            return redirect()->back()->with('success', trans('common.points_sent_successfully', ['points' => number_format($validated['points']), 'receiver_name' => $pointRequest->member->name]));
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Point request transaction failed:', [
                'request_id' => $pointRequest->id,
                'sender_id' => $sender->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', trans('common.500_description'));
        }
    }
}
