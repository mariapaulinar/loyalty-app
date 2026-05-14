<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Club Integrations Table
 *
 * This table stores connections between loyalty clubs and external e-commerce
 * platforms (Shopify, WooCommerce, etc.). Each record represents a single
 * integration instance — one club can have multiple integrations (e.g., separate
 * Shopify stores for different regions).
 *
 * Core Responsibilities:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - Store OAuth credentials (encrypted access tokens)
 * - Track integration health and status
 * - Provide webhook verification secrets
 * - Hold platform-specific configuration
 *
 * Security Model:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - access_token: Encrypted at application layer (Laravel's encrypted cast)
 * - webhook_secret: Per-integration HMAC secret for webhook verification
 * - public_api_key: Safe to expose in client-side code (prefixed rl_pub_)
 *
 * Unique Constraints:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - (club_id, platform, store_identifier): Prevents duplicate integrations
 * - public_api_key: Globally unique for widget authentication
 *
 * @see App\Models\ClubIntegration
 * @see App\Enums\IntegrationPlatform
 * @see App\Enums\IntegrationStatus
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_integrations', function (Blueprint $table) {
            // ─────────────────────────────────────────────────────────────────
            // PRIMARY KEY
            // ─────────────────────────────────────────────────────────────────

            // UUID for globally unique, non-sequential identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // OWNERSHIP
            // ─────────────────────────────────────────────────────────────────

            // The club (loyalty program) this integration belongs to
            // Cascade delete: integration removed when club deleted
            $table->foreignUuid('club_id')->constrained('clubs')->cascadeOnDelete();

            // ─────────────────────────────────────────────────────────────────
            // PLATFORM IDENTIFICATION
            // ─────────────────────────────────────────────────────────────────

            // E-commerce platform type (shopify, woocommerce, etc.)
            // @see App\Enums\IntegrationPlatform
            $table->string('platform');

            // Integration lifecycle state
            // @see App\Enums\IntegrationStatus for state machine
            $table->string('status')->default('pending');

            // Platform-specific store identifier
            // Shopify: mystore.myshopify.com
            // WooCommerce: Site URL or API key
            $table->string('store_identifier')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // AUTHENTICATION CREDENTIALS
            // ─────────────────────────────────────────────────────────────────

            // OAuth access token — ENCRYPTED at application layer
            // Never exposed in API responses or logs
            $table->text('access_token')->nullable();

            // Per-integration webhook verification secret
            // Used for HMAC signature validation on incoming webhooks
            // Auto-generated on creation (Str::random(32))
            $table->string('webhook_secret')->nullable();

            // Public API key for widget/frontend authentication
            // Safe to expose in client-side code
            // Format: rl_pub_XXXXXXXXXXXXXXXXXXXXXXXX
            $table->string('public_api_key')->unique();

            // ─────────────────────────────────────────────────────────────────
            // CONFIGURATION
            // ─────────────────────────────────────────────────────────────────

            // Integration-specific settings as JSON
            // NOTE: Primary settings live in SettingsService (namespaced)
            // This JSON is for platform-specific overrides only
            $table->json('settings')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // HEALTH MONITORING
            // ─────────────────────────────────────────────────────────────────

            // Last successful data sync timestamp
            // Updated after successful webhook processing or manual sync
            $table->dateTime('last_sync_at')->nullable();

            // Last error message (API failure, webhook processing error, etc.)
            $table->text('last_error')->nullable();

            // When the last error occurred
            $table->dateTime('last_error_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // AUDIT TRAIL
            // ─────────────────────────────────────────────────────────────────

            // Soft deletes preserve integration history for audit
            // Allows "reconnect" without losing historical data
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES FOR QUERY PERFORMANCE
            // ─────────────────────────────────────────────────────────────────

            // Prevent duplicate integrations for the same store
            // One club cannot connect the same Shopify store twice
            $table->unique(
                ['club_id', 'platform', 'store_identifier'],
                'club_integrations_club_platform_store_unique'
            );

            // Club dashboard: list all integrations for a club
            $table->index(
                ['club_id', 'platform'],
                'club_integrations_club_platform_idx'
            );

            // Admin dashboard: filter by integration health
            $table->index('status', 'club_integrations_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_integrations');
    }
};
