<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Clubs Table
 *
 * Clubs represent brands or business units within a partner account.
 * A partner can have multiple clubs (e.g., different store brands, locations).
 *
 * Use cases:
 * - Multi-brand: Partner operates "Coffee Shop" and "Bakery" as separate clubs
 * - Multi-location: Each physical location as its own club
 * - Single business: One club for simple setups
 *
 * Cards, staff, and rewards belong to clubs, enabling brand-level isolation.
 *
 * @see App\Models\Club
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            // UUID primary key for globally unique identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // CLUB IDENTITY
            // ─────────────────────────────────────────────────────────────────

            // Club/brand name displayed to members
            $table->string('name', 96)->nullable();
            $table->text('description')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // LOCALIZATION
            // ─────────────────────────────────────────────────────────────────

            // Club-level defaults; can differ from partner (e.g., regional brands)
            $table->string('locale', 12)->nullable();
            $table->char('currency', 3)->nullable();
            $table->string('time_zone', 48)->nullable();

            // ─────────────────────────────────────────────────────────────────
            // CLUB FLAGS
            // ─────────────────────────────────────────────────────────────────

            // Deactivate to hide all club content from members
            $table->boolean('is_active')->default(true);
            // Primary club is default for partner operations
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_undeletable')->default(false);
            $table->boolean('is_uneditable')->default(false);

            // ─────────────────────────────────────────────────────────────────
            // EXTENSIBLE METADATA
            // ─────────────────────────────────────────────────────────────────

            // Store: branding, feature toggles, business hours, address
            $table->json('meta')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // AUDIT TRAIL
            // ─────────────────────────────────────────────────────────────────

            // Clubs are created by partners
            $table->foreignUuid('created_by')->nullable()->constrained('partners')->cascadeOnDelete();
            $table->foreignUuid('deleted_by')->nullable()->constrained('partners')->cascadeOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('partners')->nullOnDelete();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            $table->index('is_active', 'clubs_is_active_idx');
            $table->index('is_primary', 'clubs_is_primary_idx');
            // Combined for partner-level club queries
            $table->index(['is_active', 'is_primary'], 'clubs_active_primary_idx');
            // Audit indexes
            $table->index('created_by', 'clubs_created_by_idx');
            $table->index('deleted_by', 'clubs_deleted_by_idx');
            $table->index('updated_by', 'clubs_updated_by_idx');
        });
    }

    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['updated_by']);
        });
        Schema::dropIfExists('clubs');
    }
};
