<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * A simplified trait for adding activity logging to Eloquent models.
 * Wraps Spatie's LogsActivity trait with sensible defaults for the
 * Reward Loyalty application.
 *
 * Design Tenets:
 * - **Convention Over Configuration**: Smart defaults for most use cases
 * - **Selective Logging**: Only logs dirty (changed) attributes
 * - **Privacy-Aware**: Automatically excludes sensitive fields
 * - **Customizable**: Override getActivitylogOptions() for specific behavior
 *
 * Usage:
 *
 * class Card extends Model
 * {
 *     use LogsModelActivity;
 * }
 */

namespace App\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait LogsModelActivity
{
    use LogsActivity;

    /**
     * Configure activity logging options for this model.
     *
     * Override this method in your model for full control.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName($this->getTable())
            ->logUnguarded() // Works with models using $guarded = []
            ->logExcept([
                'password',
                'remember_token',
                'two_factor_secret',
                'two_factor_recovery_codes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => $this->buildActivityDescription($eventName));
    }

    /**
     * Build a human-readable description for the activity log.
     */
    protected function buildActivityDescription(string $eventName): string
    {
        $modelName = class_basename($this);
        $identifier = $this->getActivityIdentifier();

        return match ($eventName) {
            'created' => "{$modelName} created".($identifier ? ": {$identifier}" : ''),
            'updated' => "{$modelName} updated".($identifier ? ": {$identifier}" : ''),
            'deleted' => "{$modelName} deleted".($identifier ? ": {$identifier}" : ''),
            default => "{$modelName} {$eventName}".($identifier ? ": {$identifier}" : ''),
        };
    }

    /**
     * Get a human-readable identifier for log descriptions.
     * Override in model to customize.
     */
    protected function getActivityIdentifier(): ?string
    {
        // Try common identifier fields
        foreach (['name', 'title', 'email', 'unique_identifier'] as $field) {
            if (isset($this->$field) && ! empty($this->$field)) {
                $value = $this->$field;
                // For translatable fields, get the string value
                if (is_array($value)) {
                    $value = $value[app()->getLocale()] ?? $value['en'] ?? reset($value);
                }

                return is_string($value) ? $value : null;
            }
        }

        return null;
    }
}
