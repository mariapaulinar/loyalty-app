<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Creates the activity_logs table for comprehensive audit trailing.
 * This table stores all system activities, model changes, and authentication events.
 *
 * Design Tenets:
 * - **UUID Primary Keys**: Consistent with application-wide ID strategy
 * - **Polymorphic Relations**: Flexible subject/causer tracking
 * - **JSON Properties**: Stores before/after snapshots for change tracking
 * - **Indexed for Performance**: Critical columns indexed for fast queries
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
        Schema::create(config('activitylog.table_name', 'activity_logs'), function (Blueprint $table) {
            // Primary key - UUID for consistency with other models
            $table->uuid('id')->primary();

            // Log categorization
            // Categories: 'default', 'authentication', 'transaction', 'member', 'card', 'reward', 'admin', 'api', 'webhook'
            $table->string('log_name', 64)->default('default');

            // Human-readable description of what happened
            // e.g., "Updated member email", "Created loyalty card 'Coffee Rewards'"
            $table->text('description');

            // Standardized event type for filtering and reporting
            // e.g., 'created', 'updated', 'deleted', 'login', 'logout', 'login_failed'
            $table->string('event', 64)->nullable();

            // What was affected (polymorphic - can be any model)
            // subject_type: 'App\Models\Member', 'App\Models\Card', etc.
            // subject_id: The UUID of the affected entity
            $table->nullableUuidMorphs('subject');

            // Who caused the change (polymorphic - any authenticated user)
            // causer_type: 'App\Models\Admin', 'App\Models\Partner', 'App\Models\Staff', 'App\Models\Member'
            // causer_id: The UUID of the user who made the change
            $table->nullableUuidMorphs('causer');

            // Change details as JSON
            // Structure: {"old": {...}, "attributes": {...}} for model updates
            // Or custom data for other events: {"endpoint": "/api/v1/points", "status_code": 200}
            $table->json('properties')->nullable();

            // Group related changes together (e.g., bulk operations)
            // All changes in a single request can share this UUID
            $table->uuid('batch_uuid')->nullable();

            // Request context for security auditing
            $table->string('ip_address', 45)->nullable(); // IPv6 compatible
            $table->text('user_agent')->nullable();

            // Timestamps
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // Indexes for common query patterns
            $table->index('log_name', 'activity_log_name_idx');
            $table->index('event', 'activity_event_idx');
            $table->index('batch_uuid', 'activity_batch_idx');
            $table->index(['subject_type', 'subject_id'], 'activity_subject_idx');
            $table->index(['causer_type', 'causer_id'], 'activity_causer_idx');
            $table->index('created_at', 'activity_date_idx');

            // Composite index for common admin dashboard queries
            $table->index(['log_name', 'created_at'], 'activity_log_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('activitylog.table_name', 'activity_logs'));
    }
};
