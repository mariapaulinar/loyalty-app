<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Table: reward_loyalty_updates
 *
 * Purpose:
 * Track all update attempts for Reward Loyalty. Provides a complete audit trail
 * of version upgrades, including success/failure status, timing, and rollback
 * information.
 *
 * Design Tenets:
 * - **Complete history**: Every update attempt is recorded
 * - **Rollback support**: Backup paths stored for recovery
 * - **Debugging friendly**: Error traces captured for troubleshooting
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_loyalty_updates', function (Blueprint $table) {
            $table->id();

            // Version Information
            $table->string('from_version');
            $table->string('to_version');

            // Update Process Status
            $table->enum('status', [
                'pending',
                'downloading',
                'extracting',
                'migrating',
                'completed',
                'failed',
                'rolled_back',
            ])->default('pending');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();

            // Package Information
            // URL can be very long (encrypted download tokens can exceed 400 chars)
            $table->text('package_url')->nullable();
            $table->string('package_hash', 64)->nullable(); // SHA-256 = 64 chars
            $table->bigInteger('package_size')->nullable(); // bytes

            // Backup Information
            $table->string('backup_path')->nullable();
            $table->boolean('backup_kept')->default(false);

            // Error Tracking
            $table->text('error_message')->nullable();
            $table->text('error_trace')->nullable();

            // Metadata
            $table->uuid('initiated_by')->nullable(); // Admin ID (UUID)
            $table->string('ip_address')->nullable();
            $table->json('metadata')->nullable();

            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // Indexes
            $table->index('status');
            $table->index('created_at');
            $table->index(['from_version', 'to_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_loyalty_updates');
    }
};
