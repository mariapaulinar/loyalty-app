<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * OTP Codes Table
 *
 * Stores one-time passwords for passwordless authentication and verification.
 * Supports multiple identifier types (email, phone) and purposes (login, verify_email, etc.).
 *
 * Security Features:
 * - Cryptographically secure 6-digit codes
 * - Time-based expiration (configurable, default 10 minutes)
 * - Rate limiting via attempts column
 * - Request context logging (IP, user agent)
 * - Support for multiple guards (member, staff, partner, admin)
 *
 * Future-Ready:
 * - Phone/SMS support (identifier_type = 'phone')
 * - Additional purposes (password_reset, verify_phone, etc.)
 *
 * @see App\Models\OtpCode
 * @see App\Services\Auth\OtpService
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            // UUID primary key for globally unique identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // IDENTIFIER (who is this OTP for)
            // ─────────────────────────────────────────────────────────────────

            // The email or phone number this OTP was sent to
            $table->string('identifier', 255);
            // Type of identifier: 'email' or 'phone' (future SMS support)
            $table->string('identifier_type', 16)->default('email');

            // ─────────────────────────────────────────────────────────────────
            // OTP CODE
            // ─────────────────────────────────────────────────────────────────

            // The 6-digit OTP code (hashed for security)
            $table->string('code', 255);
            // Purpose: 'login', 'verify_email', 'verify_phone', 'password_reset'
            $table->string('purpose', 32)->default('login');
            // Guard: 'member', 'staff', 'partner', 'admin'
            $table->string('guard', 16)->default('member');

            // ─────────────────────────────────────────────────────────────────
            // SECURITY & RATE LIMITING
            // ─────────────────────────────────────────────────────────────────

            // Number of failed verification attempts
            $table->unsignedTinyInteger('attempts')->default(0);
            // Maximum allowed attempts before lockout
            $table->unsignedTinyInteger('max_attempts')->default(5);
            // Whether this OTP has been successfully verified
            $table->boolean('is_verified')->default(false);
            // When the OTP was verified (null if not yet)
            $table->dateTime('verified_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // EXPIRATION
            // ─────────────────────────────────────────────────────────────────

            // When this OTP expires (typically 10 minutes from creation)
            // Nullable for MySQL strict mode compatibility (always set by application)
            $table->dateTime('expires_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // REQUEST CONTEXT (for audit & anomaly detection)
            // ─────────────────────────────────────────────────────────────────

            // IP address of the request that generated this OTP
            $table->string('ip_address', 45)->nullable();
            // User agent of the request
            $table->text('user_agent')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // TIMESTAMPS
            // ─────────────────────────────────────────────────────────────────

            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES FOR QUERY PERFORMANCE
            // ─────────────────────────────────────────────────────────────────

            // Primary lookup: find active OTP for identifier + purpose
            $table->index(['identifier', 'purpose', 'expires_at'], 'otp_codes_lookup_idx');
            // Guard-specific queries
            $table->index(['guard', 'identifier'], 'otp_codes_guard_identifier_idx');
            // Cleanup queries: find expired OTPs
            $table->index('expires_at', 'otp_codes_expires_at_idx');
            // Verification status
            $table->index('is_verified', 'otp_codes_verified_idx');
            // Rate limiting by identifier
            $table->index(['identifier', 'created_at'], 'otp_codes_rate_limit_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
