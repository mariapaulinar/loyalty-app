{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2026 NowSquare. All rights reserved.
See LICENSE file for terms.

Minimal Error Page — Zero-Dependency Fallback

Purpose:
Ultra-minimal error page that renders without ANY external dependencies.
No Vite, no CSS framework, no JavaScript, no fonts, no icons.
Used when the application is in a state where nothing else can load
(e.g., failed deployment, asset compilation error, misconfigured server).

Design:
- Pure inline CSS, system font stack
- Centered vertically and horizontally
- Large typographic error code
- Clean, professional appearance
- Dark mode via prefers-color-scheme
- Impression: this team knows what they're doing
--}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #fafafa;
            color: #1a1a1a;
            padding: 2rem;
        }

        .error-container {
            text-align: center;
            max-width: 400px;
            width: 100%;
        }

        .error-code {
            font-size: clamp(100px, 20vw, 160px);
            font-weight: 700;
            line-height: 1;
            letter-spacing: -0.02em;
            color: #e5e5e5;
            font-variant-numeric: tabular-nums;
            user-select: none;
        }

        .error-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: -0.5rem;
            margin-bottom: 0.75rem;
            letter-spacing: -0.01em;
        }

        .error-message {
            font-size: 0.875rem;
            color: #737373;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        @media (prefers-color-scheme: dark) {
            body { background-color: #0a0a0a; color: #fafafa; }
            .error-code { color: #262626; }
            .error-message { color: #a3a3a3; }
        }
    </style>
</head>

<body>
    <div class="error-container">
        <p class="error-code">@yield('code')</p>
        <h1 class="error-title">@yield('title')</h1>
        <p class="error-message">@yield('message')</p>
    </div>
</body>

</html>
