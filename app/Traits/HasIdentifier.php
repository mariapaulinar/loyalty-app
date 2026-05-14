<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasIdentifier
 *
 * Trait for adding a unique identifier to a model.
 * Generates a formatted numeric string (e.g., "123-456-789-012") on model creation.
 */
trait HasIdentifier
{
    /**
     * Initialize the trait.
     * Add event listeners to the model.
     */
    public static function bootHasIdentifier(): void
    {
        // Generate and set unique identifier when creating a new model instance
        // Only set if not already present (allows manual override)
        static::creating(function (Model $model): void {
            if (empty($model->unique_identifier)) {
                $model->unique_identifier = self::generateUniqueIdentifier($model);
            }
        });
    }

    /**
     * Generate a unique identifier.
     */
    protected static function generateUniqueIdentifier(Model $model): string
    {
        $uniqueIdentifier = self::formatIdentifier(self::generateRandomString(12));

        // If the unique identifier already exists for another model, generate a new one
        if (self::identifierExists($model, $uniqueIdentifier)) {
            return self::generateUniqueIdentifier($model);
        }

        return $uniqueIdentifier;
    }

    /**
     * Check if a unique identifier already exists.
     */
    protected static function identifierExists(Model $model, string $uniqueIdentifier): bool
    {
        return $model->where('id', '<>', $model->id)->where('unique_identifier', $uniqueIdentifier)->exists();
    }

    /**
     * Format the unique identifier.
     */
    protected static function formatIdentifier(string $identifier): string
    {
        return implode('-', str_split($identifier, 3));
    }

    /**
     * Generate a random string of a specified length.
     */
    protected static function generateRandomString(int $length, string $charset = '1234567890'): string
    {
        $str = '';
        $count = strlen($charset);

        while ($length--) {
            $str .= $charset[random_int(0, $count - 1)];
        }

        return $str;
    }
}
