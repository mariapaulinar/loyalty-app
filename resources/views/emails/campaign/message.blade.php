{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Campaign Email Template

Purpose: Render partner-composed email campaigns to members.
Philosophy: Partner's message takes center stage. Clean, distraction-free.
Design: Professional email layout that renders well across clients.

Variables:
- $body: Partner's composed message (HTML from TipTap)
- $partner: The sending partner
- $member: The recipient member
- $appName: The application name
- $unsubscribeUrl: Signed one-click unsubscribe URL
- $isPreview: Whether this is a preview (optional)
--}}

@component('mail::message')

{{-- Partner's Message Body --}}
{!! $body !!}

{{-- Spacer --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 40px 0 0 0;">
<tr>
<td style="border-top: 1px solid #e2e8f0;"></td>
</tr>
</table>

{{-- Footer --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 24px 0 0 0;">
<tr>
<td align="center">
<p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0 0 16px 0; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ $partner->getCampaignSenderName() }}
</p>
</td>
</tr>
</table>

{{-- Unsubscribe Link --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 24px 0 0 0;">
<tr>
<td align="center">
<p style="color: #94a3b8; font-size: 12px; line-height: 1.6; margin: 0; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('common.email_campaign.unsubscribe_text', [], $locale ?? config('app.locale')) }}
@if(!($isPreview ?? false))
<a href="{{ $unsubscribeUrl }}" style="color: #94a3b8; text-decoration: underline; font-weight: 500;">
{{ trans('common.email_campaign.unsubscribe', [], $locale ?? config('app.locale')) }}
</a>
@else
<span style="color: #94a3b8; text-decoration: underline;">
{{ trans('common.email_campaign.unsubscribe', [], $locale ?? config('app.locale')) }}
</span>
@endif
</p>
</td>
</tr>
</table>

@endcomponent

