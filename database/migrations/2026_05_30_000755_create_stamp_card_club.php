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
        Schema::create('stamp_card_club', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->foreignUuid('stamp_card_id')->constrained('stamp_cards')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stamp_card_club', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
            $table->dropForeign(['stamp_card_id']);
        });
        Schema::dropIfExists('stamp_card_club');
    }
};
