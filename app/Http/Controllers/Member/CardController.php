<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\Card\AnalyticsService;
use App\Services\Card\CardService;
use App\Services\Card\RewardService;
use App\Services\TierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Class CardController
 *
 * Controller for handling member card-related actions.
 */
class CardController extends Controller
{
    /**
     * Display the card index view.
     *
     * @param  string  $locale  The locale code
     * @param  string  $card_id  The card ID
     * @param  Request  $request  The HTTP request instance
     * @param  CardService  $cardService  The card service instance
     * @return \Illuminate\View\View
     */
    public function showCard(string $locale, string $card_id, Request $request, CardService $cardService, AnalyticsService $analyticsService, TierService $tierService)
    {
        $card = $cardService->findActiveCard($card_id);
        if ($card) {
            $card->load('activeRewards');
        }

        if ($card) {
            $cardViewsIncremented = $analyticsService->incrementViews($card);
        }

        // Fetch tier data for this card's club
        $memberTierData = null;
        if ($card && auth('member')->check()) {
            $member = auth('member')->user();
            $club = $card->club;

            if ($club) {
                $memberTier = $member->memberTiers()
                    ->forClub($club)
                    ->active()
                    ->with('tier')
                    ->first();

                // Only show tier if member has actually earned points in this club
                if ($memberTier) {
                    // Get qualifying stats to check if member has earned anything
                    $qualifyingStats = $tierService->getQualifyingStats($member, $club);
                    $lifetimePoints = $qualifyingStats['lifetime_points'] ?? 0;

                    // Only show tier if member has earned points (not just default tier assignment)
                    if ($lifetimePoints > 0) {
                        $nextTier = $memberTier->tier->getNextTier();
                        $progress = [];

                        // If there's a next tier, calculate progress toward it
                        if ($nextTier) {
                            $progress = [
                                'points' => [
                                    'current' => $lifetimePoints,
                                    'threshold' => $nextTier->points_threshold,
                                    'percentage' => $nextTier->points_threshold > 0
                                        ? min(100, ($lifetimePoints / $nextTier->points_threshold) * 100)
                                        : 100,
                                ],
                            ];
                        }

                        $memberTierData = [
                            'memberTier' => $memberTier,
                            'club' => $club,
                            'card' => $card,
                            'progress' => $progress,
                        ];
                    }
                }
            }
        }

        return $card ? view('member.card.index', compact('card', 'memberTierData')) : view('member.card.card-404');
    }

    /**
     * Display the card reward view.
     *
     * @param  string  $locale  The locale code
     * @param  string  $card_id  The card ID
     * @param  string  $reward_id  The reward ID
     * @param  Request  $request  The HTTP request instance
     * @param  CardService  $cardService  The card service instance
     * @return \Illuminate\View\View
     */
    public function showReward(string $locale, string $card_id, string $reward_id, Request $request, CardService $cardService, RewardService $rewardService, AnalyticsService $analyticsService, TierService $tierService)
    {
        // Card
        $card = $cardService->findActiveCard($card_id);
        if ($card) {
            $card->load('activeRewards');
        }

        if ($card) {
            $cardViewsIncremented = $analyticsService->incrementViews($card);
        }

        // Reward
        $reward = $rewardService->findActiveReward($reward_id);

        if ($reward) {
            $rewardViewsIncremented = $analyticsService->incrementViews($reward, $card);
        }

        // Balance
        $balance = ($card && $reward && auth('member')->check()) ? $card->getMemberBalance(null) : null;

        // Fetch tier data for this card's club (same as showCard)
        $memberTierData = null;
        if ($card && auth('member')->check()) {
            $member = auth('member')->user();
            $club = $card->club;

            if ($club) {
                $memberTier = $member->memberTiers()
                    ->forClub($club)
                    ->active()
                    ->with('tier')
                    ->first();

                // Only show tier if member has actually earned points in this club
                if ($memberTier) {
                    // Get qualifying stats to check if member has earned anything
                    $qualifyingStats = $tierService->getQualifyingStats($member, $club);
                    $lifetimePoints = $qualifyingStats['lifetime_points'] ?? 0;

                    // Only show tier if member has earned points (not just default tier assignment)
                    if ($lifetimePoints > 0) {
                        $nextTier = $memberTier->tier->getNextTier();
                        $progress = [];

                        // If there's a next tier, calculate progress toward it
                        if ($nextTier) {
                            $progress = [
                                'points' => [
                                    'current' => $lifetimePoints,
                                    'threshold' => $nextTier->points_threshold,
                                    'percentage' => $nextTier->points_threshold > 0
                                        ? min(100, ($lifetimePoints / $nextTier->points_threshold) * 100)
                                        : 100,
                                ],
                            ];
                        }

                        $memberTierData = [
                            'memberTier' => $memberTier,
                            'club' => $club,
                            'card' => $card,
                            'progress' => $progress,
                        ];
                    }
                }
            }
        }

        return ($card && $reward) ? view('member.card.reward', compact('card', 'reward', 'balance', 'memberTierData')) : view('member.card.reward-404');
    }

