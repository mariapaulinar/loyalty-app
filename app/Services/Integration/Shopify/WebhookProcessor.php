<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Shopify Webhook Processor
 *
 * Processes incoming Shopify webhooks, handling signature verification,
 * idempotency, topic routing, and integration with the loyalty system.
 *
 * Webhook Flow:
 * ─────────────────────────────────────────────────────────────────────────────────
 * 1. Controller receives webhook, calls verifySignature()
 * 2. If valid, calls process() with topic and payload
 * 3. Processor checks if integration can process (status=ACTIVE)
 * 4. Checks for duplicate via IntegrationWebhookReceipt
 * 5. Routes to appropriate handler based on topic
 * 6. Handler processes webhook, creates transactions if needed
 * 7. Receipt created with result and sanitized metadata
 *
 * Idempotency:
 * ─────────────────────────────────────────────────────────────────────────────────
 * Shopify may retry webhooks multiple times. We prevent duplicate processing via:
 * - Primary: X-Shopify-Webhook-Id header (shopify_event_id in receipts)
 * - Fallback: Resource ID from payload (order_id, customer_id, etc.)
 *
 * Supported Topics:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - orders/paid: Award points for completed purchases
 * - refunds/create: Deduct points for refunded orders (if enabled)
 * - customers/create: Create/update member mapping
 * - app/uninstalled: Mark integration as disconnected
 *
 * @see App\Models\ClubIntegration
 * @see App\Models\IntegrationWebhookReceipt
 * @see App\Services\Card\TransactionService
 */

namespace App\Services\Integration\Shopify;

