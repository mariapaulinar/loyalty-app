<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Add External Reference to Transactions
 *
 * Adds the ability to link loyalty transactions to external platform events.
 * This enables idempotent point issuance and creates an audit trail back to
 * the source system.
 *
 * Use Cases:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - Shopify order: "shopify:order:5678901234"
 * - WooCommerce order: "woo:order:12345"
 * - Manual adjustment: "manual:admin:uuid-here"
 * - Refund: "shopify:refund:9876543210"
 *
 * Idempotency:
 * ─────────────────────────────────────────────────────────────────────────────────
 * Before creating a transaction from a webhook, query:
 *   Transaction::where('external_reference', $ref)->exists()
 *
 * If true, the transaction was already created — skip processing.
 * This prevents duplicate point issuance from webhook retries.
 *
 * Format Convention:
 * ─────────────────────────────────────────────────────────────────────────────────
 * Recommended: "{platform}:{resource_type}:{resource_id}"
 * - platform: shopify, woocommerce, manual, api
 * - resource_type: order, refund, adjustment
 * - resource_id: Platform's unique identifier
 *
 * @see App\Models\Transaction
 * @see App\Services\Card\TransactionService
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guard: Only add column if it doesn't already exist
        // Allows safe re-running of migrations (e.g., testing, recovery)
        if (Schema::hasColumn('transactions', 'external_reference')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table): void {
            // External system reference for idempotency and audit trail
            // Indexed for fast duplicate detection during webhook processing
            $table->string('external_reference')
                ->nullable()
                ->index('transactions_external_reference_idx');
        });
    }

    public function down(): void
    {
        // Guard: Only drop column if it exists
        // Safe rollback even if up() was never run
        if (! Schema::hasColumn('transactions', 'external_reference')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex('transactions_external_reference_idx');
            $table->dropColumn('external_reference');
        });
    }
};
