@php
/**
 * Helper function to get column value, handling JSON path columns correctly.
 * For JSON path columns (e.g., 'ecommerce_settings.shopify.enabled'), this accesses
 * the nested value from the JSON column instead of trying to access a non-existent attribute.
 */
$getColumnValue = function($column, $form) {
    $columnName = $column['name'];
    $default = $column['default'] ?? null;
    $model = $form['data'];
    
    // For insert mode (empty model), return the default value
    if (!$model->exists && empty($model->getAttributes())) {
        return $default;
    }
    
    // Check if this is a JSON path column
    if (str_contains($columnName, '.') && isset($column['json_path_info']['is_json_path']) && $column['json_path_info']['is_json_path']) {
        $jsonColumn = $column['json_path_info']['json_column'];
        $jsonPath = $column['json_path_info']['json_path'];
        
        // Safely get the JSON column value
        try {
            $jsonData = $model->getAttribute($jsonColumn);
        } catch (\Throwable $e) {
            return $default;
        }
        
        if (is_array($jsonData)) {
            $value = $jsonData;
            foreach (explode('.', $jsonPath) as $segment) {
                $value = $value[$segment] ?? null;
                if ($value === null) break;
            }
            return $value ?? $default;
        }
        return $default;
    }
    
    // Standard attribute access - use getAttribute to avoid MissingAttributeException
    // Note: Only use default for INSERT (new records), not for EDIT where null is intentional
    try {
        $value = $model->getAttribute($columnName);
        
        // For existing records, return null as-is (user may have intentionally cleared the field)
        // Only apply default for new records (insert mode)
        if ($value === null && $model->exists) {
            return null; // Respect the null value - don't override with default
        }
        
        $result = $value ?? $default;
        
        // Minor units conversion for currency fields stored as cents/minor units
        // Example: minorUnits=100 means 1000 stored → 10.00 displayed
        // Supports: 100 (most currencies), 1 (JPY/KRW), 1000 (BHD/KWD/OMR)
        if (isset($column['minorUnits']) && $column['minorUnits'] > 1 && is_numeric($result)) {
            $divided = $result / $column['minorUnits'];
            // Calculate decimal places from minorUnits: 100 → 2, 1000 → 3
            $decimals = (int) log10($column['minorUnits']);
            $result = number_format($divided, $decimals, '.', '');
        }
        // Decimal formatting for decimal(10,2) fields that store actual decimals (not cents)
        // Uses explicit 'decimalPlaces' setting, or calculates from 'step' value
        // Example: step=0.01 means 2 decimal places, step=0.001 means 3 decimal places
        elseif (is_numeric($result) && $result !== null && $result !== '') {
            $decimals = null;
            
            // Explicit decimalPlaces setting takes priority
            if (isset($column['decimalPlaces'])) {
                $decimals = (int) $column['decimalPlaces'];
            }
            // Otherwise calculate from step value (0.01 → 2, 0.001 → 3)
            elseif (isset($column['step']) && is_numeric($column['step']) && $column['step'] < 1) {
                $decimals = max(0, (int) -log10((float) $column['step']));
            }
            
            if ($decimals !== null && $decimals > 0) {
                $result = number_format((float) $result, $decimals, '.', '');
            }
        }
        
        return $result;
    } catch (\Throwable $e) {
        return $default;
    }
};
@endphp
@if (in_array($column['type'], ['string', 'textarea']))
    @if ($column['translatable'])
        @php
            // Get the default locale - only this locale is required
            $defaultLocale = config('app.locale');
            $hasMultipleLanguages = count($languages['all']) > 1;
            $isRequired = in_array('required', $column['validate']);
            
            // Find the default language in the list (for proper label rendering)
            $defaultLanguage = collect($languages['all'])->firstWhere('locale', $defaultLocale) ?? $languages['current'];
            
            // Other languages (excluding the default)
            $otherLanguages = collect($languages['all'])->filter(fn($lang) => $lang['locale'] !== $defaultLocale)->values();
        @endphp
        @if ($hasMultipleLanguages)
            <fieldset class="relative p-5 border border-secondary-200 dark:border-secondary-700 rounded-xl">
                <legend class="px-2 text-sm font-medium text-secondary-700 dark:text-secondary-300">{{ $column['text'] }}</legend>
        @endif
        @php
            // Default language label (always required if field is required)
            $label = $hasMultipleLanguages 
                ? '<span class="inline-flex items-center gap-2 align-middle"><span class="w-4 h-4 rounded-full ring-1 ring-secondary-200 dark:ring-secondary-600 overflow-hidden inline-flex items-center justify-center fis fi-' . strtolower($defaultLanguage['countryCode']) . '"></span><span class="font-medium text-secondary-900 dark:text-white">' . $defaultLanguage['languageName'] . '</span><span class="text-xs text-secondary-400 dark:text-secondary-500 uppercase">(' . $defaultLanguage['countryCode'] . ')</span></span>'
                : $column['text'];
            $value = $form['data']->getTranslation($column['name'], $defaultLocale, false) ?? $column['default'];
        @endphp
        {{-- Default language input (required if field is required) --}}
        @if ($column['type'] == 'string')
            <x-forms.input
                :form="$form"
                :value="$value"
                :type="$column['format']"
                :prefix="$column['prefix']"
                :suffix="$column['suffix']"
                :class="($hasMultipleLanguages) ? 'mb-0' : ''"
                :min="$column['min']"
                :max="$column['max']"
                :step="$column['step']"
                :name="$column['name'] . '[' . $defaultLocale . ']'"
                :icon="$column['format'] == 'email' ? 'mail' : null"
                :label="$label"
                :help="$column['help']"
                :placeholder="$column['placeholder']"
                :required="$isRequired"
                :ai="$column['ai']"
                :js="$column['js'] ?? null"
            />
        @elseif ($column['type'] == 'textarea')
            <x-forms.textarea
                :form="$form"
                :value="$value"
                :type="$column['format']"
                :prefix="$column['prefix']"
                :suffix="$column['suffix']"
                :class="($hasMultipleLanguages) ? 'mb-0' : ''"
                :min="$column['min']"
                :max="$column['max']"
                :step="$column['step']"
                :name="$column['name'] . '[' . $defaultLocale . ']'"
                :icon="$column['format'] == 'email' ? 'mail' : null"
                :label="$label"
                :help="$column['help']"
                :placeholder="$column['placeholder']"
                :required="$isRequired"
                :ai="$column['ai']"
                :js="$column['js'] ?? null"
            />
        @endif
        
        {{-- Other languages in collapsible section (never required) --}}
        @if ($otherLanguages->count() > 0)
            <div class="mt-4" x-data="{ expanded: false }">
                {{-- Collapsible toggle button --}}
                <button 
                    type="button"
                    @click="expanded = !expanded"
                    class="inline-flex items-center gap-2 text-sm font-medium text-secondary-500 dark:text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-300 transition-colors"
                >
                    <x-ui.icon 
                        icon="chevron-right" 
                        class="w-4 h-4 transition-transform duration-200"
                        ::class="{ 'rotate-90': expanded }"
                    />
                    <span x-text="expanded ? '{{ trans('common.hide_other_languages') }}' : '{{ trans('common.show_other_languages', ['count' => $otherLanguages->count()]) }}'"></span>
                </button>
                
                {{-- Collapsible content --}}
                <div
                    x-show="expanded"
                    x-collapse
                    x-cloak
                    class="mt-3 pl-4 border-l-2 border-secondary-200 dark:border-secondary-700 space-y-4"
                >
                    <p class="text-xs text-secondary-400 dark:text-secondary-500 italic">
                        {{ trans('common.other_languages_optional_hint') }}
                    </p>
                    @foreach ($otherLanguages as $language)
                        @php
                            $label = '<span class="inline-flex items-center gap-2 align-middle"><span class="w-4 h-4 rounded-full ring-1 ring-secondary-200 dark:ring-secondary-600 overflow-hidden inline-flex items-center justify-center fis fi-' . strtolower($language['countryCode']) . '"></span><span class="font-medium text-secondary-900 dark:text-white">' . $language['languageName'] . '</span><span class="text-xs text-secondary-400 dark:text-secondary-500 uppercase">(' . $language['countryCode'] . ')</span></span>';
                            $value = $form['data']->getTranslation($column['name'], $language['locale'], false) ?? '';
                        @endphp
                        @if ($column['type'] == 'string')
                            <x-forms.input
                                :form="$form"
                                :value="$value"
                                :type="$column['format']"
                                :prefix="$column['prefix']"
                                :suffix="$column['suffix']"
                                :min="$column['min']"
                                :max="$column['max']"
                                :step="$column['step']"
                                :name="$column['name'] . '[' . $language['locale'] . ']'"
                                :icon="$column['format'] == 'email' ? 'mail' : null"
                                :label="$label"
                                :help="null"
                                :placeholder="$column['placeholder']"
                                :required="false"
                                :ai="$column['ai']"
                                :js="$column['js'] ?? null"
                            />
                        @elseif ($column['type'] == 'textarea')
                            <x-forms.textarea
                                :form="$form"
                                :value="$value"
                                :type="$column['format']"
                                :prefix="$column['prefix']"
                                :suffix="$column['suffix']"
                                :min="$column['min']"
                                :max="$column['max']"
                                :step="$column['step']"
                                :name="$column['name'] . '[' . $language['locale'] . ']'"
                                :icon="$column['format'] == 'email' ? 'mail' : null"
                                :label="$label"
                                :help="null"
                                :placeholder="$column['placeholder']"
                                :required="false"
                                :ai="$column['ai']"
                                :js="$column['js'] ?? null"
                            />
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
        @if ($hasMultipleLanguages)
            </fieldset>
        @endif
    @else
        @if ($column['type'] == 'string')
            <x-forms.input
                class=""
                :form="$form"
                :value="$getColumnValue($column, $form)"
                :type="$column['format']"
                :prefix="$column['prefix']"
                :suffix="$column['suffix']"
                :min="$column['min']"
                :max="$column['max']"
                :step="$column['step']"
                :name="$column['name']"
                :icon="$column['format'] == 'email' ? 'mail' : null"
                :label="$column['text']"
                :help="$column['help']"
                :placeholder="$column['placeholder']"
                :required="in_array('required', $column['validate'])"
                :ai="$column['ai']"
                :js="$column['js'] ?? null"
            />
        @elseif ($column['type'] == 'textarea')
            <x-forms.textarea
                class=""
                :form="$form"
                :value="$getColumnValue($column, $form)"
                :type="$column['format']"
                :prefix="$column['prefix']"
                :suffix="$column['suffix']"
                :min="$column['min']"
                :max="$column['max']"
                :step="$column['step']"
                :name="$column['name']"
                :icon="$column['format'] == 'email' ? 'mail' : null"
                :label="$column['text']"
                :help="$column['help']"
                :placeholder="$column['placeholder']"
                :required="in_array('required', $column['validate'])"
                :ai="$column['ai']"
                :js="$column['js'] ?? null"
            />
        @endif
    @endif
