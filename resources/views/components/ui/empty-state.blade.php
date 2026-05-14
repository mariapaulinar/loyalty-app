{{--
Empty State Component

Clean empty states with bare icon and clear CTA.

@props
- icon: string - Lucide icon name (default: 'inbox')
- title: string - Main heading
- description: string - Supporting text
- action: string - CTA button text
- actionHref: string - CTA link
- illustration: string - Optional illustration path
--}}

@props([
    'icon' => 'inbox',
    'title' => '',
    'description' => '',
    'action' => null,
    'actionHref' => null,
    'illustration' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center py-16 px-6']) }}>
    {{-- Icon or Illustration --}}
    @if($illustration)
        <img src="{{ $illustration }}" alt="{{ $title }}" class="w-48 h-48 mb-8 opacity-90">
    @else
        <x-ui.icon :icon="$icon" class="w-10 h-10 text-secondary-300 dark:text-secondary-600 mb-6" />
    @endif
    
    {{-- Title --}}
    <h3 class="text-xl font-bold text-secondary-900 dark:text-white mb-2 tracking-tight">
        {{ $title }}
    </h3>
    
    {{-- Description --}}
    <p class="text-sm text-secondary-500 dark:text-secondary-400 max-w-sm mb-8 leading-relaxed">
        {{ $description }}
    </p>

    {{-- Action Button --}}
    @if($action && $actionHref)
        <a href="{{ $actionHref }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 
               bg-primary-600 hover:bg-primary-500 text-white text-sm font-medium 
               rounded-xl transition-colors duration-200 active:scale-[0.98]">
            <x-ui.icon icon="plus" class="w-4 h-4" />
            <span>{{ $action }}</span>
        </a>
    @endif
    
    {{-- Optional Slot --}}
    @if($slot->isNotEmpty())
        <div class="mt-6">
            {{ $slot }}
        </div>
    @endif
</div>
