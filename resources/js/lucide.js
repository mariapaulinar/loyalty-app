/**
 * Lucide Icons Integration
 * 
 * Initializes Lucide icons across the application.
 * Icons are rendered using data-lucide attributes in HTML.
 */

import { createIcons, icons } from 'lucide';

// Function to initialize icons
function initLucideIcons() {
    createIcons({ icons });
}

// Initialize immediately (for static content)
initLucideIcons();

// Initialize Lucide icons on page load
document.addEventListener('DOMContentLoaded', () => {
    initLucideIcons();
});

// Re-initialize icons after dynamic content loads (e.g., HTMX, Alpine)
document.addEventListener('htmx:afterSwap', () => {
    initLucideIcons();
});

// Re-initialize after Alpine.js processes content
document.addEventListener('alpine:initialized', () => {
    initLucideIcons();
});

// Listen for custom re-initialization event (from Alpine.js dynamic content)
document.addEventListener('reinit-lucide', () => {
    initLucideIcons();
});

// Also listen for Alpine mutations
if (window.Alpine) {
    window.Alpine.plugin((Alpine) => {
        Alpine.directive('lucide', (el) => {
            initLucideIcons();
        });
    });
}

// Export for manual initialization if needed
export { createIcons, icons, initLucideIcons };
