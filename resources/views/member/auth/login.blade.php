{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Unified Login Page with OTP Support
  Two-step flow: Email → Password/OTP choice → Verification
--}}

@extends('member.layouts.default', ['robots' => false, 'authPage' => true])

@section('page_title', trans('common.login_title') . config('default.page_title_delimiter') . config('default.app_name'))

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

            {{-- Multi-step Login Flow --}}
            <div 
                x-data="loginFlow({
                    initialEmail: '{{ old('email', $email ?? '') }}',
                    hasPassword: {{ isset($password) && $password ? 'true' : 'false' }},
                    isDemo: {{ config('default.app_demo') ? 'true' : 'false' }},
                    demoPassword: '{{ env('APP_DEMO_PASSWORD', 'welcome3210') }}'
                })"
                x-cloak
                class="w-full max-w-[440px] space-y-8 animate-fade-in-up"
            >
                {{-- Logo --}}
                <div class="mb-8 text-center">
                    <a href="{{ route('member.index') }}" class="inline-block">
                        <x-ui.app-logo class="h-10 mx-auto" />
                    </a>
                </div>

                {{-- ═══════════════════════════════════════════════════════════════ --}}
                {{-- STEP 1: Email Entry --}}
                {{-- ═══════════════════════════════════════════════════════════════ --}}
                <div x-show="step === 'email'" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                    
                    <div class="text-center space-y-2 mb-8">
                        <h2 class="text-3xl font-bold tracking-tight text-secondary-900 dark:text-white">
                            {{ trans('otp.step1_title') }}
                        </h2>
                        <p class="text-secondary-500 dark:text-secondary-400">
                            {{ trans('otp.step1_subtitle') }}
                        </p>
                    </div>

                    @if(config('default.app_demo'))
                        <div class="p-4 rounded-xl bg-accent-50 dark:bg-accent-900/20 border border-accent-100 dark:border-accent-800 cursor-pointer transition-all hover:shadow-md hover:scale-[1.02] group mb-6"
                            @click="fillDemo()">
                            <div class="flex gap-3">
                                <div class="flex-shrink-0 mt-0.5">
                                    <x-ui.icon icon="info" class="w-5 h-5 text-accent-600 dark:text-accent-400" />
                                </div>
                                <div class="text-sm text-accent-700 dark:text-accent-300">
                                    <p class="font-bold mb-1">{{ trans('common.demo_access') }}</p>
                                    <p class="opacity-90">{{ trans('common.click_to_autofill') }} <span
                                            class="font-mono font-bold group-hover:underline">member@example.com</span></p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <x-forms.messages />

                    <form @submit.prevent="checkEmail" class="space-y-6">
                        <x-forms.input 
                            type="email" 
                            name="email" 
                            icon="mail"
                            x-model="email"
                            :label="trans('common.email_address')" 
                            :placeholder="trans('common.your_email')"
                            :required="true"
                            autocomplete="email"
                            class="transition-all focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500" />

                        <x-forms.button 
                            type="submit"
                            :label="trans('otp.step1_continue')"
                            x-bind:disabled="loading || !email"
                            button-class="w-full py-3.5 text-base font-bold text-white shadow-lg shadow-primary-500/20 hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none" />
                    </form>

                    {{-- Registration Link --}}
                    <div class="mt-8 text-center">
                        <p class="text-secondary-500 dark:text-secondary-400">
                            {{ trans('otp.step1_no_account') }}
                            <a href="{{ route('member.register') }}"
                                class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 transition-colors hover:underline decoration-2 underline-offset-2">
                                {{ trans('otp.step1_create_account') }}
                            </a>
                        </p>
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════════════════════════ --}}
                {{-- STEP 2: Authentication Method (Password or OTP) --}}
                {{-- ═══════════════════════════════════════════════════════════════ --}}
                <div x-show="step === 'method'" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                    
                    <div class="text-center space-y-2 mb-8">
                        <h2 class="text-3xl font-bold tracking-tight text-secondary-900 dark:text-white">
                            <span x-text="userExists ? '{{ trans('otp.step2_welcome_back') }}' : '{{ trans('otp.step2_welcome') }}'"></span>
                        </h2>
                        <div class="flex items-center justify-center gap-2 text-secondary-500 dark:text-secondary-400">
                            <span x-text="email" class="font-medium text-secondary-700 dark:text-secondary-300"></span>
                            <button type="button" @click="step = 'email'" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 text-sm font-medium">
                                {{ trans('otp.step2_change_email') }}
                            </button>
                        </div>
                    </div>

                    <x-forms.messages />

                    {{-- Password Form (if user has password) --}}
                    <form x-show="userHasPassword" @submit.prevent="loginWithPassword" class="space-y-6" x-cloak>
                        @csrf
                        <input type="hidden" name="email" x-model="email">

                        <div class="space-y-1">
                            <x-forms.input 
                                type="password" 
                                name="password" 
                                icon="lock"
                                x-model="password"
                                :label="trans('otp.step2_enter_password')" 
                                :placeholder="trans('common.password')" 
                                :required="true"
                                autocomplete="current-password"
                                class="transition-all focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500" />
                            <div class="flex justify-end">
                                <a href="{{ route('member.forgot_password') }}"
                                    class="text-sm font-medium text-secondary-500 hover:text-primary-600 dark:text-secondary-400 dark:hover:text-primary-400 transition-colors">
                                    {{ trans('otp.step2_forgot_password') }}
                                </a>
                            </div>
                        </div>

                        <x-forms.button 
                            type="submit"
                            :label="trans('otp.step2_sign_in')"
                            x-bind:disabled="loading || !password"
                            button-class="w-full py-3.5 text-base font-bold text-white shadow-lg shadow-primary-500/20 hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed" />

                        {{-- Divider --}}
                        <div class="relative my-6">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-secondary-200 dark:border-secondary-700"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-4 bg-white dark:bg-secondary-950 text-secondary-500 dark:text-secondary-400">
                                    {{ trans('otp.step2_or_divider') }}
                                </span>
                            </div>
                        </div>

                        {{-- Send Code Button --}}
                        <button 
                            type="button"
                            @click="sendOtp"
                            :disabled="loading"
                            class="w-full py-3.5 px-4 text-base font-medium text-secondary-700 dark:text-secondary-300 bg-secondary-100 dark:bg-secondary-800 hover:bg-secondary-200 dark:hover:bg-secondary-700 rounded-xl transition-all flex items-center justify-center gap-2 disabled:opacity-50">
                            <x-ui.icon icon="mail" class="w-5 h-5" />
                            {{ trans('otp.step2_send_code') }}
                        </button>
                    </form>

                    {{-- OTP Only (if user has no password) --}}
                    <div x-show="!userHasPassword" class="space-y-6" x-cloak>
                        <div class="p-4 rounded-xl bg-secondary-50 dark:bg-secondary-800/50 border border-secondary-200 dark:border-secondary-700">
                            <div class="flex gap-3">
                                <div class="flex-shrink-0 mt-0.5">
                                    <x-ui.icon icon="mail" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                                </div>
                                <div class="text-sm text-secondary-600 dark:text-secondary-400">
                                    <p class="font-medium text-secondary-700 dark:text-secondary-300">{{ trans('otp.step2_code_info') }}</p>
                                </div>
                            </div>
                        </div>

                        <button 
                            type="button"
                            @click="sendOtp"
                            :disabled="loading"
                            class="w-full py-3.5 text-base font-bold text-white bg-primary-600 hover:bg-primary-500 shadow-lg shadow-primary-500/20 hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0 rounded-xl disabled:opacity-50 flex items-center justify-center gap-2">
                            <x-ui.icon icon="mail" class="w-5 h-5" />
                            {{ trans('otp.step2_send_code') }}
                        </button>
                    </div>

                    {{-- Error Message --}}
                    <p x-show="error" x-html="error" class="mt-4 text-sm text-red-600 dark:text-red-400 text-center"></p>
                </div>

                {{-- Loading Overlay --}}
                <div x-show="loading" class="fixed inset-0 bg-white/80 dark:bg-secondary-950/80 backdrop-blur-sm z-50 flex items-center justify-center">
                    <div class="flex flex-col items-center gap-4">
                        <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-secondary-600 dark:text-secondary-400">{{ trans('otp.please_wait') }}</span>
                    </div>
                </div>
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

                <h1 class="text-4xl md:text-5xl font-bold text-white mb-8 tracking-tight leading-tight drop-shadow-lg">
                    {!! trans('common.login_block_title') !!}
                </h1>

                <div class="space-y-6 text-lg text-secondary-300 font-light leading-relaxed">
                    @foreach (trans('common.login_block_text') as $text)
                        <p>{!! $text !!}</p>
                    @endforeach
                </div>
            </div>

            {{-- Glass Overlay --}}
            <div class="absolute inset-0 bg-noise opacity-[0.03] mix-blend-overlay pointer-events-none"></div>
        </div>
    </div>
