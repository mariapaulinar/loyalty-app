<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Analytics Table
 *
 * Event-driven analytics for tracking user engagement and business metrics.
 * Separate from transactions for flexible event tracking without affecting ledger.
 *
 * Tracked events:
 * - card_viewed: Member viewed a loyalty card
 * - reward_viewed: Member viewed a reward
 * - qr_scanned: Staff scanned member QR code
 * - member_registered: New member signup
 * - purchase_completed: Purchase transaction logged
 * - reward_redeemed: Reward redemption completed
 *
 * Use for dashboards, reports, and business intelligence.
 *
 * @see App\Models\Analytic
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics', function (Blueprint $table) {
            // UUID primary key for globally unique identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // EVENT PARTICIPANTS
            // ─────────────────────────────────────────────────────────────────

            // Partner whose analytics this belongs to (required for scoping)
            $table->foreignUuid('partner_id')->constrained('partners')->cascadeOnDelete();
            // Member involved (nullable for anonymous events)
            $table->foreignUuid('member_id')->nullable()->constrained('members')->nullOnDelete();
            // Staff involved (nullable for member-initiated events)
            $table->foreignUuid('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            // Related card (nullable for non-card events)
            $table->foreignUuid('card_id')->nullable()->constrained('cards')->cascadeOnDelete();
            // Related reward (nullable for non-reward events)
            $table->foreignUuid('reward_id')->nullable()->constrained('rewards')->nullOnDelete();

            // ─────────────────────────────────────────────────────────────────
            // EVENT DATA
            // ─────────────────────────────────────────────────────────────────

            // Event type identifier
            $table->string('event', 64)->nullable();
            // User's locale at time of event
            $table->string('locale', 12)->nullable();
            // Currency of any monetary value
            $table->char('currency', 3)->nullable();
            // Purchase amount if applicable (in smallest currency unit)
            $table->unsignedBigInteger('purchase_amount')->nullable();
            // Points involved if applicable
            $table->integer('points')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // EXTENSIBLE METADATA
            // ─────────────────────────────────────────────────────────────────

            // Store: device info, location, UTM params, custom dimensions
            $table->json('meta')->nullable();

            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES FOR TIME-SERIES ANALYTICS
            // ─────────────────────────────────────────────────────────────────

            $table->index('partner_id', 'analytics_partner_id_idx');
            $table->index('member_id', 'analytics_member_id_idx');
            $table->index('staff_id', 'analytics_staff_id_idx');
            $table->index('card_id', 'analytics_card_id_idx');
            $table->index('reward_id', 'analytics_reward_id_idx');
            $table->index('event', 'analytics_event_idx');

            // Time-series queries (most common pattern)
            $table->index(['created_at', 'event'], 'analytics_date_event_idx');
            $table->index('created_at', 'analytics_created_at_idx');

            // Partner-level analytics dashboard
            $table->index(['partner_id', 'created_at'], 'analytics_partner_date_idx');
            $table->index(['partner_id', 'event', 'created_at'], 'analytics_partner_event_idx');

            // Card performance reporting
            $table->index(['card_id', 'event', 'created_at'], 'analytics_card_event_date_idx');

            // Member journey analysis
            $table->index(['member_id', 'created_at'], 'analytics_member_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('analytics', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->dropForeign(['member_id']);
            $table->dropForeign(['staff_id']);
            $table->dropForeign(['card_id']);
            $table->dropForeign(['reward_id']);
        });
        Schema::dropIfExists('analytics');
    }
};
