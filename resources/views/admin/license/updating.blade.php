{{--
Reward Loyalty - Update In Progress

Purpose:
Shows a beautiful loading screen while the update is being applied in the
background. Automatically polls for completion and redirects when done.
--}}

@extends('admin.layouts.default')

@section('page_title', trans('common.license.updating_title') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="flex items-center justify-center min-h-[calc(100vh-8rem)]">
    <div class="w-full max-w-lg p-6">
        <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
            <div class="px-6 py-5 border-b border-secondary-200 dark:border-secondary-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <x-ui.icon icon="download" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-secondary-900 dark:text-white">
                            {{ trans('common.license.updating_title') }}
                        </h2>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400">
                            {{ trans('common.license.updating_description', ['version' => $version]) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="space-y-6">
                    {{-- Progress Animation --}}
                    <div class="flex items-center justify-center py-8">
                        <div class="relative">
                            <div class="w-20 h-20 border-4 border-primary-200 dark:border-primary-900 border-t-primary-600 dark:border-t-primary-400 rounded-full animate-spin"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <x-ui.icon icon="download" class="w-8 h-8 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                    </div>

                    {{-- Status Messages --}}
                    <div class="space-y-3 text-center">
                        <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
                            {{ trans('common.license.update_in_progress') }}
                        </h3>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400">
                            {{ trans('common.license.update_wait_message') }}
                        </p>
                    </div>

                    {{-- Progress Steps --}}
                    <div class="space-y-2 max-w-sm mx-auto">
                        <div class="flex items-center gap-2 text-sm">
                            <x-ui.icon icon="check-circle" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                            <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.license.step_download') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <x-ui.icon icon="check-circle" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                            <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.license.step_backup') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <div class="w-4 h-4 border-2 border-primary-600 dark:border-primary-400 border-t-transparent rounded-full animate-spin"></div>
                            <span class="text-secondary-900 dark:text-white font-medium">{{ trans('common.license.step_installing') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm opacity-50">
                            <x-ui.icon icon="circle" class="w-4 h-4 text-secondary-400" />
                            <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.license.step_migrations') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm opacity-50">
                            <x-ui.icon icon="circle" class="w-4 h-4 text-secondary-400" />
                            <span class="text-secondary-500 dark:text-secondary-400">{{ trans('common.license.step_cleanup') }}</span>
                        </div>
                    </div>

                    {{-- Important Notice --}}
                    <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                        <div class="flex gap-3">
                            <x-ui.icon icon="alert-triangle" class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0" />
                            <div>
                                <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                                    {{ trans('common.license.keep_browser_open') }}
                                </p>
                                <p class="text-sm text-amber-700 dark:text-amber-300">
                                    {{ trans('common.license.update_browser_warning') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Auto-reload script --}}
<script>
    let consecutiveErrors = 0;
    let checkCount = 0;
    const maxChecks = 30; // 60 seconds max
    const startTime = Date.now();
    
    console.log('=== UPDATE PROGRESS PAGE LOADED ===');
    console.log('Start time:', new Date().toISOString());
    console.log('Check status URL:', '{{ route('admin.license.check-status') }}');
    console.log('Redirect URL:', '{{ route('admin.license.index') }}');
    
    const checkUpdateStatus = () => {
        checkCount++;
        const elapsed = Math.round((Date.now() - startTime) / 1000);
        
        console.log(`\n[Check #${checkCount}] Elapsed: ${elapsed}s, Errors: ${consecutiveErrors}`);
        
        // After 30 checks (60 seconds), assume success and redirect
        if (checkCount >= maxChecks) {
            console.log('❌ Max checks reached, forcing redirect...');
            window.location.href = '{{ route('admin.license.index') }}';
            return;
        }
        
        const checkStartTime = Date.now();
        fetch('{{ route('admin.license.check-status') }}', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
            signal: AbortSignal.timeout(5000) // 5 second timeout per request
        })
            .then(response => {
                const fetchDuration = Date.now() - checkStartTime;
                console.log(`  ✓ Fetch completed in ${fetchDuration}ms, status: ${response.status}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('  ✓ Response data:', data);
                consecutiveErrors = 0; // Reset error counter on success
                
                if (data.completed) {
                    console.log('✅ UPDATE COMPLETED! Redirecting in 1 second...');
                    // Add a small delay to ensure files are flushed
                    setTimeout(() => {
                        console.log('🔄 Executing redirect now...');
                        window.location.href = '{{ route('admin.license.index') }}';
                    }, 1000);
                } else if (data.failed) {
                    console.error('❌ Update failed:', data.error);
                    window.location.href = '{{ route('admin.license.index') }}?error=' + encodeURIComponent(data.error);
                } else {
                    console.log('  ⏳ Still in progress...');
                }
            })
            .catch(error => {
                consecutiveErrors++;
                const fetchDuration = Date.now() - checkStartTime;
                console.error(`  ❌ Fetch failed after ${fetchDuration}ms (${consecutiveErrors}/${maxChecks}):`, error.message);
                
                // After 5 consecutive errors, assume success and redirect
                if (consecutiveErrors >= 5) {
                    console.log('⚠️ Multiple errors detected, assuming update completed, forcing redirect...');
                    window.location.href = '{{ route('admin.license.index') }}';
                }
            });
    };

    // Poll every 2 seconds
    const pollInterval = setInterval(() => {
        checkUpdateStatus();
    }, 2000);
    
    // Initial check after 1 second
    setTimeout(() => {
        console.log('Starting initial check...');
        checkUpdateStatus();
    }, 1000);
</script>
@stop

