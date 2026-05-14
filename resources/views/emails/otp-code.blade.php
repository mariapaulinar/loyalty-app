{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Premium OTP Code Email Template

  Design Philosophy:
  - Revolut-grade minimal aesthetic
  - All colors explicitly defined (dark mode safe)
  - Centered, breathable layout
  - Code as the hero element
  - Silk-smooth, confidence-inspiring copy
--}}

@component('mail::message')
{{-- Title - Warm and welcoming --}}
<h1 style="color: #0f172a; font-size: 26px; font-weight: 700; margin: 0 0 12px 0; text-align: center; letter-spacing: -0.02em; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('otp.email_title_' . $purpose, ['app' => $appName]) }}
</h1>

{{-- Subtitle - Reassuring and clear --}}
<p style="color: #64748b; font-size: 16px; line-height: 1.7; margin: 0 0 36px 0; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('otp.email_intro_' . $purpose) }}
</p>

{{-- CODE BOX - The Hero Element (Centered) --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 0 28px 0;">
<tr>
<td align="center">
<table cellpadding="0" cellspacing="0" role="presentation" style="background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0; border-radius: 20px; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04);">
<tr>
<td style="padding: 32px 48px; text-align: center;">
{{-- Label --}}
<p style="color: #94a3b8; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.15em; margin: 0 0 20px 0; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('otp.verification_code') }}
</p>
{{-- The Code - Centered with generous letter spacing --}}
{{-- Note: color uses !important to prevent email clients from inverting in dark mode --}}
<p style="color: #0f172a !important; font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', 'Fira Code', 'Courier New', monospace; font-size: 42px; font-weight: 700; letter-spacing: 0.3em; margin: 0; padding: 0 0 0 0.3em; line-height: 1; text-align: center;">
{{ $code }}
</p>
</td>
</tr>
</table>
</td>
</tr>
</table>

{{-- Expiry Notice - Subtle but clear --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 0 36px 0;">
<tr>
<td align="center">
<span style="display: inline-block; background-color: #fef9c3; color: #854d0e; font-size: 13px; font-weight: 500; padding: 8px 16px; border-radius: 100px; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
◷ {{ trans('otp.email_expires', ['minutes' => $expiresInMinutes]) }}
</span>
</td>
</tr>
</table>

{{-- Security Notice - Trustworthy and helpful --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 0 28px 0;">
<tr>
<td align="center">
<table cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f8fafc; border-radius: 16px; max-width: 440px;">
<tr>
<td style="padding: 20px 28px;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td width="36" valign="top" style="padding-right: 14px;">
<span style="display: inline-block; color: #3b82f6; font-size: 18px; line-height: 1;">
◈
</span>
</td>
<td>
<p style="color: #64748b; font-size: 14px; line-height: 1.65; margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('otp.email_security_notice') }}
</p>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>

{{-- Warm Sign-off --}}
<p style="color: #64748b; font-size: 15px; line-height: 1.6; margin: 28px 0 0 0; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('common.salutation') }}<br>
<strong style="color: #334155;">{{ trans('common.the_team_at', ['app' => $appName]) }}</strong>
</p>

@slot('subcopy')
<p style="color: #94a3b8; font-size: 12px; line-height: 1.6; margin: 0; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('otp.email_subcopy') }}
</p>
@endslot
@endcomponent
