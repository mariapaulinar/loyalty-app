<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Affiliates Table
 *
 * Affiliates are referral partners who earn commissions by bringing new members
 * to the loyalty program. Each affiliate gets a unique identifier for tracking.
 *
 * Affiliate program enables:
 * - Referral link tracking (unique_identifier in URLs)
 * - Commission tracking per referred member
 * - Multi-level affiliate hierarchies (future)
 *
 * @see App\Models\Affiliate
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            // UUID primary key for globally unique identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // NETWORK ASSOCIATION
            // ─────────────────────────────────────────────────────────────────

            $table->foreignUuid('network_id')->nullable()->constrained('networks')->cascadeOnDelete();

            // ─────────────────────────────────────────────────────────────────
            // ACCOUNT INFORMATION
            // ─────────────────────────────────────────────────────────────────

            $table->tinyInteger('role')->default(1)->comment('1=Standard Affiliate');
            // Unique affiliate code for referral URLs (e.g., ref=ABC123)
            $table->string('unique_identifier', 32)->nullable()->unique();
            $table->string('display_name', 64)->nullable();
            $table->string('name', 128)->nullable();
            $table->string('email', 128)->unique();
            $table->dateTime('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();

            // ─────────────────────────────────────────────────────────────────
            // TWO-FACTOR AUTHENTICATION
            // ─────────────────────────────────────────────────────────────────

            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->string('two_factor_recovery_codes')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // LOCALIZATION
            // ─────────────────────────────────────────────────────────────────

            $table->string('locale', 12)->nullable();
            $table->char('currency', 3)->nullable();
            $table->string('time_zone', 48)->nullable();

            // ─────────────────────────────────────────────────────────────────
            // PHONE
            // ─────────────────────────────────────────────────────────────────

            $table->string('phone_prefix', 4)->nullable();
            $table->string('phone_country', 2)->nullable();
            $table->string('phone', 24)->nullable();
            $table->string('phone_e164', 24)->nullable();

            // ─────────────────────────────────────────────────────────────────
            // ACCOUNT FLAGS
            // ─────────────────────────────────────────────────────────────────

            $table->boolean('is_active')->default(true);
            $table->boolean('is_undeletable')->default(false);
            $table->boolean('is_uneditable')->default(false);

            // ─────────────────────────────────────────────────────────────────
            // ACTIVITY & PERFORMANCE TRACKING
            // ─────────────────────────────────────────────────────────────────

            $table->unsignedInteger('number_of_times_logged_in')->default(0);
            $table->dateTime('last_login_at')->nullable();
            // Count of members who signed up using this affiliate's link
            $table->unsignedInteger('number_of_members_affiliated')->default(0);

            // ─────────────────────────────────────────────────────────────────
            // EXTENSIBLE METADATA
            // ─────────────────────────────────────────────────────────────────

            // Store: commission rates, payout info, referral stats
            $table->json('meta')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // AUDIT TRAIL
            // ─────────────────────────────────────────────────────────────────

            $table->foreignUuid('created_by')->nullable()->constrained('admins')->cascadeOnDelete();
            $table->foreignUuid('deleted_by')->nullable()->constrained('admins')->cascadeOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            $table->index('network_id', 'affiliates_network_id_idx');
            $table->index('is_active', 'affiliates_is_active_idx');
            // Performance leaderboard queries
            $table->index('number_of_members_affiliated', 'affiliates_performance_idx');
            // Audit indexes
            $table->index('created_by', 'affiliates_created_by_idx');
            $table->index('deleted_by', 'affiliates_deleted_by_idx');
            $table->index('updated_by', 'affiliates_updated_by_idx');
        });
    }

    public function down(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropForeign(['network_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['updated_by']);
        });
        Schema::dropIfExists('affiliates');
    }
};
