{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.
--}}

@props(['voucher'])

@php
$status = match(true) {
    $voucher->is_expired => ['label' => __('common.voucher_expired'), 'color' => 'red'],
    $voucher->is_exhausted => ['label' => __('common.voucher_exhausted'), 'color' => 'gray'],
    $voucher->is_not_yet_valid => ['label' => __('common.scheduled'), 'color' => 'blue'],
    !$voucher->is_active => ['label' => __('common.voucher_inactive'), 'color' => 'gray'],
    default => ['label' => __('common.voucher_active'), 'color' => 'green'],
};

$colorClasses = match($status['color']) {
    'red' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800',
    'green' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800',
    'blue' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800',
    default => 'bg-secondary-100 dark:bg-secondary-900/30 text-secondary-700 dark:text-secondary-300 border-secondary-200 dark:border-secondary-800',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold border {$colorClasses}"]) }}>
    {{ $status['label'] }}
</span>
