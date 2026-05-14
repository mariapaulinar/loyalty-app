{{--
Premium Multi-Select Component

Custom Alpine.js multi-select with search/filter functionality.
No external dependencies - full design control.
--}}

@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'help' => null,
    'placeholder' => trans('common.select_options'),
    'searchPlaceholder' => trans('common.search') . '...',
    'options' => [],
    'value' => [],
    'required' => false,
    'class' => '',
    'text' => null,
])

@php
    $id = $id ?? 'multiselect_' . uniqid();
    $selectedValues = is_array($value) ? $value : (is_string($value) ? explode(',', $value) : []);
@endphp

<div {!! $class ? 'class="' . $class . '"' : '' !!}>
    {{-- Label --}}
    @if ($label)
        <div class="flex items-center gap-2 mb-2">
            <label for="{{ $id }}" class="text-sm font-medium text-secondary-700 dark:text-secondary-300 @error($name) text-red-600 dark:text-red-400 @enderror">
                {!! $label !!}
            </label>
            @if ($help)
                <x-ui.help-popover>{!! $help !!}</x-ui.help-popover>
            @endif
        </div>
    @endif

    {{-- Multi-Select Component --}}
    <div 
        x-data="multiSelect({
            selected: {{ json_encode(array_map('strval', $selectedValues)) }},
            options: {{ json_encode(collect($options)->map(fn($text, $val) => ['value' => (string)$val, 'text' => $text])->values()) }}
        })"
        @click.away="open = false"
        @keydown.escape.window="open = false"
        class="relative overflow-visible"
    >
        {{-- Hidden inputs for form submission --}}
        <template x-for="val in selected" :key="'hidden-' + val">
            <input type="hidden" :name="'{{ $name }}[]'" :value="val">
        </template>
        
        {{-- Trigger Area --}}
        <div
            @click="open = !open"
            class="relative w-full min-h-[48px] bg-white dark:bg-secondary-800 
                   border border-secondary-200 dark:border-secondary-700 
                   text-left text-sm rounded-2xl 
                   focus-within:ring-2 focus-within:ring-primary-500/20 focus-within:border-primary-500
                   hover:border-secondary-300 dark:hover:border-secondary-600
                   shadow-sm hover:shadow-md
                   transition-all duration-200 cursor-pointer
                   @error($name) !border-red-300 dark:!border-red-500/50 @enderror"
        >
            {{-- Selected Tags --}}
            <div class="flex flex-wrap gap-1.5 p-2.5 pr-10">
                {{-- Placeholder when empty --}}
                <span x-show="selected.length === 0" class="text-secondary-400 dark:text-secondary-500 py-0.5 px-1">
                    {{ $placeholder }}
                </span>
                
                {{-- Selected item tags --}}
                <template x-for="(val, index) in selected" :key="'tag-' + val + '-' + index">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium 
                                 bg-primary-50 dark:bg-primary-500/10 
                                 text-primary-700 dark:text-primary-300 
                                 rounded-lg border border-primary-200/50 dark:border-primary-500/20">
                        <span x-text="getOptionText(val)" class="max-w-[150px] truncate"></span>
                        <button 
                            type="button" 
                            @click.stop.prevent="removeItem(val)"
                            class="flex-shrink-0 ml-0.5 p-0.5 rounded hover:bg-primary-200/50 dark:hover:bg-primary-400/20 text-primary-500 hover:text-primary-700 dark:hover:text-primary-200 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </span>
                </template>
            </div>
            
            {{-- Dropdown Icon --}}
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg class="w-5 h-5 text-secondary-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </div>
        
        {{-- Dropdown Panel --}}
        <div 
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="absolute z-[9999] w-full mt-2 bg-white dark:bg-secondary-800 
                   border border-secondary-200 dark:border-secondary-700 
                   rounded-2xl shadow-xl shadow-secondary-200/50 dark:shadow-secondary-900/50"
            style="display: none;"
        >
            {{-- Search Input --}}
            <div class="p-3 border-b border-secondary-100 dark:border-secondary-700">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input 
                        type="text" 
                        x-model="search"
                        @click.stop
                        placeholder="{{ $searchPlaceholder }}"
                        class="w-full pl-9 pr-4 py-2.5 text-sm bg-secondary-50 dark:bg-secondary-700/50 
                               border border-secondary-200 dark:border-secondary-600 
                               rounded-xl text-secondary-900 dark:text-white
                               placeholder:text-secondary-400 dark:placeholder:text-secondary-500
                               focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500
                               transition-all"
                    >
                </div>
            </div>
            
            {{-- Quick Actions --}}
            <div class="flex items-center justify-between px-3 py-2 border-b border-secondary-100 dark:border-secondary-700 bg-secondary-50/50 dark:bg-secondary-700/30">
                <span class="text-xs font-medium text-secondary-500 dark:text-secondary-400">
                    <span x-text="selected.length"></span> {{ trans('common.selected') }}
                </span>
                <div class="flex gap-2">
                    <button 
                        type="button" 
                        @click.stop="selectAll()"
                        class="text-xs font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors">
                        {{ trans('common.select_all') }}
                    </button>
                    <span class="text-secondary-300 dark:text-secondary-600">|</span>
                    <button 
                        type="button" 
                        @click.stop="clearAll()"
                        class="text-xs font-medium text-secondary-500 dark:text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200 transition-colors">
                        {{ trans('common.clear') }}
                    </button>
                </div>
            </div>
            
            {{-- Options List --}}
            <div class="max-h-64 overflow-y-auto overscroll-contain">
                <template x-if="filteredOptions.length === 0">
                    <div class="px-4 py-8 text-center text-sm text-secondary-400 dark:text-secondary-500">
                        {{ trans('common.no_options_found') }}
                    </div>
                </template>
                <template x-for="option in filteredOptions" :key="'option-' + option.value">
                    <button
                        type="button"
                        @click.stop="toggleItem(option.value)"
                        class="w-full flex items-center gap-3 px-4 py-3 text-left text-sm
                               hover:bg-secondary-50 dark:hover:bg-secondary-700/50
                               transition-colors duration-150"
                        :class="{ 'bg-primary-50/50 dark:bg-primary-500/5': isSelected(option.value) }"
                    >
                        {{-- Checkbox --}}
                        <div 
                            class="w-5 h-5 rounded-md border-2 flex items-center justify-center flex-shrink-0 transition-all duration-200"
                            :class="isSelected(option.value) 
                                ? 'bg-primary-600 border-primary-600' 
                                : 'border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800'"
                        >
                            <svg x-show="isSelected(option.value)" x-cloak class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        
                        {{-- Label --}}
                        <span 
                            class="flex-1 truncate"
                            :class="isSelected(option.value) 
                                ? 'text-primary-700 dark:text-primary-300 font-medium' 
                                : 'text-secondary-700 dark:text-secondary-300'"
                            x-text="option.text"
                        ></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- Error / Help Text --}}
    <div class="flex mt-2">
        @error($name)
            <p class="text-sm text-red-600 dark:text-red-400 flex items-center gap-1.5">
                <x-ui.icon icon="alert-circle" class="w-4 h-4 flex-shrink-0" />
                {{ $errors->first($name) }}
            </p>
        @else
            @if ($text)
                <p class="text-sm text-secondary-500 dark:text-secondary-400">{!! $text !!}</p>
            @endif
        @enderror
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('multiSelect', (config) => ({
        open: false,
        search: '',
        selected: config.selected || [],
        options: config.options || [],
        
        init() {
            // Ensure selected values are strings for comparison
            this.selected = this.selected.map(v => String(v));
        },
        
        get filteredOptions() {
            if (!this.search) return this.options;
            const searchLower = this.search.toLowerCase();
            return this.options.filter(opt => opt.text.toLowerCase().includes(searchLower));
        },
        
        getOptionText(value) {
            const opt = this.options.find(o => o.value === String(value));
            return opt ? opt.text : value;
        },
        
        isSelected(value) {
            return this.selected.includes(String(value));
        },
        
        toggleItem(value) {
            const strValue = String(value);
            if (this.isSelected(strValue)) {
                this.selected = this.selected.filter(v => v !== strValue);
            } else {
                this.selected = [...this.selected, strValue];
            }
        },
        
        removeItem(value) {
            this.selected = this.selected.filter(v => v !== String(value));
        },
        
        selectAll() {
            this.selected = this.options.map(opt => opt.value);
        },
        
        clearAll() {
            this.selected = [];
        }
    }));
});
</script>
