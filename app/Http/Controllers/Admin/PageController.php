<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Main admin page controller handling the dashboard and system operations.
 *
 * Design Philosophy:
 * The dashboard is the command center — like Revolut's home screen,
 * it should provide instant insight and quick navigation.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        protected AdminDashboardService $dashboardService
    ) {}

    /**
     * Display the admin dashboard.
     *
     * "Welcome Home 2.0" — Inspired by Linear, Revolut, and Stripe.
     * Not just a dashboard, but a command center that tells a story.
     */
    public function index(string $locale, Request $request): View
    {
        $user = auth('admin')->user();
        $isAdmin = $user->role == 1;
        $localeSlug = app()->make('i18n')->language->current->localeSlug;

        // Get comprehensive dashboard data (includes insights, activity, week summary)
        $dashboardData = $isAdmin ? $this->dashboardService->getDashboardData() : null;

        // Check for pending migrations (admin only)
        $hasMigrations = $isAdmin ? $this->hasPendingMigrations() : false;

        // Get personalized greeting
        $greeting = $this->dashboardService->getGreeting($user->time_zone);

        // Build quick actions - now contextual based on data
        $quickActions = $this->buildQuickActions($isAdmin, $dashboardData);

        return view('admin.index', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'greeting' => $greeting,
            'hasMigrations' => $hasMigrations,
            'dashboardData' => $dashboardData,
            'quickActions' => $quickActions,
            'localeSlug' => $localeSlug,
        ]);
    }

    /**
     * Build navigation blocks based on user role.
     *
     * @return array<int, array<string, string>>
     */
    private function buildNavigationBlocks(bool $isAdmin, string $localeSlug): array
    {
        $blocks = [];

        // Non-admin sees account settings first
        if (! $isAdmin) {
            $blocks[] = [
                'link' => route('admin.data.list', ['name' => 'account']),
                'icon' => 'user-circle',
                'title' => trans('common.account_settings'),
                'desc' => trans('common.adminDashboardBlocks.account_settings'),
                'color' => 'primary',
            ];
        }

        // Admin-only sections
        if ($isAdmin) {
            $blocks[] = [
                'link' => route('admin.data.list', ['name' => 'admins']),
                'icon' => 'shield',
                'title' => trans('common.administrators'),
                'desc' => trans('common.adminDashboardBlocks.administrators', [
                    'localeSlug' => '<span class="font-mono text-xs bg-secondary-100 dark:bg-secondary-800 px-1.5 py-0.5 rounded">/'.$localeSlug.'/admin/</span>',
                ]),
                'color' => 'violet',
            ];

            $blocks[] = [
                'link' => route('admin.data.list', ['name' => 'networks']),
                'icon' => 'network',
                'title' => trans('common.networks'),
                'desc' => trans('common.adminDashboardBlocks.networks'),
                'color' => 'cyan',
            ];
        }

        // Partners (all roles)
        $blocks[] = [
            'link' => route('admin.data.list', ['name' => 'partners']),
            'icon' => 'store',
            'title' => trans('common.partners'),
            'desc' => trans('common.adminDashboardBlocks.partners', [
                'localeSlug' => '<span class="font-mono text-xs bg-secondary-100 dark:bg-secondary-800 px-1.5 py-0.5 rounded">/'.$localeSlug.'/partner/</span>',
            ]),
            'color' => 'emerald',
        ];

        // Members (admin only)
        if ($isAdmin) {
            $blocks[] = [
                'link' => route('admin.data.list', ['name' => 'members']),
                'icon' => 'users',
                'title' => trans('common.members'),
                'desc' => trans('common.adminDashboardBlocks.members'),
                'color' => 'amber',
            ];

            // Activity Log Analytics
            $blocks[] = [
                'link' => route('admin.activity-logs.analytics'),
                'icon' => 'bar-chart-2',
                'title' => trans('common.activity_log_analytics'),
                'desc' => trans('common.adminDashboardBlocks.analytics'),
                'color' => 'violet',
            ];

            // License & Updates
            $blocks[] = [
                'link' => route('admin.license.index'),
                'icon' => 'shield-check',
                'title' => trans('common.license.title'),
                'desc' => trans('common.adminDashboardBlocks.license'),
                'color' => 'cyan',
            ];
        }

        return $blocks;
    }

    /**
     * Build quick action buttons based on user role and current context.
     *
     * Simplified to avoid redundancy with sidebar navigation.
     * Only the primary action (+ Add Partner) is shown here.
     *
     * @return array<int, array<string, string>>
     */
    private function buildQuickActions(bool $isAdmin, ?array $dashboardData = null): array
    {
        $actions = [];

        if ($isAdmin) {
            // Primary action only - Add Partner
            // Other links (Analytics, Partners, Members) are in sidebar
            $actions[] = [
                'link' => route('admin.data.insert', ['name' => 'partners']),
                'icon' => 'plus',
                'label' => trans('common.add_partner'),
                'variant' => 'primary',
            ];
        }

        return array_slice($actions, 0, 4); // Max 4 quick actions
    }

    /**
     * Check if there are pending database migrations.
     */
    private function hasPendingMigrations(): bool
    {
        // Extract only the table name from the configuration array
        $tableName = config('database.migrations.table');

        // Initialize the DatabaseMigrationRepository with the correct table name
        $repository = new DatabaseMigrationRepository(app('db'), $tableName);

        // Fetch the list of migrations that have been run
        $ran = $repository->getRan();

        // Get all migration files
        $migrations = app('migrator')->getMigrationFiles(database_path('migrations'));

        // Determine pending migrations
        $pendingMigrations = array_diff(array_keys($migrations), $ran);

        return ! empty($pendingMigrations);
    }

    /**
     * Run database migration.
     *
     * @param  string  $locale  The locale for the current request.
     */
    public function runMigrations(string $locale, Request $request): RedirectResponse
    {
        try {
            $this->ensureConsoleOutputConstantsDefined();

            // Run the database migrations
            Artisan::call('migrate', ['--force' => true]);

            // Create a toast message for the result of the migrations
            $toast = [
                'type' => 'success',
                'size' => 'lg',
                'text' => trans('common.database_migrations_success'),
            ];
        } catch (\Exception $e) {
            // Report any exceptions that occur during the migration
            report($e);

            // Create a toast message for the result of the migrations
            $toast = [
                'type' => 'warning',
                'size' => 'lg',
                'text' => trans('common.database_migrations_failure'),
            ];
        }

        // Redirect to the admin index page with the toast message
        return redirect(route('admin.index'))->with('toast', $toast);
    }

    /**
     * Ensure the STDOUT, STDERR, and STDIN constants are defined for Artisan.
     */
    private function ensureConsoleOutputConstantsDefined(): void
    {
        if (! defined('STDIN')) {
            define('STDIN', fopen('php://stdin', 'rb'));
        }

        if (! defined('STDOUT')) {
            define('STDOUT', fopen('php://stdout', 'wb'));
        }

        if (! defined('STDERR')) {
            define('STDERR', fopen('php://stderr', 'wb'));
        }
    }
}
