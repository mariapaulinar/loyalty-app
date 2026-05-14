<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RewardLoyaltyUpdate;
use App\Services\License\LicenseService;
use App\Services\License\UpdateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * LicenseController - Admin License & Updates Management
 *
 * Provides a beautiful, intuitive interface for super admins to:
 * - Activate and manage their CodeCanyon license
 * - Check for and install updates
 * - View update history
 */
class LicenseController extends Controller
{
    public function __construct(
        private readonly LicenseService $licenseService,
        private readonly UpdateService $updateService
    ) {}

    /**
     * Display license management page
     */
    public function index(string $locale): View
    {
        $licenseStatus = $this->licenseService->getStatus();
        $updates = RewardLoyaltyUpdate::query()
            ->latest()
            ->take(10)
            ->get();

        $lastUpdate = RewardLoyaltyUpdate::query()
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        // Calculate if support expires soon (within 30 days)
        $supportExpiresSoon = false;
        $daysRemaining = null;
        if (! empty($licenseStatus['support_expires_at'])) {
            $expiryDate = \Carbon\Carbon::parse($licenseStatus['support_expires_at']);
            if ($expiryDate->isFuture()) {
                $daysRemaining = now()->diffInDays($expiryDate, false);
                $supportExpiresSoon = $daysRemaining <= 30;
            }
        }

        // Get available backups for restore functionality
        $backups = $this->updateService->getAvailableBackups();

        // Scan for manually placed update packages
        $manualPackages = $this->updateService->getManualUpdatePackages();

        return view('admin.license.index', [
            'licenseStatus' => $licenseStatus['status'] ?? 'inactive',
            'supportExpiresAt' => $licenseStatus['support_expires_at'] ?? null,
            'supportExpiresSoon' => $supportExpiresSoon,
            'daysRemaining' => $daysRemaining,
            'buyerEmail' => $licenseStatus['buyer_email'] ?? null,
            'updates' => $updates,
            'lastUpdate' => $lastUpdate,
            'backups' => $backups,
            'manualPackages' => $manualPackages,
        ]);
    }

