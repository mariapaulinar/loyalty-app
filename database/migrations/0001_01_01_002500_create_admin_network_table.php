<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Admin-Network Pivot Table
 *
 * Associates admins with networks they can manage.
 * Super Admins have access to all networks; Managers are scoped to assigned networks.
 *
 * Access control:
 * - Role 1 (Super Admin): Implied access to all networks
 * - Role 2 (Manager): Only networks in this pivot table
 *
 * @see App\Models\Admin::networks()
 * @see App\Models\Network::admins()
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_network', function (Blueprint $table) {
            $table->foreignUuid('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->foreignUuid('network_id')->constrained('networks')->cascadeOnDelete();

            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            // Foreign key indexes
            $table->index('admin_id', 'admin_network_admin_id_idx');
            $table->index('network_id', 'admin_network_network_id_idx');

            // Prevent duplicate assignments
            $table->unique(['admin_id', 'network_id'], 'admin_network_unique');
        });
    }

    public function down(): void
    {
        Schema::table('admin_network', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropForeign(['network_id']);
        });
        Schema::dropIfExists('admin_network');
    }
};
