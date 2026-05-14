{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.
--}}

@props(['type'])

@php
$config = match($type) {
    'percentage' => ['icon' => 'percent', 'color' => 'purple', 'label' => __('common.percentage_off')],
    'fixed_amount' => ['icon' => 'banknote', 'color' => 'green', 'label' => __('common.fixed_amount_off')],
    'free_product' => ['icon' => 'gift', 'color' => 'pink', 'label' => __('common.free_product')],
    'free_shipping' => ['icon' => 'truck', 'color' => 'blue', 'label' => __('common.free_shipping')],
    'bonus_points' => ['icon' => 'star', 'color' => 'amber', 'label' => __('common.bonus_points')],
    default => ['icon' => 'tag', 'color' => 'gray', 'label' => $type],
};

$colorClasses = match($config['color']) {
    'purple' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800',
    'green' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800',
    'pink' => 'bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-300 border-pink-200 dark:border-pink-800',
    'blue' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800',
    'amber' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800',
    default => 'bg-secondary-100 dark:bg-secondary-900/30 text-secondary-700 dark:text-secondary-300 border-secondary-200 dark:border-secondary-800',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border {$colorClasses}"]) }}>
    <x-ui.icon :icon="$config['icon']" class="w-3.5 h-3.5" />
    <span>{{ $config['label'] }}</span>
</span>
