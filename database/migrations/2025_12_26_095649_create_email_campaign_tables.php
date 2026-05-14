<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Creates email campaign tables for partner-to-member marketing emails.
 *
 * Architecture:
 * - email_campaigns: Campaign metadata, status, and translatable content
 * - email_campaign_recipients: Individual delivery tracking per member
 *
 * Design decisions:
 * - subject/body are JSON for multi-language support (HasTranslations trait)
 * - Sequential sending tracked via recipient status for resume capability
 * - Soft deletes preserve campaign history for analytics
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates two tables:
     * 1. email_campaigns - The campaign itself with content and targeting
     * 2. email_campaign_recipients - Individual send tracking per member
     */
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════════════
        // EMAIL CAMPAIGNS
        // ═══════════════════════════════════════════════════════════════════
        Schema::create('email_campaigns', function (Blueprint $table) {
            // Primary key - UUID for security (no enumeration attacks)
            $table->uuid('id')->primary();

            // Partner ownership - campaigns are strictly partner-isolated
            $table->uuid('partner_id');
            $table->foreign('partner_id')
                ->references('id')
                ->on('partners')
                ->cascadeOnDelete();

            // Translatable content - JSON columns for multi-language support
            // Structure: {"en_US": "Hello!", "pt_BR": "Olá!", "ar_SA": "مرحبا!"}
            $table->json('subject');
            $table->json('body');

            // Audience targeting - segment type and configuration
            // segment_type: all_members, card_members, points_below, etc.
            // segment_config: {"card_id": "uuid", "threshold": 100}
            $table->string('segment_type', 50);
            $table->json('segment_config')->nullable();

            // Campaign lifecycle status
            // draft: Saved for later editing/sending
            // pending: Ready to send (created, not yet started)
            // scheduled: Scheduled for future sending (requires scheduled_at)
            // sending: In progress (browser session active)
            // sent: All recipients processed
            // failed: Critical error stopped the campaign
            $table->enum('status', ['draft', 'pending', 'scheduled', 'sending', 'sent', 'failed'])
                ->default('draft');

            // Delivery statistics - updated after each email sent
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            // Scheduling - for future "Schedule for Later" feature
            $table->dateTime('scheduled_at')->nullable();

            // Timing - for analytics and debugging
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            // Standard timestamps
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            // Indexes for common queries
            $table->index('partner_id');
            $table->index('status');
            $table->index(['partner_id', 'status']);
            $table->index('created_at');
        });

        // ═══════════════════════════════════════════════════════════════════
        // EMAIL CAMPAIGN RECIPIENTS
        // ═══════════════════════════════════════════════════════════════════
        Schema::create('email_campaign_recipients', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();

            // Campaign relationship
            $table->uuid('campaign_id');
            $table->foreign('campaign_id')
                ->references('id')
                ->on('email_campaigns')
                ->cascadeOnDelete();

            // Member relationship - nullable for deleted members
            // If member is deleted mid-campaign, we still track the attempt
            $table->uuid('member_id')->nullable();
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->nullOnDelete();

            // Email snapshot - stored at creation time
            // Preserves delivery target even if member changes email
            $table->string('email', 255);

            // Delivery status tracking
            // pending: Queued for sending
            // sent: Successfully delivered to mail server
            // failed: Delivery failed (error stored)
            $table->enum('status', ['pending', 'sent', 'failed'])
                ->default('pending');

            // Error tracking for failed deliveries
            $table->text('error_message')->nullable();

            // Timing
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // Indexes for sequential sending and reporting
            $table->index('campaign_id');
            $table->index(['campaign_id', 'status']);
            $table->index('member_id');

            // Prevent duplicate recipients per campaign
            $table->unique(['campaign_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops tables in reverse dependency order.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_campaign_recipients');
        Schema::dropIfExists('email_campaigns');
    }
};
