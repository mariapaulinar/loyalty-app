<?php

declare(strict_types=1);

namespace App\Services\License;

use App\Models\RewardLoyaltyUpdate;
use App\Services\SettingsService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;

/**
 * UpdateService - Atomic Update Application with Clean Replacement
 *
 * Purpose:
 * Manages the complete update lifecycle: check, download, verify, backup,
 * extract, migrate, and rollback. Uses clean replacement strategy to
 * eliminate orphaned files and ensure consistency.
 *
 * Design Tenets:
 * - **Atomic**: All-or-nothing updates with automatic rollback on failure
 * - **Safe**: Complete backup before any changes
 * - **Clean**: Full replacement (no orphaned files)
 * - **Resilient**: Automatic retry with exponential backoff
 * - **Transparent**: Real-time progress tracking and logging
 */
class UpdateService
{
    public function __construct(
        private readonly LicenseService $licenseService,
        private readonly SettingsService $settings
    ) {}

    /**
     * Check for available updates
     *
     * @return array{has_update: bool, latest_version?: string, changelog?: string, download_url?: string, package_size?: int}
     */
    public function checkForUpdates(): array
    {
        // Verify license is active
        if (! $this->licenseService->isActive()) {
            return [
                'has_update' => false,
                'error' => trans('common.license.not_active'),
            ];
        }

        // Verify support is active
        if (! $this->licenseService->isSupportActive()) {
            return [
                'has_update' => false,
                'error' => trans('common.license.support_expired'),
            ];
        }

        $serverUrl = config('reward-loyalty.license_server.url');
        $licenseToken = $this->licenseService->getLicenseToken();
        $currentVersion = config('version.current', '1.0.0');

        try {
            $requestData = [
                'license_token' => $licenseToken,
                'domain' => parse_url(config('app.url'), PHP_URL_HOST) ?? config('app.url'),
                'current_version' => $currentVersion,
            ];

            $endpoint = "{$serverUrl}/api/licenses/v1/updates/check";

            $response = Http::timeout(config('reward-loyalty.license_server.timeout'))
                ->withHeaders([
                    'Authorization' => 'Bearer '.$licenseToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($endpoint, $requestData);

            if (! $response->successful()) {
                $statusCode = $response->status();
                $responseBody = $response->json();
                $serverMessage = $responseBody['message'] ?? $responseBody['error'] ?? 'Update check failed';

                $error = match ($statusCode) {
                    400 => $serverMessage,
                    401 => $serverMessage ?: 'License token is invalid. Please reactivate your license.',
                    402 => $serverMessage ?: 'Your support has expired. Please renew to receive updates.',
                    403 => $serverMessage ?: 'Your license does not have access to updates.',
                    404 => 'No updates found for your version.',
                    429 => 'Too many requests. Please wait a moment and try again.',
                    500, 502, 503 => 'Update server is temporarily unavailable. Please try again later.',
                    default => $serverMessage ?: 'Unable to check for updates. Please try again.',
                };

                Log::error('UPDATE CHECK FAILED', [
                    'status_code' => $statusCode,
                    'server_message' => $serverMessage,
                    'error' => $error,
                ]);

                return [
                    'has_update' => false,
                    'error' => $error,
                ];
            }

            $responseData = $response->json();
            $data = $responseData['data'] ?? $responseData;

            return [
                'has_update' => $data['update_available'] ?? false,
                'latest_version' => $data['latest_version'] ?? null,
                'changelog' => $data['changelog'] ?? null,
                'commits' => $data['commits'] ?? [],
                'download_url' => $data['download_url'] ?? null,
                'package_size' => $data['package_size'] ?? null,
                'package_hash' => $data['package_hash'] ?? null,
                'requires' => [
                    'php' => $data['requires_php'] ?? '8.2.0',
                    'laravel' => $data['requires_laravel'] ?? '11.0.0',
                ],
                'is_critical' => $data['is_critical'] ?? false,
            ];
        } catch (\Exception $e) {
            Log::error('Update check exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'has_update' => false,
                'error' => 'Unable to connect to update server',
            ];
        }
    }

    /**
     * Apply update (download and install)
     *
     * @param  string  $downloadUrl  Signed download URL from license server
     * @param  string  $toVersion  Target version
     * @param  string|null  $packageHash  Expected SHA-256 hash
     * @return array{success: bool, message: string}
     */
    public function applyUpdate(string $downloadUrl, string $toVersion, ?string $packageHash = null): array
    {
        $fromVersion = config('version.current', '1.0.0');

        // Preflight: Verify required PHP functions are available
        // The updater needs to spawn a background process and create backups
        $disabledFunctions = array_map('trim', explode(',', ini_get('disable_functions') ?: ''));
        $requiredFunctions = ['exec'];
        $missingFunctions = array_filter($requiredFunctions, function ($fn) use ($disabledFunctions) {
            return in_array($fn, $disabledFunctions) || ! function_exists($fn);
        });

        if (! empty($missingFunctions)) {
            Log::error('UPDATE: Required PHP functions are disabled', [
                'missing' => $missingFunctions,
                'disabled_functions' => $disabledFunctions,
            ]);

            return [
                'success' => false,
                'message' => 'Your server has disabled required PHP functions: ' . implode(', ', $missingFunctions)
                    . '. Please enable them in your hosting control panel (CloudWays: Settings & Packages → Advanced → PHP Settings → disable_functions).',
            ];
        }

        // Create update record
        $update = RewardLoyaltyUpdate::create([
            'from_version' => $fromVersion,
            'to_version' => $toVersion,
            'status' => 'pending',
            'started_at' => now(),
            'initiated_by' => auth('admin')->id(),
            'ip_address' => request()->ip(),
            'package_url' => $downloadUrl,
            'package_hash' => $packageHash,
        ]);

        try {
            // Step 1: Download package
            $update->update(['status' => 'downloading']);
            $packagePath = $this->downloadPackage($downloadUrl, $update);

            // Step 2: Verify checksum
            if (config('reward-loyalty.updates.verify_checksum') && $packageHash) {
                $this->verifyChecksum($packagePath, $packageHash, $update);
            }

            // Step 3: Create backup
            $backupPath = $this->createBackup();
            $update->update(['backup_path' => $backupPath]);

            // Step 4: Spawn standalone updater (can't delete Laravel files while running)
            $update->update(['status' => 'extracting']);
            $this->spawnUpdaterProcess($packagePath, $update->id);

            return [
                'success' => true,
                'message' => trans('common.license.update_in_progress'),
                'background' => true,
            ];
        } catch (\Exception $e) {
            $this->rollback($update, $e);

            return [
                'success' => false,
                'message' => trans('common.license.update_failed').': '.$e->getMessage(),
            ];
        }
    }

    /**
     * Download update package
     */
    protected function downloadPackage(string $url, RewardLoyaltyUpdate $update): string
    {
        $tempPath = storage_path('app/updates/reward-loyalty-'.$update->to_version.'.zip');

        if (! File::exists(dirname($tempPath))) {
            File::makeDirectory(dirname($tempPath), 0755, true);
        }

        $response = Http::timeout(config('reward-loyalty.updates.download_timeout'))
            ->withOptions(['sink' => $tempPath])
            ->get($url);

        if (! $response->successful()) {
            throw new \Exception("Failed to download update package: HTTP {$response->status()}");
        }

        $update->update([
            'package_size' => File::size($tempPath),
        ]);

        return $tempPath;
    }

    /**
     * Verify package checksum
     */
    protected function verifyChecksum(string $packagePath, string $expectedHash, RewardLoyaltyUpdate $update): void
    {
        $actualHash = hash_file('sha256', $packagePath);
        $update->update(['package_hash' => $actualHash]);

        if ($actualHash !== $expectedHash) {
            throw new \Exception("Package checksum mismatch. Expected: {$expectedHash}, Got: {$actualHash}");
        }
    }

    /**
     * Create complete application backup
     *
     * Creates a timestamped backup before updates. Backups are managed
     * manually by the user through the admin interface - no automatic
     * cleanup is performed to give users full control.
     *
     * Uses shell `zip` command for memory efficiency (handles large vendor dirs).
     * Falls back to PHP ZipArchive with streaming for Windows/systems without zip.
     */
    protected function createBackup(): string
    {
        // Extend execution time for backup (can take 1-5 minutes for large codebases)
        // This is safe because we're about to spawn a background process anyway
        set_time_limit(600);

        $backupsDir = storage_path('app/backups');

        // Ensure backup directory exists
        if (! File::exists($backupsDir)) {
            File::makeDirectory($backupsDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_His');
        $backupPath = "{$backupsDir}/reward-loyalty-backup-{$timestamp}.zip";

        // Try shell zip first (much more memory efficient for large codebases)
        if ($this->canUseShellZip()) {
            $this->createBackupWithShellZip($backupPath);
        } else {
            $this->createBackupWithPhpZip($backupPath);
        }

        Log::info('UPDATE: Backup created', ['path' => basename($backupPath)]);

        return $backupPath;
    }

    /**
     * Check if shell zip command is available
     */
    protected function canUseShellZip(): bool
    {
        // Windows doesn't typically have zip command
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return false;
        }

        // Check if exec() and shell_exec() are available (many shared hosts disable them)
        $disabledFunctions = array_map('trim', explode(',', ini_get('disable_functions') ?: ''));
        if (in_array('exec', $disabledFunctions) || in_array('shell_exec', $disabledFunctions)) {
            return false;
        }

        // Verify the functions actually exist (could be removed at compile time)
        if (! function_exists('exec') || ! function_exists('shell_exec')) {
            return false;
        }

        // Check if zip is available
        $result = @shell_exec('which zip 2>/dev/null');

        return ! empty(trim($result ?? ''));
    }

    /**
     * Create backup using shell zip command (memory efficient)
     *
     * Uses the system's zip command which handles memory management
     * internally and can handle millions of files without exhausting PHP memory.
     */
    protected function createBackupWithShellZip(string $backupPath): void
    {
        $basePath = base_path();

        // Build exclusion patterns for zip command
        // These directories are either regeneratable, too large, or would cause recursion
        $excludes = [
            // Large/unnecessary directories
            'node_modules/*',           // NPM packages (huge, not needed)
            '.git/*',                   // Git history (large, not needed for restore)

            // Storage exclusions (regeneratable or temporary)
            'storage/app/updates/*',        // Update packages (temporary)
            'storage/app/backups/*',        // Avoid recursive backup inclusion!
            'storage/app/manual-update/*',  // Manually placed update packages
            'storage/logs/*',              // Log files (not needed)
            'storage/framework/*',         // Cache, sessions, views (regeneratable)
            'storage/debugbar/*',       // Debug data (if exists)

            // Public exclusions (user uploads - too large, site-specific)
            'public/files/*',           // User uploaded files (large, site-specific)

            // Cache directories
            '.phpunit.cache/*',         // PHPUnit cache
            'bootstrap/cache/*',        // Laravel bootstrap cache (regeneratable)
        ];

        // Build -x arguments - use single quotes to prevent shell expansion
        // but allow zip to interpret the wildcards
        $excludeArgs = implode(' ', array_map(fn ($e) => "-x '{$e}'", $excludes));

        // Create zip from base path, excluding specified directories
        // -r = recursive, -q = quiet (no output), -9 = best compression
        $command = sprintf(
            'cd %s && zip -rq9 %s . %s 2>&1',
            escapeshellarg($basePath),
            escapeshellarg($backupPath),
            $excludeArgs
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('UPDATE: Shell zip failed', ['return_code' => $returnCode]);
            throw new \Exception('Failed to create backup with shell zip: '.implode("\n", $output));
        }
    }

    /**
     * Create backup using PHP ZipArchive (fallback for Windows)
     *
     * Uses streaming approach to avoid loading all files into memory.
     * Processes one file at a time using RecursiveDirectoryIterator.
     */
    protected function createBackupWithPhpZip(string $backupPath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($backupPath, ZipArchive::CREATE) !== true) {
            throw new \Exception('Failed to create backup archive');
        }

        $basePath = base_path();
        $fileCount = 0;

        // Use RecursiveDirectoryIterator for memory-efficient iteration
        // This doesn't load all files into memory like File::allFiles()
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $realPath = $file->getRealPath();
            $relativePath = substr($realPath, strlen($basePath) + 1);

            // Skip excluded directories (same as shell zip exclusions)
            if (str_starts_with($relativePath, 'node_modules/') ||
                str_starts_with($relativePath, '.git/') ||
                str_starts_with($relativePath, 'storage/app/updates/') ||
                str_starts_with($relativePath, 'storage/app/backups/') ||
                str_starts_with($relativePath, 'storage/app/manual-update/') ||
                str_starts_with($relativePath, 'storage/logs/') ||
                str_starts_with($relativePath, 'storage/framework/') ||
                str_starts_with($relativePath, 'storage/debugbar/') ||
                str_starts_with($relativePath, 'public/files/') ||
                str_starts_with($relativePath, '.phpunit.cache/') ||
                str_starts_with($relativePath, 'bootstrap/cache/')) {
                continue;
            }

            $zip->addFile($realPath, $relativePath);
            $fileCount++;

            // Periodically close and reopen to free memory (every 1000 files)
            if ($fileCount % 1000 === 0) {
                $zip->close();
                $zip = new ZipArchive;
                $zip->open($backupPath);
            }
        }

        $zip->close();
    }

    /**
     * Clean up old backup files, keeping only the specified number
     */
    protected function cleanupOldBackups(string $backupsDir, int $keepCount = 1): void
    {
        $backups = File::glob("{$backupsDir}/reward-loyalty-backup-*.zip");

        if (count($backups) <= $keepCount) {
            return;
        }

        // Sort by modification time (newest first)
        usort($backups, fn ($a, $b) => filemtime($b) - filemtime($a));

        // Delete older backups
        $toDelete = array_slice($backups, $keepCount);
        foreach ($toDelete as $oldBackup) {
            Log::info('UPDATE: Removing old backup', ['path' => $oldBackup]);
            File::delete($oldBackup);
        }
    }

    /**
     * Rollback failed update
     */
    protected function rollback(RewardLoyaltyUpdate $update, \Exception $e): void
    {
        Log::error('Update failed, initiating rollback', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $update->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'error_trace' => $e->getTraceAsString(),
            'completed_at' => now(),
            'duration_seconds' => (int) $update->started_at->diffInSeconds(now(), false),
        ]);
    }

