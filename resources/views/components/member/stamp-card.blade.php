{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Premium Stamp Card Component - iOS 2030 Design

The physical stamp card, elevated. Jony Ive approved.
Designed to feel like an upgrade from premium business stamp cards.
Every detail matters.
--}}

@props(['stampCard', 'member' => null, 'showBalance' => true, 'links' => true, 'detailView' => false, 'showMemberData' => true, 'customLink' => null])

@php
$element_id = 'stamp-card-' . $stampCard->id;

// Check if member is authenticated or passed explicitly (for staff views)
$authCheck = $member ? true : auth('member')->check();

// Calculate progress using passed member or authenticated member
// Only fetch enrollment data if showMemberData is true (not in partner analytics)
$memberId = null;
if ($showMemberData) {
    if ($member) {
        $memberId = $member->id;
    } elseif (auth('member')->check()) {
        $memberId = auth('member')->id();
    }
}
$enrollment = $authCheck && $memberId && $showMemberData ? $stampCard->enrollments()->where('member_id', $memberId)->first() : null;
$currentStamps = $enrollment->current_stamps ?? 0;
$stampsRequired = $stampCard->stamps_required;
$stampsRemaining = max(0, $stampsRequired - $currentStamps);
$progressPercentage = $stampsRequired > 0 ? round(($currentStamps / $stampsRequired) * 100, 1) : 0;
$pendingRewards = $enrollment->pending_rewards ?? 0;
$completedCount = $enrollment->completed_count ?? 0;
$isNearComplete = $progressPercentage >= 80 && $stampsRemaining > 0;
$isComplete = $currentStamps >= $stampsRequired;

// Check expiration
$isExpired = $stampCard->valid_until && now()->isAfter($stampCard->valid_until);

// Styling (matching premium-card.blade.php structure)
$bgImage = $stampCard->getFirstMediaUrl('background');
$logo = $stampCard->getFirstMediaUrl('logo');
$bgColor = $stampCard->bg_color ?: '#1F2937';
$bgColorOpacity = $stampCard->bg_color_opacity ?? 75;
$textColor = $stampCard->text_color ?: '#FFFFFF';
$stampIcon = $stampCard->stamp_icon ?: '⭐';
$isEmoji = preg_match('/[^\x00-\x7F]/', $stampIcon);

// Stamp colors
$stampColor = $stampCard->stamp_color ?: '#10B981'; // Default emerald
$emptyStampColor = $stampCard->empty_stamp_color ?: '#9CA3AF'; // Default gray

// Calculate luminance for contrast (W3C formula)
if (!function_exists('hexToRgbStampCard')) {
    function hexToRgbStampCard($hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }
}

if (!function_exists('getLuminanceStampCard')) {
    function getLuminanceStampCard($r, $g, $b) {
        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;
        
        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);
        
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}

if (!function_exists('darkenColorStampCard')) {
    function darkenColorStampCard($r, $g, $b, $percentage = 20) {
        $factor = 1 - ($percentage / 100);
        return [
            'r' => max(0, (int)($r * $factor)),
            'g' => max(0, (int)($g * $factor)),
            'b' => max(0, (int)($b * $factor))
        ];
    }
}

if (!function_exists('lightenColorStampCard')) {
    function lightenColorStampCard($r, $g, $b, $percentage = 20) {
        $factor = $percentage / 100;
        return [
            'r' => min(255, (int)($r + (255 - $r) * $factor)),
            'g' => min(255, (int)($g + (255 - $g) * $factor)),
            'b' => min(255, (int)($b + (255 - $b) * $factor))
        ];
    }
}

// Calculate luminance for colors and card background
$emptyRgb = hexToRgbStampCard($emptyStampColor);
$emptyLuminance = getLuminanceStampCard($emptyRgb['r'], $emptyRgb['g'], $emptyRgb['b']);

$stampRgbCalc = hexToRgbStampCard($stampColor);
$stampLuminance = getLuminanceStampCard($stampRgbCalc['r'], $stampRgbCalc['g'], $stampRgbCalc['b']);

// Calculate card background luminance
$bgRgbForLuminance = hexToRgbStampCard($bgColor);
$bgLuminance = getLuminanceStampCard($bgRgbForLuminance['r'], $bgRgbForLuminance['g'], $bgRgbForLuminance['b']);

