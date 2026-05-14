<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Centralized service for logging activities throughout the application.
 * Provides a clean API for logging custom events, authentication activities,
 * API requests, and searching/filtering the activity log.
 *
 * Design Tenets:
 * - **Fluent API**: Chainable methods for readable logging calls
 * - **Type Safety**: Strict typing throughout for reliability
 * - **Multi-Guard Aware**: Automatically detects current authenticated user
 * - **Performance**: Optimized queries with proper indexing support
 *
 * Usage Examples:
 *
 * // Log a custom activity
 * $service->log('User exported member data', $member, 'exported');
 *
 * // Log authentication event
 * $service->logAuth('login', $user);
 *
 * // Log API request
 * $service->logApiRequest('/api/v1/points', 'POST', $apiKey, 201);
 *
 * // Search activities
 * $activities = $service->search(['log_name' => 'authentication']);
 */

namespace App\Services;

use App\Models\Activity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    /**
     * Available authentication guards in order of precedence.
     *
     * @var array<string>
     */
    protected array $guards = ['admin', 'partner', 'staff', 'member'];

    /**
     * Log a custom activity.
     *
     * @param  array<string, mixed>  $properties  Additional data to store
     */
    public function log(
        string $description,
        ?Model $subject = null,
        ?string $event = null,
        array $properties = [],
        string $logName = 'default'
    ): Activity {
        $activityLogger = activity($logName);

        // Set the causer (who performed the action)
        $causer = $this->getCurrentUser();
        if ($causer) {
            $activityLogger->causedBy($causer);
        }

        // Set additional properties
        if (! empty($properties)) {
            $activityLogger->withProperties($properties);
        }

        // Set the subject (what was affected)
        if ($subject) {
            $activityLogger->performedOn($subject);
        }

        // Set the event type
        if ($event) {
            $activityLogger->event($event);
        }

        return $activityLogger->log($description);
    }

    /**
     * Log an authentication event.
     *
     * @param  array<string, mixed>  $context  Additional context data
     */
    public function logAuth(string $event, Model $user, array $context = []): Activity
    {
        $description = $this->formatAuthDescription($event, $user);

        return activity('authentication')
            ->causedBy($user)
            ->performedOn($user)
            ->event($event)
            ->withProperties(array_merge($context, [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'guard' => $this->getGuardForUser($user),
            ]))
            ->log($description);
    }

    /**
     * Log a failed login attempt.
     *
     * @param  array<string, mixed>  $credentials  The credentials that were used
     */
    public function logFailedLogin(array $credentials): Activity
    {
        $email = $credentials['email'] ?? 'unknown';

        return activity('authentication')
            ->event('login_failed')
            ->withProperties([
                'email' => $email,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Failed login attempt for {$email}");
    }

    /**
     * Log an API request.
     *
     * @param  array<string, mixed>  $context  Additional context (request body, etc.)
     */
    public function logApiRequest(
        string $endpoint,
        string $method,
        Model $apiKey,
        int $statusCode,
        array $context = []
    ): Activity {
        $description = "API {$method} {$endpoint} returned {$statusCode}";

        // Get the owner of the API key (Partner or Club)
        $owner = method_exists($apiKey, 'owner') ? $apiKey->owner : null;

        return activity('api')
            ->causedBy($owner)
            ->performedOn($apiKey)
            ->event('api_request')
            ->withProperties(array_merge($context, [
                'endpoint' => $endpoint,
                'method' => $method,
                'status_code' => $statusCode,
                'ip' => request()->ip(),
            ]))
            ->log($description);
    }

    /**
     * Log an Agent API request.
     *
     * Dedicated log for the Agentic Layer — uses 'agent_api' log name
     * for easy filtering/analytics. Called by the LogAgentActivity
     * middleware in the terminate phase (zero latency impact).
     *
     * Events are derived from HTTP method:
     *   GET    → agent_read
     *   POST   → agent_write
     *   PUT    → agent_write
     *   PATCH  → agent_write
     *   DELETE → agent_delete
     *
     * @param  array<string, mixed>  $context  Agent-specific context (key_prefix, scopes, owner_type)
     */
    public function logAgentRequest(
        string $endpoint,
        string $method,
        Model $agentKey,
        int $statusCode,
        array $context = [],
    ): Activity {
        $event = match (strtoupper($method)) {
            'GET' => 'agent_read',
            'DELETE' => 'agent_delete',
            default => 'agent_write',
        };

        $description = "Agent {$method} {$endpoint} → {$statusCode}";

        // The owner (Partner/Admin/Member) is the causer
        $owner = method_exists($agentKey, 'owner') ? $agentKey->owner : null;

        return activity('agent_api')
            ->causedBy($owner)
            ->performedOn($agentKey)
            ->event($event)
            ->withProperties(array_merge($context, [
                'endpoint' => $endpoint,
                'method' => $method,
                'status_code' => $statusCode,
                'ip' => request()->ip(),
            ]))
            ->log($description);
    }

    /**
     * Log a transaction event.
     *
     * @param  array<string, mixed>  $data  Transaction data
     */
    public function logTransaction(
        string $description,
        Model $transaction,
        string $event,
        array $data = []
    ): Activity {
        return activity('transaction')
            ->causedBy($this->getCurrentUser())
            ->performedOn($transaction)
            ->event($event)
            ->withProperties($data)
            ->log($description);
    }

    /**
     * Get activities for a specific subject (model).
     *
     * @return Collection<int, Activity>
     */
    public function getForSubject(Model $subject, int $limit = 50): Collection
    {
        return Activity::query()
            ->forSubject($subject)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities caused by a specific user.
     *
     * @return Collection<int, Activity>
     */
    public function getByCauser(Model $causer, int $limit = 50): Collection
    {
        return Activity::query()
            ->causedBy($causer)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent activities across the system.
     *
     * @return Collection<int, Activity>
     */
    public function getRecent(int $limit = 50): Collection
    {
        return Activity::query()
            ->with(['causer', 'subject'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Search activities with various filters.
     *
     * @param  array<string, mixed>  $filters  Available filters:
     *                                         - log_name: string
     *                                         - event: string
     *                                         - causer_type: string (full class name)
     *                                         - subject_type: string (full class name)
     *                                         - from_date: string (Y-m-d)
     *                                         - to_date: string (Y-m-d)
     *                                         - search: string (searches description)
     * @return LengthAwarePaginator<Activity>
     */
    public function search(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->latest();

        // Filter by log name (category)
        if (isset($filters['log_name']) && $filters['log_name'] !== '') {
            $query->inLog($filters['log_name']);
        }

        // Filter by event type
        if (isset($filters['event']) && $filters['event'] !== '') {
            $query->where('event', $filters['event']);
        }

        // Filter by causer type
        if (isset($filters['causer_type']) && $filters['causer_type'] !== '') {
            $query->where('causer_type', $filters['causer_type']);
        }

        // Filter by subject type
        if (isset($filters['subject_type']) && $filters['subject_type'] !== '') {
            $query->where('subject_type', $filters['subject_type']);
        }

        // Filter by date range
        if (isset($filters['from_date']) && $filters['from_date'] !== '') {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date']) && $filters['to_date'] !== '') {
            $query->where('created_at', '<=', $filters['to_date'].' 23:59:59');
        }

        // Search in description
        if (isset($filters['search']) && $filters['search'] !== '') {
            $query->where('description', 'like', "%{$filters['search']}%");
        }

        return $query->paginate($perPage);
    }

    /**
     * Get activity statistics for a date range.
     *
     * @return array<string, mixed>
     */
    public function getStats(?string $fromDate = null, ?string $toDate = null): array
    {
        $query = Activity::query();

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('created_at', '<=', $toDate.' 23:59:59');
        }

        return [
            'total' => $query->count(),
            'by_log_name' => (clone $query)
                ->selectRaw('log_name, COUNT(*) as count')
                ->groupBy('log_name')
                ->pluck('count', 'log_name')
                ->toArray(),
            'by_event' => (clone $query)
                ->selectRaw('event, COUNT(*) as count')
                ->groupBy('event')
                ->pluck('count', 'event')
                ->toArray(),
            'by_causer_type' => (clone $query)
                ->selectRaw('causer_type, COUNT(*) as count')
                ->whereNotNull('causer_type')
                ->groupBy('causer_type')
                ->pluck('count', 'causer_type')
                ->map(fn ($count, $type) => ['type' => class_basename($type), 'count' => $count])
                ->values()
                ->toArray(),
        ];
    }

    /**
     * Get the currently authenticated user across all guards.
     */
    protected function getCurrentUser(): ?Model
    {
        foreach ($this->guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return Auth::guard($guard)->user();
            }
        }

        return null;
    }

    /**
     * Determine which guard a user belongs to.
     */
    protected function getGuardForUser(Model $user): string
    {
        return match (true) {
            $user instanceof \App\Models\Admin => 'admin',
            $user instanceof \App\Models\Partner => 'partner',
            $user instanceof \App\Models\Staff => 'staff',
            $user instanceof \App\Models\Member => 'member',
            default => 'unknown',
        };
    }

    /**
     * Format authentication event description.
     */
    protected function formatAuthDescription(string $event, Model $user): string
    {
        $identifier = $user->email ?? $user->name ?? 'User';
        $guard = $this->getGuardForUser($user);
        $guardLabel = ucfirst($guard);

        return match ($event) {
            'login' => "{$guardLabel} logged in: {$identifier}",
            'logout' => "{$guardLabel} logged out: {$identifier}",
            'password_reset' => "{$guardLabel} reset password: {$identifier}",
            'password_changed' => "{$guardLabel} changed password: {$identifier}",
            default => "{$event} for {$guardLabel}: {$identifier}",
        };
    }
}
