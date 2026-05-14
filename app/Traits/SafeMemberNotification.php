<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2026 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Ensures notifications are only sent to registered members with emails.
 * Anonymous members (those without linked emails) are gracefully skipped.
 *
 * Usage:
 * Apply this trait to any notification that should only go to registered members:
 *
 * ```php
 * class PointsReceived extends Notification
 * {
 *     use Queueable, SafeMemberNotification;
 *     // ...
 * }
 * ```
 *
 * @see App\Models\Member::canReceiveEmail()
 */

namespace App\Traits;

use App\Models\Member;

trait SafeMemberNotification
{
    /**
     * Determine if the notification can be sent to this member.
     * Returns false for anonymous members or those who can't receive email.
     */
    protected function canSendToMember(Member $member): bool
    {
        return $member->isRegistered() && $member->canReceiveEmail();
    }

    /**
     * Get the notification's delivery channels.
     * Returns empty array for anonymous members (notification is silently skipped).
     *
     * Override this if your notification needs different channel logic,
     * but be sure to call canSendToMember() to respect anonymous members.
     *
     * @param  object  $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Skip notification entirely for anonymous members
        if ($notifiable instanceof Member && ! $this->canSendToMember($notifiable)) {
            return []; // No channels = notification skipped silently
        }

        // Default to mail channel for registered members
        return ['mail'];
    }

    /**
     * Check if the notifiable should receive this notification.
     * This is called by Laravel before via() is invoked.
     *
     * @param  object  $notifiable
     * @return bool
     */
    public function shouldSend(object $notifiable): bool
    {
        if ($notifiable instanceof Member) {
            return $this->canSendToMember($notifiable);
        }

        return true; // Non-members (e.g., admins, partners) always receive
    }
}
