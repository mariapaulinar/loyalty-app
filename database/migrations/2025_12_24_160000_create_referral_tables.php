<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Referral System Tables - Campaign-Centric Architecture
 *
 * This migration creates the necessary tables for the member-to-member referral system.
 * The system is CAMPAIGN-CENTRIC, not club-centric. Members don't "join clubs" - they
 * receive points on specific CARDS through referral campaigns.
 *
 * Tables Created:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - referral_settings: Referral campaigns with reward card configurations
 * - member_referral_codes: Unique codes assigned to members for specific campaigns
 * - referrals: Records of successful or pending referrals between members
 *
 * Key Architecture Decisions:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - NO club_id on referral_settings: Campaigns are card-based, not club-based
 * - referral_setting_id on codes: Codes are campaign-specific
 * - Cross-club rewards allowed: Referrer and referee cards can be from different clubs
 *
 * @see \App\Models\ReferralSetting
 * @see \App\Models\MemberReferralCode
 * @see \App\Models\Referral
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
        // 1. Referral Settings Table (Campaigns)
        Schema::create('referral_settings', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Campaign Info
            $table->string('name'); // Campaign name (internal use)
            $table->text('description')->nullable(); // Optional description

            // Ownership
            $table->foreignUuid('created_by')->constrained('partners')->cascadeOnDelete();

            // Configuration
            $table->boolean('is_enabled')->default(false);

            // Reward Configuration (Referrer)
            $table->unsignedInteger('referrer_points')->default(0);
            $table->foreignUuid('referrer_card_id')->constrained('cards')->cascadeOnDelete();

            // Reward Configuration (Referee)
            $table->unsignedInteger('referee_points')->default(0);
            $table->foreignUuid('referee_card_id')->constrained('cards')->cascadeOnDelete();

            // Timestamps
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        // 2. Member Referral Codes Table
        Schema::create('member_referral_codes', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Relationships
            $table->foreignUuid('referral_setting_id')->constrained('referral_settings')->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained('members')->cascadeOnDelete();

            // Unique: One code per member per campaign
            $table->unique(['referral_setting_id', 'member_id']);

            // The Code
            $table->string('code', 8)->unique(); // e.g. "AB12CD34"

            // Stats (Denormalized for performance)
            $table->unsignedInteger('referral_count')->default(0); // Total referrals initiated
            $table->unsignedInteger('successful_count')->default(0); // Completed referrals
            $table->unsignedInteger('points_earned')->default(0); // Total points earned from referrals

            // Timestamps
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        // 3. Referrals Tracking Table
        Schema::create('referrals', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Context
            $table->foreignUuid('referral_code_id')->constrained('member_referral_codes')->cascadeOnDelete();

            // Participants
            $table->foreignUuid('referrer_id')->constrained('members')->cascadeOnDelete();
            $table->foreignUuid('referee_id')->constrained('members')->cascadeOnDelete();

            // State Machine
            $table->enum('status', ['pending', 'completed', 'expired'])->default('pending');
            $table->dateTime('signed_up_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            // Transaction Linkage (Audit Trail)
            $table->foreignUuid('referrer_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignUuid('referee_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();

            // Timestamps
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // Constraints & Indexes
            // A member can only be referred ONCE per campaign
            $table->unique(['referral_code_id', 'referee_id']);

            $table->index(['referee_id', 'status']);
            $table->index('referrer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('member_referral_codes');
        Schema::dropIfExists('referral_settings');
    }
};
