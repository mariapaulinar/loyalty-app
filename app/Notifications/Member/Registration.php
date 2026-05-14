<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Notifications\Member;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;

class Registration extends Notification implements ShouldQueue
{
    use Queueable;

    protected $email;

    protected $password;

    protected $guard;

    protected $from;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $email, string $password, ?string $guard = null, ?string $from = null)
    {
        $this->email = $email;
        $this->password = $password;
        $this->guard = $guard;
        $this->from = $from;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        // Don't attempt to send if using non-sending mailers (log/array)
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            return [];
        }

        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $mailFromAddress = config('default.mail_from_address');
        $mailFromName = config('default.mail_from_name');

        // Set URL defaults to use the notifiable's preferred locale
        // This ensures generated URLs include the correct locale segment (e.g., /nl-nl/login)
        set_url_locale_for_user($notifiable);

        $routeName = $this->guard.'.login';
        if (! in_array($this->guard, ['admin', 'partner', 'staff'])) {
            $routeName = 'member.login';
        }

        $routeParams = [];

        if (config('default.registration_email_link')) {
            $routeParams['e'] = Crypt::encryptString($this->email);
            $routeParams['p'] = Crypt::encryptString($this->password);
        }

        if ($this->guard == 'member' && $this->from) {
            $routeParams['from'] = urlencode($this->from);
        }

        $loginLink = route($routeName, $routeParams);

        // Build styled password badge - clean monospace design
        $passwordBadge = '<span style="display: inline-block; background-color: #f1f5f9; color: #0f172a; padding: 10px 16px; border-radius: 8px; border: 1px solid #e2e8f0; font-family: \'SF Mono\', Monaco, \'Courier New\', monospace; font-size: 16px; font-weight: 600; letter-spacing: 1px;">'.$this->password.'</span>';

        return (new MailMessage)
            ->theme('platform')
            ->from($mailFromAddress, $mailFromName)
            ->subject(trans('common.registration_subject'))
            ->greeting(trans('common.greeting'))
            ->line(new \Illuminate\Support\HtmlString(trans('common.registration_body', ['password' => $passwordBadge])))
            ->action(trans('common.log_in'), $loginLink)
            ->line(trans('common.registration_subcopy'))
            ->salutation(trans('common.salutation'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        return [
            // Additional data if needed
        ];
    }
}