@elseif($column['type'] == 'icon-picker')
    <x-forms.icon-picker
        class=""
        :form="$form"
        :value="$getColumnValue($column, $form)"
        :name="$column['name']"
        :label="$column['text']"
        :help="$column['help']"
        :required="in_array('required', $column['validate'])"
        :js="$column['js'] ?? null"
    />
@elseif($column['type'] == 'info')
    @php
        $variant = $column['variant'] ?? 'info';
        $classes = match($variant) {
            'warning' => 'bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            'error' => 'bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-300 border-red-200 dark:border-red-800',
            'success' => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            default => 'bg-sky-50 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300 border-sky-200 dark:border-sky-800',
        };
        $iconClass = match($variant) {
            'warning' => 'text-amber-500',
            'error' => 'text-red-500',
            'success' => 'text-emerald-500',
            default => 'text-sky-500',
        };
        $defaultIcon = match($variant) {
            'warning' => 'triangle-alert',
            'error' => 'circle-x',
            'success' => 'circle-check',
            default => 'info',
        };
        $icon = $column['icon'] ?? $defaultIcon;
    @endphp
    <div class="p-4 rounded-lg border {{ $classes }} mb-6">
        <div class="flex items-start gap-3">
            @if($icon)
                <x-ui.icon :icon="$icon" class="w-5 h-5 mt-0.5 {{ $iconClass }} flex-shrink-0" />
            @endif
            <div class="flex-1">
                @if(isset($column['title']))
                    <h3 class="text-sm font-semibold mb-1">{{ $column['title'] }}</h3>
                @endif
                <div class="text-sm opacity-90">
                    {!! $column['text'] !!}
                </div>
            </div>
        </div>
    </div>
