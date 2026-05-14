{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Premium Notification Email Template
  Used by Laravel's notification system for password resets, etc.
  
  Design Philosophy:
  - Revolut-grade minimal aesthetic
  - Warm, human-centered copy
  - All colors explicitly defined (dark mode safe)
--}}
<x-mail::message>
{{-- Greeting - Warm and personal --}}
@if (! empty($greeting))
<h1 style="color: #0f172a; font-size: 26px; font-weight: 700; margin: 0 0 12px 0; text-align: center; letter-spacing: -0.02em; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{{ $greeting }}
</h1>
@else
@if ($level === 'error')
<h1 style="color: #dc2626; font-size: 26px; font-weight: 700; margin: 0 0 12px 0; text-align: center; letter-spacing: -0.02em; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
@lang('Something went wrong')
</h1>
@else
<h1 style="color: #0f172a; font-size: 26px; font-weight: 700; margin: 0 0 12px 0; text-align: center; letter-spacing: -0.02em; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
@lang('Hello there')
</h1>
@endif
@endif

{{-- Intro Lines - Clear and friendly --}}
@foreach ($introLines as $line)
<p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 20px 0; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{!! $line !!}
</p>
@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
<p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 20px 0; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
{!! $line !!}
</p>
@endforeach

{{-- Warm Sign-off --}}
<p style="color: #64748b; font-size: 15px; line-height: 1.6; margin: 28px 0 0 0; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
@if (! empty($salutation))
{!! $salutation !!}<br>
<strong style="color: #334155;">{{ trans('common.the_team_at', ['app' => config('default.app_name')]) }}</strong>
@else
{{ trans('common.salutation') }}<br>
<strong style="color: #334155;">{{ trans('common.the_team_at', ['app' => config('default.app_name')]) }}</strong>
@endif
</p>
</x-mail::message>
