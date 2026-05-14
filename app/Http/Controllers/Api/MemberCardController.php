<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Card\CardService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MemberCardController extends Controller
{
    /**
     * Retrieve all cards followed and transacted by the authenticated member.
     *
     * @OA\Get(
     *     path="/{locale}/v1/member/all-cards",
     *     operationId="getAllCards",
     *     tags={"Member"},
     *     summary="Retrieve all cards followed and transacted by the member",
     *     description="Retrieve all cards that the authenticated member follows and has transacted with.",
     *     security={{"member_auth_token": {}}},
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="path",
     *         description="Locale setting (e.g., `en-us`)",
     *         required=true,
     *
     *         @OA\Schema(type="string", default="en-us")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation, returns the array of followed and transacted cards",
     *
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Card"))
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access, invalid or missing member token",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")
     *     )
     * )
     *
     * @param  string  $locale  Locale setting (e.g., 'en-us')
     * @param  Request  $request  The incoming HTTP request
     * @param  CardService  $cardService  Service to handle card-related operations
     * @return Response JSON response containing the array of followed and transacted cards or error message
     */
    public function getAllCards(string $locale, Request $request, CardService $cardService): Response
    {
        // Authenticate the member using 'member_api' guard
        $member = $request->user('member_api');

        // Get the authenticated member's ID
        $memberId = $member->id;

        // Use the CardService to fetch all active cards followed and transacted by the member
        $followedCards = $cardService->findActiveCardsFollowedByMember($memberId, $hideColumnsForPublic = true);
        $cardsWithTransactions = $cardService->findActiveCardsWithMemberTransactions($memberId, $hideColumnsForPublic = true);

        // Add the balance to all followed cards
        $followedCards->each(function ($card) use ($member) {
            $balance = $card->getMemberBalance($member);
            $card->setAttribute('balance', $balance);
        });

        // Add the balance to all cards with transactions
        $cardsWithTransactions->each(function ($card) use ($member) {
            $balance = $card->getMemberBalance($member);
            $card->setAttribute('balance', $balance);
        });

        // Merge and sort the followed cards and cards with transactions
        $cards = $followedCards->concat($cardsWithTransactions)->sortByDesc(function ($card) {
            return [$card->getAttribute('balance'), $card->issue_date];
        });

        // Return the sorted array of followed and transacted cards in a JSON response
        return response()->json($cards);
    }

    /**
     * Retrieve all active cards followed by the authenticated member.
     *
     * @OA\Get(
     *     path="/{locale}/v1/member/followed-cards",
     *     operationId="getMemberFollowedCards",
     *     tags={"Member"},
     *     summary="Retrieve all active cards followed by the member",
     *     description="Retrieve all active cards that the authenticated member follows.",
     *     security={{"member_auth_token": {}}},
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="path",
     *         description="Locale setting (e.g., `en-us`)",
     *         required=true,
     *
     *         @OA\Schema(type="string", default="en-us")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation, returns the array of followed cards",
     *
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Card"))
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access, invalid or missing member token",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")
     *     )
     * )
     *
     * @param  string  $locale  Locale setting (e.g., 'en-us')
     * @param  Request  $request  The incoming HTTP request
     * @param  CardService  $cardService  Service to handle card-related operations
     * @return Response JSON response containing the array of followed cards or error message
     */
    public function getFollowedCards(string $locale, Request $request, CardService $cardService): Response
    {
        // Authenticate the member using 'member_api' guard
        $member = $request->user('member_api');

        // Get the authenticated member's ID
        $memberId = $member->id;

        // Use the CardService to fetch all active cards followed by the member
        $followedCards = $cardService->findActiveCardsFollowedByMember($memberId, $hideColumnsForPublic = true);

        // Add the balance to all cards
        $followedCards->each(function ($card) use ($member) {
            $balance = $card->getMemberBalance($member);
            $card->setAttribute('balance', $balance);
        });

        // Sort the followed cards by member balance and issue date in descending order
        $cards = $followedCards->sortByDesc(function ($card) {
            return [$card->getAttribute('balance'), $card->issue_date];
        });

        // Return the sorted array of followed cards in a JSON response
        return response()->json($cards);
    }

    /**
     * Retrieve all active cards the authenticated member has transacted with.
     *
     * @OA\Get(
     *     path="/{locale}/v1/member/transacted-cards",
     *     operationId="getMemberTransactedCards",
     *     tags={"Member"},
     *     summary="Retrieve all active cards transacted by the member",
     *     description="Retrieve all active cards that the authenticated member has transacted with.",
     *     security={{"member_auth_token": {}}},
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="path",
     *         description="Locale setting (e.g., `en-us`)",
     *         required=true,
     *
     *         @OA\Schema(type="string", default="en-us")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation, returns the array of cards with member's transactions",
     *
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Card"))
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access, invalid or missing member token",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")
     *     )
     * )
     *
     * @param  string  $locale  Locale setting (e.g., 'en-us')
     * @param  Request  $request  The incoming HTTP request
     * @param  CardService  $cardService  Service to handle card-related operations
     * @return Response JSON response containing the array of transacted cards or error message
     */
    public function getTransactedCards(string $locale, Request $request, CardService $cardService): Response
    {
        // Authenticate the member using 'member_api' guard
        $member = $request->user('member_api');

        // Get the authenticated member's ID
        $memberId = $member->id;

        // Use the CardService to fetch all active cards the member has transacted with
        $cardsWithTransactions = $cardService->findActiveCardsWithMemberTransactions($memberId, $hideColumnsForPublic = true);

        // Add the balance to all cards
        $cardsWithTransactions->each(function ($card) use ($member) {
            $balance = $card->getMemberBalance($member);
            $card->setAttribute('balance', $balance);
        });

        // Sort the cards by member balance and issue date in descending order
        $cards = $cardsWithTransactions->sortByDesc(function ($card) {
            return [$card->getAttribute('balance'), $card->issue_date];
        });

        // Return the sorted array of transacted cards in a JSON response
        return response()->json($cards);
    }

    /**
     * Retrieve the balance of the authenticated member for a specific card.
     *
     * @OA\Get(
     *     path="/{locale}/v1/member/balance/{cardId}",
     *     operationId="getMemberBalance",
     *     tags={"Member"},
     *     summary="Retrieve member's balance for a specific card",
     *     description="Retrieve the balance of the authenticated member for a specific card.",
     *     security={{"member_auth_token": {}}},
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="path",
     *         description="Locale setting (e.g., `en-us`)",
     *         required=true,
     *
     *         @OA\Schema(type="string", default="en-us")
     *     ),
     *
     *     @OA\Parameter(
     *         name="cardId",
     *         in="path",
     *         description="ID of the card",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation, returns the member's balance for the specific card",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="balance", type="integer", example=0, description="The member's balance for the specific card")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access, invalid or missing member token",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")
     *     )
     * )
     *
     * @param  string  $locale  Locale setting (e.g., 'en-us')
     * @param  string  $cardId  ID of the card
     * @param  Request  $request  The incoming HTTP request
     * @param  CardService  $cardService  Service to handle card-related operations
     * @return Response JSON response containing the member's balance for the specific card or error message
     */
    public function getMemberBalance(string $locale, string $cardId, Request $request, CardService $cardService): Response
    {
        // Authenticate the member using 'member_api' guard
        $member = $request->user('member_api');

        // Use the CardService to find the active card by ID
        $card = $cardService->findActiveCard($cardId);

        if (! $card) {
            // Return an error response if the card is not found
            return response()->json(['error' => 'Card not found'], 404);
        }

        // Get the member's balance for the specific card
        $balance = $card->getMemberBalance($member);

        // Return the member's balance in a JSON response
        return response()->json(compact('balance'));
    }
}
