<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Notification sent to members when their tier status changes to a lower level.
 * Provides guidance on how to regain their previous status.
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

class TierDowngraded extends Notification implements ShouldQueue
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

        $newTierName = $this->newTier->getLocalizedName($this->member->preferredLocale());
        $previousTierName = $this->previousTier?->getLocalizedName($this->member->preferredLocale()) ?? '';

        return (new MailMessage)
            ->theme('platform')
            ->from($mailFromAddress, $mailFromName)
            ->subject(trans('common.tier_status_changed_subject'))
            ->greeting(trans('common.greeting'))
            ->line(trans('common.tier_downgraded_body', [
                'new_tier' => '<strong>'.$newTierName.'</strong>',
                'previous_tier' => $previousTierName,
            ]))
            ->line(trans('common.tier_downgraded_encourage'))
            ->action(trans('common.view_wallet'), route('member.cards'))
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
            'type' => 'tier_downgraded',
            'tier_id' => $this->newTier->id,
            'tier_name' => $this->newTier->name,
            'previous_tier_id' => $this->previousTier?->id,
            'previous_tier_name' => $this->previousTier?->name,
            'club_id' => $this->club->id,
        ];
    }
}
