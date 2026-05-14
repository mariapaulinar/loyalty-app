{{--
Premium Select Component
Modern select with optional searchable dropdown matching multiselect design.
When searchable=true, displays a premium Alpine.js dropdown with search.
When searchable=false, displays a clean native select.
--}}
<div {!! $class ? 'class="' . $class . '"' : '' !!}>
    {{-- Label Row --}}
    @if ($label || ($rightText && $rightPosition == 'top'))
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                @if ($label)
                    <label for="{{ $id }}" class="text-sm font-medium text-secondary-700 dark:text-secondary-300 @error($name) text-red-600 dark:text-red-400 @enderror">
                        {!! $label !!}
                    </label>
                    @if ($help)
                        <x-ui.help-popover>{!! $help !!}</x-ui.help-popover>
                    @endif
                @endif
            </div>
            @if ($rightText && $rightPosition == 'top')
                <div class="text-sm">
                    @if ($rightLink)
                        <a href="{{ $rightLink }}" class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium transition-colors">
                    @endif
                    {!! $rightText !!}
                    @if ($rightLink)</a>@endif
                </div>
            @endif
        </div>
    @endif

    @if ($searchable && !$multiselect)
        {{-- Searchable Select Component --}}
        @php
            $optionsForJs = collect($options)->map(fn($text, $val) => ['value' => (string)$val, 'text' => $text])->values();
        @endphp
        <div 
            x-data="searchableSelect({
                selected: {{ json_encode((string)($value ?? '')) }},
                options: {{ json_encode($optionsForJs) }},
                placeholder: {{ json_encode($placeholder) }}
            })"
            @click.away="open = false"
            @keydown.escape.window="open = false"
            class="relative"
        >
            {{-- Hidden input for form submission --}}
            <input type="hidden" name="{{ $name }}" :value="selected" id="{{ $id }}">
            
            {{-- Trigger Area --}}
            <div
                @click="open = !open"
                class="relative w-full min-h-[46px] bg-white dark:bg-secondary-800/50 
                       border border-secondary-200 dark:border-secondary-700 
                       text-left text-sm rounded-xl
                       focus-within:ring-2 focus-within:ring-primary-500/20 focus-within:border-primary-500
                       hover:border-secondary-300 dark:hover:border-secondary-600
                       shadow-sm hover:shadow-md
                       transition-all duration-200 cursor-pointer
                       @error($name) !border-red-300 dark:!border-red-500/50 @enderror"
            >
                {{-- Selected Value Display --}}
                <div class="flex items-center h-[44px] px-4 pr-10">
                    @if ($icon)
                        <x-ui.icon :icon="$icon" class="w-5 h-5 mr-3 text-secondary-400 flex-shrink-0" />
                    @endif
                    <span 
                        x-text="getSelectedText()"
                        class="flex-1 truncate"
                        :class="selected ? 'text-secondary-900 dark:text-white' : 'text-secondary-400 dark:text-secondary-500'"
                    ></span>
                </div>
                
                {{-- Dropdown Icon --}}
                <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none">
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
                       rounded-xl shadow-xl shadow-secondary-200/50 dark:shadow-secondary-900/50
                       overflow-hidden"
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
                            x-ref="searchInput"
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
                
                {{-- Options List --}}
                <div class="max-h-64 overflow-y-auto overscroll-contain">
                    <template x-if="filteredOptions.length === 0">
                        <div class="px-4 py-8 text-center text-sm text-secondary-400 dark:text-secondary-500">
                            {{ trans('common.no_results_found') }}
                        </div>
                    </template>
                    <template x-for="option in filteredOptions" :key="'option-' + option.value">
                        <button
                            type="button"
                            @click.stop="selectOption(option.value)"
                            class="w-full flex items-center gap-3 px-4 py-3 text-left text-sm
                                   hover:bg-secondary-50 dark:hover:bg-secondary-700/50
                                   focus:outline-none focus:bg-secondary-100 dark:focus:bg-secondary-700
                                   transition-colors duration-150"
                            :class="{ 'bg-primary-50/50 dark:bg-primary-500/5': isSelected(option.value) }"
                        >
                            {{-- Radio indicator --}}
                            <div 
                                class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all duration-200"
                                :class="isSelected(option.value) 
                                    ? 'border-primary-600 bg-primary-600' 
                                    : 'border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800'"
                            >
                                <div 
                                    x-show="isSelected(option.value)" 
                                    x-cloak 
                                    class="w-2 h-2 rounded-full bg-white"
                                ></div>
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
    @else
        {{-- Native Select Input --}}
        <div class="relative group">
            @if ($icon)
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none z-10">
                    <x-ui.icon :icon="$icon" class="w-5 h-5 text-secondary-400 group-focus-within:text-primary-500 transition-colors" />
                </div>
            @endif
            
            <select 
                class="appearance-none w-full 
                       bg-white dark:bg-secondary-800/50 
                       border border-secondary-200 dark:border-secondary-700 
                       text-secondary-900 dark:text-white text-sm 
                       rounded-xl 
                       shadow-sm
                       @if ($icon) pl-11 @else pl-4 @endif pr-10 py-3 
                       transition-all duration-200 cursor-pointer 
                       hover:border-secondary-300 dark:hover:border-secondary-600 hover:shadow-md
                       focus:outline-none focus:bg-white dark:focus:bg-secondary-800
                       focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 
                       focus:shadow-md focus:shadow-primary-500/5
                       @error($name) 
                           !border-red-300 dark:!border-red-500/50 
                           focus:!border-red-500 focus:!ring-red-500/20 
                           focus:!shadow-red-500/5 
                       @enderror"
                id="{{ $id }}"
                @if ($multiselect) name="{{ $name }}[]" @else name="{{ $name }}" @endif
                @if ($required) required @endif 
                @if ($multiselect) multiple size="5" @endif 
                @if ($autofocus) autofocus @endif
                {{ $attributes }}>
                @if ($placeholder && !$multiselect)
                    <option value="" disabled {{ $value === null || $value === '' ? 'selected' : '' }}>{{ $placeholder }}</option>
                @endif
                {{-- Render slot content (options passed between component tags) --}}
                {{ $slot }}
                {{-- Render options passed via :options prop --}}
                @foreach ($options as $option_value => $option_text)
                    @if (is_array($option_text))
                        <optgroup label="{{ $option_value }}">
                            @foreach ($option_text as $optgroup_value => $optgroup_text)
                                <option value="{{ $optgroup_value }}" {{ ($value == $optgroup_value || (is_array($value) && in_array($optgroup_value, $value))) ? 'selected' : '' }}>
                                    {{ $optgroup_text }}
                                </option>
                            @endforeach
                        </optgroup>
                    @else
                        <option value="{{ $option_value }}" {{ ($value == $option_value || (is_array($value) && in_array($option_value, $value))) ? 'selected' : '' }}>
                            {{ $option_text }}
                        </option>
                    @endif
                @endforeach
            </select>
            
            {{-- Dropdown arrow --}}
            @if (!$multiselect)
                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none">
                    <x-ui.icon icon="chevron-down" class="h-4 w-4 text-secondary-400 group-hover:text-secondary-600 dark:group-hover:text-secondary-300 group-focus-within:text-primary-500 transition-colors" />
                </div>
            @endif
        </div>
    @endif

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
        @if ($rightText && $rightPosition == 'bottom')
            <div class="flex-1 text-right text-sm">
                @if ($rightLink)
                    <a href="{{ $rightLink }}" class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium transition-colors">
                @endif
                {!! $rightText !!}
                @if ($rightLink)</a>@endif
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('searchableSelect', (config) => ({
        open: false,
        search: '',
        selected: config.selected || '',
        options: config.options || [],
        placeholder: config.placeholder || '',
        
        init() {
            // Watch for open state to focus search input
            this.$watch('open', (value) => {
                if (value) {
                    this.$nextTick(() => {
                        this.$refs.searchInput?.focus();
                    });
                } else {
                    this.search = '';
                }
            });
        },
        
        get filteredOptions() {
            if (!this.search) return this.options;
            const searchLower = this.search.toLowerCase();
            return this.options.filter(opt => opt.text.toLowerCase().includes(searchLower));
        },
        
        getSelectedText() {
            if (!this.selected) return this.placeholder;
            const opt = this.options.find(o => o.value === String(this.selected));
            return opt ? opt.text : this.placeholder;
        },
        
        isSelected(value) {
            return this.selected === String(value);
        },
        
        selectOption(value) {
            this.selected = String(value);
            this.open = false;
            this.search = '';
        }
    }));
});
</script>