    /**
     * Display the claim reward view.
     *
     * @param  string  $locale  The locale code
     * @param  string  $card_id  The card ID
     * @param  string  $reward_id  The reward ID
     * @param  Request  $request  The HTTP request instance
     * @param  CardService  $cardService  The card service instance
     * @return \Illuminate\View\View
     */
    public function showClaimReward(string $locale, string $card_id, string $reward_id, Request $request, CardService $cardService, RewardService $rewardService, AnalyticsService $analyticsService)
    {
        // Card
        $card = $cardService->findActiveCard($card_id);
        if ($card) {
            $card->load('activeRewards');
        }

        if ($card) {
            $cardViewsIncremented = $analyticsService->incrementViews($card);
        }

        // Reward
        $reward = $rewardService->findActiveReward($reward_id);

        if ($reward) {
            $rewardViewsIncremented = $analyticsService->incrementViews($reward, $card);
        }

        // Balance
        $balance = ($card && $reward && auth('member')->check()) ? $card->getMemberBalance(null) : null;

        // Claim reward URL
        $claimRewardUrl = ($card && $reward && auth('member')->check()) ? URL::signedRoute('staff.claim.reward', ['card_id' => $card->id, 'reward_id' => $reward->id, 'member_identifier' => auth('member')->user()->unique_identifier], $expiration = now()->addMinutes((int) config('default.reward_claim_qr_valid_minutes'))) : null;

        return ($card && $reward) ? view('member.card.reward-claim', compact('card', 'reward', 'balance', 'claimRewardUrl')) : view('member.card.reward-404');
    }

    /**
     * Associate the authenticated member with a specified card.
     *
     * @param  string  $locale  The locale code
     * @param  string  $card_id  The ID of the card to be followed
     * @param  Request  $request  The current HTTP request instance
     * @param  CardService  $cardService  The service handling card operations
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function follow(string $locale, string $card_id, Request $request, CardService $cardService)
    {
        // If the member is not authenticated, store pending action and redirect to login
        if (! auth('member')->check()) {
            // Store pending action - will be executed after login
            $pendingActionService = resolve(\App\Services\Member\PendingCardActionService::class);
            $pendingActionService->store('card', $card_id);

            // Set redirect target to My Cards (action will add the card automatically)
            session()->put('from.member', route('member.cards'));

            return redirect()->route('member.login');
        }

        // Retrieve the active card with the given id
        $card = $cardService->findActiveCard($card_id);

        // If the card does not exist or is not active, show a 404 page
        if ($card === null) {
            return view('member.card.card-404');
        }

        // Follow the card and redirect to My Cards page with a success toast
        $cardService->followCard($card);

        return redirect()
            ->route('member.cards')
            ->with('toast', [
                'type' => 'success',
                'text' => trans('common.card_added'),
            ]);
    }

    /**
     * Disassociate the authenticated member from a specified card.
     *
     * @param  string  $locale  The locale code
     * @param  string  $card_id  The ID of the card to be unfollowed
     * @param  Request  $request  The current HTTP request instance
     * @param  CardService  $cardService  The service handling card operations
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function unfollow(string $locale, string $card_id, Request $request, CardService $cardService)
    {
        // If the member is not authenticated, store the current URL and redirect to the login page
        if (! auth('member')->check()) {
            session()->put('from.member', url()->current());

            return redirect()->route('member.login');
        }

        // Retrieve the active card with the given id
        $card = $cardService->findActiveCard($card_id);

        // If the card does not exist or is not active, show a 404 page
        if ($card === null) {
            return view('member.card.card-404');
        }

        // Unfollow the card and redirect to the card page with a success toast
        $cardService->unfollowCard($card);

        return redirect()
            ->route('member.card', ['card_id' => $card->id])
            ->with('toast', [
                'type' => 'success',
                'text' => trans('common.card_removed'),
            ]);
    }
}
