{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  OTP Verification Page (Step 3)
  Premium design inspired by Linear, Revolut, and Stripe.
--}}

@extends('member.layouts.default', ['robots' => false, 'authPage' => true])

@section('page_title', trans('otp.step3_title') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<section class="min-h-screen bg-white dark:bg-secondary-950">
    <div class="w-full min-h-screen grid lg:grid-cols-2">
        {{-- Left Side: Form --}}
        <div
            class="flex flex-col justify-center items-center px-6 lg:px-20 xl:px-32 py-20 lg:py-12 relative overflow-hidden">
            {{-- Back to Login --}}
            <a href="{{ route('member.login') }}"
                class="absolute top-6 left-6 lg:top-8 lg:left-10 flex items-center gap-2 text-sm font-medium text-secondary-500 hover:text-secondary-900 dark:text-secondary-400 dark:hover:text-white transition-colors group z-10">
                <div
                    class="w-10 h-10 rounded-full bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center group-hover:bg-secondary-200 dark:group-hover:bg-secondary-700 transition-all duration-300 group-hover:scale-105 shadow-sm">
                    <x-ui.icon icon="chevron-left" class="w-5 h-5" />
                </div>
                <span class="hidden sm:inline font-medium">{{ trans('common.back') }}</span>
            </a>

            <div class="w-full max-w-[440px] space-y-8 animate-fade-in-up">
                {{-- Logo --}}
                <div class="mb-8 text-center">
                    <a href="{{ route('member.index') }}" class="inline-block">
                        <x-ui.app-logo class="h-10 mx-auto" />
                    </a>
                </div>

                {{-- Header --}}
                <div class="text-center space-y-3">
                    {{-- Email Icon --}}
                    <div class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-500/10 to-accent-500/10 dark:from-primary-500/20 dark:to-accent-500/20 flex items-center justify-center mb-4">
                        <x-ui.icon icon="mail-check" class="w-8 h-8 text-primary-600 dark:text-primary-400" />
                    </div>

                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-secondary-900 dark:text-white">
                        {{ trans('otp.step3_title') }}
                    </h2>
                    <p class="text-secondary-500 dark:text-secondary-400">
                        {{ trans('otp.step3_subtitle') }}
                        <br>
                        <span class="font-medium text-secondary-700 dark:text-secondary-300">{{ $maskedEmail }}</span>
                    </p>
                </div>

                <x-forms.messages />

                {{-- OTP Form --}}
                <x-forms.form-open id="otp-form" class="space-y-6" :action="route('member.login.otp.verify.post')" method="POST" />
                    <input type="hidden" name="email" value="{{ $email }}">

                    {{-- PIN Input Component --}}
                    <x-ui.pin-input
                        name="code"
                        :length="6"
                        :auto-submit="true"
                        :auto-submit-delay="400"
                    />

                    {{-- Resend Code --}}
                    <div 
                        x-data="resendCooldown({ initialCooldown: {{ $cooldown ?? 0 }} })"
                        class="text-center"
                    >
                        <p class="text-sm text-secondary-500 dark:text-secondary-400">
                            {{ trans('otp.step3_didnt_receive') }}
                        </p>
                        <button
                            type="button"
                            @click="resend"
                            :disabled="cooldown > 0 || loading"
                            :class="{
                                'text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300': cooldown === 0 && !loading,
                                'text-secondary-400 dark:text-secondary-500 cursor-not-allowed': cooldown > 0 || loading
                            }"
                            class="mt-1 text-sm font-medium transition-colors"
                        >
                            <span x-show="!loading && cooldown === 0">{{ trans('otp.step3_resend') }}</span>
                            <span x-show="!loading && cooldown > 0" x-text="'{{ trans('otp.step3_resend_in') }}'.replace(':seconds', cooldown)"></span>
                            <span x-show="loading" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ trans('otp.sending') }}
                            </span>
                        </button>

                        {{-- Success message --}}
                        <p 
                            x-show="success" 
                            x-transition
                            class="mt-2 text-sm text-green-600 dark:text-green-400"
                        >
                            {{ trans('otp.step3_code_sent') }}
                        </p>
                    </div>

                    {{-- Try Another Method --}}
                    <div class="pt-4 text-center">
                        <a href="{{ route('member.login') }}" class="text-sm font-medium text-secondary-500 hover:text-secondary-700 dark:text-secondary-400 dark:hover:text-secondary-200 transition-colors">
                            ← {{ trans('otp.step3_try_another') }}
                        </a>
                    </div>
                <x-forms.form-close />
            </div>
        </div>

        {{-- Right Side: Visual --}}
        <div class="hidden lg:flex relative bg-secondary-900 items-center justify-center overflow-hidden">
            <div
                class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-primary-600 via-primary-700 to-primary-900">
            </div>

            {{-- Abstract Shapes --}}
            <div
                class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-[500px] h-[500px] bg-primary-500/20 rounded-full blur-[100px] animate-pulse-slow">
            </div>
            <div class="absolute bottom-0 left-0 translate-y-1/4 -translate-x-1/4 w-[500px] h-[500px] bg-primary-500/20 rounded-full blur-[100px] animate-pulse-slow"
                style="animation-delay: 2s"></div>

            {{-- Content --}}
            <div class="relative z-10 max-w-lg px-12 text-center animate-fade-in-up delay-200">

                {{-- Security Badge --}}
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-sm border border-white/10 mb-8">
                    <x-ui.icon icon="shield-check" class="w-5 h-5 text-green-400" />
                    <span class="text-sm font-medium text-white/90">{{ trans('otp.secure_verification') }}</span>
                </div>

                <h1 class="text-3xl md:text-4xl font-bold text-white mb-6 tracking-tight leading-tight drop-shadow-lg">
                    {{ trans('otp.step3_check_email') }}
                </h1>

                <p class="text-lg text-secondary-300 font-light leading-relaxed">
                    {{ trans('otp.step3_panel_description') }}
                </p>

                {{-- Security Tips --}}
                <div class="mt-10 space-y-3 text-left">
                    <div class="flex items-start gap-3 text-secondary-300/80">
                        <x-ui.icon icon="clock" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                        <span class="text-sm">{{ trans('otp.code_expires_minutes', ['minutes' => 10]) }}</span>
                    </div>
                    <div class="flex items-start gap-3 text-secondary-300/80">
                        <x-ui.icon icon="eye-off" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                        <span class="text-sm">{{ trans('otp.never_share_code') }}</span>
                    </div>
                    <div class="flex items-start gap-3 text-secondary-300/80">
                        <x-ui.icon icon="mail" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                        <span class="text-sm">{{ trans('otp.check_spam_folder') }}</span>
                    </div>
                </div>
            </div>

            {{-- Glass Overlay --}}
            <div class="absolute inset-0 bg-noise opacity-[0.03] mix-blend-overlay pointer-events-none"></div>
        </div>
    </div>
</section>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('resendCooldown', (config = {}) => ({
        cooldown: config.initialCooldown || 0,
        loading: false,
        success: false,
        interval: null,

        init() {
            if (this.cooldown > 0) {
                this.startCountdown();
            }
        },

        startCountdown() {
            this.interval = setInterval(() => {
                this.cooldown--;
                if (this.cooldown <= 0) {
                    clearInterval(this.interval);
                    this.cooldown = 0;
                }
            }, 1000);
        },

        async resend() {
            if (this.cooldown > 0 || this.loading) return;

            this.loading = true;
            this.success = false;

            try {
                const response = await fetch('{{ route('member.login.otp.resend') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.success = true;
                    this.cooldown = data.cooldown_seconds || 60;
                    this.startCountdown();

                    // Hide success message after 3 seconds
                    setTimeout(() => {
                        this.success = false;
                    }, 3000);
                } else {
                    alert(data.message || 'Failed to resend code');
                }
            } catch (error) {
                console.error('Resend error:', error);
                alert('Failed to resend code. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>
@stop

