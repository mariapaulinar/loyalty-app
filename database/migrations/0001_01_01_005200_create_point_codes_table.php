<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Point Codes Table
 *
 * Short alphanumeric codes that staff generate for offline point redemption.
 * Members enter these codes instead of scanning QR codes.
 *
 * Use cases:
 * - Phone orders where QR scanning isn't possible
 * - Staff doesn't have scanner/app available
 * - Promotional giveaway codes
 * - Drive-through or delivery scenarios
 *
 * Code lifecycle:
 * 1. Staff generates 4-digit code with point value
 * 2. Staff verbally gives code to member
 * 3. Member enters code in app/website
 * 4. Points are credited to member's account
 *
 * @see App\Models\PointCode
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_codes', function (Blueprint $table) {
            // UUID primary key for globally unique identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // CODE OWNERSHIP
            // ─────────────────────────────────────────────────────────────────

            // Staff member who created this code
            $table->foreignUuid('staff_id')->constrained('staff')->cascadeOnDelete();
            // Optional: restrict code to specific card (null = any card)
            $table->foreignUuid('card_id')->nullable()->constrained('cards')->cascadeOnDelete();

            // ─────────────────────────────────────────────────────────────────
            // CODE DETAILS
            // ─────────────────────────────────────────────────────────────────

            // 4-digit alphanumeric code (easy to communicate verbally)
            $table->string('code', 4)->unique();
            // Points awarded when code is redeemed
            $table->unsignedInteger('points');

            // ─────────────────────────────────────────────────────────────────
            // USAGE LIMITS
            // ─────────────────────────────────────────────────────────────────

            // Code can be deactivated manually
            $table->boolean('is_active')->default(true);
            // Total redemptions allowed (1 = single-use, null = unlimited)
            $table->unsignedInteger('max_uses')->default(1);
            // Current redemption count
            $table->unsignedInteger('times_redeemed')->default(0);
            // Per-member limit (1 = once per member)
            $table->unsignedInteger('max_uses_per_member')->default(1);
            // Code expiration (null = never expires)
            $table->dateTime('expires_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // REDEMPTION TRACKING (for single-use codes)
            // ─────────────────────────────────────────────────────────────────

            // Last member to use this code
            $table->foreignUuid('used_by')->nullable()->constrained('members')->nullOnDelete();
            $table->dateTime('used_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // AUDIT TRAIL
            // ─────────────────────────────────────────────────────────────────

            $table->foreignUuid('created_by')->nullable()->constrained('staff')->cascadeOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            $table->index('staff_id', 'point_codes_staff_id_idx');
            $table->index('card_id', 'point_codes_card_id_idx');
            $table->index('used_by', 'point_codes_used_by_idx');

            // Active codes lookup
            $table->index('is_active', 'point_codes_is_active_idx');

            // Combined for active code queries
            $table->index(['is_active', 'expires_at'], 'point_codes_active_expiry_idx');
            $table->index(['card_id', 'is_active'], 'point_codes_card_active_idx');

            // Audit indexes
            $table->index('created_by', 'point_codes_created_by_idx');
            $table->index('updated_by', 'point_codes_updated_by_idx');
        });
    }

    public function down(): void
    {
        Schema::table('point_codes', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
            $table->dropForeign(['card_id']);
            $table->dropForeign(['used_by']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });
        Schema::dropIfExists('point_codes');
    }
};
