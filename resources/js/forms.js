"use strict";

/**
 * Processes the form validation response and updates the UI.
 * @param {string} response - The JSON string containing the form validation response.
 */
function processFormValidation(response) {
  let formResponse = JSON.parse(response);

  for (const key in formResponse) {
    // Add 'is-invalid' class to the field and attach event listeners
    let field = document.getElementById(key);
    field.classList.add('is-invalid');
    addFieldEventListeners(field);

    // Insert feedback message after the field
    insertFeedbackMessage(field, formResponse[key]);
  }
}

// Expose the processFormValidation function to the global scope
window.processFormValidation = processFormValidation;

/**
 * Adds event listeners to a form field for removing 'is-invalid' class.
 * @param {HTMLElement} field - The form field element.
 */
function addFieldEventListeners(field) {
  field.addEventListener('change', function () {
    this.classList.remove('is-invalid');
  });
  field.addEventListener('keydown', function () {
    this.classList.remove('is-invalid');
  });
}

/**
 * Inserts a feedback message after the form field.
 * @param {HTMLElement} field - The form field element.
 * @param {string} message - The feedback message to display.
 */
function insertFeedbackMessage(field, message) {
  let invalidFeedback = document.createElement('div');
  invalidFeedback.textContent = message;
  invalidFeedback.className = 'invalid-feedback';
  field.parentNode.insertBefore(invalidFeedback, field.nextSibling);
}

/**
 * Find the tab containing the first element with a validation error and click the tab to activate it.
 *
 * Detection strategy (in order of priority):
 * 1. Standard form inputs (input, textarea, select) with border-red* error classes
 * 2. Error message elements (p, span, div with text-red-600 class) - catches custom components
 *    like icon-picker that use hidden inputs but display visible error messages
 */
function openTabWithInvalidElement() {
  let firstInvalidElement = null;

  // Strategy 1: Look for form elements with error border classes
  // This catches standard inputs, selects, textareas styled with error borders
  const allInputs = document.querySelectorAll('input, textarea, select');
  for (const input of allInputs) {
    const classes = input.className;
    if (classes.includes('border-red')) {
      firstInvalidElement = input;
      break;
    }
  }

  // Strategy 2: Look for error message elements
  // This catches custom components (like icon-picker) that use hidden inputs
  // but display visible error messages with text-red-600 class
  if (!firstInvalidElement) {
    const errorMessages = document.querySelectorAll('.text-red-600, .text-red-500');
    for (const errorMsg of errorMessages) {
      // Verify this is a validation error message (contains alert-circle icon or is in a form)
      const isInForm = errorMsg.closest('form');
      if (isInForm) {
        firstInvalidElement = errorMsg;
        break;
      }
    }
  }

  if (!firstInvalidElement) {
    return;
  }

  // Traverse up the DOM tree to find the parent tab panel
  let currentElement = firstInvalidElement;
  const xShowRegex = /activeTab\s*===\s*['"]tab-(\d+)['"]/;

  while (currentElement) {
    const xShowAttribute = currentElement.getAttribute('x-show');

    if (xShowAttribute) {
      const match = xShowAttribute.match(xShowRegex);

      if (match && match[1]) {
        // Extract the tab index number (e.g., "tab-3" -> 3)
        const tabIndex = match[1];

        // Use the reliable data-tab-index selector
        const tabButton = document.querySelector(`[data-tab-index="${tabIndex}"]`);

        if (tabButton) {
          tabButton.click();
          return;
        } else {
          console.error(`Tab button with index ${tabIndex} not found`);
          return;
        }
      }
    }

    currentElement = currentElement.parentElement;
  }
}

// Expose the openTabWithInvalidElement function to the global scope
window.openTabWithInvalidElement = openTabWithInvalidElement;


/**
 * Open tab by index.
 */
function openTab(activeTabValue) {
  // Find the tab button using data attribute for reliable selection
  const tabElement = document.querySelector(`[data-tab-index="${activeTabValue}"]`);
  
  if (tabElement) {
    tabElement.click();
  } else {
    console.error(`Tab with index ${activeTabValue} not found`);
  }
}

// Expose the openTab function to the global scope
window.openTab = openTab;