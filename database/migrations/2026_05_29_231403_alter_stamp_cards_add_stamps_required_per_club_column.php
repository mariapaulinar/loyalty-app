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
            $table->integer('stamps_required_per_club')->default(1)->after('stamps_required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stamp_cards', function (Blueprint $table) {
            $table->dropColumn('stamps_required_per_club');
        });
    }
};
