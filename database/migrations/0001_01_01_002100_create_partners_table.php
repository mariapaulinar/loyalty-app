<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Partners Table
 *
 * Partners are businesses that use Reward Loyalty to manage their customer loyalty programs.
 * Each partner belongs to a network and can create multiple clubs (brands/locations).
 *
 * Partner is the primary business account that:
 * - Creates and manages loyalty cards
 * - Defines rewards and point rules
 * - Manages staff members
 * - Views analytics and reports
 *
 * @see App\Models\Partner
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            // UUID primary key for globally unique identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // NETWORK ASSOCIATION
            // ─────────────────────────────────────────────────────────────────

            // Partner belongs to a network; cascade deletes partner if network deleted
            $table->foreignUuid('network_id')->nullable()->constrained('networks')->cascadeOnDelete();

            // ─────────────────────────────────────────────────────────────────
            // ACCOUNT INFORMATION
            // ─────────────────────────────────────────────────────────────────

            // Role 1 is default partner role; expandable for partner tiers
            $table->tinyInteger('role')->default(1)->comment('1=Standard Partner');
            // Business representative's display name
            $table->string('display_name', 64)->nullable();
            // Legal name of business owner/representative
            $table->string('name', 128)->nullable();
            // Primary login email
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
            // SUBSCRIPTION & ACCOUNT LIFECYCLE
            // ─────────────────────────────────────────────────────────────────

            // Partner account expiration (for trials or subscription-based access)
            $table->dateTime('account_expires_at')->nullable();
            // Premium tier expiration (for feature-limited free tier)
            $table->dateTime('premium_expires_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // LOCALIZATION
            // ─────────────────────────────────────────────────────────────────

            $table->string('locale', 12)->nullable();
            $table->char('country_code', 2)->nullable();
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
            // ACTIVITY TRACKING
            // ─────────────────────────────────────────────────────────────────

            $table->unsignedInteger('number_of_times_logged_in')->default(0);
            $table->dateTime('last_login_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // EXTENSIBLE METADATA
            // ─────────────────────────────────────────────────────────────────

            // Store: business info, branding preferences, feature flags
            $table->json('meta')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // AUDIT TRAIL
            // ─────────────────────────────────────────────────────────────────

            // Partners are created by admins
            $table->foreignUuid('created_by')->nullable()->constrained('admins')->cascadeOnDelete();
            $table->foreignUuid('deleted_by')->nullable()->constrained('admins')->cascadeOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            $table->index('network_id', 'partners_network_id_idx');
            $table->index('is_active', 'partners_is_active_idx');
            $table->index('last_login_at', 'partners_last_login_idx');
            // Combined index for network-level active partner queries
            $table->index(['network_id', 'is_active'], 'partners_network_active_idx');
            // Audit indexes
            $table->index('created_by', 'partners_created_by_idx');
            $table->index('deleted_by', 'partners_deleted_by_idx');
            $table->index('updated_by', 'partners_updated_by_idx');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropForeign(['network_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['updated_by']);
        });
        Schema::dropIfExists('partners');
    }
};
