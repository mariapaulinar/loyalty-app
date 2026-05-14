<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Alerts partner when a voucher reaches its maximum usage limit.
 */

namespace App\Notifications\Partner;

use App\Models\Voucher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VoucherExhaustedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Voucher $voucher
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $code = $this->voucher->code;
        $uses = $this->voucher->times_used;
        $discount = moneyFormat($this->voucher->total_discount_given / 100, $this->voucher->currency);

        // Set URL defaults to use the notifiable's preferred locale
        set_url_locale_for_user($notifiable);

        return (new MailMessage)
            ->subject("Voucher {$code} Has Reached Its Limit")
            ->greeting('Hello!')
            ->line("Your voucher <strong>{$code}</strong> has reached its maximum usage limit.")
            ->line("Total redemptions: <strong>{$uses}</strong>")
            ->line("Total discount provided: <strong>{$discount}</strong>")
            ->line('Consider creating a new voucher or increasing the usage limit for continued promotion.')
            ->action('View Voucher Analytics', route('partner.voucher-analytics.voucher', ['voucher_id' => $this->voucher->id]));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'voucher_exhausted',
            'voucher_id' => $this->voucher->id,
            'voucher_code' => $this->voucher->code,
            'total_uses' => $this->voucher->times_used,
            'total_discount' => $this->voucher->total_discount_given,
        ];
    }
}
