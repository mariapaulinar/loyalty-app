{{--
    Card Contact Component
    
    Premium button group for contact actions (website, route, call).
    Modern pill design with subtle hover effects, centered layout.
--}}

@if(!empty($buttons))            
<div class="animate-fade-in-up delay-200">
    <div class="w-full max-w-sm mx-auto">
        <div {{ $attributes->except('class') }} class="flex items-stretch justify-center gap-3 w-full {{ $attributes->get('class') }}">
            @foreach($buttons as $button)
                <a href="{{ $button['url'] }}"
                    @if(isset($button['attr']))
                        @foreach($button['attr'] as $attr => $value)
                            {{ $attr }}="{{ $value }}"
                        @endforeach
                    @endif
                    class="group flex-1 inline-flex items-center justify-center gap-2.5 px-3 py-3.5 text-sm font-medium 
                        bg-white dark:bg-secondary-800
                        text-secondary-700 dark:text-secondary-300 
                        border border-secondary-200 dark:border-secondary-700 
                        rounded-xl shadow-sm
                        hover:bg-secondary-50 dark:hover:bg-secondary-700
                        hover:border-secondary-300 dark:hover:border-secondary-600
                        hover:text-secondary-900 dark:hover:text-white
                        hover:shadow-md
                        active:scale-[0.98]
                        focus:outline-none focus:ring-2 focus:ring-primary-500/20
                        transition-all duration-200">
                    <x-ui.icon :icon="$button['icon']" class="w-4 h-4 text-secondary-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors" />
                    <span>{{ $button['text'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif