<?php

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
        Schema::table('stamp_cards', function (Blueprint $table) {
            // Tipo de tarjeta: normal (tarjetas normales) o event (tarjetas para eventos)
            $table->enum('card_type', ['normal', 'event'])->default('normal')->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stamp_cards', function (Blueprint $table) {
            $table->dropColumn('card_type');
        });
    }
};
