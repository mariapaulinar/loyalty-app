<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Shopify OAuth Controller
 *
 * Web endpoints for connecting a Shopify store to a club.
 *
 * Routes:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - install: partner-auth (initiated from dashboard)
 * - callback: PUBLIC (Shopify redirects the browser here)
 *
 * The callback route MUST be public.
 * Security is enforced by:
 * - HMAC validation (Shopify signature)
 * - Encrypted state parameter (club_id, shop, ts, nonce)
 * - Single-use nonce stored in cache (10 minutes)
 *
 * Demo Mode:
 * ─────────────────────────────────────────────────────────────────────────────────
 * When APP_DEMO=true, the OAuth flow is simulated:
 * - No actual Shopify credentials required
 * - Creates a demo integration with fake access token
 * - Allows testing the UI and flow without real Shopify store
 *
 * @see App\Services\Integration\Shopify\OAuthService
 */

namespace App\Http\Controllers\Integration\Shopify;

use App\Enums\IntegrationPlatform;
use App\Enums\IntegrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubIntegration;
use App\Services\ActivityLogService;
use App\Services\Integration\Shopify\OAuthService;
use App\Services\Integration\Shopify\ShopifyOAuthException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShopifyOAuthController extends Controller
{
    public function __construct(
        private readonly OAuthService $oauth,
        private readonly ActivityLogService $activityLog
    ) {}

    /**
     * Redirect a partner to Shopify's OAuth install URL.
     *
     * Expected query params:
     * - club_id
     * - shop (mystore or mystore.myshopify.com)
     *
     * In demo mode (APP_DEMO=true), creates a simulated integration
     * without requiring actual Shopify credentials.
     */
    public function install(string $locale, Request $request): RedirectResponse
    {
        $partner = auth('partner')->user();

        $validated = $request->validate([
            'club_id' => ['required', 'string'],
            'card_id' => ['required', 'string'],
            'shop' => ['required', 'string', 'regex:/^[a-zA-Z0-9\-]+(\\.myshopify\\.com)?$/'],
        ]);

        $club = Club::query()->findOrFail($validated['club_id']);

        if (! $partner || $club->created_by !== $partner->id) {
            abort(403, 'This club does not belong to your account');
        }

        // Normalize shop domain: append .myshopify.com if not present
        $shop = $validated['shop'];
        if (! str_ends_with($shop, '.myshopify.com')) {
            $shop = $shop.'.myshopify.com';
        }

        // ─────────────────────────────────────────────────────────────────────────
        // DEMO MODE: Simulate OAuth flow without real Shopify credentials
        // ─────────────────────────────────────────────────────────────────────────
        if (config('default.app_demo')) {
            $integration = $this->createDemoIntegration($club, $shop, $validated['card_id']);

            $this->activityLog->log(
                description: 'Shopify demo connection created',
                subject: $integration,
                event: 'integration.shopify.demo.connected',
                properties: [
                    'club_id' => $club->id,
                    'card_id' => $validated['card_id'],
                    'shop' => $shop,
                    'demo_mode' => true,
                    'ip' => $request->ip(),
                ],
                logName: 'integration'
            );

            return redirect()
                ->route('partner.integrations.shopify')
                ->with('toast', [
                    'type' => 'success',
                    'text' => trans('common.shopify_demo_connected'),
                ]);
        }

        // ─────────────────────────────────────────────────────────────────────────
        // PRODUCTION: Real OAuth flow
        // ─────────────────────────────────────────────────────────────────────────
        $install = $this->oauth->generateInstallUrl($club, $shop);

        $this->activityLog->log(
            description: 'Shopify install initiated',
            subject: $club,
            event: 'integration.shopify.install.initiated',
            properties: [
                'club_id' => $club->id,
                'shop' => $shop,
                'ip' => $request->ip(),
            ],
            logName: 'integration'
        );

        return redirect()->away($install['url']);
    }

    /**
     * Create a demo integration for testing purposes.
     *
     * This creates a fully functional ClubIntegration record with
     * simulated credentials. Useful for:
     * - Testing the UI without real Shopify store
     * - Demonstrating features to potential customers
     * - Development without OAuth credentials
     */
    private function createDemoIntegration(Club $club, string $shop, string $cardId): ClubIntegration
    {
        // Force delete any existing Shopify integrations for this club to avoid unique constraint violations
        // This ensures clean state when reconnecting in demo mode
        ClubIntegration::withTrashed()
            ->where('club_id', $club->id)
            ->where('platform', IntegrationPlatform::SHOPIFY)
            ->forceDelete();

        // Create fresh demo integration
        return ClubIntegration::create([
            'club_id' => $club->id,
            'platform' => IntegrationPlatform::SHOPIFY,
            'status' => IntegrationStatus::ACTIVE,
            'store_identifier' => $shop,
            'access_token' => 'demo_token_'.Str::random(32),
            'settings' => [
                'card_id' => $cardId,
                'demo_mode' => true,
                'connected_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Public OAuth callback endpoint.
     *
     * Shopify redirects the user's browser here after authorization.
     */
    public function callback(Request $request, ?string $locale = null): RedirectResponse
    {
        $targetLocale = $locale ?? config('app.locale', 'en-us');

        try {
            $integration = $this->oauth->handleCallback($request->query());

            return redirect()
                ->route('partner.index', ['locale' => $targetLocale])
                ->with('toast', [
                    'type' => 'success',
                    'text' => 'Shopify store connected successfully.',
                ])
                ->with('shopify_integration_id', $integration->id);
        } catch (ShopifyOAuthException $e) {
            return redirect()
                ->route('partner.index', ['locale' => $targetLocale])
                ->with('toast', [
                    'type' => 'error',
                    'text' => $e->getUserMessage(),
                ]);
        }
    }
}
