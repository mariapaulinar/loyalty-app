<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2026 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Migration: Add Anonymous Member Support
 *
 * Purpose:
 * Enables "Brawl Stars" style anonymous-first authentication for members.
 * Members can now use loyalty features without registering an email, identified
 * only by a device-bound code stored in their browser's localStorage.
 *
 * Key Changes:
 * - device_code: Short human-readable code (e.g., "4K7X") for staff lookup
 *                and device switching. Uses safe characters only.
 * - device_uuid: UUID from client localStorage for primary device binding.
 * - email: Made nullable to support anonymous members.
 *
 * Philosophy:
 * "Play first, account later" — Members can immediately earn stamps, collect
 * points, and claim rewards. Email registration becomes optional, primarily for
 * cross-device sync and receiving notifications.
 *
 * @see App\Models\Member
 * @see App\Services\Member\AnonymousMemberService
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // ─────────────────────────────────────────────────────────────
            // DEVICE-BOUND IDENTITY
            // ─────────────────────────────────────────────────────────────

            // Short, human-readable code for device switching and staff lookup.
            // Format: 4-12 chars, uppercase, using safe characters only.
            // Safe chars: A-Z (excluding I, L, O) + 2-9 (excluding 0, 1)
            // Examples: 4K7X, N2PB9H, R9HW4K7X
            $table->string('device_code', 12)
                ->nullable()
                ->unique()
                ->after('unique_identifier')
                ->comment('Anonymous member code for device switching');

            // Device UUID for localStorage binding.
            // Primary method of identifying anonymous members.
            // One member can have multiple devices after linking email.
            $table->uuid('device_uuid')
                ->nullable()
                ->index()
                ->after('device_code')
                ->comment('Primary device UUID from localStorage');
        });

        // ─────────────────────────────────────────────────────────────────
        // MAKE EMAIL NULLABLE
        // ─────────────────────────────────────────────────────────────────
        // Anonymous members have email = NULL.
        // Existing registered members with emails are unaffected.

        Schema::table('members', function (Blueprint $table) {
            $table->string('email', 128)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['device_code', 'device_uuid']);
        });

        // Note: We intentionally do not revert email to non-nullable.
        // Doing so would fail if any anonymous members exist in the database.
        // A clean rollback would require deleting those members first.
    }
};
