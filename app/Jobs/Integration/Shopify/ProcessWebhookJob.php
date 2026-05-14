<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Process Shopify Webhook Job
 *
 * Processes a single Shopify webhook event asynchronously.
 *
 * Why a job?
 * ─────────────────────────────────────────────────────────────────────────────────
 * Shopify expects webhook endpoints to respond quickly. In high-volume stores,
 * point issuance and reward logic can take longer (member lookup, database writes,
 * tier evaluation, etc.). A queued job keeps the webhook response fast while
 * preserving correctness.
 *
 * Idempotency Contract:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - The WebhookController creates a 'queued' receipt immediately (unique by event_id or resource_id)
 * - This job calls WebhookProcessor->process(), which updates the queued receipt to final status
 * - If the job crashes unexpectedly, failed() upgrades the receipt to failed + marks integration errored
 *
 * @see App\Services\Integration\Shopify\WebhookProcessor
 * @see App\Models\IntegrationWebhookReceipt
 */

namespace App\Jobs\Integration\Shopify;

use App\Models\ClubIntegration;
use App\Models\IntegrationWebhookReceipt;
use App\Services\ActivityLogService;
use App\Services\Integration\Shopify\WebhookProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Exponential-ish backoff for transient failures.
     *
     * @var array<int>
     */
    public array $backoff = [60, 120, 240];

    /**
     * Create a new job instance.
     *
     * @param  string  $integrationId  ClubIntegration UUID
     * @param  string  $topic  Shopify topic (e.g. orders/paid)
     * @param  array<string, mixed>  $payload  Webhook payload
     * @param  string|null  $shopifyEventId  X-Shopify-Webhook-Id header value
     * @param  string|null  $resourceId  Extracted resource id (order id, customer id, etc.)
     */
    public function __construct(
        public readonly string $integrationId,
        public readonly string $topic,
        public readonly array $payload,
        public readonly ?string $shopifyEventId = null,
        public readonly ?string $resourceId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WebhookProcessor $processor): void
    {
        $integration = ClubIntegration::query()->findOrFail($this->integrationId);

        $result = $processor->process(
            integration: $integration,
            topic: $this->topic,
            payload: $this->payload,
            shopifyEventId: $this->shopifyEventId
        );

        Log::info('[Shopify Webhook Job] Processed webhook', [
            'integration_id' => $integration->id,
            'topic' => $this->topic,
            'status' => $result['status'] ?? null,
            'receipt_id' => $result['receipt_id'] ?? null,
        ]);
    }

    /**
     * Handle a job failure.
     *
     * This is a safety net for unexpected failures (worker crash, timeouts,
     * serialization issues, etc.). The WebhookProcessor already marks errors
     * and writes receipts when it catches exceptions.
     */
    public function failed(\Throwable $exception): void
    {
        $integration = ClubIntegration::query()->find($this->integrationId);

        if (! $integration) {
            Log::error('[Shopify Webhook Job] Failed and integration not found', [
                'integration_id' => $this->integrationId,
                'topic' => $this->topic,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        // Mark integration as errored to surface operational issues to partners.
        $integration->markError("Webhook job failed: {$exception->getMessage()}");

        // Upgrade queued receipt to failed (or create one if missing).
        $receipt = $this->findReceipt($integration);

        if ($receipt) {
            $receipt->status = 'failed';
            $receipt->error = $exception->getMessage();
            $receipt->processed_at = now();
            $receipt->payload_meta = $receipt->payload_meta ?: $this->buildPayloadMeta($this->topic, $this->payload);
            $receipt->save();
        } else {
            IntegrationWebhookReceipt::create([
                'club_integration_id' => $integration->id,
                'topic' => strtolower(trim($this->topic)),
                'shopify_event_id' => $this->shopifyEventId,
                'resource_id' => $this->resourceId,
                'status' => 'failed',
                'error' => $exception->getMessage(),
                'processed_at' => now(),
                'payload_meta' => $this->buildPayloadMeta($this->topic, $this->payload),
            ]);
        }

        app(ActivityLogService::class)->log(
            description: 'Shopify webhook job failed',
            subject: $integration,
            event: 'integration.shopify.webhook.job_failed',
            properties: [
                'club_id' => $integration->club_id,
                'integration_id' => $integration->id,
                'topic' => $this->topic,
                'shopify_event_id' => $this->shopifyEventId,
                'resource_id' => $this->resourceId,
                'exception' => get_class($exception),
                'error' => $exception->getMessage(),
            ],
            logName: 'integration'
        );

        Log::error('[Shopify Webhook Job] Job failed', [
            'integration_id' => $integration->id,
            'topic' => $this->topic,
            'error' => $exception->getMessage(),
        ]);
    }

    private function findReceipt(ClubIntegration $integration): ?IntegrationWebhookReceipt
    {
        $query = IntegrationWebhookReceipt::query()
            ->where('club_integration_id', $integration->id)
            ->where('topic', strtolower(trim($this->topic)));

        if (! empty($this->shopifyEventId)) {
            return $query->where('shopify_event_id', $this->shopifyEventId)->first();
        }

        if (! empty($this->resourceId)) {
            return $query->where('resource_id', $this->resourceId)->first();
        }

        return null;
    }

    /**
     * Build sanitized payload metadata (NO PII).
     *
     * @return array{order_total?: int, line_items?: int, currency?: string, order_number?: string, customer_email_hash?: string}
     */
    private function buildPayloadMeta(string $topic, array $payload): array
    {
        $topic = strtolower(trim($topic));
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
