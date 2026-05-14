@extends('staff.layouts.default')

@section('page_title', $card->head . config('default.page_title_delimiter') . trans('common.add_points') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-lg mx-auto px-4 py-8 md:py-14">
    @if($member && $card)
        {{-- Header --}}
        <x-ui.page-header
            icon="coins"
            :title="trans('common.add_points')"
            :description="trans('common.award_points_for_member_purchase')"
            compact
        />

        {{-- Member Card with Tier Status --}}
        <div class="mt-8 mb-6">
            <x-member.member-card :member="$member" :club="$card->club" :show-tier="true" />
        </div>

        {{-- Messages --}}
        <div class="mb-6">
            <x-forms.messages />
        </div>

        {{-- Main Form --}}
        <x-forms.form-open action="{{ route('staff.earn.points.post', ['member_identifier' => $member->unique_identifier, 'card_identifier' => $card->unique_identifier]) }}" enctype="multipart/form-data" method="POST" />
            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                
                {{-- Form Header --}}
                <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
                    <div class="flex items-center gap-3">
                        <x-ui.icon icon="calculator" class="w-5 h-5 text-secondary-400" />
                        <div>
                            <h3 class="font-semibold text-secondary-900 dark:text-white">{{ trans('common.purchase') }}</h3>
                            <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ trans('common.enter_amount_or_points') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Amount Inputs Section --}}
                <div class="p-6 space-y-5">
                    {{-- Purchase Amount & Points Grid --}}
                    <div class="grid gap-5 sm:grid-cols-2">
                        {{-- Purchase Amount --}}
                        <div>
                            <x-forms.input
                                name="purchase_amount"
                                value=""
                                :label="trans('common.purchase_amount')"
                                type="number"
                                inputmode="decimal"
                                :suffix="$card->currency"
                                affix-class="text-secondary-400 dark:text-secondary-500 text-sm"
                                input-class="text-sm"
                                :min="0"
                                :step="$currency['step']"
                                :placeholder="$currency['placeholder']"
                                :required="true"
                            />
                            
                            {{-- Points Only Toggle --}}
                            <label class="relative inline-flex items-center mt-4 cursor-pointer group">
                                <input type="hidden" value="0" name="points_only">
                                <input type="checkbox" value="1" name="points_only" id="points_only" class="sr-only peer">
                                <div class="w-11 h-6 bg-secondary-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300/50 dark:peer-focus:ring-primary-800/50 rounded-full peer dark:bg-secondary-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-secondary-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all after:shadow-sm dark:border-secondary-700 peer-checked:bg-primary-600 transition-colors"></div>
                                <span class="ml-3 text-sm font-medium text-secondary-700 dark:text-secondary-300 group-hover:text-secondary-900 dark:group-hover:text-white transition-colors">{{ trans('common.enter_points_only') }}</span>
                            </label>
                        </div>

                        {{-- Points --}}
                        <div>
                            <x-forms.input
                                name="points"
                                value=""
                                inputmode="numeric"
                                :label="trans('common.points')"
                                type="number"
                                icon="coins"
                                affix-class="text-secondary-400 dark:text-secondary-500 text-lg"
                                input-class="text-lg cursor-not-allowed"
                                :min="$card->min_points_per_purchase"
                                :max="$card->max_points_per_purchase"
                                step="1"
                                placeholder="0"
                                :required="false"
                                :readonly="true"
                            />
                            
                            {{-- Tier Bonus Indicator --}}
                            @if(isset($tierMultiplier) && $tierMultiplier > 1)
                                <div id="tier-bonus-indicator" class="hidden mt-2 flex items-center gap-2 text-sm">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400">
                                        <x-ui.icon icon="trending-up" class="w-3.5 h-3.5" />
                                        <span class="bonus-text font-medium"></span>
                                    </span>
                                    <span class="text-secondary-500 dark:text-secondary-400">({{ $tierMultiplier }}× {{ trans('common.tier_multiplier_label') }})</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Photo Upload (Optional) --}}
                    <div>
                        <label class="block text-sm font-semibold text-secondary-700 dark:text-secondary-300 mb-2">
                            {{ trans('common.receipt_photo') }} <span class="text-secondary-400 dark:text-secondary-500 font-normal">({{ trans('common.optional') }})</span>
                        </label>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400 mb-3">
                            {{ trans('common.receipt_photo_help') }}
                        </p>
                        <x-forms.image
                            type="image"
                            capture="environment"
                            icon="camera"
                            name="image"
                            :placeholder="trans('common.add_photo_of_receipt')"
                            accept="image/*"
                        />
                    </div>

                    {{-- Notes (Optional) --}}
                    <div class="pb-2">
                        <label class="block text-sm font-semibold text-secondary-700 dark:text-secondary-300 mb-2">
                            {{ trans('common.notes') }} <span class="text-secondary-400 dark:text-secondary-500 font-normal">({{ trans('common.optional') }})</span>
                        </label>
                        <textarea 
                            name="note"
                            rows="2"
                            placeholder="{{ trans('common.internal_only_not_visible_to_customers') }}"
                            class="w-full bg-white dark:bg-secondary-800 border text-secondary-900 dark:text-white text-sm rounded-xl block px-4 py-3 resize-none transition-all duration-200 placeholder:text-secondary-400 dark:placeholder:text-secondary-500 focus:outline-none focus:ring-2 focus:ring-offset-0 border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 focus:border-primary-500 focus:ring-primary-500/20">{{ old('note') }}</textarea>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="p-6 pt-0">
                    <button type="submit" 
                            x-data="{ submitting: false }"
                            @submit.window="submitting = true"
                            :disabled="submitting"
                            class="w-full flex items-center justify-center gap-2.5 px-6 py-3.5
                                   text-sm font-semibold text-white
                                   bg-primary-600 hover:bg-primary-500
                                   rounded-xl shadow-sm hover:shadow-md
                                   focus:outline-none focus:ring-2 focus:ring-primary-500/20
                                   transition-all duration-200 active:scale-[0.98]">
                        <template x-if="!submitting">
                            <span class="flex items-center gap-2">
                                <x-ui.icon icon="coins" class="w-5 h-5" />
                                <span>{{ trans('common.add_points') }}</span>
                            </span>
                        </template>
                        <template x-if="submitting">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ trans('common.adding') }}...</span>
                            </span>
                        </template>
                    </button>
                </div>
                
                {{-- Loyalty Card Rules --}}
                <div class="px-6 pb-6 pt-4">
                    <div class="flex items-start gap-2.5">
                        <x-ui.icon icon="list-checks" class="w-4 h-4 text-secondary-400 shrink-0 mt-0.5" />
                        <div class="flex-1">
                            <h5 class="font-medium text-secondary-600 dark:text-secondary-400 text-xs mb-3">{{ trans('common.loyalty_card_rules') }}</h5>
                            
                            {{-- Points Calculation --}}
                            @if($card->points_per_currency && $card->currency_unit_amount)
                                <div class="mb-4 pb-3 border-b border-secondary-200 dark:border-secondary-800">
                                    <div class="text-xs text-secondary-500 dark:text-secondary-400 mb-1">{{ trans('common.points_calculation') }}</div>
                                    <div class="text-sm font-semibold text-secondary-900 dark:text-white">
                                        {{ $card->currency_unit_amount }} {{ $card->currency }} = {{ $card->points_per_currency }} {{ trans('common.points') }}
                                    </div>
                                    <div class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                                        {{ ($card->meta && isset($card->meta['round_points_up']) && !$card->meta['round_points_up']) ? trans('common.rounded_down') : trans('common.rounded_up') }}
                                    </div>
                                </div>
                            @endif

                            {{-- Rules Table --}}
                            <div class="space-y-2.5 text-xs">
                                @if($card->min_points_per_purchase)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.min_points_per_purchase') }}</span>
                                        <span class="font-medium text-secondary-900 dark:text-white format-number">{{ $card->min_points_per_purchase }}</span>
                                    </div>
                                @endif

                                @if($card->max_points_per_purchase)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.max_points_per_purchase') }}</span>
                                        <span class="font-medium text-secondary-900 dark:text-white format-number">{{ $card->max_points_per_purchase }}</span>
                                    </div>
                                @endif

                                @if($card->initial_bonus_points && $card->initial_bonus_points > 0)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.initial_bonus_points') }}</span>
                                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">+<span class="format-number">{{ $card->initial_bonus_points }}</span></span>
                                    </div>
                                @endif

                                @if(isset($tierMultiplier) && $tierMultiplier > 1)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.tier_bonus') }}</span>
                                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $tierMultiplier }}×</span>
                                    </div>
                                @endif

                                @if($card->points_expiration_months && $card->points_expiration_months > 0)
                                    <div class="flex items-start justify-between py-1">
                                        <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.points_expiration') }}</span>
                                        <span class="font-medium text-secondary-900 dark:text-white">{{ $card->points_expiration_months }} {{ trans('common.months') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <x-forms.form-close />

        {{-- Cancel Link --}}
        <div class="mt-6 text-center">
            <a href="{{ route('staff.transactions', ['member_identifier' => $member->unique_identifier, 'card_identifier' => $card->unique_identifier]) }}" 
               class="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white transition-colors">
                <x-ui.icon icon="arrow-left" class="w-4 h-4" />
                <span>{{ trans('common.cancel_and_view_member_history') }}</span>
            </a>
        </div>

        {{-- Points Calculation Script --}}
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const purchaseAmountInput = document.querySelector('[name=purchase_amount]');
                const purchaseAmountLabel = document.querySelector('label[for=purchase_amount]');
                const purchaseAmountPrefix = document.getElementById('purchase_amount_prefix');
                const purchaseAmountSuffix = document.getElementById('purchase_amount_suffix');
                const pointsInput = document.querySelector('[name=points]');
                const pointsLabel = document.querySelector('label[for=points]');
                const pointsOnly = document.getElementById('points_only');
                const tierBonusIndicator = document.getElementById('tier-bonus-indicator');

                function updateClasses(element, addClasses, removeClasses) {
                    if (!element) return;
                    element.classList.add(...addClasses);
                    element.classList.remove(...removeClasses);
                }

                pointsOnly.addEventListener('change', function() {
                    if (this.checked) {
                        updateClasses(purchaseAmountInput, ['cursor-not-allowed', 'opacity-50'], []);
                        updateClasses(purchaseAmountLabel, ['opacity-50'], []);
                        if (purchaseAmountPrefix) updateClasses(purchaseAmountPrefix, ['opacity-50'], []);
                        if (purchaseAmountSuffix) updateClasses(purchaseAmountSuffix, ['opacity-50'], []);
                        updateClasses(pointsInput, [], ['cursor-not-allowed', 'opacity-50']);

                        purchaseAmountInput.required = false;
                        purchaseAmountInput.disabled = true;
                        purchaseAmountInput.value = null;
                        pointsInput.required = true;
                        pointsInput.readOnly = false;
                        pointsInput.value = null;

                        purchaseAmountLabel.innerHTML = '{{ trans('common.purchase_amount') }}';
                        pointsLabel.innerHTML = '{{ trans('common.points') }}&nbsp;*';

                        if (tierBonusIndicator) tierBonusIndicator.classList.add('hidden');

                        pointsInput.focus();
                    } else {
                        updateClasses(purchaseAmountInput, [], ['cursor-not-allowed', 'opacity-50']);
                        updateClasses(purchaseAmountLabel, [], ['opacity-50']);
                        if (purchaseAmountPrefix) updateClasses(purchaseAmountPrefix, [], ['opacity-50']);
                        if (purchaseAmountSuffix) updateClasses(purchaseAmountSuffix, [], ['opacity-50']);
                        updateClasses(pointsInput, ['cursor-not-allowed'], ['opacity-50']);

                        purchaseAmountInput.required = true;
                        purchaseAmountInput.disabled = false;
                        pointsInput.required = false;
                        pointsInput.readOnly = true;
                        pointsInput.value = null;

                        purchaseAmountLabel.innerHTML = '{{ trans('common.purchase_amount') }}&nbsp;*';
                        pointsLabel.innerHTML = '{{ trans('common.points') }}';

                        purchaseAmountInput.focus();
                    }
                });

                const round_points_up = {{ json_encode((bool) ($card->meta['round_points_up'] ?? true)) }};
                const currency_unit_amount = {{ $card->currency_unit_amount }};
                const points_per_currency = {{ $card->points_per_currency }};
                const min_points_per_purchase = {{ $card->min_points_per_purchase }};
                const max_points_per_purchase = {{ $card->max_points_per_purchase }};
                const tier_multiplier = {{ $tierMultiplier ?? 1.00 }};

                purchaseAmountInput.addEventListener('input', function() {
                    if (!pointsOnly.checked) {
                        let basePoints;

                        if (round_points_up) {
                            basePoints = Math.ceil((this.value / currency_unit_amount) * points_per_currency);
                        } else {
                            basePoints = Math.floor((this.value / currency_unit_amount) * points_per_currency);
                        }

                        let pointsValue = Math.floor(basePoints * tier_multiplier);

                        if (pointsValue >= min_points_per_purchase && pointsValue <= max_points_per_purchase) {
                            pointsInput.value = pointsValue;
                        } else if (pointsValue < min_points_per_purchase) {
                            pointsInput.value = min_points_per_purchase;
                        } else if (pointsValue > max_points_per_purchase) {
                            pointsInput.value = max_points_per_purchase;
                        }

                        if (tierBonusIndicator && tier_multiplier > 1 && basePoints > 0) {
                            const bonusPoints = pointsValue - basePoints;
                            const bonusText = tierBonusIndicator.querySelector('.bonus-text');
                            if (bonusText && bonusPoints > 0) {
                                bonusText.textContent = '+' + bonusPoints + ' {{ trans('common.tier_bonus') }}';
                                tierBonusIndicator.classList.remove('hidden');
                            }
                        }
                    }
                });

                document.querySelector('form').addEventListener('submit', function() {
                    this.querySelector('button[type="submit"]').disabled = true;
                });
            });
        </script>

    @else
        {{-- Error States --}}
        <div class="space-y-6">
            @if(!$member)
                <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 rounded-xl p-8 text-center">
                    <div class="w-16 h-16 mx-auto rounded-xl bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center mb-4">
                        <x-ui.icon icon="user-x" class="w-8 h-8 text-rose-600 dark:text-rose-400" />
                    </div>
                    <h3 class="text-lg font-semibold text-rose-900 dark:text-rose-300">{{ trans('common.member_not_found') }}</h3>
                </div>
            @endif

            @if(!$card)
                <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 rounded-xl p-8 text-center">
                    <div class="w-16 h-16 mx-auto rounded-xl bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center mb-4">
                        <x-ui.icon icon="credit-card" class="w-8 h-8 text-rose-600 dark:text-rose-400" />
                    </div>
                    <h3 class="text-lg font-semibold text-rose-900 dark:text-rose-300">{{ trans('common.card_not_found') }}</h3>
                </div>
            @endif
        </div>
    @endif
</div>
@stop
