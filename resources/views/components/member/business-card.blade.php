{{--
    Reward Loyalty - Proprietary Software
    Copyright (c) 2025 NowSquare. All rights reserved.
    See LICENSE file for terms.

    Business Card — MASTERCLASS DESIGN
    ═══════════════════════════════════════════════════════════════════════════════
    A premium business card component that displays partner business information
    below loyalty cards, stamp cards, and vouchers.

    Design Philosophy:
    - Linear-level polish: Every pixel intentional
    - Stripe-grade information hierarchy
    - Revolut's ambient brand color treatment
    - Apple's restraint: No unnecessary elements
    
    Brand Color as Light:
    The brand color is used as ambient light, not paint.
    It tints, glows, and illuminates — never dominates.
--}}

@props([
    'partner' => null,
])

@php
    // Early return if no partner or no business profile
    if (!$partner || !$partner->hasBusinessProfile()) {
        return;
    }

    $brandColor = $partner->brand_color ?? '#2563EB';
    
    // Convert hex to RGB for opacity effects
    $hexToRgb = function($hex) {
        $hex = ltrim($hex, '#');
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    };
    $brandRgb = $hexToRgb($brandColor);
    $brandRgbString = "{$brandRgb['r']}, {$brandRgb['g']}, {$brandRgb['b']}";
    
    $socialLinks = $partner->getActiveSocialLinks();
    $hasLocation = $partner->getFullAddress() || $partner->maps_url;
    $hasContact = $partner->website || count($socialLinks) > 0;
    $hasHours = !empty($partner->opening_hours);
    $hasInfo = $partner->description || $hasLocation || $hasHours;
    
    // Determine if open (only if hours are configured)
    $isOpen = $hasHours ? $partner->isCurrentlyOpen() : null;
    $todaysHours = $hasHours ? $partner->getTodaysHours() : null;
@endphp

