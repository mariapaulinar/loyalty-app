{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Stamp Rules Tab - Program Rules and Terms
Now using universal <x-ui.rule-item> components for consistency!
--}}

<x-ui.rules-section>
    {{-- Rule 1: Stamps Required --}}
    <x-ui.rule-item
        :icon="$stampCard->stamp_icon ?? 'star'"
        :title="trans('common.stamps_to_complete')"
        :description="trans('common.stamp_rule_required', ['stamps' => '<span class=\'font-bold text-primary-600 dark:text-primary-400\'>' . $stampCard->stamps_required . '</span>'])"
        color="primary" />
    
    {{-- Rule 2: Expiration --}}
    @if ($stampCard->stamps_expire_days)
        <x-ui.rule-item
            icon="clock"
            :title="trans('common.expiration_policy')"
            :description="trans('common.stamp_rule_expiration', ['days' => $stampCard->stamps_expire_days])"
            color="amber" />
    @endif
    
    {{-- Rule 3: Purchase Requirements --}}
    @if ($stampCard->min_purchase_amount)
        <x-ui.rule-item
            icon="banknote"
            :title="trans('common.minimum_purchase')"
            :description="trans('common.stamp_rule_min_purchase', ['amount' => '<span class=\'font-bold text-emerald-600 dark:text-emerald-400\'>' . moneyFormat((float) $stampCard->min_purchase_amount, $stampCard->currency) . '</span>'])"
            color="emerald" />
    @endif
    
    {{-- Rule 4: Daily Limits --}}
    @if ($stampCard->max_stamps_per_day)
        <x-ui.rule-item
            icon="calendar-days"
            :title="trans('common.daily_limit')"
            :description="trans('common.stamp_rule_daily_limit', ['max' => '<span class=\'font-bold text-blue-600 dark:text-blue-400\'>' . $stampCard->max_stamps_per_day . '</span>'])"
            color="blue" />
    @endif
    
    {{-- Rule 5: Transaction Limits --}}
    @if ($stampCard->max_stamps_per_transaction)
        <x-ui.rule-item
            icon="shopping-bag"
            :title="trans('common.per_transaction')"
            :description="trans('common.stamp_rule_transaction_limit', ['max' => '<span class=\'font-bold text-purple-600 dark:text-purple-400\'>' . $stampCard->max_stamps_per_transaction . '</span>'])"
            color="purple" />
    @endif
    
    {{-- Rule 6: Validity Period --}}
    @if ($stampCard->valid_from || $stampCard->valid_until)
        @php
            // Build the PHP fallback text for progressive enhancement
            $fallbackText = '';
            if ($stampCard->valid_from && $stampCard->valid_until) {
                $fallbackText = trans('common.from') . ' ' . $stampCard->valid_from->format('M d, Y') . ' ' . trans('common.until') . ' ' . $stampCard->valid_until->format('M d, Y');
            } elseif ($stampCard->valid_from) {
                $fallbackText = trans('common.from') . ' ' . $stampCard->valid_from->format('M d, Y');
            } else {
                $fallbackText = trans('common.until') . ' ' . $stampCard->valid_until->format('M d, Y');
            }
            
            // Build the locale-aware date range span
            $validityHtml = '<span class="format-date-range"'
                . ($stampCard->valid_from ? ' data-date-from="' . $stampCard->valid_from->toISOString() . '"' : '')
                . ($stampCard->valid_until ? ' data-date-to="' . $stampCard->valid_until->toISOString() . '"' : '')
                . ' data-prefix-from="' . trans('common.from') . '"'
                . ' data-prefix-to="' . trans('common.until') . '">'
                . $fallbackText
                . '</span>';
        @endphp
        <x-ui.rule-item
            icon="calendar"
            :title="trans('common.validity_period')"
            :description="$validityHtml"
            color="pink" />
    @endif
    
    {{-- Rule 7: Reward Details (Highlighted) --}}
    <x-ui.rule-item
        icon="gift"
        :title="trans('common.reward_details')"
        :description="trans('common.stamp_rule_reward', ['reward' => $stampCard->reward_title])"
        color="amber"
        :highlight="true" />
</x-ui.rules-section>

