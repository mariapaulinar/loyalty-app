{{--
Reward Loyalty - Activity Log Purge

Purpose:
Confirmation page for purging old activity logs. Displays count of records
to be deleted and requires explicit confirmation before deletion.

Design Philosophy:
- Matches admin settings/license page aesthetics
- Clear warning for destructive action
- Category breakdown for transparency
--}}

@extends('admin.layouts.default')

@section('page_title', trans('common.purge_activity_logs') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-4xl mx-auto px-4 md:px-6 py-6 md:py-8">
    {{-- Page Header --}}
    <div class="mb-6">
        <x-ui.page-header
            icon="trash-2"
            :title="trans('common.purge_activity_logs')"
            :description="trans('common.purge_activity_logs_desc', ['days' => $retentionDays])"
        >
            <x-slot name="actions">
                <a href="{{ route('admin.data.list', ['name' => 'activity-logs']) }}"
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
    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
        
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
                        {{ trans('common.purge_warning') }}
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
                        {{ trans('common.records_to_delete') }}
                    </p>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white">
                        <span class="format-number">{{ $recordCount }}</span>
                    </p>
                </div>
                <div class="p-4 rounded-xl bg-secondary-50 dark:bg-secondary-800/50 border border-secondary-100 dark:border-secondary-700">
                    <p class="text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wide mb-2">
                        {{ trans('common.older_than') }}
                    </p>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white">
                        {{ $retentionDays }} {{ trans('common.days') }}
                    </p>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">
                        {{ trans('common.before') }} <span class="format-date" data-date="{{ $cutoffDate->toIso8601String() }}">{{ $cutoffDate->format('M j, Y') }}</span>
                    </p>
                </div>
            </div>

            {{-- Breakdown by Category --}}
            @if(count($breakdown) > 0)
                <div class="mb-6">
                    <p class="text-xs font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-wider mb-4">
                        {{ trans('common.breakdown_by_category') }}
                    </p>
                    <div class="space-y-2">
                        @foreach($breakdown as $category => $count)
                            <div class="flex justify-between items-center py-2.5 px-4 rounded-xl bg-secondary-50 dark:bg-secondary-800/50 border border-secondary-100 dark:border-secondary-700">
                                <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300 capitalize">
                                    {{ str_replace('_', ' ', $category ?: 'default') }}
                                </span>
                                <span class="text-sm font-bold text-secondary-900 dark:text-white">
                                    <span class="format-number">{{ $count }}</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Info Note --}}
            <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50">
                <div class="flex gap-3">
                    <x-ui.icon icon="info" class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="text-sm text-blue-800 dark:text-blue-200 font-medium">
                            {{ trans('common.retention_period_note', ['days' => $retentionDays]) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer Actions --}}
        <div class="px-6 py-4 bg-secondary-50 dark:bg-secondary-800/50 border-t border-secondary-100 dark:border-secondary-800">
            <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
                <a href="{{ route('admin.data.list', ['name' => 'activity-logs']) }}"
                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 
                          text-sm font-medium text-secondary-700 dark:text-secondary-300 
                          bg-white dark:bg-secondary-700 
                          border border-secondary-200 dark:border-secondary-600 
                          rounded-xl shadow-sm
                          hover:bg-secondary-50 dark:hover:bg-secondary-600 
                          transition-colors duration-200">
                    {{ trans('common.cancel') }}
                </a>
                
                @if($recordCount > 0)
                    <form action="{{ route('admin.activity-logs.purge.post') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('{{ trans('common.confirm_purge', ['count' => number_format($recordCount)]) }}')"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 w-full sm:w-auto
                                       text-sm font-semibold text-white 
                                       bg-red-600 hover:bg-red-500
                                       rounded-xl shadow-lg shadow-red-600/25 hover:shadow-xl hover:shadow-red-600/30
                                       focus:outline-none focus:ring-2 focus:ring-red-500/20
                                       transition-all duration-200 active:scale-[0.98]">
                            <x-ui.icon icon="trash-2" class="w-4 h-4" />
                            {{ trans('common.purge_records', ['count' => number_format($recordCount)]) }}
                        </button>
                    </form>
                @else
                    <div class="px-4 py-2.5 text-sm text-secondary-500 dark:text-secondary-400 bg-secondary-100 dark:bg-secondary-700 rounded-xl">
                        {{ trans('common.no_records_to_purge') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
