<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Custom Activity model extending Spatie's Activity Log for enterprise-grade
 * audit trailing. Adds UUID support, IP tracking, and custom query scopes.
 *
 * Design Tenets:
 * - **UUID Consistency**: Matches application-wide ID strategy
 * - **Request Context**: Automatically captures IP and user agent
 * - **Multi-Guard Support**: Works with all authentication guards
 * - **Query Scopes**: Provides filtering for dashboards and reports
 */

namespace App\Models;

use App\Traits\HasSchemaAccessors;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    use HasFactory, HasSchemaAccessors, HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'activity_logs';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     */
    protected $keyType = 'string';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'properties' => 'collection',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model and register event listeners.
     */
    protected static function booted(): void
    {
        // Automatically capture IP address and user agent on creation
        static::creating(function (Activity $activity): void {
            if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                return;
            }

            $activity->ip_address = $activity->ip_address ?? request()->ip();
            $activity->user_agent = $activity->user_agent ?? request()->userAgent();
        });
    }

    /**
     * Scope: Filter by authentication-related events.
     */
    public function scopeAuthentication(Builder $query): Builder
    {
        return $query->where('log_name', 'authentication');
    }

    /**
     * Scope: Filter by transaction-related events.
     */
    public function scopeTransactions(Builder $query): Builder
    {
        return $query->where('log_name', 'transaction');
    }

    /**
     * Scope: Filter by agent API events.
     */
    public function scopeAgentApi(Builder $query): Builder
    {
        return $query->where('log_name', 'agent_api');
    }

    /**
     * Scope: Filter by a specific causer type.
     */
    public function scopeByCauserType(Builder $query, string $type): Builder
    {
        return $query->where('causer_type', $type);
    }

    /**
     * Scope: Filter by admin actions.
     */
    public function scopeByAdmins(Builder $query): Builder
    {
        return $query->where('causer_type', Admin::class);
    }

    /**
     * Scope: Filter by partner actions.
     */
    public function scopeByPartners(Builder $query): Builder
    {
        return $query->where('causer_type', Partner::class);
    }

    /**
     * Scope: Filter by staff actions.
     */
    public function scopeByStaff(Builder $query): Builder
    {
        return $query->where('causer_type', Staff::class);
    }

    /**
     * Scope: Filter by member actions.
     */
    public function scopeByMembers(Builder $query): Builder
    {
        return $query->where('causer_type', Member::class);
    }

    /**
     * Scope: Filter activities within a date range.
     */
    public function scopeInDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope: Filter activities from today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope: Filter activities from this week.
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->startOfWeek());
    }

    /**
     * Scope: Filter activities from this month.
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->startOfMonth());
    }

    /**
     * Scope: Search in description.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('description', 'like', "%{$term}%");
    }

    /**
     * Scope: Filter by specific events.
     *
     * @param  array<string>|string  $events
     */
    public function scopeForEvents(Builder $query, array|string $events): Builder
    {
        $events = is_array($events) ? $events : [$events];

        return $query->whereIn('event', $events);
    }

    /**
     * Get the human-readable causer name.
     */
    public function getCauserNameAttribute(): ?string
    {
        if (! $this->causer) {
            return null;
        }

        return $this->causer->name ?? $this->causer->email ?? 'Unknown';
    }

    /**
     * Get the human-readable subject name.
     */
    public function getSubjectNameAttribute(): ?string
    {
        if (! $this->subject) {
            return null;
        }

        return $this->subject->name ?? $this->subject->title ?? $this->subject->email ?? 'Unknown';
    }

    /**
     * Get the short subject type (class name without namespace).
     */
    public function getSubjectTypeShortAttribute(): ?string
    {
        if (! $this->subject_type) {
            return null;
        }

        return class_basename($this->subject_type);
    }

    /**
     * Get the short causer type (class name without namespace).
     */
    public function getCauserTypeShortAttribute(): ?string
    {
        if (! $this->causer_type) {
            return null;
        }

        return class_basename($this->causer_type);
    }

    /**
     * Get the old values from properties.
     *
     * @return array<string, mixed>
     */
    public function getOldAttribute(): array
    {
        return $this->properties?->get('old', []) ?? [];
    }

    /**
     * Get the new/changed values from properties.
     * Named differently to avoid conflict with parent's getChangesAttribute.
     *
     * @return array<string, mixed>
     */
    public function getNewValuesAttribute(): array
    {
        return $this->properties?->get('attributes', []) ?? [];
    }

    /**
     * Determine if this activity has logged changes (old/new values).
     */
    public function hasLoggedChanges(): bool
    {
        return ! empty($this->old) || ! empty($this->new_values);
    }

    /**
     * Get the badge color for the event type.
     */
    public function getEventColorAttribute(): string
    {
        return match ($this->event) {
            'created' => 'success',
            'updated' => 'info',
            'deleted' => 'danger',
            'login' => 'primary',
            'logout' => 'secondary',
            'login_failed' => 'warning',
            'agent_read' => 'info',
            'agent_write' => 'primary',
            'agent_delete' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get the icon name for the event type.
     */
    public function getEventIconAttribute(): string
    {
        return match ($this->event) {
            'created' => 'plus-circle',
            'updated' => 'edit',
            'deleted' => 'trash-2',
            'login' => 'log-in',
            'logout' => 'log-out',
            'login_failed' => 'alert-triangle',
            'agent_read' => 'eye',
            'agent_write' => 'zap',
            'agent_delete' => 'x-circle',
            default => 'activity',
        };
    }
}
