<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Stamp Cards Tables Migration
 *
 * Creates three tables for the digital punch card system:
 * 1. stamp_cards - Card templates that clubs create
 * 2. stamp_card_member - Member enrollment and progress tracking
 * 3. stamp_transactions - Audit trail of all stamp events
 *
 * Stamp cards are INDEPENDENT from the points system. They provide simple
 * "Buy 10, Get 1 Free" mechanics that are universally understood by businesses
 * and customers alike.
 *
 * Design Considerations:
 * - MySQL strict mode compatible (no zero dates, proper defaults)
 * - Denormalized counters for performance
 * - Comprehensive indexes for query optimization
 * - JSON fields for translatable content
 * - Audit trail with soft deletes
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ══════════════════════════════════════════════════════════════════════
        // TABLE 1: stamp_cards
        // Defines the stamp card templates that clubs create
        // ══════════════════════════════════════════════════════════════════════
        Schema::create('stamp_cards', function (Blueprint $table) {
            // UUID primary key for globally unique identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // CLUB ASSOCIATION
            // ─────────────────────────────────────────────────────────────────

            $table->foreignUuid('club_id')->constrained('clubs')->cascadeOnDelete();

            // ─────────────────────────────────────────────────────────────────
            // CARD IDENTITY
            // ─────────────────────────────────────────────────────────────────

            // Internal name for partner dashboard (e.g., "Coffee Loyalty Card")
            $table->string('name', 128);
            // Unique identifier for QR codes and public URLs (e.g., "123-456-789-012")
            $table->string('unique_identifier', 32)->unique();
            // Public-facing card title shown to members (translatable)
            $table->json('title')->nullable();
            // Card message/CTA (translatable, e.g., "Save for your favorite coffee!")
            $table->json('description')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // STAMP CONFIGURATION
            // How many stamps required and earning rules
            // ─────────────────────────────────────────────────────────────────

            // Number of stamps needed for reward (1-255, typically 5-10)
            $table->unsignedTinyInteger('stamps_required')->default(10);
            // Stamps awarded per qualifying purchase (usually 1)
            $table->unsignedTinyInteger('stamps_per_purchase')->default(1);
            // Daily earning limit per member (NULL = unlimited)
            $table->unsignedTinyInteger('max_stamps_per_day')->nullable();
            // Per-transaction limit (NULL = unlimited)
            $table->unsignedTinyInteger('max_stamps_per_transaction')->nullable();
            // Minimum purchase amount in currency units to qualify (NULL = any purchase qualifies)
            $table->decimal('min_purchase_amount', 10, 2)->unsigned()->nullable();
            // Product IDs/SKUs that qualify (NULL = any product qualifies, JSON array if restricted)
            $table->json('qualifying_products')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // REWARD CONFIGURATION
            // What member gets when card is completed
            // ─────────────────────────────────────────────────────────────────

            // Reward name (translatable, e.g., "Free Coffee")
            $table->json('reward_title')->nullable();
            // Reward terms/details (translatable)
            $table->json('reward_description')->nullable();
            // Monetary value of reward in currency units for analytics (optional)
            $table->decimal('reward_value', 10, 2)->unsigned()->nullable();
            // Currency for reward_value (ISO 4217)
            $table->char('currency', 3)->nullable();
            // Loyalty points to award on completion (in addition to physical reward)
            $table->unsignedInteger('reward_points')->nullable();
            // Loyalty card to credit points to (if reward_points is set)
            $table->uuid('reward_card_id')->nullable();
            $table->foreign('reward_card_id')->references('id')->on('cards')->onDelete('set null');
            // Require physical reward claiming (show QR for staff to scan and mark as claimed)
            $table->boolean('requires_physical_claim')->default(false);

            // ─────────────────────────────────────────────────────────────────
            // VISUAL DESIGN
            // Card appearance customization
            // ─────────────────────────────────────────────────────────────────

            // Card background color (hex)
            $table->string('bg_color', 7)->default('#1F2937');
            // Background overlay opacity (0-100)
            $table->unsignedTinyInteger('bg_color_opacity')->default(75);
            // Card text color (hex)
            $table->string('text_color', 7)->default('#FFFFFF');
            // Filled stamp color (hex)
            $table->string('stamp_color', 7)->default('#10B981');
            // Empty stamp slot color (hex)
            $table->string('empty_stamp_color', 7)->default('#4B5563');
            // Emoji or icon name for stamps
            $table->string('stamp_icon', 64)->default('☕');

            // ─────────────────────────────────────────────────────────────────
            // AVAILABILITY & LIFECYCLE
            // When card is active and accessible
            // ─────────────────────────────────────────────────────────────────

            // When card becomes active (NULL = active immediately)
            $table->dateTime('valid_from')->nullable();
            // When card expires - no new enrollments after this (NULL = never expires)
            $table->dateTime('valid_until')->nullable();
            // Days until earned stamps expire (NULL = stamps never expire)
            $table->unsignedInteger('stamps_expire_days')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // CARD FLAGS
            // Control card behavior and visibility
            // ─────────────────────────────────────────────────────────────────

            // Card can be used for earning/redeeming
            $table->boolean('is_active')->default(true);
            // Card shown on public homepage (false = hidden/invite-only via QR/URL)
            $table->boolean('is_visible_by_default')->default(false);
            // Protect system cards from deletion
            $table->boolean('is_undeletable')->default(false);

            // ─────────────────────────────────────────────────────────────────
            // PARTNER-CONFIGURABLE BEHAVIOR
            // Per-card settings that partners can customize
            // ─────────────────────────────────────────────────────────────────

            // Show monetary value of reward to members
            $table->boolean('show_monetary_value')->default(false);

            // ─────────────────────────────────────────────────────────────────
            // DENORMALIZED STATISTICS
            // Updated by event listeners for performance
            // ─────────────────────────────────────────────────────────────────

            // Total stamps ever issued
            $table->unsignedInteger('total_stamps_issued')->default(0);
            // Times card has been completed
            $table->unsignedInteger('total_completions')->default(0);
            // Times reward has been redeemed
            $table->unsignedInteger('total_redemptions')->default(0);

            // ─────────────────────────────────────────────────────────────────
            // EXTENSIBLE METADATA
            // ─────────────────────────────────────────────────────────────────

            // Store: advanced rules, campaign info, external refs
            $table->json('meta')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // AUDIT TRAIL
            // ─────────────────────────────────────────────────────────────────

            $table->foreignUuid('created_by')->nullable()->constrained('partners')->cascadeOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('partners')->nullOnDelete();
            $table->foreignUuid('deleted_by')->nullable()->constrained('partners')->nullOnDelete();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            $table->index('club_id', 'stamp_cards_club_id_idx');
            $table->index('is_active', 'stamp_cards_is_active_idx');
            // Combined for club-level active card queries
            $table->index(['club_id', 'is_active'], 'stamp_cards_club_active_idx');
            // Combined for discovering visible active cards on public homepage
            $table->index(['club_id', 'is_visible_by_default', 'is_active'], 'stamp_cards_discovery_idx');
            // Expiration management
            $table->index(['is_active', 'valid_until'], 'stamp_cards_expiration_idx');
            // Audit indexes
            $table->index('created_by', 'stamp_cards_created_by_idx');
            $table->index('updated_by', 'stamp_cards_updated_by_idx');
            $table->index('deleted_by', 'stamp_cards_deleted_by_idx');
        });

        // ══════════════════════════════════════════════════════════════════════
        // TABLE 2: stamp_card_member
        // Tracks each member's progress on each stamp card
        // ══════════════════════════════════════════════════════════════════════
        Schema::create('stamp_card_member', function (Blueprint $table) {
            // UUID primary key
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // RELATIONSHIPS
            // ─────────────────────────────────────────────────────────────────

            $table->foreignUuid('stamp_card_id')->constrained('stamp_cards')->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained('members')->cascadeOnDelete();

            // ─────────────────────────────────────────────────────────────────
            // PROGRESS TRACKING
            // ─────────────────────────────────────────────────────────────────

            // Current stamps toward next reward (0 to stamps_required-1)
            $table->unsignedTinyInteger('current_stamps')->default(0);
            // Total stamps ever earned on this card (never decreases)
            $table->unsignedInteger('lifetime_stamps')->default(0);
            // Times card has been completed (filled and reset)
            $table->unsignedInteger('completed_count')->default(0);
            // Times reward has been redeemed
            $table->unsignedInteger('redeemed_count')->default(0);
            // Completed cards awaiting redemption
            $table->unsignedTinyInteger('pending_rewards')->default(0);

            // ─────────────────────────────────────────────────────────────────
            // TIMESTAMPS
            // Track member's engagement with this card
            // ─────────────────────────────────────────────────────────────────

            // When member enrolled/started this card
            $table->dateTime('enrolled_at')->useCurrent();
            // Most recent stamp earned
            $table->dateTime('last_stamp_at')->nullable();
            // Most recent card completion
            $table->dateTime('last_completed_at')->nullable();
            // Most recent reward redemption
            $table->dateTime('last_redeemed_at')->nullable();
            // When next stamp will expire (for expiration tracking)
            $table->dateTime('next_stamp_expires_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // FLAGS
            // ─────────────────────────────────────────────────────────────────

            // Enrollment is active (false = member unenrolled)
            $table->boolean('is_active')->default(true);

            // ─────────────────────────────────────────────────────────────────
            // EXTENSIBLE METADATA
            // ─────────────────────────────────────────────────────────────────

            $table->json('meta')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // TIMESTAMPS
            // ─────────────────────────────────────────────────────────────────

            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            // One enrollment per member per card
            $table->unique(['stamp_card_id', 'member_id'], 'stamp_card_member_unique_idx');
            // Member's active enrollments
            $table->index(['member_id', 'is_active'], 'stamp_card_member_member_active_idx');
            // Find "almost complete" members for marketing (e.g., 8/10 stamps)
            $table->index(['stamp_card_id', 'current_stamps'], 'stamp_card_member_progress_idx');
            // Members with pending rewards
            $table->index(['stamp_card_id', 'pending_rewards'], 'stamp_card_member_rewards_idx');
            // Expiration tracking
            $table->index('next_stamp_expires_at', 'stamp_card_member_expiration_idx');
        });

        // ══════════════════════════════════════════════════════════════════════
        // TABLE 3: stamp_transactions
        // Individual stamp earn/redeem events for audit trail
        // ══════════════════════════════════════════════════════════════════════
        Schema::create('stamp_transactions', function (Blueprint $table) {
            // UUID primary key
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // TRANSACTION PARTICIPANTS
            // ─────────────────────────────────────────────────────────────────

            $table->foreignUuid('stamp_card_id')->constrained('stamp_cards')->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained('members')->cascadeOnDelete();
            // Staff who processed (NULL for system transactions like expiration)
            $table->foreignUuid('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            // Location where transaction occurred (for future multi-location support)
            $table->foreignUuid('location_id')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // STAMP CHANGES
            // ─────────────────────────────────────────────────────────────────

            // Stamps added/removed (+1 earned, -10 redeemed, negative for expiry/void)
            $table->tinyInteger('stamps');
            // Balance before this transaction
            $table->unsignedTinyInteger('stamps_before');
            // Balance after this transaction
            $table->unsignedTinyInteger('stamps_after');

            // ─────────────────────────────────────────────────────────────────
            // TRANSACTION DETAILS
            // ─────────────────────────────────────────────────────────────────

            // Event type (earned, redeemed, expired, voided, adjusted)
            $table->string('event', 32);
            // Associated purchase amount in currency units (NULL if not purchase-related)
            $table->decimal('purchase_amount', 10, 2)->unsigned()->nullable();
            // Currency for purchase_amount (ISO 4217)
            $table->char('currency', 3)->nullable();
            // Link to order if from e-commerce (for future integration)
            $table->foreignUuid('order_id')->nullable();
            // Staff note or system message
            $table->text('note')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // EXTENSIBLE METADATA
            // ─────────────────────────────────────────────────────────────────

            // Store: device info, campaign details, external refs
            $table->json('meta')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // TIMESTAMPS
            // ─────────────────────────────────────────────────────────────────

            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // Critical for audit trail queries and reporting
            // ─────────────────────────────────────────────────────────────────

            // Member's transaction history
            $table->index(['member_id', 'created_at'], 'stamp_trans_member_date_idx');
            // Card's transaction history
            $table->index(['stamp_card_id', 'created_at'], 'stamp_trans_card_date_idx');
            // Staff performance tracking
            $table->index(['staff_id', 'created_at'], 'stamp_trans_staff_date_idx');
            // Event-based filtering
            $table->index('event', 'stamp_trans_event_idx');
            // Combined for detailed card analytics
            $table->index(['stamp_card_id', 'member_id', 'created_at'], 'stamp_trans_card_member_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stamp_transactions');
        Schema::dropIfExists('stamp_card_member');

        // Drop foreign keys before dropping stamp_cards
        Schema::table('stamp_cards', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);
        });

        Schema::dropIfExists('stamp_cards');
    }
};