</section>

<script>
// Register loginFlow data component for Alpine.js
document.addEventListener('alpine:init', () => {
    Alpine.data('loginFlow', (config = {}) => ({
        // State
        step: 'email',
        email: config.initialEmail || '',
        password: '',
        remember: true, // Always remember users (modern UX pattern)
        loading: false,
        error: '',
        userExists: false,
        userHasPassword: false,
        isDemo: config.isDemo || false,
        demoPassword: config.demoPassword || '',

        // Fill demo credentials
        fillDemo() {
            this.email = 'member@example.com';
            this.password = this.demoPassword;
        },

        // Step 1: Check if email exists
        async checkEmail() {
            if (!this.email) return;
            
            this.loading = true;
            this.error = '';

            try {
                const response = await fetch('{{ route('member.login.check') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email: this.email })
                });

                const data = await response.json();
                
                this.userExists = data.exists;
                this.userHasPassword = data.has_password;
                this.step = 'method';

                // If demo mode and email matches, pre-fill password
                if (this.isDemo && this.email === 'member@example.com') {
                    this.password = this.demoPassword;
                }
            } catch (error) {
                console.error('Check email error:', error);
                this.error = 'An error occurred. Please try again.';
            } finally {
                this.loading = false;
            }
        },

        // Step 2a: Login with password
        async loginWithPassword() {
            if (!this.password) return;

            this.loading = true;
            this.error = '';

            try {
                // Create and submit a form to the login endpoint
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('member.login.post') }}';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
                form.appendChild(csrfInput);

                const emailInput = document.createElement('input');
                emailInput.type = 'hidden';
                emailInput.name = 'email';
                emailInput.value = this.email;
                form.appendChild(emailInput);

                const passwordInput = document.createElement('input');
                passwordInput.type = 'hidden';
                passwordInput.name = 'password';
                passwordInput.value = this.password;
                form.appendChild(passwordInput);

                if (this.remember) {
                    const rememberInput = document.createElement('input');
                    rememberInput.type = 'hidden';
                    rememberInput.name = 'remember';
                    rememberInput.value = '1';
                    form.appendChild(rememberInput);
                }

                document.body.appendChild(form);
                form.submit();
            } catch (error) {
                console.error('Login error:', error);
                this.error = 'An error occurred. Please try again.';
                this.loading = false;
            }
        },

        // Step 2b: Send OTP
        async sendOtp() {
            this.loading = true;
            this.error = '';

            try {
                const response = await fetch('{{ route('member.login.otp.send') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email: this.email })
                });

                const data = await response.json();

                if (data.success) {
                    // Redirect to OTP verification page
                    window.location.href = '{{ route('member.login.otp.verify') }}';
                } else {
                    this.error = data.message || 'Failed to send code. Please try again.';
                    this.loading = false;
                }
            } catch (error) {
                console.error('Send OTP error:', error);
                this.error = 'An error occurred. Please try again.';
                this.loading = false;
            }
        }
    }));
});
</script>
@stop