{{-- Only render if partner has a business profile --}}
@if($partner && $partner->hasBusinessProfile())
<div class="group relative overflow-hidden rounded-2xl border border-secondary-200/60 dark:border-white/[0.06] bg-white dark:bg-secondary-900/80 shadow-sm hover:shadow-lg transition-all duration-500 hover:border-secondary-300 dark:hover:border-white/10"
     x-data="{ expanded: false }"
     {{ $attributes }}>
    
    {{-- Ambient Brand Glow (subtle, appears on hover) --}}
    <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"
         style="background: radial-gradient(ellipse at top, rgba({{ $brandRgbString }}, 0.03) 0%, transparent 70%);"></div>
    
    {{-- Main Content --}}
    <div class="relative p-5">
        
        {{-- Header Section --}}
        <div class="flex items-start gap-4">
            
            {{-- Logo/Avatar --}}
            @if($partner->avatar)
                <div class="w-14 h-14 rounded-xl overflow-hidden flex-shrink-0 ring-1 ring-secondary-200/60 dark:ring-white/10 shadow-lg"
                     style="box-shadow: 0 4px 20px -4px rgba({{ $brandRgbString }}, 0.15);">
                    <img src="{{ $partner->getAvatarUrl('medium') }}" 
                         alt="{{ $partner->business_name }}"
                         class="w-full h-full object-cover">
                </div>
            @else
                {{-- Gradient Initial Badge --}}
                <div class="w-14 h-14 rounded-xl flex items-center justify-center flex-shrink-0 ring-1 ring-white/20 shadow-lg"
                     style="background: linear-gradient(135deg, {{ $brandColor }} 0%, {{ $brandColor }}cc 100%);
                            box-shadow: 0 4px 20px -4px rgba({{ $brandRgbString }}, 0.4);">
                    <span class="text-2xl font-semibold text-white drop-shadow-sm">
                        {{ mb_substr($partner->business_name, 0, 1) }}
                    </span>
                </div>
            @endif
            
            {{-- Title + Status --}}
            <div class="flex-1 min-w-0">
                {{-- Business Name --}}
                <h3 class="text-base font-semibold text-secondary-900 dark:text-white truncate leading-tight">
                    {{ $partner->business_name }}
                </h3>
                
                {{-- Tagline --}}
                @if($partner->tagline)
                    <p class="text-sm text-secondary-500 dark:text-secondary-400 truncate mt-0.5 leading-snug">
                        {{ $partner->tagline }}
                    </p>
                @endif
                
                {{-- Status Badge (Open/Closed) --}}
                @if($isOpen !== null)
                    <div class="inline-flex items-center gap-1.5 mt-2">
                        {{-- LED Indicator --}}
                        <span class="relative flex h-2 w-2">
                            @if($isOpen)
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                                      style="background: {{ $brandColor }};"></span>
                            @endif
                            <span class="relative inline-flex rounded-full h-2 w-2"
                                  style="background: {{ $isOpen ? $brandColor : '#EF4444' }};"></span>
                        </span>
                        
                        {{-- Status Text --}}
                        <span class="text-xs font-medium"
                              style="color: {{ $isOpen ? $brandColor : '#EF4444' }};">
                            {{ $isOpen ? trans('common.open_now') : trans('common.closed_now') }}
                        </span>
                        
                        {{-- Today's Hours --}}
                        @if($todaysHours && $isOpen)
                            <span class="text-xs text-secondary-400 dark:text-secondary-500 ml-1">
                                {{ $todaysHours }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>
            
            {{-- Expand/Collapse Button (if has expandable content) --}}
            @if($hasInfo || $hasContact)
                <button @click="expanded = !expanded"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-secondary-400 hover:text-secondary-600 dark:text-secondary-500 dark:hover:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-white/5 transition-all duration-200 flex-shrink-0"
                        :class="{ 'bg-secondary-100 dark:bg-white/5': expanded }">
                    <x-ui.icon icon="chevron-down" 
                               class="w-4 h-4 transition-transform duration-300" 
                               x-bind:class="{ 'rotate-180': expanded }" />
                </button>
            @endif
        </div>

        {{-- Quick Actions (Always Visible) --}}
        @if($partner->maps_url || $partner->website)
            <div class="flex items-center gap-2 mt-4">
                @if($partner->maps_url)
                    <a href="{{ $partner->maps_url }}" 
                       target="_blank" 
                       rel="noopener"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 bg-secondary-100 dark:bg-white/5 text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-white/10 border border-transparent hover:border-secondary-300/50 dark:hover:border-white/10"
                       onmouseover="this.style.background = 'rgba({{ $brandRgbString }}, 0.1)'; this.style.color = '{{ $brandColor }}';"
                       onmouseout="this.style.background = ''; this.style.color = '';">
                        <x-ui.icon icon="map-pin" class="w-3.5 h-3.5" />
                        {{ trans('common.get_directions') }}
                    </a>
                @endif
                
                @if($partner->website)
                    <a href="{{ $partner->website }}" 
                       target="_blank" 
                       rel="noopener"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 bg-secondary-100 dark:bg-white/5 text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-white/10 border border-transparent hover:border-secondary-300/50 dark:hover:border-white/10"
                       onmouseover="this.style.background = 'rgba({{ $brandRgbString }}, 0.1)'; this.style.color = '{{ $brandColor }}';"
                       onmouseout="this.style.background = ''; this.style.color = '';">
                        <x-ui.icon icon="globe" class="w-3.5 h-3.5" />
                        {{ trans('common.website') }}
                    </a>
                @endif
                
                {{-- Social Icons --}}
                @if(count($socialLinks) > 0)
                    <div class="flex items-center gap-1 ml-auto">
                        @foreach($socialLinks as $platform => $url)
                            <a href="{{ $url }}" 
                               target="_blank" 
                               rel="noopener"
                               class="w-8 h-8 rounded-lg flex items-center justify-center text-secondary-400 dark:text-secondary-500 hover:text-secondary-600 dark:hover:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-white/5 transition-all duration-200"
                               title="{{ ucfirst($platform) }}"
                               onmouseover="this.style.color = '{{ $brandColor }}';"
                               onmouseout="this.style.color = '';">
                                @switch($platform)
                                    @case('instagram')
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                        @break
                                    @case('tiktok')
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z"/></svg>
                                        @break
                                    @case('facebook')
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                        @break
                                    @case('twitter')
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                        @break
                                @endswitch
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- Expandable Content --}}
        @if($hasInfo || ($hasContact && !$partner->maps_url && !$partner->website))
            <div x-show="expanded" 
                 x-collapse 
                 x-cloak
                 class="overflow-hidden">
                <div class="pt-4 mt-4 border-t border-secondary-100 dark:border-white/5 space-y-4">
                    
                    {{-- About Section --}}
                    @if($partner->description)
                        <div>
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-secondary-400 dark:text-secondary-500 mb-2">
                                {{ trans('common.about') }}
                            </h4>
                            <p class="text-sm text-secondary-600 dark:text-secondary-400 leading-relaxed">
                                {{ $partner->description }}
                            </p>
                        </div>
                    @endif
                    
                    {{-- Address --}}
                    @if($partner->getFullAddress())
                        <div>
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-secondary-400 dark:text-secondary-500 mb-2">
                                {{ trans('common.address') }}
                            </h4>
                            <p class="text-sm text-secondary-600 dark:text-secondary-400">
                                {{ $partner->getFullAddress() }}
                            </p>
                        </div>
                    @endif
                    
                    {{-- Opening Hours --}}
                    @if($hasHours)
                        <div>
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-secondary-400 dark:text-secondary-500 mb-2">
                                {{ trans('common.opening_hours') }}
                            </h4>
                            <div class="space-y-1">
                                @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                    @php
                                        $hours = $partner->opening_hours[$day] ?? null;
                                        $isClosed = isset($hours['closed']) && $hours['closed'];
                                        $timezone = $partner->time_zone ?? config('app.timezone', 'UTC');
                                        $isToday = strtolower(now()->setTimezone($timezone)->format('l')) === $day;
                                    @endphp
                                    <div class="flex justify-between text-sm {{ $isToday ? 'text-secondary-900 dark:text-white font-medium' : 'text-secondary-500 dark:text-secondary-400' }}">
                                        <span class="flex items-center gap-2">
                                            {{ trans('common.' . $day) }}
                                            @if($isToday)
                                                <span class="w-1.5 h-1.5 rounded-full" style="background: {{ $brandColor }};"></span>
                                            @endif
                                        </span>
                                        <span class="{{ $isClosed ? 'text-red-500/70' : '' }}">
                                            @if($hours)
                                                {{ $isClosed ? trans('common.closed') : ($hours['open'] . ' – ' . $hours['close']) }}
                                            @else
                                                —
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endif
