{{--
 Reward Loyalty - Proprietary Software
 Copyright (c) 2025 NowSquare. All rights reserved.
 See LICENSE file for terms.
 
 QR Modal for Data Tables (Event-Based)
 Listens for 'open-qr-modal-data' events from data table QR buttons
--}}

<div x-data="{ 
    show: false, 
    title: '', 
    subtitle: '', 
    url: '',
    copyText: '{{ trans('common.copy_link') }}',
    copiedText: '{{ trans('common.link_copied') }}'
}" 
     @open-qr-modal-data.window="
        title = $event.detail.title;
        subtitle = $event.detail.subtitle;
        url = $event.detail.url;
        show = true;
        $nextTick(() => window.processQrCodes());
     "
     style="display: none;" 
     x-show="show"
     x-cloak>
    
    <div @click.self="show = false" 
         style="position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; z-index: 9999 !important;"
         class="flex items-center justify-center px-4 bg-black/80 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0">

        <div @click.away="show = false"
             class="relative bg-white dark:bg-secondary-900 w-full max-w-md rounded-3xl p-8 shadow-2xl"
             x-transition:enter="transition ease-out duration-300" 
             x-transition:enter-start="opacity-0 scale-90 translate-y-4" 
             x-transition:enter-end="opacity-100 scale-100 translate-y-0">
            
            <button type="button" @click="show = false"
                    class="absolute top-4 right-4 w-10 h-10 rounded-full bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center hover:bg-secondary-200 dark:hover:bg-secondary-700 transition-all hover:scale-110">
                <svg class="w-5 h-5 text-secondary-600 dark:text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
            
            <div class="flex flex-col items-center space-y-6">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 shadow-xl shadow-primary-500/25">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                </div>
                
                <div class="text-center space-y-2">
                    <h3 class="text-2xl font-bold text-secondary-900 dark:text-white" x-text="title"></h3>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400" x-text="subtitle"></p>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-inner border border-secondary-200 dark:border-secondary-300">
                    <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" 
                         :data-qr-url="url" 
                         data-qr-color-light="#FCFCFC" 
                         data-qr-color-dark="#1F1F1F"
                         alt="QR Code" 
                         class="w-64 h-64" />
                </div>

                <div class="grid grid-cols-3 gap-3 w-full">
                    <button type="button" @click="const el = document.createElement('textarea'); el.value = url; el.style.position = 'fixed'; el.style.opacity = '0'; document.body.appendChild(el); el.select(); document.execCommand('copy'); document.body.removeChild(el); $el.querySelector('span').textContent = copiedText; setTimeout(() => $el.querySelector('span').textContent = copyText, 2000)"
                            class="inline-flex flex-col items-center gap-2 px-4 py-3 rounded-xl bg-white dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all shadow-sm hover:shadow group">
                        <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        <span class="text-xs" x-text="copyText"></span>
                    </button>
                    <button type="button" @click="
                        const slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); 
                        const img = $el.closest('.relative').querySelector('img[data-qr-url]');
                        
                        // Convert SVG to high-res PNG
                        const canvas = document.createElement('canvas');
                        const size = 1024; // High resolution
                        canvas.width = size;
                        canvas.height = size;
                        const ctx = canvas.getContext('2d');
                        
                        // Create a new image from the SVG
                        const svgImg = new Image();
                        svgImg.onload = function() {
                            ctx.fillStyle = '#FFFFFF';
                            ctx.fillRect(0, 0, size, size);
                            ctx.drawImage(svgImg, 0, 0, size, size);
                            
                            // Download as PNG
                            canvas.toBlob(function(blob) {
                                const url = URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = 'qr-' + slug + '.png';
                                a.click();
                                URL.revokeObjectURL(url);
                            }, 'image/png');
                        };
                        svgImg.src = img.src;
                    "
                            class="inline-flex flex-col items-center gap-2 px-4 py-3 rounded-xl bg-white dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all shadow-sm hover:shadow group">
                        <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        <span class="text-xs">{{ trans('common.download') }}</span>
                    </button>
                    <a :href="url" target="_blank"
                       class="inline-flex flex-col items-center gap-2 px-4 py-3 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 text-sm font-medium text-white hover:from-primary-600 hover:to-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all shadow-md hover:shadow-lg hover:scale-105 group">
                        <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                        <span class="text-xs">{{ trans('common.visit') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
