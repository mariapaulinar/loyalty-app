<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API: Card/Stamp Card/Voucher discovery for members.
 *
 * Members discover cards in two ways:
 * 1. Homepage browsing — cards with is_visible_by_default = true
 * 2. QR code / shared link — any active card via its URL or identifier
 *
 * This controller handles both scenarios:
 * - GET /discover            → Browse homepage-visible cards (all types)
 * - POST /discover/resolve   → Resolve a URL or identifier to a card
 * - POST /discover/follow    → Save a card to "My Cards" (follow)
 * - POST /discover/unfollow  → Remove a card from "My Cards" (unfollow)
 *
 * URL patterns the resolve endpoint accepts:
 * - https://example.com/en-us/card/{uuid}
 * - https://example.com/en-us/stamp-card/{uuid}
 * - https://example.com/en-us/voucher/{uuid}
 * - https://example.com/en-us/follow/{uuid}
 * - Raw UUID (card ID)
 * - Unique identifier (e.g. "344-319-665-971")
 *
 * Scopes:
 *   read           → GET /discover, POST /discover/resolve
 *   write:profile  → POST /discover/follow, POST /discover/unfollow
 *
 * @see CardService::findActiveCard()
 * @see CardService::findActiveCardsVisibleByDefault()
 */

namespace App\Http\Controllers\Api\Agent\Member;

