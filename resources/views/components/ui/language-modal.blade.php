{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

Language Selection Modal (M-017)
Shared component replacing duplicated modals in admin, partner, and member layouts.

Usage:
  <x-ui.language-modal :languages="$languages" route-key="adminIndex" />
  <x-ui.language-modal :languages="$languages" route-key="partnerIndex" />
  <x-ui.language-modal :languages="$languages" route-key="memberIndex" />

Requires parent element to have Alpine x-data with `showLanguageModal: false`.
--}}

@props([
    'languages' => [],
    'routeKey' => 'adminIndex',
])

@if (count($languages['all'] ?? []) > 1)
    <div x-show="showLanguageModal" 
         style="display: none;"
         x-effect="document.body.style.overflow = showLanguageModal ? 'hidden' : ''"
         @click.self="showLanguageModal = false"
         @keydown.escape.window="showLanguageModal = false"
         class="fixed inset-0 z-[60] flex items-center justify-center px-4 bg-black/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0">

        <div @click.away="showLanguageModal = false"
             class="relative bg-white dark:bg-secondary-900 w-full max-w-lg rounded-2xl shadow-2xl transform overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-90 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-90 translate-y-4">
            
            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-secondary-100 dark:border-secondary-800">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <x-ui.icon icon="globe" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-secondary-900 dark:text-white">{{ trans('common.language') }}</h3>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ count($languages['all']) }} {{ Str::plural(strtolower(trans('common.language')), count($languages['all'])) }}</p>
                    </div>
                </div>
                <button @click="showLanguageModal = false"
                        type="button"
                        class="w-8 h-8 rounded-full bg-secondary-100 dark:bg-secondary-800 
                               flex items-center justify-center 
                               hover:bg-secondary-200 dark:hover:bg-secondary-700 
                               transition-all duration-200 hover:scale-110 cursor-pointer">
                    <x-ui.icon icon="x" class="w-4 h-4 text-secondary-600 dark:text-secondary-400" />
                </button>
            </div>
            
            {{-- Language Grid --}}
            <div class="p-4 max-h-[60vh] overflow-y-auto">
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($languages['all'] as $language)
                        @php 
                            $currentLocale = $languages['current']['locale'] ?? '';
                            $langLocale = $language['locale'] ?? '';
                            $isActive = $currentLocale !== '' && $langLocale !== '' && $currentLocale === $langLocale;
                        @endphp
                        <a href="{{ $language[$routeKey] ?? '#' }}"
                            class="group flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-medium transition-all duration-200
                                {{ $isActive 
                                    ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 ring-2 ring-primary-500/20 dark:ring-primary-400/20 shadow-sm' 
                                    : 'text-secondary-700 dark:text-secondary-300 bg-secondary-50 dark:bg-secondary-800/50 hover:bg-secondary-100 dark:hover:bg-secondary-800 hover:shadow-sm active:scale-[0.98]' }}">
                            <div class="w-7 h-7 rounded-full fis fi-{{ strtolower($language['countryCode'] ?? 'us') }} shadow-md ring-1 ring-black/5 flex-shrink-0"></div>
                            <span class="flex-1 truncate">{{ $language['languageName'] ?? 'Unknown' }}</span>
                            @if($isActive)
                                <div class="w-5 h-5 rounded-full bg-primary-500 flex items-center justify-center flex-shrink-0">
                                    <x-ui.icon icon="check" class="w-3 h-3 text-white" />
                                </div>
                            @else
                                <x-ui.icon icon="chevron-right" class="w-4 h-4 text-secondary-300 dark:text-secondary-600 opacity-0 group-hover:opacity-100 transition-opacity" />
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-3 border-t border-secondary-100 dark:border-secondary-800 bg-secondary-50/50 dark:bg-secondary-800/30">
                <p class="text-[11px] text-secondary-400 dark:text-secondary-500 text-center">
                    <x-ui.icon icon="info" class="w-3 h-3 inline -mt-0.5" />
                    {{ $languages['current']['languageName'] ?? '' }} ({{ $languages['current']['countryCode'] ?? '' }})
                </p>
            </div>
        </div>
    </div>
@endif
