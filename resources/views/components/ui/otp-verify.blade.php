{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2025 NowSquare. All rights reserved.
See LICENSE file for terms.

OTP Verification Component - Reusable Identity Verification
Provides a complete OTP flow: send code -> verify code -> verified state

@props
- guard: Auth guard to use (default: 'member')
- email: Email address to send OTP to (required)
- purpose: Purpose identifier for the OTP (default: 'verification')
- onVerified: Alpine event/action to trigger when verified (optional)
- showTitle: Whether to show the title (default: true)
- compact: Compact mode for inline use (default: false)

The component exposes Alpine data:
- otpSent: Boolean - code has been sent
- otpVerified: Boolean - code verified successfully  
- otpToken: String - verification token to include in subsequent requests
- otpError: String - error message if any

Usage:
<div x-data="{ showOtp: false }">
    <x-ui.otp-verify 
        guard="member" 
        :email="$user->email"
        x-on:otp-verified="handleVerified($event.detail.token)"
    />
</div>
--}}

@props([
    'guard' => 'member',
    'email',
    'purpose' => 'verification',
    'showTitle' => true,
    'compact' => false,
])

@php
    $uniqueId = 'otp_' . Str::random(8);
@endphp

<div x-data="otpVerification_{{ $uniqueId }}({
         sendUrl: '{{ route($guard . '.profile.otp.send') }}',
         verifyUrl: '{{ route($guard . '.profile.otp.verify') }}',
         email: '{{ $email }}',
         csrfToken: '{{ csrf_token() }}',
     })"
     {{ $attributes->merge(['class' => $compact ? '' : 'space-y-4']) }}>
    
    {{-- Error Message --}}
    <div x-show="otpError" x-cloak 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         class="flex justify-center {{ $compact ? 'mb-3' : 'mb-4' }}">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30">
            <x-ui.icon icon="alert-circle" class="w-4 h-4 text-red-500 dark:text-red-400 flex-shrink-0" />
            <span class="text-sm font-medium text-red-700 dark:text-red-300" x-text="otpError"></span>
        </div>
    </div>

    {{-- Step 1: Send Code --}}
    <div x-show="!otpSent && !otpVerified" x-cloak class="text-center">
        @if($showTitle)
            <div class="w-14 h-14 mx-auto mb-4 rounded-xl bg-primary-100 dark:bg-primary-500/20 flex items-center justify-center">
                <x-ui.icon icon="shield-check" class="w-7 h-7 text-primary-600 dark:text-primary-400" />
            </div>
            
            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-2">
                {{ trans('otp.profile_verification_title') }}
            </h3>
        @endif
        <p class="text-secondary-500 dark:text-secondary-400 text-sm {{ $compact ? 'mb-3' : 'mb-6' }} max-w-sm mx-auto">
            {{ trans('otp.profile_send_code_info', ['email' => $email]) }}
        </p>
        
        <button type="button"
                @click="sendCode()"
                :disabled="loading"
                class="inline-flex items-center justify-center gap-2 px-6 py-3 
                       text-sm font-medium text-white 
                       bg-primary-600 hover:bg-primary-500
                       rounded-xl shadow-sm hover:shadow-md
                       focus:outline-none focus:ring-2 focus:ring-primary-500/20
                       transition-all duration-200 active:scale-[0.98]
                       disabled:opacity-50 disabled:cursor-not-allowed">
            <template x-if="!loading">
                <x-ui.icon icon="mail" class="w-4 h-4" />
            </template>
            <template x-if="loading">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </template>
            <span x-text="loading ? '{{ trans('otp.sending') }}' : '{{ trans('otp.profile_send_code') }}'"></span>
        </button>
    </div>

    {{-- Step 2: Enter Code --}}
    <div x-show="otpSent && !otpVerified" x-cloak class="text-center">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 mb-4 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400">
            <x-ui.icon icon="check-circle" class="w-4 h-4" />
            <span class="text-sm font-medium">{{ trans('otp.code_sent_success_short') }}</span>
        </div>
        
        <p class="text-secondary-600 dark:text-secondary-300 text-sm font-medium mb-4">
            {{ $email }}
        </p>
        
        <div class="flex justify-center mb-4" @pin-complete="verifyCode($event.detail.code)">
            <x-ui.pin-input 
                name="otp_code_{{ $uniqueId }}"
                :length="6"
                :auto-submit="true"
                data-no-form-submit
            />
        </div>

        <input type="hidden" name="otp_verification_token" x-model="otpToken" />

        <p class="text-sm text-secondary-500 dark:text-secondary-400">
            {{ trans('otp.step3_didnt_receive') }}
            <button type="button"
                    @click="sendCode()"
                    :disabled="resendCooldown > 0 || loading"
                    class="font-medium text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300 disabled:opacity-50 disabled:cursor-not-allowed ml-1">
                <span x-show="resendCooldown > 0" x-text="'{{ trans('otp.step3_resend_in') }}'.replace(':seconds', resendCooldown)"></span>
                <span x-show="resendCooldown === 0">{{ trans('otp.step3_resend') }}</span>
            </button>
        </p>
    </div>

    {{-- Step 3: Verified --}}
    <div x-show="otpVerified" x-cloak class="text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center">
            <x-ui.icon icon="check" class="w-8 h-8 text-emerald-600 dark:text-emerald-400" />
        </div>
        
        <h3 class="text-lg font-semibold text-emerald-700 dark:text-emerald-400 mb-1">
            {{ trans('otp.identity_verified') }}
        </h3>
        <p class="text-secondary-500 dark:text-secondary-400 text-sm">
            {{ trans('otp.profile_verified') }}
        </p>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    if (typeof Alpine.data['otpVerification_{{ $uniqueId }}'] !== 'undefined') return;
    
    Alpine.data('otpVerification_{{ $uniqueId }}', (config) => ({
        sendUrl: config.sendUrl,
        verifyUrl: config.verifyUrl,
        email: config.email,
        csrfToken: config.csrfToken,
        loading: false,
        otpSent: false,
        otpVerified: false,
        otpError: null,
        otpToken: '',
        resendCooldown: 0,
        cooldownInterval: null,
        
        async sendCode() {
            this.loading = true;
            this.otpError = null;
            try {
                const response = await fetch(this.sendUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email: this.email })
                });
                const data = await response.json();
                if (data.success) {
                    this.otpSent = true;
                    this.startResendCooldown(data.resend_cooldown || 60);
                } else {
                    this.otpError = data.message || '{{ trans('otp.send_failed') }}';
                }
            } catch (e) {
                this.otpError = '{{ trans('otp.send_failed') }}';
            } finally {
                this.loading = false;
            }
        },
        
        async verifyCode(code) {
            this.loading = true;
            this.otpError = null;
            try {
                const response = await fetch(this.verifyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email: this.email, code: code })
                });
                const data = await response.json();
                if (data.success) {
                    this.otpVerified = true;
                    this.otpToken = data.token;
                    this.stopResendCooldown();
                    // Dispatch event for parent components
                    this.$dispatch('otp-verified', { token: data.token });
                    // Also dispatch window event for broader listening
                    window.dispatchEvent(new CustomEvent('otp-verified', { detail: { token: data.token } }));
                } else {
                    this.otpError = data.message || '{{ trans('otp.code_invalid') }}';
                    // Reset pin input
                    document.querySelector('.pin-component')?.dispatchEvent(new CustomEvent('pin-reset'));
                }
            } catch (e) {
                this.otpError = '{{ trans('otp.verification_failed') }}';
                document.querySelector('.pin-component')?.dispatchEvent(new CustomEvent('pin-reset'));
            } finally {
                this.loading = false;
            }
        },
        
        reset() {
            this.otpSent = false;
            this.otpVerified = false;
            this.otpError = null;
            this.otpToken = '';
            this.stopResendCooldown();
        },
        
        startResendCooldown(seconds) {
            this.resendCooldown = seconds;
            this.cooldownInterval = setInterval(() => {
                this.resendCooldown--;
                if (this.resendCooldown <= 0) {
                    this.stopResendCooldown();
                }
            }, 1000);
        },
        
        stopResendCooldown() {
            if (this.cooldownInterval) {
                clearInterval(this.cooldownInterval);
                this.cooldownInterval = null;
            }
            this.resendCooldown = 0;
        }
    }));
});
</script>