@elseif($column['type'] == 'password')
    <x-forms.input 
        class=""
        :form="$form"
        value=""
        type="password"
        :prefix="$column['prefix']"
        :suffix="$column['suffix']"
        :name="$column['name']"
        icon="lock"
        :generate-password="$column['generatePasswordButton']"
        :mail-password="$column['mailUserPassword']"
        :mail-password-checked="$column['mailUserPasswordChecked']"
        :label="$column['text']"
        :help="$column['help']"
        :placeholder="$column['placeholder']"
        :required="in_array('required', $column['validate'])"
        :js="$column['js'] ?? null"
    />
@elseif($column['type'] == 'image' || $column['type'] == 'avatar')
    <x-forms.image
        class=""
        :default="($form['view'] == 'insert') ? $column['default'] : null"
        :value="$form['data']->{$column['name']} !== null && $column['conversion'] !== null ? $form['data']->{$column['name'] . '-' . $column['conversion']} : $form['data']->{$column['name']}"
        :type="$column['type'] == 'avatar' ? 'avatar' : 'image'" 
        :name="$column['name']"
        :label="$column['text']"
        :help="$column['help']"
        :placeholder="$column['placeholder']"
        :accept="$column['accept']"
        :validate="$column['validate']"
        :required="in_array('required', $column['validate'])"
        :js="$column['js'] ?? null"
    />
