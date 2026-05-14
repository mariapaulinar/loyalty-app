'use strict';

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Pickr Color Picker Integration
 *
 * Purpose:
 * Enterprise-grade color picker with premium UX for brand color selection.
 * Built on simonwep/pickr with custom Revolut-inspired styling.
 *
 * Design Tenets:
 * - Nano theme for minimal footprint
 * - Curated vibrant swatches for brand colors
 * - Real-time hex input synchronization
 * - Accessible keyboard navigation
 */

import Pickr from '@simonwep/pickr';
import '@simonwep/pickr/dist/themes/nano.min.css';

// Vibrant brand palette - 6 rows × 7 columns
const DEFAULT_SWATCHES = [
    '#ede9fe', '#fbcfe8', '#bfdbfe', '#a7f3d0', '#fde68a', '#fecaca', '#f8fafc',
    '#c4b5fd', '#f9a8d4', '#93c5fd', '#6ee7b7', '#fcd34d', '#fca5a5', '#f1f5f9',
    '#a78bfa', '#f472b6', '#60a5fa', '#34d399', '#fbbf24', '#f87171', '#e2e8f0',
    '#8b5cf6', '#ec4899', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#cbd5e1',
    '#6366f1', '#db2777', '#2563eb', '#059669', '#d97706', '#dc2626', '#94a3b8',
    '#4f46e5', '#be185d', '#1d4ed8', '#047857', '#b45309', '#b91c1c', '#1e293b',
];

/**
 * Initialize a color picker on a container element
 * @param {HTMLElement} container - The container with data-color-picker
 * @param {Object} options - Additional options
 */
function initColorPicker(container, options = {}) {
    const swatchEl = container.querySelector('[data-color-swatch]');
    const inputEl = container.querySelector('[data-color-input]');
    const triggerEl = container.querySelector('[data-color-trigger]');
    
    if (!swatchEl || !inputEl || !triggerEl) {
        console.warn('Color picker missing required elements', container);
        return null;
    }
    
    const defaultColor = inputEl.value || '#3b82f6';
    
    const pickr = Pickr.create({
        el: triggerEl,
        appendTo: 'body',
        theme: 'nano',
        default: defaultColor,
        useAsButton: true, // Use our custom swatch as button, don't replace it
        
        swatches: options.swatches || DEFAULT_SWATCHES,
        
        components: {
            // Main components
            preview: true,
            opacity: false, // Disable opacity for hex-only
            hue: true,
            
            // Input / output options
            interaction: {
                hex: true,
                rgba: false,
                hsla: false,
                hsva: false,
                cmyk: false,
                input: true,
                clear: false,
                save: false, // No Apply button - real-time sync
            },
        },
        
        // Position relative to swatch
        position: 'bottom-start',
        
        // Custom strings
        i18n: {
            'btn:save': 'Apply',
            'btn:cancel': 'Cancel',
            'btn:clear': 'Clear',
        },
    });
    
    // Track if we're programmatically updating to prevent recursion
    let isUpdating = false;
    
    // Real-time sync: Update input and swatch as user picks colors
    pickr.on('change', (color) => {
        if (color && !isUpdating) {
            isUpdating = true;
            const hex = color.toHEXA().toString();
            inputEl.value = hex;
            updateSwatchPreview(swatchEl, hex);
            
            // Dispatch change event for form validation
            inputEl.dispatchEvent(new Event('change', { bubbles: true }));
            
            // Reset flag after a short delay
            setTimeout(() => { isUpdating = false; }, 10);
        }
    });
    
    // Listen for manual input changes
    inputEl.addEventListener('input', (e) => {
        // Skip if we're in the middle of a programmatic update
        if (isUpdating) return;
        
        let value = e.target.value.trim();
        
        // Auto-add # if missing
        if (value && !value.startsWith('#')) {
            value = '#' + value;
        }
        
        if (isValidHex(value)) {
            isUpdating = true;
            pickr.setColor(value, true); // silent = true to prevent triggering events
            updateSwatchPreview(swatchEl, value);
            setTimeout(() => { isUpdating = false; }, 10);
        }
    });
    
    // Handle blur to normalize input
    inputEl.addEventListener('blur', (e) => {
        // Skip if we're in the middle of a programmatic update
        if (isUpdating) return;
        
        let value = e.target.value.trim();
        if (value && !value.startsWith('#')) {
            value = '#' + value;
            e.target.value = value;
        }
        if (isValidHex(value)) {
            isUpdating = true;
            pickr.setColor(value, true); // silent = true to prevent triggering events
            updateSwatchPreview(swatchEl, value);
            setTimeout(() => { isUpdating = false; }, 10);
        }
    });
    
    // Set initial swatch color
    updateSwatchPreview(swatchEl, defaultColor);
    
    // Store pickr instance on container
    container._pickr = pickr;
    
    return pickr;
}

/**
 * Update the swatch button's background color
 */
function updateSwatchPreview(swatchEl, hex) {
    const preview = swatchEl.querySelector('.color-swatch-preview');
    if (preview) {
        preview.style.backgroundColor = hex;
    }
}

/**
 * Validate hex color string
 */
function isValidHex(hex) {
    return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(hex);
}

/**
 * Auto-initialize color pickers on page load
 */
function autoInitColorPickers() {
    document.querySelectorAll('[data-color-picker]').forEach((container) => {
        if (!container._pickrInitialized) {
            initColorPicker(container);
            container._pickrInitialized = true;
        }
    });
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoInitColorPickers);
} else {
    autoInitColorPickers();
}

// Re-initialize when new content is loaded (for dynamic forms)
document.addEventListener('htmx:afterSwap', autoInitColorPickers);
document.addEventListener('alpine:initialized', autoInitColorPickers);

// Re-init when tabs become visible (for tabbed forms)
document.addEventListener('click', (e) => {
    // Check if a tab was clicked
    const tabButton = e.target.closest('[role="tab"], [data-tab], .tab-button');
    if (tabButton) {
        // Delay to allow tab content to become visible
        setTimeout(autoInitColorPickers, 100);
    }
});

// Export for manual initialization
window.initColorPicker = initColorPicker;
window.autoInitColorPickers = autoInitColorPickers;
window.ColorPickerSwatches = DEFAULT_SWATCHES;

export { initColorPicker, autoInitColorPickers, DEFAULT_SWATCHES, isValidHex };

