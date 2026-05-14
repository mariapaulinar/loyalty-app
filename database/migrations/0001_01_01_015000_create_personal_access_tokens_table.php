<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Personal Access Tokens Table (Laravel Sanctum)
 *
 * Stores API tokens for authenticated users across all user types.
 * Powers mobile app authentication, third-party integrations, and SPA sessions.
 *
 * Token capabilities:
 * - Scoped abilities (e.g., "read:transactions", "write:members")
 * - Expiration dates for temporary access
 * - Usage tracking for security monitoring
 *
 * @see https://laravel.com/docs/sanctum
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Polymorphic relation to any user type (Admin, Partner, Member, etc.) - UUID
            $table->uuidMorphs('tokenable');

            // Token identity
            $table->string('name')->comment('Token description for user reference');
            $table->string('token', 64)->unique()->comment('SHA-256 hashed token');

            // Permissions and lifecycle
            $table->text('abilities')->nullable()->comment('JSON array of allowed actions');
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('expires_at')->nullable();

            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            // Expiration cleanup queries
            $table->index('expires_at', 'pat_expires_at_idx');
            // Activity monitoring
            $table->index('last_used_at', 'pat_last_used_idx');

            // Note: morphs() creates index on (tokenable_type, tokenable_id)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
