<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Rewards Table
 *
 * Rewards are items or discounts that members can redeem using their points.
 * Rewards are attached to cards via the card_reward pivot table.
 *
 * Reward examples:
 * - Free coffee (500 points)
 * - 10% discount (1000 points)
 * - Free product (2500 points)
 * - VIP experience (10000 points)
 *
 * Rewards have availability windows and redemption limits.
 *
 * @see App\Models\Reward
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            // UUID primary key for globally unique identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // REWARD IDENTITY
            // ─────────────────────────────────────────────────────────────────

            // Internal name for partner dashboard
            $table->string('name', 250);
            // Unique identifier for URLs and QR codes
            $table->string('unique_identifier', 32)->nullable()->unique();
            // Translatable content displayed to members
            $table->json('title')->comment('Translatable: reward title');
            $table->json('description')->nullable()->comment('Translatable: reward description');

            // ─────────────────────────────────────────────────────────────────
            // REDEMPTION RULES
            // ─────────────────────────────────────────────────────────────────

            // Maximum times this reward can be redeemed (0 = unlimited)
            $table->integer('max_number_to_redeem')->default(0);
            // Points required to redeem this reward
            $table->unsignedInteger('points');

            // ─────────────────────────────────────────────────────────────────
            // AVAILABILITY WINDOW
            // ─────────────────────────────────────────────────────────────────

            // Rewards can be scheduled for future campaigns
            $table->dateTime('active_from')->nullable();
            // Expiration date after which reward cannot be redeemed
            $table->dateTime('expiration_date')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // REWARD FLAGS
            // ─────────────────────────────────────────────────────────────────

            $table->boolean('is_active')->default(true);
            $table->boolean('is_undeletable')->default(false);
            $table->boolean('is_uneditable')->default(false);

            // ─────────────────────────────────────────────────────────────────
            // AGGREGATED STATISTICS
            // ─────────────────────────────────────────────────────────────────

            $table->unsignedInteger('number_of_times_redeemed')->default(0);
            $table->unsignedInteger('views')->default(0);
            $table->dateTime('last_view')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // EXTENSIBLE METADATA
            // ─────────────────────────────────────────────────────────────────

            // Store: terms, restrictions, fulfillment instructions
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
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            $table->index('is_active', 'rewards_is_active_idx');
            $table->index('points', 'rewards_points_idx');
            // Active rewards listing sorted by points
            $table->index(['is_active', 'points'], 'rewards_active_points_idx');
            // Availability window queries
            $table->index(['is_active', 'expiration_date'], 'rewards_active_expiry_idx');
            $table->index(['active_from', 'expiration_date'], 'rewards_availability_idx');
            // Audit indexes
            $table->index('created_by', 'rewards_created_by_idx');
            $table->index('deleted_by', 'rewards_deleted_by_idx');
            $table->index('updated_by', 'rewards_updated_by_idx');
        });

        // Add reward_id foreign key to transactions (now that rewards table exists)
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignUuid('reward_id')->nullable()->after('card_id')->constrained('rewards')->nullOnDelete();
            $table->index('reward_id', 'transactions_reward_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['reward_id']);
            $table->dropIndex('transactions_reward_id_idx');
            $table->dropColumn('reward_id');
        });
        Schema::table('rewards', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['updated_by']);
        });
        Schema::dropIfExists('rewards');
    }
};
