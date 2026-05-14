"use strict";

import { Tooltip } from "flowbite";

/**
 * Initializes tooltips for all elements with the 'data-fb="tooltip"' attribute.
 * Each element will be wrapped in a div with a unique 'data-tooltip-target' attribute,
 * and the tooltip itself will be appended after the element inside the wrapper.
 */
function initializeTooltips() {
    // Find all elements with the 'data-fb="tooltip"' attribute
    const elements = document.querySelectorAll('[data-fb="tooltip"]');
  
    // Iterate through each element and create a tooltip
    elements.forEach((element) => {
      // Get the tooltip text from the element's 'title' attribute
      const tooltipText = element.getAttribute('title');
      // Get optional classes for <div> container from the element's 'data-fb-class' attribute
      const tooltipClass = element.getAttribute('data-fb-class');

      // Remove the 'title' attribute to prevent double tooltips
      element.removeAttribute('title');

      // Generate a unique ID for each tooltip
      const uniqueId = Date.now() + Math.floor(Math.random() * 1000);
  
      // Create a wrapper div with the 'data-tooltip-target' attribute and unique ID
      const wrapper = document.createElement('div');
      wrapper.setAttribute('data-tooltip-target', `results-tooltip-${uniqueId}`);
      wrapper.setAttribute('data-tooltip-placement', 'top');
      wrapper.setAttribute('class', tooltipClass);

      // Create the tooltip div with the appropriate ID, role, and CSS classes
      const tooltip = document.createElement('div');
      tooltip.id = `results-tooltip-${uniqueId}`;
      tooltip.setAttribute('role', 'tooltip');
      tooltip.className =
        'absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-black';

      // Set the tooltip text
      tooltip.innerHTML = tooltipText;
  
      // Create a div for the tooltip arrow
      const tooltipArrow = document.createElement('div');
      tooltipArrow.className = 'tooltip-arrow flex';
      tooltipArrow.setAttribute('data-popper-arrow', '');
      tooltip.appendChild(tooltipArrow);

      // Wrap the element in the wrapper div and append the tooltip after the element
      element.insertAdjacentElement('beforebegin', wrapper);
      wrapper.appendChild(element);
      wrapper.insertAdjacentElement('afterend', tooltip);
    });
}

// Initialize tooltips once the DOM content is fully loaded
document.addEventListener('DOMContentLoaded', () => {
  initializeTooltips();
});
