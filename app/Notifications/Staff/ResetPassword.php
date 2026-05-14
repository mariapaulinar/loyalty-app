<?php

namespace App\Notifications\Staff;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Class ResetPassword
 *
 * Notification to send password reset email to staff.
 */
class ResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    protected $resetLink;

    /**
     * Create a new notification instance.
     *
     * @param  string  $resetLink
     */
    public function __construct($resetLink)
    {
        $this->resetLink = $resetLink;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // Skip sending in demo mode or if using non-sending mailers (log/array)
        if (config('default.app_demo') || in_array(config('mail.default'), ['log', 'array'], true)) {
            return [];
        }

        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mail_from_address = config('default.mail_from_address');
        $mail_from_name = config('default.mail_from_name');

        // Set URL defaults to use the notifiable's preferred locale for translations
        set_url_locale_for_user($notifiable);

        return (new MailMessage)
            ->theme('platform')
            ->from($mail_from_address, $mail_from_name)
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
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
