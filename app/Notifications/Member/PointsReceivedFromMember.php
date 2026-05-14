<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Notifications\Member;

use App\Models\Card;
use App\Models\Member;
use App\Traits\SafeMemberNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NumberFormatter;

class PointsReceivedFromMember extends Notification implements ShouldQueue
{
    use Queueable, SafeMemberNotification;

    protected $sender;

    protected $receiver;

    protected $points;

    protected $card;

    /**
     * Create a new notification instance.
     */
    public function __construct(Member $sender, Member $receiver, string $points, Card $card)
    {
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->points = $points;
        $this->card = $card;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        // Skip if member cannot receive email (anonymous, unsubscribed, or demo mode)
        if ($notifiable instanceof Member && !$this->canSendToMember($notifiable)) {
            return [];
        }

        // Skip in demo mode or with non-sending mailers
        if (config('default.app_demo') || in_array(config('mail.default'), ['log', 'array'], true)) {
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

        // Set URL defaults to use the receiver's preferred locale
        set_url_locale_for_user($this->receiver);

        $locale = $this->receiver->preferredLocale();
        $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        $points = $formatter->format((float) $this->points);
        $cardLink = route('member.card', ['card_id' => $this->card->id]);

        // Build styled components
        $pointsBadge = '<span style="color: #059669; font-size: 18px; font-weight: 600;">'.$points.'</span>';
        $senderName = '<strong>'.$this->sender->name.'</strong>';
        $cardName = '<strong>'.$this->card->head.'</strong>';

        return (new MailMessage)
            ->theme('platform')
            ->from($mailFromAddress, $mailFromName)
            ->subject(trans('common.points_received_by_member_subject', ['points' => $points, 'sender_name' => $this->sender->name]))
            ->greeting(trans('common.greeting'))
            ->line(new \Illuminate\Support\HtmlString(trans('common.points_received_by_member_body', ['points' => $pointsBadge, 'sender_name' => $senderName, 'card_name' => $cardName])))
            ->action(trans('common.points_received_by_member_cta'), $cardLink)
            ->line(trans('common.points_received_by_member_subcopy'))
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
