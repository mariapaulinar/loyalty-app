<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Notifies member when a targeted voucher is assigned to them.
 */

namespace App\Notifications\Member;

use App\Models\Member;
use App\Models\Voucher;
use App\Traits\SafeMemberNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class VoucherReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable, SafeMemberNotification;

    public function __construct(
        public Voucher $voucher
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database']; // Always send to database for in-app notifications

        // Only send email if member can receive it (not anonymous)
        if ($notifiable instanceof Member && $this->canSendToMember($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->voucher->title ?: $this->voucher->name;
        $code = $this->voucher->code;
        $value = $this->voucher->formatted_value;

        $mailFromAddress = config('default.mail_from_address');
        $mailFromName = config('default.mail_from_name');

        // Set URL defaults to use the notifiable's preferred locale
        set_url_locale_for_user($notifiable);

        $codeHtml = new HtmlString('<strong style="font-family: monospace; font-size: 18px; letter-spacing: 2px;">'.$code.'</strong>');

        return (new MailMessage)
            ->theme('platform')
            ->from($mailFromAddress, $mailFromName)
            ->subject(trans('common.voucher_received_subject', ['value' => $value]))
            ->greeting(trans('common.greeting'))
            ->line(new HtmlString(trans('common.voucher_received_line_1', ['title' => '<strong>'.$title.'</strong>'])))
            ->line(new HtmlString(trans('common.voucher_received_line_2', ['code' => $codeHtml, 'value' => '<strong>'.$value.'</strong>'])))
            ->line($this->voucher->description ?: trans('common.voucher_received_default_description'))
            ->action(trans('common.view_voucher'), route('member.voucher', ['voucher_id' => $this->voucher->id]))
            ->line(trans('common.voucher_received_thanks'))
            ->salutation(trans('common.salutation'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'voucher_received',
            'voucher_id' => $this->voucher->id,
            'voucher_code' => $this->voucher->code,
            'voucher_title' => $this->voucher->title,
            'voucher_value' => $this->voucher->formatted_value,
        ];
    }
}
