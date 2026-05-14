{{--
Premium Icon Picker Component
Clean, consistent with form system design.
--}}
@props([
    'name' => 'icon',
    'value' => null,
    'label' => null,
    'help' => null,
    'text' => null,
    'required' => false,
    'class' => null,
])

@php
use App\Support\IconPickerConfig;
$inputId = $name . '-input';
// Ensure $currentValue is always a string (never null) to prevent TypeError in isEmoji()
$currentValue = old($name, $value) ?? '';
$currentValue = is_string($currentValue) ? $currentValue : '';
$categories = IconPickerConfig::getCategories();
$isEmoji = $currentValue !== '' && IconPickerConfig::isEmoji($currentValue);
@endphp

<div {!! $class ? 'class="' . $class . '"' : '' !!}
     x-data="{
        showPicker: false,
        searchTerm: '',
        activeCategory: 'popular',
        selectedIcon: '{{ $currentValue }}',
        isEmoji: {{ $isEmoji ? 'true' : 'false' }},
        categories: {{ json_encode($categories) }},
        
        get filteredIcons() {
            const category = this.categories[this.activeCategory];
            if (!category) return {};
            
            if (!this.searchTerm) return category.icons;
            
            const term = this.searchTerm.toLowerCase();
            return Object.fromEntries(
                Object.entries(category.icons).filter(([key, label]) => 
                    key.toLowerCase().includes(term) || 
                    label.toLowerCase().includes(term)
                )
            );
        },
        
        get selectedIconLabel() {
            if (!this.selectedIcon) return '';
            
            // First try to find by the selected value (emoji or key)
            for (const catKey in this.categories) {
                const icons = this.categories[catKey].icons;
                if (icons[this.selectedIcon]) {
                    const name = this.getIconName(icons[this.selectedIcon]);
                    return name || this.selectedIcon; // Fallback to emoji if no name
                }
            }
            
            // If not found by value, try to find by matching the emoji in labels
            if (this.isEmoji) {
                for (const catKey in this.categories) {
                    const icons = this.categories[catKey].icons;
                    for (const [iconKey, label] of Object.entries(icons)) {
                        if (label.startsWith(this.selectedIcon)) {
                            const name = this.getIconName(label);
                            return name || this.selectedIcon;
                        }
                    }
                }
            }
            
            return this.selectedIcon;
        },
        
        selectIcon(key) {
            const icons = this.categories[this.activeCategory]?.icons;
            if (!icons || !icons[key]) return;
            
            const label = icons[key];
            const emojiMatch = label.match(/^([^\x00-\x7F]+)/);
            
            if (emojiMatch) {
                // It's an emoji - store just the emoji
                this.selectedIcon = emojiMatch[1].trim();
                this.isEmoji = true;
            } else {
                // It's a Lucide icon - store the key
                this.selectedIcon = key;
                this.isEmoji = false;
            }
            
            this.showPicker = false;
        },
        
        clearIcon() {
            this.selectedIcon = '';
            this.isEmoji = false;
        },
        
        extractEmoji(label) {
            const match = label.match(/^([^\x00-\x7F]+)/);
            return match ? match[1] : '⭐';
        },
        
        getIconName(label) {
            return label.replace(/^[^\x00-\x7F]+\s*/, '');
        },
        
        updateLucideIcons() {
            if (window.lucide && !this.isEmoji) {
                setTimeout(() => lucide.createIcons(), 10);
            }
        },
        
        init() {
            // Watch for icon changes to re-render Lucide icons
            this.$watch('selectedIcon', () => this.updateLucideIcons());
            this.$watch('isEmoji', () => this.updateLucideIcons());
        }
    }">

    {{-- Label --}}
    @if ($label)
        <div class="flex items-center gap-2 mb-2">
            <label for="{{ $inputId }}" class="text-sm font-medium text-secondary-700 dark:text-secondary-300">
                {!! $label !!}
            </label>
            @if ($help)
                <x-ui.help-popover>{!! $help !!}</x-ui.help-popover>
            @endif
        </div>
    @endif

    {{-- Trigger Button (styled like select input) --}}
    <button type="button" 
            @click="showPicker = true"
            class="group relative w-full flex items-center gap-3 px-3 py-3
                   bg-white dark:bg-secondary-800/50 
                   border border-secondary-200 dark:border-secondary-700 
                   rounded-xl cursor-pointer
                   shadow-sm
                   transition-all duration-200
                   hover:border-secondary-300 dark:hover:border-secondary-600 hover:shadow-md
                   focus:outline-none focus:bg-white dark:focus:bg-secondary-800
                   focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 
                   focus:shadow-md focus:shadow-primary-500/5">
        
        {{-- Icon Preview --}}
        <div class="flex-none w-10 h-10 rounded-lg bg-secondary-100 dark:bg-secondary-700/50 flex items-center justify-center transition-colors duration-200 group-hover:bg-secondary-50 dark:group-hover:bg-secondary-700">
            {{-- Emoji --}}
            <span x-show="selectedIcon && isEmoji" 
                  x-text="selectedIcon" 
                  class="text-xl"></span>
            
            {{-- Lucide Icon --}}
            <template x-if="selectedIcon && !isEmoji">
                <svg xmlns="http://www.w3.org/2000/svg" 
                     class="w-5 h-5 text-secondary-600 dark:text-secondary-300" 
                     width="24" height="24" viewBox="0 0 24 24" 
                     fill="none" stroke="currentColor" 
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                     :data-lucide="selectedIcon"></svg>
            </template>
            
            {{-- Placeholder --}}
            <span x-show="!selectedIcon">
                <x-ui.icon icon="smile" class="w-5 h-5 text-secondary-400" />
            </span>
        </div>

        {{-- Label --}}
        <div class="flex-1 text-left">
            <span x-show="selectedIcon" x-text="selectedIconLabel" class="text-sm text-secondary-900 dark:text-white"></span>
            <span x-show="!selectedIcon" class="text-sm text-secondary-400 dark:text-secondary-500">{{ trans('common.select_icon') }}</span>
        </div>

        {{-- Right side: Clear button + Chevron --}}
        <div class="flex items-center gap-1">
            {{-- Clear Button (only visible when icon is selected) --}}
            <span x-show="selectedIcon"
                  x-cloak
                  role="button"
                  tabindex="0"
                  @click.stop="clearIcon()"
                  @keydown.enter.stop="clearIcon()"
                  @keydown.space.stop="clearIcon()"
                  class="p-1.5 rounded-lg
                         text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-300
                         hover:bg-secondary-100 dark:hover:bg-secondary-700
                         transition-colors duration-200
                         focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                  title="{{ trans('common.clear') }}">
                <x-ui.icon icon="x" class="w-4 h-4" />
            </span>

            {{-- Dropdown Indicator --}}
            <x-ui.icon icon="chevron-down" class="w-4 h-4 text-secondary-400 group-hover:text-secondary-600 dark:group-hover:text-secondary-300 group-focus:text-primary-500 transition-colors" />
        </div>
    </button>

    {{-- Hidden Input --}}
    <input type="hidden" name="{{ $name }}" x-model="selectedIcon" id="{{ $inputId }}">

    {{-- Error / Help Text --}}
    @error($name)
        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1.5">
            <x-ui.icon icon="alert-circle" class="w-4 h-4 flex-shrink-0" />
            {{ $errors->first($name) }}
        </p>
    @else
        @if ($text)
            <p class="mt-2 text-sm text-secondary-500 dark:text-secondary-400">{!! $text !!}</p>
        @endif
    @enderror

    {{-- Modal --}}
    <template x-teleport="body">
        <div x-show="showPicker" 
             x-cloak
             @click.self="showPicker = false"
             @keydown.escape.window="showPicker = false"
             class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div class="relative w-full max-w-3xl bg-white dark:bg-secondary-800 rounded-2xl shadow-2xl max-h-[85vh] flex flex-col overflow-hidden"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.stop>
                
                {{-- Header --}}
                <div class="flex items-center justify-between p-4 border-b border-secondary-200 dark:border-secondary-700">
                    <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
                        {{ trans('common.choose_icon') }}
                    </h3>
                    <button @click="showPicker = false" type="button" 
                            class="w-8 h-8 rounded-lg flex items-center justify-center
                                   text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-300
                                   hover:bg-secondary-100 dark:hover:bg-secondary-700 
                                   transition-colors duration-200">
                        <x-ui.icon icon="x" class="w-5 h-5" />
                    </button>
                </div>

                {{-- Search --}}
                <div class="p-4 border-b border-secondary-200 dark:border-secondary-700">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <x-ui.icon icon="search" class="w-4 h-4 text-secondary-400" />
                        </div>
                        <input type="search" 
                               x-model="searchTerm" 
                               placeholder="{{ trans('common.search_icons') }}"
                               class="w-full pl-10 pr-4 py-2.5
                                      bg-white dark:bg-secondary-800/50 
                                      border border-secondary-200 dark:border-secondary-700 
                                      rounded-xl text-sm text-secondary-900 dark:text-white 
                                      placeholder-secondary-400 dark:placeholder-secondary-500
                                      shadow-sm
                                      transition-all duration-200
                                      hover:border-secondary-300 dark:hover:border-secondary-600
                                      focus:outline-none focus:bg-white dark:focus:bg-secondary-800
                                      focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    </div>
                </div>

                {{-- Category Tabs --}}
                <div class="px-4 pt-4">
                    <div class="flex gap-1 p-1 bg-secondary-100 dark:bg-secondary-900 rounded-lg">
                        <template x-for="(category, key) in categories" :key="key">
                            <button type="button" 
                                    @click="activeCategory = key"
                                    :class="activeCategory === key 
                                        ? 'bg-white dark:bg-secondary-700 text-secondary-900 dark:text-white shadow-sm' 
                                        : 'text-secondary-500 dark:text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-300'"
                                    class="flex-1 px-3 py-2 rounded-md font-medium text-xs transition-all duration-200"
                                    x-text="category.name">
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Icon Grid --}}
                <div class="flex-1 overflow-y-auto p-4">
                    <div class="grid grid-cols-8 sm:grid-cols-10 md:grid-cols-12 gap-1.5">
                        <template x-for="(label, icon) in filteredIcons" :key="icon">
                            <button type="button" 
                                    @click="selectIcon(icon)"
                                    :class="selectedIcon === icon 
                                        ? 'bg-primary-50 dark:bg-primary-500/10 border-primary-500 ring-2 ring-primary-500/20' 
                                        : 'bg-secondary-50 dark:bg-secondary-700/50 border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
                                    class="aspect-square rounded-lg border flex items-center justify-center transition-all duration-200 active:scale-95"
                                    :title="getIconName(label)">
                                <span class="text-xl" x-text="extractEmoji(label)"></span>
                            </button>
                        </template>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="Object.keys(filteredIcons).length === 0" class="text-center py-12">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-secondary-100 dark:bg-secondary-700 flex items-center justify-center">
                            <x-ui.icon icon="search" class="w-6 h-6 text-secondary-400" />
                        </div>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400">
                            {{ trans('common.no_icons_found') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>