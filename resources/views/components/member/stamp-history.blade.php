{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Stamp History Component - Premium iOS 2030 Design
Timeline of stamp transactions with beautiful iconography and smooth animations.
--}}

@props(['stampCard', 'member' => null, 'enrollment' => null, 'showNotes' => false, 'showAttachments' => false, 'showStaff' => false])

@php
    // Get member from enrollment if not passed directly
    $member = $member ?? ($enrollment ? $enrollment->member : auth('member')->user());
    
    // Fetch stamp transactions for this card and member
    $transactions = collect();
    if ($member && $stampCard) {
        $transactions = \App\Models\StampTransaction::where('stamp_card_id', $stampCard->id)
            ->where('member_id', $member->id)
            ->with('staff')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }
    
    // Get stamp icon
    $stampIcon = $stampCard->stamp_icon ?? '☕';
    $isEmojiIcon = preg_match('/[^\x00-\x7F]/', $stampIcon);
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
@elseif($transactions->isEmpty())
    <div class="flex flex-col items-center justify-center py-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center mb-4">
            @if($isEmojiIcon)
                <span class="text-3xl opacity-60">{{ $stampIcon }}</span>
            @else
                <x-ui.icon :icon="$stampIcon" class="w-8 h-8 text-secondary-400 dark:text-secondary-500" />
            @endif
        </div>
        <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-2">{{ trans('common.no_stamps_earned_yet') }}</h3>
        <p class="text-secondary-500 dark:text-secondary-400 max-w-sm">{{ trans('common.stamp_history_will_appear_here') }}</p>
    </div>
