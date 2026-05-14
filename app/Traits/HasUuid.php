<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * Trait HasUuid
 *
 * Provides a UUID generation functionality for Eloquent models.
 */
trait HasUuid
{
    /**
     * Boot the trait and add UUID generation to the model's creating event.
     */
    public static function bootHasUuid()
    {
        static::creating(function ($model) {
            // Generate and set UUID
            $model->uuid = (string) Str::orderedUuid();
        });
    }
}