    /**
     * Activate license
     */
    public function activate(string $locale, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_code' => 'required|string|max:36',
            'domain' => 'required|string|max:255',
        ]);

        $result = $this->licenseService->activate(
            $validated['purchase_code'],
            $validated['domain']
        );

        if ($result['success']) {
            return redirect()
                ->route('admin.license.index')
                ->with('licenseAlert', [
                    'type' => 'success',
                    'title' => trans('common.license.activated'),
                    'message' => trans('common.license.activated_desc'),
                ]);
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('licenseAlert', [
                'type' => 'error',
                'title' => trans('common.license.activation_failed'),
                'message' => $result['message'] ?? trans('common.license.activation_failed_desc'),
            ]);
    }

    /**
     * Refresh license status from server
     */
    public function refresh(string $locale): RedirectResponse
    {
        $result = $this->licenseService->validate(force: true);

        if ($result['is_valid'] && $result['support_active']) {
            return redirect()
                ->route('admin.license.index')
                ->with('licenseAlert', [
                    'type' => 'success',
                    'title' => trans('common.license.refreshed'),
                    'message' => trans('common.license.refreshed_active'),
                ]);
        }

        if ($result['is_valid'] && ! $result['support_active']) {
            return redirect()
                ->route('admin.license.index')
                ->with('licenseAlert', [
                    'type' => 'warning',
                    'title' => trans('common.license.support_expired_title'),
                    'message' => trans('common.license.refreshed_expired'),
                ]);
        }

        return redirect()
            ->route('admin.license.index')
            ->with('licenseAlert', [
                'type' => 'error',
                'title' => trans('common.license.refresh_failed_title'),
                'message' => trans('common.license.refresh_failed'),
            ]);
    }

    /**
     * Deactivate license
     */
    public function deactivate(string $locale): RedirectResponse
    {
        $this->licenseService->deactivate();

        return redirect()
            ->route('admin.license.index')
            ->with('licenseAlert', [
                'type' => 'info',
                'title' => trans('common.license.deactivated'),
                'message' => trans('common.license.deactivated_desc'),
            ]);
    }

    /**
     * Check for available updates
     */
    public function checkUpdates(string $locale): RedirectResponse
    {
        $result = $this->updateService->checkForUpdates();

        if (isset($result['error'])) {
            return redirect()
                ->route('admin.license.index')
                ->with('licenseAlert', [
                    'type' => 'warning',
                    'title' => trans('common.license.update_check_failed'),
                    'message' => $result['error'],
                ]);
        }

        if ($result['has_update']) {
            $isCritical = $result['is_critical'] ?? false;

            return redirect()
                ->route('admin.license.index')
                ->with('updateAvailable', [
                    'version' => $result['latest_version'],
                    'changelog' => $result['changelog'],
                    'commits' => $result['commits'],
                    'download_url' => $result['download_url'],
                    'package_hash' => $result['package_hash'],
                    'package_size' => $result['package_size'],
                    'requires' => $result['requires'],
                    'is_critical' => $isCritical,
                ])
                ->with('licenseAlert', [
                    'type' => $isCritical ? 'warning' : 'success',
                    'title' => $isCritical
                        ? trans('common.license.critical_update_title')
                        : trans('common.license.update_available'),
                    'message' => $isCritical
                        ? trans('common.license.critical_update', ['version' => $result['latest_version']])
                        : trans('common.license.update_found', ['version' => $result['latest_version']]),
                ]);
        }

        return redirect()
            ->route('admin.license.index')
            ->with('licenseAlert', [
                'type' => 'success',
                'title' => trans('common.license.up_to_date_title'),
                'message' => trans('common.license.up_to_date'),
            ]);
    }

    /**
     * Install update (synchronous)
     *
     * Returns a static HTML page that doesn't depend on Laravel,
     * since Laravel files will be replaced during the update.
     */
    public function installUpdate(string $locale, Request $request)
    {
        $validated = $request->validate([
            'download_url' => 'required|string|url',
            'version' => 'required|string',
            'package_hash' => 'required|string',
        ]);

        $result = $this->updateService->applyUpdate(
            $validated['download_url'],
            $validated['version'],
            $validated['package_hash']
        );

        if ($result['success'] && isset($result['background'])) {
            // Create standalone progress page and redirect to it
            // This ensures polling works even if Laravel is down
            $url = $this->updateService->createProgressPage('update', $validated['version']);

            return redirect($url);
        }

        if ($result['success']) {
            return redirect()
                ->route('admin.license.index')
                ->with('licenseAlert', [
                    'type' => 'success',
                    'title' => trans('common.license.update_complete'),
                    'message' => $result['message'],
                ]);
        }

        return redirect()
            ->route('admin.license.index')
            ->with('licenseAlert', [
                'type' => 'error',
                'title' => trans('common.license.update_failed'),
                'message' => $result['message'],
            ]);
    }

    /**
     * Install a manually placed update package
     */
    public function installManualUpdate(string $locale, Request $request)
    {
        $filename = $request->input('filename', '');

        // Validate: filename must exist in scanned packages (prevents path traversal,
        // handles any character legal on the filesystem: spaces, parentheses, unicode)
        $packages = $this->updateService->getManualUpdatePackages();
        $package = collect($packages)->firstWhere('filename', $filename);

        if (! $package || ! $package['version']) {
            return redirect()
                ->route('admin.license.index')
                ->with('licenseAlert', [
                    'type' => 'error',
                    'title' => trans('common.license.update_failed'),
                    'message' => 'Update package not found or unrecognized.',
                ]);
        }

        $result = $this->updateService->applyManualUpdate($filename);

        if ($result['success'] && isset($result['background'])) {
            $url = $this->updateService->createProgressPage('update', $package['version']);

            return redirect($url);
        }

        if ($result['success']) {
            return redirect()
                ->route('admin.license.index')
                ->with('licenseAlert', [
                    'type' => 'success',
                    'title' => trans('common.license.update_complete'),
                    'message' => $result['message'],
                ]);
        }

        return redirect()
            ->route('admin.license.index')
            ->with('licenseAlert', [
                'type' => 'error',
                'title' => trans('common.license.update_failed'),
                'message' => $result['message'],
            ]);
    }

    /**
     * Render a static HTML page for the update process
     *
     * This page doesn't depend on Laravel/Blade since files will be replaced.
     * It polls a flag file to detect completion.
     */
    protected function renderStaticUpdatePage(string $version, string $locale): \Illuminate\Http\Response
    {
        $appName = config('app.name', 'Reward Loyalty');
        $appInitial = strtoupper(substr($appName, 0, 1));
        $redirectUrl = route('admin.license.index');
        $checkStatusUrl = route('admin.license.check-status');
        $cssUrl = asset('assets/css/static-pages.css');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installing Update - {$appName}</title>
    <link rel="stylesheet" href="{$cssUrl}">
</head>
<body class="bg-secondary-900 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
        <div class="static-card">
            <div class="static-card-header">
                <div class="flex items-center gap-3">
                    <div class="app-icon app-icon-primary">{$appInitial}</div>
                    <div>
                        <h2 class="text-lg font-semibold text-white">Installing Update</h2>
                        <p class="text-sm text-secondary-400">Updating to version {$version}</p>
                    </div>
                </div>
            </div>

            <div class="static-card-body">
                <div class="space-y-6">
                    <div class="flex items-center justify-center py-8">
                        <div class="spinner-container">
                            <div class="spinner-ring"></div>
                            <div class="spinner-icon spinner-icon-primary">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 text-center">
                        <h3 id="status-title" class="text-lg font-semibold text-white">Update in Progress</h3>
                        <p id="status-message" class="text-sm text-secondary-400">
                            Please wait while the update is being installed. This may take 30-60 seconds.
                        </p>
                    </div>

                    <div class="static-alert static-alert-amber">
                        <svg class="static-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <p class="static-alert-title">Keep this window open</p>
                            <p class="static-alert-text">Do not close or navigate away. You'll be redirected automatically when complete.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="static-card-footer">
                <div class="status-footer">
                    <span id="elapsed-time">Elapsed: 0s</span>
                    <span id="check-count">Checking...</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        const startTime = Date.now();
        let checkCount = 0;
        
        function updateElapsed() {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            document.getElementById('elapsed-time').textContent = 'Elapsed: ' + elapsed + 's';
        }
        
        function checkStatus() {
            checkCount++;
            document.getElementById('check-count').textContent = 'Check #' + checkCount;
            
            fetch('{$checkStatusUrl}')
                .then(response => response.json())
                .then(data => {
                    if (data.completed) {
                        document.getElementById('status-title').textContent = 'Update Complete!';
                        document.getElementById('status-message').textContent = 'Redirecting to license page...';
                        setTimeout(() => window.location.href = '{$redirectUrl}', 1500);
                    } else if (data.failed) {
                        document.getElementById('status-title').textContent = 'Update Failed';
                        document.getElementById('status-message').textContent = data.error || 'An error occurred during the update.';
                        document.getElementById('status-message').className = 'text-sm text-red-400';
                        setTimeout(() => window.location.href = '{$redirectUrl}?error=' + encodeURIComponent(data.error || 'Update failed'), 3000);
                    }
                })
                .catch(error => {
                    // Server might be restarting, keep trying
                    console.log('Check failed (server restarting?):', error);
                });
        }
        
        setInterval(updateElapsed, 1000);
        setInterval(checkStatus, 2000);
        setTimeout(checkStatus, 3000);
        setTimeout(() => { window.location.href = '{$redirectUrl}'; }, 120000);
    </script>
</body>
</html>
HTML;

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Check update status (for background updates)
     */
    public function checkStatus(string $locale): JsonResponse
    {
        $successFlag = storage_path('app/update_success.flag');
        $failedFlag = storage_path('app/update_failed.flag');

        if (file_exists($successFlag)) {
            unlink($successFlag);

            return response()->json([
                'completed' => true,
                'failed' => false,
            ]);
        }

        if (file_exists($failedFlag)) {
            $error = file_get_contents($failedFlag);
            unlink($failedFlag);

            return response()->json([
                'completed' => false,
                'failed' => true,
                'error' => $error,
            ]);
        }

        return response()->json([
            'completed' => false,
            'failed' => false,
        ]);
    }

    /**
     * Restore from backup
     *
     * Allows reverting to a previous version using a stored backup.
     */
    public function restoreBackup(string $locale, Request $request)
    {
        $validated = $request->validate([
            'backup_path' => 'required|string',
        ]);

        // Verify the path is within the backups directory (security)
        $backupsDir = storage_path('app/backups');
        $requestedPath = realpath($validated['backup_path']);

        if (! $requestedPath || ! str_starts_with($requestedPath, $backupsDir)) {
            return redirect()
                ->route('admin.license.index')
                ->with('licenseAlert', [
                    'type' => 'error',
                    'title' => trans('common.license.restore_failed_title'),
                    'message' => trans('common.license.backup_not_found'),
                ]);
        }

        $result = $this->updateService->restoreFromBackup($requestedPath);

        if ($result['success'] && isset($result['background'])) {
            $url = $this->updateService->createProgressPage('restore', $result['version'] ?? 'previous');

            return redirect($url);
        }

        if ($result['success']) {
            return redirect()
                ->route('admin.license.index')
                ->with('licenseAlert', [
                    'type' => 'success',
                    'title' => trans('common.license.restore_complete_title'),
                    'message' => $result['message'],
                ]);
        }

        return redirect()
            ->route('admin.license.index')
            ->with('licenseAlert', [
                'type' => 'error',
                'title' => trans('common.license.restore_failed_title'),
                'message' => $result['message'],
            ]);
    }

    /**
     * Render static HTML page for restore process
     */
    protected function renderStaticRestorePage(string $version, string $locale): \Illuminate\Http\Response
    {
        $appName = config('app.name', 'Reward Loyalty');
        $appInitial = strtoupper(substr($appName, 0, 1));
        $redirectUrl = route('admin.license.index');
        $checkStatusUrl = route('admin.license.check-restore-status');
        $cssUrl = asset('assets/css/static-pages.css');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restoring Backup - {$appName}</title>
    <link rel="stylesheet" href="{$cssUrl}">
</head>
<body class="bg-secondary-900 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
        <div class="static-card">
            <div class="static-card-header">
                <div class="flex items-center gap-3">
                    <div class="app-icon app-icon-amber">{$appInitial}</div>
                    <div>
                        <h2 class="text-lg font-semibold text-white">Restoring Backup</h2>
                        <p class="text-sm text-secondary-400">Reverting to version {$version}</p>
                    </div>
                </div>
            </div>

            <div class="static-card-body">
                <div class="space-y-6">
                    <div class="flex items-center justify-center py-8">
                        <div class="spinner-container">
                            <div class="spinner-ring spinner-ring-amber"></div>
                            <div class="spinner-icon spinner-icon-amber">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 text-center">
                        <h3 id="status-title" class="text-lg font-semibold text-white">Restore in Progress</h3>
                        <p id="status-message" class="text-sm text-secondary-400">
                            Extracting and restoring files from backup. This may take 30-60 seconds.
                        </p>
                    </div>

                    <div class="static-alert static-alert-amber">
                        <svg class="static-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <p class="static-alert-title">Keep this window open</p>
                            <p class="static-alert-text">Do not close or navigate away. You'll be redirected automatically when complete.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="static-card-footer">
                <div class="status-footer">
                    <span id="elapsed-time">Elapsed: 0s</span>
                    <span id="check-count">Checking...</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        const startTime = Date.now();
        let checkCount = 0;
        
        function updateElapsed() {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            document.getElementById('elapsed-time').textContent = 'Elapsed: ' + elapsed + 's';
        }
        
        function checkStatus() {
            checkCount++;
            document.getElementById('check-count').textContent = 'Check #' + checkCount;
            
            fetch('{$checkStatusUrl}')
                .then(response => response.json())
                .then(data => {
                    if (data.completed) {
                        document.getElementById('status-title').textContent = 'Restore Complete!';
                        document.getElementById('status-message').textContent = 'Redirecting to license page...';
                        setTimeout(() => window.location.href = '{$redirectUrl}?restored=1', 1500);
                    } else if (data.failed) {
                        document.getElementById('status-title').textContent = 'Restore Failed';
                        document.getElementById('status-message').textContent = data.error || 'An error occurred during restore.';
                        document.getElementById('status-message').className = 'text-sm text-red-400';
                        setTimeout(() => window.location.href = '{$redirectUrl}?error=' + encodeURIComponent(data.error || 'Restore failed'), 3000);
                    }
                })
                .catch(error => {
                    console.log('Check failed (server restarting?):', error);
                });
        }
        
        setInterval(updateElapsed, 1000);
        setInterval(checkStatus, 2000);
        setTimeout(checkStatus, 3000);
        setTimeout(() => { window.location.href = '{$redirectUrl}'; }, 120000);
    </script>
</body>
</html>
HTML;

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Check restore status (for background restores)
     */
    public function checkRestoreStatus(string $locale): JsonResponse
    {
        $successFlag = storage_path('app/restore_success.flag');
        $failedFlag = storage_path('app/restore_failed.flag');

        if (file_exists($successFlag)) {
            unlink($successFlag);

            return response()->json([
                'completed' => true,
                'failed' => false,
            ]);
        }

        if (file_exists($failedFlag)) {
            $error = file_get_contents($failedFlag);
            unlink($failedFlag);

            return response()->json([
                'completed' => false,
                'failed' => true,
                'error' => $error,
            ]);
        }

        return response()->json([
            'completed' => false,
            'failed' => false,
        ]);
    }

    /**
     * Delete a backup file
     *
     * Allows users to manually manage their backups by removing
     * unwanted backup files to free up disk space.
     */
    public function deleteBackup(string $locale, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'backup_path' => 'required|string',
        ]);

        // Security: Verify the path is within the backups directory
        $backupsDir = storage_path('app/backups');
        $requestedPath = realpath($validated['backup_path']);

        if (! $requestedPath || ! str_starts_with($requestedPath, $backupsDir)) {
            return redirect()
                ->route('admin.license.index')
                ->with('licenseAlert', [
                    'type' => 'error',
                    'title' => trans('common.license.delete_failed_title'),
                    'message' => trans('common.license.backup_not_found'),
                ]);
        }

        $result = $this->updateService->deleteBackup($requestedPath);

        if ($result['success']) {
            return redirect()
                ->route('admin.license.index')
                ->with('licenseAlert', [
                    'type' => 'success',
                    'title' => trans('common.license.backup_deleted_title'),
                    'message' => $result['message'],
                ]);
        }

        return redirect()
            ->route('admin.license.index')
            ->with('licenseAlert', [
                'type' => 'error',
                'title' => trans('common.license.delete_failed_title'),
                'message' => $result['message'],
            ]);
    }
}