use App\Http\Controllers\Api\Agent\BaseAgentController;
use App\Http\Controllers\Api\Agent\Concerns\EnforcesMemberGates;
use App\Models\Card;
use App\Models\StampCard;
use App\Models\Voucher;
use App\Services\Card\CardService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class AgentDiscoverController extends BaseAgentController
{
    use EnforcesMemberGates;

    public function __construct(
        private CardService $cardService,
    ) {}

    #[OA\Get(
        path: '/member/discover',
        operationId: 'member_discover',
        summary: 'Browse discoverable loyalty cards, stamp cards, and vouchers',
        description: 'Returns all cards visible on the homepage. Includes loyalty cards, stamp cards, and vouchers that are active and visible by default.',
        security: [['AgentKey' => []]],
        tags: ['Member / Discover'],
        responses: [
            new OA\Response(response: 200, description: 'Discoverable cards grouped by type', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'cards', type: 'array', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'stamp_cards', type: 'array', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'vouchers', type: 'array', items: new OA\Items(type: 'object')),
                ], type: 'object'),
            ])),
        ],
        x: ['agent-scopes' => ['read']],
    )]
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read')) {
            return $denied;
        }

        $member = $this->getMember($request);
        // Loyalty cards visible on homepage
        $cards = $this->cardService->queryDiscoverableCards(visibleOnly: true)
            ->orderBy('issue_date', 'desc')
            ->get()
            ->map(fn (Card $card) => $this->serializeDiscoverableCard($card, $member));

        // Stamp cards visible on homepage
        $stampCards = $this->queryDiscoverableStampCards(visibleOnly: true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (StampCard $sc) => $this->serializeDiscoverableStampCard($sc, $member));

        // Vouchers visible on homepage
        $vouchers = $this->queryDiscoverableVouchers(visibleOnly: true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Voucher $v) => $this->serializeDiscoverableVoucher($v));

        return $this->jsonSuccess([
            'data' => [
                'cards' => $cards,
                'stamp_cards' => $stampCards,
                'vouchers' => $vouchers,
            ],
        ]);
    }

    #[OA\Post(
        path: '/member/discover/resolve',
        operationId: 'member_resolve_card',
        summary: 'Resolve a card URL or identifier to its details',
        description: 'Accepts a QR code URL, UUID, or unique identifier and returns the corresponding card details. Primary endpoint for QR code scanning.',
        security: [['AgentKey' => []]],
        tags: ['Member / Discover'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'url', type: 'string', description: 'Full URL from a QR code or shared link'),
                new OA\Property(property: 'identifier', type: 'string', description: 'UUID or unique identifier (alternative to url)'),
            ]),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Resolved card details', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 404, description: 'Card not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
            new OA\Response(response: 422, description: 'Missing input', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['read']],
    )]
    public function resolve(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'read')) {
            return $denied;
        }

        $input = $request->input('url') ?? $request->input('identifier');

        if (! $input || ! is_string($input)) {
            return $this->jsonError(
                code: 'MISSING_INPUT',
                message: 'Provide a "url" or "identifier" parameter.',
                status: 422,
                retryStrategy: 'fix_request',
            );
        }

        $member = $this->getMember($request);

        // Try to extract type and ID from a URL
        $parsed = $this->parseCardInput($input);

        if (! $parsed) {
            return $this->jsonError(
                code: 'UNRESOLVABLE_INPUT',
                message: 'Could not resolve the provided URL or identifier to a card. Supported formats: card URL, stamp-card URL, voucher URL, UUID, or unique identifier.',
                status: 404,
                retryStrategy: 'fix_request',
            );
        }

        $result = match ($parsed['type']) {
            'card' => $this->resolveCard($parsed['id'], $member),
            'stamp-card' => $this->resolveStampCard($parsed['id'], $member),
            'voucher' => $this->resolveVoucher($parsed['id']),
            default => null,
        };

        if (! $result) {
            return $this->jsonNotFound('Card');
        }

        return $this->jsonSuccess(['data' => $result]);
    }

    #[OA\Post(
        path: '/member/discover/follow',
        operationId: 'member_follow_card',
        summary: 'Save a card to My Cards (follow)',
        description: 'Saves a loyalty card to the member\'s collection. Idempotent — following an already-followed card is a no-op.',
        security: [['AgentKey' => []]],
        tags: ['Member / Discover'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['card_id'],
                properties: [
                    new OA\Property(property: 'card_id', type: 'string', format: 'uuid'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Card followed', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 404, description: 'Card not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:profile']],
    )]
    public function follow(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:profile')) {
            return $denied;
        }

        $cardId = $request->input('card_id');
        if (! $cardId) {
            return $this->jsonError(
                code: 'MISSING_CARD_ID',
                message: 'Provide a "card_id" parameter.',
                status: 422,
                retryStrategy: 'fix_request',
            );
        }

        $member = $this->getMember($request);

        $card = $this->cardService->findDiscoverableCard($cardId);
        if (! $card) {
            return $this->jsonNotFound('Card');
        }

        // Follow via card_member pivot (syncWithoutDetaching = idempotent)
        $card->members()->syncWithoutDetaching([$member->id]);

        return $this->jsonSuccess([
            'data' => [
                'status' => 'followed',
                'card_id' => $card->id,
                'card_title' => $card->title,
                'message' => 'Card saved to your collection.',
            ],
        ]);
    }

    #[OA\Post(
        path: '/member/discover/unfollow',
        operationId: 'member_unfollow_card',
        summary: 'Remove a card from My Cards (unfollow)',
        description: 'Removes a loyalty card from the member\'s collection.',
        security: [['AgentKey' => []]],
        tags: ['Member / Discover'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['card_id'],
                properties: [
                    new OA\Property(property: 'card_id', type: 'string', format: 'uuid'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Card unfollowed', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'error', type: 'boolean', example: false),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 404, description: 'Card not found', content: new OA\JsonContent(ref: '#/components/schemas/AgentError')),
        ],
        x: ['agent-scopes' => ['write:profile']],
    )]
    public function unfollow(Request $request): JsonResponse
    {
        if ($denied = $this->requireScope($request, 'write:profile')) {
            return $denied;
        }

        $cardId = $request->input('card_id');
        if (! $cardId) {
            return $this->jsonError(
                code: 'MISSING_CARD_ID',
                message: 'Provide a "card_id" parameter.',
                status: 422,
                retryStrategy: 'fix_request',
            );
        }

        $member = $this->getMember($request);

        $card = $this->cardService->findDiscoverableCard($cardId);
        if (! $card) {
            return $this->jsonNotFound('Card');
        }

        $card->members()->detach($member->id);

        return $this->jsonSuccess([
            'data' => [
                'status' => 'unfollowed',
                'card_id' => $card->id,
                'card_title' => $card->title,
                'message' => 'Card removed from your collection.',
            ],
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // INPUT PARSING
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Parse a card URL, UUID, or unique identifier into a type + ID pair.
     *
     * Supports:
     * - Full URL: https://example.com/{locale}/card/{id}
     * - Full URL: https://example.com/{locale}/stamp-card/{id}
     * - Full URL: https://example.com/{locale}/voucher/{id}
     * - Full URL: https://example.com/{locale}/follow/{id} (follow links)
     * - Raw UUID: 019cc3a5-51cb-7315-97e1-69147399f94d
     * - Unique identifier: 344-319-665-971
     *
     * @return array{type: string, id: string}|null
     */
    private function parseCardInput(string $input): ?array
    {
        $input = trim($input);

        // 1. Try URL pattern matching
        if (filter_var($input, FILTER_VALIDATE_URL) || Str::startsWith($input, ['http://', 'https://'])) {
            $path = parse_url($input, PHP_URL_PATH) ?? '';
            $segments = array_values(array_filter(explode('/', $path)));

            // URL patterns: /{locale}/card/{id}, /{locale}/stamp-card/{id}, /{locale}/voucher/{id}, /{locale}/follow/{id}
            foreach ($segments as $i => $segment) {
                if ($segment === 'card' && isset($segments[$i + 1])) {
                    return ['type' => 'card', 'id' => $segments[$i + 1]];
                }
                if ($segment === 'follow' && isset($segments[$i + 1])) {
                    return ['type' => 'card', 'id' => $segments[$i + 1]];
                }
                if ($segment === 'stamp-card' && isset($segments[$i + 1])) {
                    return ['type' => 'stamp-card', 'id' => $segments[$i + 1]];
                }
                if ($segment === 'voucher' && isset($segments[$i + 1])) {
                    return ['type' => 'voucher', 'id' => $segments[$i + 1]];
                }
            }

            return null;
        }

        // 2. Try UUID format (36 chars with dashes)
        if (Str::isUuid($input)) {
            return $this->resolveIdToType($input);
        }

        // 3. Try unique_identifier format (e.g. 344-319-665-971)
        if (preg_match('/^\d{3}-\d{3}-\d{3}-\d{3}$/', $input)) {
            return $this->resolveUniqueIdentifierToType($input);
        }

        // 4. Fallback: try as-is (could be a short ID or partial)
        return $this->resolveIdToType($input);
    }

    /**
     * Determine the type of a resource by its UUID.
     */
    private function resolveIdToType(string $id): ?array
    {
        if ($this->cardService->queryDiscoverableCards()->where('id', $id)->exists()) {
            return ['type' => 'card', 'id' => $id];
        }
        if ($this->queryDiscoverableStampCards()->where('id', $id)->exists()) {
            return ['type' => 'stamp-card', 'id' => $id];
        }
        if ($this->queryDiscoverableVouchers()->where('id', $id)->exists()) {
            return ['type' => 'voucher', 'id' => $id];
        }

        return null;
    }

    /**
     * Determine the type of a resource by its unique_identifier.
     */
    private function resolveUniqueIdentifierToType(string $identifier): ?array
    {
        $card = $this->cardService->queryDiscoverableCards()
            ->where('unique_identifier', $identifier)
            ->first();
        if ($card) {
            return ['type' => 'card', 'id' => $card->id];
        }

        $stampCard = $this->queryDiscoverableStampCards()
            ->where('unique_identifier', $identifier)
            ->first();
        if ($stampCard) {
            return ['type' => 'stamp-card', 'id' => $stampCard->id];
        }

        $voucher = $this->queryDiscoverableVouchers()
            ->where('unique_identifier', $identifier)
            ->first();
        if ($voucher) {
            return ['type' => 'voucher', 'id' => $voucher->id];
        }

        return null;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // RESOLVERS
    // ═════════════════════════════════════════════════════════════════════════

    private function resolveCard(string $id, $member): ?array
    {
        $card = $this->cardService->findDiscoverableCard($id);
        if (! $card) {
            return null;
        }

        return $this->serializeDiscoverableCard($card, $member);
    }

    private function resolveStampCard(string $id, $member): ?array
    {
        $stampCard = $this->queryDiscoverableStampCards()
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('unique_identifier', $id);
            })
            ->first();

        if (! $stampCard) {
            return null;
        }

        return $this->serializeDiscoverableStampCard($stampCard, $member);
    }

    private function resolveVoucher(string $id): ?array
    {
        $voucher = $this->queryDiscoverableVouchers()
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('unique_identifier', $id);
            })
            ->first();

        if (! $voucher) {
            return null;
        }

        return $this->serializeDiscoverableVoucher($voucher);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // MEMBER-SAFE SERIALIZERS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Serialize a loyalty card for discovery.
     * Only member-relevant display fields + enrollment status.
     */
    private function serializeDiscoverableCard(Card $card, $member): array
    {
        $isFollowing = $card->members()->where('members.id', $member->id)->exists();

        return [
            'type' => 'loyalty_card',
            'id' => $card->id,
            'name' => $card->name,
            'title' => $card->title,
            'description' => $card->description ?? null,
            'currency' => $card->currency ?? 'points',
            'bg_color' => $card->bg_color,
            'text_color' => $card->text_color,
            'is_following' => $isFollowing,
            'balance' => $isFollowing ? $card->getMemberBalance($member) : null,
            'rewards_count' => $card->rewards()->where('is_active', true)->count(),
        ];
    }

    /**
     * Serialize a stamp card for discovery.
     */
    private function serializeDiscoverableStampCard(StampCard $sc, $member): array
    {
        // Check if member is enrolled in this stamp card
        $enrollment = $sc->enrollments()
            ->where('member_id', $member->id)
            ->first();

        return [
            'type' => 'stamp_card',
            'id' => $sc->id,
            'title' => $sc->title,
            'description' => $sc->description,
            'stamps_required' => $sc->stamps_required,
            'stamp_icon' => $sc->stamp_icon,
            'reward_title' => $sc->reward_title,
            'reward_description' => $sc->reward_description,
            'bg_color' => $sc->bg_color,
            'text_color' => $sc->text_color,
            'is_enrolled' => $enrollment !== null,
            'current_stamps' => $enrollment?->current_stamps ?? null,
            'completed_count' => $enrollment?->completed_count ?? null,
        ];
    }

    /**
     * Serialize a voucher for discovery.
     */
    private function serializeDiscoverableVoucher(Voucher $v): array
    {
        return [
            'type' => 'voucher',
            'id' => $v->id,
            'title' => $v->title,
            'description' => $v->description,
            'code' => $v->code,
            'voucher_type' => $v->type,
            'value' => $v->value,
            'currency' => $v->currency,
            'valid_from' => $v->valid_from,
            'valid_until' => $v->valid_until,
            'bg_color' => $v->bg_color,
            'text_color' => $v->text_color,
        ];
    }

    private function queryDiscoverableStampCards(bool $visibleOnly = false): Builder
    {
        $query = $this->constrainToDiscoverableClubs(
            StampCard::query()
            ->active()
            ->currentlyValid()
        );

        if ($visibleOnly) {
            $query->visible();
        }

        return $query;
    }

    private function queryDiscoverableVouchers(bool $visibleOnly = false): Builder
    {
        $query = $this->constrainToDiscoverableClubs(
            Voucher::query()
                ->available()
        );

        if ($visibleOnly) {
            $query->where('is_visible_by_default', true)
                ->whereNull('batch_id');
        }

        return $query;
    }

    /**
     * Constrain member discovery to clubs that are live and owned by an active partner/network.
     *
     * Discovery should remain stable for older rows where `created_by` may be null
     * but the resource is still valid through its club ownership chain.
     */
    private function constrainToDiscoverableClubs(Builder $query, string $clubRelation = 'club'): Builder
    {
        return $query->whereHas($clubRelation, function (Builder $clubQuery) {
            $clubQuery->where('clubs.is_active', true)
                ->whereHas('partner', function (Builder $partnerQuery) {
                    $partnerQuery->where('partners.is_active', true)
                        ->where(function (Builder $nested) {
                            $nested->whereNull('network_id')
                                ->orWhereHas('network', fn (Builder $network) => $network->where('is_active', true));
                        });
                });
        });
    }
}
