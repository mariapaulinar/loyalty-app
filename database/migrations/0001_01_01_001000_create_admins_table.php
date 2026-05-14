<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Admins Table
 *
 * System administrators who manage the Reward Loyalty platform.
 * Admins have full access to all networks, partners, and system settings.
 *
 * Role hierarchy:
 * - Role 1: Super Admin (full system access, manages other admins)
 * - Role 2: Manager (network-level management, limited admin functions)
 *
 * @see App\Models\Admin
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            // UUID primary key for globally unique identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // ACCOUNT INFORMATION
            // ─────────────────────────────────────────────────────────────────

            // Role determines admin's access level and permissions
            $table->tinyInteger('role')->default(2)->comment('1=Super Admin, 2=Manager');

            // Display name shown in UI, name is full legal name
            $table->string('display_name', 64)->nullable();
            $table->string('name', 128)->nullable();

            // Email is primary login identifier, must be unique
            $table->string('email', 128)->unique();
            $table->dateTime('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();

            // ─────────────────────────────────────────────────────────────────
            // TWO-FACTOR AUTHENTICATION
            // ─────────────────────────────────────────────────────────────────

            // 2FA protects admin accounts; secret stored encrypted
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->string('two_factor_recovery_codes')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // ACCOUNT LIFECYCLE
            // ─────────────────────────────────────────────────────────────────

            // Account can be time-limited for contractors or temporary access
            $table->dateTime('account_expires_at')->nullable();
            // Premium features for admin accounts (future use)
            $table->dateTime('premium_expires_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // LOCALIZATION
            // ─────────────────────────────────────────────────────────────────

            // Admin's preferred locale (e.g., 'en_US', 'pt_BR')
            $table->string('locale', 12)->nullable();
            // ISO 3166-1 alpha-2 country code
            $table->char('country_code', 2)->nullable();
            // ISO 4217 currency code for display preferences
            $table->char('currency', 3)->nullable();
            // IANA timezone identifier (e.g., 'America/New_York')
            $table->string('time_zone', 48)->nullable();

            // ─────────────────────────────────────────────────────────────────
            // PHONE (for 2FA SMS, OTP, notifications)
            // ─────────────────────────────────────────────────────────────────

            $table->string('phone_prefix', 4)->nullable();
            $table->string('phone_country', 2)->nullable();
            $table->string('phone', 24)->nullable();
            // E.164 format for international SMS delivery
            $table->string('phone_e164', 24)->nullable();

            // ─────────────────────────────────────────────────────────────────
            // ACCOUNT FLAGS
            // ─────────────────────────────────────────────────────────────────

            // Active flag controls login access
            $table->boolean('is_active')->default(true);
            // Prevent accidental deletion of critical system admins
            $table->boolean('is_undeletable')->default(false);
            // Prevent editing of system-generated admin accounts
            $table->boolean('is_uneditable')->default(false);

            // ─────────────────────────────────────────────────────────────────
            // ACTIVITY TRACKING
            // ─────────────────────────────────────────────────────────────────

            $table->unsignedInteger('number_of_times_logged_in')->default(0);
            $table->dateTime('last_login_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // EXTENSIBLE METADATA
            // ─────────────────────────────────────────────────────────────────

            // JSON field for custom attributes without schema changes
            $table->text('meta')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // AUDIT TRAIL
            // ─────────────────────────────────────────────────────────────────

            // Self-referential: admins create/delete/update other admins
            $table->foreignUuid('created_by')->nullable();
            $table->foreignUuid('deleted_by')->nullable();
            $table->foreignUuid('updated_by')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES FOR QUERY PERFORMANCE
            // ─────────────────────────────────────────────────────────────────

            // Role-based filtering for permission checks
            $table->index('role', 'admins_role_idx');
            // Active admin filtering
            $table->index('is_active', 'admins_is_active_idx');
            // Activity reporting
            $table->index('last_login_at', 'admins_last_login_idx');

            // Foreign key indexes
            $table->index('created_by', 'admins_created_by_idx');
            $table->index('deleted_by', 'admins_deleted_by_idx');
            $table->index('updated_by', 'admins_updated_by_idx');
        });

        // Add self-referential foreign keys after table creation
        Schema::table('admins', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('admins')->cascadeOnDelete();
            $table->foreign('deleted_by')->references('id')->on('admins')->cascadeOnDelete();
            $table->foreign('updated_by')->references('id')->on('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['updated_by']);
        });
        Schema::dropIfExists('admins');
    }
};
