'use strict';

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * PIN Input - 6-Digit Code Entry • Memory-Safe
 * 
 * Auto-advance on digit • paste support • auto-submit when complete
 * Cleanup via AbortController • keyboard nav (arrows, backspace)
 */

// WeakMap for per-container state • prevents memory leaks
const instanceMap = new WeakMap();

/**
 * Initialize PIN input instance • returns cleanup function
 * 
 * @param {HTMLElement} container - The container element
 * @param {Object} options - Configuration options
 * @returns {Object|null} - Instance object or null if already initialized
 */
function initPinInput(container, options = {}) {
    const {
        id,
        length = 6,
        autoSubmit = false,
        submitDelay = 400
    } = options;

    if (!container || instanceMap.has(container)) {
        return null;
    }

    const inputs = container.querySelectorAll('[data-pin-index]');
    const form = container.closest('form');
    const hiddenInput = id ? document.getElementById(`${id}-value`) : null;

    if (!inputs.length) {
        console.error('[PinInput] No inputs found');
        return null;
    }

    const abortController = new AbortController();
    const { signal } = abortController;

    // Update hidden field • check for completion
    const updatePinValue = () => {
        const pin = Array.from(inputs).map(input => input.value).join('');

        if (hiddenInput) {
            hiddenInput.value = pin;

            // Trigger Alpine reactivity
            const alpineComponent = hiddenInput.closest('[x-data]');
            if (alpineComponent?._x_dataStack) {
                alpineComponent._x_dataStack[0].pinInput = pin;
            }
        }

        // Auto-submit when complete
        if (autoSubmit && pin.length === length && /^\d+$/.test(pin)) {
            const alpineComponent = container.closest('[x-data]');

            // Set submitting state
            if (alpineComponent?._x_dataStack) {
                alpineComponent._x_dataStack[0].isSubmitting = true;
            }

            // Visual feedback: success state
            inputs.forEach(input => {
                input.classList.add('!border-primary-500', '!ring-4', '!ring-primary-500/20');
                input.classList.remove('border-secondary-300', 'dark:border-secondary-600');
            });

            // Submit after brief delay - use requestSubmit() to fire submit event (Alpine-compatible)
            // Falls back to submit() for older browsers
            setTimeout(() => {
                if (form) {
                    form.requestSubmit ? form.requestSubmit() : form.submit();
                }
            }, submitDelay);
        }
    };

    // Attach listeners to each input
    inputs.forEach((input, index) => {
        // Input • handle typing
        input.addEventListener('input', () => {
            const value = input.value;

            // Single digit only
            if (value.length > 1) {
                input.value = value.slice(0, 1);
            }

            // Numbers only
            if (value && !/^\d$/.test(value)) {
                input.value = '';
                return;
            }

            // Auto-advance
            if (value && index < length - 1) {
                inputs[index + 1]?.focus();
                inputs[index + 1]?.select();
            }

            updatePinValue();
        }, { signal });

        // Keydown • navigation and backspace
        input.addEventListener('keydown', (event) => {
            // Backspace: clear and move back
            if (event.key === 'Backspace') {
                if (!input.value && index > 0) {
                    event.preventDefault();
                    inputs[index - 1]?.focus();
                    inputs[index - 1]?.select();
                    if (inputs[index - 1]) {
                        inputs[index - 1].value = '';
                    }
                    updatePinValue();
                } else if (input.value) {
                    input.value = '';
                    updatePinValue();
                }
            }

            // Arrow navigation
            if (event.key === 'ArrowLeft' && index > 0) {
                event.preventDefault();
                inputs[index - 1]?.focus();
                inputs[index - 1]?.select();
            }

            if (event.key === 'ArrowRight' && index < length - 1) {
                event.preventDefault();
                inputs[index + 1]?.focus();
                inputs[index + 1]?.select();
            }

            // Block non-numeric
            if (event.key.length === 1 && !/^\d$/.test(event.key) && !event.metaKey && !event.ctrlKey) {
                event.preventDefault();
            }
        }, { signal });

        // Paste • handle pasted codes
        input.addEventListener('paste', (event) => {
            event.preventDefault();

            const paste = (event.clipboardData || window.clipboardData).getData('text');
            const digits = paste.replace(/\D/g, '').slice(0, length);

            if (digits.length > 0) {
                digits.split('').forEach((digit, idx) => {
                    if (inputs[idx]) {
                        inputs[idx].value = digit;
                    }
                });

                // Focus last filled
                const lastIndex = Math.min(digits.length, length) - 1;
                inputs[lastIndex]?.focus();
                inputs[lastIndex]?.select();

                updatePinValue();
            }
        }, { signal });

        // Focus • select all for easy replacement
        input.addEventListener('focus', () => {
            input.select();
        }, { signal });
    });

    // Auto-focus first input
    setTimeout(() => {
        if (inputs[0] && container.offsetParent !== null) {
            inputs[0].focus();
        }
    }, 50);

    // Store instance with cleanup
    const instance = {
        inputs,
        updatePinValue,
        reset: () => {
            inputs.forEach(input => input.value = '');
            updatePinValue();
            inputs[0]?.focus();
        },
        destroy: () => {
            abortController.abort();
            instanceMap.delete(container);
        }
    };

    instanceMap.set(container, instance);
    return instance;
}

/**
 * Initialize all PIN inputs on page
 */
function initAllPinInputs() {
    const containers = document.querySelectorAll('[id$="-container"][id^="pin-input-"]');

    containers.forEach(container => {
        const id = container.dataset.pinId || container.id.replace('-container', '').replace('pin-input-', '');
        const length = parseInt(container.dataset.pinLength, 10) || 6;
        const autoSubmit = container.dataset.autoSubmit === 'true';
        const submitDelay = parseInt(container.dataset.submitDelay, 10) || 400;

        initPinInput(container, { id, length, autoSubmit, submitDelay });
    });
}

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAllPinInputs);
} else {
    initAllPinInputs();
}

// HMR cleanup
if (import.meta.hot) {
    import.meta.hot.dispose(() => {
        // Cleanup all instances
        document.querySelectorAll('[id$="-container"][id^="pin-input-"]').forEach(container => {
            const instance = instanceMap.get(container);
            instance?.destroy();
        });
    });
}

// Expose to window for non-module usage
window.initPinInput = initPinInput;
window.initAllPinInputs = initAllPinInputs;

export { initPinInput, initAllPinInputs };

