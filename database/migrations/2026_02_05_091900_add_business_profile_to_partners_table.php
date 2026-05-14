<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Add Business Profile Fields to Partners Table
 *
 * Adds public-facing business profile information for the partner's micro-site.
 * These fields power the public business page with branding, location, contact,
 * and operating hours information.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            // ─────────────────────────────────────────────────────────────────
            // BRANDING - Visual Identity
            // ─────────────────────────────────────────────────────────────────
            
            // Public business name (may differ from legal name)
            $table->string('business_name', 128)->nullable()->after('name');
            // Short, catchy tagline
            $table->string('tagline', 160)->nullable()->after('business_name');
            // Primary brand color (hex format: #RRGGBB)
            $table->string('brand_color', 7)->nullable()->default('#10B981')->after('tagline');
            // Business description/about text
            $table->text('description')->nullable()->after('brand_color');

            // ─────────────────────────────────────────────────────────────────
            // LOCATION - Address & Navigation
            // ─────────────────────────────────────────────────────────────────
            
            $table->string('address_line_1', 128)->nullable()->after('description');
            $table->string('address_line_2', 128)->nullable()->after('address_line_1');
            $table->string('city', 64)->nullable()->after('address_line_2');
            $table->string('state', 64)->nullable()->after('city');
            $table->string('postal_code', 16)->nullable()->after('state');
            // Google Maps or other maps URL for directions
            $table->string('maps_url', 500)->nullable()->after('postal_code');

            // ─────────────────────────────────────────────────────────────────
            // CONTACT - Website & Social Links
            // ─────────────────────────────────────────────────────────────────
            
            // Business website URL
            $table->string('website', 255)->nullable()->after('maps_url');
            // Social media links stored as JSON: { instagram: url, facebook: url, etc. }
            $table->json('social_links')->nullable()->after('website');

            // ─────────────────────────────────────────────────────────────────
            // OPERATIONS - Business Hours
            // ─────────────────────────────────────────────────────────────────
            
            // Opening hours stored as JSON with day keys and open/close times
            $table->json('opening_hours')->nullable()->after('social_links');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn([
                'business_name',
                'tagline',
                'brand_color',
                'description',
                'address_line_1',
                'address_line_2',
                'city',
                'state',
                'postal_code',
                'maps_url',
                'website',
                'social_links',
                'opening_hours',
            ]);
        });
    }
};
