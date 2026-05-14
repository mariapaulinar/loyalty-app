<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Staff Table
 *
 * Staff members are employees who handle day-to-day loyalty operations.
 * They scan member QR codes, issue points, and redeem rewards.
 *
 * Staff capabilities:
 * - Scan member QR codes at point-of-sale
 * - Issue points for purchases
 * - Redeem rewards for members
 * - Generate point codes for promotions
 *
 * Staff belong to clubs and are managed by partners.
 *
 * @see App\Models\Staff
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            // UUID primary key for globally unique identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // CLUB ASSOCIATION
            // ─────────────────────────────────────────────────────────────────

            // Staff can belong to a specific club or work across all clubs (null)
            $table->foreignUuid('club_id')->nullable()->constrained('clubs')->nullOnDelete();

            // ─────────────────────────────────────────────────────────────────
            // ACCOUNT INFORMATION
            // ─────────────────────────────────────────────────────────────────

            $table->tinyInteger('role')->default(1)->comment('1=Standard Staff');
            // Unique staff ID for point code attribution and audit
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
            // ACCOUNT LIFECYCLE
            // ─────────────────────────────────────────────────────────────────

            // Useful for seasonal/temporary staff
            $table->dateTime('account_expires_at')->nullable();

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

            // Store: permissions, shift info, performance metrics
            $table->json('meta')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // AUDIT TRAIL
            // ─────────────────────────────────────────────────────────────────

            // Staff are managed by partners
            $table->foreignUuid('created_by')->nullable()->constrained('partners')->cascadeOnDelete();
            $table->foreignUuid('deleted_by')->nullable()->constrained('partners')->cascadeOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('partners')->nullOnDelete();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            $table->index('club_id', 'staff_club_id_idx');
            $table->index('is_active', 'staff_is_active_idx');
            $table->index('last_login_at', 'staff_last_login_idx');
            // Combined for club-level staff queries
            $table->index(['club_id', 'is_active'], 'staff_club_active_idx');
            // Audit indexes
            $table->index('created_by', 'staff_created_by_idx');
            $table->index('deleted_by', 'staff_deleted_by_idx');
            $table->index('updated_by', 'staff_updated_by_idx');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['updated_by']);
        });
        Schema::dropIfExists('staff');
    }
};