@else
    <div class="relative pl-8 sm:pl-10 space-y-8 before:absolute before:left-3 sm:before:left-3.5 before:top-2 before:bottom-2 before:w-px before:bg-secondary-300 dark:before:bg-secondary-700">
        @foreach($transactions as $transaction)
            @php
                $isPositive = $transaction->stamps > 0;
                
                // Determine icon and colors based on event type
                $icon = 'circle';
                $iconBg = 'bg-secondary-100 text-secondary-500 dark:bg-secondary-800 dark:text-secondary-400';
                $eventLabel = trans('common.transaction');
                
                switch($transaction->event) {
                    case \App\Models\StampTransaction::EVENT_STAMP_EARNED:
                        $icon = $isEmojiIcon ? null : $stampIcon;
                        $iconBg = 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400';
                        $eventLabel = trans('common.stamps_earned');
                        break;
                    case \App\Models\StampTransaction::EVENT_STAMPS_BONUS:
                        $icon = 'star';
                        $iconBg = 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400';
                        $eventLabel = trans('common.bonus_stamps');
                        break;
                    case \App\Models\StampTransaction::EVENT_CARD_COMPLETED:
                        $icon = 'check-circle-2';
                        $iconBg = 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400';
                        $eventLabel = trans('common.card_completed');
                        break;
                    case \App\Models\StampTransaction::EVENT_REWARD_REDEEMED:
                        $icon = 'gift';
                        $iconBg = 'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400';
                        $eventLabel = trans('common.reward_redeemed');
                        break;
                    case \App\Models\StampTransaction::EVENT_STAMPS_ADJUSTED:
                        $icon = 'edit-3';
                        $iconBg = 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400';
                        $eventLabel = trans('common.stamps_adjusted');
                        break;
                    case \App\Models\StampTransaction::EVENT_STAMPS_VOIDED:
                        $icon = 'x-circle';
                        $iconBg = 'bg-secondary-100 text-secondary-600 dark:bg-secondary-900/30 dark:text-secondary-400';
                        $eventLabel = trans('common.stamps_voided');
                        break;
                    case \App\Models\StampTransaction::EVENT_STAMPS_EXPIRED:
                        $icon = 'clock';
                        $iconBg = 'bg-secondary-100 text-secondary-600 dark:bg-secondary-900/30 dark:text-secondary-400';
                        $eventLabel = trans('common.stamps_expired');
                        break;
                    case 'points_rewarded':
                        $icon = 'award';
                        $iconBg = 'bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400';
                        $eventLabel = trans('common.points_rewarded');
                        break;
                }
            @endphp

            <div class="relative group">
                {{-- Timeline Dot/Icon --}}
                <div class="absolute -left-8 sm:-left-10 mt-1.5 w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center ring-4 ring-white dark:ring-secondary-900 {{ $iconBg }} transition-all duration-300 group-hover:scale-110">
                    @if($transaction->event === \App\Models\StampTransaction::EVENT_STAMP_EARNED && $isEmojiIcon)
                        <span class="text-sm">{{ $stampIcon }}</span>
                    @else
                        <x-ui.icon :icon="$icon" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                    @endif
                </div>

                {{-- Content Card --}}
                <div class="flex flex-col sm:flex-row sm:items-start gap-2 sm:gap-4">
                    {{-- Main Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-4 mb-1">
                            <h4 class="text-base font-semibold text-secondary-900 dark:text-white truncate">
                                {{ $eventLabel }}
                            </h4>
                            
                            {{-- Mobile Stamp Badge --}}
                            <div class="sm:hidden">
                                @if($isPositive)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                                        +{{ abs($transaction->stamps) }}
                                        @if($isEmojiIcon)
                                            <span class="text-[10px]">{{ $stampIcon }}</span>
                                        @else
                                            <x-ui.icon :icon="$stampIcon" class="w-3 h-3" />
                                        @endif
                                    </span>
                                @elseif($transaction->stamps < 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-bold text-rose-700 dark:text-rose-400 bg-rose-100 dark:bg-rose-900/30 rounded-lg">
                                        {{ $transaction->stamps }}
                                        @if($isEmojiIcon)
                                            <span class="text-[10px]">{{ $stampIcon }}</span>
                                        @else
                                            <x-ui.icon :icon="$stampIcon" class="w-3 h-3" />
                                        @endif
                                    </span>
                                @elseif($transaction->event === \App\Models\StampTransaction::EVENT_REWARD_REDEEMED)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold text-rose-700 dark:text-rose-400 bg-rose-100 dark:bg-rose-900/30 rounded-lg">
                                        <x-ui.icon icon="gift" class="w-3 h-3" />
                                        {{ trans('common.claimed') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Details --}}
                        <div class="text-sm text-secondary-500 dark:text-secondary-400 mb-2 space-y-1">
                            @if($showStaff && $transaction->purchase_amount)
                                <div class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-secondary-100 text-secondary-800 dark:bg-secondary-700 dark:text-secondary-300">
                                    {{ moneyFormat((float) $transaction->purchase_amount, $stampCard->currency) }}
                                </div>
                            @endif

                            @if($transaction->event === 'points_rewarded' && isset($transaction->meta['points_awarded']))
                                <div class="flex items-center gap-2 mt-2">
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800">
                                        <x-ui.icon icon="award" class="w-4 h-4 text-violet-600 dark:text-violet-400" />
                                        <span class="text-sm font-semibold text-violet-700 dark:text-violet-300">
                                            +{{ $transaction->meta['points_awarded'] }} {{ trans('common.points') }}
                                        </span>
                                    </div>
                                    @if(isset($transaction->meta['reward_card_id']))
                                        @php
                                            $rewardCard = \App\Models\Card::find($transaction->meta['reward_card_id']);
                                        @endphp
                                        @if($rewardCard)
                                            <a href="{{ route('member.card', ['card_id' => $rewardCard->id]) }}" 
                                               class="inline-flex items-center gap-1 text-xs text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 hover:underline transition-colors"
                                               target="_blank">
                                                <x-ui.icon icon="credit-card" class="w-3 h-3" />
                                                {{ $transaction->meta['reward_card_title'] ?? $rewardCard->head }}
                                                <x-ui.icon icon="external-link" class="w-3 h-3" />
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            @endif
                            
                            @if($transaction->stamps_before !== null && $transaction->stamps_after !== null && $transaction->event !== 'points_rewarded')
                                <div class="flex items-center gap-1.5 text-xs">
                                    <span class="text-secondary-400 dark:text-secondary-500">{{ $transaction->stamps_before }}/{{ $stampCard->stamps_required }}</span>
                                    <x-ui.icon icon="arrow-right" class="w-3 h-3 text-secondary-400" />
                                    <span class="font-semibold text-secondary-700 dark:text-secondary-300">{{ $transaction->stamps_after }}/{{ $stampCard->stamps_required }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Metadata Row --}}
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-secondary-400 dark:text-secondary-500">
                            <span class="flex items-center gap-1" title="{{ $transaction->created_at }}">
                                <x-ui.icon icon="clock" class="w-3 h-3" />
                                {{ $transaction->created_at?->diffForHumans() ?? trans('common.unknown') }}
                            </span>

                            @if($showStaff && $transaction->staff)
                                <span class="flex items-center gap-1">
                                    <x-ui.icon icon="user" class="w-3 h-3" />
                                    {{ $transaction->staff->name }}
                                </span>
                            @endif
                        </div>

                        {{-- Notes & Attachments (Staff/Partner/Admin Only) --}}
                        @if(($showNotes && $transaction->note) || ($showAttachments && $transaction->image))
                            <div class="mt-3 p-4 bg-amber-50/50 dark:bg-amber-900/10 rounded-xl border border-amber-200/50 dark:border-amber-800/50 space-y-3">
                                @if($showNotes && $transaction->note)
                                    <div class="flex gap-2.5">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                                <x-ui.icon icon="sticky-note" class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs font-semibold text-amber-900 dark:text-amber-300 mb-1 uppercase tracking-wide">{{ trans('common.internal_note') }}</div>
                                            <div class="text-sm text-amber-800 dark:text-amber-200 leading-relaxed">{!! nl2br(e($transaction->note)) !!}</div>
                                        </div>
                                    </div>
                                @endif
                                
                                @if($showAttachments && $transaction->image)
                                    <a href="{{ $transaction->image }}" target="_blank" class="inline-flex items-center gap-2 text-xs font-semibold text-amber-700 dark:text-amber-400 hover:text-amber-900 dark:hover:text-amber-300 transition-colors">
                                        <x-ui.icon icon="paperclip" class="w-3.5 h-3.5" />
                                        {{ trans('common.view_attachment') }}
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Desktop Stamp Badge (Right Side) --}}
                    <div class="hidden sm:block text-right shrink-0">
                        @if($isPositive)
                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 text-base font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl">
                                +{{ abs($transaction->stamps) }}
                                @if($isEmojiIcon)
                                    <span class="text-sm">{{ $stampIcon }}</span>
                                @else
                                    <x-ui.icon :icon="$stampIcon" class="w-4 h-4" />
                                @endif
                            </div>
                        @elseif($transaction->stamps < 0)
                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 text-base font-bold text-rose-700 dark:text-rose-400 bg-rose-100 dark:bg-rose-900/30 rounded-xl">
                                {{ $transaction->stamps }}
                                @if($isEmojiIcon)
                                    <span class="text-sm">{{ $stampIcon }}</span>
                                @else
                                    <x-ui.icon :icon="$stampIcon" class="w-4 h-4" />
                                @endif
                            </div>
                        @else
                            {{-- For reward redemptions (stamps = 0) --}}
                            @if($transaction->event === \App\Models\StampTransaction::EVENT_REWARD_REDEEMED)
                                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold text-rose-700 dark:text-rose-400 bg-rose-100 dark:bg-rose-900/30 rounded-xl">
                                    <x-ui.icon icon="gift" class="w-4 h-4" />
                                    <span>{{ trans('common.claimed') }}</span>
                                </div>
                            @else
                                <div class="text-xs font-medium uppercase tracking-wider text-secondary-400 dark:text-secondary-500">
                                    —
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
