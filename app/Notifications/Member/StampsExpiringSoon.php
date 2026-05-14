<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Warning notification when member's stamps are about to expire.
 * Encourages member to visit soon to avoid losing progress.
 */

namespace App\Notifications\Member;

use App\Models\Member;
use App\Models\StampCard;
use App\Traits\SafeMemberNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class StampsExpiringSoon extends Notification implements ShouldQueue
{
    use Queueable, SafeMemberNotification;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public StampCard $card,
        public int $currentStamps,
        public Carbon $expiresAt
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database']; // Always send to database for in-app notifications

        // Only send email if member can receive it (not anonymous)
        if ($notifiable instanceof Member && $this->canSendToMember($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $cardTitle = $this->card->title;
        $daysLeft = now()->diffInDays($this->expiresAt);

        $mailFromAddress = config('default.mail_from_address');
        $mailFromName = config('default.mail_from_name');

        // Set locale for translations based on the member's preference
        set_url_locale_for_user($notifiable);

        $urgency = $daysLeft <= 3
            ? trans('common.stamps_expiring_urgent_prefix')
            : trans('common.stamps_expiring_reminder_prefix');

        $stampWord = $this->currentStamps === 1 ? trans('common.stamp') : trans('common.stamps');
        $dayWord = $daysLeft === 1 ? trans('common.day') : trans('common.days');

        return (new MailMessage)
            ->theme('platform')
            ->from($mailFromAddress, $mailFromName)
            ->subject(trans('common.stamps_expiring_subject', ['urgency' => $urgency]))
            ->greeting(trans('common.greeting'))
            ->line(new HtmlString(trans('common.stamps_expiring_line_1', [
                'stamps' => $this->currentStamps,
                'stamp_word' => $stampWord,
                'card' => $cardTitle,
                'days' => $daysLeft,
                'day_word' => $dayWord,
            ])))
            ->line(trans('common.stamps_expiring_line_2'))
            ->line(trans('common.stamps_expiring_line_3'))
            ->salutation(trans('common.salutation'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $cardTitle = $this->card->title;

        return [
            'type' => 'stamps_expiring_soon',
            'stamp_card_id' => $this->card->id,
            'stamp_card_title' => $cardTitle,
            'current_stamps' => $this->currentStamps,
            'expires_at' => $this->expiresAt->toIso8601String(),
            'days_left' => now()->diffInDays($this->expiresAt),
        ];
    }
}