@elseif($column['type'] == 'boolean')
    @php
        $boolValue = $getColumnValue($column, $form);
        // Strict check for boolean true, string '1', or int 1
        $isChecked = filter_var($boolValue, FILTER_VALIDATE_BOOLEAN) === true;
    @endphp
    <x-forms.checkbox
        class=""
        :name="$column['name']"
        :checked="$isChecked"
        :label="$column['text']"
        :help="$column['help']"
        :required="in_array('required', $column['validate'])"
        :js="$column['js'] ?? null"
    />
@elseif(in_array($column['type'], ['belongsToMany', 'hasMany']))
    <x-forms.multiselect
        class=""
        :name="$column['name']"
        :value="$getColumnValue($column, $form)"
        :options="$column['options']"
        :label="$column['text']"
        :help="$column['help']"
        :required="in_array('required', $column['validate'])"
        :js="$column['js'] ?? null"
    />
@elseif(in_array($column['type'], ['time_zone', 'currency', 'locale', 'select', 'belongsTo']))
    <x-forms.select
        class=""
        :type="$column['type']"
        :multiselect="false"
        :name="$column['name']"
        :value="$getColumnValue($column, $form)"
        :options="$column['options']"
        :label="$column['text']"
        :help="$column['help']"
        :required="in_array('required', $column['validate'])"
        :onchange="$column['onchange'] ?? null"
    />
@elseif ($column['type'] == 'number')
    <x-forms.input
        class=""
        :form="$form"
        :value="$getColumnValue($column, $form)"
        type="number"
        :prefix="$column['prefix']"
        :suffix="$column['suffix']"
        :min="$column['min']"
        :max="$column['max']"
        :step="$column['step']"
        :name="$column['name']"
        :label="$column['text']"
        :help="$column['help']"
        :placeholder="$column['placeholder']"
        :required="in_array('required', $column['validate'])"
        :js="$column['js'] ?? null"
    />
@elseif ($column['type'] == 'opening_hours')
    <x-forms.opening-hours
        class=""
        :name="$column['name']"
        :value="$getColumnValue($column, $form)"
        :label="$column['text']"
        :help="$column['help']"
        :required="in_array('required', $column['validate'])"
    />
@endif