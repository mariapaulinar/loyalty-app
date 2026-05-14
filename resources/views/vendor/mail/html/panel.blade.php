{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Premium Email Panel
--}}
<table class="panel" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 24px 0; border-radius: 12px; overflow: hidden;">
<tr>
<td class="panel-content" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; color: #475569; padding: 20px 24px;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="panel-item" style="padding: 0;">
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr>
</table>
</td>
</tr>
</table>
