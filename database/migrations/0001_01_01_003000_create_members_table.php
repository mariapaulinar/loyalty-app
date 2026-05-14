<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Members Table
 *
 * Members are end-users who participate in loyalty programs.
 * They collect points, redeem rewards, and interact with loyalty cards.
 *
 * Members can:
 * - Sign up via web, mobile app, or in-store
 * - Collect points through purchases (QR scan by staff)
 * - Redeem points for rewards
 * - View their loyalty card balance and history
 * - Receive marketing communications (if opted in)
 *
 * Member data is crucial for analytics, segmentation, and personalization.
 *
 * @see App\Models\Member
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            // UUID primary key for globally unique identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // REFERRAL TRACKING
            // ─────────────────────────────────────────────────────────────────

            // Track which affiliate referred this member (for commissions)
            $table->foreignUuid('affiliate_id')->nullable()->constrained('affiliates')->nullOnDelete();

            // ─────────────────────────────────────────────────────────────────
            // ACCOUNT INFORMATION
            // ─────────────────────────────────────────────────────────────────

            $table->tinyInteger('role')->default(1)->comment('1=Standard Member');
            // Human-readable member number for card display (xxx-xxx-xxx-xxx)
            $table->string('member_number', 32)->nullable()->unique();
            // System identifier for QR codes and URLs
            $table->string('unique_identifier', 32)->nullable()->unique();
            // Public display name (nickname)
            $table->string('display_name', 64)->nullable();
            // Full legal name for receipts/formal communications
            $table->string('name', 128)->nullable();
            // Primary login identifier
            $table->string('email', 128)->unique();
            $table->dateTime('email_verified_at')->nullable();
            // Nullable password allows passwordless/OTP login
            $table->string('password')->nullable();
            $table->rememberToken();

            // ─────────────────────────────────────────────────────────────────
            // DEMOGRAPHICS (for segmentation & personalization)
            // ─────────────────────────────────────────────────────────────────

            // Birthday for birthday rewards and age-based targeting
            $table->date('birthday')->nullable();
            // Gender: 0=Unknown, 1=Male, 2=Female, 3=Non-binary, 4=Prefer not to say
            $table->tinyInteger('gender')->default(0);

            // ─────────────────────────────────────────────────────────────────
            // TWO-FACTOR AUTHENTICATION
            // ─────────────────────────────────────────────────────────────────

            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->string('two_factor_recovery_codes')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // ACCOUNT LIFECYCLE
            // ─────────────────────────────────────────────────────────────────

            // Account expiration (e.g., inactive accounts cleanup)
            $table->dateTime('account_expires_at')->nullable();
            // Premium member status (for paid membership tiers)
            $table->dateTime('premium_expires_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // LOCALIZATION
            // ─────────────────────────────────────────────────────────────────

            $table->string('locale', 12)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->string('time_zone', 48)->nullable();

            // ─────────────────────────────────────────────────────────────────
            // PHONE (for OTP, SMS notifications)
            // ─────────────────────────────────────────────────────────────────

            $table->string('phone_prefix', 4)->nullable();
            $table->string('phone_country', 2)->nullable();
            $table->string('phone', 24)->nullable();
            // E.164 format for reliable SMS delivery
            $table->string('phone_e164', 24)->nullable();

            // ─────────────────────────────────────────────────────────────────
            // ACCOUNT FLAGS
            // ─────────────────────────────────────────────────────────────────

            $table->boolean('is_active')->default(true);
            // VIP flag for special treatment (manual or tier-based)
            $table->boolean('is_vip')->default(false);
            // Marketing consent flags (GDPR compliance)
            $table->boolean('accepts_emails')->default(false);
            $table->boolean('accepts_text_messages')->default(false);
            $table->boolean('is_undeletable')->default(false);
            $table->boolean('is_uneditable')->default(false);

            // ─────────────────────────────────────────────────────────────────
            // ENGAGEMENT METRICS
            // ─────────────────────────────────────────────────────────────────

            $table->unsignedInteger('number_of_times_logged_in')->default(0);
            $table->dateTime('last_login_at')->nullable();
            $table->unsignedInteger('number_of_emails_received')->default(0);
            $table->unsignedInteger('number_of_text_messages_received')->default(0);
            $table->unsignedInteger('number_of_reviews_written')->default(0);
            $table->unsignedInteger('number_of_ratings_given')->default(0);

            // ─────────────────────────────────────────────────────────────────
            // EXTENSIBLE METADATA
            // ─────────────────────────────────────────────────────────────────

            // Store: custom attributes, app preferences, device info
            $table->json('meta')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // AUDIT TRAIL
            // ─────────────────────────────────────────────────────────────────

            // Members can be created by admins or self-register (null)
            $table->foreignUuid('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignUuid('deleted_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES FOR QUERY PERFORMANCE
            // ─────────────────────────────────────────────────────────────────

            // Affiliate performance tracking
            $table->index('affiliate_id', 'members_affiliate_id_idx');
            // Active member queries
            $table->index('is_active', 'members_is_active_idx');
            // VIP member filtering
            $table->index('is_vip', 'members_is_vip_idx');
            // Phone lookup for OTP/SMS
            $table->index('phone_e164', 'members_phone_idx');
            // Activity tracking
            $table->index('last_login_at', 'members_last_login_idx');
            // Registration date for cohort analysis
            $table->index('created_at', 'members_created_at_idx');
            // Combined indexes for common queries
            $table->index(['is_active', 'created_at'], 'members_active_created_idx');
            $table->index(['is_active', 'email_verified_at'], 'members_active_verified_idx');
            $table->index(['accepts_emails', 'is_active'], 'members_email_consent_idx');
            // Audit indexes
            $table->index('created_by', 'members_created_by_idx');
            $table->index('deleted_by', 'members_deleted_by_idx');
            $table->index('updated_by', 'members_updated_by_idx');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['affiliate_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['updated_by']);
        });
        Schema::dropIfExists('members');
    }
};
