{{--
  Theme Toggle — Fixed Position

  Discrete dark/light mode switch for auth pages.
  Positioned fixed in the bottom-right corner.
  Uses sun/moon icons matching the homepage pattern.

  The toggleTheme() function is provided globally by core.js.
--}}

<div class="fixed bottom-6 right-6 z-50">
    <button
        type="button"
        onclick="toggleTheme()"
        class="p-2.5 rounded-full bg-white/80 dark:bg-secondary-800/80 backdrop-blur-sm
            border border-secondary-200 dark:border-secondary-700
            text-secondary-500 dark:text-secondary-400
            hover:text-secondary-700 dark:hover:text-secondary-200
            hover:bg-white dark:hover:bg-secondary-800
            shadow-sm transition-colors duration-200"
        aria-label="{{ trans('common.theme') }}"
    >
        <x-ui.icon icon="sun" class="hidden w-4 h-4 dark:block" />
        <x-ui.icon icon="moon" class="w-4 h-4 dark:hidden" />
    </button>
</div>
