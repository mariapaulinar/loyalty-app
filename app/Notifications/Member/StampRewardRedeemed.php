<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Confirmation notification when member redeems a stamp card reward.
 * Provides receipt and encourages continued engagement.
 */

namespace App\Notifications\Member;

use App\Models\StampCard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class StampRewardRedeemed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public StampCard $card
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
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
            'type' => 'stamp_reward_redeemed',
            'stamp_card_id' => $this->card->id,
            'stamp_card_title' => $cardTitle,
            'reward_title' => $rewardTitle,
            'message' => trans('common.stamp_reward_redeemed_message', ['reward' => $rewardTitle]),
        ];
    }
}
