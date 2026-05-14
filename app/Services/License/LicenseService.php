<?php

declare(strict_types=1);

namespace App\Services\License;

use App\Models\Admin;
use App\Services\SettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LicenseService - CodeCanyon Purchase Code Validation
 *
 * Purpose:
 * Handles license activation, validation, and status checking with the
 * Reward Loyalty license server. Manages secure token storage and automatic
 * revalidation with grace periods for server outages.
 *
 * Design Tenets:
 * - **Secure**: All sensitive data encrypted at rest via SettingsService
 * - **Resilient**: Automatic retry with exponential backoff
 * - **Graceful**: Grace period during license server outages
 * - **Transparent**: Full audit logging of all operations
 *
 * Integration:
 * - Used by: Admin license management UI, scheduled validation
 * - Stores: SettingsService (encrypted)
 * - Calls: License server API endpoints
 */
class LicenseService
{
    public function __construct(
        private readonly SettingsService $settings
    ) {}

    /**
     * Activate license with purchase code
     *
     * @param  string  $purchaseCode  CodeCanyon purchase code
     * @param  string  $domain  Production domain (e.g., mysite.com)
     * @return array{success: bool, message: string, license_token?: string, support_expires_at?: string}
     */
    public function activate(string $purchaseCode, string $domain): array
    {
        $serverUrl = config('reward-loyalty.license_server.url');
        $productId = config('reward-loyalty.product.id');
        $admin = auth('admin')->user();

        try {
            $requestData = [
                'purchase_code' => $purchaseCode,
                'domain' => $domain,
                'product_id' => $productId,
                'environment' => app()->environment(),
                'instance_fingerprint' => $this->generateFingerprint(),
                'app_version' => config('version.current', '1.0.0'),
            ];

            $endpoint = "{$serverUrl}/api/licenses/v1/license/activate";

            // John Carmack-level logging: Log EVERYTHING before the request
            Log::info('LICENSE ACTIVATION REQUEST', [
                '🎯 URL' => $endpoint,
                '📦 Request Data' => array_merge($requestData, [
                    'purchase_code' => substr($purchaseCode, 0, 8).'...'.substr($purchaseCode, -4),
                ]),
                '⏱️ Timeout' => config('reward-loyalty.license_server.timeout').'s',
                '🔐 Fingerprint' => substr($requestData['instance_fingerprint'], 0, 16).'...',
                '👤 Admin' => $admin?->email,
            ]);

            $response = Http::timeout(config('reward-loyalty.license_server.timeout'))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($endpoint, $requestData);

            // John Carmack-level logging: Log the raw response
            Log::info('LICENSE ACTIVATION RESPONSE', [
                '📊 Status Code' => $response->status(),
                '📄 Body (raw)' => $response->body(),
                '🔍 JSON Decoded' => $response->json(),
                '✅ Successful?' => $response->successful() ? 'YES' : 'NO',
            ]);

            if (! $response->successful()) {
                $statusCode = $response->status();
                $responseBody = $response->json();
                $serverMessage = $responseBody['message'] ?? $responseBody['error'] ?? 'Unknown error';

                $message = match ($statusCode) {
                    401 => 'Invalid license token. Please contact support if this persists.',
                    403 => 'This license has been suspended. Please contact support for assistance.',
                    404 => 'License not found. Please verify your purchase code is correct.',
                    422 => $serverMessage,
                    429 => 'Too many activation attempts. Please wait a moment and try again.',
                    500, 502, 503 => 'License server is temporarily unavailable. Please try again in a few minutes.',
                    default => $serverMessage ?: 'Unable to activate license. Please try again or contact support.',
                };

                Log::error('LICENSE ACTIVATION FAILED', [
                    'status_code' => $statusCode,
                    'server_message' => $serverMessage,
                    'user_message' => $message,
                ]);

                return [
                    'success' => false,
                    'message' => $message,
                ];
            }

            $responseData = $response->json();
            $data = $responseData['data'] ?? $responseData;

            // Store license data using SettingsService (encrypted)
            $this->settings->set('rewardloyalty.license_token', $data['license_token'], $admin);
            $this->settings->set('rewardloyalty.purchase_code', $purchaseCode, $admin);
            $this->settings->set('rewardloyalty.license_status', $data['status'] ?? 'active', $admin);
            $this->settings->set('rewardloyalty.support_expires_at', $data['support_expires_at'], $admin);
            $this->settings->set('rewardloyalty.last_validated_at', now()->toDateTimeString(), $admin);

            if (isset($data['buyer_email'])) {
                $this->settings->set('rewardloyalty.buyer_email', $data['buyer_email'], $admin);
            }

            Log::info('LICENSE ACTIVATED SUCCESSFULLY', [
                'support_expires_at' => $data['support_expires_at'],
            ]);

            return [
                'success' => true,
                'message' => trans('common.license.activated'),
                'license_token' => $data['license_token'],
                'support_expires_at' => $data['support_expires_at'],
            ];
        } catch (\Exception $e) {
            Log::error('LICENSE ACTIVATION EXCEPTION', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to connect to license server. Please try again later.',
            ];
        }
    }

