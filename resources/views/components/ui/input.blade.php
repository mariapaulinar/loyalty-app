{{--
Premium Input Component

Modern form input with floating labels and elegant focus states.
Inspired by Linear and Stripe's form design.

@props
- label: string - Input label
- type: string - Input type (default: 'text')
- name: string - Input name
- id: string - Input id (defaults to name)
- placeholder: string - Placeholder text
- error: string - Error message
- icon: string - Lucide icon
- helper: string - Helper text
--}}

@props([
    'label' => null,
    'type' => 'text',
    'name' => '',
    'id' => null,
    'placeholder' => '',
    'error' => null,
    'icon' => null,
    'helper' => null,
])

@php
$id = $id ?? $name;
@endphp

<div class="w-full">
    @if ($label)
        <label for="{{ $id }}" class="block mb-2 text-sm font-medium text-secondary-700 dark:text-secondary-300">
            {{ $label }}
        </label>
    @endif

    <div class="relative group">
        @if ($icon)
            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <x-ui.icon :icon="$icon" class="w-5 h-5 text-secondary-400 group-focus-within:text-primary-500 transition-colors duration-200" />
            </div>
        @endif

        <input 
            type="{{ $type }}" 
            name="{{ $name }}" 
            id="{{ $id }}" 
            placeholder="{{ $placeholder }}"
            {{ $attributes->merge([
                'class' => 'w-full bg-white/80 dark:bg-secondary-800/80 backdrop-blur-sm border text-secondary-900 dark:text-white text-sm rounded-xl block p-3.5 transition-all duration-200 placeholder:text-secondary-400 dark:placeholder:text-secondary-500 focus:outline-none focus:ring-2 focus:ring-offset-0 ' . 
                           ($icon ? 'pl-12' : '') . ' ' . 
                           ($error 
                               ? 'border-red-300 dark:border-red-500/50 focus:border-red-500 focus:ring-red-500/20' 
                               : 'border-secondary-200/50 dark:border-secondary-700/50 hover:border-secondary-300 dark:hover:border-secondary-600 focus:border-primary-500 focus:ring-primary-500/20')
            ]) }}
        />
        
        {{-- Focus ring glow --}}
        <div class="absolute inset-0 rounded-xl bg-primary-500/10 opacity-0 group-focus-within:opacity-100 transition-opacity duration-200 pointer-events-none -z-10 blur-xl"></div>
    </div>

    @if ($error)
        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1.5">
            <x-ui.icon icon="alert-circle" class="w-4 h-4 flex-shrink-0" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($helper)
        <p class="mt-2 text-sm text-secondary-500 dark:text-secondary-400">{{ $helper }}</p>
    @endif
</div>
