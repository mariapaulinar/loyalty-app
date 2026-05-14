{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Premium Email Button
--}}
@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
@php
    $colors = [
        'primary' => ['bg' => '#0f172a', 'text' => '#ffffff'],
        'blue' => ['bg' => '#2563eb', 'text' => '#ffffff'],
        'success' => ['bg' => '#059669', 'text' => '#ffffff'],
        'green' => ['bg' => '#059669', 'text' => '#ffffff'],
        'error' => ['bg' => '#dc2626', 'text' => '#ffffff'],
        'red' => ['bg' => '#dc2626', 'text' => '#ffffff'],
    ];
    $btnColor = $colors[$color] ?? $colors['primary'];
@endphp
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 32px auto; padding: 0; text-align: {{ $align }}; width: 100%;">
<tr>
<td align="{{ $align }}">
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
<a href="{{ $url }}" class="button button-{{ $color }}" target="_blank" rel="noopener" style="background-color: {{ $btnColor['bg'] }}; border-radius: 12px; color: {{ $btnColor['text'] }}; display: inline-block; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 600; letter-spacing: 0.02em; line-height: 1; padding: 16px 32px; text-decoration: none; -webkit-text-size-adjust: none; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.15);">{{ $slot }}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
