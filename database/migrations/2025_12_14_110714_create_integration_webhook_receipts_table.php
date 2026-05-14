<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Integration Webhook Receipts Table
 *
 * Audit log for all incoming platform webhooks. This table serves two critical
 * functions: idempotency (preventing duplicate processing) and debugging
 * (understanding what happened when things go wrong).
 *
 * Idempotency Strategy:
 * ─────────────────────────────────────────────────────────────────────────────────
 * Shopify may send the same webhook multiple times (retries, network issues).
 * We prevent duplicate processing via unique constraints on:
 * - (club_integration_id, topic, shopify_event_id) — Shopify's X-Shopify-Event-Id
 * - (club_integration_id, topic, resource_id) — Fallback for order/customer IDs
 *
 * Before processing, we check if a receipt exists. If yes, skip processing.
 *
 * Processing Statuses:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - processed: Successfully handled, points issued/deducted
 * - ignored: Valid webhook but no action needed (e.g., order already processed)
 * - skipped: Intentionally not processed (e.g., integration paused)
 * - failed: Processing error occurred, stored in 'error' column
 *
 * Privacy & Storage:
 * ─────────────────────────────────────────────────────────────────────────────────
 * We do NOT store full webhook payloads (PII concerns, storage bloat).
 * payload_meta stores only essential metadata for debugging:
 * - order_total, customer_email_hash, line_item_count, etc.
 *
 * @see App\Models\IntegrationWebhookReceipt
 * @see App\Services\Integrations\WebhookProcessor
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_webhook_receipts', function (Blueprint $table) {
            // ─────────────────────────────────────────────────────────────────
            // PRIMARY KEY
            // ─────────────────────────────────────────────────────────────────

            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // RELATIONSHIPS
            // ─────────────────────────────────────────────────────────────────

            // The integration that received this webhook
            // Cascade: receipts removed when integration deleted
            $table->foreignUuid('club_integration_id')
                ->constrained('club_integrations')
                ->cascadeOnDelete();

            // ─────────────────────────────────────────────────────────────────
            // WEBHOOK IDENTIFICATION
            // ─────────────────────────────────────────────────────────────────

            // Webhook topic/event type
            // Shopify: orders/paid, orders/refunded, customers/create
            // WooCommerce: woocommerce_order_status_completed, etc.
            $table->string('topic');

            // Shopify's unique event ID (X-Shopify-Event-Id header)
            // Primary idempotency key — guaranteed unique per delivery
            $table->string('shopify_event_id')->nullable();

            // Resource ID (order ID, customer ID, etc.)
            // Secondary idempotency key — fallback when event_id unavailable
            $table->string('resource_id')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // PROCESSING STATUS
            // ─────────────────────────────────────────────────────────────────

            // Processing result: processed, ignored, skipped, failed
            $table->string('status')->default('processed');

            // Error message if processing failed
            // Contains exception message, API error, validation failure, etc.
            $table->text('error')->nullable();

            // When the webhook was processed (may differ from created_at)
            // Null if still pending (queued webhooks)
            $table->dateTime('processed_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // METADATA (NO FULL PAYLOAD)
            // ─────────────────────────────────────────────────────────────────

            // Sanitized metadata for debugging — NO PII, NO full payload
            // Example: { "order_total": 9999, "line_items": 3, "currency": "USD" }
            $table->json('payload_meta')->nullable();

            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES FOR QUERY PERFORMANCE & IDEMPOTENCY
            // ─────────────────────────────────────────────────────────────────

            // Webhook history: recent receipts for an integration
            $table->index(
                ['club_integration_id', 'processed_at'],
                'integration_webhook_receipts_integration_processed_idx'
            );

            // Idempotency: prevent duplicate processing via Shopify event ID
            // This is the PRIMARY deduplication mechanism
            $table->unique(
                ['club_integration_id', 'topic', 'shopify_event_id'],
                'integration_webhook_receipts_integration_topic_event_unique'
            );

            // Idempotency: fallback deduplication via resource ID
            // Used when shopify_event_id is unavailable
            $table->unique(
                ['club_integration_id', 'topic', 'resource_id'],
                'integration_webhook_receipts_integration_topic_resource_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_webhook_receipts');
    }
};
