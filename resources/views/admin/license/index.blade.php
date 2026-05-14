{{--
Reward Loyalty - License & Updates Management

Purpose:
Central hub for license activation, validation, and update management.
Designed for super admins to manage the Reward Loyalty license and keep
the application up to date with one-click updates.

Features:
- License activation with purchase code
- License status dashboard with expiry warnings
- One-click update checks and installation
- Update history with status tracking
--}}

@extends('admin.layouts.default')

@section('page_title', trans('common.license.title') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
    {{-- License Alerts (dismissable) --}}
    @if(session('licenseAlert'))
        @php $alert = session('licenseAlert'); @endphp
        <div class="mb-6">
            <x-ui.alert 
                :type="$alert['type']" 
                :title="$alert['title'] ?? null"
                dismissable
            >
                {{ $alert['message'] }}
            </x-ui.alert>
        </div>
    @endif

    {{-- Error from URL param (for update errors) --}}
    @if(request('error'))
        <div class="mb-6">
            <x-ui.alert type="error" :title="trans('common.license.update_failed')" dismissable>
                {{ request('error') }}
            </x-ui.alert>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="mb-6">
        <x-ui.page-header
            icon="shield-check"
            :title="trans('common.license.title')"
            :description="trans('common.license.subtitle')"
        >
            <x-slot name="actions">
                @if($licenseStatus === 'active' || $licenseStatus === 'expired')
                    <form method="POST" action="{{ route('admin.license.check-updates') }}" x-data="{ checking: false }" x-on:submit="checking = true">
                        @csrf
                        <button type="submit" 
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary-700 hover:bg-primary-800 text-white font-semibold text-sm shadow-lg shadow-primary-700/25 hover:shadow-xl hover:shadow-primary-700/30 transition-all duration-200 disabled:opacity-50 active:scale-[0.98]"
                            x-bind:disabled="checking">
                            <span x-bind:class="{ 'animate-spin': checking }"><x-ui.icon icon="refresh-cw" class="w-4 h-4" /></span>
                            <span x-show="!checking">{{ trans('common.license.check_updates') }}</span>
                            <span x-show="checking" x-cloak>{{ trans('common.checking') }}...</span>
                        </button>
                    </form>
                @endif
            </x-slot>
        </x-ui.page-header>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-8">
            
            {{-- License Status Card --}}
            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
                    <div class="flex items-center gap-3">
                        <x-ui.icon icon="shield-check" class="w-5 h-5 text-secondary-400" />
                        <div>
                            <h2 class="text-base font-semibold text-secondary-900 dark:text-white">
                                {{ trans('common.license.license_status') }}
                            </h2>
                            <p class="text-sm text-secondary-500 dark:text-secondary-400">
                                {{ trans('common.license.license_status_desc') }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="p-6">
                    @if($licenseStatus === 'inactive')
                        {{-- Activation Form --}}
                        <div class="text-center py-6 mb-6">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-primary-50 dark:bg-primary-500/10 mb-4">
                                <x-ui.icon icon="key" class="w-7 h-7 text-primary-600 dark:text-primary-400" />
                            </div>
                            <h3 class="text-xl font-bold text-secondary-900 dark:text-white mb-2">
                                {{ trans('common.license.activate_title') }}
                            </h3>
                            <p class="text-secondary-500 dark:text-secondary-400 max-w-md mx-auto">
                                {{ trans('common.license.activate_desc') }}
                            </p>
                        </div>

                        <form method="POST" action="{{ route('admin.license.activate') }}" class="space-y-5" x-data="{ submitting: false }" x-on:submit="submitting = true">
                            @csrf
                            <x-forms.input 
                                type="text"
                                name="purchase_code"
                                :value="old('purchase_code')"
                                :label="trans('common.license.purchase_code')"
                                :placeholder="trans('common.license.purchase_code_placeholder')"
                                :text="trans('common.license.purchase_code_help')"
                                icon="key"
                                required
                                maxlength="36"
                            />
                            
                            <x-forms.input 
                                type="text"
                                name="domain"
                                :value="old('domain', request()->getHost())"
                                :label="trans('common.license.production_domain')"
                                :placeholder="trans('common.license.domain_placeholder')"
                                :text="trans('common.license.domain_help')"
                                icon="globe"
                                required
                            />

                            <button type="submit" 
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl bg-primary-700 hover:bg-primary-800 text-white font-semibold text-sm shadow-lg shadow-primary-700/25 hover:shadow-xl hover:shadow-primary-700/30 transition-all duration-200 disabled:opacity-50 active:scale-[0.98]"
                                x-bind:disabled="submitting">
                                <template x-if="!submitting">
                                    <x-ui.icon icon="key" class="w-4 h-4" />
                                </template>
                                <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-show="!submitting">{{ trans('common.license.activate_button') }}</span>
                                <span x-show="submitting" x-cloak>{{ trans('common.license.activating') }}...</span>
                            </button>
                        </form>
                    @else
                        {{-- License Active --}}
                        <div class="grid grid-cols-2 gap-5">
                            <div class="bg-secondary-50 dark:bg-secondary-800/50 rounded-xl p-4">
                                <p class="text-xs font-medium text-secondary-500 dark:text-secondary-400 mb-2">
                                    {{ trans('common.license.status') }}
                                </p>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold {{ $licenseStatus === 'active' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-accent-500/10 text-accent-500' }}">
                                    @if($licenseStatus === 'active')
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    @endif
                                    {{ trans('common.license.status_' . $licenseStatus) }}
                                </span>
                            </div>

                            <div class="bg-secondary-50 dark:bg-secondary-800/50 rounded-xl p-4">
                                <p class="text-xs font-medium text-secondary-500 dark:text-secondary-400 mb-2">
                                    {{ trans('common.license.support_expires') }}
                                </p>
                                <p class="text-sm font-bold text-secondary-900 dark:text-white">
                                    {{ $supportExpiresAt ? \Carbon\Carbon::parse($supportExpiresAt)->format('M d, Y') : 'N/A' }}
                                </p>
                            </div>

                            <div class="bg-secondary-50 dark:bg-secondary-800/50 rounded-xl p-4">
                                <p class="text-xs font-medium text-secondary-500 dark:text-secondary-400 mb-2">
                                    {{ trans('common.license.current_version') }}
                                </p>
                                <p class="text-sm font-bold text-primary-700 dark:text-primary-400 font-mono">
                                    v{{ config('version.current', '1.0.0') }}
                                </p>
                            </div>

                            <div class="bg-secondary-50 dark:bg-secondary-800/50 rounded-xl p-4">
                                <p class="text-xs font-medium text-secondary-500 dark:text-secondary-400 mb-2">
                                    {{ trans('common.license.last_updated') }}
                                </p>
                                <p class="text-sm font-bold text-secondary-900 dark:text-white">
                                    @if($lastUpdate)
                                        {{ $lastUpdate->completed_at->format('M d, Y') }}
                                    @else
                                        {{ trans('common.never') }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        {{-- Support Expiry Warnings --}}
                        @if($supportExpiresSoon && $daysRemaining)
                            <div class="mt-4 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                                <div class="flex gap-3">
                                    <x-ui.icon icon="alert-triangle" class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0" />
                                    <div>
                                        <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                                            {{ trans('common.license.expiring_soon') }}
                                        </p>
                                        <p class="text-sm text-amber-700 dark:text-amber-300">
                                            {{ trans('common.license.expiring_soon_desc', ['days' => $daysRemaining]) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($licenseStatus === 'expired')
                            <div class="mt-4 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                                <div class="flex gap-3">
                                    <x-ui.icon icon="info" class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0" />
                                    <div>
                                        <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                                            {{ trans('common.license.status_expired') }}
                                        </p>
                                        <p class="text-sm text-amber-700 dark:text-amber-300">
                                            {{ trans('common.license.expired_message') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                @if($licenseStatus === 'active' || $licenseStatus === 'expired')
                    <div class="px-6 py-4 bg-secondary-50 dark:bg-secondary-800/50 border-t border-secondary-100 dark:border-secondary-800">
                        <div class="flex items-center justify-between gap-3">
                            @if($licenseStatus === 'expired')
                                <form method="POST" action="{{ route('admin.license.refresh') }}" x-data="{ refreshing: false }" x-on:submit="refreshing = true">
                                    @csrf
                                    <button type="submit" 
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-secondary-600 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-all duration-200 disabled:opacity-50"
                                        x-bind:disabled="refreshing">
                                        <span x-bind:class="{ 'animate-spin': refreshing }"><x-ui.icon icon="refresh-cw" class="w-4 h-4" /></span>
                                        <span x-show="!refreshing">{{ trans('common.license.refresh_status') }}</span>
                                        <span x-show="refreshing" x-cloak>{{ trans('common.license.refreshing') }}...</span>
                                    </button>
                                </form>
                            @else
                                <div></div>
                            @endif

                            <form method="POST" action="{{ route('admin.license.deactivate') }}" id="deactivate-form" x-data>
                                @csrf
                                <button type="button"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-600/5 dark:hover:bg-red-600/10 transition-all duration-200"
                                    onclick="if(confirm('{{ trans('common.license.deactivate_confirm') }}')) document.getElementById('deactivate-form').submit()">
                                    <x-ui.icon icon="trash-2" class="w-4 h-4" />
                                    {{ trans('common.license.deactivate') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Available Update Card --}}
            @if(session('updateAvailable'))
                @php
                    $update = session('updateAvailable');
                    $isCritical = $update['is_critical'] ?? false;
                @endphp
                
                @if($isCritical)
                    <div class="p-4 rounded-xl bg-red-600/5 dark:bg-red-600/10 border-2 border-red-600/20 dark:border-red-600/30 shadow-lg">
                        <div class="flex gap-3">
                            <x-ui.icon icon="shield-alert" class="w-5 h-5 text-red-600 flex-shrink-0" />
                            <div>
                                <p class="text-sm font-bold text-red-600 flex items-center gap-2">
                                    {{ trans('common.license.critical_update_title') }}
                                    <span class="px-2 py-0.5 rounded-full text-xs bg-red-600/10 text-red-600 font-semibold">v{{ $update['version'] }}</span>
                                </p>
                                <p class="text-sm text-red-600/80 dark:text-red-600/70">
                                    {{ trans('common.license.critical_update_desc') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
                
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 {{ $isCritical ? 'ring-2 ring-red-600/20' : '' }} overflow-hidden">
                    <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl {{ $isCritical ? 'bg-red-600/10 dark:bg-red-600/20' : 'bg-emerald-500/10 dark:bg-emerald-500/20' }} flex items-center justify-center">
                                <x-ui.icon :icon="$isCritical ? 'shield-alert' : 'download'" class="w-5 h-5 {{ $isCritical ? 'text-red-600' : 'text-emerald-500' }}" />
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-secondary-900 dark:text-white flex items-center gap-2">
                                    {{ trans('common.license.update_available') }}
                                    @if($isCritical)
                                        <span class="px-2 py-0.5 rounded-full text-xs bg-red-600/10 text-red-600 font-semibold">{{ trans('common.license.security') }}</span>
                                    @endif
                                </h2>
                                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                                    {{ trans('common.license.update_available_desc', ['version' => $update['version']]) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        @if(!empty($update['commits']))
                            <div class="space-y-3">
                                <div class="flex items-center gap-2 mb-4">
                                    <x-ui.icon icon="git-commit" class="w-4 h-4 text-primary-700" />
                                    <p class="text-xs font-bold text-secondary-500 uppercase tracking-wider">
                                        {{ trans('common.license.changes_in_update') }}
                                    </p>
                                </div>
                                
                                <div class="space-y-2 max-h-80 overflow-y-auto pr-2">
                                    @foreach($update['commits'] as $commit)
                                        <div class="flex gap-3 p-3 rounded-xl bg-secondary-50 dark:bg-secondary-800/50 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-all duration-200">
                                            <div class="flex-shrink-0 pt-0.5">
                                                <code class="px-2 py-1 text-xs font-mono bg-white dark:bg-secondary-900 text-primary-700 dark:text-primary-400 rounded-lg border border-secondary-200 dark:border-secondary-700">
                                                    {{ $commit['short_hash'] ?? substr($commit['hash'] ?? '', 0, 7) }}
                                                </code>
                                            </div>
                                            
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-secondary-900 dark:text-white leading-snug">
                                                    {{ $commit['message'] }}
                                                </p>
                                                <div class="flex items-center gap-3 mt-1 text-xs text-secondary-400 dark:text-secondary-500">
                                                    @if(!empty($commit['date']))
                                                        <span class="flex items-center gap-1">
                                                            <x-ui.icon icon="clock" class="w-3 h-3" />
                                                            {{ \Carbon\Carbon::parse($commit['date'])->diffForHumans() }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @elseif($update['changelog'] ?? null)
                            <div class="prose prose-sm dark:prose-invert max-w-none prose-headings:text-secondary-900 prose-a:text-blue-500 prose-strong:text-secondary-900">
                                {!! \Illuminate\Support\Str::markdown($update['changelog']) !!}
                            </div>
                        @endif
                    </div>

                    <div class="px-6 py-4 bg-secondary-50 dark:bg-secondary-800/50 border-t border-secondary-100 dark:border-secondary-800">
                        <form method="POST" action="{{ route('admin.license.install-update') }}" id="install-update-form">
                            @csrf
                            <input type="hidden" name="download_url" value="{{ $update['download_url'] }}">
                            <input type="hidden" name="version" value="{{ $update['version'] }}">
                            <input type="hidden" name="package_hash" value="{{ $update['package_hash'] }}">
                            
                            <button type="button"
                                id="install-update-btn"
                                class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:shadow-emerald-500/30 transition-all duration-200 active:scale-[0.98]">
                                <x-ui.icon icon="download" class="w-4 h-4" />
                                {{ trans('common.license.install_update') }}
                            </button>
                        </form>
                        
                        <script>
                            document.getElementById('install-update-btn').addEventListener('click', function() {
                                appConfirm(
                                    '{{ trans('common.license.confirm_update_title') }}',
                                    `<div class="space-y-3">
                                        <p>{{ trans('common.license.confirm_update_desc') }}</p>
                                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-700">
                                            <p class="text-sm text-amber-800 dark:text-amber-200 font-medium">{{ trans('common.license.keep_browser_open') }}</p>
                                            <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">{{ trans('common.license.update_browser_warning') }}</p>
                                        </div>
                                    </div>`,
                                    {
                                        btnConfirm: {
                                            text: '{{ trans('common.license.install_update') }}',
                                            click: function() {
                                                showFullscreenLoader('{{ trans('common.license.preparing_update') }}', '{{ trans('common.license.please_wait') }}');
                                                document.getElementById('install-update-form').submit();
                                            }
                                        },
                                        btnCancel: {
                                            text: '{{ trans('common.cancel') }}'
                                        }
                                    }
                                );
                            });
                        </script>
                    </div>
                </div>
            @endif

            {{-- Update History --}}
            @if($updates->isNotEmpty())
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden" x-data="{ showAllUpdates: false }">
                    <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
                        <div class="flex items-center gap-3">
                            <x-ui.icon icon="history" class="w-5 h-5 text-secondary-400" />
                            <div>
                                <h2 class="text-base font-semibold text-secondary-900 dark:text-white">
                                    {{ trans('common.license.update_history') }}
                                </h2>
                                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                                    {{ trans('common.license.update_history_desc') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div class="divide-y divide-secondary-100 dark:divide-secondary-800">
                            @foreach($updates as $index => $update)
                                <div class="flex items-start gap-4 py-4 first:pt-0 last:pb-0"
                                    @if($index >= 3) x-show="showAllUpdates" x-transition @endif>
                                    <div class="flex-shrink-0 w-10 h-10 rounded-xl {{ $update->status === 'completed' ? 'bg-emerald-500/10' : ($update->hasFailed() ? 'bg-red-600/10' : 'bg-accent-500/10') }} flex items-center justify-center">
                                        <x-ui.icon 
                                            :icon="$update->status === 'completed' ? 'check-circle' : ($update->hasFailed() ? 'x-circle' : 'loader')" 
                                            class="w-5 h-5 {{ $update->status === 'completed' ? 'text-emerald-500' : ($update->hasFailed() ? 'text-red-600' : 'text-accent-500') }}" />
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <p class="text-sm font-bold text-secondary-900 dark:text-white">
                                                v{{ $update->from_version }} → v{{ $update->to_version }}
                                            </p>
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold {{ $update->status === 'completed' ? 'bg-emerald-500/10 text-emerald-500' : ($update->hasFailed() ? 'bg-red-600/10 text-red-600' : 'bg-accent-500/10 text-accent-500') }}">
                                                {{ trans('common.license.status_' . $update->status) }}
                                            </span>
                                        </div>
                                        
                                        <p class="text-xs text-secondary-400 dark:text-secondary-500">
                                            {{ $update->completed_at ? $update->completed_at->format('M d, Y \a\t H:i') : $update->created_at->format('M d, Y \a\t H:i') }}
                                            @if($update->humanDuration())
                                                · {{ $update->humanDuration() }}
                                            @endif
                                        </p>

                                        @if($update->error_message)
                                            <p class="text-xs text-red-600 mt-2">
                                                {{ $update->error_message }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        @if($updates->count() > 3)
                            <div class="mt-4 text-center">
                                <button type="button" 
                                    class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors"
                                    x-show="!showAllUpdates"
                                    x-on:click="showAllUpdates = true">
                                    {{ trans('common.show_more') }}… ({{ $updates->count() - 3 }} {{ trans('common.more') }})
                                </button>
                                <button type="button" 
                                    class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors"
                                    x-show="showAllUpdates"
                                    x-cloak
                                    x-on:click="showAllUpdates = false">
                                    {{ trans('common.show_less') }}
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Available Backups --}}
            @if(!empty($backups))
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden" x-data="{ showAllBackups: false }">
                    <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
                        <div class="flex items-center gap-3">
                            <x-ui.icon icon="archive" class="w-5 h-5 text-secondary-400" />
                            <div>
                                <h2 class="text-base font-semibold text-secondary-900 dark:text-white">
                                    {{ trans('common.license.available_backups') }}
                                </h2>
                                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                                    {{ trans('common.license.available_backups_desc') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div class="divide-y divide-secondary-100 dark:divide-secondary-800">
                            @foreach($backups as $index => $backup)
                                <div class="flex items-center justify-between py-4 first:pt-0 last:pb-0 group"
                                    @if($index >= 3) x-show="showAllBackups" x-transition @endif>
                                    <div class="flex items-center gap-4">
                                        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-secondary-50 dark:bg-secondary-800 flex items-center justify-center">
                                            <x-ui.icon icon="archive-restore" class="w-5 h-5 text-accent-500" />
                                        </div>
                                        
                                        <div>
                                            <p class="text-sm font-bold text-secondary-900 dark:text-white">
                                                @if($backup['version'])
                                                    v{{ $backup['version'] }}
                                                @else
                                                    {{ trans('common.license.backup_unknown_version') }}
                                                @endif
                                            </p>
                                            <div class="flex items-center gap-2 text-xs text-secondary-400 dark:text-secondary-500">
                                                <span>{{ $backup['created_at']->format('M d, Y \a\t H:i') }}</span>
                                                <span>·</span>
                                                <span>{{ $backup['size'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        {{-- Restore Button: Scarlet Ghost/Outline Style (Caution Signal) --}}
                                        <button type="button"
                                            class="restore-backup-btn inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-red-600 border border-red-600/30 bg-white dark:bg-transparent hover:bg-red-600/5 dark:hover:bg-red-600/10 transition-all duration-200"
                                            data-backup-path="{{ $backup['path'] }}"
                                            data-backup-version="{{ $backup['version'] ?? trans('common.license.backup_unknown_version') }}">
                                            <x-ui.icon icon="rotate-ccw" class="w-4 h-4" />
                                            {{ trans('common.license.restore') }}
                                        </button>
                                        
                                        {{-- Delete Button: Hidden until hover --}}
                                        <button type="button"
                                            class="delete-backup-btn inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-secondary-400 dark:text-secondary-500 hover:text-red-600 hover:bg-red-600/5 dark:hover:bg-red-600/10 opacity-0 group-hover:opacity-100 transition-all duration-200"
                                            data-backup-path="{{ $backup['path'] }}"
                                            data-backup-version="{{ $backup['version'] ?? trans('common.license.backup_unknown_version') }}"
                                            data-backup-size="{{ $backup['size'] }}">
                                            <x-ui.icon icon="trash-2" class="w-4 h-4" />
                                            {{ trans('common.delete') }}
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        @if(count($backups) > 3)
                            <div class="mt-4 text-center">
                                <button type="button" 
                                    class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors"
                                    x-show="!showAllBackups"
                                    x-on:click="showAllBackups = true">
                                    {{ trans('common.show_more') }}… ({{ count($backups) - 3 }} {{ trans('common.more') }})
                                </button>
                                <button type="button" 
                                    class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors"
                                    x-show="showAllBackups"
                                    x-cloak
                                    x-on:click="showAllBackups = false">
                                    {{ trans('common.show_less') }}
                                </button>
                            </div>
                        @endif
                        
                        <div class="mt-5 pt-4 border-t border-secondary-100 dark:border-secondary-800">
                            <p class="flex items-start gap-2 text-xs text-secondary-500 dark:text-secondary-400">
                                <x-ui.icon icon="info" class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" />
                                <span>{{ trans('common.license.backup_management_note') }}</span>
                            </p>
                        </div>
                    </div>
                </div>
                
                {{-- Hidden form for restore --}}
                <form id="restore-backup-form" method="POST" action="{{ route('admin.license.restore-backup') }}" class="hidden">
                    @csrf
                    <input type="hidden" name="backup_path" id="restore-backup-path">
                </form>
                
                {{-- Hidden form for delete --}}
                <form id="delete-backup-form" method="POST" action="{{ route('admin.license.delete-backup') }}" class="hidden">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="backup_path" id="delete-backup-path">
                </form>
                
                <script>
                    // Restore backup handler
                    document.querySelectorAll('.restore-backup-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            const backupPath = this.dataset.backupPath;
                            const backupVersion = this.dataset.backupVersion;
                            
                            appConfirm(
                                '{{ trans('common.license.confirm_restore_title') }}',
                                `<div class="space-y-3">
                                    <p>{{ trans('common.license.confirm_restore_desc') }}</p>
                                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-700">
                                        <p class="text-sm font-medium text-amber-800 dark:text-amber-200">{{ trans('common.license.restoring_to_version') }}: ` + backupVersion + `</p>
                                    </div>
                                    <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-700">
                                        <p class="text-sm text-red-800 dark:text-red-200">{{ trans('common.license.restore_warning') }}</p>
                                    </div>
                                    <div class="p-3 bg-secondary-100 dark:bg-secondary-800 rounded-lg border border-secondary-200 dark:border-secondary-700">
                                        <p class="text-xs text-secondary-600 dark:text-secondary-400">{{ trans('common.license.restore_database_note') }}</p>
                                    </div>
                                </div>`,
                                {
                                    btnConfirm: {
                                        text: '{{ trans('common.license.restore_now') }}',
                                        click: function() {
                                            showFullscreenLoader('{{ trans('common.license.preparing_restore') }}', '{{ trans('common.license.please_wait') }}');
                                            document.getElementById('restore-backup-path').value = backupPath;
                                            document.getElementById('restore-backup-form').submit();
                                        }
                                    },
                                    btnCancel: {
                                        text: '{{ trans('common.cancel') }}'
                                    }
                                }
                            );
                        });
                    });
                    
                    // Delete backup handler
                    document.querySelectorAll('.delete-backup-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            const backupPath = this.dataset.backupPath;
                            const backupVersion = this.dataset.backupVersion;
                            const backupSize = this.dataset.backupSize;
                            
                            appConfirm(
                                '{{ trans('common.license.confirm_delete_backup_title') }}',
                                `<div class="space-y-3">
                                    <p>{{ trans('common.license.confirm_delete_backup_desc') }}</p>
                                    <div class="p-3 bg-secondary-100 dark:bg-secondary-800 rounded-lg">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-secondary-600 dark:text-secondary-400">{{ trans('common.version') }}:</span>
                                            <span class="font-medium text-secondary-900 dark:text-white">` + backupVersion + `</span>
                                        </div>
                                        <div class="flex items-center justify-between text-sm mt-1">
                                            <span class="text-secondary-600 dark:text-secondary-400">{{ trans('common.size') }}:</span>
                                            <span class="font-medium text-secondary-900 dark:text-white">` + backupSize + `</span>
                                        </div>
                                    </div>
                                    <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-700">
                                        <p class="text-sm text-red-800 dark:text-red-200">{{ trans('common.license.delete_backup_warning') }}</p>
                                    </div>
                                </div>`,
                                {
                                    btnConfirm: {
                                        text: '{{ trans('common.license.delete_backup_confirm') }}',
                                        class: 'bg-red-600 hover:bg-red-700 text-white',
                                        click: function() {
                                            document.getElementById('delete-backup-path').value = backupPath;
                                            document.getElementById('delete-backup-form').submit();
                                        }
                                    },
                                    btnCancel: {
                                        text: '{{ trans('common.cancel') }}'
                                    }
                                }
                            );
                        });
                    });
                </script>
            @endif

            {{-- Install from Package --}}
            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
                    <div class="flex items-center gap-3">
                        <x-ui.icon icon="hard-drive-upload" class="w-5 h-5 text-secondary-400" />
                        <div>
                            <h2 class="text-base font-semibold text-secondary-900 dark:text-white">
                                {{ trans('common.license.package_install') }}
                            </h2>
                            <p class="text-sm text-secondary-500 dark:text-secondary-400">
                                {{ trans('common.license.package_install_desc') }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="p-6">
                    @if(!empty($manualPackages))
                        <div class="space-y-3">
                            @foreach($manualPackages as $package)
                                <div class="flex items-center justify-between p-4 rounded-xl bg-secondary-50 dark:bg-secondary-800/50 border border-secondary-100 dark:border-secondary-800 group hover:border-secondary-200 dark:hover:border-secondary-700 transition-all duration-200">
                                    <div class="flex items-center gap-4">
                                        <div class="flex-shrink-0 w-10 h-10 rounded-xl {{ $package['is_upgrade'] ? 'bg-emerald-500/10' : ($package['is_downgrade'] ? 'bg-amber-500/10' : 'bg-primary-700/10') }} flex items-center justify-center">
                                            <x-ui.icon 
                                                :icon="$package['is_upgrade'] ? 'arrow-up-circle' : ($package['is_downgrade'] ? 'arrow-down-circle' : 'refresh-cw')" 
                                                class="w-5 h-5 {{ $package['is_upgrade'] ? 'text-emerald-500' : ($package['is_downgrade'] ? 'text-amber-500' : 'text-primary-700') }}" />
                                        </div>
                                        
                                        <div>
                                            <div class="flex items-center gap-2">
                                                @if($package['version'])
                                                    <p class="text-sm font-bold text-secondary-900 dark:text-white font-mono">
                                                        v{{ $package['version'] }}
                                                    </p>
                                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                                        {{ $package['is_upgrade'] ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : '' }}
                                                        {{ $package['is_same'] ? 'bg-primary-700/10 text-primary-700 dark:text-primary-400' : '' }}
                                                        {{ $package['is_downgrade'] ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400' : '' }}
                                                    ">
                                                        @if($package['is_upgrade'])
                                                            {{ trans('common.license.package_install_upgrade') }}
                                                        @elseif($package['is_same'])
                                                            {{ trans('common.license.package_install_reinstall') }}
                                                        @elseif($package['is_downgrade'])
                                                            {{ trans('common.license.package_install_downgrade') }}
                                                        @endif
                                                    </span>
                                                @else
                                                    <p class="text-sm font-medium text-secondary-500 dark:text-secondary-400">
                                                        {{ trans('common.license.package_install_unrecognized') }}
                                                    </p>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-2 mt-1 text-xs text-secondary-400 dark:text-secondary-500">
                                                <span class="truncate max-w-[200px]" title="{{ $package['filename'] }}">{{ $package['filename'] }}</span>
                                                <span>·</span>
                                                <span>{{ $package['size'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    @if($package['version'])
                                        <button type="button"
                                            class="manual-update-btn inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 active:scale-[0.98]
                                                {{ $package['is_upgrade'] 
                                                    ? 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:shadow-emerald-500/30' 
                                                    : 'bg-secondary-100 dark:bg-secondary-700 hover:bg-secondary-200 dark:hover:bg-secondary-600 text-secondary-700 dark:text-secondary-200' }}"
                                            data-filename="{{ $package['filename'] }}"
                                            data-version="{{ $package['version'] }}"
                                            data-type="{{ $package['is_upgrade'] ? 'upgrade' : ($package['is_downgrade'] ? 'downgrade' : 'reinstall') }}">
                                            <x-ui.icon icon="play" class="w-4 h-4" />
                                            {{ trans('common.license.package_install_apply') }}
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-secondary-100 dark:bg-secondary-800 mb-3">
                                <x-ui.icon icon="package" class="w-6 h-6 text-secondary-400" />
                            </div>
                            <p class="text-sm font-medium text-secondary-500 dark:text-secondary-400">
                                {{ trans('common.license.package_install_no_packages') }}
                            </p>
                        </div>
                    @endif
                    
                    <div class="mt-5 pt-4 border-t border-secondary-100 dark:border-secondary-800">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400 mb-2">
                            {{ trans('common.license.package_install_how') }}
                        </p>
                        <code class="block px-3 py-2 rounded-lg bg-secondary-950 dark:bg-secondary-950 text-emerald-400 text-xs font-mono select-all">{{ trans('common.license.package_install_path') }}</code>
                    </div>
                </div>
            </div>
            
            {{-- Hidden form for manual update --}}
            <form id="manual-update-form" method="POST" action="{{ route('admin.license.install-manual-update') }}" class="hidden">
                @csrf
                <input type="hidden" name="filename" id="manual-update-filename">
            </form>
            
            <script>
                document.querySelectorAll('.manual-update-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const filename = this.dataset.filename;
                        const version = this.dataset.version;
                        const type = this.dataset.type;
                        
                        const typeLabel = type === 'upgrade' ? '{{ trans('common.license.package_install_upgrade') }}'
                            : type === 'downgrade' ? '{{ trans('common.license.package_install_downgrade') }}'
                            : '{{ trans('common.license.package_install_reinstall') }}';
                        
                        appConfirm(
                            '{{ trans('common.license.package_install_confirm_title') }}',
                            `<div class="space-y-3">
                                <p>{{ trans('common.license.package_install_confirm_desc') }}</p>
                                <div class="p-3 bg-secondary-100 dark:bg-secondary-800 rounded-lg">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-secondary-600 dark:text-secondary-400">{{ trans('common.version') }}:</span>
                                        <span class="font-bold font-mono text-secondary-900 dark:text-white">v` + version + `</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm mt-1">
                                        <span class="text-secondary-600 dark:text-secondary-400">{{ trans('common.license.status') }}:</span>
                                        <span class="font-medium text-secondary-900 dark:text-white">` + typeLabel + `</span>
                                    </div>
                                </div>
                                <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-700">
                                    <p class="text-sm text-amber-800 dark:text-amber-200 font-medium">{{ trans('common.license.keep_browser_open') }}</p>
                                    <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">{{ trans('common.license.update_browser_warning') }}</p>
                                </div>
                            </div>`,
                            {
                                btnConfirm: {
                                    text: typeLabel + ' → v' + version,
                                    click: function() {
                                        showFullscreenLoader('{{ trans('common.license.preparing_update') }}', '{{ trans('common.license.please_wait') }}');
                                        document.getElementById('manual-update-filename').value = filename;
                                        document.getElementById('manual-update-form').submit();
                                    }
                                },
                                btnCancel: {
                                    text: '{{ trans('common.cancel') }}'
                                }
                            }
                        );
                    });
                });
            </script>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Help & Documentation Card --}}
            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
                    <div class="flex items-center gap-3">
                        <x-ui.icon icon="help-circle" class="w-5 h-5 text-secondary-400" />
                        <h2 class="text-base font-semibold text-secondary-900 dark:text-white">
                            {{ trans('common.license.help_title') }}
                        </h2>
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="space-y-5">
                        {{-- FAQ Item 1: Purchase Code --}}
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 mt-0.5">
                                <x-ui.icon icon="info" class="w-4 h-4 text-secondary-400" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-secondary-900 dark:text-white mb-1">
                                    {{ trans('common.license.where_purchase_code') }}
                                </p>
                                <p class="text-sm text-secondary-500 dark:text-secondary-400 leading-relaxed">
                                    {{ trans('common.license.where_purchase_code_desc') }}
                                </p>
                            </div>
                        </div>

                        {{-- FAQ Item 2: Update Process --}}
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 mt-0.5">
                                <x-ui.icon icon="info" class="w-4 h-4 text-secondary-400" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-secondary-900 dark:text-white mb-1">
                                    {{ trans('common.license.update_process') }}
                                </p>
                                <p class="text-sm text-secondary-500 dark:text-secondary-400 leading-relaxed">
                                    {{ trans('common.license.update_process_desc') }}
                                </p>
                            </div>
                        </div>

                        {{-- FAQ Item 3: License Requirements --}}
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 mt-0.5">
                                <x-ui.icon icon="info" class="w-4 h-4 text-secondary-400" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-secondary-900 dark:text-white mb-1">
                                    {{ trans('common.license.code_works_without_license') }}
                                </p>
                                <p class="text-sm text-secondary-500 dark:text-secondary-400 leading-relaxed">
                                    {{ trans('common.license.code_works_without_license_desc') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- System Information --}}
            <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                <div class="px-6 py-5 border-b border-secondary-100 dark:border-secondary-800">
                    <div class="flex items-center gap-3">
                        <x-ui.icon icon="cpu" class="w-5 h-5 text-secondary-400" />
                        <h2 class="text-base font-semibold text-secondary-900 dark:text-white">
                            {{ trans('common.license.system_info') }}
                        </h2>
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="space-y-4">
                        {{-- PHP Version --}}
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-secondary-600 dark:text-secondary-400">PHP Version</span>
                            <span class="text-sm font-bold text-secondary-900 dark:text-white font-mono bg-secondary-50 dark:bg-secondary-800 px-2.5 py-1 rounded-lg">{{ PHP_VERSION }}</span>
                        </div>
                        
                        {{-- Laravel Version --}}
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-secondary-600 dark:text-secondary-400">Laravel Version</span>
                            <span class="text-sm font-bold text-secondary-900 dark:text-white font-mono bg-secondary-50 dark:bg-secondary-800 px-2.5 py-1 rounded-lg">{{ app()->version() }}</span>
                        </div>
                        
                        {{-- App Version --}}
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-secondary-600 dark:text-secondary-400">{{ config('default.app_name') }}</span>
                            <span class="text-sm font-bold text-primary-700 dark:text-primary-400 font-mono bg-primary-700/5 dark:bg-primary-500/10 px-2.5 py-1 rounded-lg">v{{ config('version.current') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Fullscreen Loader Overlay --}}
<div id="fullscreen-loader" class="fixed inset-0 z-[99999] hidden">
    {{-- Frosted Glass Backdrop --}}
    <div class="absolute inset-0 bg-secondary-950/90 backdrop-blur-xl"></div>
    
    {{-- Centered Content --}}
    <div class="relative flex flex-col items-center justify-center min-h-screen p-4">
        {{-- App Icon --}}
        <div class="mb-8">
            <div class="w-20 h-20 rounded-2xl bg-primary-700/20 flex items-center justify-center ring-1 ring-primary-700/30">
                <span class="text-primary-700 font-bold text-3xl">{{ substr(config('app.name', 'R'), 0, 1) }}</span>
            </div>
        </div>
        
        {{-- Premium SVG Spinner (Deep Sapphire Blue) --}}
        <div class="mb-6">
            <div class="relative">
                <svg class="w-16 h-16 animate-spin" viewBox="0 0 50 50">
                    <circle class="opacity-20" cx="25" cy="25" r="20" stroke="#2563EB" stroke-width="4" fill="none"></circle>
                    <circle cx="25" cy="25" r="20" stroke="#2563EB" stroke-width="4" fill="none" stroke-linecap="round" stroke-dasharray="31.4 94.2" transform="rotate(-90 25 25)"></circle>
                </svg>
            </div>
        </div>
        
        {{-- Text --}}
        <div class="text-center max-w-md">
            <h3 id="loader-title" class="text-xl font-bold text-white mb-2">{{ trans('common.license.preparing_update') }}</h3>
            <p id="loader-subtitle" class="text-sm text-secondary-400 mb-2">{{ trans('common.license.please_wait') }}</p>
            <p id="loader-detail" class="text-xs text-secondary-500 mb-4">{{ trans('common.license.backup_in_progress_note') }}</p>
            
            {{-- Keep Browser Open Warning --}}
            <div class="p-3 bg-accent-500/10 rounded-xl border border-accent-500/20 space-y-1">
                <p class="text-xs text-accent-500 font-semibold">
                    <x-ui.icon icon="alert-triangle" class="w-3.5 h-3.5 inline-block mr-1" />
                    {{ trans('common.license.keep_browser_open') }}
                </p>
                <p class="text-xs text-accent-500/70">
                    {{ trans('common.license.patience_note') }}
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    function showFullscreenLoader(title, subtitle) {
        const loader = document.getElementById('fullscreen-loader');
        const loaderTitle = document.getElementById('loader-title');
        const loaderSubtitle = document.getElementById('loader-subtitle');
        
        if (title) loaderTitle.textContent = title;
        if (subtitle) loaderSubtitle.textContent = subtitle;
        
        loader.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    function hideFullscreenLoader() {
        const loader = document.getElementById('fullscreen-loader');
        loader.classList.add('hidden');
        document.body.style.overflow = '';
    }
</script>
@stop
