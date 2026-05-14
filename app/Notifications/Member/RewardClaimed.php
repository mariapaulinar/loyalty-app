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
use App\Models\Reward;
use App\Traits\SafeMemberNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NumberFormatter;

class RewardClaimed extends Notification implements ShouldQueue
{
    use Queueable, SafeMemberNotification;

    protected $member;

    protected $points;

    protected $card;

    protected $reward;

    /**
     * Create a new notification instance.
     */
    public function __construct(Member $member, string $points, Card $card, Reward $reward)
    {
        $this->member = $member;
        $this->points = $points;
        $this->card = $card;
        $this->reward = $reward;
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
        $rewardLink = route('member.card.reward', ['card_id' => $this->card->id, 'reward_id' => $this->reward->id]);

        return (new MailMessage)
            ->theme('platform')
            ->from($mailFromAddress, $mailFromName)
            ->subject(trans('common.reward_claimed_subject', ['reward_title' => $this->reward->title]))
            ->greeting(trans('common.greeting'))
            ->line(trans('common.reward_claimed_body', ['reward_title' => '<strong>'.$this->reward->title.'</strong>', 'points' => '<strong>'.$points.'</strong>']))
            ->action(trans('common.reward_claimed_cta'), $rewardLink)
            ->line(trans('common.reward_claimed_subcopy'))
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
