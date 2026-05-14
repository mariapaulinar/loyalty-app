"use strict";

/**
 * Modal System - Premium UI Components
 * 
 * Provides beautiful, accessible modal dialogs with:
 * - Frosted glass backdrop effect
 * - Smooth animations
 * - Keyboard accessibility
 * - Dark mode support
 */

/**
 * Create and show a basic alert modal with given content and options.
 * @param {string} content - The content to be displayed in the modal.
 * @param {Object} [opts] - Optional settings for the modal.
 */
window.appAlert = function(content, opts = {}) {
    const id = `modal_${Math.floor(Date.now() / 1000).toString(16)}${Math.random().toString(16).substr(2, 8)}`;

    const closable = opts.closable ?? true;
    const title = opts.title ?? false;
    const btnCloseText = opts.btnClose?.text ?? _lang.ok;
    const btnCloseClick = opts.btnClose?.click ?? function () { modal.hide(); };

    const dom = document.createElement("div");
    dom.innerHTML = `
        <div id="${id}" tabindex="-1" aria-hidden="true" 
            class="fixed inset-0 z-[9999] hidden overflow-y-auto overflow-x-hidden" 
            x-data="modal">
            
            <!-- Frosted Glass Backdrop -->
            <div class="fixed inset-0 bg-secondary-950/60 backdrop-blur-sm transition-opacity" @click="${closable ? 'close' : ''}"></div>
            
            <!-- Modal Container -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md transform transition-all">
                    <!-- Modal Content -->
                    <div class="relative bg-white dark:bg-secondary-900 rounded-2xl shadow-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                        
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-secondary-100 dark:border-secondary-800" x-show="title !== false">
                            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
                                ${title}
                            </h3>
                            <button type="button" @click="close" 
                                class="p-2 -mr-2 text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800 rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                <span class="sr-only">${_lang.close}</span>
                            </button>
                        </div>
                        
                        <!-- Body -->
                        <div class="px-6 py-5">
                            <div class="text-sm text-secondary-600 dark:text-secondary-400 leading-relaxed">
                                ${content}
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-secondary-50 dark:bg-secondary-800/50 border-t border-secondary-100 dark:border-secondary-800">
                            <button type="button" @click="close" id="${id}-focusButton"
                                class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-xl
                                    bg-primary-600 hover:bg-primary-700 text-white
                                    focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-secondary-900
                                    transition-colors">
                                ${btnCloseText}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    Alpine.data("modal", () => ({
        title: title,
        close() {
            btnCloseClick();
            modal.hide();
        },
    }));

    document.body.appendChild(dom);

    const $targetEl = document.getElementById(id);

    const options = {
        placement: "",
        backdrop: null, // We handle backdrop ourselves
        closable: closable,
        onHide: () => {
            if (dom && dom.parentNode) {
                dom.parentNode.removeChild(dom);
            }
        },
        onShow: () => {
            document.getElementById(`${id}-focusButton`).focus();
        },
        onToggle: () => {},
    };

    const modal = new Modal($targetEl, options);
    modal.show();
}

/**
 * Create and display a confirmation modal with the given title, content, and options.
 * @param {string} title - The title to be displayed in the modal.
 * @param {string} content - The content to be displayed in the modal.
 * @param {Object} [opts] - Optional settings for the modal.
 * 
 * @example
 * appConfirm('Confirm action', 'Are you sure?', {
 *     btnConfirm: { text: 'Yes', click: () => console.log('Confirmed') }
 * });
 */
window.appConfirm = function (title, content, opts = {}) {
    const id = `modal_${Math.floor(Date.now() / 1000).toString(16)}${Math.random().toString(16).substr(2, 8)}`;
  
    const closable = opts.closable ?? true;
    const btnCancelText = opts.btnCancel?.text ?? _lang.cancel;
    const btnConfirmText = opts.btnConfirm?.text ?? _lang.ok;
    const btnConfirmClick = opts.btnConfirm?.click ?? function () { modal.hide(); };
    const btnConfirmType = opts.btnConfirm?.type ?? 'primary'; // 'primary' or 'danger'
  
    const dom = document.createElement("div");
    dom.innerHTML = `
        <div id="${id}" tabindex="-1" aria-hidden="true" 
            class="fixed inset-0 z-[9999] hidden overflow-y-auto overflow-x-hidden" 
            x-data="modal">
            
            <!-- Frosted Glass Backdrop -->
            <div class="fixed inset-0 bg-secondary-950/60 backdrop-blur-sm transition-opacity" @click="${closable && '!loading'} ? close() : null"></div>
            
            <!-- Modal Container -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md transform transition-all">
                    <!-- Modal Content -->
                    <div class="relative bg-white dark:bg-secondary-900 rounded-2xl shadow-2xl border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                        
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-secondary-100 dark:border-secondary-800">
                            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
                                ${title}
                            </h3>
                            
                            <!-- Close button (hidden when loading) -->
                            <button type="button" x-show="!loading" @click="close" 
                                class="p-2 -mr-2 text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800 rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                <span class="sr-only">${_lang.close}</span>
                            </button>
                            
                            <!-- Loading spinner -->
                            <div x-show="loading" class="p-2 -mr-2">
                                <svg class="w-5 h-5 text-primary-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                        
                        <!-- Body -->
                        <div class="px-6 py-5">
                            <div class="text-sm text-secondary-600 dark:text-secondary-400 leading-relaxed">
                                ${content}
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-secondary-50 dark:bg-secondary-800/50 border-t border-secondary-100 dark:border-secondary-800">
                            <button type="button" @click="close" x-bind:disabled="loading"
                                class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-xl
                                    text-secondary-700 dark:text-secondary-300 
                                    bg-white dark:bg-secondary-800
                                    border border-secondary-300 dark:border-secondary-600
                                    hover:bg-secondary-50 dark:hover:bg-secondary-700
                                    focus:outline-none focus:ring-2 focus:ring-secondary-500 focus:ring-offset-2 dark:focus:ring-offset-secondary-900
                                    disabled:opacity-50 disabled:cursor-not-allowed
                                    transition-colors">
                                ${btnCancelText}
                            </button>
                            <button type="button" @click="confirm" x-bind:disabled="loading" id="${id}-focusButton"
                                class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-xl
                                    ${btnConfirmType === 'danger' ? 'bg-red-600 hover:bg-red-700' : 'bg-primary-600 hover:bg-primary-700'} text-white
                                    focus:outline-none focus:ring-2 ${btnConfirmType === 'danger' ? 'focus:ring-red-500' : 'focus:ring-primary-500'} focus:ring-offset-2 dark:focus:ring-offset-secondary-900
                                    disabled:opacity-50 disabled:cursor-not-allowed
                                    transition-colors">
                                <span x-show="loading" class="mr-2">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                                ${btnConfirmText}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    Alpine.data("modal", () => ({
        loading: false,
    
        confirm() {
            this.loading = true;
            btnConfirmClick();
            modal.hide();
        },
    
        close() {
            modal.hide();
        },
    }));
    
    document.body.appendChild(dom);
    
    const $targetEl = document.getElementById(id);
    
    const options = {
        placement: "",
        backdrop: null, // We handle backdrop ourselves
        closable: closable,
        onHide: () => {
            if (dom && dom.parentNode) {
                dom.parentNode.removeChild(dom);
            }
        },
        onShow: () => {
            document.getElementById(`${id}-focusButton`).focus();
        },
        onToggle: () => {},
    };
    
    const modal = new Modal($targetEl, options);
    modal.show();
};
      
/**
 * Create and display a custom modal with the given title, content, and options.
 * @param {string} title - The title to be displayed in the modal.
 * @param {string} content - The content to be displayed in the modal.
 * @param {Object} [opts] - Optional settings for the modal.
 */
window.appModal = function (title, content, opts) {
    // Implementation for custom modals if needed
};
