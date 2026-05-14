{{--
Premium Form Messages Component

Modern alert messages for form validation and notifications.
--}}

<div {{ $attributes }}>
    @if (Session::has('error'))
        <div id="messages-alert-component"
             class="relative flex items-center gap-3 p-3.5 mb-6 rounded-xl bg-red-50/80 dark:bg-red-500/10 border border-red-200/50 dark:border-red-500/20 backdrop-blur-sm"
             role="alert">
            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center shadow-md shadow-red-500/20">
                <x-ui.icon icon="alert-circle" class="w-4 h-4 text-white" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-red-800 dark:text-red-200">{!! Session::get('error') !!}</p>
            </div>
            <button type="button"
                    class="flex-shrink-0 w-7 h-7 rounded-lg flex items-center justify-center text-red-500 hover:text-red-700 hover:bg-red-100 dark:text-red-400 dark:hover:text-red-200 dark:hover:bg-red-500/20 transition-colors"
                    data-dismiss-target="#messages-alert-component" aria-label="Close">
                <span class="sr-only">{{ trans('common.close') }}</span>
                <x-ui.icon icon="x" class="w-4 h-4" />
            </button>
        </div>
    @elseif ($errors->any())
        <div id="messages-alert-component"
             class="relative flex items-center gap-3 p-3.5 mb-6 rounded-xl bg-red-50/80 dark:bg-red-500/10 border border-red-200/50 dark:border-red-500/20 backdrop-blur-sm"
             role="alert">
            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center shadow-md shadow-red-500/20">
                <x-ui.icon icon="alert-circle" class="w-4 h-4 text-white" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-red-800 dark:text-red-200">{!! $msg !!}</p>
            </div>
            <button type="button"
                    class="flex-shrink-0 w-7 h-7 rounded-lg flex items-center justify-center text-red-500 hover:text-red-700 hover:bg-red-100 dark:text-red-400 dark:hover:text-red-200 dark:hover:bg-red-500/20 transition-colors"
                    data-dismiss-target="#messages-alert-component" aria-label="Close">
                <span class="sr-only">{{ trans('common.close') }}</span>
                <x-ui.icon icon="x" class="w-4 h-4" />
            </button>
        </div>
    @endif

    @if (Session::has('success'))
        <div id="alert-success"
             class="relative flex items-center gap-3 p-3.5 mb-6 rounded-xl bg-emerald-50/80 dark:bg-emerald-500/10 border border-emerald-200/50 dark:border-emerald-500/20 backdrop-blur-sm"
             role="alert">
            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-md shadow-emerald-500/20">
                <x-ui.icon icon="check" class="w-4 h-4 text-white" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200">{!! Session::get('success') !!}</p>
            </div>
            <button type="button"
                    class="flex-shrink-0 w-7 h-7 rounded-lg flex items-center justify-center text-emerald-500 hover:text-emerald-700 hover:bg-emerald-100 dark:text-emerald-400 dark:hover:text-emerald-200 dark:hover:bg-emerald-500/20 transition-colors"
                    data-dismiss-target="#alert-success" aria-label="Close">
                <span class="sr-only">{{ trans('common.close') }}</span>
                <x-ui.icon icon="x" class="w-4 h-4" />
            </button>
        </div>
    @endif

    @if (Session::has('info'))
        <div id="alert-info"
             class="relative flex items-center gap-3 p-3.5 mb-6 rounded-xl bg-sky-50/80 dark:bg-sky-500/10 border border-sky-200/50 dark:border-sky-500/20 backdrop-blur-sm"
             role="alert">
            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center shadow-md shadow-sky-500/20">
                <x-ui.icon icon="info" class="w-4 h-4 text-white" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-sky-800 dark:text-sky-200">{!! Session::get('info') !!}</p>
            </div>
            <button type="button"
                    class="flex-shrink-0 w-7 h-7 rounded-lg flex items-center justify-center text-sky-500 hover:text-sky-700 hover:bg-sky-100 dark:text-sky-400 dark:hover:text-sky-200 dark:hover:bg-sky-500/20 transition-colors"
                    data-dismiss-target="#alert-info" aria-label="Close">
                <span class="sr-only">{{ trans('common.close') }}</span>
                <x-ui.icon icon="x" class="w-4 h-4" />
            </button>
        </div>
    @endif

    @if (Session::has('warning'))
        <div id="alert-warning"
             class="relative flex items-center gap-3 p-3.5 mb-6 rounded-xl bg-amber-50/80 dark:bg-amber-500/10 border border-amber-200/50 dark:border-amber-500/20 backdrop-blur-sm"
             role="alert">
            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-md shadow-amber-500/20">
                <x-ui.icon icon="alert-triangle" class="w-4 h-4 text-white" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-amber-800 dark:text-amber-200">{!! Session::get('warning') !!}</p>
            </div>
            <button type="button"
                    class="flex-shrink-0 w-7 h-7 rounded-lg flex items-center justify-center text-amber-500 hover:text-amber-700 hover:bg-amber-100 dark:text-amber-400 dark:hover:text-amber-200 dark:hover:bg-amber-500/20 transition-colors"
                    data-dismiss-target="#alert-warning" aria-label="Close">
                <span class="sr-only">{{ trans('common.close') }}</span>
                <x-ui.icon icon="x" class="w-4 h-4" />
            </button>
        </div>
    @endif
</div>
