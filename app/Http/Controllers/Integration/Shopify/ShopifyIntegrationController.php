<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Shopify Integration Controller (Partner Management)
 *
 * Partner-authenticated endpoints to manage an existing Shopify integration:
 * - View integration status
 * - Update global Shopify settings (via SettingsService)
 * - Create/update reward mappings (IntegrationReward)
 * - Pause/resume/disconnect the integration
 *
 * Design Philosophy:
 * ─────────────────────────────────────────────────────────────────────────────────
 * Controllers orchestrate. Services decide.
 *
 * - Business logic belongs in services/models
 * - Controllers validate, authorize, persist, and audit
 *
 * @see App\Models\ClubIntegration
 * @see App\Models\IntegrationReward
 * @see App\Services\SettingsService
 */

namespace App\Http\Controllers\Integration\Shopify;

use App\Http\Controllers\Controller;
use App\Models\ClubIntegration;
use App\Models\IntegrationReward;
use App\Models\Reward;
use App\Services\ActivityLogService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ShopifyIntegrationController extends Controller
{
    /**
     * Keys we allow partners to update through this controller.
     *
     * These are the global defaults stored in SettingsService.
     */
    private const SHOPIFY_SETTING_KEYS = [
        'integrations.shopify.points_use_card_rules',
        'integrations.shopify.points_per_currency_fallback',
        'integrations.shopify.points_rounding_fallback',
        'integrations.shopify.award_on',
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
     * Show integration details and current global Shopify settings.
     */
    public function show(Request $request, string $integrationId): JsonResponse
    {
        $partner = $request->user('partner_api') ?? auth('partner')->user();

        $integration = ClubIntegration::query()
            ->with(['club'])
            ->findOrFail($integrationId);

        if (! $partner || $integration->club?->created_by !== $partner->id) {
            abort(403, 'This integration does not belong to your account');
        }

        $settings = [];
        foreach (self::SHOPIFY_SETTING_KEYS as $key) {
            $settings[$key] = $this->settings->get($key);
        }

        return response()->json([
            'integration' => [
                'id' => $integration->id,
                'club_id' => $integration->club_id,
                'platform' => $integration->platform,
                'status' => $integration->status->value,
                'store_identifier' => $integration->store_identifier,
                'last_sync_at' => $integration->last_sync_at?->toIso8601String(),
                'last_error' => $integration->last_error,
                'last_error_at' => $integration->last_error_at?->toIso8601String(),
                'created_at' => $integration->created_at?->toIso8601String(),
            ],
            'settings' => $settings,
        ]);
    }

    /**
     * Update global Shopify settings.
     */
    public function updateSettings(Request $request, string $integrationId): JsonResponse
    {
        $partner = $request->user('partner_api') ?? auth('partner')->user();

        $integration = ClubIntegration::query()->with('club')->findOrFail($integrationId);

        if (! $partner || $integration->club?->created_by !== $partner->id) {
            abort(403, 'This integration does not belong to your account');
        }

        $validated = $request->validate([
            'points_use_card_rules' => ['sometimes', 'boolean'],
            'points_per_currency_fallback' => ['sometimes', 'integer', 'min:0'],
            'points_rounding_fallback' => ['sometimes', 'string', 'in:down,nearest,up'],
            'award_on' => ['sometimes', 'string', 'in:order_paid'],
            'deduct_on_refund' => ['sometimes', 'boolean'],
            'first_order_bonus' => ['sometimes', 'integer', 'min:0'],
            'use_automatic_discounts' => ['sometimes', 'boolean'],
            'widget_program_name' => ['sometimes', 'string', 'max:50'],
            'widget_primary_color' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'widget_mode' => ['sometimes', 'string', 'in:auto,light,dark'],
            'widget_position' => ['sometimes', 'string', 'in:bottom-right,bottom-left,top-right,top-left'],
        ]);

        $map = [
            'points_use_card_rules' => 'integrations.shopify.points_use_card_rules',
            'points_per_currency_fallback' => 'integrations.shopify.points_per_currency_fallback',
            'points_rounding_fallback' => 'integrations.shopify.points_rounding_fallback',
            'award_on' => 'integrations.shopify.award_on',
            'deduct_on_refund' => 'integrations.shopify.deduct_on_refund',
            'first_order_bonus' => 'integrations.shopify.first_order_bonus',
            'use_automatic_discounts' => 'integrations.shopify.use_automatic_discounts',
            'widget_program_name' => 'integrations.shopify.widget.program_name',
            'widget_primary_color' => 'integrations.shopify.widget.primary_color',
            'widget_mode' => 'integrations.shopify.widget.mode',
            'widget_position' => 'integrations.shopify.widget.position',
        ];

        $changed = [];

        foreach ($validated as $inputKey => $value) {
            $settingKey = $map[$inputKey] ?? null;
            if (! $settingKey) {
                continue;
            }

            $oldValue = $this->settings->get($settingKey);

            $this->settings->set($settingKey, $value, user: null);

            if ((string) $oldValue !== (string) $value) {
                $changed[$settingKey] = ['old' => $oldValue, 'new' => $value];
            }
        }

        if (! empty($changed)) {
            $this->activityLog->log(
                description: 'Shopify integration settings updated',
                subject: $integration,
                event: 'integration.shopify.settings.updated',
                properties: [
                    'club_id' => $integration->club_id,
                    'integration_id' => $integration->id,
                    'changes' => $changed,
                    'changed_count' => count($changed),
                    'ip' => $request->ip(),
                ],
                logName: 'integration'
            );
        }

        return response()->json([
            'success' => true,
            'changes' => $changed,
        ]);
    }

    /**
     * Create or update a reward mapping for this integration.
     */
    public function upsertRewardMapping(Request $request, string $integrationId): JsonResponse
    {
        $partner = $request->user('partner_api') ?? auth('partner')->user();

        $integration = ClubIntegration::query()->with('club')->findOrFail($integrationId);

        if (! $partner || $integration->club?->created_by !== $partner->id) {
            abort(403, 'This integration does not belong to your account');
        }

        $validated = $request->validate([
            'reward_id' => ['required', 'string'],
            'discount_type' => ['required', 'string'],
            'discount_value' => ['required', 'integer', 'min:0'],
            'discount_code_prefix' => ['sometimes', 'string', 'max:32'],
            'use_automatic_discount' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'platform_config' => ['sometimes', 'array'],
        ]);

        $reward = Reward::query()->findOrFail($validated['reward_id']);

        if ($reward->created_by !== $partner->id) {
            abort(403, 'This reward does not belong to your account');
        }

        $mapping = DB::transaction(function () use ($integration, $validated) {
            return IntegrationReward::query()->updateOrCreate(
                [
                    'club_integration_id' => $integration->id,
                    'reward_id' => $validated['reward_id'],
                ],
                [
                    'discount_type' => $validated['discount_type'],
                    'discount_value' => $validated['discount_value'],
                    'discount_code_prefix' => Arr::get($validated, 'discount_code_prefix', 'REWARD'),
                    'use_automatic_discount' => (bool) Arr::get($validated, 'use_automatic_discount', true),
                    'platform_config' => Arr::get($validated, 'platform_config'),
                    'is_active' => (bool) Arr::get($validated, 'is_active', true),
                ]
            );
        });

        $this->activityLog->log(
            description: 'Shopify reward mapping upserted',
            subject: $mapping,
            event: 'integration.shopify.reward_mapping.upserted',
            properties: [
                'club_id' => $integration->club_id,
                'integration_id' => $integration->id,
                'reward_id' => $validated['reward_id'],
                'mapping_id' => $mapping->id,
                'ip' => $request->ip(),
            ],
            logName: 'integration'
        );

        return response()->json([
            'success' => true,
            'mapping' => $mapping->fresh(['reward']),
        ]);
    }

    public function pause(Request $request, string $integrationId): JsonResponse
    {
        $partner = $request->user('partner_api') ?? auth('partner')->user();

        $integration = ClubIntegration::query()->with('club')->findOrFail($integrationId);

        if (! $partner || $integration->club?->created_by !== $partner->id) {
            abort(403, 'This integration does not belong to your account');
        }

        $integration->markPaused();

        $this->activityLog->log(
            description: 'Shopify integration paused',
            subject: $integration,
            event: 'integration.shopify.paused',
            properties: [
                'club_id' => $integration->club_id,
                'integration_id' => $integration->id,
                'ip' => $request->ip(),
            ],
            logName: 'integration'
        );

        return response()->json(['success' => true]);
    }

    public function resume(Request $request, string $integrationId): JsonResponse
    {
        $partner = $request->user('partner_api') ?? auth('partner')->user();

        $integration = ClubIntegration::query()->with('club')->findOrFail($integrationId);

        if (! $partner || $integration->club?->created_by !== $partner->id) {
            abort(403, 'This integration does not belong to your account');
        }

        $integration->markActive();

        $this->activityLog->log(
            description: 'Shopify integration resumed',
            subject: $integration,
            event: 'integration.shopify.resumed',
            properties: [
                'club_id' => $integration->club_id,
                'integration_id' => $integration->id,
                'ip' => $request->ip(),
            ],
            logName: 'integration'
        );

        return response()->json(['success' => true]);
    }

    public function disconnect(Request $request, string $integrationId): JsonResponse
    {
        $partner = $request->user('partner_api') ?? auth('partner')->user();

        $integration = ClubIntegration::query()->with('club')->findOrFail($integrationId);

        if (! $partner || $integration->club?->created_by !== $partner->id) {
            abort(403, 'This integration does not belong to your account');
        }

        // Defensive security posture: remove token on manual disconnect.
        $integration->access_token = null;
        $integration->markDisconnected();

        $this->activityLog->log(
            description: 'Shopify integration disconnected',
            subject: $integration,
            event: 'integration.shopify.disconnected',
            properties: [
                'club_id' => $integration->club_id,
                'integration_id' => $integration->id,
                'ip' => $request->ip(),
            ],
            logName: 'integration'
        );

        return response()->json(['success' => true]);
    }
}
