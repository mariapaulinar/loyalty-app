{{--
  Premium Image Upload Component
  Modern drag-and-drop image upload with elegant preview and smooth animations.
--}}
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
    
    {{-- Upload Zone --}}
    <div class="relative w-full group/upload" x-data="{ isDragging: false }">
        <label 
            class="dropzone-label relative flex items-center justify-center w-full {{ $height ?? 'min-h-40' }}
                   bg-white dark:bg-secondary-800/50
                   border border-secondary-200 dark:border-secondary-700
                   rounded-xl cursor-pointer overflow-hidden
                   shadow-sm
                   transition-all duration-200
                   hover:border-secondary-300 dark:hover:border-secondary-600 hover:shadow-md
                   focus-within:outline-none focus-within:bg-white dark:focus-within:bg-secondary-800
                   focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500/20 
                   focus-within:shadow-md focus-within:shadow-primary-500/5"
            :class="{ 
                'border-primary-500 dark:border-primary-500 bg-primary-50/50 dark:bg-primary-500/5 shadow-md shadow-primary-500/5 ring-2 ring-primary-500/20': isDragging
            }"
            @dragenter.prevent.stop="isDragging = true"
            @dragover.prevent.stop="isDragging = true"
            @dragleave.prevent.stop="isDragging = false"
            @drop.prevent.stop="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }))"
        >
            {{-- Upload Content --}}
            <div class="upload-text flex flex-col items-center justify-center p-6 text-center relative z-10 transition-transform duration-200"
                 :class="{ 'scale-[1.02]': isDragging }">
                {{-- Icon --}}
                <div class="w-12 h-12 rounded-xl mb-3
                            bg-secondary-100 dark:bg-secondary-700/50 
                            flex items-center justify-center
                            group-hover/upload:bg-primary-50 dark:group-hover/upload:bg-primary-500/10
                            transition-colors duration-200"
                    x-bind:class="{ 'bg-primary-50 dark:bg-primary-500/10': isDragging }">
                    <x-ui.icon :icon="$icon ?? 'image'" 
                            class="w-6 h-6 text-secondary-400 
                                    group-hover/upload:text-primary-500 
                                    transition-colors duration-200" 
                            x-bind:class="{ 'text-primary-500': isDragging }" />
                </div>
                
                {{-- Text --}}
                <p class="text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-0.5">
                    {{ $placeholder ?? trans('common.drop_image_here') }}
                </p>
                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                    {{ trans('common.or') }} <span class="text-primary-600 dark:text-primary-400 font-medium">{{ trans('common.browse_files') }}</span>
                </p>
                
                @if($requirements)
                    <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-3">{{ $requirements }}</p>
                @endif
            </div>
            
            {{-- Hidden Inputs --}}
            <input x-ref="fileInput" id="{{ $id }}" name="{{ $name }}" 
                   class="dropzone-file absolute inset-0 z-20 w-full h-full opacity-0 cursor-pointer" 
                   accept="{{ $accept }}" type="file" {{ $attributes }} />
            <input name="{{ $name }}_default" class="image-default" type="hidden" value="{{ $default }}" />
            <input name="{{ $name }}_changed" class="image-changed" type="hidden" value="" />
            <input name="{{ $name }}_deleted" class="image-deleted" type="hidden" value="" />
            
            {{-- Image Preview --}}
            <div class="image-wrapper absolute inset-0 hidden bg-white dark:bg-secondary-800">
                <img x-ref="preview" 
                     class="image-preview w-full h-full object-contain p-4" 
                     src="{{ $value === null ? 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=' : $value }}" 
                     alt="{{ trans('common.image_preview') }}" />
            </div>
            
            {{-- Drag Overlay --}}
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-0 transition-opacity duration-200"
                 :class="{ 'opacity-100': isDragging }">
                <div class="px-4 py-2 rounded-xl bg-primary-500 text-white text-sm font-medium shadow-lg shadow-primary-500/25">
                    <x-ui.icon icon="upload-cloud" class="w-4 h-4 inline mr-1.5 -mt-0.5" />
                    {{ trans('common.drop_to_upload') }}
                </div>
            </div>
        </label>
        
        {{-- Action Buttons --}}
        <div class="remove-image hidden absolute bottom-3 right-3 z-30 flex gap-2">
            <button type="button" 
                    class="change-image-btn inline-flex items-center gap-1.5 px-3 py-2 
                           text-xs font-medium text-secondary-600 dark:text-secondary-300
                           bg-white/95 dark:bg-secondary-800/95 backdrop-blur-sm
                           border border-secondary-200 dark:border-secondary-600 
                           rounded-lg shadow-md
                           hover:bg-secondary-50 dark:hover:bg-secondary-700 
                           transition-all duration-200
                           opacity-0 group-hover/upload:opacity-100">
                <x-ui.icon icon="image" class="w-3.5 h-3.5" />
                {{ trans('common.change') }}
            </button>
            
            <button type="button" 
                    class="delete-image-btn inline-flex items-center gap-1.5 px-3 py-2 
                           text-xs font-medium text-red-600 dark:text-red-400
                           bg-white/95 dark:bg-secondary-800/95 backdrop-blur-sm
                           border border-red-200 dark:border-red-500/30 
                           rounded-lg shadow-md
                           hover:bg-red-50 dark:hover:bg-red-500/10 
                           hover:border-red-300 dark:hover:border-red-500/50
                           transition-all duration-200
                           opacity-0 group-hover/upload:opacity-100">
                <x-ui.icon icon="trash-2" class="w-3.5 h-3.5" />
                {{ trans('common.remove') }}
            </button>
        </div>
    </div>

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
</div>