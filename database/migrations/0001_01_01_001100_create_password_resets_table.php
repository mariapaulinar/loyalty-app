<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Password Resets Table
 *
 * Stores temporary tokens for password reset requests.
 * Tokens are single-use and expire after a configurable time (config/auth.php).
 *
 * Flow:
 * 1. User requests password reset
 * 2. Token generated and stored here
 * 3. Reset link emailed to user
 * 4. User clicks link, submits new password with token
 * 5. Token validated and removed after successful reset
 *
 * @see https://laravel.com/docs/passwords
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_resets', function (Blueprint $table) {
            // User's email address (not foreign key to support multiple guards)
            $table->string('email');
            // Hashed reset token
            $table->string('token');
            // When request was created (for expiration check)
            $table->dateTime('created_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            // Email lookup for rate limiting and validation
            $table->index('email', 'password_resets_email_idx');
            // Token expiration cleanup
            $table->index('created_at', 'password_resets_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
