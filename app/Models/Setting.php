<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Setting Model
 *
 * Purpose:
 * Centralized, typed key/value configuration store for platform-wide settings.
 * Supports encryption for sensitive values and media attachments (logos, icons).
 *
 * Design Tenets:
 * - Type-safe value storage
 * - Media library integration for images
 * - Encryption support for sensitive data
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Setting extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'key',
        'value',
        'type',
        'default_value',
        'category',
        'group',
        'sort_order',
        'label',
        'description',
        'help_url',
        'validation_rules',
        'allowed_values',
        'is_public',
        'is_editable',
        'is_encrypted',
        'required_permissions',
        'is_cached',
        'cache_ttl',
        'last_modified_by',
        'last_modified_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'default_value' => 'array',
            'validation_rules' => 'array',
            'allowed_values' => 'array',
            'required_permissions' => 'array',
            'is_public' => 'boolean',
            'is_editable' => 'boolean',
            'is_encrypted' => 'boolean',
            'is_cached' => 'boolean',
            'last_modified_at' => 'datetime',
        ];
    }

    /**
     * Get the admin who last modified this setting
     */
    public function lastModifiedBy()
    {
        return $this->belongsTo(Admin::class, 'last_modified_by');
    }

    /**
     * Set a setting value (create or update)
     */
    public static function setValue(string $key, mixed $value, string $type = 'string'): Setting
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
            ]
        );
    }

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    /**
     * Register media collections for PWA icons and branding logos.
     *
     * Uses the default media disk (config: media-library.disk_name) for consistency
     * with other models like Partner. The singleFile() directive ensures old files
     * are automatically removed when new ones are uploaded.
     *
     * Collections:
     * - pwa_icon_192: 192x192 PWA app icon
     * - pwa_icon_512: 512x512 PWA app icon
     * - app_logo: Light mode application logo
     * - app_logo_dark: Dark mode application logo
     */
    public function registerMediaCollections(): void
    {
        // PWA Icon 192x192
        $this->addMediaCollection('pwa_icon_192')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp'])
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->fit(Fit::Crop, 192, 192)
                    ->format('png')
                    ->nonQueued();
            });

        // PWA Icon 512x512
        $this->addMediaCollection('pwa_icon_512')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp'])
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->fit(Fit::Crop, 512, 512)
                    ->format('png')
                    ->nonQueued();
            });

        // Application Logo (Light Mode)
        // Supports SVG for scalable vector logos alongside raster formats.
        // No media conversions needed - logos are displayed at their natural size.
        $this->addMediaCollection('app_logo')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/svg+xml',
                'image/png',
                'image/jpeg',
                'image/webp',
            ]);

        // Application Logo (Dark Mode)
        // Optional dark mode variant for optimal contrast on dark backgrounds.
        $this->addMediaCollection('app_logo_dark')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/svg+xml',
                'image/png',
                'image/jpeg',
                'image/webp',
            ]);

        // Application Favicon
        // Custom favicon displayed in browser tabs and bookmarks.
        // Supports ICO (traditional) and SVG (modern, scalable) formats.
        $this->addMediaCollection('app_favicon')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/x-icon',
                'image/vnd.microsoft.icon',
                'image/svg+xml',
            ]);

        // Homepage Hero Image (Showcase Layout)
        // Optional background image for the Showcase homepage hero section.
        // Recommended size: 1920x1080 or larger for full-width display.
        $this->addMediaCollection('homepage_hero_image')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/png',
                'image/jpeg',
                'image/webp',
            ]);
    }
}
