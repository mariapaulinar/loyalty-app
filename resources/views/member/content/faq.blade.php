@extends('member.layouts.default')

@section('page_title', trans('faq.title') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="min-h-screen">
    {{-- Hero Header --}}
    <header class="relative overflow-hidden">
        {{-- Gradient background --}}
        <div class="absolute inset-0 bg-gradient-to-br from-accent-50 via-white to-accent-50/50 dark:from-secondary-900 dark:via-secondary-900 dark:to-accent-950/20"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-accent-200/30 via-transparent to-transparent dark:from-accent-500/10 animate-pulse" style="animation-duration: 5s;"></div>
        
        {{-- Floating elements --}}
        <div class="absolute top-16 left-16 w-64 h-64 bg-accent-400/10 dark:bg-accent-500/5 rounded-full blur-3xl animate-float"></div>
        <div class="absolute bottom-10 right-20 w-80 h-80 bg-accent-300/10 dark:bg-accent-600/5 rounded-full blur-3xl animate-float-delayed"></div>
        
        <div class="relative max-w-5xl mx-auto px-6 py-16 md:py-24">
            {{-- Back Navigation --}}
            <a href="{{ route('member.index') }}" 
                class="inline-flex items-center gap-2 text-sm font-medium text-secondary-600 dark:text-secondary-400 hover:text-accent-600 dark:hover:text-accent-400 transition-colors mb-8 group animate-fade-in">
                <x-ui.icon icon="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 rtl:group-hover:translate-x-1 transition-transform" />
                {{ trans('common.back_to_home') }}
            </a>
            
            <div class="text-center">
                {{-- Help icon --}}
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-accent-600 to-accent-400 text-white shadow-xl shadow-accent-500/25 mb-8 animate-fade-in-up">
                    <x-ui.icon icon="help-circle" class="w-10 h-10" />
                </div>
                
                {{-- Title --}}
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold tracking-tight mb-6 animate-fade-in-up" style="animation-delay: 100ms;">
                    <span class="bg-gradient-to-r from-secondary-900 via-secondary-700 to-secondary-900 dark:from-white dark:via-secondary-200 dark:to-white bg-clip-text text-transparent">
                        {{ trans('faq.title') }}
                    </span>
                </h1>
                
                <p class="text-lg md:text-xl text-secondary-600 dark:text-secondary-400 max-w-2xl mx-auto animate-fade-in-up" style="animation-delay: 200ms;">
                    {{ trans('faq.description') }}
                </p>
            </div>
        </div>
    </header>

    {{-- FAQ Content --}}
    <section class="relative">
        <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-secondary-200 dark:via-secondary-800 to-transparent"></div>
        
        <div class="max-w-3xl mx-auto px-6 py-16 md:py-20">
            <div class="space-y-4" x-data="{ openItem: null }">
                @foreach(trans('faq.qa') as $index => $faq)
                    <div class="group animate-fade-in-up" style="animation-delay: {{ 300 + ($index * 50) }}ms;">
                        <div class="bg-white dark:bg-secondary-800/50 rounded-2xl border border-secondary-200/80 dark:border-secondary-700/50 overflow-hidden transition-all duration-300 hover:border-accent-300 dark:hover:border-accent-700 hover:shadow-lg hover:shadow-accent-500/5"
                            :class="{ 'ring-2 ring-accent-500/20 border-accent-300 dark:border-accent-700': openItem === {{ $index }} }">
                            
                            {{-- Question --}}
                            <button @click="openItem = openItem === {{ $index }} ? null : {{ $index }}"
                                class="w-full flex items-center justify-between gap-4 px-6 py-5 text-left cursor-pointer">
                                <span class="font-semibold text-secondary-900 dark:text-white group-hover:text-accent-600 dark:group-hover:text-accent-400 transition-colors">
                                    {!! $faq['q'] !!}
                                </span>
                                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-secondary-100 dark:bg-secondary-700 flex items-center justify-center transition-all duration-300 group-hover:bg-accent-100 dark:group-hover:bg-accent-900/30"
                                    :class="{ 'bg-accent-100 dark:bg-accent-900/30 rotate-180': openItem === {{ $index }} }">
                                    <x-ui.icon icon="chevron-down" class="w-4 h-4 text-secondary-500 dark:text-secondary-400 transition-transform duration-300" />
                                </div>
                            </button>
                            
                            {{-- Answer --}}
                            <div x-show="openItem === {{ $index }}"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-2"
                                x-cloak
                                class="px-6 pb-6">
                                <div class="pt-2 border-t border-secondary-100 dark:border-secondary-700/50">
                                    <div class="prose prose-secondary dark:prose-invert prose-sm max-w-none mt-4 text-secondary-600 dark:text-secondary-400 leading-[1.8]">
                                        {!! $faq['a'] !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            {{-- Contact CTA --}}
            <div class="mt-16 text-center animate-fade-in-up" style="animation-delay: 600ms;">
                <div class="inline-flex flex-col items-center gap-4 p-8 rounded-2xl bg-gradient-to-br from-secondary-50 to-secondary-100/50 dark:from-secondary-800/50 dark:to-secondary-900/50 border border-secondary-200/80 dark:border-secondary-700/50">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-accent-600 to-accent-400 flex items-center justify-center text-white">
                        <x-ui.icon icon="message-circle" class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-secondary-900 dark:text-white mb-1">{{ trans('faq.still_have_questions') }}</h3>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400">{{ trans('faq.contact_support') }}</p>
                    </div>
                    <a href="{{ route('member.contact') }}" 
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-secondary-900 dark:bg-white text-white dark:text-secondary-900 rounded-xl font-medium text-sm hover:bg-secondary-800 dark:hover:bg-secondary-100 transition-colors">
                        <x-ui.icon icon="mail" class="w-4 h-4" />
                        {{ trans('common.contact_us') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>

@include('member.content.partials.animations')
@stop
