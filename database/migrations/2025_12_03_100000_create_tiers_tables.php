<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Tiers Tables Migration
 *
 * Membership tiers create a status-driven engagement platform where members
 * climb Bronze → Silver → Gold → Platinum, unlocking escalating benefits
 * that drive repeat purchases.
 *
 * Tier features:
 * - Qualification thresholds (points, spend, transactions)
 * - Points multipliers for accelerated earning
 * - Redemption discounts
 * - Translatable names and descriptions
 * - Visual customization (icon, color)
 *
 * @see App\Models\Tier
 * @see App\Models\MemberTier
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────────────────
        // TIERS TABLE
        // Defines tier levels available for each club
        // ─────────────────────────────────────────────────────────────────────

        Schema::create('tiers', function (Blueprint $table) {
            // UUID primary key for globally unique identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // CLUB ASSOCIATION
            // ─────────────────────────────────────────────────────────────────

            $table->foreignUuid('club_id')->constrained('clubs')->cascadeOnDelete();

            // ─────────────────────────────────────────────────────────────────
            // TIER IDENTITY
            // ─────────────────────────────────────────────────────────────────

            // Internal name for partner dashboard (e.g., "Bronze", "Silver")
            $table->string('name', 64);
            // Translatable member-facing name (JSON for multi-language support)
            $table->json('display_name')->nullable()->comment('Translatable: member-facing tier name');
            // Translatable benefits summary
            $table->json('description')->nullable()->comment('Translatable: tier description/benefits summary');
            // Emoji or icon identifier for visual display
            $table->string('icon', 64)->nullable();
            // Hex color for badges (e.g., "#FFD700" for gold)
            $table->string('color', 7)->nullable();
            // Hierarchy level (0 = base/default, higher = better)
            $table->unsignedInteger('level')->default(0);

            // ─────────────────────────────────────────────────────────────────
            // QUALIFICATION THRESHOLDS
            // Members qualify when meeting any/all thresholds (configurable)
            // ─────────────────────────────────────────────────────────────────

            // Lifetime points required to qualify
            $table->unsignedBigInteger('points_threshold')->nullable();
            // Lifetime spend in cents required to qualify
            $table->unsignedBigInteger('spend_threshold')->nullable();
            // Transaction count required to qualify
            $table->unsignedInteger('transactions_threshold')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // TIER BENEFITS
            // ─────────────────────────────────────────────────────────────────

            // Earning multiplier (1.5 = +50% bonus points)
            $table->decimal('points_multiplier', 5, 2)->default(1.00);
            // Redemption cost reduction (0.10 = -10% points needed)
            $table->decimal('redemption_discount', 5, 2)->default(0.00);
            // Array of benefit descriptions for display
            $table->json('benefits')->nullable()->comment('Array of translatable benefit strings');

            // ─────────────────────────────────────────────────────────────────
            // TIER FLAGS
            // ─────────────────────────────────────────────────────────────────

            // New members automatically receive this tier
            $table->boolean('is_default')->default(false);
            // Tier is available for qualification
            $table->boolean('is_active')->default(true);
            // Protect system/default tiers from deletion
            $table->boolean('is_undeletable')->default(false);

            // ─────────────────────────────────────────────────────────────────
            // EXTENSIBLE METADATA
            // ─────────────────────────────────────────────────────────────────

            $table->json('meta')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // AUDIT TRAIL
            // ─────────────────────────────────────────────────────────────────

            $table->foreignUuid('created_by')->nullable()->constrained('partners')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('partners')->nullOnDelete();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            // Club tier hierarchy queries
            $table->index(['club_id', 'level'], 'tiers_club_level_idx');
            // Active tier queries
            $table->index(['club_id', 'is_active'], 'tiers_club_active_idx');
            // Default tier lookup
            $table->index(['club_id', 'is_default'], 'tiers_club_default_idx');
        });

        // ─────────────────────────────────────────────────────────────────────
        // MEMBER_TIERS TABLE
        // Tracks each member's current tier status per club
        // ─────────────────────────────────────────────────────────────────────

        Schema::create('member_tiers', function (Blueprint $table) {
            // UUID primary key for globally unique identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // RELATIONSHIPS
            // ─────────────────────────────────────────────────────────────────

            $table->foreignUuid('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignUuid('tier_id')->constrained('tiers')->cascadeOnDelete();
            // Denormalized for query efficiency
            $table->foreignUuid('club_id')->constrained('clubs')->cascadeOnDelete();

            // ─────────────────────────────────────────────────────────────────
            // TIER STATUS
            // ─────────────────────────────────────────────────────────────────

            // When this tier was achieved
            $table->dateTime('achieved_at')->useCurrent();
            // For annual renewal programs (null = never expires)
            $table->dateTime('expires_at')->nullable();
            // Points that qualified the member for this tier
            $table->unsignedBigInteger('qualifying_points')->default(0);
            // Spend that qualified the member for this tier (cents)
            $table->unsignedBigInteger('qualifying_spend')->default(0);
            // Transaction count that qualified the member
            $table->unsignedInteger('qualifying_transactions')->default(0);
            // For tracking progression history
            $table->foreignUuid('previous_tier_id')->nullable()->constrained('tiers')->nullOnDelete();
            // Current status (for soft-disable without deletion)
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

            // One active tier per member per club (enforced in application layer)
            // We use an index rather than unique constraint to allow tier history
            $table->index(['member_id', 'club_id', 'is_active'], 'member_tiers_member_club_active_idx');
            // Tier population reports
            $table->index(['club_id', 'tier_id'], 'member_tiers_club_tier_idx');
            // Active member tier lookups
            $table->index(['member_id', 'is_active'], 'member_tiers_member_active_idx');
            // Expiration management
            $table->index('expires_at', 'member_tiers_expires_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_tiers');
        Schema::dropIfExists('tiers');
    }
};