use App\Models\Card;
use App\Models\ClubIntegration;
use App\Models\IntegrationWebhookReceipt;
use App\Models\Member;
use App\Models\Transaction;
use App\Services\ActivityLogService;
use App\Services\Member\MemberService;
use App\Services\SettingsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookProcessor
{
    /**
     * Receipt status constants.
     */
    private const STATUS_PROCESSED = 'processed';

    private const STATUS_IGNORED = 'ignored';

    private const STATUS_SKIPPED = 'skipped';

    private const STATUS_FAILED = 'failed';

    /**
     * Receipt status used when webhook processing is queued.
     *
     * Written by WebhookController when config('integrations.shopify.queue_webhooks') is enabled.
     * A queued receipt is not a processed duplicate; it exists to guarantee idempotency
     * before the queued job runs.
     */
    private const STATUS_QUEUED = 'queued';

    public function __construct(
        private readonly MemberService $memberService,
        private readonly ActivityLogService $activityLog,
        private readonly SettingsService $settings
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // SIGNATURE VERIFICATION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Verify the webhook signature using HMAC-SHA256.
     *
     * Tries integration-specific secret first, falls back to global secret.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  ClubIntegration  $integration  The target integration
     * @return bool True if signature is valid
     */
    public function verifySignature(Request $request, ClubIntegration $integration): bool
    {
        $signature = $request->header('X-Shopify-Hmac-SHA256');
        if (empty($signature)) {
            $this->log('warning', 'Missing X-Shopify-Hmac-SHA256 header', $integration);

            return false;
        }

        $payload = $request->getContent();

        // Try integration-specific secret first (preferred)
        if (! empty($integration->webhook_secret)) {
            $expectedHash = base64_encode(hash_hmac('sha256', $payload, $integration->webhook_secret, true));
            if (hash_equals($expectedHash, $signature)) {
                return true;
            }
        }

        // Fallback to global secret (legacy/development)
        $globalSecret = config('integrations.shopify.global_webhook_secret');
        if (! empty($globalSecret)) {
            $expectedHash = base64_encode(hash_hmac('sha256', $payload, $globalSecret, true));
            if (hash_equals($expectedHash, $signature)) {
                return true;
            }
        }

        $this->log('warning', 'Invalid webhook signature', $integration, [
            'has_integration_secret' => ! empty($integration->webhook_secret),
            'has_global_secret' => ! empty($globalSecret),
        ]);

        return false;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MAIN PROCESSING
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Process an incoming webhook.
     *
     * @param  ClubIntegration  $integration  The target integration
     * @param  string  $topic  Webhook topic (e.g., 'orders/paid')
     * @param  array  $payload  Webhook payload
     * @param  string|null  $shopifyEventId  X-Shopify-Webhook-Id header
     * @return array{status: string, message: string, receipt_id?: string}
     */
    public function process(
        ClubIntegration $integration,
        string $topic,
        array $payload,
        ?string $shopifyEventId = null
    ): array {
        // Normalize topic (Shopify sends "orders/paid", we store as "orders/paid")
        $topic = strtolower(trim($topic));

        // Extract resource ID for idempotency fallback
        $resourceId = $this->extractResourceId($topic, $payload);

        try {
            // If a receipt already exists, treat it as duplicate unless it is queued.
            $existingReceipt = $this->findExistingReceipt($integration, $topic, $shopifyEventId, $resourceId);
            if ($existingReceipt && $existingReceipt->status !== self::STATUS_QUEUED) {
                return [
                    'status' => self::STATUS_SKIPPED,
                    'message' => 'Duplicate webhook, already processed',
                    'receipt_id' => $existingReceipt->id,
                ];
            }

            // Check if integration can process webhooks
            if (! $integration->status->canProcess()) {
                return $this->createReceipt($integration, $topic, self::STATUS_SKIPPED, [
                    'shopify_event_id' => $shopifyEventId,
                    'resource_id' => $resourceId,
                    'error' => "Integration status is {$integration->status->value}",
                    'payload_meta' => $this->buildPayloadMeta($topic, $payload),
                ], $existingReceipt);
            }

            // Route to appropriate handler
            $result = match ($topic) {
                'orders/paid' => $this->handleOrderPaid($integration, $payload),
                'refunds/create' => $this->handleRefundCreate($integration, $payload),
                'customers/create', 'customers/update' => $this->handleCustomerCreate($integration, $payload),
                'app/uninstalled' => $this->handleAppUninstalled($integration, $payload),
                default => [
                    'status' => self::STATUS_IGNORED,
                    'message' => "Unhandled topic: {$topic}",
                ],
            };

            // Create receipt
            return $this->createReceipt($integration, $topic, $result['status'], [
                'shopify_event_id' => $shopifyEventId,
                'resource_id' => $resourceId,
                'error' => $result['error'] ?? null,
                'payload_meta' => $this->buildPayloadMeta($topic, $payload),
            ], $existingReceipt);

        } catch (\Throwable $e) {
            // Log error and mark integration as errored
            $this->log('error', 'Webhook processing failed', $integration, [
                'topic' => $topic,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark integration as errored for repeated failures
            $integration->markError("Webhook processing failed: {$e->getMessage()}");

            // Audit the failure
            $this->activityLog->log(
                "Shopify webhook processing failed: {$topic}",
                $integration,
                'integration.shopify.webhook.failed',
                [
                    'topic' => $topic,
                    'error' => $e->getMessage(),
                    'resource_id' => $resourceId,
                ]
            );

            return $this->createReceipt($integration, $topic, self::STATUS_FAILED, [
                'shopify_event_id' => $shopifyEventId,
                'resource_id' => $resourceId,
                'error' => $e->getMessage(),
                'payload_meta' => $this->buildPayloadMeta($topic, $payload),
            ], $existingReceipt ?? null);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HANDLERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Handle orders/paid webhook.
     *
     * Awards points to member based on order total.
     *
     * @return array{status: string, message: string, error?: string}
     */
    private function handleOrderPaid(ClubIntegration $integration, array $payload): array
    {
        $orderId = $payload['id'] ?? null;
        $externalReference = "shopify:order:{$orderId}";

        // Check if already processed via external_reference
        if (Transaction::where('external_reference', $externalReference)->exists()) {
            return [
                'status' => self::STATUS_SKIPPED,
                'message' => 'Order already processed',
            ];
        }

        // Extract customer email
        $customer = $payload['customer'] ?? [];
        $email = $customer['email'] ?? $payload['email'] ?? null;

        if (empty($email)) {
            // Guest checkout — can't award points without member identification
            $this->activityLog->log(
                'Shopify order skipped: guest checkout (no email)',
                $integration,
                'integration.shopify.order_paid.skipped_guest_checkout',
                [
                    'order_id' => $orderId,
                    'order_number' => $payload['order_number'] ?? null,
                ]
            );

            return [
                'status' => self::STATUS_SKIPPED,
                'message' => 'Guest checkout - no customer email',
            ];
        }

        // Find or create member
        $member = $this->findOrCreateMember($integration, $email, $customer);

        // Get default card for the club
        $card = $this->getDefaultCard($integration);
        if (! $card) {
            return [
                'status' => self::STATUS_SKIPPED,
                'message' => 'No active card found for club',
                'error' => 'No active loyalty card configured',
            ];
        }

        // Calculate order amount in cents
        // Prefer total_price_set.shop_money for accurate shop currency
        $amountCents = $this->extractAmountCents($payload);
        $currency = $payload['currency'] ?? $card->currency ?? 'USD';

        // Calculate points
        $points = $this->calculatePoints($integration, $card, $amountCents);

        // Check for first order bonus
        $firstOrderBonus = $this->applyFirstOrderBonus($integration, $member, $card);
        $totalPoints = $points + $firstOrderBonus;

        if ($totalPoints <= 0) {
            return [
                'status' => self::STATUS_IGNORED,
                'message' => 'Zero points calculated',
            ];
        }

        // Create transaction
        DB::transaction(function () use ($integration, $member, $card, $amountCents, $totalPoints, $externalReference, $orderId, $currency, $points, $firstOrderBonus) {
            $transaction = $this->createEarnTransaction(
                member: $member,
                card: $card,
                points: $totalPoints,
                amountCents: $amountCents,
                currency: $currency,
                externalReference: $externalReference,
                note: "Shopify order #{$orderId}",
                meta: [
                    'source' => 'shopify',
                    'order_id' => $orderId,
                    'base_points' => $points,
                    'first_order_bonus' => $firstOrderBonus,
                    'integration_id' => $integration->id,
                ]
            );

            // Auto-follow card if not already
            if (! $card->members()->where('member_id', $member->id)->exists()) {
                $card->members()->syncWithoutDetaching([$member->id]);
            }

            // Update card stats
            $card->total_amount_purchased += $amountCents;
            $card->number_of_points_issued += $totalPoints;
            $card->last_points_issued_at = now();
            $card->save();
        });

        // Audit successful processing
        $this->activityLog->log(
            "Shopify order processed: {$totalPoints} points awarded",
            $integration,
            'integration.shopify.order_paid.processed',
            [
                'order_id' => $orderId,
                'member_id' => $member->id,
                'points' => $totalPoints,
                'amount_cents' => $amountCents,
                'currency' => $currency,
            ]
        );

        return [
            'status' => self::STATUS_PROCESSED,
            'message' => "Awarded {$totalPoints} points",
        ];
    }

    /**
     * Handle refunds/create webhook.
     *
     * Deducts points if full refund and deduction is enabled.
     *
     * @return array{status: string, message: string, error?: string}
     */
    private function handleRefundCreate(ClubIntegration $integration, array $payload): array
    {
        // Check if deduction on refund is enabled
        if (! $integration->shouldDeductOnRefund()) {
            return [
                'status' => self::STATUS_IGNORED,
                'message' => 'Point deduction on refund is disabled',
            ];
        }

        $refundId = $payload['id'] ?? null;
        $orderId = $payload['order_id'] ?? null;
        $externalReference = "shopify:refund:{$refundId}";

        // Check if already processed
        if (Transaction::where('external_reference', $externalReference)->exists()) {
            return [
                'status' => self::STATUS_SKIPPED,
                'message' => 'Refund already processed',
            ];
        }

        // Check if this is a partial refund (Phase 1: ignore partial refunds)
        $refundLineItems = $payload['refund_line_items'] ?? [];
        $orderLineItems = $payload['order']['line_items'] ?? [];

        // If we have refund line items, it might be partial
        // For Phase 1, we'll check if all items are fully refunded
        // A simple heuristic: compare refund amount to order total
        $refundAmount = 0;
        foreach ($payload['transactions'] ?? [] as $transaction) {
            if (($transaction['kind'] ?? '') === 'refund') {
                $refundAmount += (float) ($transaction['amount'] ?? 0);
            }
        }

        // Find original earn transaction
        $originalRef = "shopify:order:{$orderId}";
        $originalTransaction = Transaction::where('external_reference', $originalRef)
            ->where('points', '>', 0)
            ->first();

        if (! $originalTransaction) {
            return [
                'status' => self::STATUS_IGNORED,
                'message' => 'Original order transaction not found',
            ];
        }

        // Check if this is a partial refund by comparing amounts
        $orderTotal = $originalTransaction->purchase_amount / 100; // Convert cents to decimal
        $isPartial = abs($refundAmount - $orderTotal) > 0.01;

        if ($isPartial) {
            $this->activityLog->log(
                'Shopify partial refund ignored',
                $integration,
                'integration.shopify.refund.partial_ignored',
                [
                    'refund_id' => $refundId,
                    'order_id' => $orderId,
                    'refund_amount' => $refundAmount,
                    'order_total' => $orderTotal,
                ]
            );

            return [
                'status' => self::STATUS_IGNORED,
                'message' => 'Partial refund - not deducting points',
            ];
        }

        // Full refund: deduct points
        $pointsToDeduct = $originalTransaction->points;

        $member = Member::find($originalTransaction->member_id);
        $card = Card::find($originalTransaction->card_id);

        if (! $member || ! $card) {
            return [
                'status' => self::STATUS_SKIPPED,
                'message' => 'Member or card not found',
            ];
        }

        // Create deduction transaction
        DB::transaction(function () use ($member, $card, $pointsToDeduct, $externalReference, $refundId, $orderId, $integration, $originalTransaction) {
            $this->createDeductTransaction(
                member: $member,
                card: $card,
                points: $pointsToDeduct,
                externalReference: $externalReference,
                note: "Shopify refund #{$refundId} for order #{$orderId}",
                meta: [
                    'source' => 'shopify',
                    'refund_id' => $refundId,
                    'order_id' => $orderId,
                    'original_transaction_id' => $originalTransaction->id,
                    'integration_id' => $integration->id,
                ]
            );

            // Update card stats
            $card->number_of_points_issued -= $pointsToDeduct;
            $card->save();
        });

        // Audit
        $this->activityLog->log(
            "Shopify refund processed: {$pointsToDeduct} points deducted",
            $integration,
            'integration.shopify.refund_full.processed',
            [
                'refund_id' => $refundId,
                'order_id' => $orderId,
                'member_id' => $member->id,
                'points_deducted' => $pointsToDeduct,
            ]
        );

        return [
            'status' => self::STATUS_PROCESSED,
            'message' => "Deducted {$pointsToDeduct} points",
        ];
    }

    /**
     * Handle customers/create webhook.
     *
     * Creates or updates member mapping.
     *
     * @return array{status: string, message: string}
     */
    private function handleCustomerCreate(ClubIntegration $integration, array $payload): array
    {
        $customerId = $payload['id'] ?? null;
        $email = $payload['email'] ?? null;

        if (empty($email)) {
            return [
                'status' => self::STATUS_IGNORED,
                'message' => 'No email in customer data',
            ];
        }

        // Find or create member
        $member = $this->findOrCreateMember($integration, $email, $payload);

        // Store Shopify customer ID in member meta for future lookups
        $meta = $member->meta ?? [];
        $meta['shopify_customer_ids'] = $meta['shopify_customer_ids'] ?? [];

        $key = $integration->id;
        if (! isset($meta['shopify_customer_ids'][$key]) || $meta['shopify_customer_ids'][$key] !== $customerId) {
            $meta['shopify_customer_ids'][$key] = $customerId;
            $member->meta = $meta;
            $member->save();
        }

        return [
            'status' => self::STATUS_PROCESSED,
            'message' => 'Member mapping updated',
        ];
    }

    /**
     * Handle app/uninstalled webhook.
     *
     * Marks integration as disconnected.
     *
     * @return array{status: string, message: string}
     */
    private function handleAppUninstalled(ClubIntegration $integration, array $payload): array
    {
        $integration->markDisconnected();

        $this->activityLog->log(
            'Shopify app uninstalled',
            $integration,
            'integration.shopify.app_uninstalled',
            [
                'store' => $integration->store_identifier,
                'club_id' => $integration->club_id,
            ]
        );

        return [
            'status' => self::STATUS_PROCESSED,
            'message' => 'Integration disconnected',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPER METHODS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Find or create a member by email.
     */
    private function findOrCreateMember(ClubIntegration $integration, string $email, array $customerData): Member
    {
        $email = strtolower(trim($email));
        $member = $this->memberService->findByEmail($email);

        if ($member) {
            return $member;
        }

        // Create new member
        $firstName = $customerData['first_name'] ?? '';
        $lastName = $customerData['last_name'] ?? '';
        $name = trim("{$firstName} {$lastName}") ?: null;

        return $this->memberService->store([
            'email' => $email,
            'name' => $name,
            'display_name' => $firstName ?: $name,
            'is_active' => true,
            'meta' => [
                'source' => 'shopify',
                'shopify_customer_ids' => [
                    $integration->id => $customerData['id'] ?? null,
                ],
            ],
        ]);
    }

    /**
     * Get the default active card for the integration's club.
     */
    private function getDefaultCard(ClubIntegration $integration): ?Card
    {
        return Card::where('club_id', $integration->club_id)
            ->where('is_active', true)
            ->orderBy('created_at', 'asc') // First created = default
            ->first();
    }

    /**
     * Extract order amount in cents from payload.
     */
    private function extractAmountCents(array $payload): int
    {
        // Prefer total_price_set.shop_money for accurate shop currency
        $shopMoney = $payload['total_price_set']['shop_money'] ?? null;
        if ($shopMoney && isset($shopMoney['amount'])) {
            return (int) round((float) $shopMoney['amount'] * 100);
        }

        // Fallback to total_price
        $totalPrice = $payload['total_price'] ?? $payload['subtotal_price'] ?? '0';

        return (int) round((float) $totalPrice * 100);
    }

    /**
     * Calculate points based on integration settings and card rules.
     */
    private function calculatePoints(ClubIntegration $integration, Card $card, int $amountCents): int
    {
        // Convert cents to currency units
        $amount = $amountCents / 100;

        // Use card rules if enabled and card has points_per_currency
        if ($integration->useCardRules() && $card->points_per_currency > 0) {
            $points = $amount * $card->points_per_currency;

            // Apply card's rounding setting
            $roundUp = $card->meta['round_points_up'] ?? false;

            return $roundUp ? (int) ceil($points) : (int) floor($points);
        }

        // Fallback to integration settings
        $pointsPerCurrency = $integration->getFallbackPointsPerCurrency();
        $points = $amount * $pointsPerCurrency;

        // Apply rounding
        $rounding = $integration->getFallbackRounding();

        return match ($rounding) {
            'up' => (int) ceil($points),
            'nearest' => (int) round($points),
            default => (int) floor($points), // 'down'
        };
    }

    /**
     * Apply first order bonus if this is the member's first transaction.
     */
    private function applyFirstOrderBonus(ClubIntegration $integration, Member $member, Card $card): int
    {
        $bonus = $integration->getFirstOrderBonus();
        if ($bonus <= 0) {
            return 0;
        }

        // Efficient check: any existing transaction for this member/card?
        $hasTransaction = Transaction::where('member_id', $member->id)
            ->where('card_id', $card->id)
            ->exists();

        return $hasTransaction ? 0 : $bonus;
    }

    /**
     * Create an earn transaction (points credited).
     */
    private function createEarnTransaction(
        Member $member,
        Card $card,
        int $points,
        int $amountCents,
        string $currency,
        string $externalReference,
        string $note,
        array $meta = []
    ): Transaction {
        $partner = $card->partner;

        return Transaction::create([
            'member_id' => $member->id,
            'card_id' => $card->id,
            'staff_id' => null, // No staff for Shopify orders
            'points' => $points,
            'points_used' => 0,
            'purchase_amount' => $amountCents,
            'event' => 'shopify_order_points',
            'note' => $note,
            'external_reference' => $externalReference,
            'partner_name' => $partner?->name,
            'partner_email' => $partner?->email,
            'card_title' => $card->getTranslations('head'),
            'currency' => $currency,
            'points_per_currency' => $card->points_per_currency,
            'min_points_per_purchase' => $card->min_points_per_purchase,
            'max_points_per_purchase' => $card->max_points_per_purchase,
            'meta' => $meta,
            'expires_at' => Carbon::now()->addMonths((int) $card->points_expiration_months),
            'created_by' => $partner?->id,
        ]);
    }

    /**
     * Create a deduct transaction (points removed due to refund).
     */
    private function createDeductTransaction(
        Member $member,
        Card $card,
        int $points,
        string $externalReference,
        string $note,
        array $meta = []
    ): Transaction {
        $partner = $card->partner;

        return Transaction::create([
            'member_id' => $member->id,
            'card_id' => $card->id,
            'staff_id' => null,
            'points' => -$points, // Negative for deduction
            'event' => 'shopify_refund_deduction',
            'note' => $note,
            'external_reference' => $externalReference,
            'partner_name' => $partner?->name,
            'partner_email' => $partner?->email,
            'card_title' => $card->getTranslations('head'),
            'currency' => $card->currency,
            'meta' => $meta,
            'created_by' => $partner?->id,
        ]);
    }

    /**
     * Find an existing receipt for idempotency.
     *
     * This is used for two purposes:
     * - Detect duplicates that should be skipped
     * - Allow a queued receipt to be updated by the queued job
     */
    private function findExistingReceipt(
        ClubIntegration $integration,
        string $topic,
        ?string $shopifyEventId,
        ?string $resourceId
    ): ?IntegrationWebhookReceipt {
        $query = IntegrationWebhookReceipt::query()
            ->where('club_integration_id', $integration->id)
            ->where('topic', $topic);

        // Primary check: Shopify event ID
        if (! empty($shopifyEventId)) {
            return (clone $query)->where('shopify_event_id', $shopifyEventId)->first();
        }

        // Fallback check: resource ID
        if (! empty($resourceId)) {
            return (clone $query)->where('resource_id', $resourceId)->first();
        }

        return null;
    }

    /**
     * Extract resource ID from payload based on topic.
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
     * @return array{order_total?: int, line_items?: int, currency?: string, customer_email_hash?: string}
     */
    private function buildPayloadMeta(string $topic, array $payload): array
    {
        $meta = [];

        if (str_starts_with($topic, 'orders/') || str_starts_with($topic, 'refunds/')) {
            // Order metadata
            $meta['order_total'] = $this->extractAmountCents($payload);
            $meta['line_items'] = count($payload['line_items'] ?? []);
            $meta['currency'] = $payload['currency'] ?? null;
            $meta['order_number'] = $payload['order_number'] ?? $payload['name'] ?? null;

            // Hash email for debugging without PII
            $email = $payload['customer']['email'] ?? $payload['email'] ?? null;
            if ($email) {
                $meta['customer_email_hash'] = substr(hash('sha256', strtolower($email)), 0, 12);
            }
        }

        if (str_starts_with($topic, 'customers/')) {
            $email = $payload['email'] ?? null;
            if ($email) {
                $meta['customer_email_hash'] = substr(hash('sha256', strtolower($email)), 0, 12);
            }
        }

        return $meta;
    }

    /**
     * Create a webhook receipt record.
     *
     * @return array{status: string, message: string, receipt_id: string}
     */
    private function createReceipt(
        ClubIntegration $integration,
        string $topic,
        string $status,
        array $data = [],
        ?IntegrationWebhookReceipt $existingReceipt = null
    ): array {
        $receipt = $existingReceipt;

        if ($receipt) {
            $receipt->status = $status;
            $receipt->error = $data['error'] ?? null;
            $receipt->processed_at = now();
            $receipt->payload_meta = $data['payload_meta'] ?? null;

            $receipt->shopify_event_id ??= $data['shopify_event_id'] ?? null;
            $receipt->resource_id ??= $data['resource_id'] ?? null;

            $receipt->save();
        } else {
            $receipt = IntegrationWebhookReceipt::create([
                'club_integration_id' => $integration->id,
                'topic' => $topic,
                'shopify_event_id' => $data['shopify_event_id'] ?? null,
                'resource_id' => $data['resource_id'] ?? null,
                'status' => $status,
                'error' => $data['error'] ?? null,
                'processed_at' => now(),
                'payload_meta' => $data['payload_meta'] ?? null,
            ]);
        }

        return [
            'status' => $status,
            'message' => $data['error'] ?? "Webhook {$status}",
            'receipt_id' => $receipt->id,
        ];
    }

    /**
     * Log a message with integration context.
     */
    private function log(string $level, string $message, ClubIntegration $integration, array $context = []): void
    {
        Log::log($level, "[Shopify Webhook] {$message}", array_merge($context, [
            'integration_id' => $integration->id,
            'store_identifier' => $integration->store_identifier,
        ]));
    }
}
