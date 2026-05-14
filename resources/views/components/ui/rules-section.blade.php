{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Universal Rules Section Component

Purpose:
Wraps a collection of rule items with a consistent header and container.
Used across loyalty cards, stamp cards, and vouchers.

Props:
- title: Section title (optional, defaults to "Rules & Conditions")
- icon: Header icon (optional, defaults to "shield-check")
- noHeader: Hide the header completely (boolean, default: false)

Slot:
- default: Contains multiple <x-ui.rule-item> components
--}}

@props([
    'title' => null,
    'icon' => 'shield-check',
    'noHeader' => false,
])

<div class="space-y-4">
    {{-- Rules Header (optional) --}}
    @if(!$noHeader)
        <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-primary-50 dark:bg-primary-900/30 rounded-xl">
                <x-ui.icon :icon="$icon" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
            </div>
            <h3 class="text-xl font-bold text-secondary-900 dark:text-white">
                {{ $title ?? trans('common.rules_and_conditions') }}
            </h3>
        </div>
    @endif

    {{-- Rules Grid --}}
    <div class="grid gap-4">
        {{ $slot }}
    </div>
</div>
