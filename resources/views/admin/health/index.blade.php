{{--
Reward Loyalty - Health Center

Purpose:
Admin diagnostics dashboard showing system environment, database,
storage, and service status. Helps operators identify and resolve
configuration issues before they become support tickets.
--}}

@extends('admin.layouts.default')

@section('page_title', trans('common.health.title') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">

    {{-- Page Header --}}
    <div class="mb-6">
        <x-ui.page-header
            icon="heart-pulse"
            :title="trans('common.health.title')"
            :description="trans('common.health.subtitle')"
        >
            <x-slot name="actions">
                <a href="{{ route('admin.health.json') }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-secondary-100 dark:bg-secondary-800 hover:bg-secondary-200 dark:hover:bg-secondary-700 text-secondary-700 dark:text-secondary-300 font-medium text-sm transition-all duration-200"
                    title="{{ trans('common.health.export_json') }}">
                    <x-ui.icon icon="code" class="w-4 h-4" />
                    JSON
                </a>
            </x-slot>
        </x-ui.page-header>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        {{-- Total Checks --}}
        <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-5">
            <p class="text-xs font-medium text-secondary-500 dark:text-secondary-400 mb-2">
                {{ trans('common.health.total_checks') }}
            </p>
            <p class="text-2xl font-bold text-secondary-900 dark:text-white">
                {{ $summary['total'] }}
            </p>
        </div>

        {{-- Passed --}}
        <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-5">
            <p class="text-xs font-medium text-secondary-500 dark:text-secondary-400 mb-2">
                {{ trans('common.health.passed') }}
            </p>
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                {{ $summary['ok'] }}
            </p>
        </div>

        {{-- Warnings --}}
        <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-5">
            <p class="text-xs font-medium text-secondary-500 dark:text-secondary-400 mb-2">
                {{ trans('common.health.warnings') }}
            </p>
            <p class="text-2xl font-bold {{ $summary['warning'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-secondary-400 dark:text-secondary-500' }}">
                {{ $summary['warning'] }}
            </p>
        </div>

        {{-- Critical --}}
        <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 p-5">
            <p class="text-xs font-medium text-secondary-500 dark:text-secondary-400 mb-2">
                {{ trans('common.health.critical') }}
            </p>
            <p class="text-2xl font-bold {{ $summary['critical'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-secondary-400 dark:text-secondary-500' }}">
                {{ $summary['critical'] }}
            </p>
        </div>
    </div>

    {{-- Overall Status Banner --}}
    @if($summary['critical'] > 0)
        <div class="mb-8 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center">
                    <x-ui.icon icon="alert-triangle" class="w-5 h-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-red-800 dark:text-red-200">
                        {{ trans('common.health.status_critical') }}
                    </p>
                    <p class="text-xs text-red-700 dark:text-red-300">
                        {{ trans('common.health.status_critical_desc') }}
                    </p>
                </div>
            </div>
        </div>
    @elseif($summary['warning'] > 0)
        <div class="mb-8 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center">
                    <x-ui.icon icon="info" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        {{ trans('common.health.status_warning') }}
                    </p>
                    <p class="text-xs text-amber-700 dark:text-amber-300">
                        {{ trans('common.health.status_warning_desc') }}
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="mb-8 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                    <x-ui.icon icon="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">
                        {{ trans('common.health.status_ok') }}
                    </p>
                    <p class="text-xs text-emerald-700 dark:text-emerald-300">
                        {{ trans('common.health.status_ok_desc') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Check Categories --}}
    <div class="space-y-6">
        @php
            $categoryConfig = [
                'environment' => ['icon' => 'cpu', 'title' => trans('common.health.cat_environment')],
                'application' => ['icon' => 'globe', 'title' => trans('common.health.cat_application')],
                'database' => ['icon' => 'database', 'title' => trans('common.health.cat_database')],
                'storage' => ['icon' => 'hard-drive', 'title' => trans('common.health.cat_storage')],
                'services' => ['icon' => 'cog', 'title' => trans('common.health.cat_services')],
            ];
        @endphp

        @foreach($categories as $categoryKey => $checks)
            @php
                $config = $categoryConfig[$categoryKey] ?? ['icon' => 'circle', 'title' => ucfirst($categoryKey)];
                $hasCritical = collect($checks)->contains('status', 'critical');
                $hasWarning = collect($checks)->contains('status', 'warning');
            @endphp

            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                {{-- Category Header --}}
                <div class="px-6 py-4 border-b border-secondary-100 dark:border-secondary-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <x-ui.icon :icon="$config['icon']" class="w-5 h-5 text-secondary-400" />
                            <h2 class="text-base font-semibold text-secondary-900 dark:text-white">
                                {{ $config['title'] }}
                            </h2>
                        </div>
                        @if($hasCritical)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-500/10 text-red-600 dark:text-red-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                {{ trans('common.health.issues_found') }}
                            </span>
                        @elseif($hasWarning)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                {{ trans('common.health.review_recommended') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                <x-ui.icon icon="check" class="w-3 h-3" />
                                {{ trans('common.health.all_ok') }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Check Items --}}
                <div class="divide-y divide-secondary-100 dark:divide-secondary-800">
                    @foreach($checks as $check)
                        <div class="px-6 py-4 flex items-start gap-4">
                            {{-- Status Icon --}}
                            <div class="flex-shrink-0 mt-0.5">
                                @if($check['status'] === 'ok')
                                    <div class="w-6 h-6 rounded-full bg-emerald-500/10 flex items-center justify-center">
                                        <x-ui.icon icon="check" class="w-3.5 h-3.5 text-emerald-500" />
                                    </div>
                                @elseif($check['status'] === 'warning')
                                    <div class="w-6 h-6 rounded-full bg-amber-500/10 flex items-center justify-center">
                                        <x-ui.icon icon="alert-triangle" class="w-3.5 h-3.5 text-amber-500" />
                                    </div>
                                @else
                                    <div class="w-6 h-6 rounded-full bg-red-500/10 flex items-center justify-center">
                                        <x-ui.icon icon="x" class="w-3.5 h-3.5 text-red-500" />
                                    </div>
                                @endif
                            </div>

                            {{-- Label & Detail --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-medium text-secondary-900 dark:text-white">
                                        {{ $check['label'] }}
                                    </p>
                                    <p class="text-sm font-mono text-secondary-600 dark:text-secondary-300 text-right whitespace-nowrap">
                                        {{ $check['value'] }}
                                    </p>
                                </div>
                                @if($check['detail'])
                                    <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">
                                        {{ $check['detail'] }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Footer Note --}}
    <div class="mt-8 text-center">
        <p class="text-xs text-secondary-400 dark:text-secondary-500">
            {{ trans('common.health.footer_note') }}
        </p>
    </div>
</div>
@endsection
