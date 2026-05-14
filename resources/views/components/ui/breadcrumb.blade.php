{{--
Premium Breadcrumb Component
Modern breadcrumb navigation with subtle styling and smooth transitions.
Collapses middle items on mobile when there are more than 3 crumbs.
--}}
@if ($crumbs !== null && count($crumbs) > 0)
<nav class="flex overflow-x-auto scrollbar-hide" aria-label="Breadcrumb">
    <ol class="inline-flex items-center gap-1 whitespace-nowrap">
        @foreach ($crumbs as $index => $crumb)
            @php
                $hasIcon = !empty($crumb['icon']);
                $hasText = !empty($crumb['text']);
                $hasUrl = !empty($crumb['url']);
                $isLast = $loop->last;
                $isFirst = $loop->first;
                $hasContent = $hasIcon || $hasText;
                $isMiddle = !$isFirst && !$isLast;
                $shouldCollapse = $isMiddle && count($crumbs) > 3;
            @endphp
            
            @if ($hasContent)
                {{-- Show ellipsis once for collapsed middle items on mobile --}}
                @if ($shouldCollapse && $index === 1)
                    <li class="inline-flex sm:hidden items-center">
                        <x-ui.icon icon="chevron-right" class="w-4 h-4 text-secondary-300 dark:text-secondary-600 mx-1.5 flex-shrink-0" />
                        <span class="text-secondary-400 dark:text-secondary-500">…</span>
                    </li>
                @endif

                <li class="{{ $shouldCollapse ? 'hidden sm:inline-flex' : 'inline-flex' }} items-center" @if ($isLast) aria-current="page" @endif>
                    {{-- Separator (not for first item) --}}
                    @if ($index > 0)
                        <x-ui.icon icon="chevron-right" class="w-4 h-4 text-secondary-300 dark:text-secondary-600 mx-1.5 flex-shrink-0" />
                    @endif
                    
                    {{-- Crumb Content --}}
                    @if ($hasUrl)
                        <a href="{{ $crumb['url'] }}" 
                           @if (!empty($crumb['target'])) target="{{ $crumb['target'] }}" @endif 
                           @if (!empty($crumb['title'])) title="{{ $crumb['title'] }}" @endif
                           class="inline-flex items-center gap-1.5 text-sm font-medium text-secondary-600 dark:text-secondary-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200 group">
                            @if ($hasIcon)
                                <x-ui.icon :icon="$crumb['icon']" class="w-4 h-4 flex-shrink-0" />
                            @endif
                            @if ($hasText)
                                <span class="block truncate">{!! $crumb['text'] !!}</span>
                            @endif
                            @if (!empty($crumb['target']) && $crumb['target'] === '_blank')
                                <x-ui.icon icon="external-link" class="w-3 h-3 flex-shrink-0 opacity-50 group-hover:opacity-100 transition-opacity" />
                            @endif
                        </a>
                    @else
                        <span class="inline-flex items-center gap-1.5 text-sm font-medium {{ $isLast ? 'text-secondary-900 dark:text-white' : 'text-secondary-500 dark:text-secondary-400' }}">
                            @if ($hasIcon)
                                <x-ui.icon :icon="$crumb['icon']" class="w-4 h-4 flex-shrink-0" />
                            @endif
                            @if ($hasText)
                                <span class="block truncate">{!! $crumb['text'] !!}</span>
                            @endif
                        </span>
                    @endif
                </li>
            @endif
        @endforeach
    </ol>
</nav>
@endif