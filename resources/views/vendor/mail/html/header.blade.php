{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Premium Email Header
--}}
@props(['url'])
<tr>
<td class="header" style="padding: 40px 0 32px 0; text-align: center; background-color: #ffffff;">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo" style="height: 48px; max-height: 48px; width: auto;">
@else
<span style="color: #0f172a; font-size: 24px; font-weight: 800; text-decoration: none; letter-spacing: -0.03em; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">{{ $slot }}</span>
@endif
</a>
</td>
</tr>
