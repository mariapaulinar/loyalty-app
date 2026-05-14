<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent Keys Table
 *
 * Stores API keys for the Agent Layer — a parallel authentication system
 * to Sanctum's personal_access_tokens. Keys are long-lived, scoped, and
 * polymorphically owned by Admin, Partner, or Member users.
 *
 * Security Model:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - key_hash: bcrypt hash — raw key is NEVER stored and shown only once
 * - key_prefix: First N characters of the raw key for display and DB lookup
 * - Prefix-based candidate narrowing → bcrypt verification (no full-table scans)
 *
 * Ownership:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - Polymorphic via owner_type/owner_id (same pattern as personal_access_tokens)
 * - Owner types: App\Models\Admin, App\Models\Partner, App\Models\Member
 *
 * @see App\Models\AgentKey
 * @see RewardLoyalty-100-agent.md §2
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_keys', function (Blueprint $table) {
            // ─────────────────────────────────────────────────────────────────
            // PRIMARY KEY
            // ─────────────────────────────────────────────────────────────────
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // POLYMORPHIC OWNERSHIP
            // ─────────────────────────────────────────────────────────────────
            // Same pattern as personal_access_tokens.tokenable_type/tokenable_id
            $table->uuidMorphs('owner'); // Creates owner_type (string) + owner_id (uuid)

            // ─────────────────────────────────────────────────────────────────
            // KEY IDENTITY
            // ─────────────────────────────────────────────────────────────────

            // Human-readable label: "Zapier Integration", "My Webshop"
            $table->string('name');

            // First N chars of raw key for display & efficient lookup
            // Format: rl_admin_A1b2C3d4 | rl_agent_A1b2C3d4 | rl_member_A1b2C3d4
            $table->string('key_prefix', 18);

            // bcrypt hash of the full raw key
            // Raw key is shown once at creation and never stored
            $table->string('key_hash');

            // ─────────────────────────────────────────────────────────────────
            // PERMISSIONS & LIMITS
            // ─────────────────────────────────────────────────────────────────

            // JSON array of scope strings: ["read", "write:transactions"]
            $table->json('scopes');

            // Per-key RPM limit; default 60 (can be adjusted per key)
            $table->unsignedInteger('rate_limit')->default(60);

            // ─────────────────────────────────────────────────────────────────
            // LIFECYCLE
            // ─────────────────────────────────────────────────────────────────

            // Debounced: only updated if >5 min since last write
            $table->dateTime('last_used_at')->nullable();

            // Null = never expires; set for member keys (90-day default)
            $table->dateTime('expires_at')->nullable();

            // Soft-disable: false = revoked (preserves audit trail)
            $table->boolean('is_active')->default(true);

            // Arbitrary metadata (reserved for future use: webhook URLs, etc.)
            $table->json('meta')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // AUDIT
            // ─────────────────────────────────────────────────────────────────

            // CRUD framework fields — DataService always sets these on insert/update
            // No FK constraint because owner can be Admin, Partner, or Member
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();

            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            // Prefix lookup: AuthenticateAgent narrows candidates by prefix
            $table->index('key_prefix', 'agent_keys_prefix_idx');

            // Owner dashboard: "show my keys" queries
            // (owner_type + owner_id index created automatically by uuidMorphs)

            // Cleanup: find expired or inactive keys
            $table->index('is_active', 'agent_keys_active_idx');
            $table->index('expires_at', 'agent_keys_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_keys');
    }
};
