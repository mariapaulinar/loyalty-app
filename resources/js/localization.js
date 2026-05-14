"use strict";

/**
 * Helper function to get the content of a meta tag by name.
 * @param {string} name - The name of the meta tag.
 * @return {string|null} The content of the meta tag or null if not found.
 */
function getMetaContent(name) {
    const metaTag = document.querySelector(`meta[name="${name}"]`);
    return metaTag ? metaTag.getAttribute('content') : null;
}

// Retrieve values from meta tags and store them in global variables
window.headerLocale = getMetaContent('app-locale');
window.headerLanguage = getMetaContent('app-language');
window.headerCurrency = getMetaContent('app-currency');
window.headerTimeZone = getMetaContent('app-timezone');
window.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

/**
 * Formats a number according to the user's locale.
 * @param {number} number - The number to be formatted.
 * @return {string} The formatted number.
 */
window.appFormatNumber = function (number) {
    const locale = window.headerLanguage || navigator.language;
    return (isNaN(number) || number == null || number == '') ? number : new Intl.NumberFormat(locale).format(parseFloat(number));
}

/**
 * Formats all elements with the class "format-number" according to the user's locale.
 */
window.formatNumbers = function () {
    const elements = document.getElementsByClassName("format-number");
    for (let element of elements) {
        const newContent = window.appFormatNumber(element.textContent);
        if (element.classList.contains("replace-container")) {
            // Create a text node with the new content
            const textNode = document.createTextNode(newContent);
            
            // Replace the original element with the new text node
            element.parentNode.replaceChild(textNode, element);
        } else {
            element.textContent = newContent;
        }
    }
}

/**
 * Formats a currency amount according to the user's locale.
 * @param {number} amount - The currency amount to be formatted.
 * @param {string} [currency='USD'] - The currency code (e.g., 'USD', 'EUR', 'JPY').
 * @return {string} The formatted currency amount.
 */
window.appFormatCurrency = function (amount, currency = 'USD') {
    const locale = window.headerLanguage || navigator.language;
    return isNaN(amount)
        ? amount
        : new Intl.NumberFormat(locale, {
            style: "currency",
            currency: currency,
        }).format(amount);
}

/**
 * Formats a date string according to the user's locale and desired format.
 * 
 * @param {string} dateString - The date string to be formatted (in a format understood by JavaScript's Date object).
 * @param {string} dateFormat - The desired format for the date string. Expected values are 'md', 'lg', 'xl', or undefined.
 *                              'md': short weekday, 2-digit day, and long month.
 *                              'lg': short weekday, 2-digit day, long month, and numeric year.
 *                              'xl': long weekday, 2-digit day, long month, and numeric year.
 * @return {string} The formatted date string according to the user's locale and the specified dateFormat. 
 *                  If the dateString is not a valid date, the original dateString is returned.
 */
window.appFormatDate = function (dateString, dateFormat) {
    // Convert the dateString into a Date object
    const date = new Date(dateString);

    // Check if date is valid, if not return the original string
    if (isNaN(date)) {
        return dateString;
    }

    // Use the language from the header or fallback to the browser's language
    const locale = window.headerLanguage || navigator.language;

    // Default format options
    let formatOptions = { timeZone: window.headerTimeZone };

    // Set format options based on dateFormat
    switch (dateFormat) {
        case 'md':
            formatOptions = { 
                ...formatOptions,
                weekday: 'short', 
                day: '2-digit', 
                month: 'long',
            };
            break;
        case 'lg':
            formatOptions = { 
                ...formatOptions,
                weekday: 'short', 
                day: '2-digit', 
                month: 'long', 
                year: 'numeric'
            };
            break;
        case 'xl':
            formatOptions = { 
                ...formatOptions,
                weekday: 'long', 
                day: '2-digit', 
                month: 'long', 
                year: 'numeric'
            };
            break;
        default:
            // You can specify a default format option for any unexpected dateFormat values
            formatOptions = { 
                ...formatOptions,
                day: '2-digit', 
                month: 'short', 
                year: 'numeric'
            };
            break;
    }

    // Create a DateTimeFormat object with the provided locale and format options
    const dateTimeFormat = new Intl.DateTimeFormat(locale, formatOptions);

    // Return the formatted date
    return dateTimeFormat.format(date);
};

/**
 * Formats all elements with the class "format-date" according to the user's locale.
 * It fetches the date and format from data attributes of the element, and uses these to format the date.
 */
window.formatDates = function () {
    // Fetch all elements with the class "format-date"
    const elements = document.getElementsByClassName("format-date");

    // Iterate over each element
    for (const element of elements) {
        // Fetch the 'data-date' attribute, or fallback to the text content of the element
        const date = element.getAttribute('data-date') || element.textContent;

        // Fetch the 'data-date-format' attribute, or fallback to 'plain'
        const dateFormat = element.getAttribute('data-date-format') || 'plain';

        // If a date exists, format it according to the dateFormat and update the text content of the element
        if (date) {
            element.textContent = window.appFormatDate(date, dateFormat);
        }
    }
};

