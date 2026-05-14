<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Member-Voucher Pivot Table
 *
 * Tracks which members have claimed which vouchers.
 * A member can claim multiple vouchers, and a voucher can be claimed by one member.
 *
 * When member claims a voucher from a batch:
 * 1. Record is created in this pivot table
 * 2. Voucher becomes visible in member's wallet
 * 3. Member can redeem the voucher at partner location
 *
 * @see App\Models\Voucher::members()
 * @see App\Models\Member::vouchers()
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_voucher', function (Blueprint $table) {
            // The member who claimed the voucher
            $table->foreignUuid('member_id')->constrained('members')->cascadeOnDelete();
            // The voucher that was claimed
            $table->foreignUuid('voucher_id')->constrained('vouchers')->cascadeOnDelete();

            // How the voucher was claimed
            $table->enum('claimed_via', ['qr_scan', 'link', 'email', 'manual'])->nullable();
            
            // Timestamps track when voucher was claimed
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            // Foreign key indexes
            $table->index('member_id', 'member_voucher_member_id_idx');
            $table->index('voucher_id', 'member_voucher_voucher_id_idx');

            // Prevent duplicate claims (one member can only claim a specific voucher once)
            $table->unique(['member_id', 'voucher_id'], 'member_voucher_unique');

            // Recent claims for analytics
            $table->index('created_at', 'member_voucher_created_at_idx');
            
            // Query by claim method
            $table->index('claimed_via', 'member_voucher_claimed_via_idx');
        });
    }

    public function down(): void
    {
        Schema::table('member_voucher', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropForeign(['voucher_id']);
        });
        Schema::dropIfExists('member_voucher');
    }
};
