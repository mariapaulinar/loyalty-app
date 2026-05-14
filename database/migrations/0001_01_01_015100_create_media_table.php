<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Media Table (Spatie Media Library)
 *
 * Stores metadata for all uploaded files (images, documents, etc.).
 * Actual files stored on disk/S3; this table tracks relationships and conversions.
 *
 * Features:
 * - Polymorphic: Any model can have media attachments
 * - Collections: Group media (e.g., "avatar", "gallery", "documents")
 * - Conversions: Auto-generated thumbnails, webp, etc.
 * - Responsive images: srcset generation for optimal loading
 *
 * Media types in Reward Loyalty:
 * - Card backgrounds and logos
 * - Reward images
 * - Member avatars
 * - Partner branding assets
 *
 * @see https://spatie.be/docs/laravel-medialibrary
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Polymorphic relation to owning model (using UUID for model_id)
            $table->uuidMorphs('model');

            // Unique identifier for URL generation
            $table->uuid('uuid')->nullable()->unique();

            // Organization
            $table->string('collection_name')->comment('Media group: avatar, logo, gallery');
            $table->string('name')->comment('User-facing filename');
            $table->string('file_name')->comment('Storage filename');

            // File metadata
            $table->string('mime_type')->nullable();
            $table->string('disk')->comment('Storage disk: local, s3');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size')->comment('File size in bytes');

            // Image processing configuration
            $table->json('manipulations')->comment('Pending image manipulations');
            $table->json('custom_properties')->comment('User-defined metadata');
            $table->json('generated_conversions')->comment('Completed conversions');
            $table->json('responsive_images')->comment('Responsive image URLs');

            // Ordering within collection
            $table->unsignedInteger('order_column')->nullable();

            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            // Collection filtering
            $table->index('collection_name', 'media_collection_idx');
            // Ordering within collections
            $table->index('order_column', 'media_order_idx');
            // Disk usage reporting
            $table->index('disk', 'media_disk_idx');

            // Note: morphs() creates index on (model_type, model_id)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
