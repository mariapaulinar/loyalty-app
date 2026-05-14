{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Campaign Email Preview Template

Purpose: Render email preview without mail component dependencies.
This template mirrors the actual email layout for accurate preview.
--}}

<!DOCTYPE html>
<html lang="{{ $locale ?? config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans('common.email_campaign.preview') }}</title>
    <style>
        /* Email-safe reset and base styles */
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #1e293b;
            background-color: #f1f5f9;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-body {
            padding: 32px 48px;
        }
        .email-content {
            color: #1e293b;
        }
        .email-content p {
            margin: 0 0 16px 0;
        }
        .email-content h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 16px 0;
            color: #0f172a;
        }
        .email-content h2 {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 12px 0;
            color: #0f172a;
        }
        .email-content h3 {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 12px 0;
            color: #0f172a;
        }
        .email-content ul, .email-content ol {
            margin: 0 0 16px 0;
            padding-left: 24px;
        }
        .email-content li {
            margin-bottom: 8px;
        }
        .email-content a {
            color: #6366f1;
            text-decoration: underline;
        }
        .email-content blockquote {
            margin: 16px 0;
            padding: 12px 20px;
            border-left: 4px solid #e2e8f0;
            background-color: #f8fafc;
            color: #475569;
        }
        .email-content hr {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 24px 0;
        }
        .email-footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 24px;
            margin-top: 32px;
            text-align: center;
        }
        .sender-name {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 16px;
        }
        .unsubscribe {
            color: #94a3b8;
            font-size: 12px;
        }
        .unsubscribe a {
            color: #94a3b8;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-body">
            {{-- Partner's Message Body --}}
            <div class="email-content">
                {!! $body !!}
            </div>

            {{-- Footer --}}
            <div class="email-footer">
                <p class="sender-name">
                    {{ $partner->getCampaignSenderName() }}
                </p>
                <p class="unsubscribe">
                    {{ trans('common.email_campaign.unsubscribe_text', [], $locale ?? config('app.locale')) }}
                    <span style="text-decoration: underline;">
                        {{ trans('common.email_campaign.unsubscribe', [], $locale ?? config('app.locale')) }}
                    </span>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