// Determine text colors based on contrast
$emptyTextIsDark = $emptyLuminance > 0.5; // If light background, use dark text
$filledTextIsDark = $stampLuminance > 0.5; // If light stamp, use dark text

// Next stamp border: darken/lighten empty stamp color based on card background
$cardBgIsLight = $bgLuminance > 0.5;
$nextStampBorderRgb = $cardBgIsLight 
    ? darkenColorStampCard($emptyRgb['r'], $emptyRgb['g'], $emptyRgb['b'], 25) 
    : lightenColorStampCard($emptyRgb['r'], $emptyRgb['g'], $emptyRgb['b'], 25);

// Determine Shadow Class based on TEXT color (not background - users set text color explicitly)
// Light text (white) needs dark shadow for contrast, dark text needs light shadow
$textRgb = hexToRgbStampCard($textColor);
$textLuminance = getLuminanceStampCard($textRgb['r'], $textRgb['g'], $textRgb['b']);
// Light text (luminance > 0.5) gets black shadow; dark text gets white shadow
$shadowClass = $textLuminance > 0.5 ? 'text-shadow-subtle-black' : 'text-shadow-subtle-white';

// Content
$contentTitle = $stampCard->title; // Public-facing title
$contentDescription = $stampCard->description; // CTA message

// Show balance  
$showBalance = true;

// URL for QR code (if member is available)
// Use explicitly passed $member first (for staff views), fallback to authenticated member
$memberIdentifier = $member?->unique_identifier ?? auth('member')->user()?->unique_identifier;
$urlToCollectStamp = ($authCheck && $memberId && $memberIdentifier) 
    ? route('staff.stamps.add.show', ['member_identifier' => $memberIdentifier, 'stamp_card_id' => $stampCard->id])
    : '';

$stampLogos = [];
if ($stampCard->card_type === 'event' && $enrollment && $showMemberData) {
    $stampLogos = app(\App\Services\StampService::class)
        ->getVisibleStampLogosForEventCard($enrollment);
}
@endphp

<div {{ $attributes->except('class') }} id="{{ $element_id }}"
    class="premium-card group relative w-full h-full max-w-[700px] mx-auto rounded-3xl overflow-hidden shadow-xl select-none @container 
           {{ $isComplete ? 'card-complete' : '' }}
           {{ $attributes->get('class') }}"
    @if($authCheck && $urlToCollectStamp)
        data-pwa-card
        data-pwa-card-id="stamp-{{ $stampCard->id }}"
        data-pwa-card-type="stamp"
        data-pwa-card-name="{{ $contentTitle }}"
        data-pwa-card-balance="{{ $currentStamps }} / {{ $stampsRequired }} {{ trans('common.stamps') }}"
        data-pwa-card-qr="{{ $urlToCollectStamp }}"
    @endif
