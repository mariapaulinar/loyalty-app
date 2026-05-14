<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * System health diagnostics service for the admin Health Center.
 * Runs environment checks and returns structured results for display.
 *
 * Design:
 * Each check returns a HealthCheck array with status (ok|warning|critical),
 * label, value, and optional detail message. Checks are pure diagnostics —
 * no mutations, no external calls, no secrets exposed.
 */

namespace App\Services;

use App\Services\Billing\BillingManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class HealthService
{
    /**
     * Run all health checks and return structured results.
     *
     * @return array{
     *     checks: array<int, array{category: string, label: string, status: string, value: string, detail: string|null}>,
     *     summary: array{ok: int, warning: int, critical: int, total: int}
     * }
     */
    public function runChecks(): array
    {
        $checks = [
            // Environment
            $this->checkPhpVersion(),
            $this->checkPhpExtensions(),
            $this->checkDisabledFunctions(),

            // Application
            $this->checkAppUrl(),
            $this->checkAppDebug(),
            $this->checkHttps(),
            $this->checkAppVersion(),

            // Database
            $this->checkDatabaseConnection(),
            $this->checkDatabaseVersion(),
            $this->checkPendingMigrations(),

            // Storage
            $this->checkWritableDirectories(),
            $this->checkStorageSymlink(),

            // Services
            $this->checkQueueDriver(),
            $this->checkMailDriver(),
            $this->checkCronStatus(),
            $this->checkBillingProvider(),
        ];

        $summary = [
            'ok' => count(array_filter($checks, fn ($c) => $c['status'] === 'ok')),
            'warning' => count(array_filter($checks, fn ($c) => $c['status'] === 'warning')),
            'critical' => count(array_filter($checks, fn ($c) => $c['status'] === 'critical')),
            'total' => count($checks),
        ];

        return [
            'checks' => $checks,
            'summary' => $summary,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // ENVIRONMENT CHECKS
    // ═══════════════════════════════════════════════════════════════

    private function checkPhpVersion(): array
    {
        $version = PHP_VERSION;
        $major = PHP_MAJOR_VERSION;
        $minor = PHP_MINOR_VERSION;

        if ($major >= 8 && $minor >= 2) {
            $status = 'ok';
            $detail = null;
        } elseif ($major >= 8 && $minor >= 1) {
            $status = 'warning';
            $detail = 'PHP 8.2+ recommended for best performance and security.';
        } else {
            $status = 'critical';
            $detail = 'PHP 8.1+ required. Current version is not supported.';
        }

        return [
            'category' => 'environment',
            'label' => 'PHP Version',
            'status' => $status,
            'value' => $version,
            'detail' => $detail,
        ];
    }

    private function checkPhpExtensions(): array
    {
        $required = [
            'bcmath', 'ctype', 'curl', 'dom', 'fileinfo',
            'json', 'mbstring', 'openssl', 'pdo', 'tokenizer',
            'xml', 'gd',
        ];

        $missing = array_filter($required, fn ($ext) => ! extension_loaded($ext));

        if (empty($missing)) {
            return [
                'category' => 'environment',
                'label' => 'PHP Extensions',
                'status' => 'ok',
                'value' => count($required).' required extensions loaded',
                'detail' => null,
            ];
        }

        return [
            'category' => 'environment',
            'label' => 'PHP Extensions',
            'status' => 'critical',
            'value' => count($missing).' missing',
            'detail' => 'Missing: '.implode(', ', $missing),
        ];
    }

    private function checkDisabledFunctions(): array
    {
        $important = ['proc_open', 'proc_close', 'exec', 'shell_exec'];
        $disabled = array_map('trim', explode(',', ini_get('disable_functions') ?: ''));
        $blocked = array_intersect($important, $disabled);

        if (empty($blocked)) {
            return [
                'category' => 'environment',
                'label' => 'Disabled Functions',
                'status' => 'ok',
                'value' => 'None blocking',
                'detail' => null,
            ];
        }

        return [
            'category' => 'environment',
            'label' => 'Disabled Functions',
            'status' => 'warning',
            'value' => count($blocked).' functions disabled',
            'detail' => implode(', ', $blocked).' — may affect updates and queue workers.',
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // APPLICATION CHECKS
    // ═══════════════════════════════════════════════════════════════

    private function checkAppUrl(): array
    {
        $appUrl = config('app.url');
        $currentUrl = request()->getSchemeAndHttpHost();

        if (! $appUrl || $appUrl === 'http://localhost') {
            return [
                'category' => 'application',
                'label' => 'APP_URL',
                'status' => 'warning',
                'value' => $appUrl ?: 'not set',
                'detail' => 'APP_URL should match your domain for correct link generation.',
            ];
        }

        $matches = rtrim($appUrl, '/') === rtrim($currentUrl, '/');

        return [
            'category' => 'application',
            'label' => 'APP_URL',
            'status' => $matches ? 'ok' : 'warning',
            'value' => $appUrl,
            'detail' => $matches ? null : "Current URL ({$currentUrl}) does not match APP_URL.",
        ];
    }

    private function checkAppDebug(): array
    {
        $debug = config('app.debug');

        return [
            'category' => 'application',
            'label' => 'Debug Mode',
            'status' => $debug ? 'warning' : 'ok',
            'value' => $debug ? 'Enabled' : 'Disabled',
            'detail' => $debug ? 'Debug mode should be disabled in production to prevent exposing sensitive data.' : null,
        ];
    }

    private function checkHttps(): array
    {
        $isSecure = request()->isSecure();

        return [
            'category' => 'application',
            'label' => 'HTTPS',
            'status' => $isSecure ? 'ok' : 'warning',
            'value' => $isSecure ? 'Active' : 'Not detected',
            'detail' => $isSecure ? null : 'HTTPS is strongly recommended for production environments.',
        ];
    }

    private function checkAppVersion(): array
    {
        $version = config('version.current', config('app.version', 'unknown'));

        return [
            'category' => 'application',
            'label' => 'App Version',
            'status' => 'ok',
            'value' => (string) $version,
            'detail' => null,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // DATABASE CHECKS
    // ═══════════════════════════════════════════════════════════════

    private function checkDatabaseConnection(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'category' => 'database',
                'label' => 'Database Connection',
                'status' => 'ok',
                'value' => 'Connected',
                'detail' => 'Driver: '.config('database.default'),
            ];
        } catch (\Exception $e) {
            return [
                'category' => 'database',
                'label' => 'Database Connection',
                'status' => 'critical',
                'value' => 'Failed',
                'detail' => 'Could not connect to database. Check .env configuration.',
            ];
        }
    }

    private function checkDatabaseVersion(): array
    {
        try {
            $driver = config('database.default');
            $version = match ($driver) {
                'mysql' => DB::selectOne('SELECT VERSION() as version')->version ?? 'unknown',
                'pgsql' => DB::selectOne('SELECT version() as version')->version ?? 'unknown',
                'sqlite' => DB::selectOne('SELECT sqlite_version() as version')->version ?? 'unknown',
                default => 'unknown',
            };

            return [
                'category' => 'database',
                'label' => 'Database Version',
                'status' => 'ok',
                'value' => ucfirst($driver).' '.$version,
                'detail' => null,
            ];
        } catch (\Exception $e) {
            return [
                'category' => 'database',
                'label' => 'Database Version',
                'status' => 'warning',
                'value' => 'Could not determine',
                'detail' => null,
            ];
        }
    }

    private function checkPendingMigrations(): array
    {
        try {
            $tableName = config('database.migrations.table', 'migrations');

            if (! Schema::hasTable($tableName)) {
                return [
                    'category' => 'database',
                    'label' => 'Migrations',
                    'status' => 'critical',
                    'value' => 'Migration table missing',
                    'detail' => 'Run php artisan migrate to initialize the database.',
                ];
            }

            $repository = new \Illuminate\Database\Migrations\DatabaseMigrationRepository(app('db'), $tableName);
            $ran = $repository->getRan();
            $migrations = app('migrator')->getMigrationFiles(database_path('migrations'));
            $pending = array_diff(array_keys($migrations), $ran);

            if (empty($pending)) {
                return [
                    'category' => 'database',
                    'label' => 'Migrations',
                    'status' => 'ok',
                    'value' => count($ran).' migrations applied',
                    'detail' => null,
                ];
            }

            return [
                'category' => 'database',
                'label' => 'Migrations',
                'status' => 'warning',
                'value' => count($pending).' pending',
                'detail' => 'Run database migrations from the dashboard or CLI.',
            ];
        } catch (\Exception $e) {
            return [
                'category' => 'database',
                'label' => 'Migrations',
                'status' => 'warning',
                'value' => 'Could not check',
                'detail' => null,
            ];
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // STORAGE CHECKS
    // ═══════════════════════════════════════════════════════════════

    private function checkWritableDirectories(): array
    {
        $directories = [
            'storage/app' => storage_path('app'),
            'storage/framework/cache' => storage_path('framework/cache'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/framework/views' => storage_path('framework/views'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        $notWritable = [];
        foreach ($directories as $label => $path) {
            if (! is_writable($path)) {
                $notWritable[] = $label;
            }
        }

        if (empty($notWritable)) {
            return [
                'category' => 'storage',
                'label' => 'Writable Directories',
                'status' => 'ok',
                'value' => count($directories).' directories writable',
                'detail' => null,
            ];
        }

        return [
            'category' => 'storage',
            'label' => 'Writable Directories',
            'status' => 'critical',
            'value' => count($notWritable).' not writable',
            'detail' => 'Not writable: '.implode(', ', $notWritable),
        ];
    }

    private function checkStorageSymlink(): array
    {
        $publicStorage = public_path('storage');
        $linkExists = is_link($publicStorage) || is_dir($publicStorage);

        return [
            'category' => 'storage',
            'label' => 'Storage Symlink',
            'status' => $linkExists ? 'ok' : 'warning',
            'value' => $linkExists ? 'Linked' : 'Missing',
            'detail' => $linkExists ? null : 'Run php artisan storage:link to create the public storage symlink.',
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // SERVICES CHECKS
    // ═══════════════════════════════════════════════════════════════

    private function checkQueueDriver(): array
    {
        $driver = config('queue.default');

        if ($driver === 'null') {
            return [
                'category' => 'services',
                'label' => 'Queue Driver',
                'status' => 'warning',
                'value' => 'Null',
                'detail' => 'Queue is disabled — queued jobs (email, notifications) will be silently discarded.',
            ];
        }

        if ($driver === 'sync') {
            return [
                'category' => 'services',
                'label' => 'Queue Driver',
                'status' => 'ok',
                'value' => 'Sync',
                'detail' => 'Jobs run inline during the request. For high-volume or SaaS usage, consider database or Redis queues with a worker process.',
            ];
        }

        return [
            'category' => 'services',
            'label' => 'Queue Driver',
            'status' => 'ok',
            'value' => ucfirst($driver),
            'detail' => null,
        ];
    }

    private function checkMailDriver(): array
    {
        $driver = config('mail.default');
        $isLog = $driver === 'log';
        $isNull = in_array($driver, ['null', 'array']);

        if ($isNull) {
            return [
                'category' => 'services',
                'label' => 'Mail Driver',
                'status' => 'warning',
                'value' => ucfirst($driver),
                'detail' => 'Mail is disabled. Transactional emails (OTP, resets) will not be sent.',
            ];
        }

        if ($isLog) {
            return [
                'category' => 'services',
                'label' => 'Mail Driver',
                'status' => 'warning',
                'value' => 'Log',
                'detail' => 'Mail goes to log file only. Configure SMTP or a mail service for production.',
            ];
        }

        return [
            'category' => 'services',
            'label' => 'Mail Driver',
            'status' => 'ok',
            'value' => ucfirst($driver),
            'detail' => null,
        ];
    }

    private function checkCronStatus(): array
    {
        // Check if the scheduler has run recently by looking at the cache
        $lastRun = cache()->get('health:scheduler_last_run');

        if ($lastRun) {
            $minutesAgo = (int) now()->diffInMinutes($lastRun, absolute: true);

            if ($minutesAgo <= 5) {
                return [
                    'category' => 'services',
                    'label' => 'Cron / Scheduler',
                    'status' => 'ok',
                    'value' => "Last run {$minutesAgo}m ago",
                    'detail' => null,
                ];
            }

            return [
                'category' => 'services',
                'label' => 'Cron / Scheduler',
                'status' => 'warning',
                'value' => "Last run {$minutesAgo}m ago",
                'detail' => 'Scheduler has not run recently. Check cron configuration.',
            ];
        }

        return [
            'category' => 'services',
            'label' => 'Cron / Scheduler',
            'status' => 'warning',
            'value' => 'Unknown',
            'detail' => 'No scheduler heartbeat detected. Cron may not be configured.',
        ];
    }

    private function checkBillingProvider(): array
    {
        try {
            $manager = app(BillingManager::class);
            $provider = $manager->provider();
            $name = $provider->name();

            if ($name === 'null') {
                return [
                    'category' => 'services',
                    'label' => 'Billing Provider',
                    'status' => 'ok',
                    'value' => 'Manual / Offline',
                    'detail' => 'Partner plans are managed manually via the admin dashboard.',
                ];
            }

            if ($name === 'stripe') {
                if ($provider->isConfigured()) {
                    return [
                        'category' => 'services',
                        'label' => 'Billing Provider',
                        'status' => 'ok',
                        'value' => 'Stripe',
                        'detail' => null,
                    ];
                }

                if (! $provider->hasApiKeys()) {
                    return [
                        'category' => 'services',
                        'label' => 'Billing Provider',
                        'status' => 'warning',
                        'value' => 'Stripe (not configured)',
                        'detail' => 'BILLING_PROVIDER is set to stripe but STRIPE_KEY and STRIPE_SECRET are missing. Set these in .env or switch to manual billing.',
                    ];
                }

                // API keys present but webhook secret missing — critical
                // because Cashier accepts unsigned requests without it.
                return [
                    'category' => 'services',
                    'label' => 'Billing Provider',
                    'status' => 'critical',
                    'value' => 'Stripe (webhook insecure)',
                    'detail' => 'STRIPE_WEBHOOK_SECRET is not set. Without it, the webhook endpoint accepts unsigned requests. Set STRIPE_WEBHOOK_SECRET in .env to the signing secret from your Stripe Dashboard.',
                ];
            }

            return [
                'category' => 'services',
                'label' => 'Billing Provider',
                'status' => 'ok',
                'value' => ucfirst($name),
                'detail' => null,
            ];
        } catch (\Exception $e) {
            return [
                'category' => 'services',
                'label' => 'Billing Provider',
                'status' => 'critical',
                'value' => 'Error',
                'detail' => 'Could not resolve billing provider: '.$e->getMessage(),
            ];
        }
    }

}
