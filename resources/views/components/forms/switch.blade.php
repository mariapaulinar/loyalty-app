{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Premium Toggle Switch Component
  Beautiful iOS-style toggle switch with label and description.
--}}

@props([
    'name' => 'switch',
    'id' => null,
    'label' => null,
    'description' => null,
    'checked' => false,
    'value' => '1',
    'class' => null,
    'alpine' => null,
])

@php
    $id = $id ?? $name;
    $alpineModel = $alpine ? "x-model=\"{$alpine}\"" : '';
@endphp

<label class="relative inline-flex items-center cursor-pointer w-full p-4 rounded-xl border-2 transition-all duration-200 hover:border-primary-300 {{ $class }}"
       x-data="{ isChecked: {{ $checked ? 'true' : 'false' }} }"
       :class="isChecked ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-secondary-200 dark:border-secondary-700'">
    
    {{-- Hidden Checkbox --}}
    <input 
        type="checkbox" 
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
        class="sr-only peer"
        @change="isChecked = $event.target.checked"
        {{ $checked ? 'checked' : '' }}
        {!! $alpineModel !!}
        {{ $attributes }}
    />
    
    {{-- Toggle Switch --}}
    <div class="relative w-11 h-6 bg-secondary-200 
                peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 
                dark:peer-focus:ring-primary-800 
                rounded-full peer 
                dark:bg-secondary-700 
                peer-checked:after:translate-x-full 
                rtl:peer-checked:after:-translate-x-full 
                peer-checked:after:border-white 
                after:content-[''] 
                after:absolute 
                after:top-[2px] 
                after:start-[2px] 
                after:bg-white 
                after:border-secondary-300 
                after:border 
                after:rounded-full 
                after:h-5 
                after:w-5 
                after:transition-all 
                dark:border-secondary-600 
                peer-checked:bg-primary-600">
    </div>
    
    {{-- Label & Description --}}
    <div class="ms-3 flex-1">
        @if($label)
            <span class="text-sm font-medium text-secondary-900 dark:text-secondary-300">
                {!! $label !!}
            </span>
        @endif
        @if($description)
            <p class="text-xs text-secondary-500 mt-1">{!! $description !!}</p>
        @endif
    </div>
</label>
