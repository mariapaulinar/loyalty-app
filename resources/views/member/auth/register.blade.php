@extends('member.layouts.default', ['robots' => false, 'authPage' => true])

@section('page_title', trans('common.registration_title') . config('default.page_title_delimiter') . config('default.app_name'))

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
                        {{ trans('common.registration_title') }}
                    </h2>
                    <p class="text-secondary-500 dark:text-secondary-400">
                        {{ trans('common.login_text') }}
                        <a href="{{ route('member.login') }}"
                            class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 transition-colors hover:underline decoration-2 underline-offset-2">
                            {{ trans('common.login_link') }}
                        </a>
                    </p>
                </div>

                <x-forms.messages />

                @if (!Session::has('success'))
                            <x-forms.form-open class="space-y-6" :action="route('member.register.post')" method="POST" />
                            <input type="hidden" name="time_zone" id="time_zone" />
                            @if (Session::has('from.member'))
                                <input type="hidden" name="from" value="{{ Session::get('from.member') }}" />
                            @endif
                            <script>
                                window.onload = function () {
                                    var timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                                    if (!timeZone) {
                                        timeZone = '{{ app()->make('i18n')->time_zone }}';
                                    }
                                    document.getElementById('time_zone').value = timeZone;
                                }
                            </script>

                            <div class="space-y-5">
                                <x-forms.input type="text" name="name" icon="user" :label="trans('common.name')"
                                    :placeholder="trans('common.your_name')" :required="true"
                                    class="transition-all focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 hover:border-secondary-400 dark:hover:border-secondary-500" />
                                <x-forms.input type="email" name="email" icon="mail" :label="trans('common.email_address')"
                                    :placeholder="trans('common.your_email')" :required="true"
                                    class="transition-all focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 hover:border-secondary-400 dark:hover:border-secondary-500" />
                            </div>

                            <div class="space-y-4 pt-2">
                                <x-forms.checkbox name="consent" :label="trans('common.registration_consent', [
                        'terms_of_use' => '<a rel=\'nofollow\' tabindex=\'-1\' target=\'_blank\' class=\'font-medium text-primary-600 hover:underline dark:text-primary-400\' href=\'' . route('member.terms') . '\'>' . trans('common.terms') . '</a>',
                        'privacy_policy' => '<a rel=\'nofollow\' tabindex=\'-1\' target=\'_blank\' class=\'font-medium text-primary-600 hover:underline dark:text-primary-400\' href=\'' . route('member.privacy') . '\'>' . trans('common.privacy_policy') . '</a>',
                    ])" />
                                <x-forms.checkbox name="accepts_emails" :label="trans('common.registration_accepts_emails')" />
                            </div>

                            <x-forms.button :label="trans('common.register')"
                                button-class="w-full py-3.5 text-base font-bold text-white shadow-lg shadow-primary-500/20 hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0 rounded-xl" />
                            <x-forms.form-close />
                @endif
            </div>
        </div>

        {{-- Right Side: Visual --}}
        <div class="hidden lg:flex relative bg-secondary-900 items-center justify-center overflow-hidden">
            <div
                class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-primary-600 via-primary-700 to-primary-900">
            </div>

            {{-- Abstract Shapes --}}
            <div
                class="absolute top-0 left-0 -translate-y-1/4 -translate-x-1/4 w-[500px] h-[500px] bg-primary-500/20 rounded-full blur-[100px] animate-pulse-slow">
            </div>
            <div class="absolute bottom-0 right-0 translate-y-1/4 translate-x-1/4 w-[500px] h-[500px] bg-primary-500/20 rounded-full blur-[100px] animate-pulse-slow"
                style="animation-delay: 2s"></div>

            {{-- Content --}}
            <div class="relative z-10 max-w-lg px-12 text-center animate-fade-in-up delay-200">

                <h1 class="text-4xl md:text-5xl font-bold text-white mb-8 tracking-tight leading-tight drop-shadow-lg">
                    {!! trans('common.register_block_title') !!}
                </h1>

                <div class="space-y-6 text-lg text-secondary-300 font-light leading-relaxed">
                    @foreach (trans('common.register_block_text') as $text)
                        <p>{!! $text !!}</p>
                    @endforeach
                </div>
            </div>

            {{-- Glass Overlay --}}
            <div class="absolute inset-0 bg-noise opacity-[0.03] mix-blend-overlay pointer-events-none"></div>
        </div>
    </div>
</section>
@stop