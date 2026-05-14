<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2026 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Migration: Add Interaction Tracking for Anonymous Members
 *
 * Purpose:
 * Tracks when members first interact with the platform (earn points, stamps,
 * claim vouchers, etc.). This differentiates between:
 *
 * - GHOST MEMBERS: Created but never interacted (can be purged after 6 months)
 * - ACTIVE ANONYMOUS: Have interacted but no email (valuable users)
 * - REGISTERED: Have email linked (full accounts)
 *
 * Benefits:
 * - Database cleanup: Purge ghost members that never engaged
 * - Analytics: Track conversion from visitor → active → registered
 * - GDPR: Only retain data for genuinely interested users
 *
 * @see App\Models\Member::hasInteracted()
 * @see App\Models\Member::scopeGhost()
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
            // INTERACTION TRACKING
            // ─────────────────────────────────────────────────────────────

            // Timestamp of first meaningful interaction.
            // Set when member first:
            // - Earns points, stamps, or vouchers
            // - Claims a reward
            // - Follows a card
            // - Makes any transaction
            //
            // NULL = Never interacted (ghost member)
            // NOT NULL = Active user (has engaged with the platform)
            $table->dateTime('first_interaction_at')
                ->nullable()
                ->after('device_uuid')
                ->index()
                ->comment('Timestamp of first meaningful interaction (null = ghost)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('first_interaction_at');
        });
    }
};
