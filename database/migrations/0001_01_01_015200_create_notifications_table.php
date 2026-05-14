<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Notifications Table
 *
 * Laravel's database notification driver storage.
 * Stores notifications for all user types (admins, partners, staff, members).
 *
 * Uses polymorphic relationship (notifiable_type, notifiable_id) to support
 * notifications for any user model without separate tables.
 *
 * @see https://laravel.com/docs/notifications#database-notifications
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            // UUID primary key (Laravel convention for notifications)
            $table->uuid('id')->primary();

            // Notification class name (e.g., App\Notifications\PointsEarned)
            $table->string('type');

            // Polymorphic relation to the user receiving the notification (UUID)
            // notifiable_type: e.g., 'App\Models\Member'
            // notifiable_id: e.g., UUID
            $table->uuidMorphs('notifiable');

            // Notification payload (JSON-encoded data)
            $table->text('data');

            // Null = unread, timestamp = read
            $table->dateTime('read_at')->nullable();

            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            // Unread notifications query (most common)
            $table->index('read_at', 'notifications_read_at_idx');

            // Time-based queries for cleanup
            $table->index('created_at', 'notifications_created_at_idx');

            // Note: morphs() already creates index on (notifiable_type, notifiable_id)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
