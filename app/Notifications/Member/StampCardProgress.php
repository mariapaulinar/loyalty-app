<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Notification sent when member reaches a progress milestone (50%, 80%, etc.).
 * Keeps members engaged and motivated to complete their stamp card.
 */

namespace App\Notifications\Member;

use App\Models\Member;
use App\Models\StampCard;
use App\Traits\SafeMemberNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class StampCardProgress extends Notification implements ShouldQueue
{
    use Queueable, SafeMemberNotification;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public StampCard $card,
        public int $currentStamps,
        public int $stampsRequired,
        public int $milestone
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
        $rewardTitle = $this->card->reward_title;
        $stampsNeeded = $this->stampsRequired - $this->currentStamps;

        $mailFromAddress = config('default.mail_from_address');
        $mailFromName = config('default.mail_from_name');

        // Set locale for translations based on the member's preference
        set_url_locale_for_user($notifiable);

        $encouragement = match ($this->milestone) {
            50 => trans('common.stamp_progress_encouragement_halfway'),
            75, 80 => trans('common.stamp_progress_encouragement_almost'),
            default => trans('common.stamp_progress_encouragement_default'),
        };

        $stampsWord = $this->stampsRequired === 1 ? trans('common.stamp') : trans('common.stamps');
        $stampNeededWord = $stampsNeeded === 1 ? trans('common.stamp') : trans('common.stamps');

        return (new MailMessage)
            ->theme('platform')
            ->from($mailFromAddress, $mailFromName)
            ->subject(trans('common.stamp_progress_subject', ['encouragement' => $encouragement, 'milestone' => $this->milestone, 'card' => $cardTitle]))
            ->greeting(trans('common.greeting'))
            ->line(new HtmlString(trans('common.stamp_progress_line_1', [
                'encouragement' => $encouragement,
                'current' => $this->currentStamps,
                'required' => $this->stampsRequired,
                'stamps_word' => $stampsWord,
                'card' => '<strong>'.$cardTitle.'</strong>',
            ])))
            ->line(new HtmlString(trans('common.stamp_progress_line_2', [
                'needed' => $stampsNeeded,
                'stamp_needed_word' => $stampNeededWord,
                'reward' => '<strong>'.$rewardTitle.'</strong>',
            ])))
            ->line(trans('common.stamp_progress_line_3'))
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
        $rewardTitle = $this->card->reward_title;

        return [
            'type' => 'stamp_progress',
            'stamp_card_id' => $this->card->id,
            'stamp_card_title' => $cardTitle,
            'current_stamps' => $this->currentStamps,
            'stamps_required' => $this->stampsRequired,
            'milestone' => $this->milestone,
            'reward_title' => $rewardTitle,
            'stamps_needed' => $this->stampsRequired - $this->currentStamps,
        ];
    }
}
