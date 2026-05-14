@if(config('default.app_demo'))
    <meta name="robots" content="noindex, nofollow" />
@endif
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes, shrink-to-fit=no">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="app-locale" content="{{ $locale }}">
<meta name="app-locale-slug" content="{{ $localeSlug }}">
<meta name="app-language" content="{{ $language }}">
<meta name="app-currency" content="{{ $currency }}">
<meta name="app-timezone" content="{{ $timezone }}">
<link rel="canonical" href="{{ url()->current() }}" />

@if (count($languages['all'] ?? []) > 1)
    @foreach ($languages['all'] as $language)
        @if (!$language['current'])
            <link rel="alternate" href="{{ $language['canonical'] }}" hreflang="{{ $language['localeSlug'] }}" />
        @endif
    @endforeach
@endif