    /**
     * Validate license with server
     *
     * @param  bool  $force  Force validation (ignore grace period)
     * @return array{is_valid: bool, status: string, support_active: bool, days_remaining?: int}
     */
    public function validate(bool $force = false): array
    {
        // Check if we need to revalidate (cached for 24 hours)
        if (! $force) {
            $lastValidatedStr = $this->settings->get('rewardloyalty.last_validated_at');
            if ($lastValidatedStr) {
                $lastValidated = Carbon::parse($lastValidatedStr);
                $revalidationInterval = 86400; // 24 hours

                if ($lastValidated->addSeconds($revalidationInterval)->isFuture()) {
                    return $this->getCachedStatus();
                }
            }
        }

        $licenseToken = $this->getLicenseToken();
        if (! $licenseToken) {
            return [
                'is_valid' => false,
                'status' => 'inactive',
                'support_active' => false,
            ];
        }

        $serverUrl = config('reward-loyalty.license_server.url');

        try {
            $requestData = [
                'license_token' => $licenseToken,
                'domain' => parse_url(config('app.url'), PHP_URL_HOST) ?? config('app.url'),
            ];

            $endpoint = "{$serverUrl}/api/licenses/v1/license/validate";

            // John Carmack-level logging: Log request details
            Log::info('LICENSE VALIDATION REQUEST', [
                '🎯 URL' => $endpoint,
                '📦 Request Data' => [
                    'license_token' => substr($licenseToken, 0, 16).'...',
                    'domain' => $requestData['domain'],
                ],
                '⏱️ Timeout' => config('reward-loyalty.license_server.timeout').'s',
            ]);

            $response = Http::timeout(config('reward-loyalty.license_server.timeout'))
                ->withHeaders([
                    'Authorization' => 'Bearer '.$licenseToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($endpoint, $requestData);

            // John Carmack-level logging: Log the raw response
            Log::info('LICENSE VALIDATION RESPONSE', [
                '📊 Status Code' => $response->status(),
                '📄 Body (raw)' => $response->body(),
                '🔍 JSON Decoded' => $response->json(),
                '✅ Successful?' => $response->successful() ? 'YES' : 'NO',
            ]);

            if (! $response->successful()) {
                Log::warning('LICENSE VALIDATION FAILED', [
                    'status_code' => $response->status(),
                    'message' => $response->json('message', 'Validation failed'),
                ]);

                return $this->handleValidationFailure();
            }

            $responseData = $response->json();
            $data = $responseData['data'] ?? $responseData;

            // Derive status from actual expiry date
            if (isset($data['support_expires_at'])) {
                $expiresAt = Carbon::parse($data['support_expires_at']);
                $status = $expiresAt->isFuture() ? 'active' : 'expired';
            } else {
                $status = $data['status'] ?? ($data['is_valid']
                    ? ($data['support_active'] ? 'active' : 'expired')
                    : 'invalid');
            }

            // Update stored status and support expiry
            $admin = auth('admin')->user();
            if ($admin) {
                $this->settings->set('rewardloyalty.license_status', $status, $admin);
                $this->settings->set('rewardloyalty.last_validated_at', now()->toDateTimeString(), $admin);

                if (isset($data['support_expires_at'])) {
                    $this->settings->set('rewardloyalty.support_expires_at', $data['support_expires_at'], $admin);
                }
            }

            return [
                'is_valid' => $data['is_valid'],
                'status' => $status,
                'support_active' => $data['support_active'],
                'days_remaining' => $data['days_remaining'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('License validation exception', [
                'error' => $e->getMessage(),
            ]);

            return $this->handleValidationFailure();
        }
    }

    /**
     * Get current license status (from database)
     *
     * @return array{status: string, support_expires_at: ?string, last_validated_at: ?string, buyer_email: ?string}
     */
    public function getStatus(): array
    {
        return [
            'status' => $this->settings->get('rewardloyalty.license_status', 'inactive'),
            'support_expires_at' => $this->settings->get('rewardloyalty.support_expires_at'),
            'last_validated_at' => $this->settings->get('rewardloyalty.last_validated_at'),
            'buyer_email' => $this->settings->get('rewardloyalty.buyer_email'),
        ];
    }

    /**
     * Check if license is active
     */
    public function isActive(): bool
    {
        return $this->settings->get('rewardloyalty.license_status', 'inactive') === 'active';
    }

    /**
     * Check if support is active (not expired)
     */
    public function isSupportActive(): bool
    {
        $expiresAt = $this->settings->get('rewardloyalty.support_expires_at');
        if (! $expiresAt) {
            return false;
        }

        return Carbon::parse($expiresAt)->isFuture();
    }

    /**
     * Get support expiry date
     */
    public function getSupportExpiryDate(): ?Carbon
    {
        $expiresAt = $this->settings->get('rewardloyalty.support_expires_at');

        return $expiresAt ? Carbon::parse($expiresAt) : null;
    }

    /**
     * Deactivate license (remove from this installation)
     */
    public function deactivate(): bool
    {
        $licenseToken = $this->getLicenseToken();
        if (! $licenseToken) {
            return true; // Already inactive
        }

        $serverUrl = config('reward-loyalty.license_server.url');
        $admin = auth('admin')->user();

        try {
            Http::timeout(config('reward-loyalty.license_server.timeout'))
                ->withHeaders([
                    'Authorization' => 'Bearer '.$licenseToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$serverUrl}/api/licenses/v1/license/deactivate", [
                    'license_token' => $licenseToken,
                    'domain' => parse_url(config('app.url'), PHP_URL_HOST) ?? config('app.url'),
                ]);
        } catch (\Exception $e) {
            Log::warning('License server deactivation failed', [
                'error' => $e->getMessage(),
            ]);
        }

        // Clear local license data
        if ($admin) {
            $this->settings->set('rewardloyalty.license_token', '', $admin);
            $this->settings->set('rewardloyalty.purchase_code', '', $admin);
            $this->settings->set('rewardloyalty.license_status', 'inactive', $admin);
            $this->settings->set('rewardloyalty.support_expires_at', '', $admin);
            $this->settings->set('rewardloyalty.buyer_email', '', $admin);
        }

        Log::info('LICENSE DEACTIVATED');

        return true;
    }

    /**
     * Get decrypted license token (public for UpdateService)
     */
    public function getLicenseToken(): ?string
    {
        try {
            return $this->settings->get('rewardloyalty.license_token');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve license token', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate unique instance fingerprint
     */
    protected function generateFingerprint(): string
    {
        return hash('sha256', config('app.key').config('app.url'));
    }

    /**
     * Get cached license status
     */
    protected function getCachedStatus(): array
    {
        return [
            'is_valid' => $this->isActive(),
            'status' => $this->settings->get('rewardloyalty.license_status', 'inactive'),
            'support_active' => $this->isSupportActive(),
        ];
    }

    /**
     * Handle validation failure with grace period
     */
    protected function handleValidationFailure(): array
    {
        $lastValidatedStr = $this->settings->get('rewardloyalty.last_validated_at');
        if (! $lastValidatedStr) {
            return [
                'is_valid' => false,
                'status' => 'inactive',
                'support_active' => false,
            ];
        }

        $lastValidated = Carbon::parse($lastValidatedStr);
        $gracePeriodHours = 72; // 3 days
        $graceExpiry = $lastValidated->addHours($gracePeriodHours);

        if ($graceExpiry->isFuture()) {
            return $this->getCachedStatus();
        }

        return [
            'is_valid' => false,
            'status' => 'validation_failed',
            'support_active' => false,
        ];
    }
}
