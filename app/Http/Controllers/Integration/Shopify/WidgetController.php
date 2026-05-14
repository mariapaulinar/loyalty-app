<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Shopify Widget Controller
 *
 * Storefront-facing endpoints used by the embedded widget.
 *
 * Authentication Model:
 * ─────────────────────────────────────────────────────────────────────────────────
 * The widget is loaded on Shopify storefronts, so we cannot rely on session auth.
 * Instead, requests must include `X-API-Key`, which matches
 * `ClubIntegration.public_api_key`.
 *
 * This is intentionally simple and durable:
 * - Public key: safe to embed in storefront JS
 * - Server still enforces integration status + reward ownership
 *
 * @see App\Services\Integration\Shopify\WidgetService
 */

namespace App\Http\Controllers\Integration\Shopify;

use App\Http\Controllers\Controller;
use App\Models\ClubIntegration;
use App\Models\IntegrationReward;
use App\Models\Member;
use App\Services\Integration\Shopify\WidgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetController extends Controller
{
    public function __construct(
        private readonly WidgetService $widgetService
    ) {}

    /**
     * Return widget configuration for the storefront.
     */
    public function config(Request $request, string $integrationId): JsonResponse
    {
        $integration = $this->resolveIntegration($request, $integrationId);

        // Optional: allow a known member identifier (future auth layer can tighten this).
        $member = null;
        $memberIdentifier = $request->query('member_identifier');

        if (is_string($memberIdentifier) && $memberIdentifier !== '') {
            $member = Member::query()
                ->where('unique_identifier', $memberIdentifier)
                ->orWhere('id', $memberIdentifier)
                ->first();
        }

        return response()->json([
            'config' => $this->widgetService->getWidgetConfig($integration, $member),
        ]);
    }

    /**
     * Redeem a reward and return discount details.
     */
    public function redeem(Request $request, string $integrationId): JsonResponse
    {
        $integration = $this->resolveIntegration($request, $integrationId);

        $validated = $request->validate([
            'integration_reward_id' => ['required', 'string'],
            'member_identifier' => ['required', 'string'],
        ]);

        $integrationReward = IntegrationReward::query()
            ->where('id', $validated['integration_reward_id'])
            ->where('club_integration_id', $integration->id)
            ->with('reward')
            ->firstOrFail();

        $member = Member::query()
            ->where('unique_identifier', $validated['member_identifier'])
            ->orWhere('id', $validated['member_identifier'])
            ->firstOrFail();

        $result = $this->widgetService->redeemReward($integration, $member, $integrationReward);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Resolve and authenticate an integration via X-API-Key.
     */
    private function resolveIntegration(Request $request, string $integrationId): ClubIntegration
    {
        $apiKey = $request->header('X-API-Key');

        if (! is_string($apiKey) || $apiKey === '') {
            abort(401, 'Missing X-API-Key');
        }

        $integration = ClubIntegration::query()->findOrFail($integrationId);

        if (! hash_equals($integration->public_api_key, $apiKey)) {
            abort(401, 'Invalid API key');
        }

        if (! $integration->status->canProcess()) {
            abort(403, 'Integration is not active');
        }

        return $integration;
    }
}
