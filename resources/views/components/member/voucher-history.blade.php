{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Voucher History Component - Premium iOS 2030 Design
Timeline of voucher redemptions with beautiful iconography and smooth animations.
--}}

@props(['member' => null, 'voucher' => null, 'showNotes' => false, 'showStaff' => false])

@php
    // Get member from auth if not passed directly
    $member = $member ?? auth('member')->user();
    
    // Fetch voucher redemptions for this member
    $redemptions = collect();
    if ($member) {
        $query = \App\Models\VoucherRedemption::where('member_id', $member->id);
        
        // If specific voucher provided, filter by it
        if ($voucher) {
            $query->where('voucher_id', $voucher->id);
        }
        
        $redemptions = $query
            ->with(['voucher.club', 'staff'])
            ->orderBy('redeemed_at', 'desc')
            ->limit(50)
            ->get();
    }
@endphp

@if(!$member)
    <div class="flex flex-col items-center justify-center py-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center mb-4">
            <x-ui.icon icon="lock" class="w-8 h-8 text-secondary-400 dark:text-secondary-500" />
        </div>
        <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-2">{{ trans('common.login_required') }}</h3>
        <p class="text-secondary-500 dark:text-secondary-400 max-w-sm mb-6">{{ trans('common.login_to_view_history') }}</p>
        <a href="{{ route('member.login') }}" class="inline-flex items-center justify-center gap-2 bg-primary-600 hover:bg-primary-500 text-white rounded-xl px-5 py-2.5 text-sm font-medium shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
            {{ trans('common.log_in') }}
        </a>
    </div>
@elseif($redemptions->isEmpty())
    <div class="flex flex-col items-center justify-center py-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center mb-4">
            <x-ui.icon icon="ticket" class="w-8 h-8 text-secondary-400 dark:text-secondary-500" />
        </div>
        <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-2">{{ trans('common.no_vouchers_redeemed_yet') }}</h3>
        <p class="text-secondary-500 dark:text-secondary-400 max-w-sm">{{ trans('common.voucher_history_will_appear_here') }}</p>
    </div>
