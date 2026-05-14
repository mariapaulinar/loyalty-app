{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Premium Email Layout
  Apple/Revolut-inspired design with forced light mode for email client compatibility.
--}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{{ config('default.app_name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
{{-- Force light mode in email clients --}}
<meta name="color-scheme" content="light only">
<meta name="supported-color-schemes" content="light only">
<!--[if mso]>
<style type="text/css">
    body, table, td {font-family: Arial, Helvetica, sans-serif !important;}
</style>
<![endif]-->
<style>
/* Force light mode and prevent dark mode overrides */
:root {
    color-scheme: light only;
    supported-color-schemes: light only;
}

/* Dark mode prevention - keep wrapper backgrounds light */
@media (prefers-color-scheme: dark) {
    .wrapper, .body, .inner-body, .content-cell, .header, .footer {
        background-color: #f8fafc !important;
    }
    .inner-body {
        background-color: #ffffff !important;
    }
    /* Note: We do NOT reset colors to 'inherit' here because that would
       override explicitly set inline colors (like the OTP code).
       Email templates should use explicit inline colors for dark mode safety. */
}

/* Mobile responsive */
@media only screen and (max-width: 600px) {
    .inner-body {
        width: 100% !important;
        border-radius: 0 !important;
    }
    .footer {
        width: 100% !important;
    }
    .content-cell {
        padding: 32px 24px !important;
    }
}

@media only screen and (max-width: 500px) {
    .button {
        width: 100% !important;
    }
    .content-cell {
        padding: 24px 20px !important;
    }
}
</style>
</head>
<body style="margin: 0; padding: 0; width: 100%; background-color: #f8fafc; -webkit-text-size-adjust: none;">

{{-- Wrapper Table --}}
<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f8fafc; margin: 0; padding: 0; width: 100%;">
<tr>
<td align="center" style="background-color: #f8fafc;">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{{ $header ?? '' }}

{{-- Email Body --}}
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border: hidden !important; margin: 0; padding: 0; width: 100%;">
<table class="inner-body" align="center" width="560" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; margin: 0 auto; padding: 0; width: 560px; max-width: 100%;">
{{-- Body Content --}}
<tr>
<td class="content-cell" style="background-color: #ffffff; padding: 40px 48px; border-radius: 16px;">
{{ Illuminate\Mail\Markdown::parse($slot) }}

{{ $subcopy ?? '' }}
</td>
</tr>
</table>
</td>
</tr>

{{ $footer ?? '' }}
</table>
</td>
</tr>
</table>
</body>
</html>
