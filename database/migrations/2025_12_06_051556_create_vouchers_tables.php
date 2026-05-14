<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Vouchers Tables Migration
 *
 * Creates two tables for the voucher/discount code system:
 * 1. vouchers - Voucher templates (discount codes) that clubs create
 * 2. voucher_redemptions - Audit trail of all redemption events
 *
 * Vouchers provide instant value through discount codes, complementing the
 * points system with immediate gratification. They support multiple discount
 * types, complex targeting rules, and comprehensive analytics.
 *
 * Design Considerations:
 * - MySQL strict mode compatible (no zero dates, proper defaults)
 * - Denormalized counters for performance (times_used, total_discount_given)
 * - Comprehensive indexes for query optimization
 * - JSON fields for translatable content and flexible metadata
 * - Complete audit trail with voiding support
 * - Monetary amounts stored in cents (bigint) for precision
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
        // TABLE 1: vouchers
        // Defines the voucher templates (discount codes) that clubs create
        // ══════════════════════════════════════════════════════════════════════
        Schema::create('vouchers', function (Blueprint $table) {
            // ─────────────────────────────────────────────────────────────────
            // PRIMARY KEY & OWNERSHIP
            // ─────────────────────────────────────────────────────────────────

            // UUID primary key for consistency with 3.0 architecture
            $table->uuid('id')->primary();

            // Club this voucher belongs to (cascade delete when club deleted)
            $table->foreignUuid('club_id')->constrained('clubs')->cascadeOnDelete();

            // ─────────────────────────────────────────────────────────────────
            // IDENTITY & BRANDING
            // ─────────────────────────────────────────────────────────────────

            // The code members enter (e.g., "SUMMER20", "WELCOME10")
            // Uppercase, 1-32 characters, unique per club
            $table->string('code', 32);

            // Internal reference name (e.g., "Summer Sale 2025")
            $table->string('name', 128);

            // Unique identifier for QR codes and public URLs (generated, 12 digits formatted as XXX-XXX-XXX-XXX)
            $table->string('unique_identifier', 32)->unique();

            // Public-facing title shown to members (translatable JSON)
            $table->json('title')->nullable();

            // Public-facing description/terms (translatable JSON)
            $table->json('description')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // VOUCHER TYPE & VALUE
            // ─────────────────────────────────────────────────────────────────

            // Type: percentage, fixed_amount, free_product, bonus_points
            // NOTE: free_shipping removed - brick & mortar only, no ecommerce for now
            $table->string('type', 32)->default('percentage');

            // Value interpretation depends on type:
            // - percentage: 0-100 (e.g., 20 = 20% off)
            // - fixed_amount: cents (e.g., 1000 = 10.00 in partner's currency)
            // - free_product: not used (see free_product_name)
            // - bonus_points: not used (see points_value)
            $table->unsignedBigInteger('value')->default(0);

            // Currency code for fixed_amount type (ISO 4217, e.g., USD, EUR)
            $table->char('currency', 3)->nullable();

            // Points to award for bonus_points type
            $table->unsignedInteger('points_value')->nullable();

            // Card to credit points to (required when points_value is set)
            $table->foreignUuid('reward_card_id')->nullable()->constrained('cards')->nullOnDelete();

            // ─────────────────────────────────────────────────────────────────
            // BATCH GENERATION
            // ─────────────────────────────────────────────────────────────────

            // If generated as part of a batch, reference to the batch
            // NULL = custom/manual voucher, NOT NULL = batch-generated
            // This enables architectural separation: custom vs mass-generated vouchers
            $table->string('batch_id', 36)->nullable();

            // ─────────────────────────────────────────────────────────────────
            // CONDITIONS & RESTRICTIONS
            // ─────────────────────────────────────────────────────────────────

            // Minimum purchase amount in cents (NULL = no minimum)
            $table->unsignedBigInteger('min_purchase_amount')->nullable();

            // Maximum discount cap in cents for percentage types (NULL = no cap)
            $table->unsignedBigInteger('max_discount_amount')->nullable();

            // Translatable product name for free_product type (JSON)
            $table->json('free_product_name')->nullable();

            // JSON array of product IDs/SKUs this voucher applies to (NULL = all products)
            $table->json('applicable_products')->nullable();

            // JSON array of category IDs this voucher applies to (NULL = all categories)
            $table->json('applicable_categories')->nullable();

            // JSON array of excluded product IDs/SKUs
            $table->json('excluded_products')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // VALIDITY PERIOD
            // ─────────────────────────────────────────────────────────────────

            // When voucher becomes active (NULL = active immediately)
            $table->dateTime('valid_from')->nullable();

            // When voucher expires (NULL = never expires)
            $table->dateTime('valid_until')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // USAGE LIMITS
            // ─────────────────────────────────────────────────────────────────

            // Total usage limit across all members (NULL = unlimited)
            $table->unsignedInteger('max_uses_total')->nullable();

            // Per-member usage limit (NULL = unlimited per member)
            $table->unsignedInteger('max_uses_per_member')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // TARGETING & ELIGIBILITY
            // ─────────────────────────────────────────────────────────────────

            // Target specific member (NULL = available to all)
            $table->foreignUuid('target_member_id')->nullable()->constrained('members')->nullOnDelete();

            // JSON array of tier IDs this voucher is restricted to (NULL = all tiers)
            $table->json('target_tiers')->nullable();

            // Only valid for member's first order
            $table->boolean('first_order_only')->default(false);

            // Only valid for new members (joined within new_members_days)
            $table->boolean('new_members_only')->default(false);

            // Days since joining to qualify as "new member" (default 30 if new_members_only is true)
            $table->unsignedInteger('new_members_days')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // FLAGS & BEHAVIOR
            // ─────────────────────────────────────────────────────────────────

            // Voucher can be used
            $table->boolean('is_active')->default(true);

            // Visible in public voucher listings for members to discover
            $table->boolean('is_public')->default(false);

            // Voucher shown on public homepage (false = hidden/invite-only via code/URL)
            $table->boolean('is_visible_by_default')->default(false);

            // Can only be used once (overrides max_uses_per_member)
            $table->boolean('is_single_use')->default(false);

            // Automatically apply at checkout if eligible
            $table->boolean('is_auto_apply')->default(false);

            // Can be combined with other vouchers
            $table->boolean('stackable')->default(false);

            // ─────────────────────────────────────────────────────────────────
            // SOURCE TRACKING
            // For vouchers generated via campaigns, integrations, or batch operations
            // ─────────────────────────────────────────────────────────────────

            // Source: manual, batch, campaign, api, integration
            $table->string('source', 32)->default('manual');

            // Source identifier (e.g., campaign_id, batch_id)
            $table->uuid('source_id')->nullable();

            // Distribution/Claim tracking
            $table->enum('claimed_via', ['qr_scan', 'email', 'manual', 'api'])->nullable(); // How voucher was claimed/distributed
            $table->dateTime('claimed_at')->nullable(); // When voucher was claimed by member
            $table->foreignUuid('claimed_by_member_id')->nullable()->constrained('members')->nullOnDelete(); // Member who claimed it

            // ─────────────────────────────────────────────────────────────────
            // DENORMALIZED STATISTICS
            // Updated by VoucherService for performance (avoid COUNT queries)
            // ─────────────────────────────────────────────────────────────────

            // Total times voucher has been redeemed (not voided)
            $table->unsignedInteger('times_used')->default(0);

            // Total discount amount given in cents (sum of all redemptions)
            $table->unsignedBigInteger('total_discount_given')->default(0);

            // Number of unique members who have used this voucher
            $table->unsignedInteger('unique_members_used')->default(0);

            // Number of times voucher has been viewed
            $table->unsignedInteger('views')->default(0);

            // Last time voucher was viewed (NULL = never viewed)
            $table->dateTime('last_view')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // VISUAL DESIGN
            // For member-facing display (voucher cards in app)
            // ─────────────────────────────────────────────────────────────────

            // Background color (hex)
            $table->string('bg_color', 7)->default('#7C3AED');

            // Background color opacity (0-100)
            $table->unsignedTinyInteger('bg_color_opacity')->default(85);

            // Text color (hex)
            $table->string('text_color', 7)->default('#FFFFFF');

            // Background image stored via Spatie MediaLibrary (background collection)
            // Logo stored via Spatie MediaLibrary (logo collection)

            // ─────────────────────────────────────────────────────────────────
            // EXTENSIBLE METADATA
            // ─────────────────────────────────────────────────────────────────

            // Store: advanced rules, external refs, custom data
            $table->json('meta')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // AUDIT TRAIL
            // ─────────────────────────────────────────────────────────────────

            $table->foreignUuid('created_by')->nullable()->constrained('partners')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('partners')->nullOnDelete();
            $table->foreignUuid('deleted_by')->nullable()->constrained('partners')->nullOnDelete();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // Critical for query performance and business operations
            // ─────────────────────────────────────────────────────────────────

            // Code uniqueness per club (business constraint)
            $table->unique(['club_id', 'code'], 'vouchers_club_code_unique');

            // Single-column indexes for filtering
            $table->index('club_id', 'vouchers_club_id_idx');
            $table->index('code', 'vouchers_code_idx');
            $table->index('is_active', 'vouchers_is_active_idx');
            $table->index('unique_identifier', 'vouchers_unique_identifier_idx');

            // Composite indexes for common queries
            $table->index(['club_id', 'is_active'], 'vouchers_club_active_idx');
            $table->index(['club_id', 'is_public', 'is_active'], 'vouchers_discovery_idx');
            $table->index(['club_id', 'is_visible_by_default', 'is_active'], 'vouchers_homepage_idx');
            $table->index(['is_active', 'valid_from', 'valid_until'], 'vouchers_validity_idx');

            // Targeting indexes
            $table->index('target_member_id', 'vouchers_target_member_idx');

            // Bonus points card index
            $table->index('reward_card_id', 'vouchers_reward_card_idx');

            // Audit indexes
            $table->index('created_by', 'vouchers_created_by_idx');
            $table->index('updated_by', 'vouchers_updated_by_idx');
            $table->index('deleted_by', 'vouchers_deleted_by_idx');

            // Batch index
            $table->index('batch_id', 'vouchers_batch_id_idx');
        });

        // ══════════════════════════════════════════════════════════════════════
        // TABLE 2: voucher_redemptions
        // Immutable audit trail of all voucher usage events
        // ══════════════════════════════════════════════════════════════════════
        Schema::create('voucher_redemptions', function (Blueprint $table) {
            // ─────────────────────────────────────────────────────────────────
            // PRIMARY KEY & RELATIONSHIPS
            // ─────────────────────────────────────────────────────────────────

            // UUID primary key
            $table->uuid('id')->primary();

            // Voucher that was redeemed (cascade delete when voucher deleted)
            $table->foreignUuid('voucher_id')->constrained('vouchers')->cascadeOnDelete();

            // Member who redeemed (cascade delete when member deleted)
            $table->foreignUuid('member_id')->constrained('members')->cascadeOnDelete();

            // Staff who processed redemption (NULL for self-service, online)
            $table->foreignUuid('staff_id')->nullable()->constrained('staff')->nullOnDelete();

            // Physical location where redeemed (future multi-location support)
            $table->uuid('location_id')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // ORDER CONTEXT
            // Link to e-commerce order if applicable
            // ─────────────────────────────────────────────────────────────────

            // Order UUID (future e-commerce integration)
            $table->uuid('order_id')->nullable();

            // Human-readable order reference (e.g., "ORD-12345")
            $table->string('order_reference', 64)->nullable();

            // ─────────────────────────────────────────────────────────────────
            // DISCOUNT APPLIED
            // Monetary amounts in cents for precision
            // ─────────────────────────────────────────────────────────────────

            // Discount amount given in cents (e.g., 1000 = $10.00 off)
            $table->unsignedBigInteger('discount_amount')->default(0);

            // Original order amount before discount (cents)
            $table->unsignedBigInteger('original_amount')->nullable();

            // Final order amount after discount (cents)
            $table->unsignedBigInteger('final_amount')->nullable();

            // Currency code (ISO 4217)
            $table->char('currency', 3)->default('USD');

            // ─────────────────────────────────────────────────────────────────
            // POINTS (for bonus_points type vouchers)
            // ─────────────────────────────────────────────────────────────────

            // Points awarded (if type = bonus_points)
            $table->unsignedInteger('points_awarded')->nullable();

            // Link to points transaction if created
            $table->foreignUuid('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();

            // ─────────────────────────────────────────────────────────────────
            // STATUS
            // ─────────────────────────────────────────────────────────────────

            // Status: applied, completed, voided, expired
            $table->string('status', 32)->default('completed');

            // ─────────────────────────────────────────────────────────────────
            // VOID INFORMATION
            // For refunds, cancellations, corrections
            // ─────────────────────────────────────────────────────────────────

            // When redemption was voided
            $table->dateTime('voided_at')->nullable();

            // Staff who voided the redemption
            $table->foreignUuid('voided_by')->nullable()->constrained('staff')->nullOnDelete();

            // Reason for voiding (e.g., "Customer refund", "Order cancelled")
            $table->text('void_reason')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // TIMESTAMPS
            // ─────────────────────────────────────────────────────────────────

            // When voucher was actually redeemed
            $table->dateTime('redeemed_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // EXTENSIBLE METADATA
            // ─────────────────────────────────────────────────────────────────

            // Store: device info, IP address, user agent, external refs
            $table->json('meta')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // AUDIT TIMESTAMPS
            // ─────────────────────────────────────────────────────────────────

            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // Critical for audit trail queries and reporting
            // ─────────────────────────────────────────────────────────────────

            // Voucher redemption history
            $table->index(['voucher_id', 'created_at'], 'voucher_redemptions_voucher_date_idx');

            // Member redemption history
            $table->index(['member_id', 'created_at'], 'voucher_redemptions_member_date_idx');

            // Combined for detailed analytics
            $table->index(['voucher_id', 'member_id'], 'voucher_redemptions_voucher_member_idx');

            // Order lookups
            $table->index('order_id', 'voucher_redemptions_order_idx');

            // Status filtering (find voided, completed, etc.)
            $table->index('status', 'voucher_redemptions_status_idx');

            // Staff performance tracking
            $table->index('staff_id', 'voucher_redemptions_staff_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop in reverse order due to foreign key constraints
        Schema::dropIfExists('voucher_redemptions');
        Schema::dropIfExists('vouchers');
    }
};

// Note: batch_id references voucher_batches.id (created in separate migration)
// Foreign key constraint is added after voucher_batches table exists
