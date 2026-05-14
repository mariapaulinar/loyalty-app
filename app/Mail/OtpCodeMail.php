<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Mailable for delivering OTP verification codes via email.
 * Clean, minimal design focused on the 6-digit code for easy reading.
 *
 * Design Tenets:
 * - **Clear**: Large, prominent display of the 6-digit code
 * - **Secure**: Includes expiration notice and "if you didn't request" warning
 * - **Branded**: Uses app's standard email theme and branding
 */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The plain OTP code to display.
     */
    public string $code;

    /**
     * The purpose of the OTP (login, verify_email, etc.).
     */
    public string $purpose;

    /**
     * The guard type (member, staff, partner, admin).
     */
    public string $guard;

    /**
     * Expiration time in minutes.
     */
    public int $expiresInMinutes;

    /**
     * The user's preferred locale for translations (stored separately to avoid Mailable property conflict).
     */
    protected ?string $userLocale;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $code,
        string $purpose = 'login',
        string $guard = 'member',
        int $expiresInMinutes = 10,
        ?string $locale = null
    ) {
        $this->code = $code;
        $this->purpose = $purpose;
        $this->guard = $guard;
        $this->expiresInMinutes = $expiresInMinutes;
        $this->userLocale = $locale ?? config('app.locale');

        // Use Laravel's built-in locale() method on Mailable
        // This properly sets locale for the queued mail job
        $this->locale($this->userLocale);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Ensure locale is set for translations in this context (especially for subject)
        app()->setLocale($this->userLocale);

        $subject = match ($this->purpose) {
            'login' => trans('otp.email_subject_login'),
            'verify_email' => trans('otp.email_subject_verify'),
            'password_reset' => trans('otp.email_subject_reset'),
            default => trans('otp.email_subject_default'),
        };

        return new Envelope(
            from: config('default.mail_from_address'),
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.otp-code',
            with: [
                'code' => $this->code,
                'purpose' => $this->purpose,
                'guard' => $this->guard,
                'expiresInMinutes' => $this->expiresInMinutes,
                'appName' => config('default.app_name', config('app.name')),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
