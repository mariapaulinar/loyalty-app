<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Subscribes to Laravel's authentication events and logs them using
 * the ActivityLogService. Tracks logins, logouts, and failed attempts
 * across all authentication guards (admin, partner, staff, member).
 *
 * Design Tenets:
 * - **Comprehensive Tracking**: Captures all auth events systemwide
 * - **Security-Focused**: Logs failed attempts for intrusion detection
 * - **Non-Blocking**: Errors in logging don't affect authentication flow
 * - **Privacy-Aware**: Only logs necessary authentication context
 *
 * Tracked Events:
 * - Login (successful authentication)
 * - Logout (explicit sign out)
 * - Failed (incorrect credentials)
 * - Lockout (too many failed attempts)
 */

namespace App\Listeners;

use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Log;

class AuthEventSubscriber
{
    /**
     * Create the event subscriber.
     */
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Handle successful login events.
     */
    public function handleLogin(Login $event): void
    {
        try {
            if ($event->user) {
                $this->activityLog->logAuth('login', $event->user, [
                    'remember' => $event->remember,
                ]);
            }
        } catch (\Throwable $e) {
            // Don't let logging failures affect authentication
            Log::error('Failed to log login event', [
                'error' => $e->getMessage(),
                'user_id' => $event->user?->id,
            ]);
        }
    }

    /**
     * Handle logout events.
     */
    public function handleLogout(Logout $event): void
    {
        try {
            if ($event->user) {
                $this->activityLog->logAuth('logout', $event->user);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to log logout event', [
                'error' => $e->getMessage(),
                'user_id' => $event->user?->id,
            ]);
        }
    }

    /**
     * Handle failed login attempts.
     */
    public function handleFailed(Failed $event): void
    {
        try {
            $this->activityLog->logFailedLogin($event->credentials);
        } catch (\Throwable $e) {
            Log::error('Failed to log failed login event', [
                'error' => $e->getMessage(),
                'email' => $event->credentials['email'] ?? 'unknown',
            ]);
        }
    }

    /**
     * Handle account lockout events.
     */
    public function handleLockout(Lockout $event): void
    {
        try {
            $email = $event->request->input('email', 'unknown');

            activity('authentication')
                ->event('lockout')
                ->withProperties([
                    'email' => $email,
                    'ip' => $event->request->ip(),
                    'user_agent' => $event->request->userAgent(),
                ])
                ->log("Account locked out due to too many failed attempts: {$email}");
        } catch (\Throwable $e) {
            Log::error('Failed to log lockout event', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle password reset events.
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        try {
            if ($event->user) {
                $this->activityLog->logAuth('password_reset', $event->user);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to log password reset event', [
                'error' => $e->getMessage(),
                'user_id' => $event->user?->id,
            ]);
        }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<class-string, string>
     */
    public function subscribe(): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            Lockout::class => 'handleLockout',
            PasswordReset::class => 'handlePasswordReset',
        ];
    }
}
