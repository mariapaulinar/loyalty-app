<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Transactions Table
 *
 * The ledger of all point movements - earning, spending, adjustments, and expirations.
 * This is the source of truth for member point balances.
 *
 * Transaction events:
 * - initial_bonus_points: Points given when member activates card
 * - staff_credited_points_for_purchase: Points earned from purchase
 * - staff_redeemed_points_for_reward: Points spent on reward
 * - points_adjusted: Manual adjustment by staff/partner
 * - points_expired: Automatic expiration of unused points
 * - campaign_bonus: Extra points from promotional campaign
 *
 * Balance calculation: SUM(points - points_used) WHERE expires_at > NOW()
 *
 * @see App\Models\Transaction
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            // UUID primary key for globally unique identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // TRANSACTION PARTICIPANTS
            // ─────────────────────────────────────────────────────────────────

            // Staff who processed the transaction (null for system transactions)
            $table->foreignUuid('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            // Member whose points are affected (required)
            $table->foreignUuid('member_id')->constrained('members')->cascadeOnDelete();
            // Card associated with this transaction
            $table->foreignUuid('card_id')->nullable()->constrained('cards')->cascadeOnDelete();
            // Reward redeemed (if applicable) - added after rewards table
            // $table->foreignUuid('reward_id') is added below

            // ─────────────────────────────────────────────────────────────────
            // DENORMALIZED PARTICIPANT DATA
            // Preserved for historical accuracy even if entities are deleted
            // ─────────────────────────────────────────────────────────────────

            $table->string('partner_name', 128)->nullable();
            $table->string('partner_email', 128)->nullable();
            $table->string('staff_name', 128)->nullable();
            $table->string('staff_email', 128)->nullable();
            // JSON for translatable titles at time of transaction
            $table->json('card_title')->nullable();
            $table->json('reward_title')->nullable();
            $table->unsignedInteger('reward_points')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // TRANSACTION AMOUNTS
            // ─────────────────────────────────────────────────────────────────

            // Currency of original purchase (ISO 4217)
            $table->char('currency', 3)->nullable();
            // Purchase amount in smallest currency unit (cents)
            $table->unsignedBigInteger('purchase_amount')->nullable();
            // Points earned (positive) or spent (negative) in this transaction
            $table->integer('points');
            // Points already consumed from this transaction (for partial usage)
            $table->unsignedInteger('points_used')->default(0);

            // ─────────────────────────────────────────────────────────────────
            // POINT RULES AT TIME OF TRANSACTION
            // Preserved for audit and historical accuracy
            // ─────────────────────────────────────────────────────────────────

            $table->unsignedInteger('currency_unit_amount')->nullable();
            $table->unsignedInteger('points_per_currency')->nullable();
            $table->decimal('point_value', 8, 4)->nullable();
            $table->unsignedInteger('min_points_per_purchase')->nullable();
            $table->unsignedInteger('max_points_per_purchase')->nullable();
            $table->unsignedInteger('min_points_per_redemption')->nullable();
            $table->unsignedInteger('max_points_per_redemption')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // TRANSACTION DETAILS
            // ─────────────────────────────────────────────────────────────────

            // Event type (see class docblock for values)
            $table->string('event', 64)->nullable();
            // Staff/partner note for this transaction
            $table->text('note')->nullable();
            // When these points expire (calculated from card rules)
            $table->dateTime('expires_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // EXTENSIBLE METADATA
            // ─────────────────────────────────────────────────────────────────

            // Store: external references, campaign info, device info
            $table->json('meta')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // AUDIT TRAIL
            // ─────────────────────────────────────────────────────────────────

            $table->foreignUuid('created_by')->nullable()->constrained('partners')->cascadeOnDelete();
            $table->foreignUuid('deleted_by')->nullable()->constrained('partners')->cascadeOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('partners')->nullOnDelete();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES FOR QUERY PERFORMANCE
            // Critical for balance calculations and reporting
            // ─────────────────────────────────────────────────────────────────

            $table->index('staff_id', 'transactions_staff_id_idx');
            $table->index('member_id', 'transactions_member_id_idx');
            $table->index('card_id', 'transactions_card_id_idx');
            $table->index('event', 'transactions_event_idx');

            // CRITICAL: Balance calculation query optimization
            // Query: WHERE member_id=? AND card_id=? AND expires_at > NOW()
            $table->index(['member_id', 'card_id', 'expires_at'], 'transactions_balance_idx');

            // Points expiration batch processing
            $table->index(['expires_at', 'points'], 'transactions_expiration_idx');

            // Event filtering for reporting
            $table->index(['card_id', 'event', 'created_at'], 'transactions_card_event_idx');

            // Time-series analytics
            $table->index(['created_at', 'card_id'], 'transactions_date_card_idx');
            $table->index('created_at', 'transactions_created_at_idx');

            // Purchase amount analytics
            $table->index(['card_id', 'purchase_amount'], 'transactions_purchase_idx');

            // Staff performance tracking
            $table->index(['staff_id', 'created_at'], 'transactions_staff_date_idx');

            // Audit indexes
            $table->index('created_by', 'transactions_created_by_idx');
            $table->index('deleted_by', 'transactions_deleted_by_idx');
            $table->index('updated_by', 'transactions_updated_by_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
            $table->dropForeign(['member_id']);
            $table->dropForeign(['card_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['updated_by']);
        });
        Schema::dropIfExists('transactions');
    }
};
