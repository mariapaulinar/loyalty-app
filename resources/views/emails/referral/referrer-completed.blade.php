{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.
--}}

@component('mail::message')
{{-- Title --}}
<h1 style="color: #0f172a; font-size: 26px; font-weight: 700; margin: 0 0 12px 0; text-align: center; letter-spacing: -0.02em; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('common.referral.email_referrer_title') }}
</h1>

{{-- Subtitle --}}
<p style="color: #64748b; font-size: 16px; line-height: 1.7; margin: 0 0 36px 0; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('common.referral.email_referrer_body', ['points' => $points, 'card' => $cardTitle]) }}
</p>

{{-- Hero Box --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 0 28px 0;">
<tr>
<td align="center">
<table cellpadding="0" cellspacing="0" role="presentation" style="background: linear-gradient(145deg, #f0f9ff 0%, #e0f2fe 100%); border: 1px solid #bae6fd; border-radius: 20px; box-shadow: 0 4px 24px rgba(14, 165, 233, 0.1);">
<tr>
<td style="padding: 32px 48px; text-align: center;">
<p style="color: #0369a1; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.15em; margin: 0 0 12px 0; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('common.referral.you_earned') }}
</p>
<p style="color: #0284c7; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 42px; font-weight: 800; letter-spacing: -0.02em; margin: 0; padding: 0; line-height: 1; text-align: center;">
{{ $points }} {{ trans('common.referral.pts') }}
</p>
</td>
</tr>
</table>
</td>
</tr>
</table>

@component('mail::button', ['url' => route('member.cards')])
{{ trans('common.referral.view_wallet') }}
@endcomponent

{{-- Footer --}}
<p style="color: #64748b; font-size: 15px; line-height: 1.6; margin: 28px 0 0 0; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('common.salutation') }}<br>
<strong style="color: #334155;">{{ trans('common.the_team_at', ['app' => config('app.name')]) }}</strong>
</p>
@endcomponent