>

    {{-- Clickable Wrapper --}}
    @if ($links ?? true)
        @if ($customLink)
            <a href="{{ $customLink }}" class="card-hit absolute inset-0 z-20"></a>
        @elseif ($detailView && !$authCheck)
            {{-- Detail view + not logged in: clicking card goes to login --}}
            <a href="{{ route('member.login') }}" class="card-hit absolute inset-0 z-20"></a>
        @elseif (!$detailView)
            {{-- List view: clicking card goes to card detail --}}
            <a href="{{ route('member.stamp-card', ['stamp_card_id' => $stampCard->id]) }}" class="card-hit absolute inset-0 z-20"></a>
        @endif
    @endif

    {{-- Background Layer - Pixel-perfect rendering --}}
    <div class="absolute inset-0 z-0 rounded-3xl overflow-hidden">
        {{-- Background Image with proper containment --}}
        @if ($bgImage)
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat rounded-3xl transform-gpu" 
             style="background-image: url('{{ $bgImage }}'); will-change: auto;"></div>
        @endif

        {{-- Colored Overlay - uses rgba for iOS Safari compatibility --}}
        <div class="absolute inset-0 bgColorOverlay_{{ $element_id }} rounded-3xl"></div>
    </div>

    {{-- Expired Ribbon --}}
    @if ($isExpired)
        <div class="absolute right-2 top-2 z-30 pointer-events-none">
            <span class="px-2 py-1 text-xs font-bold uppercase text-white bg-red-600 rounded shadow-lg">
                {{ trans('common.expired') }}
            </span>
        </div>
    @endif

    {{-- Card Content - New Layout: Title & Progress Top, Description & Stamps Below --}}
    <div class="relative z-10 h-full p-5 @[400px]:p-6 @[500px]:p-8 textColor_{{ $element_id }} flex flex-col gap-4">

        {{-- TOP ROW: Title/Logo (Left) + Progress Badge (Right) --}}
        <div class="flex items-start justify-between gap-4">
            {{-- Title/Logo --}}
            <div class="flex-1 min-w-0">
                @if ($logo)
                    <img class="h-8 @[400px]:h-10 @[500px]:h-12 object-contain object-left drop-shadow-lg" 
                         src="{{ $logo }}" 
                         alt="{{ parse_attr($contentTitle) }}">
                @else
                    <h3 class="text-lg @[400px]:text-xl @[500px]:text-2xl font-bold tracking-tight leading-tight {{ $shadowClass }}">
                        {{ html_entity_decode($contentTitle) }}
                    </h3>
                @endif
            </div>

            {{-- Progress Badge (Now inline with title) - numbers have inline shadow --}}
            <div class="flex-none text-right relative group/progress">
                <div class="text-[9px] @[400px]:text-[10px] font-bold uppercase tracking-widest opacity-60 mb-1 transition-all duration-300 group-hover/progress:opacity-80">
                    {{ trans('common.progress') }}
                </div>
                @if ($showMemberData && $authCheck && $showBalance)
                    {{-- Show member's current progress --}}
                    <div class="flex items-baseline justify-end gap-0.5 transition-all duration-300 group-hover/progress:scale-105">
                        <span class="text-2xl @[400px]:text-3xl @[500px]:text-4xl font-mono font-bold tabular-nums drop-shadow-sm 
                                     {{ $isNearComplete ? 'animate-pulse-gentle' : '' }}"
                              style="text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2), 0 0 20px rgba(255, 255, 255, 0.1);">{{ $currentStamps }}</span>
                        <span class="text-base @[400px]:text-lg @[500px]:text-xl opacity-60 font-light">/</span>
                        <span class="text-lg @[400px]:text-xl @[500px]:text-2xl opacity-80 tabular-nums font-medium">{{ $stampsRequired }}</span>
                    </div>
                    
                    {{-- Near completion excitement indicator --}}
                    @if ($isNearComplete)
                        <div class="absolute -inset-2 rounded-lg animate-pulse-gentle -z-10 progress-glow-{{ $element_id }}"></div>
                    @endif
                @else
                    {{-- Show only stamps required (no member data) --}}
                    <div class="flex items-baseline justify-end gap-0.5 transition-all duration-300 group-hover/progress:scale-105">
                        <span class="text-2xl @[400px]:text-3xl @[500px]:text-4xl font-mono font-bold tabular-nums drop-shadow-sm"
                              style="text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2), 0 0 20px rgba(255, 255, 255, 0.1);">{{ $stampsRequired }}</span>
                        <span class="text-lg @[400px]:text-xl @[500px]:text-2xl opacity-80 tabular-nums">{{ trans('common.stamps') }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- MIDDLE ROW: Description (Left) + Stamps (Right) --}}
        <div class="flex flex-1 gap-4 @[400px]:gap-6 @[500px]:gap-8">
            {{-- LEFT COLUMN: Description + Reward --}}
            <div class="flex-1 flex flex-col justify-between min-w-0">
                {{-- Description/CTA --}}
                @if ($contentDescription)
                    <div class="flex-1">
                        <p class="text-sm @[400px]:text-base @[500px]:text-lg font-normal leading-relaxed opacity-90 line-clamp-4">
                            {{ html_entity_decode($contentDescription) }}
                        </p>
                    </div>
                @endif

                {{-- Reward Info (Bottom of left column) --}}
                <div class="mt-auto pt-4 @[500px]:pt-5 border-t border-white/20">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="text-[9px] @[500px]:text-[10px] font-bold uppercase tracking-widest opacity-60 mb-0.5">
                                {{ trans('common.reward') }}
                            </div>
                            <div class="text-sm @[500px]:text-base font-semibold tracking-tight truncate {{ $shadowClass }}">
                                {{ html_entity_decode($stampCard->reward_title) }}
                            </div>
                        </div>
                        @if ($authCheck && $pendingRewards > 0)
                            <div class="flex-none reward-badge">
                                <div class="relative px-2.5 py-1.5 rounded-lg bg-linear-to-br from-green-500/30 to-emerald-500/30 backdrop-blur-sm border border-green-400/40 
                                            transition-all duration-300 hover:scale-110 hover:shadow-lg hover:shadow-emerald-500/30">
                                    {{-- Shine effect --}}
                                    <div class="absolute inset-0 rounded-lg bg-linear-to-br from-white/20 via-transparent to-transparent opacity-50"></div>
                                    
                                    <div class="relative flex items-center gap-1.5">
                                        <x-ui.icon icon="gift" class="w-4 h-4 drop-shadow-sm animate-bounce-subtle" />
                                        <span class="text-sm font-bold drop-shadow-sm font-mono tabular-nums">{{ $pendingRewards }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: Premium Stamp Grid — Debossed Slots + Elevated Stamps --}}
            @php
                // ═══════════════════════════════════════════════════════════
                // CURATED GRID CALCULATOR - Pixel-Perfect for 5, 6, 8, 9, 10, 12
                // "Fewer choices, each one perfect." — Jony Ive philosophy
                // ═══════════════════════════════════════════════════════════
                $displayStamps = $stampsRequired;
                
                // Original grid layouts - established and expected by users
                $gridCols = match($displayStamps) {
                    5 => 5,     // 5×1: Single elegant row
                    6 => 3,     // 3×2: Balanced rectangle  
                    8 => 4,     // 4×2: Wide rectangle
                    9 => 3,     // 3×3: Perfect square
                    10 => 5,    // 5×2: Industry standard
                    12 => 4,    // 4×3: Balanced rectangle
                    default => match(true) {
                        $displayStamps <= 3 => $displayStamps,
                        $displayStamps <= 4 => 2,
                        $displayStamps <= 6 => 3,
                        $displayStamps <= 8 => 4,
                        $displayStamps <= 10 => 5,
                        $displayStamps <= 12 => 4,
                        default => 5
                    }
                };
                
                // Calculate rows for dynamic sizing
                $gridRows = (int) ceil($displayStamps / $gridCols);
                
                // Container widths optimized for each curated layout
                $containerWidth = match($displayStamps) {
                    5 => 'w-[62%] @[400px]:w-[58%] @[500px]:w-[55%]',              // 5×1: Wide single row
                    6 => 'w-[48%] @[400px]:w-[46%] @[500px]:w-[48%]',              // 3×2: Medium
                    8 => 'w-[58%] @[400px]:w-[56%] @[500px]:w-[58%]',              // 4×2: Wide
                    9 => 'w-[38%] @[400px]:w-[38%] @[500px]:w-[42%]',              // 3×3: Compact square
                    10 => 'w-[58%] @[400px]:w-[56%] @[500px]:w-[58%]',             // 5×2: Wide
                    12 => 'w-[44%] @[400px]:w-[46%] @[500px]:w-[48%]',             // 4×3: Medium
                    default => match(true) {
                        $gridRows <= 2 => 'w-[58%] @[400px]:w-[56%] @[500px]:w-[58%]',
                        $gridRows == 3 => 'w-[38%] @[400px]:w-[38%] @[500px]:w-[42%]',
                        default => 'w-[44%] @[400px]:w-[46%] @[500px]:w-[48%]'
                    }
                };
                
                // Responsive gap sizes - optimized per layout
                $gapSize = match($displayStamps) {
                    5 => 'gap-2 @[400px]:gap-2.5 @[500px]:gap-3',                  // 5×1: Tighter for single row
                    6 => 'gap-3 @[400px]:gap-4 @[500px]:gap-5',                    // 3×2: Generous
                    8 => 'gap-3 @[400px]:gap-4 @[500px]:gap-5',                    // 4×2: Generous
                    9 => 'gap-2 @[400px]:gap-2.5 @[500px]:gap-3',                  // 3×3: Tighter for fit
                    10 => 'gap-3 @[400px]:gap-4 @[500px]:gap-5',                   // 5×2: Generous
                    12 => 'gap-2 @[400px]:gap-2.5 @[500px]:gap-3',                 // 4×3: Tighter for fit
                    default => match(true) {
                        $gridRows <= 2 => 'gap-3 @[400px]:gap-4 @[500px]:gap-5',
                        $gridRows == 3 => 'gap-2 @[400px]:gap-2.5 @[500px]:gap-3',
                        default => 'gap-2 @[400px]:gap-2.5 @[500px]:gap-3'
                    }
                };
                
                // Determine which stamps show milestone numbers
                // Grid-aware milestones for visual balance
                $milestones = match($displayStamps) {
                    5 => [5],                           // 5×1: Just the final
                    6 => [3, 6],                        // 3×2: End of each row
                    8 => [4, 8],                        // 4×2: End of each row
                    9 => [3, 6, 9],                     // 3×3: End of each row
                    10 => [5, 10],                      // 5×2: End of each row
                    12 => [4, 8, 12],                   // 4×3: End of each row
                    default => [5, 10, $displayStamps]  // Fallback
                };
            @endphp
            
            @php
                $totalSlots = $gridCols * $gridRows;
                $emptySlots = $totalSlots - $displayStamps;
            @endphp
            
            <div class="flex-none {{ $containerWidth }} flex items-end">
                <div class="w-full grid {{ $gapSize }}" style="grid-template-columns: repeat({{ $gridCols }}, minmax(0, 1fr));">
                    {{-- Empty placeholders to push stamps right --}}
                    @for ($i = 0; $i < $emptySlots; $i++)
                        <div class="aspect-square"></div>
                    @endfor
                    @for ($i = 1; $i <= min($displayStamps, 20); $i++)
                        @php
                            $isFilled = $i <= $currentStamps;
                            $isNext = $i == $currentStamps + 1;
                            $isNearComplete = $isNext && $currentStamps >= $stampsRequired - 2;
                            $isMilestone = in_array($i, $milestones);
                        @endphp
                        
                        {{-- ═══════════════════════════════════════════════════════════
                             PREMIUM STAMP — Debossed Slot / Elevated Fill
                             "Like pressing a gold seal into fine leather."
                             ═══════════════════════════════════════════════════════════ --}}
                        <div class="stamp-{{ $element_id }}-{{ $i }} aspect-square rounded-full flex items-center justify-center relative
                                    {{ $isFilled ? 'stamp-slot-filled-' . $element_id : 'stamp-slot-empty-' . $element_id }}
                                    {{ $isNext ? 'stamp-next' : '' }}
                                    {{ $isNearComplete ? 'stamp-near-complete' : '' }}
                                    transition-transform duration-200">
                            
                            @if ($isFilled)
                                {{-- ═══ FILLED STAMP — Elevated, sitting ON TOP ═══ --}}
                                
                                {{-- Base with stamp color --}}
                                <div class="absolute inset-0 rounded-full filled-stamp-bg-{{ $element_id }}"></div>
                                
                                {{-- Specular highlight - top-left catchlight --}}
                                <div class="absolute inset-0 rounded-full bg-gradient-to-br from-white/35 via-transparent to-transparent"></div>
                                
                                {{-- Inner rim light --}}
                                <div class="absolute inset-px rounded-full border border-white/15"></div>
                                
                                                                {{-- Icon/Emoji o logo del partner (event stamp cards) --}}
                                @if ($stampCard->card_type === 'event' && !empty($stampLogos[$i]))
                                    <img
                                        src="{{ $stampLogos[$i] }}"
                                        alt=""
                                        class="stamp-icon relative z-10 w-[70%] h-[70%] object-contain rounded-full drop-shadow-sm"
                                    />
                                @elseif ($isEmoji)
                                    <span class="stamp-icon relative z-10 text-lg @[400px]:text-xl @[500px]:text-3xl drop-shadow-sm">{{ $stampIcon }}</span>
                                @else
                                    <x-ui.icon :icon="$stampIcon" class="stamp-icon relative z-10 w-5 h-5 @[400px]:w-7 @[400px]:h-7 @[500px]:w-9 @[500px]:h-9 drop-shadow-sm" />
                                @endif
                                
                            @else
                                {{-- ═══ EMPTY STAMP — Debossed, pressed INTO the card ═══ --}}
                                
                                {{-- Debossed hole effect --}}
                                <div class="absolute inset-0 rounded-full empty-slot-debossed-{{ $element_id }}"></div>
                                
                                {{-- Subtle rim catch --}}
                                <div class="absolute inset-0 rounded-full empty-slot-rim-{{ $element_id }}"></div>
                                
                                {{-- Next stamp indicator (pulsing border) --}}
                                @if ($isNext)
                                    <div class="absolute -inset-0.5 rounded-full next-stamp-ring-{{ $element_id }} animate-pulse-subtle"></div>
                                @endif
                                
                                {{-- Milestone number (row-end positions) --}}
                                @if ($isMilestone)
                                    <span class="stamp-milestone relative z-10 text-[10px] @[400px]:text-xs @[500px]:text-sm font-mono font-bold tabular-nums">{{ $i }}</span>
                                @endif
                            @endif
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
    
    {{-- ═══════════════════════════════════════════════════════════════════
         SURFACE TEXTURE — Premium Cardstock Feel
         Noise texture + specular highlight for tactile materiality
         ═══════════════════════════════════════════════════════════════════ --}}
    
    {{-- Noise texture overlay --}}
    <div class="absolute inset-0 rounded-3xl pointer-events-none z-20 opacity-[0.025]"
         style="background-image: url(&quot;data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E&quot;);"></div>
    
    {{-- Specular highlight (light catching top edge) --}}
    <div class="absolute inset-0 rounded-3xl pointer-events-none z-20"
         style="background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.02) 30%, transparent 60%);"></div>
    
    {{-- Inner border glow for depth --}}
    <div class="absolute inset-0 rounded-3xl pointer-events-none z-20"
         style="box-shadow: inset 0 1px 0 0 rgba(255,255,255,0.1), inset 0 0 0 1px rgba(255,255,255,0.05);"></div>
