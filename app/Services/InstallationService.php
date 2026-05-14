<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Handles the installation process of the application including server
 * requirement checks, database setup, and configuration file generation.
 */

namespace App\Services;

use App\Models\Admin;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;

class InstallationService
{
    /**
     * Get server requirements.
     *
     * @return array<string, mixed> Array with requirements
     */
    public function getServerRequirements(): array
    {
        $requirements = [
            'PHP >= 8.4.0 ('.PHP_VERSION.')' => version_compare(PHP_VERSION, '8.4.0') >= 0,
            'Bcmath (ext-bcmath)' => extension_loaded('bcmath'),
            'Ctype (ext-ctype)' => extension_loaded('ctype'),
            'cURL (ext-curl)' => extension_loaded('curl'),
            'DOM (ext-dom)' => extension_loaded('dom'),
            'Exif (ext-exif)' => extension_loaded('exif'),
            'Fileinfo (ext-fileinfo)' => extension_loaded('fileinfo'),
            'Filter (ext-filter)' => extension_loaded('filter'),
            'GD (ext-gd)' => extension_loaded('gd'),
            'Hash (ext-hash)' => extension_loaded('hash'),
            'Iconv (ext-iconv)' => extension_loaded('iconv'),
            'Intl (ext-intl)' => extension_loaded('intl'),
            'JSON (ext-json)' => extension_loaded('json'),
            'Libxml (ext-libxml)' => extension_loaded('libxml'),
            'Mbstring (ext-mbstring)' => extension_loaded('mbstring'),
            'OpenSSL (ext-openssl)' => extension_loaded('openssl'),
            'PCRE (ext-pcre)' => extension_loaded('pcre'),
            'PDO (ext-pdo)' => extension_loaded('pdo'),
            'PDO SQLite (ext-pdo_sqlite)' => extension_loaded('pdo_sqlite'),
            'Session (ext-session)' => extension_loaded('session'),
            'Tokenizer (ext-tokenizer)' => extension_loaded('tokenizer'),
            'XML (ext-xml)' => extension_loaded('xml'),
            'Zip (ext-zip)' => extension_loaded('zip'),
            'Zlib (ext-zlib)' => extension_loaded('zlib'),
        ];

        // Environment checks — these verify the deployment context, not just PHP capabilities.
        $environment = [
            'Root directory is writable (.env)' => is_writable(base_path()),
            'Installed at domain root (not in a subdirectory)' => $this->isInstalledAtDomainRoot(),
        ];

        $combined = array_merge($requirements, $environment);
        $allRequirementsMet = ! in_array(false, $combined);

        return [
            'allMet' => $allRequirementsMet,
            'requirements' => $requirements,
            'environment' => $environment,
        ];
    }

    /**
     * Detect whether the application is installed in a subdirectory.
     *
     * The script must be served from a domain root (e.g., loyalty.example.com
     * or example.com), never from a path like example.com/loyalty. This is
     * because Laravel's routing assumes it owns the URL root, and subdirectory
     * installations cause broken asset URLs, incorrect route generation, and
     * unpredictable behavior.
     *
     * Important: SCRIPT_NAME is unreliable here — many servers (Herd, Valet,
     * FrankenPHP) set it to the full request URI (/en-us/install) rather than
     * the PHP file path (/index.php), causing false positives on locale routes.
     *
     * Filesystem-based detection (SCRIPT_FILENAME vs DOCUMENT_ROOT) is also
     * unreliable — setups with a public/ rewrite via .htaccess (common on
     * Hostinger, cPanel) cause false positives because index.php physically
     * lives inside a subdirectory of the document root by design.
     *
     * Instead, we use two URL-based signals:
     * 1. Laravel's Request::getBaseUrl() — Symfony's kernel-level detection
     *    of any URL prefix before the application routes.
     * 2. Request path segment count — the install route is always /{locale}/install
     *    (e.g., /es-es/install), so more than two segments before /install
     *    indicates a subdirectory prefix.
     */
    private function isInstalledAtDomainRoot(): bool
    {
        // Method 1: Symfony/Laravel's canonical base URL detection.
        // Returns '' when at domain root, '/loyalty' when in a subdirectory.
        // This correctly ignores locale segments (/es-es/) because those are
        // application routes, not server path prefixes.
        $baseUrl = Request::getBaseUrl();

        if ($baseUrl !== '' && $baseUrl !== '/') {
            return false;
        }

        // Method 2: Path segment count as a secondary signal.
        // The install route is always /{locale}/install, e.g. /es-es/install.
        // If there are more than two segments and the last one is 'install',
        // the extra prefix is a subdirectory, not a locale.
        // Example: /loyalty/es-es/install → ['loyalty', 'es-es', 'install'] → false.
        $segments = explode('/', trim(Request::path(), '/'));

        if (count($segments) > 2 && end($segments) === 'install') {
            return false;
        }

        return true;
    }

