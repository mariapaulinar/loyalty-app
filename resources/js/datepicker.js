/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Flatpickr Date Picker Integration
 * Modern date picker for datetime-local inputs with premium styling
 * Supports full localization via Laravel translation files
 */

'use strict';

import flatpickr from 'flatpickr';

/**
 * Get localized Flatpickr configuration from Laravel translations
 */
function getLocalizedConfig() {
    // Check if translations are available (check both window._lang and global _lang)
    const langData = window._lang || (typeof _lang !== 'undefined' ? _lang : null);
    
    if (!langData || !langData.datepicker) {
        // Fallback to English if translations not available
        return {
            locale: {
                weekdays: {
                    shorthand: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
                    longhand: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
                },
                months: {
                    shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    longhand: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
                },
                rangeSeparator: ' to ',
                weekAbbreviation: 'Wk',
                scrollTitle: 'Scroll to increment',
                toggleTitle: 'Click to toggle',
                firstDayOfWeek: 0
            },
            altFormat: 'F j, Y'
        };
    }

    const datepickerConfig = langData.datepicker;

    return {
        locale: {
            weekdays: datepickerConfig.weekdays,
            months: datepickerConfig.months,
            rangeSeparator: datepickerConfig.rangeSeparator,
            weekAbbreviation: datepickerConfig.weekAbbreviation,
            scrollTitle: datepickerConfig.scrollTitle,
            toggleTitle: datepickerConfig.toggleTitle,
            firstDayOfWeek: datepickerConfig.firstDayOfWeek
        },
        altFormat: datepickerConfig.dateFormat
    };
}

/**
 * Initialize all datetime-local inputs with flatpickr
 */
document.addEventListener('DOMContentLoaded', () => {
    initializeDatePickers();
});

/**
 * Initialize date pickers on datetime-local inputs
 */
function initializeDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="datetime-local"][data-datepicker]');
    
    dateInputs.forEach(input => {
        // Skip if already initialized
        if (input._flatpickr) {
            return;
        }

        // Get initial value (format: "YYYY-MM-DD HH:MM")
        const initialValue = input.value;
        let parsedInitialDate = null;
        
        // Parse initial value if exists
        if (initialValue && initialValue.trim()) {
            // Handle format: "2025-01-15 14:30" or "2025-01-15"
            const parts = initialValue.split(' ');
            const datePart = parts[0];
            
            if (datePart && datePart.match(/^\d{4}-\d{2}-\d{2}$/)) {
                const [year, month, day] = datePart.split('-');
                parsedInitialDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
            }
        }

        // Get localized configuration
        const localizedConfig = getLocalizedConfig();

        // Initialize flatpickr with localization
        const fp = flatpickr(input, {
            enableTime: false,
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: localizedConfig.altFormat,
            allowInput: true,
            time_24hr: true,
            
            // Apply locale settings
            locale: localizedConfig.locale,
            
            // Set default time to end of day (23:59)
            defaultHour: 23,
            defaultMinute: 59,
            
            // Set initial date if we have one
            defaultDate: parsedInitialDate,
            
            // Styling hooks
            prevArrow: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>',
            nextArrow: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>',
            
            // On ready, set initial value with time
            onReady: function(selectedDates, dateStr, instance) {
                if (initialValue && initialValue.trim()) {
                    // Preserve the original value with time
                    input.value = initialValue;
                }
            },
            
            // On change, append time to the date
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length > 0) {
                    // Format: YYYY-MM-DD HH:MM
                    const date = selectedDates[0];
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    
                    // Set value with end-of-day time (23:59)
                    input.value = `${year}-${month}-${day} 23:59`;
                }
            },
            
            // When calendar opens
            onOpen: function(selectedDates, dateStr, instance) {
                // Add dark mode class if needed
                if (document.documentElement.classList.contains('dark')) {
                    instance.calendarContainer.classList.add('dark-theme');
                }
            }
        });
    });
}

// Re-initialize when content changes (for dynamic forms)
if (typeof MutationObserver !== 'undefined') {
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.addedNodes.length) {
                initializeDatePickers();
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

// Export for manual initialization if needed
window.initializeDatePickers = initializeDatePickers;
