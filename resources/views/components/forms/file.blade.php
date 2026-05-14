{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Premium File Upload Component
  Modern drag-and-drop file upload with elegant preview and smooth animations.
--}}

@props([
    'name' => 'file',
    'id' => null,
    'label' => null,
    'help' => null,
    'placeholder' => null,
    'requirements' => null,
    'text' => null,
    'accept' => '*/*',
    'icon' => 'upload-cloud',
    'required' => false,
    'class' => null,
    'height' => 'min-h-40',
])

@php
    $id = $id ?? $name;
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
    
    {{-- Upload Zone --}}
    <div class="relative w-full group/upload" x-data="{ isDragging: false }">
        <label 
            class="dropzone-label relative flex items-center justify-center w-full {{ $height }}
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
                    <x-ui.icon :icon="$icon" 
                            class="w-6 h-6 text-secondary-400 
                                    group-hover/upload:text-primary-500 
                                    transition-colors duration-200" 
                            x-bind:class="{ 'text-primary-500': isDragging }" />
                </div>
                
                {{-- Text --}}
                <p class="text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-0.5">
                    {{ $placeholder ?? trans('common.drop_file_here') }}
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
            
            {{-- Drag Overlay --}}
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-0 transition-opacity duration-200"
                 :class="{ 'opacity-100': isDragging }">
                <div class="px-4 py-2 rounded-xl bg-primary-500 text-white text-sm font-medium shadow-lg shadow-primary-500/25">
                    <x-ui.icon icon="upload-cloud" class="w-4 h-4 inline mr-1.5 -mt-0.5" />
                    {{ trans('common.drop_to_upload') }}
                </div>
            </div>
        </label>
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
