<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Table: settings
 *
 * Purpose:
 * Provide a centralized, typed, and documented key/value configuration store for
 * platform-wide and feature-scoped settings (e.g., license data, system config).
 * Values can be validated, optionally encrypted, grouped for admin UX, and cached
 * with TTL hints.
 *
 * Design Tenets:
 * - **Single source of truth**: One canonical table for cross-cutting config.
 * - **Typed & documented**: Each key carries type, help text, and validation metadata.
 * - **Safe by default**: Sensitive keys are encrypted; public exposure must be explicit.
 * - **Portable JSON**: Complex values live in JSON, portable across MySQL/PG/SQLite.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            // Identity & Keying
            $table->bigIncrements('id');

            // Canonical unique key, e.g.:
            //   "rewardloyalty.license_token", "rewardloyalty.support_expires_at"
            $table->string('key', 255)->unique();

            // Value & Typing
            $table->json('value')->nullable();
            $table->string('type', 50)->default('string');
            $table->json('default_value')->nullable();

            // Organization & Admin UX
            $table->string('category', 100)->default('general');
            $table->string('group', 100)->nullable();
            $table->integer('sort_order')->default(0);

            // Human-facing metadata & docs
            $table->string('label', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('help_url', 255)->nullable();

            // Validation contract & enumerations
            $table->json('validation_rules')->nullable();
            $table->json('allowed_values')->nullable();

            // Security & Visibility
            $table->boolean('is_public')->default(false);
            $table->boolean('is_editable')->default(true);
            $table->boolean('is_encrypted')->default(false);
            $table->json('required_permissions')->nullable();

            // Caching Hints
            $table->boolean('is_cached')->default(true);
            $table->integer('cache_ttl')->default(3600);

            // Audit Trail
            // Note: Admin model uses UUIDs (HasUuids trait), so this must be string(36)
            $table->string('last_modified_by', 36)->nullable();
            $table->dateTime('last_modified_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // Lookup Indexes
            $table->index('key');
            $table->index('category');
            $table->index('group');
            $table->index('is_public');
            $table->index(['category', 'sort_order']);
        });

        // Vendor-specific constraints (optional)
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("
                ALTER TABLE settings
                ADD CONSTRAINT settings_type_check
                CHECK (type IN ('string','boolean','integer','number','json','array'))
            ");

            DB::statement('
                ALTER TABLE settings
                ADD CONSTRAINT settings_public_vs_encrypted_check
                CHECK (NOT (is_public = TRUE AND is_encrypted = TRUE))
            ');
        }

        if ($driver === 'mysql') {
            try {
                DB::statement("
                    ALTER TABLE settings
                    ADD CONSTRAINT settings_type_check
                    CHECK (type IN ('string','boolean','integer','number','json','array'))
                ");

                DB::statement('
                    ALTER TABLE settings
                    ADD CONSTRAINT settings_public_vs_encrypted_check
                    CHECK (NOT (is_public = 1 AND is_encrypted = 1))
                ');
            } catch (\Throwable $e) {
                // MySQL < 8.0.16 doesn't support CHECK constraints
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            try {
                DB::statement('ALTER TABLE settings DROP CONSTRAINT IF EXISTS settings_type_check');
                DB::statement('ALTER TABLE settings DROP CONSTRAINT IF EXISTS settings_public_vs_encrypted_check');
            } catch (\Throwable $e) {
                // Safe to ignore; table drop removes remaining objects.
            }
        }

        if ($driver === 'mysql') {
            try {
                DB::statement('ALTER TABLE settings DROP CHECK settings_type_check');
                DB::statement('ALTER TABLE settings DROP CHECK settings_public_vs_encrypted_check');
            } catch (\Throwable $e) {
                // Ignore errors
            }
        }

        Schema::dropIfExists('settings');
    }
};
