{{--
Premium Form Input Component
Modern input with elegant focus states, icons, and AI integration.
--}}
<div {!! $class ? 'class="' . $class . '"' : '' !!}>
    {{-- Label Row --}}
    @if ($label || $rightText)
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                @if ($label)
                    <label for="{{ $id }}" class="text-sm font-medium text-secondary-700 dark:text-secondary-300 {{ $classLabel }} @error($nameToDotNotation) text-red-600 dark:text-red-400 @enderror">
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
            
            @if($ai)
                @php
                    $aiName = $name;
                    $locale = null;
                    if (preg_match('/^(.*)\[(.*)\]$/', $name, $matches)) {
                        $aiName = $matches[1];
                        $locale = $matches[2];
                    }
                @endphp
                <div class="flex items-center">
                    <button type="button" class="relative flex items-center p-1.5 rounded-lg text-secondary-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition-all duration-200" id="{{ $id }}_ai-menu-button" aria-expanded="false" data-dropdown-toggle="{{ $id }}_ai-dropdown" data-dropdown-placement="left-start" {{ $value ? '' : 'disabled' }} @if(isset($ai['autoFill']) && isset($ai['autoFillPrompt']) && $ai['autoFill'] && isset($form['view']) && $form['view'] == 'insert') data-ai-autofill data-target-id="{{ $id }}" data-meta='{{ json_encode(['field' => $aiName, 'locale' => $locale]) }}' @endif>
                        <span class="sr-only">Open AI menu</span>
                        <x-ui.icon icon="sparkles" class="w-4 h-4" />
                        <span id="{{ $id }}_ai-indicator" class="absolute -top-1 -right-1 inline-flex items-center justify-center px-1 h-4 text-[10px] font-bold text-white bg-gradient-to-r from-violet-500 to-purple-600 rounded shadow-sm">AI</span>
                    </button>
                    <div class="hidden min-w-52 z-50 my-4 bg-white dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 divide-y divide-secondary-100 dark:divide-secondary-700 rounded-xl shadow-xl" id="{{ $id }}_ai-dropdown">
                        @if(config('prompts.prompts'))
                            <ul class="py-1.5" aria-labelledby="{{ $id }}_ai-menu-button">
                                @foreach(config('prompts.prompts') as $action => $prompt)
                                    @if($action == 'divider')
                            </ul>
                            <ul class="py-1.5" aria-labelledby="{{ $id }}_ai-menu-button_{{ $loop->index }}">
                                    @else
                                        @if(isset($prompt['hasSub']) && $prompt['hasSub'] && (($action == 'translate' && count($languages['all'] ?? []) > 1) || $action != 'translate'))
                                            <li>
                                                <button id="{{ $id }}_ai_btn_{{ $action }}" data-dropdown-toggle="{{ $id }}_ai_btn_{{ $action }}_dropdown" data-dropdown-placement="right-start" type="button" class="flex items-center justify-between w-full text-left rtl:text-right text-sm px-4 py-2 text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700 transition-colors">
                                                    <x-ui.icon :icon="$prompt['icon']" class="flex-shrink-0 w-4 h-4 mr-2 rtl:ml-2 text-secondary-400" />
                                                    <span class="flex-grow">{{ trans('common.' . $action) }}</span>
                                                    <x-ui.icon icon="chevron-right" class="flex-shrink-0 w-3 h-3 ml-2 text-secondary-400" /></button>
                                                <div id="{{ $id }}_ai_btn_{{ $action }}_dropdown" class="z-10 hidden bg-white dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 divide-y divide-secondary-100 dark:divide-secondary-700 rounded-xl shadow-xl min-w-44">
                                                    <ul class="py-1.5" aria-labelledby="{{ $id }}_ai_btn_{{ $action }}">
                                                        @if($action == 'translate' && count($languages['all'] ?? []) > 1)
                                                            @foreach ($languages['all'] as $language)
                                                                <button id="{{ $id }}_ai_translate_btn_{{ $language['locale'] }}" type="button" data-type="ai" data-action="translate" data-target-id="{{ $id }}" data-meta='{{ json_encode(['field' => $aiName, 'locale' => $locale,'translate_to_locale' => $language['locale']]) }}' class="flex items-center w-full text-left rtl:text-right px-4 py-2 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700 transition-colors">
                                                                    <div class="w-4 h-4 mr-2.5 rtl:ml-2.5 rounded-full fis fi-{{ strtolower($language['countryCode']) }}"></div>
                                                                    {{ $language['languageName'] }}
                                                                </button>
                                                            @endforeach
                                                        @elseif($action != 'translate')
                                                            @foreach ($prompt['templates'] as $subAction => $subPrompt)
                                                                <button id="{{ $id }}_ai_{{ $action }}_btn_{{ $loop->index }}" type="button" data-type="ai" data-action="{{ $action . '.templates.' . $subAction }}" data-target-id="{{ $id }}" data-meta='{{ json_encode(['field' => $aiName, 'locale' => $locale]) }}' class="flex items-center w-full text-left rtl:text-right px-4 py-2 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700 transition-colors">
                                                                    {{ trans('common.' . $subAction) }}
                                                                </button>
                                                            @endforeach
                                                        @endif
                                                    </ul>
                                                </div>
                                            </li>
                                        @else
                                            <li>
                                                <button id="{{ $id }}_ai_btn_{{ $loop->index }}" type="button" data-type="ai" data-action="{{ $action }}" data-target-id="{{ $id }}" data-meta='{{ json_encode(['field' => $aiName, 'locale' => $locale]) }}' class="flex items-center w-full text-left rtl:text-right px-4 py-2 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700 transition-colors">
                                                    <x-ui.icon :icon="$prompt['icon']" class="flex-shrink-0 w-4 h-4 mr-2 rtl:ml-2 text-secondary-400" />
                                                    <span class="flex-grow">{{ trans('common.' . $action) }}</span>
                                                </button>
                                            </li>
                                        @endif
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Input Row --}}
    <div class="flex items-center gap-3" x-data="{ input: @js($type), rangeValue: @js($value) }">
        @if ($type == 'color')
            {{-- Color Picker with Swatch --}}
            <div class="flex gap-3 w-full items-center" data-color-picker>
                <button 
                    type="button"
                    class="color-swatch-btn shrink-0" 
                    data-color-swatch 
                    data-color-trigger
                    aria-label="Choose color"
                >
                    <div class="color-swatch-preview" style="background-color: {{ $value ?: '#3b82f6' }};"></div>
                </button>
                
                <div class="relative flex-1 group">
                <input 
                    type="text" 
                    id="{{ $id }}" 
                    name="{{ $name }}" 
                    value="{{ $value ?: '#3b82f6' }}" 
                    data-color-input
                    class="w-full bg-white dark:bg-secondary-800/50 
                           border border-secondary-200 dark:border-secondary-700 
                           text-secondary-900 dark:text-white text-sm font-mono 
                           rounded-xl px-3 py-3 
                           shadow-sm
                           transition-all duration-200 
                           placeholder:text-secondary-400 dark:placeholder:text-secondary-500 
                           uppercase
                           hover:border-secondary-300 dark:hover:border-secondary-600 hover:shadow-md
                           focus:outline-none focus:bg-white dark:focus:bg-secondary-800
                           focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 
                           focus:shadow-md focus:shadow-primary-500/5
                           @error($nameToDotNotation) 
                               border-red-300 dark:border-red-500/50 
                               focus:border-red-500 focus:ring-red-500/20 
                               focus:shadow-red-500/5
                           @enderror 
                           {{ $inputClass }}"
                        placeholder="#3b82f6" 
                        pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
                        maxlength="7"
                        @if ($required) required @endif 
                        @if ($autofocus) autofocus @endif 
                        {{ $attributes }}
                    >
                </div>
            </div>

        @elseif ($type == 'range')
            {{-- Range Slider --}}
            <div class="flex-1 flex items-center py-3">
                <input 
                    type="range" 
                    id="{{ $id }}" 
                    x-model="rangeValue"
                    class="w-full h-1.5 bg-secondary-200 dark:bg-secondary-700 rounded-full appearance-none cursor-pointer
                           [&::-webkit-slider-thumb]:appearance-none
                           [&::-webkit-slider-thumb]:w-5
                           [&::-webkit-slider-thumb]:h-5
                           [&::-webkit-slider-thumb]:rounded-full
                           [&::-webkit-slider-thumb]:bg-white
                           [&::-webkit-slider-thumb]:shadow-md
                           [&::-webkit-slider-thumb]:border
                           [&::-webkit-slider-thumb]:border-secondary-300
                           [&::-webkit-slider-thumb]:cursor-pointer
                           [&::-webkit-slider-thumb]:transition-transform
                           [&::-webkit-slider-thumb]:duration-150
                           [&::-webkit-slider-thumb]:hover:scale-110
                           [&::-webkit-slider-thumb]:active:scale-95
                           [&::-moz-range-thumb]:w-5
                           [&::-moz-range-thumb]:h-5
                           [&::-moz-range-thumb]:rounded-full
                           [&::-moz-range-thumb]:bg-white
                           [&::-moz-range-thumb]:shadow-md
                           [&::-moz-range-thumb]:border
                           [&::-moz-range-thumb]:border-secondary-300
                           [&::-moz-range-thumb]:border-solid
                           [&::-moz-range-thumb]:cursor-pointer
                           [&::-moz-range-track]:bg-transparent
                           focus:outline-none"
                    {{ $attributes }}
                >
            </div>
            
            <input 
                type="text" 
                inputmode="numeric"
                pattern="[0-9]*"
                id="{{ $id }}_output" 
                name="{{ $name }}" 
                x-model="rangeValue"
                class="w-16 bg-white dark:bg-secondary-800/50 
                       border border-secondary-200 dark:border-secondary-700 
                       text-center text-secondary-900 dark:text-white text-sm 
                       rounded-xl py-3 
                       shadow-sm
                       transition-all duration-200
                       hover:border-secondary-300 dark:hover:border-secondary-600
                       focus:outline-none focus:bg-white dark:focus:bg-secondary-800
                       focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
            >

        @elseif ($type == 'number')
            {{-- 
                Enlightened Numeric Input (Steve Jobs × Jony Ive Edition)
                - Uses text input with inputmode="numeric" for clean mobile keyboard without ugly spinners
                - Beautiful +/− stepper buttons for quick adjustments
                - Smooth transitions and proper touch targets
                - Input sanitization to only allow numbers
            --}}
            @php
                $minValue = $min;
                $maxValue = $max;
                $stepValue = $step ?? 1;
                $allowNegative = $minValue === null || $minValue < 0;
            @endphp
            <div class="relative w-full group flex items-center gap-2" 
                 x-modelable="value"
                 {{ $attributes }}
                 x-data="{ 
                    value: '{{ $value }}',
                    min: {{ $minValue ?? 'null' }},
                    max: {{ $maxValue ?? 'null' }},
                    step: {{ $stepValue }},
                    allowNegative: {{ $allowNegative ? 'true' : 'false' }},
                    sanitize(e) {
                        const input = e.target;
                        const cursorPos = input.selectionStart;
                        const oldLen = input.value.length;
                        
                        // Allow: 0-9, period, comma (for international decimals), minus (if allowed)
                        let pattern = this.allowNegative ? /[^0-9.,\-]/g : /[^0-9.,]/g;
                        let cleaned = input.value.replace(pattern, '');
                        
                        // Normalize: Convert comma to period for decimal
                        // Smart detection: if comma is followed by exactly 3 digits at end, it's likely a thousand separator
                        // Otherwise, treat comma as decimal separator (European format: 1,5 = 1.5)
                        if (cleaned.includes(',')) {
                            const parts = cleaned.split(',');
                            if (parts.length === 2 && parts[1].length === 3 && parts[0].length > 0 && !cleaned.includes('.')) {
                                // Looks like thousand separator (1,000) - remove it
                                cleaned = parts.join('');
                            } else {
                                // Treat comma as decimal separator - convert to period
                                cleaned = cleaned.replace(/,/g, '.');
                            }
                        }
                        
                        // Only allow one decimal point
                        const parts = cleaned.split('.');
                        if (parts.length > 2) {
                            cleaned = parts[0] + '.' + parts.slice(1).join('');
                        }
                        
                        // Only allow minus at start
                        if (this.allowNegative && cleaned.includes('-')) {
                            const hasMinus = cleaned.startsWith('-');
                            cleaned = cleaned.replace(/-/g, '');
                            if (hasMinus) cleaned = '-' + cleaned;
                        }
                        
                        this.value = cleaned;
                        
                        // Restore cursor position
                        this.$nextTick(() => {
                            const newLen = input.value.length;
                            const newPos = cursorPos - (oldLen - newLen);
                            input.setSelectionRange(newPos, newPos);
                        });
                    },
                    validate() {
                        // Clamp to min/max on blur and format to step precision
                        let num = parseFloat(this.value);
                        if (isNaN(num) || this.value === '') { this.value = ''; return; }
                        if (this.min !== null && num < this.min) num = this.min;
                        if (this.max !== null && num > this.max) num = this.max;
                        // Format to appropriate decimal places based on step
                        const stepDecimals = String(this.step).split('.')[1]?.length || 0;
                        this.value = stepDecimals > 0 ? num.toFixed(stepDecimals) : String(Math.round(num));
                    },
                    decrement() {
                        let current = parseFloat(this.value) || 0;
                        let newVal = current - this.step;
                        if (this.min !== null && newVal < this.min) newVal = this.min;
                        this.value = this.step % 1 !== 0 ? newVal.toFixed(String(this.step).split('.')[1]?.length || 0) : newVal;
                    },
                    increment() {
                        let current = parseFloat(this.value) || 0;
                        let newVal = current + this.step;
                        if (this.max !== null && newVal > this.max) newVal = this.max;
                        this.value = this.step % 1 !== 0 ? newVal.toFixed(String(this.step).split('.')[1]?.length || 0) : newVal;
                    }
                 }">
                
                {{-- Decrement Button --}}
                <button type="button" 
                        @click="decrement()"
                        :disabled="min !== null && parseFloat(value) <= min"
                        class="flex-shrink-0 flex items-center justify-center w-11 h-11
                               bg-secondary-50 dark:bg-secondary-800/50 
                               border border-secondary-200 dark:border-secondary-700 
                               rounded-xl text-secondary-500 dark:text-secondary-400
                               hover:bg-secondary-100 dark:hover:bg-secondary-700 
                               hover:text-secondary-700 dark:hover:text-secondary-200
                               hover:border-secondary-300 dark:hover:border-secondary-600
                               focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500
                               disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-secondary-50 dark:disabled:hover:bg-secondary-800/50
                               transition-all duration-150 active:scale-95"
                        tabindex="-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                    </svg>
                </button>
                
                {{-- Input Field --}}
                <div class="relative flex-1">
                    @if ($prefix)
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <span class="text-secondary-500 dark:text-secondary-400 text-sm {{ $affixClass }}" id="{{ $id }}_prefix">{{ $prefix }}</span>
                        </div>
                    @endif
                    
                    <input 
                        type="text"
                        inputmode="decimal"
                        pattern="[0-9]*\.?[0-9]*"
                        id="{{ $id }}" 
                        name="{{ $name }}" 
                        x-model="value"
                        @input="sanitize($event)"
                        @blur="validate()"
                        placeholder="{{ $placeholder }}"
                        class="w-full bg-white dark:bg-secondary-800/50 
                               border border-secondary-200 dark:border-secondary-700 
                               text-secondary-900 dark:text-white text-sm text-center
                               rounded-xl block p-3 
                               shadow-sm
                               transition-all duration-200 
                               placeholder:text-secondary-400 dark:placeholder:text-secondary-500 
                               focus:outline-none focus:bg-white dark:focus:bg-secondary-800
                               focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 
                               focus:shadow-md focus:shadow-primary-500/5
                               hover:border-secondary-300 dark:hover:border-secondary-600 hover:shadow-md
                               @if ($prefix) pl-10 @endif
                               @if ($suffix) pr-10 @endif
                               @error($nameToDotNotation) 
                                   border-red-300 dark:border-red-500/50 
                                   focus:border-red-500 focus:ring-red-500/20 
                                   focus:shadow-red-500/5
                               @enderror 
                               {{ $inputClass }}"
                        @if ($required) required @endif 
                        @if ($autofocus) autofocus @endif
                        @if ($minValue !== null) min="{{ $minValue }}" @endif
                        @if ($maxValue !== null) max="{{ $maxValue }}" @endif
                        step="{{ $stepValue }}"
                    >
                    
                    @if ($suffix)
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5">
                            <span class="text-secondary-500 dark:text-secondary-400 text-sm {{ $affixClass }}" id="{{ $id }}_suffix">{{ $suffix }}</span>
                        </div>
                    @endif
                </div>
                
                {{-- Increment Button --}}
                <button type="button" 
                        @click="increment()"
                        :disabled="max !== null && parseFloat(value) >= max"
                        class="flex-shrink-0 flex items-center justify-center w-11 h-11
                               bg-secondary-50 dark:bg-secondary-800/50 
                               border border-secondary-200 dark:border-secondary-700 
                               rounded-xl text-secondary-500 dark:text-secondary-400
                               hover:bg-secondary-100 dark:hover:bg-secondary-700 
                               hover:text-secondary-700 dark:hover:text-secondary-200
                               hover:border-secondary-300 dark:hover:border-secondary-600
                               focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500
                               disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-secondary-50 dark:disabled:hover:bg-secondary-800/50
                               transition-all duration-150 active:scale-95"
                        tabindex="-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/>
                    </svg>
                </button>
            </div>

        @else
            {{-- Standard Input --}}
            <div class="relative w-full group">
                @if ($icon)
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                        <x-ui.icon :icon="$icon" class="w-5 h-5 text-secondary-400 group-focus-within:text-primary-500 transition-colors {{ $affixClass }}" />
                    </div>
                @endif
                @if ($prefix)
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                        <span class="text-secondary-500 dark:text-secondary-400 text-sm {{ $affixClass }}" id="{{ $id }}_prefix">{{ $prefix }}</span>
                    </div>
                    <script>
                        const {{ $id }}_prefix_label = document.getElementById('{{ $id }}_prefix');
                        const {{ $id }}_prefix_input = document.getElementById('{{ $id }}');
                        const {{ $id }}_prefix_observer = new IntersectionObserver((entries, {{ $id }}_prefix_observer) => {
                            entries.forEach(entry => {
                                if (entry.isIntersecting) {
                                    {{ $id }}_prefix_input.style.paddingLeft = {{ $id }}_prefix_label.offsetWidth + 20 + 'px';
                                    {{ $id }}_prefix_observer.disconnect();
                                }
                            });
                        });
                        {{ $id }}_prefix_observer.observe({{ $id }}_prefix_label);
                    </script>
                @endif
                
                <input 
                    type="{{ $type }}" 
                    id="{{ $id }}" 
                    name="{{ $name }}" 
                    value="{{ $value }}" 
                    placeholder="{{ $placeholder }}"
                    class="w-full bg-white dark:bg-secondary-800/50 
                           border border-secondary-200 dark:border-secondary-700 
                           text-secondary-900 dark:text-white text-sm 
                           rounded-xl block p-3 
                           shadow-sm
                           transition-all duration-200 
                           placeholder:text-secondary-400 dark:placeholder:text-secondary-500 
                           focus:outline-none focus:bg-white dark:focus:bg-secondary-800
                           focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 
                           focus:shadow-md focus:shadow-primary-500/5
                           hover:border-secondary-300 dark:hover:border-secondary-600 hover:shadow-md
                           @if ($icon) pl-11 @endif 
                           @error($nameToDotNotation) 
                               border-red-300 dark:border-red-500/50 
                               focus:border-red-500 focus:ring-red-500/20 
                               focus:shadow-red-500/5
                           @enderror 
                           {{ $inputClass }}"
                    @if ($type == 'datetime-local') data-datepicker @endif
                    @if ($required) required @endif 
                    @if ($autofocus) autofocus @endif 
                    {{ $attributes }}
                    x-bind:type="input"
                >
                
                @if ($suffix)
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5">
                        <span class="text-secondary-500 dark:text-secondary-400 text-sm {{ $affixClass }}" id="{{ $id }}_suffix">{{ $suffix }}</span>
                    </div>
                    <script>
                        const {{ $id }}_suffix_label = document.getElementById('{{ $id }}_suffix');
                        const {{ $id }}_suffix_input = document.getElementById('{{ $id }}');
                        const {{ $id }}_suffix_observer = new IntersectionObserver((entries, {{ $id }}_suffix_observer) => {
                            entries.forEach(entry => {
                                if (entry.isIntersecting) {
                                    {{ $id }}_suffix_input.style.paddingRight = {{ $id }}_suffix_label.offsetWidth + 20 + 'px';
                                    {{ $id }}_suffix_observer.disconnect();
                                }
                            });
                        });
                        {{ $id }}_suffix_observer.observe({{ $id }}_suffix_label);
                    </script>
                @endif
                
                {{-- Calendar icon for datetime-local inputs --}}
                @if ($type == 'datetime-local')
                    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3.5">
                        <x-ui.icon icon="calendar" class="w-5 h-5 text-secondary-400" />
                    </div>
                @endif
            </div>
            
            @if ($type == 'password')
                <input id="{{ $id }}_changed" type="hidden" value="" />
                
                <button type="button" tabindex="-1" 
                        class="flex items-center justify-center w-12 h-12
                               bg-secondary-50 dark:bg-secondary-800/50 
                               border border-secondary-200 dark:border-secondary-700 
                               rounded-xl text-secondary-400 
                               hover:text-secondary-600 dark:hover:text-secondary-300 
                               hover:bg-secondary-100 dark:hover:bg-secondary-700 
                               focus:outline-none focus:ring-2 focus:ring-primary-500/20
                               transition-all duration-200 active:scale-95" 
                        x-on:click="input = (input === 'password') ? 'text' : 'password'"
                        data-fb="tooltip" 
                        title="{{ trans('common.toggle_password_visibility') }}">
                    <svg x-show="input === 'password'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg x-show="input != 'password'" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
                
                @if($generatePassword)
                    <button type="button" tabindex="-1" 
                            class="flex items-center justify-center w-12 h-12
                                   bg-primary-50 dark:bg-primary-500/10 
                                   border border-primary-200 dark:border-primary-500/30 
                                   rounded-xl text-primary-600 dark:text-primary-400 
                                   hover:bg-primary-100 dark:hover:bg-primary-500/20 
                                   hover:border-primary-300 dark:hover:border-primary-500/50
                                   focus:outline-none focus:ring-2 focus:ring-primary-500/20
                                   transition-all duration-200 active:scale-95" 
                            onClick="document.getElementById('{{ $id }}').value = generatePassword(12); document.getElementById('{{ $id }}_changed').value = '1';" 
                            data-fb="tooltip" 
                            title="{!! parse_attr(trans('common.generate_password')) !!}">
                        <x-ui.icon icon="refresh-cw" class="w-5 h-5" />
                    </button>
                @endif
            @endif
        @endif
    </div>
    
    {{-- Error / Help Text --}}
    <div class="flex mt-2">
        @error($nameToDotNotation)
            <p class="text-sm text-red-600 dark:text-red-400 flex items-center gap-1.5">
                <x-ui.icon icon="alert-circle" class="w-4 h-4 flex-shrink-0" />
                {{ $errors->first($nameToDotNotation) }}
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
    
    @if($mailPassword)
        <x-forms.checkbox class="mt-4" name="send_user_password" :checked="$mailPasswordChecked" :label="trans('common.send_user_password')" />
    @endif
</div>