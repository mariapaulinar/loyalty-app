@extends('member.layouts.default', ['robots' => false])

@section('page_title', ($meta['title'] ?? trans('common.terms')) . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="min-h-screen">
    {{-- Hero Header --}}
    <header class="relative overflow-hidden">
        {{-- Gradient background --}}
        <div class="absolute inset-0 bg-gradient-to-br from-amber-50 via-white to-orange-50/50 dark:from-secondary-900 dark:via-secondary-900 dark:to-amber-950/20"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_right,_var(--tw-gradient-stops))] from-amber-200/30 via-transparent to-transparent dark:from-amber-500/10 animate-pulse" style="animation-duration: 5s;"></div>
        
        {{-- Floating elements --}}
        <div class="absolute top-20 right-16 w-60 h-60 bg-amber-400/10 dark:bg-amber-500/5 rounded-full blur-3xl animate-float"></div>
        <div class="absolute bottom-16 left-20 w-72 h-72 bg-orange-300/10 dark:bg-orange-600/5 rounded-full blur-3xl animate-float-delayed"></div>
        
        <div class="relative max-w-5xl mx-auto px-6 py-16 md:py-24">
            {{-- Back Navigation --}}
            <a href="{{ route('member.index') }}" 
                class="inline-flex items-center gap-2 text-sm font-medium text-secondary-600 dark:text-secondary-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors mb-8 group animate-fade-in">
                <x-ui.icon icon="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 rtl:group-hover:translate-x-1 transition-transform" />
                {{ trans('common.back_to_home') }}
            </a>
            
            <div class="text-center">
                {{-- Document icon --}}
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-xl shadow-amber-500/25 mb-8 animate-fade-in-up">
                    <x-ui.icon icon="{{ $meta['icon'] ?? 'file-text' }}" class="w-10 h-10" />
                </div>
                
                {{-- Title --}}
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold tracking-tight mb-6 animate-fade-in-up" style="animation-delay: 100ms;">
                    <span class="bg-gradient-to-r from-secondary-900 via-secondary-700 to-secondary-900 dark:from-white dark:via-secondary-200 dark:to-white bg-clip-text text-transparent">
                        {{ $meta['title'] ?? trans('common.terms') }}
                    </span>
                </h1>
                
                @if(!empty($meta['description']))
                    <p class="text-lg md:text-xl text-secondary-600 dark:text-secondary-400 max-w-2xl mx-auto animate-fade-in-up" style="animation-delay: 200ms;">
                        {{ $meta['description'] }}
                    </p>
                @endif
            </div>
        </div>
    </header>

    {{-- Content --}}
    <section class="relative">
        <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-secondary-200 dark:via-secondary-800 to-transparent"></div>
        
        <div class="max-w-3xl mx-auto px-6 py-16 md:py-20">
            <article class="prose prose-secondary dark:prose-invert max-w-none
                prose-headings:font-bold prose-headings:tracking-tight
                prose-h2:text-2xl prose-h2:mt-14 prose-h2:mb-6 prose-h2:pb-4 prose-h2:border-b prose-h2:border-secondary-200 dark:prose-h2:border-secondary-800
                prose-h3:text-xl prose-h3:mt-10 prose-h3:mb-4
                prose-p:text-[15px] prose-p:text-secondary-600 dark:prose-p:text-secondary-400 prose-p:leading-[1.85] prose-p:mb-6
                prose-a:text-amber-600 dark:prose-a:text-amber-400 prose-a:no-underline hover:prose-a:underline prose-a:font-medium
                prose-strong:text-secondary-900 dark:prose-strong:text-white prose-strong:font-semibold
                prose-ul:space-y-3 prose-ul:my-6 prose-li:text-[15px] prose-li:text-secondary-600 dark:prose-li:text-secondary-400 prose-li:leading-[1.8]
                prose-li:marker:text-amber-500
                prose-ol:space-y-3 prose-ol:my-6
                animate-fade-in-up" style="animation-delay: 300ms;">
                <div class="w-full my-8">
                    <iframe 
                        src="https://lealmi-413539127619-us-east-2-an.s3.us-east-2.amazonaws.com/legal-docs/Lealmi_Terminos_Condiciones_V1.pdf"
                        width="100%" 
                        height="850"
                        style="border: none; min-height: 500px;"
                        title="Términos y Condiciones PDF"
                        allowfullscreen
                    ></iframe>
                </div>
                {{-- {!! $content !!} --}}
            </article>
        </div>
    </section>
</div>

@include('member.content.partials.animations')
@stop
