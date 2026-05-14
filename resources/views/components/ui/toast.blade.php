{{--
    Toast Notification Component
    
    Premium toast notifications with smooth animations.
    Inspired by Linear and Vercel's notification systems.
--}}

@if (session('toast'))
    @php
        $toastType = session('toast')['type'];
        $toastSize = session('toast')['size'] ?? 'sm';
        $toastText = session('toast')['text'];
        
        $config = [
            'success' => [
                'icon' => 'check',
                'iconBg' => 'bg-emerald-100 dark:bg-emerald-500/20',
                'iconColor' => 'text-emerald-600 dark:text-emerald-400',
                'border' => 'border-emerald-200/50 dark:border-emerald-500/20',
                'glow' => 'shadow-emerald-500/10',
            ],
            'warning' => [
                'icon' => 'alert-triangle',
                'iconBg' => 'bg-amber-100 dark:bg-amber-500/20',
                'iconColor' => 'text-amber-600 dark:text-amber-400',
                'border' => 'border-amber-200/50 dark:border-amber-500/20',
                'glow' => 'shadow-amber-500/10',
            ],
            'danger' => [
                'icon' => 'x-circle',
                'iconBg' => 'bg-red-100 dark:bg-red-500/20',
                'iconColor' => 'text-red-600 dark:text-red-400',
                'border' => 'border-red-200/50 dark:border-red-500/20',
                'glow' => 'shadow-red-500/10',
            ],
            'error' => [ // Alias for 'danger'
                'icon' => 'x-circle',
                'iconBg' => 'bg-red-100 dark:bg-red-500/20',
                'iconColor' => 'text-red-600 dark:text-red-400',
                'border' => 'border-red-200/50 dark:border-red-500/20',
                'glow' => 'shadow-red-500/10',
            ],
        ];
        
        $c = $config[$toastType] ?? $config['success'];
    @endphp

    <div id="toast-{{ $toastType }}"
         class="fixed top-6 right-6 z-50 
                flex items-center gap-3 w-96 max-w-[calc(100vw-3rem)]
                p-4 pr-12
                bg-white dark:bg-secondary-900
                border {{ $c['border'] }}
                rounded-2xl shadow-2xl {{ $c['glow'] }}
                animate-toast-in"
         role="alert">
        
        {{-- Icon --}}
        <div class="flex-shrink-0 w-10 h-10 rounded-xl {{ $c['iconBg'] }} {{ $c['iconColor'] }} flex items-center justify-center">
            <x-ui.icon icon="{{ $c['icon'] }}" class="w-5 h-5" />
        </div>
        
        {{-- Message --}}
        <p class="flex-1 text-sm font-medium text-secondary-900 dark:text-white">{{ $toastText }}</p>
        
        {{-- Close Button --}}
        <button type="button"
                class="absolute top-1/2 right-3 -translate-y-1/2
                       w-8 h-8 rounded-lg
                       flex items-center justify-center
                       text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200
                       hover:bg-secondary-100 dark:hover:bg-secondary-700/50
                       transition-colors duration-200"
                data-dismiss-target="#toast-{{ $toastType }}" 
                aria-label="Close">
            <span class="sr-only">{{ trans('common.close') }}</span>
            <x-ui.icon icon="x" class="w-4 h-4" />
        </button>
        
        {{-- Progress bar --}}
        <div class="absolute bottom-0 left-4 right-4 h-0.5 bg-secondary-200 dark:bg-secondary-700 rounded-full overflow-hidden">
            <div class="h-full {{ str_replace('text-', 'bg-', $c['iconColor']) }} animate-toast-progress"></div>
        </div>
    </div>

    <style>
        @keyframes toast-in {
            0% { 
                opacity: 0; 
                transform: translateX(100%) scale(0.95); 
            }
            100% { 
                opacity: 1; 
                transform: translateX(0) scale(1); 
            }
        }
        
        @keyframes toast-progress {
            0% { width: 100%; }
            100% { width: 0%; }
        }
        
        .animate-toast-in {
            animation: toast-in 0.3s ease-out forwards;
        }
        
        .animate-toast-progress {
            animation: toast-progress 5s linear forwards;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const toastEl = document.getElementById('toast-{{ $toastType }}');
                if (toastEl) {
                    toastEl.style.transition = 'all 0.3s ease-out';
                    toastEl.style.opacity = '0';
                    toastEl.style.transform = 'translateX(100%) scale(0.95)';
                    setTimeout(() => toastEl.remove(), 300);
                }
            }, 5000);
        });
    </script>
@endif
