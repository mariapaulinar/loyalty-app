<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Congratulations notification when member completes a stamp card.
 * Informs member their reward is ready and how to redeem it.
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

class StampCardCompleted extends Notification implements ShouldQueue
{
    use Queueable, SafeMemberNotification;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public StampCard $card,
        public int $completionCount
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
        $rewardDescription = $this->card->reward_description;

        $mailFromAddress = config('default.mail_from_address');
        $mailFromName = config('default.mail_from_name');

        // Set locale for translations based on the member's preference
        set_url_locale_for_user($notifiable);

        $mail = (new MailMessage)
            ->theme('platform')
            ->from($mailFromAddress, $mailFromName)
            ->subject(trans('common.stamp_completed_subject', ['reward' => $rewardTitle]))
            ->greeting(trans('common.greeting'))
            ->line(new HtmlString(trans('common.stamp_completed_line_1', ['card' => '<strong>'.$cardTitle.'</strong>'])))
            ->line(new HtmlString(trans('common.stamp_completed_line_2', ['reward' => '<strong>'.$rewardTitle.'</strong>'])));

        if ($rewardDescription) {
            $mail->line($rewardDescription);
        }

        if ($this->card->require_staff_for_redemption) {
            $mail->line(new HtmlString(trans('common.stamp_completed_redeem_staff')));
        } else {
            $mail->line(new HtmlString(trans('common.stamp_completed_redeem_self', ['cta' => trans('common.collect_reward')])));
        }

        $mail->line(trans('common.stamp_completed_thanks'))
            ->salutation(trans('common.salutation'));

        return $mail;
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
            'type' => 'stamp_card_completed',
            'stamp_card_id' => $this->card->id,
            'stamp_card_title' => $cardTitle,
            'reward_title' => $rewardTitle,
            'completion_count' => $this->completionCount,
            'require_staff_redemption' => $this->card->require_staff_for_redemption,
        ];
    }
}
