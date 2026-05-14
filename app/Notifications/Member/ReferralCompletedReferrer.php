<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Notifications\Member;

use App\Models\Member;
use App\Models\Referral;
use App\Traits\SafeMemberNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralCompletedReferrer extends Notification implements ShouldQueue
{
    use Queueable, SafeMemberNotification;

    public function __construct(
        public Referral $referral,
        public int $points,
        public string $cardTitle,
        public string $clubName,
    ) {}

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

    public function toMail($notifiable): MailMessage
    {
        $mailFromAddress = config('default.mail_from_address');
        $mailFromName = config('default.mail_from_name');

        // Set locale for translations and URLs based on the referrer's preference
        set_url_locale_for_user($notifiable);

        return (new MailMessage)
            ->theme('platform')
            ->from($mailFromAddress, $mailFromName)
            ->subject(trans('common.referral.email_referrer_subject', ['points' => $this->points]))
            ->view('emails.referral.referrer-completed', [
                'referral' => $this->referral,
                'points' => $this->points,
                'cardTitle' => $this->cardTitle,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'referral_id' => $this->referral->id,
            'points' => $this->points,
            'event' => 'referral_completed_referrer',
        ];
    }
}
