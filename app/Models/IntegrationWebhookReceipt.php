<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Integration Webhook Receipt Model
 *
 * Audit log for incoming webhooks from external platforms. This model serves
 * two critical functions: idempotency (preventing duplicate processing) and
 * debugging (understanding what happened when things go wrong).
 *
 * Idempotency Strategy:
 * ─────────────────────────────────────────────────────────────────────────────────
 * Shopify may send the same webhook multiple times (retries, network issues).
 * Before processing a webhook, we check for an existing receipt:
 *
 *   $exists = IntegrationWebhookReceipt::where('club_integration_id', $id)
 *       ->where('topic', $topic)
 *       ->where('shopify_event_id', $eventId)
 *       ->exists();
 *
 * If a receipt exists, skip processing to prevent duplicate point issuance.
 *
 * Processing Statuses:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - processed: Successfully handled (points issued, discount created, etc.)
 * - ignored: Valid webhook but no action needed (order already handled)
 * - skipped: Intentionally not processed (integration paused, test mode)
 * - failed: Processing error occurred (see 'error' column for details)
 *
 * Privacy & Storage Considerations:
 * ─────────────────────────────────────────────────────────────────────────────────
 * We explicitly do NOT store full webhook payloads for:
 * - PII compliance (customer data, emails, addresses)
 * - Storage efficiency (payloads can be large)
 *
 * The payload_meta column stores only sanitized metadata for debugging:
 * - Order totals, item counts, currency
 * - Hashed identifiers (not raw emails)
 *
 * @see App\Models\ClubIntegration
 * @see App\Services\Integrations\WebhookProcessor (future)
 *
 * @property string $id UUID primary key
 * @property string $club_integration_id Foreign key to club_integrations
 * @property string $topic Webhook event type (orders/paid, customers/create)
 * @property string|null $shopify_event_id Shopify's unique event identifier
 * @property string|null $resource_id Platform resource ID (order ID, customer ID)
 * @property string $status Processing result (processed, ignored, skipped, failed)
 * @property string|null $error Error message if processing failed
 * @property \Carbon\Carbon|null $processed_at When webhook was processed
 * @property array|null $payload_meta Sanitized metadata (no PII)
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read ClubIntegration $integration
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationWebhookReceipt extends Model
{
    use HasFactory, HasUuids;

    // ─────────────────────────────────────────────────────────────────────────
    // MODEL CONFIGURATION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * The table associated with the model.
     */
    protected $table = 'integration_webhook_receipts';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'processed_at' => 'datetime',
        'payload_meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get the integration that received this webhook.
     *
     * @return BelongsTo<ClubIntegration, IntegrationWebhookReceipt>
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(ClubIntegration::class, 'club_integration_id');
    }
}
