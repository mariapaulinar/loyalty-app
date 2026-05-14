{{--
Premium Toggle Switch Component
iOS-style toggle switch with smooth transitions and clean styling.
--}}
<div class="{{ $class }}">
    <label for="{{ $id }}" class="flex items-center gap-3 cursor-pointer">
        <input type="hidden" name="{{ $name }}" id="{{ $id }}_value" value="{{ $checked ? '1' : '0' }}" @if ($model) x-model="{{ $model }}" @endif>
        
        <div class="relative">
            <input
                class="sr-only peer"
                type="checkbox" 
                value="{{ $value }}" 
                id="{{ $id }}"
                onchange="document.getElementById('{{ $id }}_value').value = this.checked ? '1' : '0';@error($name) this.closest('label').querySelector('.toggle-track').classList.remove('!bg-red-500/20', '!border-red-500') @enderror"
                @if ($autofocus) autofocus @endif 
                @if ($checked) checked @endif
                {{ $attributes }}
            >
            
            {{-- Track --}}
            <div class="toggle-track w-11 h-6 rounded-full shadow-inner transition-colors duration-200
                        bg-secondary-200 dark:bg-secondary-700
                        peer-checked:bg-primary-600 dark:peer-checked:bg-primary-600
                        peer-focus-visible:ring-2 peer-focus-visible:ring-primary-500/20 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-white dark:peer-focus-visible:ring-offset-secondary-900
                        @error($name) !bg-red-500/20 border border-red-500 @enderror">
            </div>
            
            {{-- Knob --}}
            <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full shadow-md transition-transform duration-200 ease-out pointer-events-none
                        bg-white
                        peer-checked:translate-x-5">
            </div>
        </div>
        
        @if ($label)
            <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300 select-none">
                {!! $label !!}
            </span>
        @endif
        
        @if ($help)
            <span onclick="event.preventDefault(); event.stopPropagation();">
                <x-ui.help-popover>{!! $help !!}</x-ui.help-popover>
            </span>
        @endif
    </label>
    
    @error($name)
        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1.5 ml-14">
            <x-ui.icon icon="alert-circle" class="w-4 h-4 flex-shrink-0" />
            {{ $errors->first($name) }}
        </p>
    @else
        @if ($text)
            <p class="mt-2 text-sm text-secondary-500 dark:text-secondary-400 ml-14">{!! $text !!}</p>
        @endif
    @enderror
</div>