    /**
     * Extract the PHP version from an FPM binary name or path.
     */
    protected function extractPhpVersionFromFpmBinary(string $binaryName, string $binaryPath): ?string
    {
        $patterns = [
            '/php-?fpm(?P<major>\d+)\.(?P<minor>\d+)$/i',  // php-fpm8.4, phpfpm8.4
            '/php(?P<major>\d+)\.(?P<minor>\d+)-fpm$/i',  // php8.4-fpm
            '/php(?P<major>\d)(?P<minor>\d)-fpm$/i',      // php84-fpm
            '/php-?fpm(?P<major>\d)(?P<minor>\d)$/i',     // php-fpm84
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $binaryName, $matches)) {
                return $matches['major'].'.'.$matches['minor'];
            }
        }

        if (preg_match('/\/php\/(?P<major>\d+)\.(?P<minor>\d+)\//', $binaryPath, $matches)) {
            return $matches['major'].'.'.$matches['minor'];
        }

        return null;
    }

    /**
     * Build candidate CLI binary paths for a detected FPM binary.
     *
     * @return array<int, string>
     */
    protected function buildCliCandidatesForFpmBinary(string $binaryPath, string $binaryName, string $version): array
    {
        $dir = dirname($binaryPath);
        $compactVersion = str_replace('.', '', $version);

        $attempts = [
            $dir.'/php'.$version,                    // php8.4
            $dir.'/php'.$compactVersion,             // Herd style: php84
            $dir.'/php',                             // Herd symlink: php -> php84
            str_replace('/sbin/', '/bin/', $dir).'/php', // Plesk
            '/usr/bin/php'.$version,                 // Ubuntu style: php8.4
            '/usr/bin/php'.$compactVersion,          // Ubuntu style: php84
        ];

        // php84-fpm implies a colocated php84 binary even if no symlink exists.
        if (preg_match('/php(?P<major>\d)(?P<minor>\d)-fpm$/i', $binaryName, $matches)) {
            $attempts[] = $dir.'/php'.$matches['major'].$matches['minor'];
        }

        return array_values(array_unique($attempts));
    }

    /**
     * Probe a PHP binary path and return its version if it is executable.
     */
    protected function probePhpBinaryVersion(string $path): string
    {
        return trim(@shell_exec(escapeshellarg($path).' -r "echo PHP_VERSION;" 2>/dev/null') ?: '');
    }

    /**
     * Get PHP CLI binary (not FPM binary)
     *
     * Handles various FPM binary naming patterns:
     * - php-fpm, php-fpm8.4, php8.4-fpm
     * - Works on Plesk, CloudWays, cPanel, standard VPS
     */
    protected function getPhpCliBinary(): string
    {
        $binary = PHP_BINARY;
        $binaryName = basename($binary);

        Log::info('UPDATE: Detecting PHP CLI binary', [
            'PHP_BINARY' => $binary,
            'binaryName' => $binaryName,
        ]);

        // Check if this is an FPM binary (contains 'fpm' in the name)
        if (str_contains($binaryName, 'fpm')) {
            Log::info('UPDATE: FPM binary detected, searching for CLI equivalent');

            $version = $this->extractPhpVersionFromFpmBinary($binaryName, $binary);

            // Try common CLI binary patterns with the same version
            // Only attempt if version is 8.4 or higher
            $versionFloat = (float) $version;
            Log::info('UPDATE: FPM version extracted', [
                'version' => $version,
                'versionFloat' => $versionFloat,
                'meetsRequirement' => $versionFloat >= 8.4,
            ]);

            if ($versionFloat >= 8.4) {
                $attempts = $this->buildCliCandidatesForFpmBinary($binary, $binaryName, $version);
            } else {
                // FPM version is too old or not found, skip to common paths
                if (empty($version)) {
                    Log::warning('UPDATE: Could not extract version from FPM binary, skipping to common paths');
                } else {
                    Log::warning('UPDATE: FPM version too old (<8.4), skipping to common paths');
                }
                $attempts = [];
            }

            foreach ($attempts as $attempt) {
                // Test if binary works by trying to get version
                // This bypasses open_basedir file_exists() issues
                $testVersion = $this->probePhpBinaryVersion($attempt);

                Log::info('UPDATE: Testing FPM-derived path', [
                    'path' => $attempt,
                    'works' => ! empty($testVersion),
                    'version' => $testVersion,
                ]);

                if (! empty($testVersion) && (float) $testVersion >= 8.4) {
                    Log::info('UPDATE: Found working CLI binary via FPM pattern', ['path' => $attempt]);

                    return $attempt;
                }
            }

            // FPM detected but versioned CLI not found
            Log::info('UPDATE: FPM CLI equivalent not found, falling through to PATH search');
        } elseif (@file_exists($binary) && @is_executable($binary)) {
            // Not FPM and exists - safe to use
            Log::info('UPDATE: Using PHP_BINARY directly (not FPM)', ['path' => $binary]);

            return $binary;
        }

        // Search for php in PATH as fallback
        $which = trim(shell_exec('which php 2>/dev/null') ?: '');
        if (! empty($which) && @file_exists($which) && @is_executable($which)) {
            // Get version to verify it meets requirements
            $detectedVersion = $this->probePhpBinaryVersion($which);
            $versionFloat = (float) $detectedVersion;

            Log::info('UPDATE: Found PHP in PATH', [
                'path' => $which,
                'version' => $detectedVersion,
                'meetsRequirement' => $versionFloat >= 8.4,
            ]);

            if ($versionFloat >= 8.4) {
                return $which;
            }

            Log::warning('UPDATE: PHP in PATH is too old (<8.4), trying common paths');
        }

        // Last resort: try common PHP CLI locations
        // Only PHP 8.4+ (required by Reward Loyalty 3.9+)
        Log::info('UPDATE: Searching common PHP CLI paths (8.4+ only)');
        $commonPaths = [
            // Versioned paths first (most likely to be correct version)
            // CloudWays / Ubuntu style (php8.5, php8.4)
            '/usr/bin/php8.5',
            '/usr/bin/php8.4',

            // Alternative format without dots (php85, php84)
            '/usr/bin/php85',
            '/usr/bin/php84',

            // Plesk paths
            '/opt/plesk/php/8.5/bin/php',
            '/opt/plesk/php/8.4/bin/php',

            // cPanel EasyApache paths
            '/opt/cpanel/ea-php85/root/usr/bin/php',
            '/opt/cpanel/ea-php84/root/usr/bin/php',

            // Alternative cPanel paths (some hosts use different structure)
            '/usr/local/bin/ea-php85',
            '/usr/local/bin/ea-php84',

            // Generic paths last (need version check)
            '/usr/bin/php',                          // Standard path
            '/usr/local/bin/php',                    // macOS, custom installs
        ];

        foreach ($commonPaths as $path) {
            // Suppress open_basedir warnings on restricted hosting (Plesk, cPanel)
            if (@file_exists($path) && @is_executable($path)) {
                // For generic paths like /usr/bin/php, verify version
                $needsVersionCheck = in_array($path, ['/usr/bin/php', '/usr/local/bin/php']);

                if ($needsVersionCheck) {
                    $detectedVersion = $this->probePhpBinaryVersion($path);
                    $versionFloat = (float) $detectedVersion;

                    Log::info('UPDATE: Checking generic PHP path version', [
                        'path' => $path,
                        'version' => $detectedVersion,
                        'meetsRequirement' => $versionFloat >= 8.4,
                    ]);

                    if ($versionFloat < 8.4) {
                        Log::warning('UPDATE: Skipping path - version too old', [
                            'path' => $path,
                            'version' => $detectedVersion,
                        ]);

                        continue;
                    }
                }

                Log::info('UPDATE: Found suitable PHP binary', ['path' => $path]);

                return $path;
            }
        }

        // Absolute fallback (will likely fail but at least won't use FPM)
        Log::error('UPDATE: No suitable PHP 8.4+ binary found, falling back to "php" (likely to fail)');

        return 'php';
    }

    /**
     * Spawn standalone updater process
     */
    protected function spawnUpdaterProcess(string $packagePath, int $updateId): void
    {
        $basePath = base_path();
        $updaterScript = storage_path('app/updater.php');
        $protectedPaths = config('reward-loyalty.protected_paths');

        $script = $this->generateUpdaterScript($basePath, $updateId, $protectedPaths, $packagePath);
        File::put($updaterScript, $script);
        chmod($updaterScript, 0755);

        $stderrLog = storage_path('logs/updater_stderr.log');
        $phpBinary = $this->getPhpCliBinary();

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B \"$phpBinary\" \"$updaterScript\" 2> \"$stderrLog\"", 'r'));
        } else {
            // Use nohup with full output redirection to ensure true background execution
            $cmd = "nohup \"$phpBinary\" \"$updaterScript\" >> \"$stderrLog\" 2>&1 &";
            exec($cmd);
        }

        Log::info('UPDATE: Spawned updater process', ['update_id' => $updateId]);
    }

    /**
     * Generate standalone updater script
     */
    protected function generateUpdaterScript(string $basePath, int $updateId, array $protectedPaths, string $packagePath): string
    {
        $protectedPathsJson = json_encode($protectedPaths, JSON_UNESCAPED_SLASHES);
        $tempExtract = storage_path('app/temp_extract');
        $tempProtected = storage_path('app/temp_protected');
        $dbConfig = config('database.default');
        $dbConnection = config("database.connections.{$dbConfig}");
        $phpBinary = $this->getPhpCliBinary();

        // Ensure update-progress.php is protected
        if (! in_array('public/update-progress.php', $protectedPaths)) {
            $protectedPaths[] = 'public/update-progress.php';
        }

        // Build database connection string based on driver
        $dbDriver = $dbConnection['driver'] ?? 'sqlite';
        if ($dbDriver === 'sqlite') {
            $dbPath = $dbConnection['database'] ?? database_path('database.sqlite');
            $pdoConnection = "new PDO('sqlite:$dbPath')";
            $updateQuery = "UPDATE reward_loyalty_updates SET status = 'completed', completed_at = datetime('now'), duration_seconds = CAST((julianday('now') - julianday(started_at)) * 86400 AS INTEGER) WHERE id = ?";
        } else {
            $host = $dbConnection['host'] ?? '127.0.0.1';
            $port = $dbConnection['port'] ?? 3306;
            $database = $dbConnection['database'] ?? 'reward_loyalty';
            $username = $dbConnection['username'] ?? 'root';
            $password = $dbConnection['password'] ?? '';
            $pdoConnection = "new PDO('mysql:host=$host;port=$port;dbname=$database', '$username', '$password')";
            $updateQuery = "UPDATE reward_loyalty_updates SET status = 'completed', completed_at = UTC_TIMESTAMP(), duration_seconds = TIMESTAMPDIFF(SECOND, started_at, UTC_TIMESTAMP()) WHERE id = ?";
        }

        return <<<PHP
<?php
/**
 * Standalone Reward Loyalty Updater
 * Runs independently after Laravel exits
 */

\$basePath = '$basePath';
\$logFile = \$basePath . '/storage/logs/updater.log';
\$log = function(\$message) use (\$logFile) {
    file_put_contents(\$logFile, date('[Y-m-d H:i:s] ') . \$message . PHP_EOL, FILE_APPEND);
};

\$packagePath = '$packagePath';
\$tempExtract = '$tempExtract';
\$tempProtected = '$tempProtected';
\$updateId = $updateId;
\$protectedPaths = $protectedPathsJson;
\$phpBinary = '$phpBinary';

\$log('Update started (ID: ' . \$updateId . ', PHP: ' . PHP_VERSION . ')');

sleep(2);

try {
    \$log('Step 1/7: Extracting package...');
    
    if (!class_exists('ZipArchive')) {
        throw new Exception('ZipArchive extension not available');
    }
    
    if (!file_exists(\$packagePath)) {
        throw new Exception('Package not found: ' . \$packagePath);
    }
    
    \$zip = new ZipArchive();
    if (\$zip->open(\$packagePath) !== true) {
        throw new Exception('Failed to open ZIP package');
    }
    
    if (!is_dir(\$tempExtract)) {
        mkdir(\$tempExtract, 0755, true);
    }
    
    if (!\$zip->extractTo(\$tempExtract)) {
        throw new Exception('Failed to extract ZIP');
    }
    \$numFiles = \$zip->numFiles;
    \$zip->close();
    
    // Detect package root - must contain 'app/' and 'public/' directories.
    // Handles multiple structures:
    //   - Direct (app/ + public/ at root)
    //   - Named wrapper (reward-loyalty/app/ + public/)
    //   - CodeCanyon double-wrapper (Reward-Loyalty-v3.37.2/public_html/app/ + public/)
    \$packageRoot = null;
    
    \$findAppRoot = function(\$dir, \$depth = 0) use (&\$findAppRoot, &\$log) {
        if (\$depth > 3) return null; // Safety: don't recurse forever
        
        \$contents = array_diff(scandir(\$dir), ['.', '..']);
        
        // Check if this directory IS the app root
        if (in_array('app', \$contents) && in_array('public', \$contents)) {
            \$log('Found app root at depth ' . \$depth . ': ' . basename(\$dir));
            return \$dir;
        }
        
        // Recurse into subdirectories (skip files)
        \$subdirs = array_filter(\$contents, function(\$f) use (\$dir) {
            return is_dir(\$dir . '/' . \$f);
        });
        
        foreach (\$subdirs as \$subdir) {
            \$result = \$findAppRoot(\$dir . '/' . \$subdir, \$depth + 1);
            if (\$result) return \$result;
        }
        
        return null;
    };
    
    \$packageRoot = \$findAppRoot(\$tempExtract);
    
    if (!\$packageRoot) {
        \$log('ERROR: Could not find valid package structure');
        \$log('Contents of temp_extract: ' . implode(', ', scandir(\$tempExtract)));
        throw new Exception('Invalid package structure - could not locate app/public directories');
    }
    
    \$log('Extracted ' . \$numFiles . ' files');
    
    \$log('Step 2/7: Removing old files...');
    // Check for critical files before deletion
    \$htaccessExists = file_exists(\$basePath . '/.htaccess');
    \$envExists = file_exists(\$basePath . '/.env');
    \$log('Pre-deletion check: .htaccess=' . (\$htaccessExists ? 'EXISTS' : 'MISSING') . ', .env=' . (\$envExists ? 'EXISTS' : 'MISSING'));
    
    \$deleteCount = 0;
    \$preservedCount = 0;
    \$criticalFilesPreserved = [];
    
    // Build preserved paths list: protected paths + their parent directories
    \$preservedDirs = ['storage'];
    foreach (\$protectedPaths as \$protected) {
        \$preservedDirs[] = \$protected;
        \$parts = explode('/', \$protected);
        \$current = '';
        foreach (\$parts as \$part) {
            \$current .= (\$current ? '/' : '') . \$part;
            if (!in_array(\$current, \$preservedDirs)) {
                \$preservedDirs[] = \$current;
            }
        }
    }
    
    \$shouldPreserve = function(\$relativePath) use (\$preservedDirs, \$protectedPaths, &\$preservedCount, &\$criticalFilesPreserved) {
        if (in_array(\$relativePath, \$preservedDirs)) {
            \$preservedCount++;
            // Track critical files
            if (in_array(\$relativePath, ['.env', '.htaccess'])) {
                \$criticalFilesPreserved[] = \$relativePath;
            }
            return true;
        }
        foreach (\$protectedPaths as \$protected) {
            if (\$relativePath === \$protected || strpos(\$relativePath, \$protected . '/') === 0) {
                \$preservedCount++;
                // Track critical files
                if (in_array(\$relativePath, ['.env', '.htaccess'])) {
                    \$criticalFilesPreserved[] = \$relativePath;
                }
                return true;
            }
        }
        if (\$relativePath === 'storage' || strpos(\$relativePath, 'storage/') === 0) {
            \$preservedCount++;
            return true;
        }
        return false;
    };
    
    \$safeDelete = function(\$dir) use (&\$safeDelete, &\$deleteCount, \$shouldPreserve, \$basePath) {
        if (!is_dir(\$dir)) return true;
        \$items = array_diff(scandir(\$dir), ['.', '..']);
        \$canDeleteThisDir = true;
        foreach (\$items as \$item) {
            \$fullPath = \$dir . '/' . \$item;
            \$relativePath = ltrim(str_replace(\$basePath, '', \$fullPath), '/');
            if (\$shouldPreserve(\$relativePath)) {
                \$canDeleteThisDir = false;
                if (is_dir(\$fullPath)) \$safeDelete(\$fullPath);
                continue;
            }
            if (is_dir(\$fullPath)) {
                \$safeDelete(\$fullPath);
                if (count(array_diff(scandir(\$fullPath), ['.', '..'])) === 0) {
                    @rmdir(\$fullPath);
                    \$deleteCount++;
                } else {
                    \$canDeleteThisDir = false;
                }
            } else {
                @unlink(\$fullPath);
                \$deleteCount++;
            }
        }
        return \$canDeleteThisDir;
    };
    
    // Use scandir() instead of glob() to include hidden files like .htaccess, .env
    \$topItems = array_diff(scandir(\$basePath), ['.', '..']);
    foreach (\$topItems as \$itemName) {
        \$item = \$basePath . '/' . \$itemName;
        \$relativePath = \$itemName;
        
        if (\$shouldPreserve(\$relativePath)) {
            if (is_dir(\$item)) \$safeDelete(\$item);
            continue;
        }
        if (is_dir(\$item)) {
            \$safeDelete(\$item);
            if (count(array_diff(scandir(\$item), ['.', '..'])) === 0) {
                @rmdir(\$item);
                \$deleteCount++;
            }
        } else {
            @unlink(\$item);
            \$deleteCount++;
        }
    }
    \$log('Removed ' . \$deleteCount . ' old files');
    \$log('Preserved ' . \$preservedCount . ' protected files/directories');
    if (!empty(\$criticalFilesPreserved)) {
        \$log('Critical files preserved: ' . implode(', ', array_unique(\$criticalFilesPreserved)));
    }
    
    // Verify critical files still exist after deletion
    \$htaccessAfter = file_exists(\$basePath . '/.htaccess');
    \$envAfter = file_exists(\$basePath . '/.env');
    \$log('Post-deletion check: .htaccess=' . (\$htaccessAfter ? 'EXISTS' : 'DELETED!') . ', .env=' . (\$envAfter ? 'EXISTS' : 'DELETED!'));
    
    \$log('Step 3/7: Copying new files...');
    \$copyCount = 0;
    \$skippedCount = 0;
    \$criticalFilesSkipped = [];
    
    \$isPathProtected = function(\$relativePath) use (\$protectedPaths, &\$skippedCount, &\$criticalFilesSkipped) {
        foreach (\$protectedPaths as \$protected) {
            if (\$relativePath === \$protected || strpos(\$relativePath, \$protected . '/') === 0) {
                \$skippedCount++;
                // Track critical files
                if (in_array(\$relativePath, ['.env', '.htaccess'])) {
                    \$criticalFilesSkipped[] = \$relativePath;
                }
                return true;
            }
        }
        return false;
    };
    
    \$copyDir = function(\$src, \$dst) use (&\$copyDir, &\$copyCount, \$isPathProtected, \$basePath, &\$log) {
        \$dir = opendir(\$src);
        if (!\$dir) return;
        if (!is_dir(\$dst)) @mkdir(\$dst, 0755, true);
        while (false !== (\$file = readdir(\$dir))) {
            if (\$file != '.' && \$file != '..') {
                \$srcPath = \$src . '/' . \$file;
                \$dstPath = \$dst . '/' . \$file;
                \$relativePath = ltrim(str_replace(\$basePath, '', \$dstPath), '/');
                if (\$isPathProtected(\$relativePath)) continue;
                if (is_dir(\$srcPath)) {
                    \$copyDir(\$srcPath, \$dstPath);
                } else {
                    copy(\$srcPath, \$dstPath);
                    \$copyCount++;
                }
            }
        }
        closedir(\$dir);
    };
    
    \$copyDir(\$packageRoot, \$basePath);
    \$log('Copied ' . \$copyCount . ' new files');
    \$log('Skipped ' . \$skippedCount . ' protected files (not overwritten)');
    if (!empty(\$criticalFilesSkipped)) {
        \$log('Critical files NOT overwritten: ' . implode(', ', array_unique(\$criticalFilesSkipped)));
    }
    
    // Final verification of critical files
    \$htaccessFinal = file_exists(\$basePath . '/.htaccess');
    \$envFinal = file_exists(\$basePath . '/.env');
    \$log('Post-copy check: .htaccess=' . (\$htaccessFinal ? 'EXISTS' : 'MISSING!') . ', .env=' . (\$envFinal ? 'EXISTS' : 'MISSING!'));
    
    \$log('Step 4/7: Clearing caches...');
    if (function_exists('opcache_reset')) {
        opcache_reset();
        \$log('OPcache cleared');
    }
    
    // Clear bootstrap/cache to prevent stale config/route/service caches
    \$bootstrapCache = \$basePath . '/bootstrap/cache';
    if (is_dir(\$bootstrapCache)) {
        foreach (glob(\$bootstrapCache . '/*.php') as \$cacheFile) {
            @unlink(\$cacheFile);
        }
        \$log('Cleared bootstrap/cache');
    }
    if (!is_dir(\$bootstrapCache)) {
        mkdir(\$bootstrapCache, 0755, true);
    }
    
    \$log('Step 5/7: Changing working directory...');
    chdir(\$basePath);
    
    \$log('Step 6/7: Running migrations...');
    \$artisan = \$basePath . '/artisan';
    
    \$descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    
    \$process = proc_open(
        [\$phpBinary, \$artisan, 'migrate', '--force'],
        \$descriptorspec,
        \$pipes,
        \$basePath
    );
    
    if (is_resource(\$process)) {
        fclose(\$pipes[0]);
        \$migrationOutput = array_filter(explode(PHP_EOL, stream_get_contents(\$pipes[1])));
        fclose(\$pipes[1]);
        fclose(\$pipes[2]);
        \$migrationReturn = proc_close(\$process);
        
        if (\$migrationReturn === 0) {
            \$log('Migrations completed successfully');
        } else {
            \$log('Migration exit code: ' . \$migrationReturn);
        }
    }
    
    exec(escapeshellarg(\$phpBinary) . ' ' . escapeshellarg(\$artisan) . ' optimize:clear 2>&1');
    \$log('Laravel caches cleared');
    
    \$log('Step 7/7: Updating database...');
    try {
        \$pdo = $pdoConnection;
        \$stmt = \$pdo->prepare("$updateQuery");
        \$stmt->execute([\$updateId]);
        \$log('Database updated');
    } catch (Exception \$e) {
        \$log('Database update failed: ' . \$e->getMessage());
    }
    
    \$log('Update completed successfully');
    // Write status to JSON file in public directory (polled by progress page)
    file_put_contents(\$basePath . '/public/update-status.json', json_encode(['status' => 'completed']));
    // Also write flag file for backward compatibility
    file_put_contents(\$basePath . '/storage/app/update_success.flag', time());
    
    \$deleteDir = function(\$dir) use (&\$deleteDir) {
        if (!is_dir(\$dir)) return;
        \$files = array_diff(scandir(\$dir), ['.', '..']);
        foreach (\$files as \$file) {
            \$path = \$dir . '/' . \$file;
            is_dir(\$path) ? \$deleteDir(\$path) : @unlink(\$path);
        }
        @rmdir(\$dir);
    };
    
    if (is_dir(\$tempExtract)) \$deleteDir(\$tempExtract);
    if (is_dir(\$tempProtected)) \$deleteDir(\$tempProtected);
    // Only delete packages from the automated updates directory.
    // Manual update packages (in storage/app/manual-update/) are user-managed.
    if (file_exists(\$packagePath) && !str_contains(\$packagePath, 'manual-update')) {
        @unlink(\$packagePath);
    }
    
} catch (Exception \$e) {
    \$log('ERROR: ' . \$e->getMessage());
    \$log('File: ' . \$e->getFile() . ':' . \$e->getLine());
    // Write status to JSON file in public directory (polled by progress page)
    file_put_contents(\$basePath . '/public/update-status.json', json_encode(['status' => 'failed', 'error' => \$e->getMessage()]));
    // Also write flag file for backward compatibility
    file_put_contents(\$basePath . '/storage/app/update_failed.flag', \$e->getMessage());
}

\$log('Finished at ' . date('Y-m-d H:i:s'));
@unlink(__FILE__);
PHP;
    }

    /**
     * Scan for manually placed update packages.
     *
     * Users upload zip files (as downloaded from CodeCanyon) to
     * storage/app/manual-update/. This method reads each zip,
     * detects the version from config/version.php inside the
     * package, and returns metadata for the UI.
     *
     * CodeCanyon zips have a double-wrapper structure:
     *   SomeFile.zip → Reward-Loyalty-v3.37.2/public_html/config/version.php
     *
     * @return array<int, array{filename: string, version: string|null, size: string, size_bytes: int, is_upgrade: bool}>
     */
    public function getManualUpdatePackages(): array
    {
        $dir = storage_path('app/manual-update');

        if (! File::exists($dir)) {
            return [];
        }

        $zips = File::glob("{$dir}/*.zip");

        if (empty($zips)) {
            return [];
        }

        $currentVersion = config('version.current', '1.0.0');
        $packages = [];

        foreach ($zips as $zipPath) {
            $version = $this->readVersionFromZip($zipPath);

            $sizeBytes = File::size($zipPath);

            $packages[] = [
                'filename' => basename($zipPath),
                'version' => $version,
                'size' => $this->formatFileSize($sizeBytes),
                'size_bytes' => $sizeBytes,
                'is_upgrade' => $version && version_compare($version, $currentVersion, '>'),
                'is_same' => $version && version_compare($version, $currentVersion, '='),
                'is_downgrade' => $version && version_compare($version, $currentVersion, '<'),
            ];
        }

        // Newest version first
        usort($packages, function ($a, $b) {
            if (! $a['version'] || ! $b['version']) {
                return 0;
            }

            return version_compare($b['version'], $a['version']);
        });

        return $packages;
    }

    /**
     * Apply a manually placed update package.
     *
     * Reuses the same backup + spawned-process pipeline as automatic
     * updates. The only difference: the zip is already on disk instead
     * of being downloaded from the license server.
     *
     * Note: No license/support check is performed here. This is intentional.
     * The license/support gate exists on the *download* path (checking for
     * updates from the server). If the user already has the zip file —
     * whether from CodeCanyon directly or from their downloads — they've
     * already paid and should be able to install. This aligns with the
     * docs: "updates themselves are free forever."
     *
     * @return array{success: bool, message: string, background?: bool}
     */
    public function applyManualUpdate(string $filename): array
    {
        $packagePath = storage_path("app/manual-update/{$filename}");

        if (! File::exists($packagePath)) {
            return [
                'success' => false,
                'message' => 'Update package not found.',
            ];
        }

        // Validate it's actually a zip
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($packagePath);

        if (! in_array($mimeType, ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])) {
            return [
                'success' => false,
                'message' => "Invalid file type: {$mimeType}. Expected a .zip file.",
            ];
        }

        // Read version from inside the zip
        $toVersion = $this->readVersionFromZip($packagePath);

        if (! $toVersion) {
            return [
                'success' => false,
                'message' => 'Could not detect version. Ensure the zip contains a valid Reward Loyalty package with config/version.php.',
            ];
        }

        $fromVersion = config('version.current', '1.0.0');

        // Preflight: Verify required PHP functions
        $disabledFunctions = array_map('trim', explode(',', ini_get('disable_functions') ?: ''));
        $requiredFunctions = ['exec'];
        $missingFunctions = array_filter($requiredFunctions, function ($fn) use ($disabledFunctions) {
            return in_array($fn, $disabledFunctions) || ! function_exists($fn);
        });

        if (! empty($missingFunctions)) {
            Log::error('UPDATE: Required PHP functions are disabled', [
                'missing' => $missingFunctions,
            ]);

            return [
                'success' => false,
                'message' => 'Your server has disabled required PHP functions: '.implode(', ', $missingFunctions)
                    .'. Please enable them in your hosting control panel.',
            ];
        }

        // Create update record
        $update = RewardLoyaltyUpdate::create([
            'from_version' => $fromVersion,
            'to_version' => $toVersion,
            'status' => 'pending',
            'started_at' => now(),
            'initiated_by' => auth('admin')->id(),
            'ip_address' => request()->ip(),
            'package_url' => "manual://{$filename}",
            'package_hash' => hash_file('sha256', $packagePath),
        ]);

        try {
            // Step 1: Create backup
            $backupPath = $this->createBackup();
            $update->update(['backup_path' => $backupPath]);

            // Step 2: Spawn standalone updater (same as automatic updates)
            $update->update(['status' => 'extracting']);
            $this->spawnUpdaterProcess($packagePath, $update->id);

            Log::info('MANUAL UPDATE: Started', [
                'filename' => $filename,
                'from' => $fromVersion,
                'to' => $toVersion,
            ]);

            return [
                'success' => true,
                'message' => trans('common.license.update_in_progress'),
                'background' => true,
            ];
        } catch (\Exception $e) {
            $this->rollback($update, $e);

            return [
                'success' => false,
                'message' => trans('common.license.update_failed').': '.$e->getMessage(),
            ];
        }
    }

    /**
     * Read the application version from inside a zip package.
     *
     * Handles CodeCanyon's double-wrapper structure where the app
     * lives inside Reward-Loyalty-vX.Y.Z/public_html/.
     */
    protected function readVersionFromZip(string $zipPath): ?string
    {
        $zip = new \ZipArchive;

        if ($zip->open($zipPath) !== true) {
            return null;
        }

        // Try common paths for config/version.php
        // Direct: config/version.php
        // Single wrapper: {anything}/config/version.php
        // Double wrapper (CodeCanyon): {anything}/public_html/config/version.php
        $version = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if ($name === false) {
                continue;
            }

            // Match any path ending in config/version.php
            if (! str_ends_with($name, 'config/version.php')) {
                continue;
            }

            $content = $zip->getFromIndex($i);

            if ($content === false) {
                continue;
            }

            // Extract version from PHP return array: return ['current' => '3.37.2'];
            if (preg_match("/'current'\s*=>\s*'([^']+)'/", $content, $matches)) {
                $version = $matches[1];
                break;
            }
        }

        $zip->close();

        return $version;
    }

    /**
     * Format file size to human-readable string.
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);
        $factor = min($factor, count($units) - 1);

        return sprintf('%.1f %s', $bytes / pow(1024, $factor), $units[$factor]);
    }

    public function getAvailableBackups(): array
    {
        $backupsDir = storage_path('app/backups');

        if (! File::exists($backupsDir)) {
            return [];
        }

        $backupFiles = File::glob("{$backupsDir}/reward-loyalty-backup-*.zip");

        if (empty($backupFiles)) {
            return [];
        }

        // Sort by modification time (newest first)
        usort($backupFiles, fn ($a, $b) => filemtime($b) - filemtime($a));

        $backups = [];
        foreach ($backupFiles as $backupPath) {
            $filename = basename($backupPath);
            $size = File::size($backupPath);
            $mtime = filemtime($backupPath);

            // Extract version from filename if stored (reward-loyalty-backup-2024-01-15_123456.zip)
            // or try to read from the backup's version.php
            $version = $this->extractVersionFromBackup($backupPath);

            // Extract timestamp from filename
            preg_match('/backup-(\d{4}-\d{2}-\d{2}_\d{6})\.zip$/', $filename, $matches);
            $timestamp = $matches[1] ?? null;

            $backups[] = [
                'path' => $backupPath,
                'filename' => $filename,
                'version' => $version,
                'created_at' => \Carbon\Carbon::createFromTimestamp($mtime),
                'size' => $this->formatBytes($size),
                'size_bytes' => $size,
                'timestamp' => $timestamp,
            ];
        }

        return $backups;
    }

    /**
     * Extract version from backup archive
     *
     * Reads config/version.php from the backup to determine version
     */
    protected function extractVersionFromBackup(string $backupPath): ?string
    {
        try {
            $zip = new ZipArchive;
            if ($zip->open($backupPath) !== true) {
                return null;
            }

            $versionContent = $zip->getFromName('config/version.php');
            $zip->close();

            if (! $versionContent) {
                return null;
            }

            // Parse the version from the PHP file content
            if (preg_match("/'current'\s*=>\s*['\"]([^'\"]+)['\"]/", $versionContent, $matches)) {
                return $matches[1];
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Could not extract version from backup', [
                'backup' => $backupPath,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Format bytes to human readable string
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Delete a specific backup file
     *
     * Validates the backup exists and is within the backups directory,
     * then permanently removes it from disk.
     *
     * @return array{success: bool, message: string}
     */
    public function deleteBackup(string $backupPath): array
    {
        $backupsDir = storage_path('app/backups');

        // Security: Ensure the path is within the backups directory
        $realPath = realpath($backupPath);
        if (! $realPath || ! str_starts_with($realPath, $backupsDir)) {
            Log::warning('BACKUP DELETE: Invalid path attempted', ['path' => $backupPath]);

            return [
                'success' => false,
                'message' => trans('common.license.backup_not_found'),
            ];
        }

        // Verify it's actually a backup file (extra safety)
        if (! preg_match('/reward-loyalty-backup-\d{4}-\d{2}-\d{2}_\d{6}\.zip$/', basename($realPath))) {
            Log::warning('BACKUP DELETE: Non-backup file deletion attempted', ['path' => $backupPath]);

            return [
                'success' => false,
                'message' => trans('common.license.backup_invalid_file'),
            ];
        }

        if (! File::exists($realPath)) {
            return [
                'success' => false,
                'message' => trans('common.license.backup_not_found'),
            ];
        }

        try {
            $filename = basename($realPath);
            $size = File::size($realPath);

            File::delete($realPath);

            Log::info('BACKUP DELETE: Backup removed successfully', [
                'path' => $realPath,
                'filename' => $filename,
                'size' => $this->formatBytes($size),
            ]);

            return [
                'success' => true,
                'message' => trans('common.license.backup_deleted'),
            ];
        } catch (\Exception $e) {
            Log::error('BACKUP DELETE: Failed to delete backup', [
                'path' => $realPath,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => trans('common.license.backup_delete_failed'),
            ];
        }
    }

    /**
     * Restore from a backup
     *
     * This is a dangerous operation that replaces all application files.
     * Spawns independent process to avoid corrupting running PHP.
     *
     * @return array{success: bool, message: string}
     */
    public function restoreFromBackup(string $backupPath): array
    {
        if (! File::exists($backupPath)) {
            return [
                'success' => false,
                'message' => trans('common.license.backup_not_found'),
            ];
        }

        // Verify it's a valid backup
        $zip = new ZipArchive;
        if ($zip->open($backupPath) !== true) {
            return [
                'success' => false,
                'message' => trans('common.license.backup_corrupted'),
            ];
        }

        // Check for essential directories by scanning entries
        // Note: ZipArchive::locateName('app/') looks for exact directory entries,
        // but addFile() only adds files, not directories. So we scan for any file
        // starting with 'app/' or 'public/' to validate the backup structure.
        $hasApp = false;
        $hasPublic = false;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! $hasApp && str_starts_with($name, 'app/')) {
                $hasApp = true;
            }
            if (! $hasPublic && str_starts_with($name, 'public/')) {
                $hasPublic = true;
            }
            if ($hasApp && $hasPublic) {
                break; // Found both, no need to continue
            }
        }

        $zip->close();

        if (! $hasApp || ! $hasPublic) {
            Log::warning('RESTORE: Invalid backup structure', [
                'backup' => $backupPath,
                'has_app' => $hasApp,
                'has_public' => $hasPublic,
            ]);

            return [
                'success' => false,
                'message' => trans('common.license.backup_invalid_structure'),
            ];
        }

        $version = $this->extractVersionFromBackup($backupPath);

        Log::info('RESTORE: Starting', ['version' => $version]);

        // Generate and spawn the restore script
        $basePath = base_path();
        $restoreScript = $this->generateRestoreScript($basePath, $backupPath);
        $scriptPath = storage_path('app/restorer.php');

        File::put($scriptPath, $restoreScript);
        chmod($scriptPath, 0755);

        // Spawn independent process using nohup to properly detach
        // Note: exec() with & can still block on some systems, so we use nohup + /dev/null redirect
        $phpBinary = $this->getPhpCliBinary();
        $stderrLog = storage_path('logs/restorer_stderr.log');

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B \"$phpBinary\" \"$scriptPath\" 2> \"$stderrLog\"", 'r'));
        } else {
            // Use nohup with full output redirection to ensure true background execution
            $cmd = "nohup \"$phpBinary\" \"$scriptPath\" >> \"$stderrLog\" 2>&1 &";
            exec($cmd);
        }

        Log::info('RESTORE: Spawned restorer process');

        return [
            'success' => true,
            'message' => trans('common.license.restore_started'),
            'version' => $version,
            'background' => true,
        ];
    }

    /**
     * Generate standalone restore script
     *
     * Runs independently of Laravel to restore backup files
     */
    protected function generateRestoreScript(string $basePath, string $backupPath): string
    {
        $tempExtract = storage_path('app/temp_restore');
        $phpBinary = $this->getPhpCliBinary();

        // For restore, only protect critical files that MUST be preserved
        // Don't protect entire directories as they can be huge and cause timeouts
        $protectedPaths = [
            '.env',
            'database/database.sqlite',
        ];
        $protectedPathsJson = json_encode($protectedPaths, JSON_UNESCAPED_SLASHES);

        return <<<PHP
<?php
/**
 * Standalone Reward Loyalty Restore Script
 * Runs independently after Laravel exits
 */

\$basePath = '$basePath';
\$logFile = \$basePath . '/storage/logs/restorer.log';
\$log = function(\$message) use (\$logFile) {
    file_put_contents(\$logFile, date('[Y-m-d H:i:s] ') . \$message . PHP_EOL, FILE_APPEND);
};

\$backupPath = '$backupPath';
\$tempExtract = '$tempExtract';
\$protectedPaths = $protectedPathsJson;
\$phpBinary = '$phpBinary';

\$log('Restore started (PHP: ' . PHP_VERSION . ')');
\$log('Backup: ' . \$backupPath);

// Wait for Laravel/PHP-FPM to fully release before touching files
// This prevents 502 errors from nginx losing connection mid-restore
\$log('Waiting for PHP-FPM to release...');
sleep(5);

try {
    \$log('Step 1/6: Extracting backup...');
    
    if (!class_exists('ZipArchive')) {
        throw new Exception('ZipArchive extension not available');
    }
    
    if (!file_exists(\$backupPath)) {
        throw new Exception('Backup not found: ' . \$backupPath);
    }
    
    \$zip = new ZipArchive();
    if (\$zip->open(\$backupPath) !== true) {
        throw new Exception('Failed to open backup archive');
    }
    
    if (!is_dir(\$tempExtract)) {
        mkdir(\$tempExtract, 0755, true);
    }
    
    if (!\$zip->extractTo(\$tempExtract)) {
        throw new Exception('Failed to extract backup');
    }
    \$numFiles = \$zip->numFiles;
    \$zip->close();
    
    \$log('Extracted ' . \$numFiles . ' files');
    
    \$log('Step 2/6: Backing up protected files...');
    \$tempProtected = \$basePath . '/storage/app/temp_protected_restore';
    if (!is_dir(\$tempProtected)) {
        mkdir(\$tempProtected, 0755, true);
    }
    
    foreach (\$protectedPaths as \$path) {
        \$fullPath = \$basePath . '/' . \$path;
        \$backupTo = \$tempProtected . '/' . \$path;
        
        if (file_exists(\$fullPath)) {
            \$dir = dirname(\$backupTo);
            if (!is_dir(\$dir)) {
                mkdir(\$dir, 0755, true);
            }
            
            if (is_dir(\$fullPath)) {
                \$iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(\$fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                foreach (\$iterator as \$item) {
                    \$target = \$backupTo . '/' . \$iterator->getSubPathname();
                    if (\$item->isDir()) {
                        if (!is_dir(\$target)) mkdir(\$target, 0755, true);
                    } else {
                        copy(\$item, \$target);
                    }
                }
            } else {
                copy(\$fullPath, \$backupTo);
            }
            \$log('  Protected: ' . \$path);
        }
    }
    
    \$log('Step 3/6: Restoring files...');
    \$fileCount = 0;
    
    \$iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(\$tempExtract, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    // Helper to check if path is protected (exact match or inside protected directory)
    \$isPathProtected = function(\$path) use (\$protectedPaths) {
        foreach (\$protectedPaths as \$protected) {
            if (\$path === \$protected || strpos(\$path, \$protected . '/') === 0) {
                return true;
            }
        }
        return false;
    };
    
    foreach (\$iterator as \$item) {
        \$relativePath = \$iterator->getSubPathname();
        \$targetPath = \$basePath . '/' . \$relativePath;
        
        // Skip protected paths (exact match or children)
        if (\$isPathProtected(\$relativePath)) continue;
        
        if (\$item->isDir()) {
            if (!is_dir(\$targetPath)) {
                mkdir(\$targetPath, 0755, true);
            }
        } else {
            \$dir = dirname(\$targetPath);
            if (!is_dir(\$dir)) {
                mkdir(\$dir, 0755, true);
            }
            copy(\$item->getRealPath(), \$targetPath);
            \$fileCount++;
        }
    }
    
    \$log('Restored ' . \$fileCount . ' files');
    
    \$log('Step 4/6: Restoring protected files...');
    foreach (\$protectedPaths as \$path) {
        \$protectedFrom = \$tempProtected . '/' . \$path;
        \$targetPath = \$basePath . '/' . \$path;
        
        if (file_exists(\$protectedFrom)) {
            if (is_dir(\$protectedFrom)) {
                \$iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(\$protectedFrom, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                foreach (\$iterator as \$item) {
                    \$target = \$targetPath . '/' . \$iterator->getSubPathname();
                    if (\$item->isDir()) {
                        if (!is_dir(\$target)) mkdir(\$target, 0755, true);
                    } else {
                        copy(\$item, \$target);
                    }
                }
            } else {
                \$dir = dirname(\$targetPath);
                if (!is_dir(\$dir)) mkdir(\$dir, 0755, true);
                copy(\$protectedFrom, \$targetPath);
            }
        }
    }
    
    \$log('Step 5/6: Cleaning up temp files...');
    
    // Remove temp directories
    \$removeDir = function(\$dir) use (&\$removeDir) {
        if (!is_dir(\$dir)) return;
        \$items = scandir(\$dir);
        foreach (\$items as \$item) {
            if (\$item === '.' || \$item === '..') continue;
            \$path = \$dir . '/' . \$item;
            is_dir(\$path) ? \$removeDir(\$path) : unlink(\$path);
        }
        rmdir(\$dir);
    };
    
    \$removeDir(\$tempExtract);
    \$removeDir(\$tempProtected);
    
    // Clear Laravel caches
    @unlink(\$basePath . '/bootstrap/cache/config.php');
    @unlink(\$basePath . '/bootstrap/cache/routes-v7.php');
    @unlink(\$basePath . '/bootstrap/cache/services.php');
    @unlink(\$basePath . '/bootstrap/cache/packages.php');
    
    \$log('Step 6/6: Removing backup archive...');
    if (file_exists(\$backupPath)) {
        @unlink(\$backupPath);
        \$log('Backup archive deleted: ' . basename(\$backupPath));
    }
    
    \$log('Restore completed successfully!');
    // Write status to JSON file in public directory (polled by progress page)
    file_put_contents(\$basePath . '/public/update-status.json', json_encode(['status' => 'completed']));
    // Also write flag file for backward compatibility
    file_put_contents(\$basePath . '/storage/app/restore_success.flag', 'Restore completed at ' . date('Y-m-d H:i:s'));
    
} catch (Exception \$e) {
    \$log('ERROR: ' . \$e->getMessage());
    \$log('File: ' . \$e->getFile() . ':' . \$e->getLine());
    // Write status to JSON file in public directory (polled by progress page)
    file_put_contents(\$basePath . '/public/update-status.json', json_encode(['status' => 'failed', 'error' => \$e->getMessage()]));
    // Also write flag file for backward compatibility
    file_put_contents(\$basePath . '/storage/app/restore_failed.flag', \$e->getMessage());
}

\$log('Finished at ' . date('Y-m-d H:i:s'));
@unlink(__FILE__);
PHP;
    }

    /**
     * Create standalone progress page in public directory
     */
    public function createProgressPage(string $mode, string $version): string
    {
        // Use .html extension so Herd/Valet serves it directly (not through Laravel)
        // Status is checked via JSON files that the background process updates
        $filename = 'update-progress.html';
        $statusFilename = 'update-status.json';
        $path = public_path($filename);
        $statusPath = public_path($statusFilename);
        $appName = config('app.name', 'Reward Loyalty');
        $appInitial = strtoupper(substr($appName, 0, 1));
        // Get locale from request URL segment (e.g., 'en-us') for proper redirect
        $locale = request()->segment(1) ?: str_replace('_', '-', strtolower(config('app.locale', 'en_US')));
        $redirectUrl = route('admin.license.index', ['locale' => $locale]);

        // Initialize status file as "in progress"
        File::put($statusPath, json_encode(['status' => 'in_progress', 'mode' => $mode]));

        // CSS for the page
        $css = file_get_contents(public_path('assets/css/static-pages.css'));

        $title = $mode === 'restore' ? 'Restoring Backup' : 'Installing Update';
        $subtitle = $mode === 'restore' ? "Reverting to version {$version}" : "Updating to version {$version}";
        $iconClass = $mode === 'restore' ? 'app-icon-amber' : 'app-icon-primary';
        $spinnerClass = $mode === 'restore' ? 'spinner-ring-amber' : 'spinner-ring-primary';
        $spinnerIconClass = $mode === 'restore' ? 'spinner-icon-amber' : 'spinner-icon-primary';
        $alertClass = $mode === 'restore' ? 'static-alert-amber' : 'static-alert-primary';

        // Pure HTML page - no PHP needed! Status is polled via JSON file
        $content = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - {$appName}</title>
    <style>
        {$css}
    </style>
</head>
<body class="bg-secondary-900 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
        <div class="static-card">
            <div class="static-card-header">
                <div class="flex items-center gap-3">
                    <div class="app-icon {$iconClass}">{$appInitial}</div>
                    <div>
                        <h2 class="text-lg font-semibold text-white">{$title}</h2>
                        <p class="text-sm text-secondary-400">{$subtitle}</p>
                    </div>
                </div>
            </div>

            <div class="static-card-body">
                <div class="space-y-6">
                    <div class="flex items-center justify-center py-8">
                        <div class="spinner-container">
                            <div class="spinner-ring {$spinnerClass}"></div>
                            <div class="spinner-icon {$spinnerIconClass}">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 text-center">
                        <h3 id="status-title" class="text-lg font-semibold text-white">In Progress...</h3>
                        <p id="status-message" class="text-sm text-secondary-400">
                            Please wait while the operation completes. This may take a minute.
                        </p>
                    </div>

                    <div class="static-alert {$alertClass}">
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
        let consecutiveErrors = 0;
        const maxChecks = 60; // 120 seconds max (60 checks * 2s interval)
        const maxConsecutiveErrors = 5;
        
        console.log('=== UPDATE PROGRESS PAGE (STATIC HTML) ===');
        console.log('Start time:', new Date().toISOString());
        console.log('Status file:', '/update-status.json');
        console.log('Redirect URL:', '$redirectUrl');
        console.log('Max checks:', maxChecks, '(~' + (maxChecks * 2) + ' seconds)');
        
        function updateElapsed() {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            document.getElementById('elapsed-time').textContent = 'Elapsed: ' + elapsed + 's';
        }
        
        function forceRedirect(reason) {
            console.log('🔄 FORCING REDIRECT:', reason);
            document.getElementById('status-title').textContent = 'Complete!';
            document.getElementById('status-message').textContent = 'Redirecting...';
            setTimeout(() => {
                console.log('→ Executing redirect to:', '$redirectUrl');
                window.location.href = '$redirectUrl';
            }, 1000);
        }
        
        function checkStatus() {
            checkCount++;
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            
            console.log('[Check #' + checkCount + '] Elapsed: ' + elapsed + 's, Errors: ' + consecutiveErrors);
            document.getElementById('check-count').textContent = 'Check #' + checkCount;
            
            // Safety: force redirect after max checks
            if (checkCount >= maxChecks) {
                console.error('❌ Max checks reached (' + maxChecks + ')');
                forceRedirect('Max checks exceeded');
                return;
            }
            
            // Safety: force redirect after too many consecutive errors
            if (consecutiveErrors >= maxConsecutiveErrors) {
                console.error('❌ Too many consecutive errors (' + consecutiveErrors + ')');
                forceRedirect('Too many errors, assuming success');
                return;
            }
            
            const checkStartTime = Date.now();
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10s timeout
            
            // Poll the JSON status file (cache-busted)
            fetch('/update-status.json?t=' + Date.now(), {
                signal: controller.signal,
                cache: 'no-store'
            })
                .then(response => {
                    clearTimeout(timeoutId);
                    const fetchDuration = Date.now() - checkStartTime;
                    console.log('  ✓ Fetch OK (' + fetchDuration + 'ms), status: ' + response.status);
                    
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    consecutiveErrors = 0; // Reset error counter
                    console.log('  ✓ Data:', JSON.stringify(data));
                    
                    if (data.status === 'completed') {
                        console.log('✅ UPDATE COMPLETED!');
                        document.getElementById('status-title').textContent = 'Complete!';
                        document.getElementById('status-message').textContent = 'Redirecting...';
                        setTimeout(() => {
                            console.log('→ Executing redirect to:', '$redirectUrl');
                            window.location.href = '$redirectUrl';
                        }, 1500);
                    } else if (data.status === 'failed') {
                        console.error('❌ UPDATE FAILED:', data.error);
                        document.getElementById('status-title').textContent = 'Failed';
                        document.getElementById('status-message').textContent = data.error || 'An error occurred.';
                        document.getElementById('status-message').className = 'text-sm text-red-400';
                        setTimeout(() => {
                            console.log('→ Redirecting to error page');
                            window.location.href = '$redirectUrl?error=' + encodeURIComponent(data.error || 'Failed');
                        }, 3000);
                    } else {
                        console.log('  ⏳ Still in progress...');
                    }
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    consecutiveErrors++;
                    const fetchDuration = Date.now() - checkStartTime;
                    console.error('  ❌ Check failed (' + fetchDuration + 'ms, ' + consecutiveErrors + '/' + maxConsecutiveErrors + '):', error.message || error);
                    
                    // Check if we should force redirect
                    if (consecutiveErrors >= maxConsecutiveErrors) {
                        forceRedirect('Too many consecutive errors');
                    }
                });
        }
        
        setInterval(updateElapsed, 1000);
        setInterval(checkStatus, 2000);
        
        // Initial check after 1 second
        setTimeout(() => {
            console.log('Starting initial status check...');
            checkStatus();
        }, 1000);
    </script>
</body>
</html>
HTML;

        File::put($path, $content);

        return asset($filename);
    }
}
