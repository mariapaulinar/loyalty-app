<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * CampaignEmail Mailable
 *
 * Purpose:
 * Sends partner email campaigns to members.
 * Renders partner's message within branded template.
 *
 * Architecture:
 * - Uses member's preferred locale for translated content
 * - Falls back to default locale if translation missing
 * - Partner's sender name + system email for deliverability
 * - Includes signed unsubscribe link for compliance
 *
 * Personalization placeholders:
 * - {name} - Member's full name
 * - {email} - Member's email address
 */

namespace App\Mail;

use App\Models\EmailCampaign;
use App\Models\Member;
use App\Models\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class CampaignEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * The subject line for this member's locale.
     */
    private string $subjectLine;

    /**
     * The body content for this member's locale (with personalization applied).
     */
    private string $bodyContent;

    /**
     * The member's preferred locale for content translation.
     */
    private string $memberLocale;

    /**
     * Create a new message instance.
     *
     * @param  EmailCampaign  $campaign  The campaign being sent
     * @param  Member  $member  The recipient member
     * @param  Partner  $partner  The sending partner
     * @param  string  $memberLocale  The member's preferred locale
     */
    public function __construct(
        public EmailCampaign $campaign,
        public Member $member,
        public Partner $partner,
        string $memberLocale
    ) {
        $this->memberLocale = $memberLocale;

        // Get translated content for member's locale with fallback
        $this->subjectLine = $this->campaign->getSubjectForLocale($this->memberLocale);
        $this->bodyContent = $this->campaign->getBodyForLocale($this->memberLocale);

        // Apply personalization placeholders to both subject and body
        $this->subjectLine = $this->applyPersonalization($this->subjectLine);
        $this->bodyContent = $this->applyPersonalization($this->bodyContent);
    }

    /**
     * Get the message envelope.
     *
     * Sender configuration:
     * - From address: System email (for SPF/DKIM compliance)
     * - From name: Partner's configured sender name
     * - Reply-To: Partner's reply email (so replies go to them)
     */
    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            from: new Address(
                config('default.mail_from_address'),
                $this->partner->getCampaignSenderName()
            ),
            subject: $this->subjectLine,
        );

        // Add reply-to if partner has configured one
        $replyTo = $this->partner->getCampaignReplyTo();
        if ($replyTo) {
            $envelope->replyTo = [new Address($replyTo)];
        }

        return $envelope;
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.campaign.message',
            with: [
                'campaign' => $this->campaign,
                'member' => $this->member,
                'partner' => $this->partner,
                'body' => $this->bodyContent,
                'locale' => $this->memberLocale,
                'appName' => config('default.app_name', config('app.name')),
                'unsubscribeUrl' => $this->generateUnsubscribeUrl(),
            ],
        );
    }

    /**
     * Generate signed unsubscribe URL for this member.
     *
     * The URL is signed (tamper-proof) and never expires.
     * Clicking it sets member.accepts_emails = false.
     * Includes locale segment for proper i18n support.
     */
    private function generateUnsubscribeUrl(): string
    {
        // Convert locale to URL slug format (en_US → en-us)
        $localeSlug = strtolower(str_replace('_', '-', $this->memberLocale));

        return URL::signedRoute(
            'email.unsubscribe',
            [
                'locale' => $localeSlug,
                'member' => $this->member->id,
            ]
        );
    }

    /**
     * Replace personalization placeholders with member data.
     *
     * Available placeholders:
     * - {name} → Member's full name
     * - {email} → Member's email address
     */
    private function applyPersonalization(string $content): string
    {
        return str_replace(
            ['{name}', '{email}'],
            [
                $this->member->name ?? '',
                $this->member->email ?? '',
            ],
            $content
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
