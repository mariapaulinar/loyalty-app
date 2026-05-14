@php
$pageTitle = $settings['overrideTitle'] 
    ? $settings['overrideTitle'] 
    : trans('common.edit_item_', ['item' => $settings['subject_column'] 
        ? parse_attr($form['data']->{$settings['subject_column']}) 
        : trans('common.item')]) . config('default.page_title_delimiter') . $settings['title'];
@endphp
@extends($settings['guard'].'.layouts.default')
@section('page_title', $pageTitle . config('default.page_title_delimiter') . config('default.app_name'))
@section('content')
    <div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8" @onclicktab="window.appSetImageUploadHeight()">
        
        {{-- Page Header (New Universal Component) --}}
        <x-ui.page-header
            :icon="$settings['icon']"
            :title="$settings['overrideTitle'] ?? $settings['title']"
            :description="!$settings['overrideTitle'] ? trans('common.edit_item_', ['item' => $settings['subject_column'] ? parse_attr($form['data']->{$settings['subject_column']}) : trans('common.item')]) : null"
        >
            <x-slot name="actions">
                @if($settings['list'])
                    <a href="{{ route($settings['guard'].'.data.list', ['name' => $dataDefinition->name]) }}{{ request()->has('batch_id') ? '?batch_id='.request('batch_id') : '' }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 
                              text-sm font-medium text-secondary-700 dark:text-secondary-300 
                              bg-white dark:bg-secondary-800 
                              border border-secondary-200 dark:border-secondary-700 
                              rounded-xl shadow-sm
                              hover:bg-secondary-50 dark:hover:bg-secondary-700 
                              hover:border-secondary-300 dark:hover:border-secondary-600
                              focus:outline-none focus:ring-2 focus:ring-primary-500/20
                              transition-colors duration-200">
                        <x-ui.icon icon="arrow-left" class="w-4 h-4" />
                        <span class="hidden sm:inline">{{ trans('common.back_to_list') }}</span>
                    </a>
                @endif
                @if ($settings['view'])
                    <a href="{{ route($settings['guard'].'.data.view', ['name' => $dataDefinition->name, 'id' => $form['data']->id]) }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 
                              text-sm font-medium text-secondary-600 dark:text-secondary-400 
                              bg-secondary-50 dark:bg-secondary-800 
                              border border-secondary-200 dark:border-secondary-700 
                              rounded-xl shadow-sm
                              hover:bg-secondary-100 dark:hover:bg-secondary-700 
                              hover:text-secondary-900 dark:hover:text-white
                              focus:outline-none focus:ring-2 focus:ring-primary-500/20
                              transition-colors duration-200">
                        <x-ui.icon class="w-4 h-4" icon="eye" />
                        <span class="hidden sm:inline">{{ trans('common.view') }}</span>
                    </a>
                @endif
                @if ($settings['delete'])
                    <button type="button" 
                            class="inline-flex items-center gap-2 px-4 py-2.5 
                                   text-sm font-medium text-red-600 dark:text-red-400 
                                   bg-red-50 dark:bg-red-500/10 
                                   border border-red-200 dark:border-red-500/30 
                                   rounded-xl shadow-sm
                                   hover:bg-red-100 dark:hover:bg-red-500/20 
                                   hover:border-red-300 dark:hover:border-red-500/50
                                   focus:outline-none focus:ring-2 focus:ring-red-500/20
                                   transition-all duration-200"
                            @click="deleteItem('{{ $form['data']->id }}', '{{ $settings['subject_column'] ? str_replace("'", "\'", parse_attr($form['data']->{$settings['subject_column']})) : null }}')">
                        <x-ui.icon class="w-4 h-4" icon="trash" />
                        <span class="hidden sm:inline">{{ trans('common.delete') }}</span>
                    </button>
                @endif
            </x-slot>
        </x-ui.page-header>

        @php $hasTabs = !empty($form['tabs']); @endphp
        
        {{-- Form Content (NO CARD WRAPPER!) --}}
        <div class="space-y-6">
            <x-forms.messages />
            <x-forms.form-open
                :novalidate="$hasTabs"
                action="{{ route($settings['guard'].'.data.edit.post', ['name' => $dataDefinition->name, 'id' => $form['data']->id]) }}"
                enctype="multipart/form-data" id="formDataDefinition" method="POST" class="space-y-6" />
            <input type="hidden" id="meta-data-guard" value="{{ $settings['guard'] }}" />
            <input type="hidden" id="meta-data-name" value="{{ $dataDefinition->name }}" />
            <input type="hidden" id="meta-data-view" value="edit" />
            
            @if ($form['columns'])
                @if($hasTabs)
                    {{-- Tabs at Page Level (NO card wrapper!) --}}
                    {{-- 
                        Tab types:
                        - 'standard': Renders form fields from DataDefinition
                        - 'custom': Renders a Blade view (for non-form content like account deletion, data export)
                        
                        When a custom tab is active, the CRUD's OTP verification and save buttons are hidden.
                        Custom tabs must implement their own verification mechanisms.
                    --}}
                    @php
                        // Group columns by their tab for easier rendering
                        $columnsByTab = collect($form['columns'])
                            ->filter(fn($col) => !$col['hidden'] && isset($col['tab']))
                            ->groupBy('tab')
                            ->toArray();
                        
                        // Check if any tabs are custom type (used to conditionally show/hide CRUD elements)
                        $hasCustomTabs = collect($form['tabs'])->contains(fn($tab) => ($tab['type'] ?? 'standard') === 'custom');
                        
                        // Get the first tab's type to know initial visibility state
                        $firstTabType = collect($form['tabs'])->first()['type'] ?? 'standard';
                    @endphp
                    <x-ui.tabs :tabs="array_values($form['tabs'])" active-tab="1">
                        @foreach ($form['tabs'] as $tabKey => $tabMeta)
                            <x-slot :name="$tabKey">
                                @if (($tabMeta['type'] ?? 'standard') === 'custom' && !empty($tabMeta['view']))
                                    {{-- Custom Tab: Render the specified Blade view --}}
                                    {{-- Pass common context variables to the custom view --}}
                                    @include($tabMeta['view'], [
                                        'form' => $form,
                                        'settings' => $settings,
                                        'dataDefinition' => $dataDefinition,
                                        'member' => $form['data'],
                                    ])
                                @else
                                    {{-- Standard Tab: Render form fields --}}
                                    <div class="space-y-6">
                                        @foreach ($columnsByTab[$tabKey] ?? [] as $column)
                                            @if($column['container_start::edit'] ?? $column['container_start::insert'])
                                                <div class="{{ $column['container_start::edit'] ?? $column['container_start::insert'] }}">
                                            @endif
                                            @if($column['classes::edit'] ?? $column['classes::insert'])
                                                <div class="{{ $column['classes::edit'] ?? $column['classes::insert'] }}">
                                            @endif
                                            @if(!empty($column['conditional']))
                                                <div class="conditional-field" 
                                                     data-condition-field="{{ $column['conditional']['field'] }}" 
                                                     data-condition-values="{{ implode(',', $column['conditional']['values']) }}"
                                                     @if(!in_array($form['data']->{$column['conditional']['field']} ?? '', $column['conditional']['values'])) style="display: none;" @endif>
                                            @endif
                                            @include('data.form', compact('form', 'column'))
                                            @if(!empty($column['conditional']))
                                                </div>
                                            @endif
                                            @if($column['classes::edit'] ?? $column['classes::insert'])
                                                </div>
                                            @endif
                                            @if($column['container_end::edit'] ?? $column['container_end::insert'])
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </x-slot>
                        @endforeach
                    </x-ui.tabs>
                @else
                    {{-- Non-tabbed form: Wrap in clean card --}}
                    <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 shadow-sm p-6">
                        <div class="space-y-6">
                            @foreach ($form['columns'] as $column)
                                @if (!$column['hidden'])
                                    @if($column['container_start::edit'] ?? $column['container_start::insert'])
                                        <div class="{{ $column['container_start::edit'] ?? $column['container_start::insert'] }}">
                                    @endif
                                    @if($column['classes::edit'] ?? $column['classes::insert'])
                                        <div class="{{ $column['classes::edit'] ?? $column['classes::insert'] }}">
                                    @endif
                                    @if(!empty($column['conditional']))
                                        <div class="conditional-field" 
                                             data-condition-field="{{ $column['conditional']['field'] }}" 
                                             data-condition-values="{{ implode(',', $column['conditional']['values']) }}"
                                             @if(!in_array($form['data']->{$column['conditional']['field']} ?? '', $column['conditional']['values'])) style="display: none;" @endif>
                                    @endif
                                    @include('data.form', compact('form', 'column'))
                                    @if(!empty($column['conditional']))
                                        </div>
                                    @endif
                                    @if($column['classes::edit'] ?? $column['classes::insert'])
                                        </div>
                                    @endif
                                    @if($column['container_end::edit'] ?? $column['container_end::insert'])
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

            {{-- 
                Password, OTP Verification, and Save Button sections.
                These are hidden when a custom tab is active because custom tabs 
                implement their own verification and action buttons.
            --}}
            @if ($hasTabs && $hasCustomTabs)
            <div x-data="{ activeTabType: '{{ $firstTabType }}' }" 
                 @onclicktab.window="activeTabType = $event.detail.type"
                 x-show="activeTabType === 'standard'"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="space-y-6">
            @endif

            {{-- Password Confirmation --}}
            @if ($settings['editRequiresPassword'])
                <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 shadow-sm p-6">
                    <div class="p-6 rounded-xl bg-secondary-50 dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700">
                        <x-forms.input 
                            value=""
                            class-label="mb-4"
                            type="password"
                            name="current_password_required_to_save_changes"
                            icon="lock"
                            :label="trans('common.current_password_to_save_changes')"
                            :placeholder="trans('common.current_password')"
                            :required="true"
                        />
                    </div>
                </div>
            @endif

            {{-- OTP Verification --}}
            @if ($settings['editRequiresOtp'])
                <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 shadow-sm overflow-hidden"
                             x-data="profileOtpVerification({
                                 sendUrl: '{{ route($settings['guard'].'.profile.otp.send') }}',
                                 verifyUrl: '{{ route($settings['guard'].'.profile.otp.verify') }}',
                                 email: '{{ auth($settings['guard'])->user()->email }}',
                                 csrfToken: '{{ csrf_token() }}'
                             })">
                            <div class="px-6 py-8 sm:px-10">
                                {{-- Error Message --}}
                                <div x-show="error && !verified" x-cloak 
                                     x-transition:enter="transition ease-out duration-200" 
                                     x-transition:enter-start="opacity-0" 
                                     x-transition:enter-end="opacity-100" 
                                     class="mb-6 flex justify-center">
                                    <div class="inline-flex items-center gap-2 px-4 py-3 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30">
                                        <x-ui.icon icon="alert-circle" class="w-5 h-5 text-red-500 dark:text-red-400 flex-shrink-0" />
                                        <span class="text-sm font-medium text-red-700 dark:text-red-300" x-text="error"></span>
                                    </div>
                                </div>

                                {{-- Step 1: Send Code --}}
                                <div x-show="!codeSent" x-cloak class="text-center">
                                    <x-ui.icon icon="shield-check" class="w-8 h-8 text-secondary-400 dark:text-secondary-500 mx-auto mb-4" />
                                    
                                    <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-2">
                                        {{ trans('otp.profile_verification_title') }}
                                    </h3>
                                    <p class="text-secondary-500 dark:text-secondary-400 text-sm mb-6 max-w-sm mx-auto">
                                        {{ trans('otp.profile_send_code_info_generic') }}
                                    </p>
                                    
                                    <button type="button"
                                            @click="sendCode()"
                                            :disabled="loading"
                                            class="inline-flex items-center justify-center gap-2 px-6 py-3 
                                                   text-sm font-medium text-white 
                                                   bg-primary-600 hover:bg-primary-500
                                                   rounded-xl shadow-sm hover:shadow-md
                                                   focus:outline-none focus:ring-2 focus:ring-primary-500/20
                                                   transition-all duration-200 active:scale-[0.98]
                                                   disabled:opacity-50 disabled:cursor-not-allowed">
                                        <template x-if="!loading">
                                            <x-ui.icon icon="mail" class="w-4 h-4" />
                                        </template>
                                        <template x-if="loading">
                                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </template>
                                        <span x-text="loading ? '{{ trans('otp.sending') }}' : '{{ trans('otp.profile_send_code') }}'"></span>
                                    </button>
                                </div>

                                {{-- Step 2: Enter Code --}}
                                <div x-show="codeSent && !verified" x-cloak class="text-center">
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 mb-4 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400">
                                        <x-ui.icon icon="check-circle" class="w-4 h-4" />
                                        <span class="text-sm font-medium">{{ trans('otp.code_sent_success_short') }}</span>
                                    </div>
                                    
                                    <p class="text-secondary-600 dark:text-secondary-300 text-sm font-medium mb-6" x-text="emailUsedForOtp">
                                    </p>
                                    
                                    <div class="flex justify-center mb-6" @pin-complete="verifyCode($event.detail.code)">
                                        <x-ui.pin-input 
                                            name="otp_code_input"
                                            :length="6"
                                            :auto-submit="true"
                                            data-no-form-submit
                                        />
                                    </div>

                                    <input type="hidden" name="otp_verification_token" x-model="verificationToken" />

                                    <p class="text-sm text-secondary-500 dark:text-secondary-400">
                                        {{ trans('otp.step3_didnt_receive') }}
                                        <button type="button"
                                                @click="sendCode()"
                                                :disabled="resendCooldown > 0 || loading"
                                                class="font-medium text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300 disabled:opacity-50 disabled:cursor-not-allowed ml-1">
                                            <span x-show="resendCooldown > 0" x-text="'{{ trans('otp.step3_resend_in') }}'.replace(':seconds', resendCooldown)"></span>
                                            <span x-show="resendCooldown === 0">{{ trans('otp.step3_resend') }}</span>
                                        </button>
                                    </p>
                                </div>

                                {{-- Step 3: Verified --}}
                                <div x-show="verified" x-cloak class="text-center">
                                    <x-ui.icon icon="check" class="w-8 h-8 text-emerald-600 dark:text-emerald-400 mx-auto mb-4" />
                                    
                                    <h3 class="text-lg font-semibold text-emerald-700 dark:text-emerald-400 mb-1">
                                        {{ trans('otp.identity_verified') }}
                                    </h3>
                                    <p class="text-secondary-500 dark:text-secondary-400 text-sm">
                                        {{ trans('otp.profile_verified') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <script>
                            document.addEventListener('alpine:init', () => {
                                Alpine.data('profileOtpVerification', (config) => ({
                                    sendUrl: config.sendUrl,
                                    verifyUrl: config.verifyUrl,
                                    initialEmail: config.email, // Email at page load (may be empty for anonymous)
                                    csrfToken: config.csrfToken,
                                    codeSent: false,
                                    loading: false,
                                    verified: false,
                                    error: null,
                                    verificationToken: '',
                                    resendCooldown: 0,
                                    cooldownInterval: null,
                                    emailUsedForOtp: '', // Track which email the OTP was sent to
                                    
                                    // Get the email to use for OTP - either existing email or from form input
                                    getEmailForOtp() {
                                        // If user already has an email, use that
                                        if (this.initialEmail && this.initialEmail.trim() !== '') {
                                            return this.initialEmail;
                                        }
                                        // Otherwise, get the email from the form input (new email being added)
                                        const emailInput = document.querySelector('input[name="email"]');
                                        return emailInput ? emailInput.value.trim() : '';
                                    },
                                    
                                    async sendCode() {
                                        const email = this.getEmailForOtp();
                                        
                                        // Validate email is present
                                        if (!email) {
                                            this.error = '{{ trans("validation.required", ["attribute" => trans("validation.attributes.email")]) }}';
                                            return;
                                        }
                                        
                                        // Basic email format validation
                                        if (!email.includes('@')) {
                                            this.error = '{{ trans("validation.email", ["attribute" => trans("validation.attributes.email")]) }}';
                                            return;
                                        }
                                        
                                        this.loading = true;
                                        this.error = null;
                                        this.emailUsedForOtp = email; // Remember which email we're verifying
                                        
                                        try {
                                            const response = await fetch(this.sendUrl, {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': this.csrfToken,
                                                    'Accept': 'application/json'
                                                },
                                                body: JSON.stringify({ email: email })
                                            });
                                            const data = await response.json();
                                            if (data.success) {
                                                this.codeSent = true;
                                                this.startResendCooldown(data.resend_cooldown || 60);
                                            } else {
                                                this.error = data.message || '{{ trans('otp.send_failed') }}';
                                            }
                                        } catch (e) {
                                            this.error = '{{ trans('otp.send_failed') }}';
                                        } finally {
                                            this.loading = false;
                                        }
                                    },
                                    async verifyCode(code) {
                                        this.loading = true;
                                        this.error = null;
                                        try {
                                            const response = await fetch(this.verifyUrl, {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': this.csrfToken,
                                                    'Accept': 'application/json'
                                                },
                                                body: JSON.stringify({ email: this.emailUsedForOtp, code: code })
                                            });
                                            const data = await response.json();
                                            if (data.success) {
                                                this.verified = true;
                                                this.verificationToken = data.token;
                                                this.stopResendCooldown();
                                                document.querySelector('.pin-component')?.dispatchEvent(new CustomEvent('pin-success'));
                                                window.dispatchEvent(new CustomEvent('otp-verified'));
                                            } else {
                                                this.error = data.message || '{{ trans('otp.code_invalid') }}';
                                                if (data.locked || data.expired) {
                                                    this.codeSent = false;
                                                    this.stopResendCooldown();
                                                } else {
                                                    document.querySelector('.pin-component')?.dispatchEvent(new CustomEvent('pin-reset'));
                                                }
                                            }
                                        } catch (e) {
                                            this.error = '{{ trans('otp.verification_failed') }}';
                                            document.querySelector('.pin-component')?.dispatchEvent(new CustomEvent('pin-reset'));
                                        } finally {
                                            this.loading = false;
                                        }
                                    },
                                    startResendCooldown(seconds) {
                                        this.resendCooldown = seconds;
                                        this.cooldownInterval = setInterval(() => {
                                            this.resendCooldown--;
                                            if (this.resendCooldown <= 0) {
                                                this.stopResendCooldown();
                                            }
                                        }, 1000);
                                    },
                                    stopResendCooldown() {
                                        if (this.cooldownInterval) {
                                            clearInterval(this.cooldownInterval);
                                            this.cooldownInterval = null;
                                        }
                                        this.resendCooldown = 0;
                                    }
                                }));
                            });
                        </script>
            @endif

            {{-- Form Actions --}}
            <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 shadow-sm p-6">
                <div class="flex flex-col items-center justify-center gap-3 sm:flex-row"
                     @if ($settings['editRequiresOtp']) x-data="{ otpVerified: false }" x-on:otp-verified.window="otpVerified = true" @endif>
                    @if ($settings['editRequiresOtp'])
                            <button type="submit" 
                                    :disabled="!otpVerified"
                                    :class="{ 
                                        'opacity-50 cursor-not-allowed': !otpVerified,
                                        'hover:bg-primary-500 active:scale-[0.98]': otpVerified 
                                    }"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3
                                           text-sm font-medium text-white 
                                           bg-primary-600 
                                           rounded-xl shadow-sm hover:shadow-md
                                           focus:outline-none focus:ring-2 focus:ring-primary-500/20
                                           transition-all duration-200">
                                <span x-show="!otpVerified" class="inline-flex items-center gap-2">
                                    <x-ui.icon icon="lock" class="w-4 h-4" />
                                    {{ trans('otp.verify_to_save') }}
                                </span>
                                <span x-show="otpVerified" x-cloak>
                                    {{ trans('common.save_changes') }}<span class="form-dirty hidden">&nbsp;•</span>
                                </span>
                            </button>
                        @else
                            <button type="submit" 
                                    class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3
                                           text-sm font-medium text-white 
                                           bg-primary-600 hover:bg-primary-500
                                           rounded-xl shadow-sm hover:shadow-md
                                           focus:outline-none focus:ring-2 focus:ring-primary-500/20
                                           transition-all duration-200 active:scale-[0.98]">
                                {{ trans('common.save_changes') }}<span class="form-dirty hidden">&nbsp;•</span>
                            </button>
                        @endif
                        @if($settings['list'])
                            <a href="{{ route($settings['guard'].'.data.list', ['name' => $dataDefinition->name]) }}{{ request()->has('batch_id') ? '?batch_id='.request('batch_id') : '' }}"
                               class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3
                                      text-sm font-medium text-secondary-700 dark:text-secondary-300
                                      bg-white dark:bg-secondary-800
                                      border border-secondary-200 dark:border-secondary-700
                                      rounded-xl shadow-sm
                                      hover:bg-secondary-50 dark:hover:bg-secondary-700
                                      hover:border-secondary-300 dark:hover:border-secondary-600
                                      focus:outline-none focus:ring-2 focus:ring-primary-500/20
                                      transition-colors duration-200">
                                {{ trans('common.cancel') }}
                            </a>
                @endif
            </div>
        </div>
        @endif

        {{-- Close the custom tab conditional wrapper --}}
        @if ($hasTabs && $hasCustomTabs)
        </div>
        @endif
        
        <x-forms.form-close />

        {{-- Custom JavaScript from DataDefinition settings --}}
        @php
            $customJs = null;
            if (!empty($settings['js'])) {
                if (is_string($settings['js'])) {
                    // String = applies to all views
                    $customJs = $settings['js'];
                } elseif (is_array($settings['js'])) {
                    // Check for exact match or comma-separated keys containing 'edit'
                    foreach ($settings['js'] as $views => $code) {
                        $viewList = array_map('trim', explode(',', $views));
                        if (in_array('edit', $viewList) || in_array('all', $viewList)) {
                            $customJs = $code;
                            break;
                        }
                    }
                }
            }
        @endphp
        @if ($customJs)
            <script>
                document.addEventListener("DOMContentLoaded", () => {
                    {!! $customJs !!}
                });
            </script>
        @endif

        @if (session('current_tab_index') && $hasTabs)
            <script>
                document.addEventListener("DOMContentLoaded", () => {
                    window.openTab({{ session('current_tab_index') }});
                });
            </script>
        @endif
        @if ($errors->any())
            <script>
                document.addEventListener("DOMContentLoaded", () => {
                    window.openTabWithInvalidElement();
                });
            </script>
        @endif
    </div>

    @if ($settings['delete'])
        <script>
            function deleteItem(id, item) {
                if (item == null) item = "{{ trans('common.this_item') }}";
                appConfirm('{{ trans('common.confirm_deletion') }}', _lang.delete_confirmation_text.replace(":item",
                    '<strong>' + item + '</strong>'), {
                    'btnConfirm': {
                        'click': function() {
                            const form = document.getElementById('formDataDefinition');
                            form.action =
                                '{{ route($settings['guard'].'.data.delete.post', ['name' => $dataDefinition->name]) }}/' + id;
                            form.submit();
                        }
                    }
                });
            }
        </script>
    @endif
@stop