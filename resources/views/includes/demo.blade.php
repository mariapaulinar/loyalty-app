@if(config('default.app_demo'))
    <div id="demo-banner"
        class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-50 w-full max-w-2xl px-4 pointer-events-none">
        <div
            class="pointer-events-auto relative overflow-hidden bg-gradient-to-r from-primary-600 via-primary-500 to-primary-400 p-[1px] rounded-2xl shadow-2xl">
            <div class="relative bg-white dark:bg-secondary-900 rounded-2xl p-4 backdrop-blur-xl">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4 flex-1">
                        <div class="relative flex-shrink-0">
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-primary-500 to-primary-500 rounded-xl blur-lg opacity-50 animate-pulse">
                            </div>
                            <div
                                class="relative w-12 h-12 rounded-xl bg-gradient-to-br from-primary-600 to-primary-400 flex items-center justify-center shadow-lg">
                                <x-ui.icon icon="flask-conical" class="w-6 h-6 text-white" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="text-sm font-bold text-secondary-900 dark:text-white">Demo Mode Active</h4>
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gradient-to-r from-primary-500 to-primary-500 text-white">
                                    <span class="w-1.5 h-1.5 bg-white rounded-full mr-1.5 animate-pulse"></span>
                                    Live
                                </span>
                            </div>
                            <p class="text-xs text-secondary-600 dark:text-secondary-400 leading-relaxed">
                                {{ trans('common.demo_mode') }}
                            </p>
                        </div>
                    </div>
                    <button
                        onclick="setCookie('demo', 'true', 7); document.getElementById('demo-banner').classList.add('hidden');"
                        type="button"
                        class="flex-shrink-0 p-2 text-secondary-400 hover:text-secondary-600 dark:text-secondary-500 dark:hover:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800 rounded-lg transition-all duration-200">
                        <x-ui.icon icon="x" class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Check if the 'demo' cookie is not set
            if (!window.checkCookie('demo')) {
                // If the cookie is not set, remove the 'hidden' class from the 'demo-banner'
                document.getElementById('demo-banner').classList.remove('hidden');
            }
        });
    </script>
@endif