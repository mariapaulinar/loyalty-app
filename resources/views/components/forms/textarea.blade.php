{{--
Premium Textarea Component
Modern textarea with elegant focus states and AI integration.
--}}
<div @class([$class => $class])>
    {{-- Label Row --}}
    @if ($label || $rightText || $ai)
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                @if ($label)
                    <label for="{{ $id }}" @class([
                        'text-sm font-medium',
                        'text-secondary-700 dark:text-secondary-300' => !$errors->has($nameToDotNotation),
                        'text-red-600 dark:text-red-400' => $errors->has($nameToDotNotation),
                        $classLabel,
                    ])>
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
                            {!! $rightText !!}
                        </a>
                    @else
                        {!! $rightText !!}
                    @endif
                </div>
            @endif
            
            @if ($ai)
                @php
                    $aiName = $name;
                    $locale = null;
                    if (preg_match('/^(.*)\[(.*)\]$/', $name, $matches)) {
                        $aiName = $matches[1];
                        $locale = $matches[2];
                    }
                @endphp
                <div class="flex items-center">
                    <button 
                        type="button" 
                        class="relative flex items-center p-1.5 rounded-lg text-secondary-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition-all duration-200" 
                        id="{{ $id }}_ai-menu-button" 
                        aria-expanded="false" 
                        data-dropdown-toggle="{{ $id }}_ai-dropdown" 
                        data-dropdown-placement="left-start" 
                        @disabled(!$value)
                        @if (isset($ai['autoFill'], $ai['autoFillPrompt']) && $ai['autoFill'] && ($form['view'] ?? null) === 'insert')
                            data-ai-autofill 
                            data-target-id="{{ $id }}" 
                            data-meta='@json(['field' => $aiName, 'locale' => $locale])'
                        @endif
                    >
                        <span class="sr-only">Open AI menu</span>
                        <x-ui.icon icon="sparkles" class="w-4 h-4" />
                        <span id="{{ $id }}_ai-indicator" class="absolute -top-1 -right-1 inline-flex items-center justify-center px-1 h-4 text-[10px] font-bold text-white bg-gradient-to-r from-violet-500 to-purple-600 rounded shadow-sm">AI</span>
                    </button>
                    <div class="hidden min-w-52 z-50 my-4 bg-white dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 divide-y divide-secondary-100 dark:divide-secondary-700 rounded-xl shadow-xl" id="{{ $id }}_ai-dropdown">
                        @if (config('prompts.prompts'))
                            <ul class="py-1.5" aria-labelledby="{{ $id }}_ai-menu-button">
                                @foreach (config('prompts.prompts') as $action => $prompt)
                                    @if ($action === 'divider')
                                        </ul>
                                        <ul class="py-1.5" aria-labelledby="{{ $id }}_ai-menu-button_{{ $loop->index }}">
                                    @elseif (isset($prompt['hasSub']) && $prompt['hasSub'] && (($action === 'translate' && count($languages['all'] ?? []) > 1) || $action !== 'translate'))
                                        <li>
                                            <button 
                                                id="{{ $id }}_ai_btn_{{ $action }}" 
                                                data-dropdown-toggle="{{ $id }}_ai_btn_{{ $action }}_dropdown" 
                                                data-dropdown-placement="right-start" 
                                                type="button" 
                                                class="flex items-center justify-between w-full text-left rtl:text-right text-sm px-4 py-2 text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700 transition-colors"
                                            >
                                                <x-ui.icon :icon="$prompt['icon']" class="flex-shrink-0 w-4 h-4 mr-2 rtl:ml-2 text-secondary-400" />
                                                <span class="flex-grow">{{ trans('common.' . $action) }}</span>
                                                <x-ui.icon icon="chevron-right" class="flex-shrink-0 w-3 h-3 ml-2 text-secondary-400" />
                                            </button>
                                            <div id="{{ $id }}_ai_btn_{{ $action }}_dropdown" class="z-10 hidden bg-white dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 divide-y divide-secondary-100 dark:divide-secondary-700 rounded-xl shadow-xl min-w-44">
                                                <ul class="py-1.5" aria-labelledby="{{ $id }}_ai_btn_{{ $action }}">
                                                    @if ($action === 'translate' && count($languages['all'] ?? []) > 1)
                                                        @foreach ($languages['all'] as $language)
                                                            <li>
                                                                <button 
                                                                    id="{{ $id }}_ai_translate_btn_{{ $language['locale'] }}" 
                                                                    type="button" 
                                                                    data-type="ai" 
                                                                    data-action="translate" 
                                                                    data-target-id="{{ $id }}" 
                                                                    data-meta='@json(['field' => $aiName, 'locale' => $locale, 'translate_to_locale' => $language['locale']])' 
                                                                    class="flex items-center w-full text-left rtl:text-right px-4 py-2 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700 transition-colors"
                                                                >
                                                                    <div class="w-4 h-4 mr-2.5 rtl:ml-2.5 rounded-full fis fi-{{ strtolower($language['countryCode']) }}"></div>
                                                                    {{ $language['languageName'] }}
                                                                </button>
                                                            </li>
                                                        @endforeach
                                                    @elseif ($action !== 'translate')
                                                        @foreach ($prompt['templates'] as $subAction => $subPrompt)
                                                            <li>
                                                                <button 
                                                                    id="{{ $id }}_ai_{{ $action }}_btn_{{ $loop->index }}" 
                                                                    type="button" 
                                                                    data-type="ai" 
                                                                    data-action="{{ $action }}.templates.{{ $subAction }}" 
                                                                    data-target-id="{{ $id }}" 
                                                                    data-meta='@json(['field' => $aiName, 'locale' => $locale])' 
                                                                    class="flex items-center w-full text-left rtl:text-right px-4 py-2 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700 transition-colors"
                                                                >
                                                                    {{ trans('common.' . $subAction) }}
                                                                </button>
                                                            </li>
                                                        @endforeach
                                                    @endif
                                                </ul>
                                            </div>
                                        </li>
                                    @else
                                        <li>
                                            <button 
                                                id="{{ $id }}_ai_btn_{{ $loop->index }}" 
                                                type="button" 
                                                data-type="ai" 
                                                data-action="{{ $action }}" 
                                                data-target-id="{{ $id }}" 
                                                data-meta='@json(['field' => $aiName, 'locale' => $locale])' 
                                                class="flex items-center w-full text-left rtl:text-right px-4 py-2 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-700 transition-colors"
                                            >
                                                <x-ui.icon :icon="$prompt['icon']" class="flex-shrink-0 w-4 h-4 mr-2 rtl:ml-2 text-secondary-400" />
                                                <span class="flex-grow">{{ trans('common.' . $action) }}</span>
                                            </button>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif
    
    {{-- Textarea --}}
    <div class="relative group">
        @if ($icon)
            <div class="absolute top-3.5 left-3.5 pointer-events-none">
                <x-ui.icon :icon="$icon" class="w-5 h-5 text-secondary-400 group-focus-within:text-primary-500 transition-colors" />
            </div>
        @endif
        
        <textarea 
        {{ $attributes->merge([
            'rows' => 5,
            'id' => $id,
            'name' => $name,
            'placeholder' => $placeholder,
        ])->class([
            'w-full bg-white dark:bg-secondary-800 border text-secondary-900 dark:text-white text-sm rounded-xl block p-3 resize-y transition-all duration-200 placeholder:text-secondary-400 dark:placeholder:text-secondary-500 focus:outline-none focus:ring-2 focus:ring-offset-0',
            'pl-11' => $icon,
            'min-h-[120px]' => !$attributes->has('rows'),
            'border-red-300 dark:border-red-500/50 focus:border-red-500 focus:ring-red-500/20' => $errors->has($nameToDotNotation),
            'border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 focus:border-primary-500 focus:ring-primary-500/20' => !$errors->has($nameToDotNotation),
        ]) }}
        @required($required)
        @if ($autofocus) autofocus @endif
        @error($nameToDotNotation) onkeydown="this.classList.remove('border-red-300', 'dark:border-red-500/50')" @enderror
    >{{ $value }}</textarea>
    </div>
    
    {{-- Error / Help Text --}}
    @if ($errors->has($nameToDotNotation) || $text || ($rightText && $rightPosition === 'bottom'))
        <div class="flex mt-2">
            @error($nameToDotNotation)
                <p class="text-sm text-red-600 dark:text-red-400 flex items-center gap-1.5">
                    <x-ui.icon icon="alert-circle" class="w-4 h-4 flex-shrink-0" />
                    {{ $message }}
                </p>
            @else
                @if ($text)
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">{!! $text !!}</p>
                @endif
            @enderror
            
            @if ($rightText && $rightPosition === 'bottom')
                <div class="flex-1 text-right text-sm">
                    @if ($rightLink)
                        <a href="{{ $rightLink }}" class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium transition-colors">
                            {!! $rightText !!}
                        </a>
                    @else
                        {!! $rightText !!}
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>