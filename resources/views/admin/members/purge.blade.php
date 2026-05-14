{{--
Reward Loyalty - Purge Ghost Members

Purpose:
Configuration and confirmation page for purging inactive anonymous members
who have never interacted with the platform. Allows admins to select a
retention period & preview counts before executing the purge.

Design Philosophy:
- Matches admin settings page aesthetics (same card/header styles)
- Selectable time range with live preview
- Transparent breakdown of member stats
- Clear destructive-action warnings
--}}

@extends('admin.layouts.default')

@section('page_title', trans('common.purge_ghost_members') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-4xl mx-auto px-4 md:px-6 py-6 md:py-8">
    {{-- Page Header --}}
    <div class="mb-6">
        <x-ui.page-header
            icon="user-minus"
            :title="trans('common.purge_ghost_members')"
            :description="trans('common.purge_ghost_members_desc')"
        >
            <x-slot name="actions">
                <a href="{{ route('admin.data.list', ['name' => 'members']) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 
                          text-sm font-medium text-secondary-700 dark:text-secondary-300 
                          bg-white dark:bg-secondary-800 
                          border border-secondary-200 dark:border-secondary-700 
                          rounded-xl shadow-sm
                          hover:bg-secondary-50 dark:hover:bg-secondary-700 
                          transition-colors duration-200">
                    <x-ui.icon icon="arrow-left" class="w-4 h-4" />
                    {{ trans('common.back') }}
                </a>
            </x-slot>
        </x-ui.page-header>
    </div>

    {{-- Main Card --}}
    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800">
        
        {{-- Retention Period Selector --}}
        <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-500/20 flex items-center justify-center">
                    <x-ui.icon icon="calendar" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-secondary-900 dark:text-white">
                        {{ trans('common.purge_select_period') }}
                    </h2>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">
                        {{ trans('common.purge_select_period_desc') }}
                    </p>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                @foreach($retentionOptions as $months)
                    <a href="{{ route('admin.members.purge', ['months' => $months]) }}"
                       class="relative flex flex-col items-center gap-1.5 p-4 rounded-xl border-2 transition-all duration-200
                              @if($retentionMonths === $months)
                                  border-primary-500 bg-primary-50 dark:bg-primary-500/10 shadow-sm
                              @else
                                  border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-800
                                  hover:border-secondary-300 dark:hover:border-secondary-600 hover:bg-secondary-50 dark:hover:bg-secondary-700
                              @endif">
                        @if($retentionMonths === $months)
                            <div class="absolute -top-2 -right-2 w-5 h-5 rounded-full bg-primary-500 flex items-center justify-center">
                                <x-ui.icon icon="check" class="w-3 h-3 text-white" />
                            </div>
                        @endif
                        <span class="text-2xl font-bold @if($retentionMonths === $months) text-primary-600 dark:text-primary-400 @else text-secondary-900 dark:text-white @endif">
                            {{ $months }}
                        </span>
                        <span class="text-xs font-medium @if($retentionMonths === $months) text-primary-600 dark:text-primary-400 @else text-secondary-500 dark:text-secondary-400 @endif uppercase tracking-wide">
                            {{ trans('common.months') }}
                        </span>
                        <span class="text-xs font-medium @if($retentionMonths === $months) text-primary-700 dark:text-primary-300 @else text-secondary-600 dark:text-secondary-300 @endif">
                            <span class="format-number">{{ $periodCounts[$months] }}</span> {{ trans('common.members') }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Warning Header --}}
        <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800 bg-amber-50 dark:bg-amber-500/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-500/20 flex items-center justify-center">
                    <x-ui.icon icon="alert-triangle" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-200">
                        {{ trans('common.warning') }}
                    </h2>
                    <p class="text-sm text-amber-700 dark:text-amber-400">
                        {{ trans('common.purge_members_warning') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Content --}}
        <div class="p-6">
            {{-- Summary Stats --}}
            <div class="grid sm:grid-cols-2 gap-4 mb-6">
                <div class="p-4 rounded-xl bg-secondary-50 dark:bg-secondary-800/50 border border-secondary-100 dark:border-secondary-700">
                    <p class="text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wide mb-2">
                        {{ trans('common.members_to_purge') }}
                    </p>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white">
                        <span class="format-number">{{ $purgeableCount }}</span>
                    </p>
                </div>
                <div class="p-4 rounded-xl bg-secondary-50 dark:bg-secondary-800/50 border border-secondary-100 dark:border-secondary-700">
                    <p class="text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wide mb-2">
                        {{ trans('common.older_than') }}
                    </p>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white">
                        {{ $retentionMonths }} {{ trans('common.months') }}
                    </p>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">
                        {{ trans('common.before') }} <span class="format-date" data-date="{{ $cutoffDate->toIso8601String() }}">{{ $cutoffDate->format('M j, Y') }}</span>
                    </p>
                </div>
            </div>

            {{-- Breakdown --}}
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <x-ui.icon icon="pie-chart" class="w-4 h-4 text-secondary-400" />
                    <p class="text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                        {{ trans('common.member_breakdown') }}
                    </p>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center py-2.5 px-4 rounded-xl bg-secondary-50 dark:bg-secondary-800/50 border border-secondary-100 dark:border-secondary-700">
                        <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300">
                            {{ trans('common.total_members') }}
                        </span>
                        <span class="text-sm font-bold text-secondary-900 dark:text-white">
                            <span class="format-number">{{ $totalMembers }}</span>
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-2.5 px-4 rounded-xl bg-secondary-50 dark:bg-secondary-800/50 border border-secondary-100 dark:border-secondary-700">
                        <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300">
                            {{ trans('common.anonymous_members') }}
                        </span>
                        <span class="text-sm font-bold text-secondary-900 dark:text-white">
                            <span class="format-number">{{ $totalAnonymous }}</span>
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-2.5 px-4 rounded-xl bg-secondary-50 dark:bg-secondary-800/50 border border-secondary-100 dark:border-secondary-700">
                        <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300">
                            {{ trans('common.ghost_members') }}
                        </span>
                        <span class="text-sm font-bold text-secondary-900 dark:text-white">
                            <span class="format-number">{{ $anonymousGhosts }}</span>
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-2.5 px-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30">
                        <span class="text-sm font-medium text-red-700 dark:text-red-300">
                            {{ trans('common.will_be_deleted') }}
                        </span>
                        <span class="text-sm font-bold text-red-700 dark:text-red-300">
                            <span class="format-number">{{ $purgeableCount }}</span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Info Note --}}
            <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50">
                <div class="flex gap-3">
                    <x-ui.icon icon="info" class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="text-sm text-blue-800 dark:text-blue-200 font-medium">
                            {{ trans('common.purge_members_safe_note') }}
                        </p>
                        <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                            {{ trans('common.purge_members_criteria') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer Actions --}}
        <div class="px-6 py-4 bg-secondary-50 dark:bg-secondary-800/50 border-t border-secondary-100 dark:border-secondary-800">
            <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
                <a href="{{ route('admin.data.list', ['name' => 'members']) }}"
                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 
                          text-sm font-medium text-secondary-700 dark:text-secondary-300 
                          bg-white dark:bg-secondary-700 
                          border border-secondary-200 dark:border-secondary-600 
                          rounded-xl shadow-sm
                          hover:bg-secondary-50 dark:hover:bg-secondary-600 
                          transition-colors duration-200">
                    {{ trans('common.cancel') }}
                </a>
                
                @if($purgeableCount > 0)
                    <form id="purge-members-form" action="{{ route('admin.members.purge.post') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="retention_months" value="{{ $retentionMonths }}">
                        <button type="button"
                                onclick="confirmPurge()"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 w-full sm:w-auto
                                       text-sm font-semibold text-white 
                                       bg-red-600 hover:bg-red-500
                                       rounded-xl shadow-lg shadow-red-600/25 hover:shadow-xl hover:shadow-red-600/30
                                       focus:outline-none focus:ring-2 focus:ring-red-500/20
                                       transition-all duration-200 active:scale-[0.98]">
                            <x-ui.icon icon="trash-2" class="w-4 h-4" />
                            {{ trans('common.purge_members_button', ['count' => number_format($purgeableCount)]) }}
                        </button>
                    </form>
                @else
                    <div class="px-4 py-2.5 text-sm text-secondary-500 dark:text-secondary-400 bg-secondary-100 dark:bg-secondary-700 rounded-xl">
                        {{ trans('common.purge_members_none') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($purgeableCount > 0)
<script>
    function confirmPurge() {
        appConfirm(
            '{{ trans('common.purge_ghost_members') }}',
            '{!! trans('common.purge_members_confirm', ['count' => number_format($purgeableCount)]) !!}',
            {
                'btnConfirm': {
                    'click': function() {
                        document.getElementById('purge-members-form').submit();
                    }
                }
            }
        );
    }
</script>
@endif
@endsection
