<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Handles email testing during the installation process.
 * Allows users to verify their email configuration works before completing setup.
 */

namespace App\Http\Controllers\Installation;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailTestController extends Controller
{
    /**
     * Test email configuration by sending a test email.
     */
    public function test(string $locale, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'MAIL_MAILER' => 'required|string',
            'MAIL_FROM_ADDRESS' => 'required|email',
            'MAIL_FROM_NAME' => 'required|string',
            'MAIL_HOST' => 'nullable|string',
            'MAIL_PORT' => 'nullable|integer',
            'MAIL_USERNAME' => 'nullable|string',
            'MAIL_PASSWORD' => 'nullable|string',
            'MAIL_ENCRYPTION' => 'nullable|string',
            'MAILGUN_DOMAIN' => 'nullable|string',
            'MAILGUN_SECRET' => 'nullable|string',
            'MAILGUN_ENDPOINT' => 'nullable|string',
            'AWS_ACCESS_KEY_ID' => 'nullable|string',
            'AWS_SECRET_ACCESS_KEY' => 'nullable|string',
            'AWS_DEFAULT_REGION' => 'nullable|string',
            'POSTMARK_TOKEN' => 'nullable|string',
            'RESEND_KEY' => 'nullable|string',
            'test_recipient' => 'required|email',
        ]);

        // For log and mailpit drivers, we can always "succeed"
        if (in_array($validated['MAIL_MAILER'], ['log', 'mailpit'])) {
            return response()->json([
                'success' => true,
                'message' => $validated['MAIL_MAILER'] === 'log'
                    ? trans('install.test_email_success').' (Email logged to storage/logs/laravel.log)'
                    : trans('install.test_email_success'),
            ]);
        }

        try {
            // Temporarily configure mail settings
            $this->configureMailSettings($validated);

            // Send test email
            Mail::raw(
                $this->getTestEmailContent(),
                function ($message) use ($validated) {
                    $message->to($validated['test_recipient'])
                        ->subject('🎉 '.trans('install.test_email_success').' - '.config('default.app_name'));
                }
            );

            return response()->json([
                'success' => true,
                'message' => trans('install.test_email_success'),
            ]);
        } catch (\Exception $e) {
            Log::error('Installation email test failed', [
                'error' => $e->getMessage(),
                'mailer' => $validated['MAIL_MAILER'],
            ]);

            return response()->json([
                'success' => false,
                'message' => trans('install.test_email_failed'),
                'error' => $this->getFriendlyErrorMessage($e),
            ], 422);
        }
    }

    /**
     * Configure mail settings temporarily for testing.
     */
    private function configureMailSettings(array $settings): void
    {
        $mailer = $settings['MAIL_MAILER'];

        // Set the mailer
        config(['mail.default' => $mailer]);

        // Set from address
        config([
            'mail.from.address' => $settings['MAIL_FROM_ADDRESS'],
            'mail.from.name' => $settings['MAIL_FROM_NAME'],
        ]);

        // Configure based on mailer type
        match ($mailer) {
            'smtp' => $this->configureSmtp($settings),
            'mailgun' => $this->configureMailgun($settings),
            'ses' => $this->configureSes($settings),
            'postmark' => $this->configurePostmark($settings),
            'resend' => $this->configureResend($settings),
            'sendmail' => $this->configureSendmail(),
            default => null,
        };
    }

    /**
     * Configure SMTP settings.
     */
    private function configureSmtp(array $settings): void
    {
        // Handle encryption: "null" string from form means no encryption (actual null)
        $encryption = $settings['MAIL_ENCRYPTION'] ?? 'tls';
        if ($encryption === 'null' || $encryption === '') {
            $encryption = null;
        }

        config([
            'mail.mailers.smtp.host' => $settings['MAIL_HOST'] ?? 'localhost',
            'mail.mailers.smtp.port' => (int) ($settings['MAIL_PORT'] ?? 587),
            'mail.mailers.smtp.username' => $settings['MAIL_USERNAME'] ?? null,
            'mail.mailers.smtp.password' => $settings['MAIL_PASSWORD'] ?? null,
            'mail.mailers.smtp.encryption' => $encryption,
        ]);
    }

    /**
     * Configure Mailgun settings.
     */
    private function configureMailgun(array $settings): void
    {
        config([
            'services.mailgun.domain' => $settings['MAILGUN_DOMAIN'] ?? '',
            'services.mailgun.secret' => $settings['MAILGUN_SECRET'] ?? '',
            'services.mailgun.endpoint' => $settings['MAILGUN_ENDPOINT'] ?? 'api.mailgun.net',
        ]);
    }

    /**
     * Configure Amazon SES settings.
     */
    private function configureSes(array $settings): void
    {
        config([
            'services.ses.key' => $settings['AWS_ACCESS_KEY_ID'] ?? '',
            'services.ses.secret' => $settings['AWS_SECRET_ACCESS_KEY'] ?? '',
            'services.ses.region' => $settings['AWS_DEFAULT_REGION'] ?? 'us-east-1',
        ]);
    }

    /**
     * Configure Postmark settings.
     */
    private function configurePostmark(array $settings): void
    {
        config([
            'services.postmark.token' => $settings['POSTMARK_TOKEN'] ?? '',
        ]);
    }

    /**
     * Configure Resend settings.
     */
    private function configureResend(array $settings): void
    {
        config([
            'resend.api_key' => $settings['RESEND_KEY'] ?? '',
        ]);
    }

    /**
     * Configure Sendmail settings.
     */
    private function configureSendmail(): void
    {
        config([
            'mail.mailers.sendmail.path' => '/usr/sbin/sendmail -bs -i',
        ]);
    }

    /**
     * Get test email content.
     */
    private function getTestEmailContent(): string
    {
        $appName = config('default.app_name');

        return <<<TEXT
        🎉 Congratulations!

        Your email configuration is working perfectly.

        This test email confirms that {$appName} can send emails to your customers.
        They'll receive secure login codes just like this message.

        You're all set to complete the installation!

        —
        {$appName} Installation Wizard
        TEXT;
    }

    /**
     * Get a user-friendly error message from the exception.
     */
    private function getFriendlyErrorMessage(\Exception $e): string
    {
        $message = $e->getMessage();

        // Common error patterns and friendly messages
        $patterns = [
            '/Connection could not be established/i' => 'Cannot connect to the mail server. Please check the host and port.',
            '/Authentication failed/i' => 'Invalid username or password. For Gmail, make sure you\'re using an App Password.',
            '/Connection refused/i' => 'The mail server refused the connection. Check if the port is correct.',
            '/Timeout/i' => 'Connection timed out. The server may be slow or unreachable.',
            '/SSL|TLS|certificate/i' => 'Security error. Try a different encryption setting.',
            '/550|553|554/i' => 'The server rejected the email. Check your from address is valid.',
            '/relay/i' => 'Mail relay not permitted. Verify your credentials and from address.',
        ];

        foreach ($patterns as $pattern => $friendlyMessage) {
            if (preg_match($pattern, $message)) {
                return $friendlyMessage;
            }
        }

        // Return a generic message with the technical details for debugging
        return 'Error: '.substr($message, 0, 150);
    }
}
