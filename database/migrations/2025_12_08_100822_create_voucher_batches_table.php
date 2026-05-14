<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the voucher_batches table for managing bulk voucher generations.
     * Each batch represents a group of vouchers created together with shared settings.
     */
    public function up(): void
    {
        Schema::create('voucher_batches', function (Blueprint $table) {
            $table->string('id')->primary(); // BATCH-XXXXXXXX format
            $table->uuid('club_id');
            $table->uuid('partner_id')->nullable();
            $table->string('name'); // Batch name for organization
            $table->text('description')->nullable();
            $table->integer('quantity'); // Number of vouchers to generate
            $table->string('code_prefix')->nullable(); // Optional code prefix
            $table->json('config'); // Shared voucher configuration
            $table->string('status')->default('active'); // active, completed, archived
            $table->integer('vouchers_created')->default(0); // Actual count created
            $table->string('claim_token', 64)->unique()->nullable(); // Unique token for QR code claims
            $table->json('meta')->nullable(); // Additional metadata
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // Indexes
            $table->index('club_id');
            $table->index('partner_id');
            $table->index('status');
            $table->index('created_at');
            $table->index('claim_token');

            // Foreign keys
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('set null');
        });

        // Add foreign key constraint from vouchers.batch_id to voucher_batches.id
        // This must be done after voucher_batches table is created
        Schema::table('vouchers', function (Blueprint $table) {
            $table->foreign('batch_id')->references('id')->on('voucher_batches')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key first
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
        });

        Schema::dropIfExists('voucher_batches');
    }
};
