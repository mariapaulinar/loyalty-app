{{--
Premium Alert Component

Modern dismissable alerts with glassmorphism and smooth animations.
Inspired by Linear and Vercel's notification systems.

@props
- type: 'success'|'error'|'warning'|'info' (default: 'info')
- title: string - Optional title
- dismissable: boolean (default: true)
- icon: string - Override default icon
--}}

@props([
    'type' => 'info',
    'title' => null,
    'dismissable' => true,
    'icon' => null,
])

@php
$config = [
    'success' => [
        'bg' => 'bg-emerald-50/80 dark:bg-emerald-500/10',
        'border' => 'border-emerald-200/50 dark:border-emerald-500/20',
        'icon_bg' => 'bg-gradient-to-br from-emerald-500 to-teal-600',
        'icon_color' => 'text-white',
        'title_color' => 'text-emerald-900 dark:text-emerald-200',
        'text_color' => 'text-emerald-700 dark:text-emerald-300',
        'close_color' => 'text-emerald-500 hover:text-emerald-700 hover:bg-emerald-100 dark:text-emerald-400 dark:hover:text-emerald-200 dark:hover:bg-emerald-500/20',
        'default_icon' => 'check',
        'shadow' => 'shadow-emerald-500/10',
    ],
    'error' => [
        'bg' => 'bg-red-50/80 dark:bg-red-500/10',
        'border' => 'border-red-200/50 dark:border-red-500/20',
        'icon_bg' => 'bg-gradient-to-br from-red-500 to-rose-600',
        'icon_color' => 'text-white',
        'title_color' => 'text-red-900 dark:text-red-200',
        'text_color' => 'text-red-700 dark:text-red-300',
        'close_color' => 'text-red-500 hover:text-red-700 hover:bg-red-100 dark:text-red-400 dark:hover:text-red-200 dark:hover:bg-red-500/20',
        'default_icon' => 'x',
        'shadow' => 'shadow-red-500/10',
    ],
    'warning' => [
        'bg' => 'bg-amber-50/80 dark:bg-amber-500/10',
        'border' => 'border-amber-200/50 dark:border-amber-500/20',
        'icon_bg' => 'bg-gradient-to-br from-amber-500 to-orange-600',
        'icon_color' => 'text-white',
        'title_color' => 'text-amber-900 dark:text-amber-200',
        'text_color' => 'text-amber-700 dark:text-amber-300',
        'close_color' => 'text-amber-500 hover:text-amber-700 hover:bg-amber-100 dark:text-amber-400 dark:hover:text-amber-200 dark:hover:bg-amber-500/20',
        'default_icon' => 'alert-triangle',
        'shadow' => 'shadow-amber-500/10',
    ],
    'info' => [
        'bg' => 'bg-sky-50/80 dark:bg-sky-500/10',
        'border' => 'border-sky-200/50 dark:border-sky-500/20',
        'icon_bg' => 'bg-gradient-to-br from-sky-500 to-blue-600',
        'icon_color' => 'text-white',
        'title_color' => 'text-sky-900 dark:text-sky-200',
        'text_color' => 'text-sky-700 dark:text-sky-300',
        'close_color' => 'text-sky-500 hover:text-sky-700 hover:bg-sky-100 dark:text-sky-400 dark:hover:text-sky-200 dark:hover:bg-sky-500/20',
        'default_icon' => 'info',
        'shadow' => 'shadow-sky-500/10',
    ],
];

$c = $config[$type] ?? $config['info'];
$alertIcon = $icon ?? $c['default_icon'];
@endphp

<div 
    x-data="{ show: true }" 
    x-show="show" 
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
    {{ $attributes->merge(['class' => "relative overflow-hidden p-4 rounded-2xl backdrop-blur-sm {$c['bg']} border {$c['border']} shadow-lg {$c['shadow']}"]) }}
    role="alert"
>
    {{-- Decorative gradient --}}
    <div class="absolute top-0 right-0 w-32 h-32 {{ str_replace('text-', 'bg-', $c['title_color']) }}/5 rounded-full blur-2xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
    
    <div class="relative flex gap-4">
        {{-- Icon --}}
        <div class="flex-shrink-0">
            <div class="w-10 h-10 rounded-xl {{ $c['icon_bg'] }} flex items-center justify-center shadow-lg {{ $c['shadow'] }}">
                <x-ui.icon :icon="$alertIcon" class="w-5 h-5 {{ $c['icon_color'] }}" />
            </div>
        </div>
        
        {{-- Content --}}
        <div class="flex-1 min-w-0 pt-0.5">
            @if($title)
                <h4 class="text-sm font-semibold {{ $c['title_color'] }} mb-1">
                    {{ $title }}
                </h4>
            @endif
            
            <div class="text-sm {{ $c['text_color'] }} leading-relaxed">
                {{ $slot }}
            </div>
        </div>

        {{-- Close Button --}}
        @if($dismissable)
            <div class="flex-shrink-0">
                <button 
                    type="button" 
                    @click="show = false"
                    class="w-8 h-8 rounded-lg flex items-center justify-center {{ $c['close_color'] }} transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-current/20"
                >
                    <span class="sr-only">{{ trans('common.dismiss') }}</span>
                    <x-ui.icon icon="x" class="w-4 h-4" />
                </button>
            </div>
        @endif
    </div>
</div>
