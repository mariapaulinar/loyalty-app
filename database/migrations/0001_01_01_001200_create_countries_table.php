<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * ISO Countries, Languages, and Currencies Tables
 *
 * Reference data for internationalization (i18n) and localization (l10n).
 * Uses ISO standards for consistent identifiers across the platform.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ISO 3166-1 Countries
        Schema::create('countries', function (Blueprint $table) {
            $table->string('id', 2)->comment('ISO 3166-1 alpha-2')->primary();
            $table->string('alpha_3', 3)->nullable();
            $table->json('name');
            $table->string('native_name')->nullable();
            $table->string('capital')->nullable();
            $table->string('top_level_domain')->nullable();
            $table->string('calling_code')->nullable();
            $table->string('region')->nullable();
            $table->string('subregion')->nullable();
            $table->unsignedInteger('population')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lon', 11, 8)->nullable();
            $table->string('demonym')->nullable();
            $table->unsignedInteger('area')->nullable();
            $table->float('gini')->nullable();
        });

        // ISO 639-1 Languages
        Schema::create('languages', function (Blueprint $table) {
            $table->string('id', 2)->comment('ISO 639-1')->primary();
            $table->string('iso639_2', 3)->index();
            $table->string('iso639_2b', 3)->nullable()->index();
            $table->string('name');
            $table->string('native_name')->nullable();
            $table->string('family')->nullable();
            $table->string('wiki_url')->nullable();
        });

        // ISO 4217 Currencies
        Schema::create('currencies', function (Blueprint $table) {
            $table->string('id', 3)->comment('ISO 4217')->primary();
            $table->string('name');
            $table->string('name_plural');
            $table->string('symbol');
            $table->string('symbol_native');
            $table->unsignedTinyInteger('decimal_digits');
            $table->unsignedTinyInteger('rounding');
        });

        // Pivot: Country-Language relationships
        Schema::create('country_language', function (Blueprint $table) {
            $table->string('country_id', 2)->index();
            $table->string('language_id', 2)->index();
            $table->primary(['country_id', 'language_id']);
        });

        // Pivot: Country-Currency relationships
        Schema::create('country_currency', function (Blueprint $table) {
            $table->string('country_id', 2)->index();
            $table->string('currency_id', 3)->index();
            $table->primary(['country_id', 'currency_id']);
        });

        // Pivot: Country-Country (neighbors) relationships
        Schema::create('country_country', function (Blueprint $table) {
            $table->string('country_id', 2)->index();
            $table->string('neighbour_id', 2)->index();
            $table->primary(['country_id', 'neighbour_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('country_language');
        Schema::dropIfExists('country_currency');
        Schema::dropIfExists('country_country');
    }
};
