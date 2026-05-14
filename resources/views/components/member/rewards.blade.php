@if($card->activeRewards->isEmpty())
    <div class="flex flex-col items-center justify-center py-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center mb-4">
            <x-ui.icon icon="gift" class="w-8 h-8 text-secondary-400 dark:text-secondary-500" />
        </div>
        <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-2">
            {{ trans('common.no_items_yet', ['items' => trans('common.rewards')]) }}
        </h3>
        <p class="text-secondary-500 dark:text-secondary-400 max-w-sm mx-auto">
            {{ trans('common.memberDashboardBlocksTitle') }}
        </p>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach ($card->activeRewards as $reward)
            <div data-clickable-href="{{ route('member.card.reward', ['card_id' => $card->id, 'reward_id' => $reward->id]) }}"
                class="group relative bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-4 cursor-pointer hover:border-primary-500 dark:hover:border-primary-500 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 @if($currentReward && $currentReward->id == $reward->id) ring-2 ring-primary-500 @endif">

                <div class="flex items-start gap-4">
                    {{-- Reward Image/Icon --}}
                    <div class="flex-shrink-0">
                        <div
                            class="w-16 h-16 rounded-xl bg-secondary-50 dark:bg-secondary-800 flex items-center justify-center overflow-hidden group-hover:scale-105 transition-transform duration-300">
                            @if($reward->image1)
                                <img src="{{ $reward->getImageUrl('image1', 'xs') }}" alt="{{ parse_attr($reward->title) }}"
                                    class="w-full h-full object-cover">
                            @else
                                <x-ui.icon icon="gift"
                                    class="w-8 h-8 text-secondary-400 dark:text-secondary-500 group-hover:text-primary-500 transition-colors" />
                            @endif
                        </div>
                    </div>

                    {{-- Reward Details --}}
                    <div class="flex-1 min-w-0">
                        <h4
                            class="text-sm font-semibold text-secondary-900 dark:text-white mb-1 line-clamp-2 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                            {{ $reward->title }}
                        </h4>

                        <div class="flex items-center gap-2 mt-2">
                            <div
                                class="flex items-center px-2 py-1 rounded-lg bg-secondary-100 dark:bg-secondary-800 group-hover:bg-primary-50 dark:group-hover:bg-primary-900/30 transition-colors">
                                <x-ui.icon icon="coins"
                                    class="w-3.5 h-3.5 mr-1.5 text-secondary-500 dark:text-secondary-400 group-hover:text-primary-600 dark:group-hover:text-primary-400" />
                                <span
                                    class="text-xs font-bold font-mono text-secondary-700 dark:text-secondary-300 group-hover:text-primary-700 dark:group-hover:text-primary-300 format-number">
                                    {{ $reward->points }}
                                </span>
                            </div>

                            @if($showClaimable && $card->getMemberBalance(null) >= $reward->points)
                                <span class="flex h-2.5 w-2.5 relative">
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif