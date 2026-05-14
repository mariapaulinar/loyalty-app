{{--
Navigation Link Component

Primary-tinted active state with no borders.
The active item uses a warm, branded background tint
and primary text color — not a cold border indicator.

Border radius: rounded-lg (8px) — the enterprise sweet spot.
Modern enough without being playful.

Pattern reference: Linear, Stripe Dashboard, Vercel.

@props
- active: boolean - Is this link active
- href: string - Link URL
- icon: string - Lucide icon
--}}

@props(['active' => false, 'href' => '#', 'icon' => null])
@aware(['navGroupChild' => false])

<a href="{{ $href }}" 
    {{ $attributes->merge(['class' => 'group/link relative flex items-center gap-3 px-4 py-2 rounded-lg transition-colors duration-200 ' . 
        ($active 
            ? 'bg-primary-50 dark:bg-primary-500/10'
            : 'hover:bg-secondary-50 dark:hover:bg-secondary-800/50')
    ]) }}
    @if($active) data-nav-active="true" @endif>
    
    @if($icon)
        <x-ui.icon 
            :icon="$icon"
            class="w-5 h-5 transition-colors duration-200 {{ $active ? 'text-primary-600 dark:text-primary-400' : 'text-secondary-400 dark:text-secondary-500 group-hover/link:text-secondary-600 dark:group-hover/link:text-secondary-400' }}" 
        />
    @endif
    
    <span class="flex-1 text-[13px] tracking-tight transition-colors duration-200 {{ $active ? 'text-primary-700 dark:text-primary-300 font-semibold' : 'text-secondary-600 dark:text-secondary-400 font-medium group-hover/link:text-secondary-900 dark:group-hover/link:text-white' }}">
        {{ $slot }}
    </span>
</a>
