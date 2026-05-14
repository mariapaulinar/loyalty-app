@props(['card', 'member', 'transactions', 'showNotes' => false, 'showAttachments' => false, 'showStaff' => false])

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
            <x-ui.icon icon="clock" class="w-8 h-8 text-secondary-400 dark:text-secondary-500" />
        </div>
        <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-2">{{ trans('common.no_history_yet') }}</h3>
        <p class="text-secondary-500 dark:text-secondary-400 max-w-sm">{{ trans('common.transactions_will_appear_here') }}</p>
    </div>
@else
    <div class="relative pl-8 sm:pl-10 space-y-8 before:absolute before:left-3 sm:before:left-3.5 before:top-2 before:bottom-2 before:w-px before:bg-secondary-300 dark:before:bg-secondary-700">
        @foreach($transactions as $transaction)
            @php
                $isExpired = ($transaction->reward_points === null && ($transaction->expires_at && $transaction->expires_at->isPast() || $transaction->points == $transaction->points_used));
                $isPositive = $transaction->points > 0;
                
                // Determine icon and colors
                $icon = 'circle';
                $iconBg = 'bg-secondary-100 text-secondary-500 dark:bg-secondary-800 dark:text-secondary-400';
                
                if ($transaction->event == 'initial_bonus_points') {
                    $icon = 'star';
                    $iconBg = 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400';
                } elseif ($transaction->event == 'referral_bonus') {
                    $icon = 'users';
                    $iconBg = 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400';
                } elseif ($transaction->event == 'referral_welcome_bonus') {
                    $icon = 'gift';
                    $iconBg = 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400';
                } elseif (in_array($transaction->event, ['staff_credited_points_for_purchase', 'staff_credited_points', 'member_redeemed_code_for_points'])) {
                    $icon = 'plus';
                    $iconBg = 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400';
                } elseif ($transaction->event == 'staff_redeemed_points_for_reward') {
                    $icon = 'gift';
                    $iconBg = 'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400';
                } elseif (in_array($transaction->event, ['member_received_points_request', 'member_sent_points_request'])) {
                    $icon = 'arrow-right-left';
                    $iconBg = 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400';
                }

                if ($isExpired) {
                    $iconBg = 'bg-secondary-100 text-secondary-400 dark:bg-secondary-800 dark:text-secondary-500';
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
                                @if($transaction->event == 'initial_bonus_points')
                                    {{ trans('common.initial_bonus_points') }}
                                @elseif($transaction->event == 'referral_bonus')
                                    {{ trans('common.referral_bonus') }}
                                @elseif($transaction->event == 'referral_welcome_bonus')
                                    {{ trans('common.referral_welcome_bonus') }}
                                @elseif($transaction->event == 'staff_credited_points_for_purchase')
                                    {{ trans('common.purchase') }}
                                @elseif($transaction->event == 'staff_credited_points')
                                    {{ trans('common.points_issued') }}
                                @elseif($transaction->event == 'member_redeemed_code_for_points')
                                    {{ trans('common.code_redeemed') }}
                                @elseif($transaction->event == 'staff_redeemed_points_for_reward')
                                    {{ trans('common.reward') }}
                                @elseif($transaction->event == 'member_received_points_request')
                                    {{ trans('common.received_points') }}
                                @elseif($transaction->event == 'member_sent_points_request')
                                    {{ trans('common.sent_points') }}
                                @else
                                    {{ trans('common.transaction') }}
                                @endif
                            </h4>
                            
                            {{-- Mobile Points Display --}}
                            <div class="sm:hidden font-bold {{ $isExpired ? 'text-secondary-400 dark:text-secondary-500' : ($isPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400') }}">
                                {{ $isPositive ? '+' : '' }}{{ $transaction->points }}
                            </div>
                        </div>

                        {{-- Details --}}
                        <p class="text-sm text-secondary-500 dark:text-secondary-400 mb-2">
                            @if($transaction->event == 'referral_bonus')
                                <span class="inline-flex items-center gap-1.5">
                                    <x-ui.icon icon="user-check" class="w-3.5 h-3.5" />
                                    {{ trans('common.referral_bonus_desc') }}
                                </span>
                            @elseif($transaction->event == 'referral_welcome_bonus')
                                <span class="inline-flex items-center gap-1.5">
                                    <x-ui.icon icon="sparkles" class="w-3.5 h-3.5" />
                                    {{ trans('common.referral_welcome_bonus_desc') }}
                                </span>
                            @elseif($transaction->event == 'staff_credited_points_for_purchase')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-secondary-100 text-secondary-800 dark:bg-secondary-700 dark:text-secondary-300">
                                    {{ $transaction->purchase_amount_formatted }}
                                </span>
                            @endif
                            
                            @if($transaction->reward_title)
                                {{ $transaction->reward_title }}
                            @endif

                            @if($transaction->event == 'member_sent_points_request')
                                {{ trans('common.to') }}: {{ $transaction->staff_name }}
                            @endif

                            @if($transaction->event == 'member_received_points_request')
                                {{ trans('common.from') }}: {{ $transaction->staff_name }}
                            @endif
                        </p>

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

                            {{-- Expiry Info --}}
                            @if(!$isExpired && $transaction->expires_at && $transaction->expires_at->isFuture())
                                <span class="flex items-center gap-1 text-amber-600 dark:text-amber-500">
                                    <x-ui.icon icon="hourglass" class="w-3 h-3" />
                                    {{ trans('common.expires') }} {{ $transaction->expires_at?->diffForHumans() ?? trans('common.unknown') }}
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

                    {{-- Desktop Points Display (Right Side) --}}
                    <div class="hidden sm:block text-right shrink-0">
                        <div class="text-lg font-bold {{ $isExpired ? 'text-secondary-400 dark:text-secondary-500' : ($isPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400') }}">
                            {{ $isPositive ? '+' : '' }}{{ $transaction->points }}
                        </div>
                        <div class="text-xs font-medium uppercase tracking-wider text-secondary-400 dark:text-secondary-500">
                            {{ trans('common.points') }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
