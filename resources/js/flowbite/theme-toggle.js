"use strict";

/**
 * Theme Toggle Functionality
 * Handles dark/light mode switching for both mobile and desktop
 */

// Global toggleTheme function for onclick usage (instant switch, no jitter)
window.toggleTheme = function() {
  const html = document.documentElement;
  const isDark = html.classList.contains("dark");

  // Disable all transitions temporarily to prevent jitter
  html.classList.add("disable-transitions");

  // Force a reflow to ensure the class is applied
  html.offsetHeight;

  // Toggle theme immediately
  if (isDark) {
    html.classList.remove("dark");
    localStorage.setItem("color-theme", "light");
  } else {
    html.classList.add("dark");
    localStorage.setItem("color-theme", "dark");
  }

  // Re-enable transitions after theme has switched (next frame)
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      html.classList.remove("disable-transitions");
    });
  });
};

document.addEventListener('DOMContentLoaded', () => {
  // Get all theme toggle elements
  const themeToggleDarkIcon = document.getElementById("theme-toggle-dark-icon");
  const themeToggleLightIcon = document.getElementById("theme-toggle-light-icon");
  const themeToggleDarkIconDesktop = document.getElementById("theme-toggle-dark-icon-desktop");
  const themeToggleLightIconDesktop = document.getElementById("theme-toggle-light-icon-desktop");
  const themeToggleDarkIconMobile = document.getElementById("theme-toggle-dark-icon-mobile");
  const themeToggleLightIconMobile = document.getElementById("theme-toggle-light-icon-mobile");

  // Change the icons based on previous settings
  if (
    localStorage.getItem("color-theme") === "dark" ||
    (!("color-theme" in localStorage) &&
      window.matchMedia("(prefers-color-scheme: dark)").matches)
  ) {
    // Show light icon (we're in dark mode)
    themeToggleLightIcon?.classList.remove("hidden");
    themeToggleLightIconDesktop?.classList.remove("hidden");
    themeToggleLightIconMobile?.classList.remove("hidden");
  } else {
    // Show dark icon (we're in light mode)
    themeToggleDarkIcon?.classList.remove("hidden");
    themeToggleDarkIconDesktop?.classList.remove("hidden");
    themeToggleDarkIconMobile?.classList.remove("hidden");
  }

  // Internal toggleTheme that also handles icon toggling for legacy buttons
  const toggleThemeWithIcons = () => {
    // Toggle icons for mobile header
    themeToggleDarkIcon?.classList.toggle("hidden");
    themeToggleLightIcon?.classList.toggle("hidden");

    // Toggle icons for desktop
    themeToggleDarkIconDesktop?.classList.toggle("hidden");
    themeToggleLightIconDesktop?.classList.toggle("hidden");

    // Toggle icons for mobile menu
    themeToggleDarkIconMobile?.classList.toggle("hidden");
    themeToggleLightIconMobile?.classList.toggle("hidden");

    // Call global toggleTheme
    window.toggleTheme();
  };

  // Add click listeners to all toggle buttons
  const themeToggleBtn = document.getElementById("theme-toggle");
  const themeToggleBtnDesktop = document.getElementById("theme-toggle-desktop");
  const themeToggleBtnMobile = document.getElementById("theme-toggle-mobile");

  themeToggleBtn?.addEventListener("click", toggleThemeWithIcons);
  themeToggleBtnDesktop?.addEventListener("click", toggleThemeWithIcons);
  themeToggleBtnMobile?.addEventListener("click", toggleThemeWithIcons);
});

/**
 * Initialize the color theme based on user preferences or local storage.
 */
function initColorTheme() {
  if (
    localStorage.getItem("color-theme") === "dark" ||
    (!("color-theme" in localStorage) &&
      window.matchMedia("(prefers-color-scheme: dark)").matches)
  ) {
    document.documentElement.classList.add("dark");
  } else {
    document.documentElement.classList.remove("dark");
  }
}

// Initialize color theme immediately
initColorTheme();
