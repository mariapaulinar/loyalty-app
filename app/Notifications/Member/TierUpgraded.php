<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Notification sent to members when they upgrade to a higher tier.
 * Celebrates their achievement and highlights new benefits.
 */

namespace App\Notifications\Member;

use App\Models\Club;
use App\Models\Member;
use App\Models\Tier;
use App\Traits\SafeMemberNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TierUpgraded extends Notification implements ShouldQueue
{
    use Queueable, SafeMemberNotification;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Member $member,
        protected ?Tier $previousTier,
        protected Tier $newTier,
        protected Club $club
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
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

        $tierName = $this->newTier->getLocalizedName($this->member->preferredLocale());
        $multiplierText = '';

        if ((float) $this->newTier->points_multiplier > 1.0) {
            $multiplier = number_format((float) $this->newTier->points_multiplier, 1);
            $multiplierText = trans('common.tier_multiplier_bonus', ['multiplier' => $multiplier]);
        }

        return (new MailMessage)
            ->theme('platform')
            ->from($mailFromAddress, $mailFromName)
            ->subject(trans('common.tier_upgraded_subject', ['tier' => $tierName]))
            ->greeting(trans('common.greeting'))
            ->line(trans('common.tier_upgraded_body', [
                'tier' => '<strong>'.$tierName.'</strong>',
                'icon' => $this->newTier->icon ?? '🎉',
            ]))
            ->when($multiplierText, function (MailMessage $mail) use ($multiplierText) {
                return $mail->line($multiplierText);
            })
            ->action(trans('common.view_wallet'), route('member.cards'))
            ->line(trans('common.tier_upgraded_subcopy'))
            ->salutation(trans('common.salutation'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'tier_upgraded',
            'tier_id' => $this->newTier->id,
            'tier_name' => $this->newTier->name,
            'previous_tier_id' => $this->previousTier?->id,
            'previous_tier_name' => $this->previousTier?->name,
            'club_id' => $this->club->id,
        ];
    }
}
