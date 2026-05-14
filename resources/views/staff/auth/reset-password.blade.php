@extends('staff.layouts.default', ['robots' => false, 'authPage' => true])

@section('page_title', trans('common.reset_password_title') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<section class="min-h-screen bg-white dark:bg-secondary-950">
    <div class="w-full min-h-screen grid lg:grid-cols-2">
        {{-- Left Side: Form --}}
        <div
            class="flex flex-col justify-center items-center px-6 lg:px-20 xl:px-32 py-20 lg:py-12 relative overflow-hidden">
            {{-- Back to Home --}}
            <a href="{{ route('member.index') }}"
                class="absolute top-6 left-6 lg:top-8 lg:left-10 flex items-center gap-2 text-sm font-medium text-secondary-500 hover:text-secondary-900 dark:text-secondary-400 dark:hover:text-white transition-colors group z-10">
                <div
                    class="w-10 h-10 rounded-full bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center group-hover:bg-secondary-200 dark:group-hover:bg-secondary-700 transition-all duration-300 group-hover:scale-105 shadow-sm">
                    <x-ui.icon icon="chevron-left" class="w-5 h-5" />
                </div>
                <span class="hidden sm:inline font-medium">{{ trans('common.home') }}</span>
            </a>

            <div class="w-full max-w-[440px] space-y-8 animate-fade-in-up">
                {{-- Logo --}}
                <div class="mb-8 text-center">
                    <a href="{{ route('member.index') }}" class="inline-block">
                        <x-ui.app-logo class="h-10 mx-auto" />
                    </a>
                </div>

                <div class="text-center space-y-2">
                    <h2 class="text-3xl font-bold tracking-tight text-secondary-900 dark:text-white">
                        {!! trans('common.reset_password_title') !!}
                    </h2>
                    <p class="text-secondary-500 dark:text-secondary-400">
                        {{ trans('common.create_new_password_text') }}
                    </p>
                </div>

                <x-forms.messages />

                @if (!Session::has('success'))
                    <x-forms.form-open class="space-y-6" :action="$postResetLink" method="POST" />
                    <div class="space-y-5">
                        <x-forms.input type="password" name="password" icon="lock" :label="trans('common.password')"
                            :placeholder="trans('common.password')" :required="true"
                            class="transition-all focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 hover:border-secondary-400 dark:hover:border-secondary-500" />
                    </div>

                    <x-forms.button :label="trans('common.save_password')"
                        button-class="w-full py-3.5 text-base font-bold text-white shadow-lg shadow-primary-500/20 hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0 rounded-xl cursor-pointer flex items-center justify-center gap-2" />
                    <x-forms.form-close />
                @endif
            </div>
        </div>

        {{-- Right Side: Visual --}}
        <div class="hidden lg:flex relative bg-secondary-900 items-center justify-center overflow-hidden">
            <div
                class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,_var(--tw-gradient-stops))] from-accent-600 via-accent-700 to-accent-900">
            </div>

            {{-- Abstract Shapes --}}
            <div
                class="absolute bottom-0 right-0 translate-y-1/4 translate-x-1/4 w-[600px] h-[600px] bg-emerald-500/10 rounded-full blur-[120px] animate-pulse-slow">
            </div>

            {{-- Content --}}
            <div class="relative z-10 max-w-lg px-12 text-center animate-fade-in-up delay-200">
                <div
                    class="w-24 h-24 mx-auto mb-10 rounded-full bg-white/5 backdrop-blur-2xl border border-white/10 flex items-center justify-center text-white shadow-2xl ring-1 ring-white/20">
                    <x-ui.icon icon="shield-check" class="w-10 h-10 text-white/80" />
                </div>

                <h1 class="text-3xl md:text-4xl font-bold text-white mb-6 tracking-tight leading-tight drop-shadow-lg">
                    {{ trans('common.secure_your_account') }}
                </h1>

                <p class="text-lg text-secondary-400 font-light leading-relaxed">
                    {{ trans('common.secure_your_account_text') }}
                </p>
            </div>

            {{-- Glass Overlay --}}
            <div class="absolute inset-0 bg-noise opacity-[0.03] mix-blend-overlay pointer-events-none"></div>
        </div>
    </div>
</section>
@stop