/**
 * Formats a date range according to the user's locale.
 * Handles cases where only from, only to, or both dates are provided.
 * 
 * @param {string|null} fromDateString - The start date string (can be null).
 * @param {string|null} toDateString - The end date string (can be null).
 * @param {string} [separator=' – '] - The separator between dates.
 * @return {string} The formatted date range string.
 */
window.appFormatDateRange = function (fromDateString, toDateString, separator = ' – ') {
    const locale = window.headerLanguage || navigator.language;
    const timeZone = window.headerTimeZone;
    
    const fromDate = fromDateString ? new Date(fromDateString) : null;
    const toDate = toDateString ? new Date(toDateString) : null;
    
    const isFromValid = fromDate && !isNaN(fromDate);
    const isToValid = toDate && !isNaN(toDate);
    
    // If neither date is valid, return empty string
    if (!isFromValid && !isToValid) {
        return '';
    }
    
    // Format options for the "from" date (shorter format when both dates exist)
    const fromFormatOptions = {
        timeZone: timeZone,
        day: '2-digit',
        month: 'short',
    };
    
    // Format options for the "to" date (includes year)
    const toFormatOptions = {
        timeZone: timeZone,
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    };
    
    // If only "from" date exists, include year
    const fromOnlyFormatOptions = {
        timeZone: timeZone,
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    };
    
    // Both dates exist - show range
    if (isFromValid && isToValid) {
        const fromFormatter = new Intl.DateTimeFormat(locale, fromFormatOptions);
        const toFormatter = new Intl.DateTimeFormat(locale, toFormatOptions);
        return fromFormatter.format(fromDate) + separator + toFormatter.format(toDate);
    }
    
    // Only "from" date exists
    if (isFromValid) {
        const formatter = new Intl.DateTimeFormat(locale, fromOnlyFormatOptions);
        return formatter.format(fromDate);
    }
    
    // Only "to" date exists
    if (isToValid) {
        const formatter = new Intl.DateTimeFormat(locale, toFormatOptions);
        return formatter.format(toDate);
    }
    
    return '';
};

/**
 * Formats all elements with the class "format-date-range" according to the user's locale.
 * Uses data-date-from and data-date-to attributes for the date range.
 * Optionally uses data-prefix-from and data-prefix-to for labels when only one date exists.
 */
window.formatDateRanges = function () {
    const elements = document.getElementsByClassName("format-date-range");

    for (const element of elements) {
        const fromDate = element.getAttribute('data-date-from');
        const toDate = element.getAttribute('data-date-to');
        const prefixFrom = element.getAttribute('data-prefix-from') || '';
        const prefixTo = element.getAttribute('data-prefix-to') || '';
        
        // Determine which prefix to use based on which dates exist
        let prefix = '';
        if (fromDate && toDate) {
            // Both dates - no prefix needed
            prefix = '';
        } else if (fromDate && !toDate) {
            // Only from date
            prefix = prefixFrom;
        } else if (!fromDate && toDate) {
            // Only to date
            prefix = prefixTo;
        }
        
        const formattedRange = window.appFormatDateRange(fromDate, toDate);
        
        if (formattedRange) {
            element.textContent = prefix ? prefix + ' ' + formattedRange : formattedRange;
        }
    }
};

/**
 * Formats a date time according to the user's locale.
 * @param {string} dateTimeString - The date time string to be formatted.
 * @param {boolean} local - A flag to determine whether to convert timezone or not.
 * @return {string} The formatted date time.
 */
window.appFormatDateTime = function (dateTimeString, local = false) {
    const date = new Date(dateTimeString);
    if (isNaN(date)) {
        return dateTimeString;
    }
    const locale = window.headerLanguage || navigator.language;
    const formatOptions = {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "numeric"
    };
    
    if (!local) {
        // Only set the timeZone option if local is false.
        formatOptions.timeZone = window.headerTimeZone;
    }
    
    const dateTimeFormat = new Intl.DateTimeFormat(locale, formatOptions);
    return dateTimeFormat.format(date);
}

/**
 * Formats all elements with the class "format-date-time" or "format-date-time-local" according to the user's locale.
 */
window.formatDateTimes = function () {
    const elementsDateTime = document.getElementsByClassName("format-date-time");
    for (let element of elementsDateTime) {
        const dateTime = element.getAttribute('data-date-time') || element.textContent;
        if (dateTime) {
            element.textContent = window.appFormatDateTime(dateTime);
        }
    }

    const elementsDateTimeLocal = document.getElementsByClassName("format-date-time-local");
    for (let element of elementsDateTimeLocal) {
        const dateTimeLocal = element.getAttribute('data-date-time-local') || element.textContent;
        if (dateTimeLocal) {
            element.textContent = window.appFormatDateTime(dateTimeLocal, true);
        }
    }
};

// Call the formatting functions
window.formatDateTimes();
window.formatDates();
window.formatDateRanges();
window.formatNumbers();