<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Points Requests Table
 *
 * Member-generated payment request links for receiving points from others.
 * Similar to payment request QR codes but for loyalty points.
 *
 * Use cases:
 * - Member requests points from another member (peer-to-peer)
 * - Staff creates request link for specific member
 * - Promotional point distribution via shared links
 *
 * Flow:
 * 1. Member generates unique request link
 * 2. Link is shared (QR, URL, messaging)
 * 3. Others scan/click to send points
 * 4. Points transferred to requesting member
 *
 * @see App\Models\PointRequest
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_requests', function (Blueprint $table) {
            // UUID primary key for globally unique identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // REQUEST SCOPE
            // ─────────────────────────────────────────────────────────────────

            // Optional: restrict to specific card (null = any card accepted)
            $table->foreignUuid('card_id')->nullable()->constrained('cards')->cascadeOnDelete();

            // ─────────────────────────────────────────────────────────────────
            // REQUEST IDENTITY
            // ─────────────────────────────────────────────────────────────────

            // Unique identifier for URL (format: xxx-xxx-xxx-xxx)
            $table->string('unique_identifier', 32)->nullable()->unique();

            // ─────────────────────────────────────────────────────────────────
            // REQUEST STATE
            // ─────────────────────────────────────────────────────────────────

            // Can be deactivated to stop accepting points
            $table->boolean('is_active')->default(true);

            // ─────────────────────────────────────────────────────────────────
            // USAGE LIMITS
            // ─────────────────────────────────────────────────────────────────

            // Total uses allowed (null = unlimited)
            $table->unsignedInteger('max_uses')->nullable();
            // Current usage count
            $table->unsignedInteger('usage_count')->default(0);
            // Total points received through this request
            $table->unsignedInteger('points_received')->default(0);
            // Per-sender limit (0 = unlimited)
            $table->unsignedInteger('per_member_limit')->default(0);
            // Request expiration (null = never)
            $table->dateTime('expires_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // ACTIVITY TRACKING
            // ─────────────────────────────────────────────────────────────────

            $table->dateTime('last_transaction_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // AUDIT TRAIL
            // ─────────────────────────────────────────────────────────────────

            // Member who created this request
            $table->foreignUuid('created_by')->nullable()->constrained('members')->cascadeOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('members')->nullOnDelete();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            $table->index('card_id', 'points_requests_card_id_idx');
            $table->index('is_active', 'points_requests_is_active_idx');

            // Active request queries
            $table->index(['is_active', 'expires_at'], 'points_requests_active_expiry_idx');
            $table->index(['card_id', 'is_active'], 'points_requests_card_active_idx');

            // Audit indexes
            $table->index('created_by', 'points_requests_created_by_idx');
            $table->index('updated_by', 'points_requests_updated_by_idx');
        });
    }

    public function down(): void
    {
        Schema::table('points_requests', function (Blueprint $table) {
            $table->dropForeign(['card_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });
        Schema::dropIfExists('points_requests');
    }
};
