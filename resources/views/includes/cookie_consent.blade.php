@if(config('default.cookie_consent') && (request()->cookie('cookie_consent') == -1 || is_null(request()->cookie('cookie_consent'))))
    <div id="cookie-consent-banner" tabindex="-1" aria-hidden="false"
        class="fixed inset-0 z-50 flex items-end justify-center pointer-events-none">
        <div
            class="w-full bg-white/90 dark:bg-secondary-900/90 backdrop-blur-xl border-t border-secondary-200 dark:border-secondary-800 shadow-2xl pointer-events-auto transition-all duration-300 transform translate-y-0">
            <div class="max-w-7xl mx-auto p-4 md:p-6 lg:flex lg:items-center lg:justify-between gap-6">
                <div class="flex-1 mb-4 lg:mb-0">
                    <h3 class="text-base font-semibold text-secondary-900 dark:text-white mb-1">
                        {{ trans('common.cookie_consent') }}</h3>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">
                        {{ trans('common.cookie_consent_message') }}
                        <a href="{{ route('member.privacy') }}"
                            class="text-primary-600 dark:text-primary-400 hover:underline ml-1">{{ trans('common.privacy_policy') }}</a>
                    </p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <button id="block-cookies" type="button"
                        class="block-cookies px-5 py-2.5 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-xl hover:bg-secondary-50 focus:ring-4 focus:ring-secondary-100 dark:bg-secondary-800 dark:text-secondary-300 dark:border-secondary-600 dark:hover:bg-secondary-700 dark:focus:ring-secondary-700 transition-all">
                        {{ trans('common.decline') }}
                    </button>
                    <button id="accept-cookies" type="button"
                        class="accept-cookies px-5 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-xl hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all">
                        {{ trans('common.accept') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Function to make an AJAX call to set the cookie in Laravel
            function setConsentCookie(value) {
                const url = `{{ route('set.consent.cookie.post', ['value' => '']) }}/${value}`;
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('cookie-consent-banner').style.display = 'none';
                        } else {
                            console.error('Error setting the cookie.');
                        }
                    })
                    .catch(error => {
                        console.error('There was an error with the AJAX request:', error);
                    });
            }

            // Event listeners for the "Accept" button
            document.querySelectorAll('.accept-cookies').forEach(function (button) {
                button.addEventListener('click', function () {
                    setConsentCookie(1); // 1 means cookies are allowed
                });
            });

            // Event listeners for the "Decline" button
            document.querySelectorAll('.block-cookies').forEach(function (button) {
                button.addEventListener('click', function () {
                    setConsentCookie(0); // 0 means cookies are not allowed
                });
            });
        });
    </script>
@endif