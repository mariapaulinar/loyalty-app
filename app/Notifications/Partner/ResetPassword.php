<?php

namespace App\Notifications\Partner;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    protected $resetLink;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $resetLink)
    {
        $this->resetLink = $resetLink;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        // Skip sending in demo mode (except local dev) or if using non-sending mailers
        if (config('default.app_demo') && ! app()->isLocal()) {
            return [];
        }

        // Don't attempt to send if using log/array mailers (development/testing)
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

        // Set URL defaults to use the notifiable's preferred locale for translations
        set_url_locale_for_user($notifiable);

        return (new MailMessage)
            ->theme('platform')
            ->from($mailFromAddress, $mailFromName)
            ->subject(trans('common.reset_password_subject'))
            ->greeting(trans('common.greeting'))
            ->line(trans('common.reset_password_body'))
            ->action(trans('common.reset_password_cta'), $this->resetLink)
            ->line(trans('common.reset_password_subcopy'))
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