    /**
     * Install the script.
     *
     * @param  array<string, mixed>  $request  An array with request data
     */
    public function installScript(array $request): void
    {
        set_time_limit(0);

        // Delete log
        File::delete(storage_path('logs/laravel.log'));

        // Check if sqlite file exists, if not, create
        if ($request['DB_CONNECTION'] === 'sqlite') {
            $sqlite = database_path('database.sqlite');

            if (! File::exists($sqlite)) {
                File::put($sqlite, '');
            }
        }

        // Get the blueprint
        $env = File::get(base_path('.env.blueprint'));

        // Filter form values not used in env file
        $all = [];
        $filter = ['ADMIN_NAME', 'ADMIN_MAIL', 'ADMIN_TIMEZONE', 'ADMIN_PASS', 'ADMIN_PASS_CONFIRM'];
        foreach ($request as $key => $value) {
            if (! in_array($key, $filter)) {
                $all[$key] = $value;
            }
        }

        // Add env variables
        $all['APP_URL'] = Request::getSchemeAndHttpHost();
        $all['APP_KEY'] = 'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher')));
        $all['APP_INSTALLATION_DATE'] = date('Y-m-d H:i:s');
        $all['SNOWFLAKE_EPOCH'] = date('Y-m-d H:i:s');
        $all['APP_DEBUG'] = 'false';
        $all['APP_ENV'] = 'production';

        // Handle demo mode - set both env AND config (config is cached at boot)
        if (isset($all['APP_DEMO']) && $all['APP_DEMO'] === 'true') {
            putenv('APP_DEMO=true');
            putenv('APP_IS_UNEDITABLE=false');
            config(['default.app_demo' => true]);
        } else {
            putenv('APP_DEMO=false');
            config(['default.app_demo' => false]);
        }

        // Handle mail configuration based on driver
        $all = $this->processMailConfiguration($all);

        // Replace .env.blueprint values with user-provided values
        $new_env = preg_replace_callback('/^(\w+)=(.*)$/m', function ($matches) use ($all) {
            $key = $matches[1];

            if (array_key_exists($key, $all)) {
                $value = $all[$key];

                return $key.'='.(is_numeric($value) || $value === 'true' || $value === 'false' ? $value : '"'.$value.'"');
            }

            return $matches[0];
        }, $env);

        // Add any new env variables that don't exist in the blueprint
        $new_env = $this->appendNewEnvVariables($new_env, $all);

        // Override database config before migrating and seeding db
        putenv('APP_ENV=development');

        config([
            'app.env' => 'development',
            'database.default' => $all['DB_CONNECTION'],
            'database.connections.mysql.host' => $all['DB_HOST'] ?? '127.0.0.1',
            'database.connections.mysql.port' => $all['DB_PORT'] ?? '3306',
            'database.connections.mysql.database' => $all['DB_DATABASE'] ?? '',
            'database.connections.mysql.username' => $all['DB_USERNAME'] ?? '',
            'database.connections.mysql.password' => $all['DB_PASSWORD'] ?? '',
            // Force sync queue during installation to prevent "jobs table not found" errors
            'queue.default' => 'sync',
        ]);

        // Run database migration and seeding
        Artisan::call('install');

        // Update root admin
        $admin = Admin::take(1)->first();
        $admin->name = $request['ADMIN_NAME'];
        $admin->email = $request['ADMIN_MAIL'];
        $admin->password = bcrypt($request['ADMIN_PASS']);
        $admin->time_zone = $request['ADMIN_TIMEZONE'];
        $admin->save();

        // Update .env file, causes restart with `php artisan serve`
        File::put(base_path('.env'), $new_env);
    }

    /**
     * Process mail configuration and set sensible defaults.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function processMailConfiguration(array $config): array
    {
        $mailer = $config['MAIL_MAILER'] ?? 'smtp';

        // Set defaults based on mailer
        match ($mailer) {
            'smtp' => $config = $this->processSmtpConfig($config),
            'mailgun' => $config = $this->processMailgunConfig($config),
            'ses' => $config = $this->processSesConfig($config),
            'postmark' => $config = $this->processPostmarkConfig($config),
            'resend' => $config = $this->processResendConfig($config),
            'sendmail' => $config = $this->processSendmailConfig($config),
            'mailpit' => $config = $this->processMailpitConfig($config),
            'log' => $config = $this->processLogConfig($config),
            default => null,
        };

        return $config;
    }

    /**
     * Process SMTP configuration.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function processSmtpConfig(array $config): array
    {
        // Ensure required SMTP fields have values
        $config['MAIL_HOST'] = $config['MAIL_HOST'] ?? 'smtp.example.com';
        $config['MAIL_PORT'] = $config['MAIL_PORT'] ?? '587';

        // Handle encryption: "null" string from form means no encryption
        $encryption = $config['MAIL_ENCRYPTION'] ?? 'tls';
        $config['MAIL_ENCRYPTION'] = ($encryption === 'null' || $encryption === '') ? null : $encryption;

        return $config;
    }

    /**
     * Process Mailgun configuration.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function processMailgunConfig(array $config): array
    {
        // Mailgun uses API, set appropriate defaults
        $config['MAIL_HOST'] = null;
        $config['MAIL_PORT'] = null;
        $config['MAIL_USERNAME'] = null;
        $config['MAIL_PASSWORD'] = null;
        $config['MAIL_ENCRYPTION'] = null;

        // Ensure Mailgun endpoint is set
        $config['MAILGUN_ENDPOINT'] = $config['MAILGUN_ENDPOINT'] ?? 'api.mailgun.net';

        return $config;
    }

    /**
     * Process Amazon SES configuration.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function processSesConfig(array $config): array
    {
        // SES uses AWS SDK
        $config['MAIL_HOST'] = null;
        $config['MAIL_PORT'] = null;
        $config['MAIL_USERNAME'] = null;
        $config['MAIL_PASSWORD'] = null;
        $config['MAIL_ENCRYPTION'] = null;

        // Ensure AWS region is set
        $config['AWS_DEFAULT_REGION'] = $config['AWS_DEFAULT_REGION'] ?? 'us-east-1';

        return $config;
    }

    /**
     * Process Postmark configuration.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function processPostmarkConfig(array $config): array
    {
        // Postmark uses API
        $config['MAIL_HOST'] = null;
        $config['MAIL_PORT'] = null;
        $config['MAIL_USERNAME'] = null;
        $config['MAIL_PASSWORD'] = null;
        $config['MAIL_ENCRYPTION'] = null;

        return $config;
    }

    /**
     * Process Resend configuration.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function processResendConfig(array $config): array
    {
        // Resend uses API
        $config['MAIL_HOST'] = null;
        $config['MAIL_PORT'] = null;
        $config['MAIL_USERNAME'] = null;
        $config['MAIL_PASSWORD'] = null;
        $config['MAIL_ENCRYPTION'] = null;

        return $config;
    }

    /**
     * Process Sendmail configuration.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function processSendmailConfig(array $config): array
    {
        // Sendmail doesn't need SMTP settings
        $config['MAIL_HOST'] = null;
        $config['MAIL_PORT'] = null;
        $config['MAIL_USERNAME'] = null;
        $config['MAIL_PASSWORD'] = null;
        $config['MAIL_ENCRYPTION'] = null;

        return $config;
    }

    /**
     * Process Mailpit (local testing) configuration.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function processMailpitConfig(array $config): array
    {
        // Mailpit defaults
        $config['MAIL_HOST'] = '127.0.0.1';
        $config['MAIL_PORT'] = '1025';
        $config['MAIL_USERNAME'] = null;
        $config['MAIL_PASSWORD'] = null;
        $config['MAIL_ENCRYPTION'] = null;

        return $config;
    }

    /**
     * Process Log configuration.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function processLogConfig(array $config): array
    {
        // Log driver doesn't need any SMTP settings
        $config['MAIL_HOST'] = null;
        $config['MAIL_PORT'] = null;
        $config['MAIL_USERNAME'] = null;
        $config['MAIL_PASSWORD'] = null;
        $config['MAIL_ENCRYPTION'] = null;

        return $config;
    }

    /**
     * Append new env variables that don't exist in the blueprint.
     *
     * @param  array<string, mixed>  $config
     */
    private function appendNewEnvVariables(string $envContent, array $config): string
    {
        $additionalVars = [
            'MAILGUN_DOMAIN',
            'MAILGUN_SECRET',
            'MAILGUN_ENDPOINT',
            'POSTMARK_TOKEN',
            'RESEND_KEY',
        ];

        $additions = [];

        foreach ($additionalVars as $var) {
            if (isset($config[$var]) && ! preg_match("/^{$var}=/m", $envContent)) {
                $value = $config[$var];
                $additions[] = "{$var}=\"{$value}\"";
            }
        }

        if (! empty($additions)) {
            $envContent .= "\n\n# Additional Mail Configuration\n".implode("\n", $additions);
        }

        return $envContent;
    }
}
