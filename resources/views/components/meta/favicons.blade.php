{{--
    Favicon & Theme Color Meta Tags

    Purpose:
    Renders the favicon link and theme-color meta tag in the <head> of all layouts.

    Favicon:
    - If a custom favicon is uploaded in Admin → Settings → Branding, it is used.
    - Otherwise, falls back to the default /favicon.ico file.

    Theme Color:
    - Uses the configured brand color from Admin → Settings → Branding.
    - Falls back to the config/default.php brand_color value.
--}}
@php
    $brandingSetting = null;
    $faviconUrl = null;
    $brandColor = config('default.brand_color', '#2563EB');

    // Only query the database if the app is installed
    if (config('default.app_is_installed')) {
        try {
            $brandingSetting = \App\Models\Setting::where('key', 'brand_color')->first();
            $faviconUrl = $brandingSetting?->getFirstMediaUrl('app_favicon');
        } catch (\Exception $e) {
            // Database not available yet — use defaults
        }
    }
@endphp
@if ($faviconUrl)
    @php
        $faviconExtension = pathinfo($brandingSetting->getFirstMedia('app_favicon')?->file_name ?? '', PATHINFO_EXTENSION);
        $faviconType = $faviconExtension === 'svg' ? 'image/svg+xml' : 'image/x-icon';
    @endphp
    <link rel="icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}">
@else
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
@endif
<meta name="theme-color" content="{{ $brandColor }}">
