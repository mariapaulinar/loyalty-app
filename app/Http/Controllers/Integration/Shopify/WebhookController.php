<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Shopify Webhook Controller
 *
 * Provides per-topic endpoints for Shopify webhooks.
 *
 * Why per-topic endpoints?
 * ─────────────────────────────────────────────────────────────────────────────────
 * - Shopify webhooks are configured per topic (orders/paid, refunds/create, etc.)
 * - Clear routing keeps handlers explicit and makes audit trails obvious
 * - Allows per-topic middleware / rate limits later without branching logic
 *
 * Responsibilities:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - Resolve the target ClubIntegration
 * - Verify webhook signature (HMAC)
 * - Hand off payload processing to WebhookProcessor
 * - Return an HTTP response Shopify can interpret for retry behavior
 *
 * NOTE:
 * This controller deliberately does NOT contain business logic.
 * All decisions (idempotency, points issuance, refunds) live in WebhookProcessor.
 *
 * @see App\Services\Integration\Shopify\WebhookProcessor
 */

namespace App\Http\Controllers\Integration\Shopify;

use App\Http\Controllers\Controller;
use App\Jobs\Integration\Shopify\ProcessWebhookJob;
use App\Models\ClubIntegration;
use App\Models\IntegrationWebhookReceipt;
use App\Services\ActivityLogService;
use App\Services\Integration\Shopify\WebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookProcessor $processor,
        private readonly ActivityLogService $activityLog
    ) {}

    public function ordersPaid(Request $request, string $integrationId): JsonResponse
    {
        return $this->handleTopic($request, $integrationId, 'orders/paid');
    }

    public function refundsCreate(Request $request, string $integrationId): JsonResponse
    {
        return $this->handleTopic($request, $integrationId, 'refunds/create');
    }

    public function customersCreate(Request $request, string $integrationId): JsonResponse
    {
        return $this->handleTopic($request, $integrationId, 'customers/create');
    }

    public function customersUpdate(Request $request, string $integrationId): JsonResponse
    {
        return $this->handleTopic($request, $integrationId, 'customers/update');
    }

    public function appUninstalled(Request $request, string $integrationId): JsonResponse
    {
        return $this->handleTopic($request, $integrationId, 'app/uninstalled');
    }

    /**
     * Handle a Shopify webhook for a specific topic.
     *
     * We return:
     * - 200 for processed/ignored/skipped to avoid unnecessary retries
     * - 401 for invalid signature (security)
     * - 500 for failed processing (signals Shopify to retry)
     */
    private function handleTopic(Request $request, string $integrationId, string $topic): JsonResponse
    {
        $integration = ClubIntegration::query()->findOrFail($integrationId);

        if (! $this->processor->verifySignature($request, $integration)) {
            $this->activityLog->log(
                description: 'Shopify webhook rejected: invalid signature',
                subject: $integration,
                event: 'integration.shopify.webhook.invalid_signature',
                properties: [
                    'club_id' => $integration->club_id,
                    'integration_id' => $integration->id,
                    'topic' => $topic,
                    'ip' => $request->ip(),
                ],
                logName: 'integration'
            );

            return response()->json([
                'status' => 'rejected',
                'message' => 'Invalid signature',
            ], 401);
        }

        $shopifyEventId = $request->header('X-Shopify-Webhook-Id');

        // Shopify sends JSON; decode defensively to avoid null payload surprises.
        $payload = $request->json()->all();
        if ($payload === []) {
            $decoded = json_decode($request->getContent(), true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        // Optional: queue webhook processing to keep Shopify response fast.
        // When enabled, we create a "queued" receipt immediately to guarantee idempotency
        // (prevents multiple jobs for the same webhook when Shopify retries).
        if ((bool) config('integrations.shopify.queue_webhooks', false) === true) {
            $resourceId = $this->extractResourceId($topic, $payload);
            $receipt = $this->queueReceipt($integration, $topic, $payload, $shopifyEventId, $resourceId);

            if ($receipt['status'] === 'skipped') {
                return response()->json($receipt, 200);
            }

            ProcessWebhookJob::dispatch(
                integrationId: $integration->id,
                topic: $topic,
                payload: $payload,
                shopifyEventId: is_string($shopifyEventId) ? $shopifyEventId : null,
                resourceId: $resourceId
            );

            return response()->json($receipt, 202);
        }

        $result = $this->processor->process(
            integration: $integration,
            topic: $topic,
            payload: $payload,
            shopifyEventId: is_string($shopifyEventId) ? $shopifyEventId : null
        );

        $statusCode = ($result['status'] ?? null) === 'failed' ? 500 : 200;

        return response()->json($result, $statusCode);
    }

    /**
     * Create a queued receipt to guarantee idempotency before dispatching a job.
     *
     * @return array{status: string, message: string, receipt_id?: string}
     */
    private function queueReceipt(
        ClubIntegration $integration,
        string $topic,
        array $payload,
        mixed $shopifyEventId,
        ?string $resourceId
    ): array {
        $eventId = is_string($shopifyEventId) ? $shopifyEventId : null;

        $defaults = [
            'resource_id' => $resourceId,
            'status' => 'queued',
            'processed_at' => null,
            'payload_meta' => $this->buildPayloadMeta($topic, $payload),
        ];

        if (! empty($eventId)) {
            $receipt = IntegrationWebhookReceipt::firstOrCreate(
                [
                    'club_integration_id' => $integration->id,
                    'topic' => $topic,
                    'shopify_event_id' => $eventId,
                ],
                $defaults
            );
        } elseif (! empty($resourceId)) {
            $receipt = IntegrationWebhookReceipt::firstOrCreate(
                [
                    'club_integration_id' => $integration->id,
                    'topic' => $topic,
                    'resource_id' => $resourceId,
                ],
                array_merge($defaults, [
                    'shopify_event_id' => null,
                ])
            );
        } else {
            // No idempotency keys available (should be rare). We still queue but cannot deduplicate.
            $receipt = IntegrationWebhookReceipt::create([
                'club_integration_id' => $integration->id,
                'topic' => $topic,
                'shopify_event_id' => null,
                'resource_id' => null,
                'status' => 'queued',
                'processed_at' => null,
                'payload_meta' => $defaults['payload_meta'],
            ]);
        }

        if (! $receipt->wasRecentlyCreated) {
            return [
                'status' => 'skipped',
                'message' => 'Duplicate webhook, already received',
                'receipt_id' => $receipt->id,
            ];
        }

        return [
            'status' => 'queued',
            'message' => 'Webhook queued for processing',
            'receipt_id' => $receipt->id,
        ];
    }

    /**
     * Extract resource ID from payload based on topic.
     *
     * This intentionally mirrors WebhookProcessor's strategy so that queued
     * receipts use the same idempotency key as synchronous processing.
     */
    private function extractResourceId(string $topic, array $payload): ?string
    {
        $id = match (true) {
            str_starts_with($topic, 'orders/') => $payload['id'] ?? null,
            str_starts_with($topic, 'refunds/') => $payload['id'] ?? null,
            str_starts_with($topic, 'customers/') => $payload['id'] ?? null,
            $topic === 'app/uninstalled' => 'uninstall',
            default => $payload['id'] ?? null,
        };

        return $id !== null ? (string) $id : null;
    }

    /**
     * Build sanitized payload metadata (NO PII).
     *
     * This minimal schema enables debugging and analytics without storing full
     * webhook payloads or personally identifiable information.
     *
     * @return array{order_total?: int, line_items?: int, currency?: string, order_number?: string, customer_email_hash?: string}
     */
    private function buildPayloadMeta(string $topic, array $payload): array
    {
        $meta = [];

        if (str_starts_with($topic, 'orders/') || str_starts_with($topic, 'refunds/')) {
            $amount = $payload['total_price_set']['shop_money']['amount']
                ?? $payload['current_total_price_set']['shop_money']['amount']
                ?? $payload['total_price']
                ?? null;

            if (is_string($amount) || is_numeric($amount)) {
                $meta['order_total'] = (int) round(((float) $amount) * 100);
            }

            $meta['line_items'] = count($payload['line_items'] ?? []);
            $meta['currency'] = $payload['currency'] ?? null;
            $meta['order_number'] = $payload['order_number'] ?? $payload['name'] ?? null;

            $email = $payload['customer']['email'] ?? $payload['email'] ?? null;
            if (is_string($email) && $email !== '') {
                $meta['customer_email_hash'] = substr(hash('sha256', strtolower($email)), 0, 12);
            }
        }

        if (str_starts_with($topic, 'customers/')) {
            $email = $payload['email'] ?? null;
            if (is_string($email) && $email !== '') {
                $meta['customer_email_hash'] = substr(hash('sha256', strtolower($email)), 0, 12);
            }
        }

        return $meta;
    }
}
