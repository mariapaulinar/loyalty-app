{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Voucher Claimed Email Template

  Design Philosophy:
  - Apple-grade premium aesthetic
  - Voucher code as hero element (large, monospaced)
  - All discount details clearly presented
  - Single clear CTA: View in Wallet
  - Color palette: Primary brand colors with accessibility
--}}

@component('mail::message')
{{-- Celebration Header --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 0 24px 0;">
<tr>
<td align="center">
<span style="font-size: 48px; line-height: 1;">✨</span>
</td>
</tr>
</table>

{{-- Title --}}
<h1 style="color: #0f172a; font-size: 28px; font-weight: 700; margin: 0 0 12px 0; text-align: center; letter-spacing: -0.02em; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('common.voucher_claimed') }}!
</h1>

{{-- Subtitle --}}
<p style="color: #64748b; font-size: 16px; line-height: 1.7; margin: 0 0 36px 0; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('common.your_exclusive_discount') }}
</p>

{{-- VOUCHER CODE BOX - The Hero Element --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 0 32px 0;">
<tr>
<td align="center">
<table cellpadding="0" cellspacing="0" role="presentation" style="background: linear-gradient(145deg, #f0f9ff 0%, #e0f2fe 100%); border: 2px solid #3b82f6; border-radius: 20px; box-shadow: 0 8px 32px rgba(59, 130, 246, 0.12);">
<tr>
<td style="padding: 40px 48px; text-align: center;">
{{-- Label --}}
<p style="color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.15em; margin: 0 0 20px 0; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('common.your_code') }}
</p>
{{-- The Code --}}
<p style="color: #1e40af; font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', 'Fira Code', 'Courier New', monospace; font-size: 38px; font-weight: 700; letter-spacing: 0.2em; margin: 0; padding: 0 0 0 0.2em; line-height: 1; text-align: center;">
{{ $voucher->code }}
</p>
</td>
</tr>
</table>
</td>
</tr>
</table>

{{-- Discount Details --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 0 32px 0;">
<tr>
<td align="center">
<table cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f8fafc; border-radius: 16px; max-width: 480px; width: 100%;">
<tr>
<td style="padding: 28px;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">

{{-- Discount Type/Value --}}
@if($voucher->type === 'percentage')
<tr>
<td width="40" valign="top" style="padding: 0 12px 20px 0;">
<span style="display: inline-block; background-color: #d1fae5; color: #059669; width: 32px; height: 32px; border-radius: 10px; text-align: center; line-height: 32px; font-size: 18px;">%</span>
</td>
<td style="padding: 0 0 20px 0;">
<p style="color: #0f172a; font-size: 16px; font-weight: 600; margin: 0 0 4px 0; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ rtrim(rtrim(number_format($voucher->value / 100, 2, '.', ''), '0'), '.') }}% {{ trans('common.off') }}
</p>
<p style="color: #64748b; font-size: 14px; margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('common.on_your_purchase') }}
</p>
</td>
</tr>
@elseif($voucher->type === 'fixed_amount')
<tr>
<td width="40" valign="top" style="padding: 0 12px 20px 0;">
<span style="display: inline-block; background-color: #d1fae5; color: #059669; width: 32px; height: 32px; border-radius: 10px; text-align: center; line-height: 32px; font-size: 18px;">{{ currencySymbol($voucher->currency) }}</span>
</td>
<td style="padding: 0 0 20px 0;">
<p style="color: #0f172a; font-size: 16px; font-weight: 600; margin: 0 0 4px 0; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ moneyFormat($voucher->value / 100, $voucher->currency) }} {{ trans('common.off') }}
</p>
<p style="color: #64748b; font-size: 14px; margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('common.instant_discount') }}
</p>
</td>
</tr>
@endif

{{-- Club --}}
@if($voucher->club)
<tr>
<td width="40" valign="top" style="padding: 0 12px 20px 0;">
<span style="display: inline-block; background-color: #dbeafe; color: #1d4ed8; width: 32px; height: 32px; border-radius: 10px; text-align: center; line-height: 32px; font-size: 14px;">🏪</span>
</td>
<td style="padding: 0 0 20px 0;">
<p style="color: #0f172a; font-size: 16px; font-weight: 600; margin: 0 0 4px 0; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ $voucher->club->name }}
</p>
<p style="color: #64748b; font-size: 14px; margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('common.valid_at_this_location') }}
</p>
</td>
</tr>
@endif

{{-- Expiry --}}
@if($voucher->valid_until)
<tr>
<td width="40" valign="top" style="padding: 0 12px 0 0;">
<span style="display: inline-block; background-color: #fef3c7; color: #92400e; width: 32px; height: 32px; border-radius: 10px; text-align: center; line-height: 32px; font-size: 14px;">⏰</span>
</td>
<td style="padding: 0;">
<p style="color: #0f172a; font-size: 16px; font-weight: 600; margin: 0 0 4px 0; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ $voucher->valid_until->format('M d, Y') }}
</p>
<p style="color: #64748b; font-size: 14px; margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('common.use_before_expiry') }}
</p>
</td>
</tr>
@endif

</table>
</td>
</tr>
</table>
</td>
</tr>
</table>

{{-- CTA Button - Centered & Prominent --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 0 32px 0;">
<tr>
<td align="center">
<a href="{{ $voucherUrl }}" style="display: inline-block; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 16px; font-weight: 600; text-decoration: none; padding: 16px 48px; border-radius: 12px; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35); transition: all 0.3s ease;">
{{ trans('common.view_my_voucher') }}
</a>
</td>
</tr>
</table>

{{-- Footer Note --}}
<p style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin: 32px 0 0 0; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('common.show_this_code_at_checkout') }}
</p>

{{-- Sign-off --}}
<p style="color: #64748b; font-size: 15px; line-height: 1.6; margin: 36px 0 0 0; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ trans('common.happy_shopping') }}<br>
<strong style="color: #334155;">{{ trans('common.the_team_at', ['app' => $appName]) }}</strong>
</p>

@endcomponent
