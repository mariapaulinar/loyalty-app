{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Premium PIN Input Component - Revolut/Linear Grade UX

  6-digit verification code input featuring:
  • Auto-advance on digit entry (iPhone-style)
  • Intelligent paste handling (full code distribution)
  • Auto-submit on completion
  • Buttery smooth animations & focus states
  • Accessible (ARIA labels, keyboard navigation)

  Design inspired by: Stripe, Linear, Revolut, Mercury
--}}

@props([
    'length' => 6,
    'name' => 'code',
    'autoSubmit' => true,
    'autoSubmitDelay' => 350,
    'disabled' => false,
    'error' => null,
])

@php
$uid = 'pin-' . uniqid();
@endphp

<div 
    id="{{ $uid }}"
    class="pin-component w-full"
    {{ $attributes }}
>
    {{-- Hidden input for form submission --}}
    <input type="hidden" name="{{ $name }}" id="{{ $uid }}-hidden" autocomplete="one-time-code" />

    {{-- PIN Input Grid --}}
    <div 
        id="{{ $uid }}-grid"
        class="flex justify-center items-center gap-2.5 sm:gap-3"
        role="group" 
        aria-label="{{ trans('otp.pin_aria_label') }}"
        data-length="{{ $length }}"
        data-auto-submit="{{ $autoSubmit ? '1' : '0' }}"
        data-submit-delay="{{ $autoSubmitDelay }}"
    >
        @for ($i = 0; $i < $length; $i++)
        <div class="pin-slot relative">
            <input
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="1"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                data-index="{{ $i }}"
                @if($disabled) disabled @endif
                class="pin-digit
                    w-12 h-14 sm:w-[52px] sm:h-[60px] md:w-14 md:h-16
                    text-center text-[22px] sm:text-2xl font-bold tracking-tight
                    text-secondary-900 dark:text-white
                    bg-white dark:bg-secondary-800/60 
                    hover:bg-secondary-50 dark:hover:bg-secondary-700/60
                    border-2 border-secondary-200 dark:border-secondary-600/50
                    rounded-xl
                    shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)]
                    transition-all duration-150 ease-out
                    focus:outline-none 
                    focus:bg-secondary-50 dark:focus:bg-secondary-700/80
                    focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30
                    focus:shadow-[0_0_20px_rgba(59,130,246,0.15)]
                    placeholder:text-secondary-300 dark:placeholder:text-secondary-500/70 placeholder:font-normal
                    disabled:opacity-40 disabled:cursor-not-allowed 
                    disabled:hover:bg-white dark:disabled:hover:bg-secondary-800/60
                    caret-transparent selection:bg-transparent
                "
                placeholder="○"
                aria-label="{{ trans('otp.pin_digit_aria', ['n' => $i + 1, 'total' => $length]) }}"
            />
            {{-- Focus glow effect --}}
            <div class="pin-glow absolute inset-0 -z-10 rounded-xl opacity-0 scale-95 bg-primary-500/20 blur-xl transition-all duration-200 pointer-events-none"></div>
        </div>
        @endfor
    </div>

    {{-- Loading State --}}
    <div id="{{ $uid }}-loading" class="hidden mt-5 flex items-center justify-center gap-2.5">
        <div class="relative w-5 h-5">
            <div class="absolute inset-0 rounded-full border-2 border-primary-500/20"></div>
            <div class="absolute inset-0 rounded-full border-2 border-primary-500 border-t-transparent animate-spin"></div>
        </div>
        <span class="text-sm font-medium text-secondary-400">{{ trans('otp.step3_verifying') }}</span>
    </div>

    {{-- Error --}}
    @if ($error)
    <div class="mt-4 flex items-center justify-center gap-2 text-sm text-red-400 animate-shake">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span>{{ $error }}</span>
    </div>
    @endif
</div>

