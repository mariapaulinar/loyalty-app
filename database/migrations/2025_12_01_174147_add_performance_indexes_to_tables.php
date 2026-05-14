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
        Schema::table('transactions', function (Blueprint $table) {
            // For balance calculations
            $table->index(['member_id', 'card_id', 'expires_at'], 'idx_trans_bal_calc');

            // For transaction history
            $table->index(['card_id', 'member_id', 'created_at'], 'idx_trans_history');

            // For event-based queries
            $table->index(['member_id', 'card_id', 'event'], 'idx_trans_event');
        });

        Schema::table('cards', function (Blueprint $table) {
            // For partner queries
            $table->index(['created_by', 'is_active'], 'idx_cards_partner');

            // For date-based filtering
            $table->index(['issue_date', 'expiration_date'], 'idx_cards_dates');

            // For analytics sorting
            $table->index(['views', 'last_view'], 'idx_cards_views');
            $table->index(['number_of_points_issued', 'last_points_issued_at'], 'idx_cards_points_issued');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_trans_bal_calc');
            $table->dropIndex('idx_trans_history');
            $table->dropIndex('idx_trans_event');
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->dropIndex('idx_cards_partner');
            $table->dropIndex('idx_cards_dates');
            $table->dropIndex('idx_cards_views');
            $table->dropIndex('idx_cards_points_issued');
        });
    }
};
