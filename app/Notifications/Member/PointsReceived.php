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

class PointsReceived extends Notification implements ShouldQueue
{
    use Queueable, SafeMemberNotification;

    protected $member;

    protected $points;

    protected $card;

    /**
     * Create a new notification instance.
     */
    public function __construct(Member $member, string $points, Card $card)
    {
        $this->member = $member;
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

        // Set URL defaults to use the member's preferred locale
        set_url_locale_for_user($this->member);

        $locale = $this->member->preferredLocale();
        $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        $points = $formatter->format((float) $this->points);
        $cardLink = route('member.card', ['card_id' => $this->card->id]);

        // Build styled points badge
        $pointsBadge = '<span style="color: #059669; font-size: 18px; font-weight: 600;">'.$points.'</span>';

        return (new MailMessage)
            ->theme('platform')
            ->from($mailFromAddress, $mailFromName)
            ->subject(trans('common.points_received_subject', ['points' => $points]))
            ->greeting(trans('common.greeting'))
            ->line(new \Illuminate\Support\HtmlString(trans('common.points_received_body', ['points' => $pointsBadge])))
            ->action(trans('common.points_received_cta'), $cardLink)
            ->line(trans('common.points_received_subcopy'))
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
