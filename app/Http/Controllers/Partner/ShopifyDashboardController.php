<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Shopify Integration Dashboard Controller
 *
 * Partner-facing dashboard for managing Shopify integrations. Provides a unified
 * interface for configuration, monitoring, and control of the loyalty widget
 * embedded in Shopify storefronts.
 *
 * Key Features:
 * - Widget embed snippet generation (server-side, no metafields dependency)
 * - Connection lifecycle controls (pause/resume/disconnect)
 * - Settings management via SettingsService
 * - Reward mapping configuration
 * - Recent webhook activity monitoring
 *
 * All actions are audit-logged via ActivityLogService.
 */

namespace App\Http\Controllers\Partner;

use App\Enums\IntegrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Club;
use App\Models\ClubIntegration;
use App\Services\ActivityLogService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ShopifyDashboardController extends Controller
{
    /**
     * Shopify setting keys that can be updated via the settings form.
     *
     * @var array<string>
     */
    private const EDITABLE_SETTINGS = [
        'integrations.shopify.points_use_card_rules',
        'integrations.shopify.points_per_currency_fallback',
        'integrations.shopify.points_rounding_fallback',
        'integrations.shopify.deduct_on_refund',
        'integrations.shopify.first_order_bonus',
        'integrations.shopify.use_automatic_discounts',
        'integrations.shopify.widget.program_name',
        'integrations.shopify.widget.primary_color',
        'integrations.shopify.widget.mode',
        'integrations.shopify.widget.position',
    ];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly ActivityLogService $activityLog
    ) {}

    /**
     * Display the Shopify integration dashboard.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $partner = auth('partner')->user();
        $clubId = $request->query('club_id');

        // Get partner's clubs
        $clubs = Club::where('created_by', $partner->id)->orderBy('name')->get();

        if ($clubs->isEmpty()) {
            return redirect()
                ->route('partner.data.list', ['name' => 'clubs'])
                ->with('toast', [
                    'type' => 'info',
                    'text' => trans('common.create_club_first'),
                ]);
        }

        // Select club (default to first if not specified)
        $club = $clubId
            ? $clubs->firstWhere('id', $clubId)
            : $clubs->first();

        if (! $club) {
            $club = $clubs->first();
        }

        // Find existing Shopify integration for this club
        $integration = ClubIntegration::query()
            ->forClub($club)
            ->shopify()
            ->first();

        // Get active loyalty cards for this club (for card rule selection)
        $availableCards = Card::where('club_id', $club->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'points_per_currency', 'currency']);

        // Get recent webhook receipts (last 10)
        $recentReceipts = $integration
            ? $integration->webhookReceipts()
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
            : collect();

        // Build widget embed snippet (server-side generation)
        $widgetSnippet = $integration && $integration->status === IntegrationStatus::ACTIVE
            ? $this->generateWidgetSnippet($integration)
            : null;

        // Get current settings for the form (includes per-integration card_id)
        $currentSettings = $this->getCurrentSettings($integration);

        return view('partner.integrations.shopify.index', [
            'clubs' => $clubs,
            'club' => $club,
            'integration' => $integration,
            'availableCards' => $availableCards,
            'recentReceipts' => $recentReceipts,
            'widgetSnippet' => $widgetSnippet,
            'currentSettings' => $currentSettings,
        ]);
    }

    /**
     * Update global Shopify settings via SettingsService.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $partner = auth('partner')->user();

        $validated = $request->validate([
            'integration_id' => ['required', 'uuid'],
            'settings' => ['required', 'array'],
            'settings.card_id' => ['required', 'uuid'],
            'settings.widget_program_name' => ['required', 'string', 'max:50'],
            'settings.widget_primary_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'settings.widget_mode' => ['required', 'in:auto,light,dark'],
            'settings.widget_position' => ['required', 'in:bottom-right,bottom-left,top-right,top-left'],
        ]);

        $settings = $validated['settings'];

        // Verify integration belongs to partner
        $integration = ClubIntegration::query()
            ->whereHas('club', fn ($q) => $q->where('created_by', $partner->id))
            ->findOrFail($validated['integration_id']);

        // Validate card belongs to the integration's club
        $cardExists = Card::where('id', $settings['card_id'])
            ->where('club_id', $integration->club_id)
            ->where('is_active', true)
            ->exists();

        if (! $cardExists) {
            return back()->withErrors(['settings.card_id' => trans('common.invalid_card_selection')]);
        }

        // Update integration settings JSON with card_id
        $integrationSettings = $integration->settings ?? [];
        $integrationSettings['card_id'] = $settings['card_id'];
        $integration->update(['settings' => $integrationSettings]);

        // Save widget settings to SettingsService (global defaults)
        $this->settings->set('integrations.shopify.widget.program_name', $settings['widget_program_name']);
        $this->settings->set('integrations.shopify.widget.primary_color', $settings['widget_primary_color']);
        $this->settings->set('integrations.shopify.widget.mode', $settings['widget_mode']);
        $this->settings->set('integrations.shopify.widget.position', $settings['widget_position']);

        // Audit log
        $this->activityLog->log(
            description: 'Shopify integration settings updated',
            subject: $partner,
            event: 'integration.shopify.settings_updated',
            properties: ['settings_updated' => array_keys($settings)]
        );

        return back()->with('toast', [
            'type' => 'success',
            'text' => trans('common.settings_saved'),
        ]);
    }

    /**
     * Pause an active integration.
     */
    public function pause(string $locale, string $integrationId): RedirectResponse
    {
        $partner = auth('partner')->user();

        $integration = ClubIntegration::query()
            ->whereHas('club', fn ($q) => $q->where('created_by', $partner->id))
            ->findOrFail($integrationId);

        if ($integration->status !== IntegrationStatus::ACTIVE) {
            return back()->with('toast', [
                'type' => 'error',
                'text' => trans('common.integration_not_active'),
            ]);
        }

        $integration->markPaused();

        $this->activityLog->log(
            description: 'Shopify integration paused',
            subject: $integration,
            event: 'integration.shopify.paused',
            properties: ['integration_id' => $integration->id]
        );

        return back()->with('toast', [
            'type' => 'success',
            'text' => trans('common.integration_paused'),
        ]);
    }

    /**
     * Resume a paused integration.
     */
    public function resume(string $locale, string $integrationId): RedirectResponse
    {
        $partner = auth('partner')->user();

        $integration = ClubIntegration::query()
            ->whereHas('club', fn ($q) => $q->where('created_by', $partner->id))
            ->findOrFail($integrationId);

        if (! in_array($integration->status, [IntegrationStatus::PAUSED, IntegrationStatus::ERROR], true)) {
            return back()->with('toast', [
                'type' => 'error',
                'text' => trans('common.integration_cannot_resume'),
            ]);
        }

        $integration->markActive();

        $this->activityLog->log(
            description: 'Shopify integration resumed',
            subject: $integration,
            event: 'integration.shopify.resumed',
            properties: ['integration_id' => $integration->id]
        );

        return back()->with('toast', [
            'type' => 'success',
            'text' => trans('common.integration_resumed'),
        ]);
    }

    /**
     * Disconnect an integration (soft delete credentials).
     */
    public function disconnect(string $locale, Request $request, string $integrationId): RedirectResponse
    {
        $partner = auth('partner')->user();

        $integration = ClubIntegration::query()
            ->whereHas('club', fn ($q) => $q->where('created_by', $partner->id))
            ->findOrFail($integrationId);

        // Confirm action
        if ($request->input('confirm') !== 'DISCONNECT') {
            return back()->with('toast', [
                'type' => 'error',
                'text' => trans('common.please_confirm_disconnect'),
            ]);
        }

        // Store info for logging before deletion
        $integrationId = $integration->id;
        $storeIdentifier = $integration->store_identifier;

        // Clear sensitive data and soft-delete
        $integration->access_token = null;
        $integration->webhook_secret = null;
        $integration->save();
        $integration->delete(); // Soft delete

        $this->activityLog->log(
            description: 'Shopify integration disconnected',
            subject: $integration,
            event: 'integration.shopify.disconnected',
            properties: [
                'integration_id' => $integrationId,
                'store_identifier' => $storeIdentifier,
            ]
        );

        Log::info('[Shopify] Integration disconnected by partner', [
            'integration_id' => $integrationId,
            'partner_id' => $partner->id,
        ]);

        return redirect()
            ->route('partner.integrations.shopify')
            ->with('toast', [
                'type' => 'success',
                'text' => trans('common.integration_disconnected'),
            ]);
    }

    /**
     * Generate the widget embed snippet for Shopify theme.
     *
     * Generates a compact, single-line snippet that can be pasted into
     * theme.liquid before </body>. No Shopify metafields dependency in Phase 1.
     */
    private function generateWidgetSnippet(ClubIntegration $integration): array
    {
        $appUrl = rtrim((string) config('integrations.shopify.app_url', ''), '/');
        $version = config('app.version', app()->version());

        // Build config JSON (compact, single-line)
        $config = [
            'integrationId' => $integration->id,
            'apiKey' => $integration->public_api_key,
            'apiBase' => $appUrl.'/api/widget',
        ];

        // JSON encode without pretty-print
        $configJson = json_encode($config, JSON_UNESCAPED_SLASHES);

        // Build the full snippet
        $snippet = <<<HTML
<!-- Reward Loyalty Widget v{$version} -->
<script>window.RewardLoyaltyConfig={$configJson};window.RewardLoyaltyConfig.customerId={{customer.id|default:null}};window.RewardLoyaltyConfig.customerEmail='{{customer.email|default:""}}';</script>
<script src="{$appUrl}/widget/rewards.js?v={$version}" defer></script>
<link rel="stylesheet" href="{$appUrl}/widget/rewards.css?v={$version}">
HTML;

        return [
            'snippet' => $snippet,
            'appUrl' => $appUrl,
            'version' => $version,
            'apiKey' => $integration->public_api_key,
        ];
    }

    /**
     * Get current settings values for the form.
     */
    /**
     * Get current settings values for the form.
     *
     * Global settings come from SettingsService.
     * Per-integration settings (like card_id) come from the integration's settings JSON.
     */
    private function getCurrentSettings(?ClubIntegration $integration = null): array
    {
        return [
            // Per-integration setting: which card's rules to use (stored in integration settings JSON)
            'card_id' => $integration?->settings['card_id'] ?? null,

            // Global settings from SettingsService
            'points_use_card_rules' => (bool) $this->settings->get('integrations.shopify.points_use_card_rules', true),
            'points_per_currency_fallback' => (int) $this->settings->get('integrations.shopify.points_per_currency_fallback', 10),
            'points_rounding_fallback' => (string) $this->settings->get('integrations.shopify.points_rounding_fallback', 'down'),
            'deduct_on_refund' => (bool) $this->settings->get('integrations.shopify.deduct_on_refund', true),
            'first_order_bonus' => (int) $this->settings->get('integrations.shopify.first_order_bonus', 0),
            'use_automatic_discounts' => (bool) $this->settings->get('integrations.shopify.use_automatic_discounts', true),
            'widget_program_name' => (string) $this->settings->get('integrations.shopify.widget.program_name', 'Rewards'),
            'widget_primary_color' => (string) $this->settings->get('integrations.shopify.widget.primary_color', '#F59E0B'),
            'widget_mode' => (string) $this->settings->get('integrations.shopify.widget.mode', 'auto'),
            'widget_position' => (string) $this->settings->get('integrations.shopify.widget.position', 'bottom-right'),
        ];
    }
}
