<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Voucher Claimed Notification Email
 *
 * Purpose:
 * Beautiful, actionable email sent when a member claims a voucher.
 * Includes the unique code, discount details, and link to wallet.
 *
 * Design Tenets:
 * - **Code First**: Voucher code prominently displayed
 * - **Actionable**: Clear CTA to view in wallet
 * - **Complete**: All details needed to redeem (expiry, min purchase, etc.)
 * - **Branded**: Matches app's visual identity
 */

namespace App\Mail;

use App\Models\Member;
use App\Models\Voucher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VoucherClaimedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Voucher $voucher;

    public Member $member;

    public string $voucherUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Voucher $voucher, Member $member)
    {
        $this->voucher = $voucher->load(['club']);
        $this->member = $member;

        // Set URL defaults to use the member's preferred locale
        // This ensures the voucher URL includes the correct locale segment (e.g., /nl-nl/member/voucher)
        set_url_locale_for_user($member);

        // Generate direct voucher URL (member will see confetti and celebration!)
        $this->voucherUrl = route('member.voucher', [
            'voucher_id' => $voucher->id,
        ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: config('default.mail_from_address'),
            subject: trans('common.your_voucher_is_ready'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.voucher-claimed',
            with: [
                'voucher' => $this->voucher,
                'member' => $this->member,
                'voucherUrl' => $this->voucherUrl,
                'appName' => config('default.app_name', config('app.name')),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
