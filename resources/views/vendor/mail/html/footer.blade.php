{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Premium Email Footer
--}}
<tr>
<td style="background-color: #ffffff;">
<table class="footer" align="center" width="560" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 auto; padding: 0; text-align: center; width: 560px; background-color: #ffffff;">
<tr>
<td class="content-cell" align="center" style="padding: 32px 48px 40px 48px; border-top: 1px solid #f1f5f9;">
<p style="color: #94a3b8; font-size: 12px; text-align: center; line-height: 1.6; margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ Illuminate\Mail\Markdown::parse($slot) }}
</p>
</td>
</tr>
</table>
</td>
</tr>
