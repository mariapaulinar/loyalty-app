{{--
Premium Opening Hours Component
A beautiful day-by-day schedule editor with open/closed toggles and time pickers.
Inspired by modern UI patterns with smooth transitions and elegant design.

Usage in DataDefinition:
    'opening_hours' => [
        'text' => trans('common.opening_hours'),
        'type' => 'opening_hours',
        'help' => trans('common.opening_hours_help'),
        'default' => [
            'monday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
            ...
        ],
        'validate' => ['nullable', 'array'],
        'actions' => ['edit', 'view'],
    ],
--}}
@props([
    'name' => 'opening_hours',
    'value' => [],
    'label' => null,
    'help' => null,
    'required' => false,
    'class' => '',
])

@php
    // Default hours structure for all days
    $defaultHours = [
        'monday'    => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
        'tuesday'   => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
        'wednesday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
        'thursday'  => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
        'friday'    => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
        'saturday'  => ['open' => '10:00', 'close' => '16:00', 'closed' => false],
        'sunday'    => ['open' => '10:00', 'close' => '16:00', 'closed' => true],
    ];
    
    // Merge provided value with defaults
    $hours = is_array($value) ? array_merge($defaultHours, $value) : $defaultHours;
    
    // Day labels (translatable)
    $days = [
        'monday'    => trans('common.monday'),
        'tuesday'   => trans('common.tuesday'),
        'wednesday' => trans('common.wednesday'),
        'thursday'  => trans('common.thursday'),
        'friday'    => trans('common.friday'),
        'saturday'  => trans('common.saturday'),
        'sunday'    => trans('common.sunday'),
    ];
@endphp

<div class="{{ $class }}">
    {{-- Label Row --}}
    @if ($label)
        <div class="flex items-center gap-2 mb-3">
            <label class="text-sm font-medium text-secondary-700 dark:text-secondary-300">
                {{ $label }}
                @if ($required)
                    <span class="text-red-500">*</span>
                @endif
            </label>
            @if ($help)
                <x-ui.help-popover>{!! $help !!}</x-ui.help-popover>
            @endif
        </div>
    @endif

    {{-- Hours Card --}}
    <div class="bg-white dark:bg-secondary-800/50 border border-secondary-200 dark:border-secondary-700 rounded-xl overflow-hidden shadow-sm">
        {{-- Header --}}
        <div class="px-4 py-3 bg-secondary-50 dark:bg-secondary-800 border-b border-secondary-200 dark:border-secondary-700">
            <div class="flex items-center gap-2">
                <x-ui.icon icon="clock" class="w-4 h-4 text-secondary-500 dark:text-secondary-400" />
                <span class="text-xs font-medium uppercase tracking-wide text-secondary-500 dark:text-secondary-400">
                    {{ trans('common.weekly_schedule') ?? 'Weekly Schedule' }}
                </span>
            </div>
        </div>

        {{-- Days List --}}
        <div class="divide-y divide-secondary-100 dark:divide-secondary-700/50">
            @foreach($days as $dayKey => $dayLabel)
                @php
                    $dayHours = $hours[$dayKey] ?? $defaultHours[$dayKey];
                    $isClosed = $dayHours['closed'] ?? false;
                @endphp
                <div 
                    class="flex items-center gap-3 sm:gap-4 px-3 sm:px-4 py-3 transition-colors hover:bg-secondary-50 dark:hover:bg-secondary-800/50"
                    x-data="{ closed: {{ $isClosed ? 'true' : 'false' }} }">
                    
                    {{-- Day Name --}}
                    <div class="w-20 sm:w-28 flex-shrink-0">
                        <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300">
                            {{ $dayLabel }}
                        </span>
                    </div>

                    {{-- Open/Closed Toggle --}}
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <input type="hidden" name="{{ $name }}[{{ $dayKey }}][closed]" :value="closed ? '1' : '0'">
                        <button 
                            type="button"
                            @click="closed = !closed"
                            :class="closed 
                                ? 'bg-red-50 text-red-600 border-red-200/60 dark:bg-red-500/10 dark:text-red-400 dark:border-transparent' 
                                : 'bg-emerald-50 text-emerald-600 border-emerald-200/60 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-transparent'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-all duration-150 hover:scale-105 active:scale-95">
                            <span x-show="!closed">{{ trans('common.open') }}</span>
                            <span x-show="closed" x-cloak>{{ trans('common.closed') }}</span>
                        </button>
                    </div>

                    {{-- Time Inputs (shown when open) --}}
                    <div 
                        class="flex-1 flex items-center gap-2"
                        x-show="!closed"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100">
                        
                        {{-- Open Time --}}
                        <input 
                            type="time" 
                            name="{{ $name }}[{{ $dayKey }}][open]"
                            value="{{ $dayHours['open'] ?? '09:00' }}"
                            class="time-input-clean w-24 sm:w-28 px-2.5 sm:px-3 py-2 text-sm font-mono
                                   bg-white dark:bg-secondary-800
                                   border border-secondary-200 dark:border-secondary-600
                                   text-secondary-900 dark:text-white
                                   rounded-xl shadow-sm
                                   transition-all duration-200
                                   hover:border-secondary-300 dark:hover:border-secondary-500
                                   focus:outline-none focus:bg-white dark:focus:bg-secondary-800
                                   focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20
                                   focus:shadow-md focus:shadow-primary-500/5">
                        
                        <x-ui.icon icon="arrow-right" class="w-4 h-4 text-secondary-300 dark:text-secondary-600 flex-shrink-0" />
                        
                        {{-- Close Time --}}
                        <input 
                            type="time" 
                            name="{{ $name }}[{{ $dayKey }}][close]"
                            value="{{ $dayHours['close'] ?? '17:00' }}"
                            class="time-input-clean w-24 sm:w-28 px-2.5 sm:px-3 py-2 text-sm font-mono
                                   bg-white dark:bg-secondary-800
                                   border border-secondary-200 dark:border-secondary-600
                                   text-secondary-900 dark:text-white
                                   rounded-xl shadow-sm
                                   transition-all duration-200
                                   hover:border-secondary-300 dark:hover:border-secondary-500
                                   focus:outline-none focus:bg-white dark:focus:bg-secondary-800
                                   focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20
                                   focus:shadow-md focus:shadow-primary-500/5">
                    </div>

                    {{-- Closed Indicator (shown when closed) --}}
                    <div 
                        class="flex-1 flex items-center gap-2 text-sm text-secondary-400 dark:text-secondary-500"
                        x-show="closed"
                        x-cloak>
                        <x-ui.icon icon="moon" class="w-4 h-4 opacity-50" />
                        <span class="italic">{{ trans('common.not_open_this_day') ?? 'Not open this day' }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<style>
    /* Clean up native time input styling */
    .time-input-clean::-webkit-calendar-picker-indicator {
        opacity: 0.5;
        cursor: pointer;
        transition: opacity 0.2s;
    }
    .time-input-clean:hover::-webkit-calendar-picker-indicator,
    .time-input-clean:focus::-webkit-calendar-picker-indicator {
        opacity: 1;
    }
</style>