<script>
(function() {
    'use strict';

    // Wait for DOM to be ready
    function ready(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    ready(function() {
        const container = document.getElementById('{{ $uid }}');
        if (!container) return;

        const grid = document.getElementById('{{ $uid }}-grid');
        const hidden = document.getElementById('{{ $uid }}-hidden');
        const loading = document.getElementById('{{ $uid }}-loading');
        const inputs = grid.querySelectorAll('input[data-index]');
        const form = container.closest('form');

        const length = parseInt(grid.dataset.length) || 6;
        const autoSubmit = grid.dataset.autoSubmit === '1';
        const submitDelay = parseInt(grid.dataset.submitDelay) || 350;
        let isSubmitting = false;

        // ═══════════════════════════════════════════════════════════════════
        // CORE FUNCTIONS
        // ═══════════════════════════════════════════════════════════════════

        function getValue() {
            return Array.from(inputs).map(i => i.value).join('');
        }

        function updateHidden() {
            if (hidden) hidden.value = getValue();
        }

        function focusAt(index) {
            if (inputs[index] && !inputs[index].disabled) {
                inputs[index].focus();
                inputs[index].select();
            }
        }

        function setGlow(input, active) {
            const glow = input.parentElement.querySelector('.pin-glow');
            if (glow) {
                if (active) {
                    glow.classList.add('opacity-100', 'scale-100');
                    glow.classList.remove('opacity-0', 'scale-95');
                } else {
                    glow.classList.remove('opacity-100', 'scale-100');
                    glow.classList.add('opacity-0', 'scale-95');
                }
            }
        }

        function triggerSubmit() {
            if (isSubmitting) return;
            isSubmitting = true;

            const code = getValue();

            // Show loading
            if (loading) loading.classList.remove('hidden');

            // Disable inputs & show success state
            inputs.forEach(input => {
                input.disabled = true;
                input.classList.add('!border-primary-500/70', '!bg-primary-500/10');
            });

            // Dispatch custom event for AJAX verification (can be caught by Alpine)
            const event = new CustomEvent('pin-complete', { 
                bubbles: true,
                detail: { code: code }
            });
            container.dispatchEvent(event);
            window.dispatchEvent(event);

            // If there's a form and we want direct submission, do it after delay
            // But only if no external handler prevents it
            if (form && !container.hasAttribute('data-no-form-submit')) {
                setTimeout(() => {
                    if (form.requestSubmit) form.requestSubmit();
                    else form.submit();
                }, submitDelay);
            }
        }

        function checkComplete() {
            const val = getValue();
            if (autoSubmit && val.length === length && /^\d+$/.test(val)) {
                triggerSubmit();
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // EVENT HANDLERS
        // ═══════════════════════════════════════════════════════════════════

        inputs.forEach((input, index) => {
            // Handle digit entry
            input.addEventListener('input', function(e) {
                let val = this.value;

                // Only keep last character and ensure it's a digit
                if (val.length > 1) val = val.slice(-1);
                if (!/^\d$/.test(val)) val = '';
                this.value = val;

                updateHidden();

                // Auto-advance
                if (val && index < length - 1) {
                    focusAt(index + 1);
                }

                checkComplete();
            });

            // Handle keyboard navigation
            input.addEventListener('keydown', function(e) {
                const key = e.key;

                if (key === 'Backspace') {
                    e.preventDefault();
                    if (this.value) {
                        this.value = '';
                    } else if (index > 0) {
                        inputs[index - 1].value = '';
                        focusAt(index - 1);
                    }
                    updateHidden();
                    return;
                }

                if (key === 'Delete') {
                    e.preventDefault();
                    this.value = '';
                    updateHidden();
                    return;
                }

                if (key === 'ArrowLeft' && index > 0) {
                    e.preventDefault();
                    focusAt(index - 1);
                    return;
                }

                if (key === 'ArrowRight' && index < length - 1) {
                    e.preventDefault();
                    focusAt(index + 1);
                    return;
                }

                // Block non-digits (except Tab/Enter)
                if (key.length === 1 && !/^\d$/.test(key) && !e.metaKey && !e.ctrlKey) {
                    e.preventDefault();
                }
            });

            // Handle paste
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const digits = paste.replace(/\D/g, '').slice(0, length);

                if (digits.length > 0) {
                    digits.split('').forEach((digit, i) => {
                        if (inputs[i]) inputs[i].value = digit;
                    });
                    updateHidden();
                    focusAt(Math.min(digits.length, length) - 1);
                    checkComplete();
                }
            });

            // Visual feedback
            input.addEventListener('focus', function() {
                this.select();
                setGlow(this, true);
            });

            input.addEventListener('blur', function() {
                setGlow(this, false);
            });
        });

        // ═══════════════════════════════════════════════════════════════════
        // EXTERNAL API (for reset on error)
        // ═══════════════════════════════════════════════════════════════════

        // Reset PIN on error (listen for custom event)
        container.addEventListener('pin-reset', function() {
            isSubmitting = false;
            if (loading) loading.classList.add('hidden');
            inputs.forEach(input => {
                input.disabled = false;
                input.value = '';
                input.classList.remove('!border-primary-500/70', '!bg-primary-500/10', '!border-green-500/70', '!bg-green-500/10');
            });
            updateHidden();
            setTimeout(() => focusAt(0), 100);
        });

        // Show success state
        container.addEventListener('pin-success', function() {
            inputs.forEach(input => {
                input.classList.remove('!border-primary-500/70', '!bg-primary-500/10');
                input.classList.add('!border-emerald-500/70', '!bg-emerald-500/10');
            });
            if (loading) loading.classList.add('hidden');
        });

        // ═══════════════════════════════════════════════════════════════════
        // INITIALIZATION
        // ═══════════════════════════════════════════════════════════════════

        // Auto-focus first input
        setTimeout(() => focusAt(0), 50);
    });
})();
</script>

<style>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-3px); }
    40%, 80% { transform: translateX(3px); }
}
.animate-shake { animation: shake 0.35s ease-in-out; }

/* Smooth digit appearance */
.pin-digit {
    font-variant-numeric: tabular-nums;
    font-feature-settings: "tnum";
    -webkit-tap-highlight-color: transparent;
}

/* Focus lift effect */
.pin-digit:focus {
    transform: translateY(-1px);
}

/* Success pulse */
.pin-digit.success-pulse {
    animation: successPulse 0.3s ease-out;
}
@keyframes successPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>
