<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Networks Table
 *
 * Networks are the top-level organizational unit for multi-tenant deployments.
 * Each network can have multiple partners (businesses) and operates independently.
 *
 * Use cases:
 * - Single network: One business with multiple locations/brands
 * - Multi-network: Agency managing multiple independent businesses
 * - White-label: Resellers with their own branded networks
 *
 * @see App\Models\Network
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('networks', function (Blueprint $table) {
            // UUID primary key for globally unique identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // NETWORK IDENTITY
            // ─────────────────────────────────────────────────────────────────

            // Internal name for admin reference
            $table->string('name', 96)->nullable();
            $table->text('description')->nullable();

            // Custom domain for white-label deployments (e.g., loyalty.client.com)
            $table->string('host', 250)->nullable();
            // URL-friendly slug for multi-tenant routing
            $table->string('slug', 250)->unique()->nullable();

            // ─────────────────────────────────────────────────────────────────
            // DEFAULT LOCALIZATION
            // ─────────────────────────────────────────────────────────────────

            // Defaults inherited by partners unless overridden
            $table->string('locale', 12)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->string('time_zone', 48)->nullable();

            // ─────────────────────────────────────────────────────────────────
            // NETWORK FLAGS
            // ─────────────────────────────────────────────────────────────────

            // Deactivate to suspend all partners in network
            $table->boolean('is_active')->default(true);
            // Prevent deletion of primary/system networks
            $table->boolean('is_undeletable')->default(false);
            $table->boolean('is_uneditable')->default(false);
            // Mark as primary for default routing
            $table->boolean('is_primary')->default(false);

            // ─────────────────────────────────────────────────────────────────
            // EXTENSIBLE METADATA
            // ─────────────────────────────────────────────────────────────────

            // Store custom attributes: branding, limits, feature flags
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

            // Active networks lookup
            $table->index('is_active', 'networks_is_active_idx');
            // Primary network resolution
            $table->index('is_primary', 'networks_is_primary_idx');
            // Host-based routing
            $table->index('host', 'networks_host_idx');
            // Audit trail indexes
            $table->index('created_by', 'networks_created_by_idx');
            $table->index('deleted_by', 'networks_deleted_by_idx');
            $table->index('updated_by', 'networks_updated_by_idx');
        });
    }

    public function down(): void
    {
        Schema::table('networks', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['updated_by']);
        });
        Schema::dropIfExists('networks');
    }
};
