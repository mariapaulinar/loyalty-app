<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Sessions Table (Laravel Core)
 *
 * Database-backed session storage for web users.
 * Enables session management across multiple servers (load balancing).
 *
 * Benefits over file sessions:
 * - Horizontal scaling across multiple app servers
 * - Session visibility in database for debugging
 * - Easy cleanup of stale sessions via queries
 * - User device tracking (IP, user agent)
 *
 * @see config/session.php - Set driver to 'database'
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            // Session identifier (stored in cookie)
            $table->string('id')->primary();

            // User association (null = guest session)
            $table->foreignId('user_id')->nullable();

            // Request context for device tracking / security
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Session data (serialized PHP array)
            $table->longText('payload');

            // Unix timestamp of last activity (for GC)
            $table->integer('last_activity');

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            // User session lookup (view all devices)
            $table->index('user_id', 'sessions_user_id_idx');
            // Garbage collection queries
            $table->index('last_activity', 'sessions_last_activity_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
