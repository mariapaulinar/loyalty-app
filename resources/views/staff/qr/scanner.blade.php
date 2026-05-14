@extends('staff.layouts.default')

@section('page_title', trans('common.scan_qr') . config('default.page_title_delimiter') . trans('common.dashboard') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-md mx-auto px-4 py-8 md:py-14">
    {{-- Header --}}
    <x-ui.page-header
        icon="qr-code"
        :title="trans('common.scan_qr')"
        :description="trans('common.qr_scanner_info')"
        compact
    />

    {{-- Scanner Container --}}
    <div class="relative mt-8 mb-6">
        {{-- Video Container with Scanner Frame --}}
        <div class="relative aspect-square w-full rounded-2xl overflow-hidden bg-secondary-900 border border-secondary-200 dark:border-secondary-800">
            {{-- Corner Decorations (Scanner Frame Effect) --}}
            <div class="absolute inset-0 z-10 pointer-events-none">
                {{-- Top Left Corner --}}
                <div class="absolute top-4 left-4 w-12 h-12 border-l-4 border-t-4 border-primary-500 rounded-tl-lg"></div>
                {{-- Top Right Corner --}}
                <div class="absolute top-4 right-4 w-12 h-12 border-r-4 border-t-4 border-primary-500 rounded-tr-lg"></div>
                {{-- Bottom Left Corner --}}
                <div class="absolute bottom-4 left-4 w-12 h-12 border-l-4 border-b-4 border-primary-500 rounded-bl-lg"></div>
                {{-- Bottom Right Corner --}}
                <div class="absolute bottom-4 right-4 w-12 h-12 border-r-4 border-b-4 border-primary-500 rounded-br-lg"></div>
                
                {{-- Scanning Line Animation --}}
                <div class="scanning-line absolute left-4 right-4 h-0.5 bg-gradient-to-r from-transparent via-primary-500 to-transparent"></div>
            </div>
            
            {{-- Video Element --}}
            <video id="video" class="w-full h-full object-cover"></video>
            
            {{-- Placeholder when camera not active --}}
            <div id="camera-placeholder" class="absolute inset-0 flex flex-col items-center justify-center bg-secondary-800 text-secondary-400">
                <x-ui.icon icon="camera" class="w-16 h-16 mb-4 opacity-50" />
                <p class="text-sm">{{ trans('common.camera_will_appear_here') ?? 'Camera will appear here' }}</p>
            </div>
        </div>
    </div>

    {{-- Scan Button --}}
    <div class="mb-6">
        <button type="button" 
                class="scan-qr w-full flex items-center justify-center gap-2.5 px-6 py-3.5
                       text-sm font-semibold text-white
                       bg-primary-600 hover:bg-primary-500
                       rounded-xl shadow-sm hover:shadow-md
                       focus:outline-none focus:ring-2 focus:ring-primary-500/20
                       transition-all duration-200 active:scale-[0.98]">
            <x-ui.icon icon="qr-code" class="w-5 h-5" data-scan-icon />
            <x-ui.icon icon="x-mark" class="w-5 h-5" style="display:none" data-stop-icon />
            <span data-scan-label 
                  data-idle-text="{{ trans('common.scan_qr') }}" 
                  data-scanning-text="{{ trans('common.scanning') ?? 'Scanning… Tap to stop' }}">{{ trans('common.scan_qr') }}</span>
        </button>
    </div>

    {{-- Status Messages --}}
    <div class="space-y-3">
        {{-- Info Message --}}
        <div id="scanner-info" 
             class="hide-on-scan flex items-center gap-4 p-4 
                    bg-white dark:bg-secondary-900 
                    rounded-xl border border-secondary-200 dark:border-secondary-800">
            <x-ui.icon icon="info" class="w-5 h-5 text-secondary-400 shrink-0"/>
            <p class="text-sm text-secondary-600 dark:text-secondary-300">
                {{ trans('common.qr_scanner_info') }}
            </p>
        </div>

        {{-- Success Message --}}
        <div id="code-found" 
             class="hidden flex items-center gap-4 p-4 
                    bg-emerald-50 dark:bg-emerald-950/30 
                    rounded-xl border border-emerald-200 dark:border-emerald-800">
            <x-ui.icon icon="check" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0"/>
            <div>
                <p class="font-medium text-emerald-800 dark:text-emerald-300">
                    {{ trans('common.code_found') }}
                </p>
                <p class="text-sm text-emerald-600 dark:text-emerald-400">
                    {{ trans('common.redirecting') ?? 'Redirecting...' }}
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    .scanning-line {
        animation: scan 2s ease-in-out infinite;
    }
    
    @keyframes scan {
        0%, 100% {
            top: 1rem;
            opacity: 0;
        }
        10% {
            opacity: 1;
        }
        90% {
            opacity: 1;
        }
        100% {
            top: calc(100% - 1rem);
            opacity: 0;
        }
    }
    
    #video:not([src=""]) ~ #camera-placeholder,
    #video.active ~ #camera-placeholder {
        display: none;
    }

    /* Scanning state button styles */
    .scan-qr.scanning {
        background-color: var(--color-secondary-600, #52525b);
        animation: pulse-scanning 2s ease-in-out infinite;
    }
    .scan-qr.scanning:hover {
        background-color: var(--color-secondary-500, #71717a);
    }
    @keyframes pulse-scanning {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.85; }
    }
</style>

<script>
window.onload = function() {
    const codeFound = document.getElementById('code-found');
    const scannerInfo = document.getElementById('scanner-info');
    const cameraPlaceholder = document.getElementById('camera-placeholder');
    const video = document.getElementById('video');

    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            codeFound.classList.add('hidden');
            scannerInfo.classList.remove('hidden');
        }
    });
    
    video.addEventListener('loadedmetadata', function() {
        if (cameraPlaceholder) {
            cameraPlaceholder.style.display = 'none';
        }
        video.classList.add('active');
    });
};
</script>
@stop