</div>

@php
    // Convert hex to rgba for iOS Safari compatibility - Background color
    $hex = ltrim($bgColor, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $alpha = $bgColorOpacity / 100;
    
    // Convert stamp colors to RGB
    $stampRgb = hexToRgbStampCard($stampColor);
    $emptyStampRgb = hexToRgbStampCard($emptyStampColor);
    
    // Text colors based on contrast
    $emptyTextColor = $emptyTextIsDark ? 'rgba(0, 0, 0, 0.75)' : 'rgba(255, 255, 255, 0.9)';
    $emptyTextShadow = $emptyTextIsDark 
        ? '0 1px 2px rgba(255, 255, 255, 0.3)' 
        : '0 1px 3px rgba(0, 0, 0, 0.5), 0 0 10px rgba(0, 0, 0, 0.3)';
    
    $filledTextColor = $filledTextIsDark ? 'rgba(0, 0, 0, 0.8)' : 'rgba(255, 255, 255, 1)';
    $filledIconFilter = $filledTextIsDark
        ? 'drop-shadow(0 1px 3px rgba(255, 255, 255, 0.4))'
        : 'drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3)) drop-shadow(0 0 8px rgba(255, 255, 255, 0.3))';
@endphp
<style>
    #{{ $element_id }} {
        --card-accent-rgb: {{ $r }}, {{ $g }}, {{ $b }};
    }

    /* Background overlay with anti-aliasing optimization */
    .bgColorOverlay_{{ $element_id }} {
        background-color: rgba({{ $r }}, {{ $g }}, {{ $b }}, {{ $alpha }});
        /* Prevent scaling artifacts */
        backface-visibility: hidden;
        transform: translateZ(0);
        -webkit-font-smoothing: subpixel-antialiased;
    }

    .textColor_{{ $element_id }} {
        color: {{ $textColor }};
    }
    
    /* ═══════════════════════════════════════════════════════════════════
       STAMP SLOTS — Premium Debossed/Elevated System
       Inspired by Stampify: Empty slots pressed INTO the card,
       Filled stamps sitting ON TOP. Premium cardstock feel.
       
       Colors respect the editor's choices with tasteful transparency.
       ═══════════════════════════════════════════════════════════════════ */
    
    /* ─────────────────────────────────────────────────────────────────
       EMPTY SLOTS — Debossed Holes (pressed INTO the card)
       Uses empty_stamp_color from editor @ 50% opacity
       ───────────────────────────────────────────────────────────────── */
    .empty-slot-debossed-{{ $element_id }} {
        /* User's empty_stamp_color with tasteful transparency */
        background: rgba({{ $emptyStampRgb['r'] }}, {{ $emptyStampRgb['g'] }}, {{ $emptyStampRgb['b'] }}, 0.5);
        /* Deeper inset shadow for pronounced "pressed in" effect */
        box-shadow: 
            inset 0 3px 6px rgba(0, 0, 0, 0.4),
            inset 0 1px 2px rgba(0, 0, 0, 0.25),
            inset 0 -1px 0 rgba(255, 255, 255, 0.1);
    }
    
    .empty-slot-rim-{{ $element_id }} {
        /* Subtle rim catches light at the edge */
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
    
    /* Stamp slot hover - subtle lift */
    .stamp-slot-empty-{{ $element_id }}:hover {
        transform: scale(1.06);
    }
    
    /* Milestone number styling - uses card's text color @ 80% for strong legibility */
    #{{ $element_id }} .stamp-milestone {
        color: {{ $textColor }};
        opacity: 0.8;
        text-shadow: 
            0 1px 3px rgba(0, 0, 0, 0.4),
            0 0 6px rgba(0, 0, 0, 0.2);
    }
    
    /* ─────────────────────────────────────────────────────────────────
       FILLED STAMPS — Elevated (sitting ON TOP of the card)
       ───────────────────────────────────────────────────────────────── */
    .filled-stamp-bg-{{ $element_id }} {
        background: {{ $stampColor }};
    }
    
    .stamp-slot-filled-{{ $element_id }} {
        /* Elevated shadow - stamp sits ON TOP */
        box-shadow: 
            0 3px 6px rgba(0, 0, 0, 0.25),
            0 1px 2px rgba(0, 0, 0, 0.15);
    }
    
    /* Filled stamp icon/emoji color based on contrast */
    .stamp-slot-filled-{{ $element_id }} .stamp-icon {
        color: {{ $filledTextColor }};
    }
    
    /* Filled stamp hover - lift effect */
    .stamp-slot-filled-{{ $element_id }}:hover {
        transform: scale(1.1) translateY(-2px);
        box-shadow: 
            0 6px 12px rgba(0, 0, 0, 0.3),
            0 2px 4px rgba(0, 0, 0, 0.2);
    }
    
    .stamp-slot-filled-{{ $element_id }}:hover .stamp-icon {
        transform: scale(1.08) rotate(-3deg);
    }
    
    /* ─────────────────────────────────────────────────────────────────
       NEXT STAMP INDICATOR — Subtle Pulsing Ring
       ───────────────────────────────────────────────────────────────── */
    .next-stamp-ring-{{ $element_id }} {
        border: 2px solid rgba({{ $nextStampBorderRgb['r'] }}, {{ $nextStampBorderRgb['g'] }}, {{ $nextStampBorderRgb['b'] }}, 0.5);
        box-shadow: 0 0 8px rgba({{ $nextStampBorderRgb['r'] }}, {{ $nextStampBorderRgb['g'] }}, {{ $nextStampBorderRgb['b'] }}, 0.3);
    }
    
    /* Near completion - stronger pulse */
    .stamp-near-complete .next-stamp-ring-{{ $element_id }} {
        border-color: rgba({{ $nextStampBorderRgb['r'] }}, {{ $nextStampBorderRgb['g'] }}, {{ $nextStampBorderRgb['b'] }}, 0.7);
        box-shadow: 0 0 12px rgba({{ $nextStampBorderRgb['r'] }}, {{ $nextStampBorderRgb['g'] }}, {{ $nextStampBorderRgb['b'] }}, 0.4);
    }
    
    /* Progress badge near-complete glow - uses stamp color */
    .progress-glow-{{ $element_id }} {
        background-color: rgba({{ $stampRgb['r'] }}, {{ $stampRgb['g'] }}, {{ $stampRgb['b'] }}, 0.15);
    }
    
    /* ═══════════════════════════════════════════════════════════════════
       STAMP ANIMATIONS — Refined & Purposeful
       "Every animation should have a purpose. Every purpose deserves craft."
       ═══════════════════════════════════════════════════════════════════ */
    
    /* Filled stamp - Spring physics entrance with subtle rotation */
    @keyframes stampAppear {
        0% { 
            transform: scale(0) rotate(-20deg); 
            opacity: 0; 
        }
        60% { 
            transform: scale(1.1) rotate(3deg); 
        }
        100% { 
            transform: scale(1) rotate(var(--stamp-rotation, 0deg)); 
            opacity: 1; 
        }
    }
    
    .stamp-slot-filled-{{ $element_id }} {
        animation: stampAppear 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        --stamp-rotation: 0deg;
    }
    
    /* Stagger animation based on stamp number */
    @php
        for ($j = 1; $j <= 20; $j++) {
            $rotation = rand(-4, 4);
            echo ".stamp-{$element_id}-{$j}.stamp-slot-filled-{$element_id} { animation-delay: " . (($j - 1) * 0.05) . "s; --stamp-rotation: {$rotation}deg; }\n        ";
        }
    @endphp
    
    /* Subtle pulse animation for next stamp ring */
    @keyframes pulseSubtle {
        0%, 100% {
            opacity: 0.6;
            transform: scale(1);
        }
        50% {
            opacity: 1;
            transform: scale(1.04);
        }
    }
    
    .animate-pulse-subtle {
        animation: pulseSubtle 2.5s ease-in-out infinite;
    }
    
    /* Icon transition */
    .stamp-icon {
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    
    /* Accessibility: Respect prefers-reduced-motion */
    @media (prefers-reduced-motion: reduce) {
        .stamp-slot-filled-{{ $element_id }},
        .animate-pulse-subtle {
            animation: none;
        }
        
        .stamp-slot-filled-{{ $element_id }}:hover,
        .stamp-slot-empty-{{ $element_id }}:hover,
        .stamp-slot-filled-{{ $element_id }}:hover .stamp-icon {
            transform: none;
        }
    }
    
    /* Premium glow effect for progress bar */
    .shadow-glow-sm {
        box-shadow: 0 0 10px currentColor, 0 0 20px currentColor;
    }
    
    /* ═══════════════════════════════════════════════════════════════════
       CARD-LEVEL EFFECTS - Premium Interactions
       Note: Main hover animations handled by card-animations.css
       ═══════════════════════════════════════════════════════════════════ */
    
    /* Completion state - Celebration mode (uses stamp color) */
    #{{ $element_id }}.card-complete {
        position: relative;
    }
    
    #{{ $element_id }}.card-complete::before {
        content: '';
        position: absolute;
        inset: -2px;
        border-radius: 1.5rem;
        background: linear-gradient(
            135deg,
            rgba({{ $stampRgb['r'] }}, {{ $stampRgb['g'] }}, {{ $stampRgb['b'] }}, 0.4),
            rgba({{ $stampRgb['r'] }}, {{ $stampRgb['g'] }}, {{ $stampRgb['b'] }}, 0.25),
            rgba({{ $stampRgb['r'] }}, {{ $stampRgb['g'] }}, {{ $stampRgb['b'] }}, 0.4)
        );
        background-size: 200% 200%;
        animation: gradientShift 3s ease-in-out infinite;
        z-index: -1;
        filter: blur(8px);
    }
    
    @keyframes gradientShift {
        0%, 100% {
            background-position: 0% 50%;
        }
        50% {
            background-position: 100% 50%;
        }
    }
    
    /* Glass morphism effect on hover (optional, can enable for extra premium feel) */
    .premium-card:hover::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 40%;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.08) 0%, transparent 100%);
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.4s ease;
    }
    
    .premium-card:hover::after {
        opacity: 1;
    }
    
    /* ═══════════════════════════════════════════════════════════════════
       MICRO-INTERACTIONS - Reward Badge & Progress
       ═══════════════════════════════════════════════════════════════════ */
    
    /* Gentle pulse for near-completion excitement - VERY SUBTLE */
    @keyframes pulseGentle {
        0%, 100% {
            opacity: 0.9;
            transform: scale(1);
        }
        50% {
            opacity: 1;
            transform: scale(1.02);
        }
    }
    
    .animate-pulse-gentle {
        animation: pulseGentle 4s ease-in-out infinite;
    }
    
    /* Subtle bounce for reward badge */
    @keyframes bounceSubtle {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-2px);
        }
    }
    
    .animate-bounce-subtle {
        animation: bounceSubtle 2s ease-in-out infinite;
    }
    
    /* Reward badge hover glow */
    .reward-badge:hover {
        filter: brightness(1.1);
    }
</style>
