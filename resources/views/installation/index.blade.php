@extends('installation.layouts.default')

@section('page_title', trans('install.install') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')

    <form id="form1" method="POST" class="h-full flex flex-col" x-data="{
        db: 'mysql',
        mail: 'smtp',
        seedDemo: false,
        testingDb: false,
        testingEmail: false,
        dbTestResult: null,
        emailTestResult: null,
        emailTestMessage: '',
        passwordStrength: 0,
        passLength: 0,
        confirmLength: 0,
        passwordMatch: true,
        emailValid: true,
        showGmailHelp: false,
        adminEmail: '',
        adminName: ''
    }">
        {{-- Hidden input for APP_DEMO - first element in form --}}
        <input type="hidden" name="APP_DEMO" id="APP_DEMO_INPUT" value="false">
        
        <!-- Step 1: Requirements -->
        <div x-show="tab === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            @php
                $phpRequirements = $requirements['requirements'];
                $envChecks = $requirements['environment'] ?? [];
                $allChecks = array_merge($phpRequirements, $envChecks);
                $metCount = collect($allChecks)->filter()->count();
                $totalCount = count($allChecks);
                $failedCount = $totalCount - $metCount;
                $allMet = $requirements['allMet'];
            @endphp

            {{-- Header --}}
            <div class="mb-10">
                <h1 class="text-2xl font-bold text-secondary-900 dark:text-white tracking-tight">Requirements</h1>
                <p class="text-secondary-500 dark:text-secondary-400 text-base mt-2">Checking your server compatibility</p>
            </div>

            {{-- Status Card --}}
            @if ($allMet)
                <div class="bg-emerald-50/50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-800 rounded-2xl p-6 mb-8 flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0">
                        <x-ui.icon icon="check" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div class="pt-1">
                        <h2 class="text-lg font-bold text-secondary-900 dark:text-white mb-1">System Ready</h2>
                        <p class="text-secondary-600 dark:text-secondary-400 text-sm leading-relaxed">
                            Your server meets all {{ $totalCount }} requirements. You're ready to proceed with installation.
                        </p>
                    </div>
                </div>
            @else
                <div class="bg-red-50/50 dark:bg-red-900/10 border border-red-100 dark:border-red-800 rounded-2xl p-6 mb-8">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                            <x-ui.icon icon="alert-triangle" class="w-6 h-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div class="pt-1">
                            <h2 class="text-lg font-bold text-secondary-900 dark:text-white mb-1">{{ $failedCount }} Issue{{ $failedCount > 1 ? 's' : '' }} Found</h2>
                            <p class="text-secondary-600 dark:text-secondary-400 text-sm leading-relaxed">
                                {{ $metCount }} of {{ $totalCount }} checks passed. Please resolve the following before continuing.
                            </p>
                        </div>
                    </div>
                    {{-- Failed checks list --}}
                    <div class="mt-5 pt-5 border-t border-red-100 dark:border-red-800/50 space-y-2">
                        @foreach ($allChecks as $check => $met)
                            @if (!$met)
                                <div class="flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                    <x-ui.icon icon="x" class="w-4 h-4 flex-shrink-0" />
                                    <span>{{ $check }}</span>
                                </div>
                            @endif
                        @endforeach

                        {{-- Contextual help for subdirectory issue --}}
                        @if (isset($envChecks['Installed at domain root (not in a subdirectory)']) && !$envChecks['Installed at domain root (not in a subdirectory)'])
                            <div class="mt-3 p-3 rounded-lg bg-red-100/50 dark:bg-red-900/20 text-xs text-red-700 dark:text-red-300 leading-relaxed">
                                <strong>Subdirectory installs are not supported.</strong> The script must be served from a domain root
                                (e.g., <code class="px-1 bg-red-200/50 dark:bg-red-800/30 rounded">loyalty.example.com</code> or
                                <code class="px-1 bg-red-200/50 dark:bg-red-800/30 rounded">example.com</code>), not from a path like
                                <code class="px-1 bg-red-200/50 dark:bg-red-800/30 rounded">example.com/loyalty</code>.
                            </div>
                        @endif

                        {{-- Contextual help for writable issue --}}
                        @if (isset($envChecks['Root directory is writable (.env)']) && !$envChecks['Root directory is writable (.env)'])
                            <div class="mt-3 p-3 rounded-lg bg-red-100/50 dark:bg-red-900/20 text-xs text-red-700 dark:text-red-300 leading-relaxed">
                                <strong>The root directory must be writable</strong> so the installer can create the
                                <code class="px-1 bg-red-200/50 dark:bg-red-800/30 rounded">.env</code> configuration file.
                                Run <code class="px-1 bg-red-200/50 dark:bg-red-800/30 rounded">chmod 755</code> on the project root,
                                or ensure the web server user has write permissions.
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Technical Details Accordion --}}
            <div class="mb-8">
                <details class="group" @if(!$allMet) open @endif>
                    <summary class="inline-flex items-center gap-2 text-sm font-medium text-secondary-500 hover:text-secondary-800 dark:hover:text-secondary-300 cursor-pointer transition-colors select-none">
                        <x-ui.icon icon="chevron-right" class="w-4 h-4 transition-transform duration-200 group-open:rotate-90" />
                        <span>View technical details</span>
                    </summary>
                    <div class="mt-4 rounded-xl border border-secondary-100 dark:border-secondary-800 bg-white dark:bg-secondary-900/50 overflow-hidden">
                        {{-- PHP Extensions --}}
                        <div class="p-4">
                            <h4 class="text-[10px] font-bold text-secondary-400 uppercase tracking-widest mb-3">PHP Extensions</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1.5">
                                @foreach ($phpRequirements as $requirement => $met)
                                    <div class="flex items-center gap-2 text-xs py-0.5">
                                        <x-ui.icon icon="{{ $met ? 'check' : 'x' }}" class="w-3.5 h-3.5 flex-shrink-0 {{ $met ? 'text-emerald-500' : 'text-red-500' }}" />
                                        <span class="font-mono truncate {{ $met ? 'text-secondary-600 dark:text-secondary-400' : 'text-red-600 dark:text-red-400 font-semibold' }}">{{ $requirement }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Environment Checks --}}
                        @if (count($envChecks) > 0)
                            <div class="p-4 border-t border-secondary-100 dark:border-secondary-800">
                                <h4 class="text-[10px] font-bold text-secondary-400 uppercase tracking-widest mb-3">Environment</h4>
                                <div class="space-y-1.5">
                                    @foreach ($envChecks as $check => $met)
                                        <div class="flex items-center gap-2 text-xs py-0.5">
                                            <x-ui.icon icon="{{ $met ? 'check' : 'x' }}" class="w-3.5 h-3.5 flex-shrink-0 {{ $met ? 'text-emerald-500' : 'text-red-500' }}" />
                                            <span class="font-mono truncate {{ $met ? 'text-secondary-600 dark:text-secondary-400' : 'text-red-600 dark:text-red-400 font-semibold' }}">{{ $check }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </details>
            </div>

            {{-- The Footer (Matches Step 2) --}}
            <div class="flex justify-end mt-12 pt-6 border-t border-secondary-200 dark:border-secondary-800">
                <button type="button"
                    @if (!$allMet) disabled @else @click="tab = 2; window.scrollTo(0,0)" @endif
                    class="bg-primary-600 hover:bg-primary-700 text-white font-medium px-8 py-3 rounded-xl shadow-lg shadow-primary-500/20 hover:shadow-primary-500/30 hover:-translate-y-0.5 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-primary-500/20">
                    Configure Instance <x-ui.icon icon="arrow-right" class="w-4 h-4" />
                </button>
            </div>
        </div>

        <div x-show="tab === 2" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            
            {{-- Header --}}
            <div class="mb-10">
                <h1 class="text-2xl font-bold text-secondary-900 dark:text-white tracking-tight">Configure your instance</h1>
                <p class="text-secondary-500 dark:text-secondary-400 text-base mt-2">Set up your database connection and mail drivers.</p>
            </div>

            <div class="space-y-12">
                
                {{-- SECTION 1: GENERAL & ADMIN (The Basics) --}}
                <div class="grid gap-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-semibold text-secondary-700 dark:text-secondary-300 mb-2">Platform Name</label>
                            <input type="text" name="APP_NAME" class="input-text w-full" placeholder="e.g. Starbucks Rewards" value="{{ old('APP_NAME', env('APP_NAME', config('default.app_name'))) }}" required
                                @input="document.body._x_dataStack && (document.body._x_dataStack[0].appName = $el.value)">
                        </div>
                        <div>
                            <x-forms.select 
                                name="ADMIN_TIMEZONE" 
                                label="Time Zone"
                                :options="$timezones" 
                                value="UTC"
                                icon="clock"
                                :searchable="true"
                                searchPlaceholder="Search timezone..."
                            />
                        </div>
                        <script>
                            // Auto-detect browser timezone after Alpine initializes
                            document.addEventListener('DOMContentLoaded', () => {
                                setTimeout(() => {
                                    const browserTz = Intl.DateTimeFormat().resolvedOptions().timeZone;
                                    const validTimezones = @json(array_keys($timezones));
                                    
                                    if (validTimezones.includes(browserTz)) {
                                        const input = document.querySelector('input[name="ADMIN_TIMEZONE"]');
                                        if (input) {
                                            input.value = browserTz;
                                            // Update Alpine component state
                                            const wrapper = input.closest('[x-data]');
                                            if (wrapper && wrapper._x_dataStack) {
                                                wrapper._x_dataStack[0].selected = browserTz;
                                            }
                                        }
                                    }
                                }, 200);
                            });
                        </script>
                    </div>

                    {{-- Admin Credentials Card --}}
                    <div class="p-6 rounded-2xl bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-700 shadow-sm">
                        <h3 class="text-sm font-bold text-secondary-900 dark:text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                            <x-ui.icon icon="shield" class="w-4 h-4 text-primary-500" />
                            Admin Account
                        </h3>
                        
                        {{-- Row 1: Name | Email --}}
                        <div class="grid gap-6 md:grid-cols-2 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1.5">Name</label>
                                <input type="text" name="ADMIN_NAME" class="input-text w-full" placeholder="Admin" value="{{ old('ADMIN_NAME', 'Admin') }}" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1.5">Email Address</label>
                                <input type="email" name="ADMIN_MAIL" class="input-text w-full" placeholder="admin@example.com" value="{{ old('ADMIN_MAIL', env('MAIL_FROM_ADDRESS')) }}" required>
                            </div>
                        </div>

                        {{-- Row 2: Password | Confirm Password --}}
                        <div class="grid gap-6 md:grid-cols-2" x-data="{ showPass: false, showConfirm: false }">
                            <div>
                                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1.5">Password</label>
                                <div class="flex items-center gap-2">
                                    <div class="relative flex-1">
                                        <input :type="showPass ? 'text' : 'password'" id="ADMIN_PASS" name="ADMIN_PASS" class="input-text w-full pr-10" minlength="8" 
                                            @input="
                                                const pass = $el.value;
                                                const confirm = document.getElementById('ADMIN_PASS_CONFIRM');
                                                passLength = pass.length;
                                                confirmLength = confirm?.value.length || 0;
                                                let strength = 0;
                                                if (pass.length >= 8) strength++;
                                                if (pass.length >= 12) strength++;
                                                if (/[a-z]/.test(pass) && /[A-Z]/.test(pass)) strength++;
                                                if (/[0-9]/.test(pass)) strength++;
                                                if (/[^a-zA-Z0-9]/.test(pass)) strength++;
                                                passwordStrength = strength;
                                                passwordMatch = confirm ? pass === confirm.value : true;
                                            "
                                            :class="{ '!border-red-500 focus:!ring-red-500': passLength > 0 && passLength < 8 }"
                                            required>
                                        <button type="button" @click="showPass = !showPass" tabindex="-1" class="absolute inset-y-0 right-0 pr-3 flex items-center text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-300 focus:outline-none">
                                            <x-ui.icon x-show="!showPass" icon="eye" class="w-4 h-4" />
                                            <x-ui.icon x-show="showPass" x-cloak icon="eye-off" class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <button type="button" tabindex="-1" class="p-2.5 rounded-lg bg-secondary-100 dark:bg-secondary-700 text-secondary-600 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-600 transition-colors"
                                            @click="
                                                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
                                                let pass = '';
                                                for (let i = 0; i < 16; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
                                                document.getElementById('ADMIN_PASS').value = pass;
                                                document.getElementById('ADMIN_PASS').dispatchEvent(new Event('input'));
                                                showPass = true;
                                            " title="Generate Secure Password">
                                        <x-ui.icon icon="refresh-cw" class="w-4 h-4" />
                                    </button>
                                </div>
                                {{-- Strength Bars --}}
                                <div class="mt-2 flex gap-1 h-1">
                                    <div class="flex-1 rounded-full transition-all duration-500" :class="passwordStrength >= 1 ? 'bg-red-500' : 'bg-secondary-100 dark:bg-secondary-700'"></div>
                                    <div class="flex-1 rounded-full transition-all duration-500" :class="passwordStrength >= 2 ? 'bg-orange-500' : 'bg-secondary-100 dark:bg-secondary-700'"></div>
                                    <div class="flex-1 rounded-full transition-all duration-500" :class="passwordStrength >= 3 ? 'bg-yellow-500' : 'bg-secondary-100 dark:bg-secondary-700'"></div>
                                    <div class="flex-1 rounded-full transition-all duration-500" :class="passwordStrength >= 4 ? 'bg-emerald-500' : 'bg-secondary-100 dark:bg-secondary-700'"></div>
                                </div>
                                {{-- Min length error --}}
                                <p x-show="passLength > 0 && passLength < 8" x-cloak class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                    <x-ui.icon icon="alert-circle" class="w-3 h-3" /> Minimum 8 characters required
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1.5">Confirm Password</label>
                                <div class="relative">
                                    <input :type="showConfirm ? 'text' : 'password'" id="ADMIN_PASS_CONFIRM" name="ADMIN_PASS_CONFIRM" class="input-text w-full pr-10" minlength="8" 
                                        @input="confirmLength = $el.value.length; passwordMatch = $el.value === document.getElementById('ADMIN_PASS').value"
                                        @blur="confirmLength = $el.value.length; passwordMatch = $el.value === document.getElementById('ADMIN_PASS').value"
                                        :class="{ '!border-red-500 focus:!ring-red-500': !passwordMatch && confirmLength > 0, '!border-emerald-500 focus:!ring-emerald-500': passwordMatch && confirmLength > 0 }"
                                        required>
                                    <button type="button" @click="showConfirm = !showConfirm" tabindex="-1" class="absolute inset-y-0 right-0 pr-3 flex items-center text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-300 focus:outline-none">
                                        <x-ui.icon x-show="!showConfirm" icon="eye" class="w-4 h-4" />
                                        <x-ui.icon x-show="showConfirm" x-cloak icon="eye-off" class="w-4 h-4" />
                                    </button>
                                </div>
                                <p x-show="!passwordMatch && confirmLength > 0" x-cloak class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                    <x-ui.icon icon="x" class="w-3 h-3" /> Passwords do not match
                                </p>
                                <p x-show="passwordMatch && confirmLength > 0" x-cloak class="mt-1.5 text-xs text-emerald-600 flex items-center gap-1">
                                    <x-ui.icon icon="check" class="w-3 h-3" /> Passwords match
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECTION 2: DATABASE --}}
                <div class="border-t border-secondary-200 dark:border-secondary-700 pt-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-bold text-secondary-900 dark:text-white">Database Connection</h2>
                            <p class="text-sm text-secondary-500">Configure where your data will be stored.</p>
                        </div>
                        {{-- Connection Type Selector --}}
                        <div class="w-48">
                            <div class="relative">
                                <select name="DB_CONNECTION" class="input-select w-full pl-9 appearance-none text-sm py-2" x-model="db">
                                    <option value="mysql">MySQL / MariaDB</option>
                                    <option value="sqlite">SQLite</option>
                                </select>
                                <x-ui.icon icon="database" class="absolute left-3 top-2.5 w-4 h-4 text-secondary-400" />
                                <x-ui.icon icon="chevron-down" class="absolute right-3 top-3 w-3 h-3 text-secondary-400 pointer-events-none" />
                            </div>
                        </div>
                    </div>

                    {{-- SQLite Info --}}
                    <div x-show="db === 'sqlite'" x-transition class="p-4 rounded-xl bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800 flex gap-3">
                        <x-ui.icon icon="info" class="w-5 h-5 text-blue-600 flex-shrink-0" />
                        <p class="text-sm text-blue-800 dark:text-blue-200">SQLite is a file-based database — no additional setup required.</p>
                    </div>

                    {{-- MySQL Fields --}}
                    <div x-show="db === 'mysql'" x-transition class="grid gap-6">
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-8 md:col-span-9">
                                <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">Host</label>
                                <input type="text" name="DB_HOST" class="input-text w-full font-mono text-sm" value="127.0.0.1" :required="db === 'mysql'">
                            </div>
                            <div class="col-span-4 md:col-span-3">
                                <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">Port</label>
                                <input type="text" name="DB_PORT" class="input-text w-full font-mono text-sm" value="3306" :required="db === 'mysql'">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">Database Name</label>
                            <input type="text" name="DB_DATABASE" class="input-text w-full" placeholder="laravel" :required="db === 'mysql'">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">Username</label>
                                <input type="text" name="DB_USERNAME" class="input-text w-full" placeholder="root" :required="db === 'mysql'">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">Password</label>
                                <input type="password" name="DB_PASSWORD" class="input-text w-full" placeholder="••••••">
                            </div>
                        </div>

                        {{-- Connection Test --}}
                        <div class="flex items-center gap-4 mt-2">
                            <button type="button" @click="testDatabaseConnection" :disabled="testingDb"
                                class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 flex items-center gap-2 px-4 py-2 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                                <x-ui.icon icon="zap" class="w-4 h-4" x-show="!testingDb" />
                                <svg x-show="testingDb" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="testingDb ? 'Testing...' : 'Test Connection'"></span>
                            </button>
                            
                            <div x-show="dbTestResult" x-transition class="text-sm flex items-center gap-2" 
                                :class="dbTestResult === 'success' ? 'text-emerald-600' : 'text-red-600'">
                                <x-ui.icon x-bind:icon="dbTestResult === 'success' ? 'check' : 'alert-circle'" class="w-4 h-4" />
                                <span x-text="dbTestResult === 'success' ? 'Connected successfully' : 'Connection failed'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECTION 3: EMAIL (The Overhaul) --}}
                <div class="border-t border-secondary-200 dark:border-secondary-700 pt-8">
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-secondary-900 dark:text-white">Email Configuration</h2>
                        <p class="text-sm text-secondary-500">How should the system send emails?</p>
                    </div>

                    {{-- FROM ADDRESS GROUP --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                         <div>
                            <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">From Name</label>
                            <input type="text" name="MAIL_FROM_NAME" class="input-text w-full" value="{{ old('MAIL_FROM_NAME', config('default.app_name')) }}" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">From Email</label>
                            <input type="email" name="MAIL_FROM_ADDRESS" class="input-text w-full" placeholder="noreply@yourdomain.com" value="{{ old('MAIL_FROM_ADDRESS', 'noreply@example.com') }}" required>
                        </div>
                    </div>

                    {{-- DRIVER GRID --}}
                    <label class="block text-xs font-semibold uppercase text-secondary-500 mb-3">Select Driver</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
                        {{-- SMTP --}}
                        <label class="cursor-pointer group">
                            <input type="radio" name="MAIL_MAILER" value="smtp" x-model="mail" class="peer sr-only">
                            <div class="flex flex-col items-center justify-center p-4 h-28 rounded-xl border-2 border-secondary-100 dark:border-secondary-700 bg-white dark:bg-secondary-800 transition-all duration-200 peer-checked:border-primary-500 peer-checked:bg-primary-50/30 dark:peer-checked:bg-primary-500/10 peer-checked:ring-0 group-hover:border-secondary-300 dark:group-hover:border-secondary-600">
                                <div class="w-10 h-10 rounded-full bg-secondary-100 dark:bg-secondary-700 flex items-center justify-center text-secondary-500 dark:text-secondary-400 mb-2">
                                    <x-ui.icon icon="server" class="w-5 h-5" />
                                </div>
                                <span class="font-semibold text-sm text-secondary-900 dark:text-white">SMTP</span>
                            </div>
                        </label>

                        {{-- Mailgun --}}
                        <label class="cursor-pointer group">
                            <input type="radio" name="MAIL_MAILER" value="mailgun" x-model="mail" class="peer sr-only">
                            <div class="flex flex-col items-center justify-center p-4 h-28 rounded-xl border-2 border-secondary-100 dark:border-secondary-700 bg-white dark:bg-secondary-800 transition-all duration-200 peer-checked:border-primary-500 peer-checked:bg-primary-50/30 dark:peer-checked:bg-primary-500/10 peer-checked:ring-0 group-hover:border-secondary-300 dark:group-hover:border-secondary-600">
                                <div class="w-10 h-10 rounded-full bg-secondary-100 dark:bg-secondary-700 flex items-center justify-center text-secondary-500 dark:text-secondary-400 mb-2">
                                    <x-ui.icon icon="send" class="w-5 h-5" />
                                </div>
                                <span class="font-semibold text-sm text-secondary-900 dark:text-white">Mailgun</span>
                            </div>
                        </label>

                        {{-- Amazon SES --}}
                        <label class="cursor-pointer group">
                            <input type="radio" name="MAIL_MAILER" value="ses" x-model="mail" class="peer sr-only">
                            <div class="flex flex-col items-center justify-center p-4 h-28 rounded-xl border-2 border-secondary-100 dark:border-secondary-700 bg-white dark:bg-secondary-800 transition-all duration-200 peer-checked:border-primary-500 peer-checked:bg-primary-50/30 dark:peer-checked:bg-primary-500/10 peer-checked:ring-0 group-hover:border-secondary-300 dark:group-hover:border-secondary-600">
                                <div class="w-10 h-10 rounded-full bg-secondary-100 dark:bg-secondary-700 flex items-center justify-center text-secondary-500 dark:text-secondary-400 mb-2">
                                    <x-ui.icon icon="cloud" class="w-5 h-5" />
                                </div>
                                <span class="font-semibold text-sm text-secondary-900 dark:text-white">Amazon SES</span>
                            </div>
                        </label>

                        {{-- Postmark --}}
                        <label class="cursor-pointer group">
                            <input type="radio" name="MAIL_MAILER" value="postmark" x-model="mail" class="peer sr-only">
                            <div class="flex flex-col items-center justify-center p-4 h-28 rounded-xl border-2 border-secondary-100 dark:border-secondary-700 bg-white dark:bg-secondary-800 transition-all duration-200 peer-checked:border-primary-500 peer-checked:bg-primary-50/30 dark:peer-checked:bg-primary-500/10 peer-checked:ring-0 group-hover:border-secondary-300 dark:group-hover:border-secondary-600">
                                <div class="w-10 h-10 rounded-full bg-secondary-100 dark:bg-secondary-700 flex items-center justify-center text-secondary-500 dark:text-secondary-400 mb-2">
                                    <x-ui.icon icon="zap" class="w-5 h-5" />
                                </div>
                                <span class="font-semibold text-sm text-secondary-900 dark:text-white">Postmark</span>
                            </div>
                        </label>

                         {{-- Resend --}}
                         <label class="cursor-pointer group">
                            <input type="radio" name="MAIL_MAILER" value="resend" x-model="mail" class="peer sr-only">
                            <div class="flex flex-col items-center justify-center p-4 h-28 rounded-xl border-2 border-secondary-100 dark:border-secondary-700 bg-white dark:bg-secondary-800 transition-all duration-200 peer-checked:border-primary-500 peer-checked:bg-primary-50/30 dark:peer-checked:bg-primary-500/10 peer-checked:ring-0 group-hover:border-secondary-300 dark:group-hover:border-secondary-600">
                                <div class="w-10 h-10 rounded-full bg-secondary-100 dark:bg-secondary-700 flex items-center justify-center text-secondary-500 dark:text-secondary-400 mb-2">
                                    <x-ui.icon icon="arrow-up-right" class="w-5 h-5" />
                                </div>
                                <span class="font-semibold text-sm text-secondary-900 dark:text-white">Resend</span>
                            </div>
                        </label>

                        {{-- Log (Dev) --}}
                        <label class="cursor-pointer group">
                            <input type="radio" name="MAIL_MAILER" value="log" x-model="mail" class="peer sr-only">
                            <div class="flex flex-col items-center justify-center p-4 h-28 rounded-xl border-2 border-secondary-100 dark:border-secondary-700 bg-white dark:bg-secondary-800 transition-all duration-200 peer-checked:border-primary-500 peer-checked:bg-primary-50/30 dark:peer-checked:bg-primary-500/10 peer-checked:ring-0 group-hover:border-secondary-300 dark:group-hover:border-secondary-600">
                                <div class="w-10 h-10 rounded-full bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center text-secondary-500 mb-2">
                                    <x-ui.icon icon="file-text" class="w-5 h-5" />
                                </div>
                                <span class="font-semibold text-sm text-secondary-900 dark:text-white">Log File</span>
                            </div>
                        </label>
                    </div>

                    {{-- DYNAMIC FORMS --}}
                    <div class="bg-secondary-50 dark:bg-secondary-800/50 rounded-2xl p-6 border border-secondary-200 dark:border-secondary-700" x-cloak x-show="mail !== 'log' && mail !== 'mailpit' && mail !== 'sendmail'">
                        
                        {{-- SMTP Form --}}
                        <div x-show="mail === 'smtp'">
                            <div class="grid grid-cols-12 gap-4 mb-4">
                                <div class="col-span-8 md:col-span-9">
                                    <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">{{ trans('install.smtp_host') }}</label>
                                    <input type="text" name="MAIL_HOST" class="input-text w-full" value="smtp.gmail.com" :required="mail === 'smtp'">
                                </div>
                                <div class="col-span-4 md:col-span-3">
                                    <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">{{ trans('install.smtp_port') }}</label>
                                    <input type="number" name="MAIL_PORT" class="input-text w-full" value="587" :required="mail === 'smtp'">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">{{ trans('install.smtp_username') }}</label>
                                    <input type="text" name="MAIL_USERNAME" class="input-text w-full">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">{{ trans('install.smtp_password') }}</label>
                                    <input type="password" name="MAIL_PASSWORD" class="input-text w-full">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-secondary-500 mb-2">{{ trans('install.smtp_encryption') }}</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="MAIL_ENCRYPTION" value="tls" checked class="text-primary-600 focus:ring-primary-500"><span class="text-sm text-secondary-700 dark:text-secondary-300">TLS</span></label>
                                    <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="MAIL_ENCRYPTION" value="ssl" class="text-primary-600 focus:ring-primary-500"><span class="text-sm text-secondary-700 dark:text-secondary-300">SSL</span></label>
                                    <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="MAIL_ENCRYPTION" value="null" class="text-primary-600 focus:ring-primary-500"><span class="text-sm text-secondary-700 dark:text-secondary-300">None</span></label>
                                </div>
                            </div>
                        </div>

                        {{-- Mailgun Form --}}
                        <div x-show="mail === 'mailgun'">
                            <div class="grid gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">Domain</label>
                                    <input type="text" name="MAILGUN_DOMAIN" class="input-text w-full" placeholder="mg.yourdomain.com" :required="mail === 'mailgun'">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">Secret Key</label>
                                    <input type="password" name="MAILGUN_SECRET" class="input-text w-full font-mono" :required="mail === 'mailgun'">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-secondary-500 mb-2">Endpoint</label>
                                    <div class="flex gap-4">
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="MAILGUN_ENDPOINT" value="api.mailgun.net" checked class="text-primary-600"><span class="text-sm text-secondary-700 dark:text-secondary-300">US</span></label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="MAILGUN_ENDPOINT" value="api.eu.mailgun.net" class="text-primary-600"><span class="text-sm text-secondary-700 dark:text-secondary-300">EU</span></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                         {{-- Postmark Form --}}
                         <div x-show="mail === 'postmark'">
                            <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">Server Token</label>
                            <input type="password" name="POSTMARK_TOKEN" class="input-text w-full font-mono" :required="mail === 'postmark'">
                        </div>

                        {{-- Resend Form --}}
                        <div x-show="mail === 'resend'">
                            <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">API Key</label>
                            <input type="password" name="RESEND_KEY" class="input-text w-full font-mono" placeholder="re_..." :required="mail === 'resend'">
                        </div>

                        {{-- SES Form --}}
                         <div x-show="mail === 'ses'">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">Access Key</label>
                                    <input type="text" name="AWS_ACCESS_KEY_ID" class="input-text w-full font-mono" :required="mail === 'ses'">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">Secret Key</label>
                                    <input type="password" name="AWS_SECRET_ACCESS_KEY" class="input-text w-full font-mono" :required="mail === 'ses'">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-secondary-500 mb-1.5">Region</label>
                                <select name="AWS_DEFAULT_REGION" class="input-select w-full" :required="mail === 'ses'">
                                    <option value="us-east-1">US East (N. Virginia)</option>
                                    <option value="eu-west-1">EU (Ireland)</option>
                                    <option value="us-west-2">US West (Oregon)</option>
                                </select>
                            </div>
                        </div>

                        {{-- Test Email Section --}}
                         <div class="mt-6 pt-6 border-t border-secondary-200 dark:border-secondary-700 flex flex-col sm:flex-row gap-3">
                            <input type="email" id="test_email_recipient" class="input-text flex-1" placeholder="Enter email to test connection">
                            <button type="button" @click="testEmailConnection" :disabled="testingEmail" class="btn-secondary px-4 py-2 rounded-lg whitespace-nowrap">
                                <span x-show="!testingEmail">Send Test</span>
                                <span x-show="testingEmail">Sending...</span>
                            </button>
                        </div>
                        <div x-show="emailTestResult" class="mt-2 text-sm" :class="emailTestResult === 'success' ? 'text-emerald-600' : 'text-red-600'">
                            <span x-text="emailTestMessage"></span>
                        </div>
                    </div>

                    {{-- Log/Dev Message --}}
                    <div x-show="mail === 'log'" class="p-4 rounded-xl bg-secondary-100 dark:bg-secondary-800 text-secondary-600 dark:text-secondary-400 text-sm flex gap-3">
                         <x-ui.icon icon="file-text" class="w-5 h-5 flex-shrink-0" />
                         <p>Emails will be written to <code class="px-1 bg-white dark:bg-secondary-700 rounded border border-secondary-200 dark:border-secondary-600 text-xs">storage/logs/laravel.log</code>. No actual emails will be sent.</p>
                    </div>

                </div>
            </div>

            {{-- Footer Actions --}}
            <div class="flex justify-between items-center mt-12 pt-6 border-t border-secondary-200 dark:border-secondary-800">
                <button type="button" @click="tab = 1; window.scrollTo(0,0)" class="text-secondary-500 hover:text-secondary-800 dark:hover:text-secondary-200 font-medium px-4 py-2 transition-colors">
                    &larr; Back
                </button>
                <button type="button" @click="validateForm" class="bg-primary-600 hover:bg-primary-700 text-white font-medium px-8 py-3 rounded-xl shadow-lg shadow-primary-500/20 hover:shadow-primary-500/30 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                    Next Step <x-ui.icon icon="arrow-right" class="w-4 h-4" />
                </button>
            </div>

        </div>


        <!-- Step 3: Install -->
        <div x-show="tab === 3" x-cloak 
             x-data="{ 
                response: 0,
                copied: null,
                copyUrl(url) {
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(url);
                    } else {
                        const textArea = document.createElement('textarea');
                        textArea.value = url;
                        textArea.style.position = 'fixed';
                        textArea.style.left = '-999999px';
                        document.body.appendChild(textArea);
                        textArea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textArea);
                    }
                    this.copied = url;
                    setTimeout(() => this.copied = null, 2000);
                }
             }" 
             @@show-error="installing = false; response = 500">
            
            @php
                $locale = request()->segment(1) ?: 'en-us';
                $baseUrl = rtrim(url('/'), '/');
                $adminUrl = $baseUrl . '/' . $locale . '/admin';
                $partnerUrl = $baseUrl . '/' . $locale . '/partner';
                $staffUrl = $baseUrl . '/' . $locale . '/staff';
            @endphp

            {{-- THE ORCHESTRATOR (Full-screen Loader) --}}
            <div x-show="installing" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-white/90 dark:bg-secondary-900/90 backdrop-blur-md">
                
                {{-- Pulsing Ring --}}
                <div class="relative w-20 h-20 mb-8">
                    <div class="absolute inset-0 border-4 border-primary-200 dark:border-primary-900 rounded-full"></div>
                    <div class="absolute inset-0 border-4 border-primary-600 rounded-full border-t-transparent animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <x-ui.icon icon="server" class="w-8 h-8 text-primary-600 animate-pulse" />
                    </div>
                </div>

                <h2 class="text-2xl font-bold text-secondary-900 dark:text-white mb-2">Installing...</h2>
                <p class="text-secondary-500 font-medium">This may take a while, keep this page open.</p>
            </div>

            {{-- THE PRE-INSTALL STATE (Before clicking install) --}}
            <div x-show="!installing && response == 0">
                
                {{-- Header --}}
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-secondary-900 dark:text-white tracking-tight">Ready to Launch</h1>
                    <p class="text-secondary-500 dark:text-secondary-400 text-base mt-2">Review your configuration and deploy your loyalty platform.</p>
                </div>

                {{-- Demo Data Toggle Card --}}
                <div @click="seedDemo = !seedDemo; document.getElementById('APP_DEMO_INPUT').value = seedDemo ? 'true' : 'false'" 
                     class="relative flex items-start gap-4 p-4 rounded-xl border cursor-pointer transition-colors mb-8"
                     :class="seedDemo ? 'border-primary-400 dark:border-primary-600 bg-primary-50/50 dark:bg-primary-900/10' : 'border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-800/50 hover:border-primary-300'">
                    <div class="flex items-center h-6 pt-0.5">
                        {{-- Custom Toggle UI --}}
                        <div class="relative w-11 h-6 rounded-full transition-colors"
                             :class="seedDemo ? 'bg-primary-600' : 'bg-secondary-200 dark:bg-secondary-700'">
                            <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform" 
                                 :class="seedDemo ? 'translate-x-5' : 'translate-x-0'"></div>
                        </div>
                    </div>
                    
                    <div class="flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-bold text-secondary-900 dark:text-white">Populate with sample data</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 uppercase tracking-wide">Recommended for Testing</span>
                        </div>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">
                            Adds example partners, members, staff, cards, and transactions. Preview the homepage with demo loyalty cards and explore all dashboards. Installation takes a bit longer.
                        </p>
                        <p x-show="!seedDemo" x-cloak class="text-xs text-amber-600 dark:text-amber-400 mt-2 flex items-center gap-1">
                            <x-ui.icon icon="alert-triangle" class="w-3 h-3" />
                            You will start with a completely empty database.
                        </p>
                    </div>
                </div>

                {{-- The "Receipt" Container --}}
                <div class="bg-white dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 rounded-2xl overflow-hidden shadow-sm mb-8">
                    
                    {{-- Section 1: Configuration Summary --}}
                    <div class="p-6 border-b border-secondary-100 dark:border-secondary-700/50">
                        <h3 class="text-xs font-bold text-secondary-400 uppercase tracking-wider mb-4">Configuration Summary</h3>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <span class="block text-xs font-semibold text-secondary-500 mb-1">Admin</span>
                                <span class="text-secondary-900 dark:text-white font-medium break-all text-sm" x-text="adminName || 'Admin'"></span>
                                <span class="block text-xs text-secondary-400 break-all mt-0.5" x-text="adminEmail || '—'"></span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-secondary-500 mb-1">Database</span>
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span class="text-secondary-900 dark:text-white font-medium capitalize text-sm" x-text="db"></span>
                                </div>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-secondary-500 mb-1">Mail Driver</span>
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-primary-500"></span>
                                    <span class="text-secondary-900 dark:text-white font-medium capitalize text-sm" x-text="mail"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: System Access Points --}}
                    <div class="p-6 bg-secondary-50/50 dark:bg-secondary-800/50">
                        <h3 class="text-xs font-bold text-secondary-400 uppercase tracking-wider mb-4">System Access Points</h3>
                        
                        <div class="grid gap-3">
                            {{-- Admin Dashboard --}}
                            <div class="group flex items-center justify-between p-3 bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-700 rounded-xl transition-colors">
                                <div class="flex items-center gap-3 overflow-hidden flex-1">
                                    <div class="w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                                        <x-ui.icon icon="shield" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-secondary-900 dark:text-white">Admin Dashboard</p>
                                        <span class="text-xs text-secondary-500 truncate block">{{ $adminUrl }}</span>
                                        {{-- Admin uses configured credentials, not demo --}}
                                        <p class="text-xs text-secondary-400 mt-1">Login with your configured admin email & password</p>
                                    </div>
                                </div>
                                <div class="flex items-center pl-2">
                                    <button type="button" @click="copyUrl('{{ $adminUrl }}')" class="p-2 rounded-lg transition-colors" :class="copied === '{{ $adminUrl }}' ? 'text-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'text-secondary-400 hover:text-secondary-600 hover:bg-secondary-100 dark:hover:bg-secondary-800'" title="Copy URL">
                                        <x-ui.icon x-show="copied !== '{{ $adminUrl }}'" icon="copy" class="w-4 h-4" />
                                        <x-ui.icon x-show="copied === '{{ $adminUrl }}'" x-cloak icon="check" class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            {{-- Partner Dashboard (with demo credentials when enabled) --}}
                            <div class="group flex items-center justify-between p-3 bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-700 rounded-xl transition-colors">
                                <div class="flex items-center gap-3 overflow-hidden flex-1">
                                    <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                        <x-ui.icon icon="briefcase" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-secondary-900 dark:text-white">Partner Dashboard</p>
                                        <span class="text-xs text-secondary-500 truncate block">{{ $partnerUrl }}</span>
                                        <p x-show="seedDemo" x-cloak class="text-xs text-blue-600 dark:text-blue-400 mt-1 font-mono">partner@example.com / welcome3210</p>
                                    </div>
                                </div>
                                <div class="flex items-center pl-2">
                                    <button type="button" @click="copyUrl('{{ $partnerUrl }}')" class="p-2 rounded-lg transition-colors" :class="copied === '{{ $partnerUrl }}' ? 'text-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'text-secondary-400 hover:text-secondary-600 hover:bg-secondary-100 dark:hover:bg-secondary-800'" title="Copy URL">
                                        <x-ui.icon x-show="copied !== '{{ $partnerUrl }}'" icon="copy" class="w-4 h-4" />
                                        <x-ui.icon x-show="copied === '{{ $partnerUrl }}'" x-cloak icon="check" class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            {{-- Staff Dashboard (with demo credentials when enabled) --}}
                            <div class="group flex items-center justify-between p-3 bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-700 rounded-xl transition-colors">
                                <div class="flex items-center gap-3 overflow-hidden flex-1">
                                    <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                                        <x-ui.icon icon="users" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-secondary-900 dark:text-white">Staff Dashboard</p>
                                        <span class="text-xs text-secondary-500 truncate block">{{ $staffUrl }}</span>
                                        <p x-show="seedDemo" x-cloak class="text-xs text-purple-600 dark:text-purple-400 mt-1 font-mono">staff@example.com / welcome3210</p>
                                    </div>
                                </div>
                                <div class="flex items-center pl-2">
                                    <button type="button" @click="copyUrl('{{ $staffUrl }}')" class="p-2 rounded-lg transition-colors" :class="copied === '{{ $staffUrl }}' ? 'text-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'text-secondary-400 hover:text-secondary-600 hover:bg-secondary-100 dark:hover:bg-secondary-800'" title="Copy URL">
                                        <x-ui.icon x-show="copied !== '{{ $staffUrl }}'" icon="copy" class="w-4 h-4" />
                                        <x-ui.icon x-show="copied === '{{ $staffUrl }}'" x-cloak icon="check" class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            {{-- Member Dashboard (only shown when demo enabled) --}}
                            @php $memberUrl = $baseUrl . '/' . $locale . '/member'; @endphp
                            <div x-show="seedDemo" x-cloak class="group flex items-center justify-between p-3 bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-700 rounded-xl transition-colors">
                                <div class="flex items-center gap-3 overflow-hidden flex-1">
                                    <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0">
                                        <x-ui.icon icon="user" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-secondary-900 dark:text-white">Member Dashboard</p>
                                        <span class="text-xs text-secondary-500 truncate block">{{ $memberUrl }}</span>
                                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-mono">member@example.com / welcome3210</p>
                                    </div>
                                </div>
                                <div class="flex items-center pl-2">
                                    <button type="button" @click="copyUrl('{{ $memberUrl }}')" class="p-2 rounded-lg transition-colors" :class="copied === '{{ $memberUrl }}' ? 'text-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'text-secondary-400 hover:text-secondary-600 hover:bg-secondary-100 dark:hover:bg-secondary-800'" title="Copy URL">
                                        <x-ui.icon x-show="copied !== '{{ $memberUrl }}'" icon="copy" class="w-4 h-4" />
                                        <x-ui.icon x-show="copied === '{{ $memberUrl }}'" x-cloak icon="check" class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Important Note --}}
                <div class="bg-primary-50 dark:bg-primary-900/10 rounded-xl p-4 flex items-start gap-3 border border-primary-100 dark:border-primary-800/30 mb-8">
                    <x-ui.icon icon="info" class="w-5 h-5 text-primary-600 mt-0.5 flex-shrink-0" />
                    <p class="text-sm text-primary-800 dark:text-primary-200">
                        After installation, you'll be redirected to the admin login. Your credentials will be the email and password you configured in Step 2.
                    </p>
                </div>

                {{-- Footer --}}
                <div class="flex justify-between items-center mt-10 pt-6 border-t border-secondary-200 dark:border-secondary-800">
                    <button type="button" @click="tab = 2; window.scrollTo(0,0)" class="text-secondary-500 hover:text-secondary-800 dark:hover:text-secondary-200 font-medium px-4 py-2 transition-colors">
                        &larr; Back to Configuration
                    </button>
                    
                    <button type="button" id="submitForm" 
                        hx-post="{{ route('installation.install') }}"
                        hx-include="#form1"
                        @click="installing = true; submitForm()"
                        class="bg-primary-600 hover:bg-primary-700 text-white font-medium px-8 py-3 rounded-xl shadow-lg shadow-primary-500/20 hover:shadow-primary-500/30 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                        Install Now
                        <x-ui.icon icon="rocket" class="w-4 h-4" />
                    </button>
                </div>
            </div>

            {{-- Error State --}}
            <div x-show="response == 500" x-cloak>
                {{-- Header --}}
                <div class="mb-10">
                    <h1 class="text-2xl font-bold text-secondary-900 dark:text-white tracking-tight">Installation Failed</h1>
                    <p class="text-secondary-500 dark:text-secondary-400 text-base mt-2">We encountered an error while configuring the server.</p>
                </div>

                {{-- Error Card --}}
                <div class="bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-800 rounded-2xl p-6 mb-8 flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                        <x-ui.icon icon="x" class="w-6 h-6 text-red-600 dark:text-red-400" />
                    </div>
                    <div class="pt-1">
                        <h2 class="text-lg font-bold text-secondary-900 dark:text-white mb-1">Something Went Wrong</h2>
                        <p class="text-secondary-600 dark:text-secondary-400 text-sm leading-relaxed">
                            The installation could not be completed. Please check the installation log for details and try again.
                        </p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-between items-center mt-12 pt-6 border-t border-secondary-200 dark:border-secondary-800">
                    <a href="{{ route('installation.log') }}" class="text-sm text-secondary-500 hover:text-secondary-800 dark:hover:text-secondary-300 flex items-center gap-1 transition-colors">
                        <x-ui.icon icon="file-text" class="w-4 h-4" />
                        Download Install Log
                    </a>
                    
                    <button type="button" @click="location.reload()" class="bg-primary-600 hover:bg-primary-700 text-white font-medium px-8 py-3 rounded-xl shadow-lg shadow-primary-500/20 hover:shadow-primary-500/30 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                        Try Again
                        <x-ui.icon icon="refresh-cw" class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script>
        function testDatabaseConnection() {
            const data = this;
            data.testingDb = true;
            data.dbTestResult = null;

            // Simulate database test (you can implement actual AJAX call here)
            setTimeout(() => {
                // For now, just simulate success if all fields are filled
                const host = document.querySelector('[name="DB_HOST"]').value;
                const database = document.querySelector('[name="DB_DATABASE"]').value;
                const username = document.querySelector('[name="DB_USERNAME"]').value;
                
                if (host && database && username) {
                    data.dbTestResult = 'success';
                } else {
                    data.dbTestResult = 'error';
                }
                data.testingDb = false;
            }, 1500);
        }

        function testEmailConnection() {
            const data = this;
            const recipient = document.getElementById('test_email_recipient').value;
            
            if (!recipient) {
                data.emailTestResult = 'error';
                data.emailTestMessage = 'Please enter an email address to receive the test.';
                return;
            }
            
            data.testingEmail = true;
            data.emailTestResult = null;
            data.emailTestMessage = '';

            // Collect form data
            const form = document.getElementById('form1');
            const formData = new FormData(form);
            formData.append('test_recipient', recipient);

            fetch('{{ route('installation.test-email') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                data.testingEmail = false;
                if (result.success) {
                    data.emailTestResult = 'success';
                    data.emailTestMessage = result.message;
                } else {
                    data.emailTestResult = 'error';
                    data.emailTestMessage = result.error || result.message;
                }
            })
            .catch(error => {
                data.testingEmail = false;
                data.emailTestResult = 'error';
                data.emailTestMessage = 'Network error. Please check your connection.';
            });
        }

        function validateForm() {
            const form = document.getElementById('form1');
            const pass = document.getElementById('ADMIN_PASS');
            const confirm = document.getElementById('ADMIN_PASS_CONFIRM');

            // Access form Alpine component
            let component = null;
            if (form._x_dataStack) {
                component = form._x_dataStack[0];
            }

            const isMatch = pass.value === confirm.value;
            
            if (component) {
                component.passwordMatch = isMatch;
            }

            if (!isMatch) {
                confirm.setCustomValidity("{{ trans('install.passwords_must_match') }}");
            } else {
                confirm.setCustomValidity('');
            }

            if (form.reportValidity()) {
                // Capture admin details into Alpine state for step 3 summary
                if (component) {
                    component.adminEmail = document.querySelector('[name=ADMIN_MAIL]')?.value || '';
                    component.adminName = document.querySelector('[name=ADMIN_NAME]')?.value || 'Admin';
                }

                // Access Alpine state from body element
                const bodyComponent = document.body._x_dataStack?.[0];
                if (bodyComponent) {
                    bodyComponent.tab = 3;
                    window.scrollTo(0, 0);
                }
            }
        }

        window.submitForm = function () {
            // Disable buttons
            document.querySelectorAll('button').forEach(b => b.disabled = true);
        }

        document.body.addEventListener('htmx:beforeSwap', function (evt) {
            evt.detail.shouldSwap = false;
            if (evt.detail.xhr.status === 500) {
                // Trigger error state
                const bodyComponent = document.body._x_dataStack?.[0];
                if (bodyComponent) {
                    bodyComponent.installing = false;
                }
                // Dispatch event to the step 3 div
                const step3 = document.querySelector('[x-data*="response"]');
                if (step3 && step3._x_dataStack?.[0]) {
                    step3._x_dataStack[0].response = 500;
                }
                evt.detail.isError = false;
            } else if (evt.detail.xhr.status === 422) {
                // Validation error - go back to step 2
                const bodyComponent = document.body._x_dataStack?.[0];
                if (bodyComponent) {
                    bodyComponent.tab = 2;
                    bodyComponent.installing = false;
                }
                evt.detail.isError = true;
            } else {
                // Success
                window.location.href = '{{ route('admin.login') }}';
            }
        });
    </script>
@endsection
