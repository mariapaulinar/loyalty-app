{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

TipTap WYSIWYG Editor Component

IMPORTANT: Do NOT add x-init="init()" - Alpine auto-calls init()
NOTE: updatedAt is referenced in :class bindings to force Alpine reactivity
--}}

<div @class(['mb-6', $class => $class])>
    {{-- Label --}}
    @if ($label)
        <div class="flex items-center justify-between mb-2">
            <label 
                for="{{ $id }}" 
                @class([
                    'text-sm font-medium',
                    'text-secondary-700 dark:text-secondary-300' => !$errors->has($nameToDotNotation),
                    'text-red-600 dark:text-red-400' => $errors->has($nameToDotNotation),
                ])
            >
                {!! $label !!}
            </label>
            @if ($help)
                <x-ui.help-popover>{!! $help !!}</x-ui.help-popover>
            @endif
        </div>
    @endif

    {{-- Editor Container --}}
    <div 
        x-data="tiptapEditor(@js($value))"
        class="relative"
        id="{{ $id }}-container"
    >
        {{-- Toolbar --}}
        <div @class([
            'flex flex-wrap items-center gap-1 p-2',
            'border border-b-0 rounded-t-xl',
            'bg-secondary-50 dark:bg-secondary-800/50',
            'border-red-300 dark:border-red-500/50' => $errors->has($nameToDotNotation),
            'border-secondary-200 dark:border-secondary-700' => !$errors->has($nameToDotNotation),
        ])>
            {{-- Headings Dropdown --}}
            <div x-data="{ open: false }" class="relative">
                <button 
                    type="button" 
                    @click.prevent="open = !open"
                    @click.outside="open = false"
                    :class="(updatedAt, isActiveHeading(1) || isActiveHeading(2) || isActiveHeading(3))
                        ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' 
                        : 'text-secondary-500 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
                    class="flex items-center gap-1 px-2.5 py-2 rounded-lg transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500/20 text-sm font-medium"
                    title="{{ trans('common.headings') }}"
                >
                    <x-ui.icon icon="heading" class="w-4 h-4" />
                    <x-ui.icon icon="chevron-down" class="w-3 h-3" />
                </button>
                <div 
                    x-show="open" 
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute left-0 top-full mt-1 w-32 bg-white dark:bg-secondary-800 rounded-lg shadow-lg border border-secondary-200 dark:border-secondary-700 py-1 z-50"
                >
                    <button 
                        type="button" 
                        @click.prevent="toggleHeading(1); open = false"
                        :class="(updatedAt, isActiveHeading(1)) ? 'bg-primary-50 dark:bg-primary-900/30' : ''"
                        class="w-full px-3 py-2 text-left text-lg font-bold hover:bg-secondary-50 dark:hover:bg-secondary-700"
                    >
                        {{ trans('common.heading') }} 1
                    </button>
                    <button 
                        type="button" 
                        @click.prevent="toggleHeading(2); open = false"
                        :class="(updatedAt, isActiveHeading(2)) ? 'bg-primary-50 dark:bg-primary-900/30' : ''"
                        class="w-full px-3 py-2 text-left text-base font-bold hover:bg-secondary-50 dark:hover:bg-secondary-700"
                    >
                        {{ trans('common.heading') }} 2
                    </button>
                    <button 
                        type="button" 
                        @click.prevent="toggleHeading(3); open = false"
                        :class="(updatedAt, isActiveHeading(3)) ? 'bg-primary-50 dark:bg-primary-900/30' : ''"
                        class="w-full px-3 py-2 text-left text-sm font-semibold hover:bg-secondary-50 dark:hover:bg-secondary-700"
                    >
                        {{ trans('common.heading') }} 3
                    </button>
                </div>
            </div>

            {{-- Separator --}}
            <div class="w-px h-5 bg-secondary-200 dark:bg-secondary-700 mx-1"></div>

            {{-- Bold --}}
            <button 
                type="button" 
                @click.prevent="toggleBold()"
                :class="(updatedAt, isActiveBold()) 
                    ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' 
                    : 'text-secondary-500 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
                class="p-2 rounded-lg transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                title="{{ trans('common.bold') }}"
            >
                <x-ui.icon icon="bold" class="w-4 h-4" />
            </button>

            {{-- Italic --}}
            <button 
                type="button" 
                @click.prevent="toggleItalic()"
                :class="(updatedAt, isActiveItalic()) 
                    ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' 
                    : 'text-secondary-500 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
                class="p-2 rounded-lg transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                title="{{ trans('common.italic') }}"
            >
                <x-ui.icon icon="italic" class="w-4 h-4" />
            </button>

            {{-- Underline --}}
            <button 
                type="button" 
                @click.prevent="toggleUnderline()"
                :class="(updatedAt, isActiveUnderline()) 
                    ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' 
                    : 'text-secondary-500 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
                class="p-2 rounded-lg transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                title="{{ trans('common.underline') }}"
            >
                <x-ui.icon icon="underline" class="w-4 h-4" />
            </button>

            {{-- Separator --}}
            <div class="w-px h-5 bg-secondary-200 dark:bg-secondary-700 mx-1"></div>

            {{-- Text Align Left --}}
            <button 
                type="button" 
                @click.prevent="setAlignLeft()"
                :class="(updatedAt, isActiveAlignLeft()) 
                    ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' 
                    : 'text-secondary-500 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
                class="p-2 rounded-lg transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                title="{{ trans('common.align_left') }}"
            >
                <x-ui.icon icon="align-left" class="w-4 h-4" />
            </button>

            {{-- Text Align Center --}}
            <button 
                type="button" 
                @click.prevent="setAlignCenter()"
                :class="(updatedAt, isActiveAlignCenter()) 
                    ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' 
                    : 'text-secondary-500 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
                class="p-2 rounded-lg transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                title="{{ trans('common.align_center') }}"
            >
                <x-ui.icon icon="align-center" class="w-4 h-4" />
            </button>

            {{-- Text Align Right --}}
            <button 
                type="button" 
                @click.prevent="setAlignRight()"
                :class="(updatedAt, isActiveAlignRight()) 
                    ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' 
                    : 'text-secondary-500 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
                class="p-2 rounded-lg transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                title="{{ trans('common.align_right') }}"
            >
                <x-ui.icon icon="align-right" class="w-4 h-4" />
            </button>

            {{-- Separator --}}
            <div class="w-px h-5 bg-secondary-200 dark:bg-secondary-700 mx-1"></div>

            {{-- Bullet List --}}
            <button 
                type="button" 
                @click.prevent="toggleBulletList()"
                :class="(updatedAt, isActiveBulletList()) 
                    ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' 
                    : 'text-secondary-500 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
                class="p-2 rounded-lg transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                title="{{ trans('common.bullet_list') }}"
            >
                <x-ui.icon icon="list" class="w-4 h-4" />
            </button>

            {{-- Ordered List --}}
            <button 
                type="button" 
                @click.prevent="toggleOrderedList()"
                :class="(updatedAt, isActiveOrderedList()) 
                    ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' 
                    : 'text-secondary-500 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
                class="p-2 rounded-lg transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                title="{{ trans('common.numbered_list') }}"
            >
                <x-ui.icon icon="list-ordered" class="w-4 h-4" />
            </button>

            {{-- Separator --}}
            <div class="w-px h-5 bg-secondary-200 dark:bg-secondary-700 mx-1"></div>

            {{-- Blockquote --}}
            <button 
                type="button" 
                @click.prevent="toggleBlockquote()"
                :class="(updatedAt, isActiveBlockquote()) 
                    ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' 
                    : 'text-secondary-500 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
                class="p-2 rounded-lg transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                title="{{ trans('common.blockquote') }}"
            >
                <x-ui.icon icon="quote" class="w-4 h-4" />
            </button>

            {{-- Horizontal Rule --}}
            <button 
                type="button" 
                @click.prevent="insertHorizontalRule()"
                class="p-2 rounded-lg text-secondary-500 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                title="{{ trans('common.horizontal_rule') }}"
            >
                <x-ui.icon icon="minus" class="w-4 h-4" />
            </button>

            {{-- Separator --}}
            <div class="w-px h-5 bg-secondary-200 dark:bg-secondary-700 mx-1"></div>

            {{-- Link --}}
            <button 
                type="button" 
                @click.prevent="setLink()"
                :class="(updatedAt, isActiveLink()) 
                    ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' 
                    : 'text-secondary-500 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
                class="p-2 rounded-lg transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                title="{{ trans('common.add_link') }}"
            >
                <x-ui.icon icon="link" class="w-4 h-4" />
            </button>

            {{-- Remove Link --}}
            <button 
                type="button" 
                @click.prevent="unsetLink()"
                x-show="updatedAt, isActiveLink()"
                x-cloak
                class="p-2 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-red-500/20"
                title="{{ trans('common.remove_link') }}"
            >
                <x-ui.icon icon="unlink" class="w-4 h-4" />
            </button>
        </div>

        {{-- Editor Area --}}
        <div 
            x-ref="editor"
            @class([
                'min-h-[200px] bg-white dark:bg-secondary-900',
                'border rounded-b-xl overflow-hidden',
                'focus-within:ring-2 focus-within:ring-offset-0',
                'border-red-300 dark:border-red-500/50 focus-within:ring-red-500/20' => $errors->has($nameToDotNotation),
                'border-secondary-200 dark:border-secondary-700 focus-within:border-primary-500 focus-within:ring-primary-500/20' => !$errors->has($nameToDotNotation),
            ])
            data-placeholder="{{ $placeholder }}"
        ></div>

        {{-- Hidden Input --}}
        <input 
            type="hidden" 
            x-ref="hiddenInput" 
            name="{{ $name }}" 
            id="{{ $id }}"
            :value="content"
            @if($required) required @endif
        >
    </div>

    {{-- Error / Help Text --}}
    @error($nameToDotNotation)
        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1.5">
            <x-ui.icon icon="alert-circle" class="w-4 h-4 flex-shrink-0" />
            {{ $message }}
        </p>
    @else
        @if ($help)
            <p class="mt-2 text-sm text-secondary-500 dark:text-secondary-400">{!! $help !!}</p>
        @endif
    @enderror
</div>