@else
    <div class="relative pl-8 sm:pl-10 space-y-8 before:absolute before:left-3 sm:before:left-3.5 before:top-2 before:bottom-2 before:w-px before:bg-secondary-300 dark:before:bg-secondary-700">
        @foreach($redemptions as $redemption)
            @php
                $voucher = $redemption->voucher;
                $isVoided = $redemption->is_voided;
                
                // Determine icon and colors based on voucher type
                $icon = 'ticket';
                $iconBg = 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400';
                $typeLabel = trans('common.' . $voucher->type);
                
                switch($voucher->type) {
                    case 'percentage':
                        $icon = 'percent';
                        break;
                    case 'fixed_amount':
                        $icon = 'dollar-sign';
                        break;
                    case 'free_product':
                        $icon = 'gift';
                        $iconBg = 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400';
                        break;
                    case 'free_shipping':
                        $icon = 'truck';
                        $iconBg = 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400';
                        break;
                    case 'bonus_points':
                        $icon = 'award';
                        $iconBg = 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400';
                        break;
                }
                
                if ($isVoided) {
                    $iconBg = 'bg-secondary-100 text-secondary-600 dark:bg-secondary-900/30 dark:text-secondary-400';
                }
            @endphp

            <div class="relative group">
                {{-- Timeline Dot/Icon --}}
                <div class="absolute -left-8 sm:-left-10 mt-1.5 w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center ring-4 ring-white dark:ring-secondary-900 {{ $iconBg }} transition-all duration-300 group-hover:scale-110">
                    <x-ui.icon :icon="$icon" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                </div>

                {{-- Content Card --}}
                <div class="flex flex-col sm:flex-row sm:items-start gap-2 sm:gap-4">
                    {{-- Main Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-4 mb-1">
                            <h4 class="text-base font-semibold text-secondary-900 dark:text-white truncate">
                                @php
                                    // Display benefit based on voucher type
                                    $benefitDisplay = '';
                                    switch($voucher->type) {
                                        case 'percentage':
                                            // Percentages stored as minor units: 1500 = 15%
                                            $benefitDisplay = rtrim(rtrim(number_format($voucher->value / 100, 2, '.', ''), '0'), '.') . '% ' . trans('common.off');
                                            break;
                                        case 'fixed_amount':
                                            $benefitDisplay = moneyFormat($voucher->value / 100, $voucher->currency) . ' ' . trans('common.off');
                                            break;
                                        case 'free_product':
                                            $benefitDisplay = trans('common.free') . ' ' . ($voucher->free_product_name ?: trans('common.product'));
                                            break;
                                        case 'free_shipping':
                                            $benefitDisplay = trans('common.free_shipping');
                                            break;
                                        case 'bonus_points':
                                            $benefitDisplay = '+' . $redemption->points_awarded . ' ' . trans('common.points');
                                            break;
                                        default:
                                            $benefitDisplay = $typeLabel;
                                    }
                                @endphp
                                {{ $benefitDisplay }}
                                @if($isVoided)
                                    <span class="inline-flex items-center text-xs px-2 py-0.5 rounded bg-secondary-100 dark:bg-secondary-900/30 text-secondary-600 dark:text-secondary-400 font-medium ml-2">
                                        {{ trans('common.voided') }}
                                    </span>
                                @endif
                            </h4>
                            
                            {{-- Mobile Badge --}}
                            <div class="sm:hidden">
                                @if(!$isVoided && $voucher->type !== 'bonus_points')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-bold text-purple-700 dark:text-purple-400 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                                        <x-ui.icon :icon="$icon" class="w-3 h-3" />
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Voucher Code --}}
                        <div class="mb-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-secondary-100 dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700">
                                <x-ui.icon icon="hash" class="w-3 h-3 text-secondary-500 dark:text-secondary-400" />
                                <span class="font-mono text-sm font-semibold text-secondary-700 dark:text-secondary-300">{{ $voucher->code }}</span>
                            </span>
                        </div>

                        {{-- Details --}}
                        <div class="text-sm text-secondary-500 dark:text-secondary-400 mb-2 space-y-1">
                            @if($showStaff && $redemption->original_amount)
                                <div class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-secondary-100 text-secondary-800 dark:bg-secondary-700 dark:text-secondary-300">
                                    {{ trans('common.purchase') }}: {{ moneyFormat($redemption->original_amount / 100, $voucher->currency) }}
                                </div>
                            @endif

                            @if($redemption->points_awarded && $voucher->type === 'bonus_points')
                                <div class="flex items-center gap-2 mt-2">
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800">
                                        <x-ui.icon icon="award" class="w-4 h-4 text-violet-600 dark:text-violet-400" />
                                        <span class="text-sm font-semibold text-violet-700 dark:text-violet-300">
                                            +{{ $redemption->points_awarded }} {{ trans('common.points') }}
                                        </span>
                                    </div>
                                    @if($voucher->reward_card_id)
                                        @php
                                            $rewardCard = \App\Models\Card::find($voucher->reward_card_id);
                                        @endphp
                                        @if($rewardCard)
                                            <a href="{{ route('member.card', ['card_id' => $rewardCard->id]) }}" 
                                               class="inline-flex items-center gap-1 text-xs text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 hover:underline transition-colors"
                                               target="_blank">
                                                <x-ui.icon icon="credit-card" class="w-3 h-3" />
                                                {{ $rewardCard->head }}
                                                <x-ui.icon icon="external-link" class="w-3 h-3" />
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Metadata Row --}}
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-secondary-400 dark:text-secondary-500">
                            <span class="flex items-center gap-1" title="{{ $redemption->redeemed_at }}">
                                <x-ui.icon icon="clock" class="w-3 h-3" />
                                {{ $redemption->redeemed_at?->diffForHumans() ?? trans('common.unknown') }}
                            </span>

                            @if($showStaff && $redemption->staff)
                                <span class="flex items-center gap-1">
                                    <x-ui.icon icon="user" class="w-3 h-3" />
                                    {{ $redemption->staff->name }}
                                </span>
                            @endif

                            @if($showStaff && $redemption->club)
                                <span class="flex items-center gap-1">
                                    <x-ui.icon icon="map-pin" class="w-3 h-3" />
                                    {{ $redemption->club->name }}
                                </span>
                            @endif

                            @if($redemption->order_reference)
                                <span class="flex items-center gap-1">
                                    <x-ui.icon icon="file-text" class="w-3 h-3" />
                                    {{ $redemption->order_reference }}
                                </span>
                            @endif
                        </div>

                        {{-- Notes (Staff/Partner/Admin Only) --}}
                        @if($showNotes && $redemption->void_reason)
                            <div class="mt-3 p-4 bg-amber-50/50 dark:bg-amber-900/10 rounded-xl border border-amber-200/50 dark:border-amber-800/50">
                                <div class="flex gap-2.5">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                            <x-ui.icon icon="sticky-note" class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-semibold text-amber-900 dark:text-amber-300 mb-1 uppercase tracking-wide">{{ trans('common.void_reason') }}</div>
                                        <div class="text-sm text-amber-800 dark:text-amber-200 leading-relaxed">{!! nl2br(e($redemption->void_reason)) !!}</div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Desktop Badge (Right Side) --}}
                    <div class="hidden sm:block text-right shrink-0">
                        @if(!$isVoided)
                            @if($voucher->type === 'bonus_points')
                                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 text-base font-bold text-amber-700 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/30 rounded-xl">
                                    <x-ui.icon icon="award" class="w-4 h-4" />
                                    +{{ $redemption->points_awarded }}
                                </div>
                            @else
                                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 text-base font-bold text-purple-700 dark:text-purple-400 bg-purple-100 dark:bg-purple-900/30 rounded-xl">
                                    <x-ui.icon :icon="$icon" class="w-4 h-4" />
                                </div>
                            @endif
                        @else
                            <div class="text-xs font-medium uppercase tracking-wider text-secondary-400 dark:text-secondary-500">
                                {{ trans('common.voided') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
