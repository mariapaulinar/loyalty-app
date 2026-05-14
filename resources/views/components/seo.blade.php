{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

SEO Meta Tags Component
=======================

A comprehensive SEO component that handles:
- Proper title construction (decoded entities, no duplication)
- Meta description
- Open Graph tags (og:title, og:description, og:image, og:url, og:type, og:site_name)
- Twitter Card tags
- Canonical URLs

Usage in views:
---------------
Basic (just title):
@section('page_title', $reward->title)

With description:
@section('page_title', $reward->title)
@section('meta_description', $reward->description)

With OG image:
@section('page_title', $reward->title)
@section('meta_description', $reward->description)
@section('meta_image', $reward->image1)

Props override sections:
<x-seo
    :title="$reward->title"
    :description="$reward->description"
    :image="$reward->image1"
    type="product"
/>
--}}

@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'type' => 'website',
    'siteName' => null,
])

@php
    use Illuminate\Support\Str;
    
    // ═══════════════════════════════════════════════════════════════════════════
    // TITLE CONSTRUCTION
    // ═══════════════════════════════════════════════════════════════════════════
    // Priority: prop > section > default
    // Handles: HTML entity decoding, duplicate app name prevention
    
    $appName = config('default.app_name');
    $delimiter = config('default.page_title_delimiter', ' - ');
    $siteName = $siteName ?? $appName;
    
    // Get the page-specific title from prop or section
    $pageTitle = $title ?? trim(View::yieldContent('page_title', ''));
    
    // Decode HTML entities (fixes &#039; → ')
    $pageTitle = html_entity_decode($pageTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Intelligent duplicate prevention:
    // If the page title already ends with the app name, don't add it again
    $pageTitle = trim($pageTitle);
    $appNamePattern = preg_quote($delimiter . $appName, '/');
    
    if ($pageTitle === '' || $pageTitle === $appName) {
        // No specific title or just the app name - use app name alone
        $fullTitle = $appName;
    } elseif (preg_match('/' . $appNamePattern . '$/i', $pageTitle)) {
        // Title already ends with delimiter + app name - use as-is
        $fullTitle = $pageTitle;
    } else {
        // Normal case: add app name suffix
        $fullTitle = $pageTitle . $delimiter . $appName;
    }
    
    // Clean title for OG (without app name suffix for cleaner social sharing)
    $ogTitle = ($pageTitle === '' || $pageTitle === $appName) 
        ? $appName 
        : preg_replace('/' . $appNamePattern . '$/i', '', $pageTitle);
    $ogTitle = trim($ogTitle) ?: $appName;
    
    // ═══════════════════════════════════════════════════════════════════════════
    // DESCRIPTION
    // ═══════════════════════════════════════════════════════════════════════════
    // Priority: prop > section > default
    
    $metaDescription = $description ?? trim(View::yieldContent('meta_description', ''));
    $metaDescription = html_entity_decode($metaDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Strip HTML tags and limit length for meta description
    $metaDescription = strip_tags($metaDescription);
    $metaDescription = Str::limit($metaDescription, 160, '...');
    
    // Fallback to app description if no specific description
    if (empty($metaDescription)) {
        $metaDescription = config('default.pwa_description', trans('common.app_meta_description', ['app' => $appName]));
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // IMAGE
    // ═══════════════════════════════════════════════════════════════════════════
    // Priority: prop > section > default
    
    $metaImage = $image ?? trim(View::yieldContent('meta_image', ''));
    
    // Ensure absolute URL for social sharing
    if ($metaImage && !str_starts_with($metaImage, 'http')) {
        $metaImage = url($metaImage);
    }
    
    // Fallback to app logo (no default image if none configured)
    // OG tags work fine without an image, better than a broken image
    if (empty($metaImage) && config('default.app_logo')) {
        $metaImage = url(config('default.app_logo'));
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // URL
    // ═══════════════════════════════════════════════════════════════════════════
    
    $canonicalUrl = url()->current();
    
    // ═══════════════════════════════════════════════════════════════════════════
    // TYPE
    // ═══════════════════════════════════════════════════════════════════════════
    // Common types: website, article, product, profile
    
    $ogType = trim(View::yieldContent('meta_type', $type));
    
    // ═══════════════════════════════════════════════════════════════════════════
    // SAFE ATTRIBUTE ESCAPING
    // ═══════════════════════════════════════════════════════════════════════════
    // Blade's {{ }} escapes apostrophes to &#039; which looks ugly in titles.
    // For double-quoted HTML attributes, apostrophes are safe and don't need escaping.
    // We only need to escape: < > & " (not single quotes)
    
    $escapeAttr = fn($value) => htmlspecialchars($value, ENT_COMPAT | ENT_HTML5, 'UTF-8');
@endphp

{{-- Page Title (using ENT_COMPAT to preserve apostrophes) --}}
<title>{!! $escapeAttr($fullTitle) !!}</title>

{{-- Meta Description --}}
@if($metaDescription)
<meta name="description" content="{!! $escapeAttr($metaDescription) !!}">
@endif

{{-- Open Graph Tags --}}
<meta property="og:title" content="{!! $escapeAttr($ogTitle) !!}">
<meta property="og:site_name" content="{!! $escapeAttr($siteName) !!}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:type" content="{{ $ogType }}">
@if($metaDescription)
<meta property="og:description" content="{!! $escapeAttr($metaDescription) !!}">
@endif
@if($metaImage)
<meta property="og:image" content="{{ $metaImage }}">
@endif

{{-- Twitter Card Tags --}}
<meta name="twitter:card" content="{{ $metaImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{!! $escapeAttr($ogTitle) !!}">
@if($metaDescription)
<meta name="twitter:description" content="{!! $escapeAttr($metaDescription) !!}">
@endif
@if($metaImage)
<meta name="twitter:image" content="{{ $metaImage }}">
@endif

{{-- Stack for additional meta tags from child views --}}
@stack('seo')

