<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2026 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Activity Log Configuration
 *
 * Purpose:
 * Configure Spatie's Activity Log package for comprehensive audit trailing.
 * This configuration enables enterprise-grade activity tracking across all
 * user types (Admin, Partner, Staff, Member) and system operations.
 *
 * Design Tenets:
 * - **Compliance-Ready**: Retains logs for regulatory requirements
 * - **Multi-Guard Support**: Tracks activities across all authentication guards
 * - **Performance-Optimized**: Configurable retention and batch processing
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Activity Logger Enabled
    |--------------------------------------------------------------------------
    |
    | If set to false, no activities will be saved to the database. This is
    | useful for testing environments or temporary disabling during migrations.
    |
    */
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Record Retention Period
    |--------------------------------------------------------------------------
    |
    | When the clean-command is executed, all recorded activities older than
    | the number of days specified here will be deleted. For compliance, we
    | recommend keeping at least 365 days of records.
    |
    */
    'delete_records_older_than_days' => (int) env('ACTIVITY_LOGGER_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Default Log Name
    |--------------------------------------------------------------------------
    |
    | If no log name is passed to the activity() helper, we use this default
    | log name. Log names help categorize activities for filtering and reporting.
    |
    | Available log names:
    | - default: General system activities
    | - authentication: Login, logout, password changes
    | - transaction: Points earned, redeemed, transferred
    | - member: Member profile changes
    | - card: Loyalty card operations
    | - reward: Reward catalog changes
    | - admin: Administrative actions
    | - api: API key usage and management
    | - webhook: Webhook delivery attempts
    |
    */
    'default_log_name' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Default Auth Driver
    |--------------------------------------------------------------------------
    |
    | Specify an auth driver here that gets user models. If this is null,
    | we'll use the current Laravel auth driver. Since Reward Loyalty uses
    | multiple guards (admin, partner, staff, member), we leave this null
    | to automatically detect the current authenticated user.
    |
    */
    'default_auth_driver' => null,

    /*
    |--------------------------------------------------------------------------
    | Soft Deleted Models
    |--------------------------------------------------------------------------
    |
    | If set to true, the subject returns soft deleted models. This is important
    | for audit trails where we need to reference deleted records.
    |
    */
    'subject_returns_soft_deleted_models' => true,

    /*
    |--------------------------------------------------------------------------
    | Activity Model
    |--------------------------------------------------------------------------
    |
    | This model will be used to log activity. It should implement the
    | Spatie\Activitylog\Contracts\Activity interface and extend
    | Illuminate\Database\Eloquent\Model.
    |
    | We use a custom Activity model to:
    | - Add IP address and user agent tracking
    | - Support our Shortflake ID system
    | - Add custom scopes for filtering
    | - Enable batch UUID grouping for related changes
    |
    */
    'activity_model' => \App\Models\Activity::class,

    /*
    |--------------------------------------------------------------------------
    | Table Name
    |--------------------------------------------------------------------------
    |
    | This is the name of the table that will be created by the migration and
    | used by the Activity model. We use 'activity_logs' to match our existing
    | enterprise migration naming convention.
    |
    */
    'table_name' => env('ACTIVITY_LOGGER_TABLE_NAME', 'activity_logs'),

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | This is the database connection that will be used by the migration and
    | the Activity model. In case it's not set, Laravel's database.default
    | will be used instead.
    |
    */
    'database_connection' => env('ACTIVITY_LOGGER_DB_CONNECTION'),
];
