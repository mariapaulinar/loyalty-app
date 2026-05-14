<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * DataService provides core CRUD operations for the DataDefinition system,
 * including record creation, updating, deletion, validation, and JSON
 * column handling for nested data structures.
 *
 * Key Features:
 * - Nested JSON path support (e.g., 'ecommerce_settings.shopify.enabled')
 * - Automatic validation transformation for dotted field names
 * - HTML sanitization with HTMLPurifier
 * - Relation handling (belongsTo, belongsToMany)
 * - Password hashing and email notifications
 */

namespace App\Services\Data;

use App\Notifications\Member\Registration;
use Carbon\Carbon;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

class DataService
{
    /**
     * Pending JSON column updates to be batched and applied at the end of processInputData.
     *
     * Structure: ['json_column_name' => ['path.to.key' => value, ...], ...]
     *
     * @var array<string, array<string, mixed>>
     */
    private array $pendingJsonUpdates = [];

    /**
     * Set a nested value in an array using dot notation path.
     *
     * Example: setNestedValue($arr, 'shopify.enabled', true)
     * Results in: ['shopify' => ['enabled' => true]]
     *
     * @param  array  $array  The array to modify (passed by reference)
     * @param  string  $path  Dot notation path (e.g., 'shopify.enabled')
     * @param  mixed  $value  The value to set
     */
    private function setNestedValue(array &$array, string $path, mixed $value): void
    {
        $keys = explode('.', $path);
        $current = &$array;

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                // Last key - set the value
                $current[$key] = $value;
            } else {
                // Intermediate key - ensure array exists
                if (! isset($current[$key]) || ! is_array($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
        }
    }

    /**
     * Apply all pending JSON column updates to the model.
     *
     * This method batches all nested JSON updates and applies them at once,
     * ensuring proper merging with existing JSON data.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  The model to update
     */
    private function applyPendingJsonUpdates($model): void
    {
        foreach ($this->pendingJsonUpdates as $jsonColumn => $updates) {
            // Get the current JSON value from the model
            // Handle case where the column wasn't selected in the original query
            $currentValue = null;

            // Check if the attribute was loaded (exists in model's attributes)
            $loadedAttributes = $model->getAttributes();
            if (array_key_exists($jsonColumn, $loadedAttributes)) {
                // Attribute was loaded - use its current value
                $currentValue = $model->getAttributeValue($jsonColumn);
            } else {
                // Attribute wasn't loaded - fetch it directly from the database
                // This prevents data loss when partial selects are used
                $primaryKey = $model->getKeyName();
                $primaryValue = $model->getKey();

                if ($primaryValue !== null) {
                    // Existing record - fetch current value from DB
                    $freshValue = DB::table($model->getTable())
                        ->where($primaryKey, $primaryValue)
                        ->value($jsonColumn);

                    // Decode if it's a JSON string
                    if (is_string($freshValue)) {
                        $currentValue = json_decode($freshValue, true);
                    } elseif (is_array($freshValue)) {
                        $currentValue = $freshValue;
                    }
                }
            }

            // Ensure it's an array
            if (! is_array($currentValue)) {
                $currentValue = [];
            }

            // Apply each update using nested path
            foreach ($updates as $path => $value) {
                $this->setNestedValue($currentValue, $path, $value);
            }

            // Set the merged value back on the model
            $model->{$jsonColumn} = $currentValue;
        }

        // Clear pending updates
        $this->pendingJsonUpdates = [];
    }

    /**
     * Check if a model has dirty attributes that are JSON-cast columns.
     *
     * This is used to detect when we need to disable activity logging to prevent
     * Spatie's laravel-activitylog from trying to call JSON column names as
     * relationship methods (which causes BadMethodCallException).
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  The model to check
     * @return bool True if the model has dirty JSON attributes
     */
    private function hasJsonDirtyAttributes($model): bool
    {
        $dirtyAttributes = $model->getDirty();

        if (empty($dirtyAttributes)) {
            return false;
        }

        $casts = $model->getCasts();
        $jsonCastTypes = ['array', 'json', 'object', 'collection', 'encrypted:array', 'encrypted:json'];

        foreach (array_keys($dirtyAttributes) as $attribute) {
            $castType = $casts[$attribute] ?? null;

            if ($castType !== null) {
                // Handle cast types that may have parameters (e.g., 'encrypted:array')
                $baseCastType = explode(':', $castType)[0];

                if (in_array($castType, $jsonCastTypes, true) || in_array($baseCastType, ['array', 'json', 'object', 'collection'], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Remove dotted alias attributes from a model.
     *
     * The DataDefinition system selects nested JSON paths using aliases like:
     * - ecommerce_settings.shopify.enabled
     *
     * Spatie's activitylog interprets dotted attribute names as relations, which breaks
     * when the first segment (e.g. ecommerce_settings) is actually a JSON-cast array.
     *
     * We strip these *query-time aliases* before saving so model events (including
     * activity logging) cannot accidentally traverse them.
     */
    private function stripDottedAttributes(Model $model): void
    {
        // Only remove dotted keys from attributes - they shouldn't affect save
        foreach (array_keys($model->getAttributes()) as $key) {
            if (str_contains($key, '.')) {
                $model->offsetUnset($key);
            }
        }
    }

    /**
     * Check if a model has any JSON-cast attributes currently loaded.
     *
     * When JSON-cast attributes (like ecommerce_settings) are loaded and contain
     * nested arrays, Spatie's activitylog tries to traverse them as relationships
     * (e.g., it sees ecommerce_settings with shopify key and tries to access
     * ecommerce_settings.shopify as a relation). This causes crashes.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  The model to check
     * @return bool True if any JSON-cast attributes are loaded
     */
    private function hasJsonAttributesLoaded($model): bool
    {
        $casts = $model->getCasts();
        $attributes = $model->getAttributes();
        $jsonCastTypes = ['array', 'json', 'object', 'collection'];

        foreach ($casts as $attribute => $castType) {
            $baseCastType = explode(':', $castType)[0];

            if (in_array($baseCastType, $jsonCastTypes, true)) {
                // Check if this JSON-cast attribute is loaded in the model
                if (array_key_exists($attribute, $attributes)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Ensure unique_identifier is generated for models using HasIdentifier trait.
     *
     * When saveQuietly() is used, model events are skipped, meaning the HasIdentifier
     * trait's creating event never fires. This method manually generates the identifier
     * for new models (those without an ID yet) that use the trait.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  The model to check and update
     */
    private function ensureUniqueIdentifierGenerated($model): void
    {
        // Only process if model uses HasIdentifier trait and unique_identifier is empty
        if (! in_array(\App\Traits\HasIdentifier::class, class_uses_recursive($model))) {
            return;
        }

        if (! empty($model->unique_identifier)) {
            return;
        }

        // Generate a unique identifier in the same format as HasIdentifier trait
        $model->unique_identifier = $this->generateUniqueIdentifierFor($model);
    }

    /**
     * Generate a unique identifier for the given model.
     *
     * Produces a formatted numeric string (e.g., "123-456-789-012").
     * Recursively checks for uniqueness within the model's table.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  The model to generate identifier for
     * @return string The unique identifier
     */
    private function generateUniqueIdentifierFor($model): string
    {
        $identifier = implode('-', str_split($this->generateRandomNumericString(12), 3));

        // Check if this identifier already exists for this model type
        if ($model->newQuery()->where('unique_identifier', $identifier)->exists()) {
            return $this->generateUniqueIdentifierFor($model);
        }

        return $identifier;
    }

    /**
     * Generate a random numeric string of specified length.
     *
     * @param  int  $length  The length of the string to generate
     * @return string The random numeric string
     */
    private function generateRandomNumericString(int $length): string
    {
        $str = '';
        $charset = '1234567890';
        $count = strlen($charset);

        while ($length--) {
            $str .= $charset[random_int(0, $count - 1)];
        }

        return $str;
    }

    /**
     * Determine if a model currently has any dotted attributes loaded.
     *
     * These attributes are typically query-time aliases from JSON_EXTRACT selections
     * (e.g. "ecommerce_settings.shopify.enabled") and should never be present during
     * model events (Spatie activitylog interprets them as relation traversal).
     */
    private function hasDottedAttributes(Model $model): bool
    {
        foreach (array_keys($model->getAttributes()) as $attribute) {
            if (str_contains($attribute, '.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Transform dotted keys in request data to nested arrays for validation.
     *
     * This ensures Laravel's validator can properly validate fields with dotted names
     * like 'ecommerce_settings.shopify.enabled'.
     *
     * @param  Request  $request  The HTTP request to transform
     * @param  array  $columns  The column configurations
     */
    private function transformDottedKeysForValidation(Request $request, array $columns): void
    {
        $allInput = $request->all();
        $transformed = [];

        foreach ($columns as $columnName => $column) {
            // Skip if column name doesn't contain dots
            if (! str_contains($columnName, '.')) {
                continue;
            }

            // Check for the value using multiple key formats
            $value = null;
            $found = false;

            // Check exact dotted key first
            if (array_key_exists($columnName, $allInput)) {
                $value = $allInput[$columnName];
                $found = true;
            }

            // Check underscore-converted key (form components may convert dots to underscores)
            if (! $found) {
                $underscoreKey = str_replace('.', '_', $columnName);
                if (array_key_exists($underscoreKey, $allInput)) {
                    $value = $allInput[$underscoreKey];
                    $found = true;
                }
            }

            // If found, transform to nested array structure for validation
            if ($found) {
                $segments = explode('.', $columnName);

                // Build nested array
                $nested = &$transformed;
                foreach ($segments as $i => $segment) {
                    if ($i === count($segments) - 1) {
                        $nested[$segment] = $value;
                    } else {
                        if (! isset($nested[$segment]) || ! is_array($nested[$segment])) {
                            $nested[$segment] = [];
                        }
                        $nested = &$nested[$segment];
                    }
                }
            }
        }

        // Merge transformed nested arrays into the request
        if (! empty($transformed)) {
            $request->merge($transformed);
        }
    }

    /**
     * Get a value from the request, handling dotted field names correctly.
     *
     * When HTML forms have inputs with dotted names like 'ecommerce_settings.shopify.enabled',
     * PHP stores them as literal keys (not nested arrays). However, Laravel's $request->input()
     * interprets dots as nested array accessors. This method handles both cases.
     *
     * @param  Request  $request  The HTTP request
     * @param  string  $fieldName  The field name (may contain dots)
     * @return mixed The field value, or null if not found
     */
    private function getRequestValue(Request $request, string $fieldName): mixed
    {
        // First, try to get the value using Laravel's standard method
        // This works for nested array notation like 'settings[shopify][enabled]'
        $value = $request->input($fieldName);

        if ($value !== null) {
            return $value;
        }

        $allInput = $request->all();

        // If not found and the field name contains dots, check multiple formats
        if (str_contains($fieldName, '.')) {
            // Check if the exact dotted key exists in the request data
            // This handles HTML inputs with name="ecommerce_settings.shopify.enabled"
            if (array_key_exists($fieldName, $allInput)) {
                return $allInput[$fieldName];
            }

            // Check for underscore-converted field name
            // Some form components convert dots to underscores in HTML field names
            // e.g., 'ecommerce_settings.shopify.enabled' becomes 'ecommerce_settings_shopify_enabled'
            $underscoreKey = str_replace('.', '_', $fieldName);
            if (array_key_exists($underscoreKey, $allInput)) {
                return $allInput[$underscoreKey];
            }
        }

        return null;
    }

    /**
     * Find a data definition by its name.
     *
     * @param  string  $dataDefinitionName  The name of the data definition to find.
     * @return string|null Returns the data definition class name if found, otherwise null.
     */
    public function findDataDefinitionByName($dataDefinitionName)
    {
        // Obtain the user type from the route name (member, staff, partner, or admin)
        $classDir = explode('.', request()->route()->getName())[0];
        $classDir = ucfirst($classDir);

        // Get all the data definition models and search for a match
        // Use reflection to read the $name property WITHOUT instantiating the class
        // This prevents permission checks in constructors from being triggered
        foreach (glob(app_path().'/DataDefinitions/Models/'.$classDir.'/*.php') as $file) {
            $class = '\\App\\DataDefinitions\\Models\\'.$classDir.'\\'.basename($file, '.php');
            
            // Use reflection to get the default value of the $name property
            try {
                $reflection = new \ReflectionClass($class);
                $property = $reflection->getProperty('name');
                
                // Get the default value without instantiating
                $defaultProperties = $reflection->getDefaultProperties();
                $name = $defaultProperties['name'] ?? null;
                
                if ($dataDefinitionName === $name) {
                    return $class; // Return class name, not instance
                }
            } catch (\ReflectionException $e) {
                // If reflection fails, skip this class
                continue;
            }
        }

        return null;
    }

    /**
     * Delete one or more records.
     *
     * @param  int|array  $ids  ID or array with IDs of records to delete.
     * @param  string  $dataDefinitionName  The name of the data definition to use for deleting records.
     * @return array The result message containing the type and text for the deletion process.
     *
     * @throws \Exception If the data definition is not found.
     */
    public function deleteRecords($ids, string $dataDefinitionName): array
    {
        // Find data definition
        $dataDefinition = $this->findDataDefinitionByName($dataDefinitionName);

        // If the data definition is found, create a new instance, otherwise throw an exception
        if ($dataDefinition !== null) {
            $dataDefinition = new $dataDefinition;
        } else {
            throw new \Exception('Data definition "'.$dataDefinitionName.'" not found');
        }

        // Get settings for the data definition
        $settings = $dataDefinition->getSettings([]);

        // Obtain the user type from the route name (member, staff, partner, or admin)
        // and verify if the Data Definition is permitted for that user type
        $guard = explode('.', request()->route()->getName())[0];
        if ($settings['guard'] !== $guard) {
            abort(405, 'View not allowed for '.$guard.', '.$settings['guard'].' required');
        }

        // Make sure delete is allowed for data definition
        if (! $settings['delete']) {
            abort('Delete Not Allowed', 405);
        }

        // Ensure $ids is an array
        if (! is_array($ids)) {
            $ids = [$ids];
        }

        // Primary key
        $primaryKey = $dataDefinition->model->getKeyName();

        // Check if the model has an 'is_undeletable' column
        $hasUndeletableColumn = $dataDefinition->model->schemaHasColumn('is_undeletable');
        $oneOrMoreRecordsUndeletable = false;
        $recordsDeleted = 0;

        // Delete records
        foreach ($ids as $id) {
            // Only access to record(s) created by current user
            if ($settings['userMustOwnRecords']) {
                $user_id = auth($settings['guard'])->user()->id;
                $result = $dataDefinition->model->where('created_by', $user_id)->where($primaryKey, $id)->first();
            } else {
                $result = $dataDefinition->model->find($id);
            }

            // If the model has 'is_undeletable' column and the record is marked as undeletable, skip it
            if ($result === null || ($hasUndeletableColumn && $result->is_undeletable)) {
                $oneOrMoreRecordsUndeletable = true;
            } else {
                // Otherwise, delete the record
                $result->delete();
                $recordsDeleted++;
            }
        }

        // Set the appropriate result message based on the deletion results
        if ($oneOrMoreRecordsUndeletable && $recordsDeleted == 0) {
            $message = [
                'type' => 'danger',
                'size' => 'lg',
                'text' => trans('common.one_or_more_records_not_deleted'),
            ];
        } elseif ($oneOrMoreRecordsUndeletable && $recordsDeleted > 0) {
            $message = [
                'type' => 'warning',
                'size' => 'lg',
                'text' => trans('common.number_some_records_deleted', ['number' => $recordsDeleted]),
            ];
        } else {
            $message = [
                'type' => 'success',
                'size' => 'lg',
                'text' => trans('common.number_records_deleted', ['number' => $recordsDeleted]),
            ];
        }

        return $message;
    }

    /**
     * Sanitize input data in the request.
     *
     * This method iterates over each column in the provided array, and if the column
     * is not marked as 'allowHtml', it purifies the corresponding data in the request.
     *
     * @param  Request  $request  The HTTP request containing the data to be sanitized.
     * @param  array  $columns  An array of column configurations.
     */
    public function sanitizeInput(Request $request, array $columns): void
    {
        // Instantiate a new HTML Purifier
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path('framework/cache'));
        $config->set('HTML.Allowed', ''); // Do not allow any HTML tags
        $purifier = new HTMLPurifier($config);

        // Iterate over each column in the array
        foreach ($columns as $column) {
            // Check if the column allows HTML
            if (! $column['allowHtml']) {
                // Get the input data using our helper to handle dotted field names
                $inputData = $this->getRequestValue($request, $column['name']);

                if ($inputData === null) {
                    continue;
                }

                // Check if the input data is an array
                if (is_array($inputData)) {
                    // If it's an array, iterate over it and sanitize each item
                    foreach ($inputData as $key => $value) {
                        if (is_string($value)) {
                            $inputData[$key] = $purifier->purify($value);
                        }
                    }
                } elseif (is_string($inputData)) {
                    // If it's a string, sanitize it
                    $inputData = $purifier->purify($inputData);
                }

                // Merge the sanitized data back into the request
                $request->merge([$column['name'] => $inputData]);
            }
        }
    }

    /**
     * Insert a record using the specified data definition.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  array  $form  The form configuration.
     * @param  array  $settings  Additional settings.
     * @return array|\Illuminate\Validation\Validator The result message or a MessageBag instance in case of validation errors.
     *
     * @throws \Exception If the data definition is not found.
     */
    public function insertRecord(Request $request, array $form, array $settings): array|\Illuminate\Validation\Validator
    {
        // Obtain the user type from the route name (member, staff, partner, or admin)
        // and verify if the Data Definition is permitted for that user type
        $guard = explode('.', $request->route()->getName())[0];
        if ($settings['guard'] !== $guard) {
            abort(405, 'View not allowed for '.$guard.', '.$settings['guard'].' required');
        }

        // Sanitize input
        $this->sanitizeInput($request, $form['columns']);

        // Transform dotted keys to nested arrays for validation
        $this->transformDottedKeysForValidation($request, $form['columns']);

        // Prepare validation rules and custom messages for each column
        $vars = ['id' => null];
        [$rules, $customAttributeNames, $customMessages] = $this->prepareValidationRulesAndMessages($form['columns'], $request, $vars);

        // Validate the input data using the prepared rules and custom messages
        $validator = Validator::make($request->all(), $rules, $customMessages);

        // Set custom attribute names for the input fields
        $validator->setAttributeNames($customAttributeNames);

        // If validation fails, return the validation errors
        if ($validator->fails()) {
            return $validator;
        }

        // Process the input data for each column and update the record accordingly
        $postSaveCallback = $this->processInputData($request, $form, $settings);

        // Add created_by
        $prefix = trim(Route::getCurrentRoute()->getPrefix(), '/');
        $guard = ($prefix === '{locale}') ? 'member' : str_replace('{locale}/', '', $prefix);
        $form['data']->created_by = auth($guard)->user()->id;

        // Process relations pre-save model
        foreach ($form['relations'] as $relation) {
            if ($relation['type'] == 'belongsTo') {
                $relationValue = $request->get($relation['column']);
                // Use the relation method name, not the column name
                $relationMethod = $relation['relation'];

                // Only associate if value exists
                if (! empty($relationValue)) {
                    $form['data']->{$relationMethod}()->associate($relationValue);
                }
            }
        }

        DB::transaction(function () use ($form, $request, $settings, $postSaveCallback) {
            $this->stripDottedAttributes($form['data']);

            // Call the beforeInsert function if it exists in settings
            // This allows DataDefinitions to inject attributes before save
            // (e.g., polymorphic ownership fields that must be set before model events)
            if (isset($settings['beforeInsert']) && is_callable($settings['beforeInsert'])) {
                $settings['beforeInsert']($form['data']);
            }

            // Check if model has any JSON-cast columns loaded in attributes
            // Spatie's activity log crashes when it encounters array values in attributes
            // because it tries to traverse them as relationships (e.g., ecommerce_settings.shopify)
            $hasJsonAttributesLoaded = $this->hasJsonAttributesLoaded($form['data']);

            // Save the new record
            // Use saveQuietly() if model has JSON attributes loaded to bypass all model events
            // This prevents Spatie's activitylog from trying to process JSON columns as relations
            if ($hasJsonAttributesLoaded) {
                // Manually trigger HasIdentifier trait behavior since saveQuietly() skips events
                // This ensures unique_identifier is generated for models that require it
                $this->ensureUniqueIdentifierGenerated($form['data']);

                $form['data']->saveQuietly();
            } else {
                $form['data']->save();
            }

            // Execute post-save actions (e.g. media uploads)
            if ($postSaveCallback) {
                $postSaveCallback($form['data']);
            }

            // Process relations post-save model
            foreach ($form['relations'] as $relation) {
                if (in_array($relation['type'], ['belongsToMany'])) {
                    $relationIds = $request->get($relation['column']);

                    if (is_array($relationIds) ? (! empty($relationIds) && $relationIds[0] !== '') : $relationIds !== '') {
                        $form['data']->{$relation['column']}()->sync($relationIds);
                    }
                }
            }

            // Call the afterInsert function if it exists in settings
            if (isset($settings['afterInsert']) && is_callable($settings['afterInsert'])) {
                $settings['afterInsert']($form['data']);
            }
        });

        // Send user password email
        if ($request->send_user_password) {
            // If correct, the current model record is a user
            $user = $form['data'];
            if ($user->email && $request->password != '') {
                $password = $request->password;
                $user->notify(new Registration($user->email, $password, $settings['mailUserPasswordGuard']));
            }
        }

        // Set the result message
        $message = [
            'type' => 'success',
            'size' => 'lg',
            'text' => trans('common.record_inserted'),
        ];

        return $message;
    }

    /**
     * Update a record using the specified data definition.
     *
     * @param  int  $id  The ID of the record to update.
     * @param  Request  $request  The incoming HTTP request.
     * @param  array  $form  The form configuration.
     * @param  array  $settings  Additional settings.
     * @return array|\Illuminate\Validation\Validator The result message or a MessageBag instance in case of validation errors.
     *
     * @throws \Exception If the data definition is not found.
     */
    public function updateRecord(string $id, Request $request, array $form, array $settings): array|\Illuminate\Validation\Validator
    {
        // Obtain the user type from the route name (member, staff, partner, or admin)
        // and verify if the Data Definition is permitted for that user type
        $guard = explode('.', $request->route()->getName())[0];
        if ($settings['guard'] !== $guard) {
            abort(405, 'View not allowed for '.$guard.', '.$settings['guard'].' required');
        }

        // Determine if the model has an 'is_uneditable' column
        $hasUneditableColumn = $form['data']->schemaHasColumn('is_uneditable');

        // Check if the record is marked as uneditable
        $recordIsUneditable = $hasUneditableColumn && $form['data']->is_uneditable;

        if (! $recordIsUneditable) {
            // Sanitize input
            $this->sanitizeInput($request, $form['columns']);

            // Transform dotted keys to nested arrays for validation
            $this->transformDottedKeysForValidation($request, $form['columns']);

            // Prepare validation rules and custom messages for each column
            $vars = ['id' => $id];
            [$rules, $customAttributeNames, $customMessages] = $this->prepareValidationRulesAndMessages($form['columns'], $request, $vars);

            // Validate the input data using the prepared rules and custom messages
            $validator = Validator::make($request->all(), $rules, $customMessages);

            // Set custom attribute names for the input fields
            $validator->setAttributeNames($customAttributeNames);

            // Validate user password
            if ($settings['editRequiresPassword']) {
                $validator->after(function ($validator) use ($request, $guard) {
                    $password = $request->current_password_required_to_save_changes;

                    // Get the current user's password hash
                    $currentPasswordHash = auth($guard)->user()->password;

                    // Check if the provided password matches the stored password hash
                    if (! Hash::check($password, $currentPasswordHash)) {
                        // Add an error message to the password field
                        $validator->errors()->add('current_password_required_to_save_changes', trans('common.validation.current_password'));
                    }
                });
            }

            // Validate OTP verification token
            if ($settings['editRequiresOtp']) {
                $validator->after(function ($validator) use ($request, $guard) {
                    $otpToken = $request->otp_verification_token;
                    $sessionKey = "otp_verified_{$guard}_profile_update";

                    // Check if OTP was verified and token matches
                    $sessionToken = session($sessionKey);

                    if (! $otpToken || ! $sessionToken || ! hash_equals($sessionToken, $otpToken)) {
                        $validator->errors()->add('otp_verification_token', trans('otp.profile_otp_required'));
                    }
                });
            }

            // If validation fails, return the validation errors
            if ($validator->fails()) {
                return $validator;
            }

            // Process the input data for each column and update the record accordingly
            $postSaveCallback = $this->processInputData($request, $form, $settings);

            // Process relations pre-save model
            foreach ($form['relations'] as $relation) {
                if ($relation['type'] == 'belongsTo') {
                    $relationValue = $request->get($relation['column']);
                    // Use the relation method name, not the column name
                    $relationMethod = $relation['relation'];

                    // Associate if value exists, dissociate if empty
                    if (! empty($relationValue)) {
                        $form['data']->{$relationMethod}()->associate($relationValue);
                    } else {
                        $form['data']->{$relationMethod}()->dissociate();
                    }

                    // Ensure the foreign key attribute is properly set (not empty string)
                    // Get the actual foreign key name from the relationship
                    $foreignKeyName = $form['data']->{$relationMethod}()->getForeignKeyName();
                    if ($form['data']->getAttribute($foreignKeyName) === '') {
                        $form['data']->setAttribute($foreignKeyName, null);
                    }
                } else {
                    // For non-belongsTo relations, unset column to prevent save error
                    unset($form['data']->{$relation['column']});
                }
            }

            // Add updated_by if not set to false
            if ($settings['updatedBy'] !== false) {
                $form['data']->updated_by = auth($guard)->user()->id;
            }

            DB::transaction(function () use ($form, $request, $settings, $postSaveCallback) {
                $this->stripDottedAttributes($form['data']);

                // Call the beforeUpdate function if it exists in settings
                // This runs even when saveQuietly() is used, so model-level
                // guards (e.g., uniqueness checks) still fire for JSON-cast models
                if (isset($settings['beforeUpdate']) && is_callable($settings['beforeUpdate'])) {
                    $settings['beforeUpdate']($form['data']);
                }

                // Check if model has any JSON-cast columns loaded in attributes
                // Spatie's activity log crashes when it encounters array values in attributes
                // because it tries to traverse them as relationships (e.g., ecommerce_settings.shopify)
                $hasJsonAttributesLoaded = $this->hasJsonAttributesLoaded($form['data']);

                // Save the updated record
                // Use saveQuietly() if model has JSON attributes loaded to bypass all model events
                // This prevents Spatie's activitylog from trying to process JSON columns as relations
                if ($hasJsonAttributesLoaded) {
                    $form['data']->saveQuietly();
                } else {
                    $form['data']->save();
                }

                // Execute post-save actions (e.g. media uploads)
                if ($postSaveCallback) {
                    $postSaveCallback($form['data']);
                }

                // Process relations post-save model
                foreach ($form['relations'] as $relation) {
                    // Save relation
                    if ($relation['type'] == 'belongsToMany') {
                        $relationIds = $request->get($relation['column']);

                        // If relationIds is empty, then sync with empty array to remove all existing relations
                        if (empty($relationIds)) {
                            $relationIds = [];
                        }

                        $form['data']->{$relation['column']}()->sync($relationIds);
                    }
                }

                // Call the afterUpdate function if it exists in settings
                // This runs even when saveQuietly() is used, so model-level
                // side effects (e.g., tier recalculation) still fire for JSON-cast models
                if (isset($settings['afterUpdate']) && is_callable($settings['afterUpdate'])) {
                    $settings['afterUpdate']($form['data']);
                }
            });

            // Send user password email
            if ($request->send_user_password) {
                // If correct, the current model record is a user
                $user = $form['data'];
                if ($user->email && $request->password != '') {
                    $password = $request->password;
                    $user->notify(new Registration($user->email, $password, $settings['mailUserPasswordGuard']));
                }
            }
        }

        // Set the result message based on the record's editability status
        $message = $recordIsUneditable ? [
            'type' => 'danger',
            'size' => 'lg',
            'text' => trans('common.record_is_not_editable'),
        ] : [
            'type' => 'success',
            'size' => 'lg',
            'text' => trans('common.record_updated'),
        ];

        return $message;
    }

    /**
     * Prepare validation rules and custom messages for each column.
     *
     * For translatable fields with 'required' validation:
     * - Only the default locale (config('app.locale')) is required
     * - Other locales are optional and will use the default locale's value as fallback
     *
     * @param  array  $columns  The form configuration columns.
     * @param  Request  $request  The incoming HTTP request.
     * @param  array  $vars  Potential variables used in validation rules.
     * @return array An array containing the prepared validation rules and custom messages.
     */
    private function prepareValidationRulesAndMessages(array $columns, Request $request, array $vars): array
    {
        $rules = [];
        $customAttributeNames = [];
        $customMessages = [];

        // Get the default locale for determining which translatable fields are required
        $defaultLocale = config('app.locale');

        // Iterate through each column in the form configuration
        foreach ($columns as $columnName => $column) {
            if ($column['translatable']) {
                // Ensure the translatable field is an array before iterating
                $translatableValues = $request->{$columnName};
                if (is_array($translatableValues)) {
                    foreach ($translatableValues as $locale => $value) {
                        $rules[$columnName.'.'.$locale] = [];
                        $customAttributeNames[$columnName.'.'.$locale] = $columnName;
                    }
                } else {
                    // If not an array, treat it as a single value
                    $rules[$columnName] = [];
                    $customAttributeNames[$columnName] = $column['text'];
                }
            } else {
                $rules[$columnName] = [];
                $customAttributeNames[$columnName] = $column['text'];
            }

            // Process each validation rule for the current column
            foreach ($column['validate'] as $validate) {
                // Replace the ':id' placeholder with the actual ID if found
                // For inserts, $vars['id'] is null, so we default to empty string
                if (strpos($validate, ':id') !== false) {
                    $validate = str_replace(':id', $vars['id'] ?? '', $validate);
                }
                // Replace the ':option_keys' placeholder with the options array keys if found
                if (strpos($validate, ':option_keys') !== false && is_array($column['options'])) {
                    $option_keys = implode(',', array_keys($column['options']));
                    $validate = str_replace(':option_keys', $option_keys, $validate);
                }

                // Add the updated validation rule to the rules array
                if ($column['translatable']) {
                    $translatableValues = $request->{$columnName};
                    if (is_array($translatableValues)) {
                        foreach ($translatableValues as $locale => $value) {
                            // For translatable fields, only apply 'required' to the default locale
                            // Other locales get 'nullable' instead of 'required'
                            $ruleToApply = $validate;
                            if ($validate === 'required' && $locale !== $defaultLocale) {
                                $ruleToApply = 'nullable';
                            }
                            $rules[$columnName.'.'.$locale][] = $ruleToApply;
                        }
                    } else {
                        // If not an array, treat it as a single value
                        $rules[$columnName][] = $validate;
                    }
                } else {
                    $rules[$columnName][] = $validate;
                }

                // Prepare custom messages for image-related validation rules
                if (in_array($column['type'], ['image', 'avatar'])) {
                    if (strpos($validate, 'image') !== false) {
                        $customMessages["{$columnName}.image"] = trans('common.validation.image');
                    }
                    if (preg_match('/mimes:(.+)/', $validate, $matches)) {
                        $customMessages["{$columnName}.mimes"] = trans('common.validation.image_type', ['types' => $matches[1]]);
                    }
                    if (preg_match('/max:(\d+)/', $validate, $matches)) {
                        $customMessages["{$columnName}.max"] = trans('common.validation.image_size');
                    }
                    if (strpos($validate, 'dimensions') !== false) {
                        preg_match('/max_width=(\d+)/', $validate, $maxWidthMatches);
                        preg_match('/max_height=(\d+)/', $validate, $maxHeightMatches);
                        preg_match('/min_width=(\d+)/', $validate, $minWidthMatches);
                        preg_match('/min_height=(\d+)/', $validate, $minHeightMatches);
                        $maxWidth = $maxWidthMatches[1] ?? null;
                        $maxHeight = $maxHeightMatches[1] ?? null;
                        $minWidth = $minWidthMatches[1] ?? null;
                        $minHeight = $minHeightMatches[1] ?? null;

                        $customMessages["{$columnName}.dimensions"] = trans('common.validation.image_dimensions', ['maxWidth' => $maxWidth, 'maxHeight' => $maxHeight, 'minWidth' => $minWidth, 'minHeight' => $minHeight]);
                    }
                }
            }
        }

        return [$rules, $customAttributeNames, $customMessages];
    }

    /**
     * Process the input data for each column and update the record accordingly.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  array  $form  The form configuration.
     * @param  array  $settings  Additional settings.
     * @return callable|null Closure to execute after save, or null.
     */
    private function processInputData(Request $request, array &$form, array $settings): ?callable
    {
        // Reset pending JSON updates at the start
        $this->pendingJsonUpdates = [];
        $postSaveActions = [];

        // Get list of belongsTo relation column names to skip
        $belongsToColumns = [];
        foreach ($form['relations'] as $relation) {
            if ($relation['type'] == 'belongsTo') {
                $belongsToColumns[] = $relation['column'];
            }
        }

        foreach ($form['columns'] as $columnName => $column) {
            // Skip belongsTo relation columns - they will be handled separately
            if (in_array($columnName, $belongsToColumns)) {
                continue;
            }

            // Get the input value from the request
            // For dotted field names (nested JSON paths), we need to check multiple sources
            // because Laravel's dot notation interpretation may differ from the actual form field name
            $columnInput = $this->getRequestValue($request, $columnName);

            // Apply default_when_null if input is null/empty and the parameter is defined
            // This ensures database constraints are satisfied for hidden fields
            if (($columnInput === null || $columnInput === '') && isset($column['default_when_null'])) {
                $columnInput = $column['default_when_null'];
            }

            // Handle email address format
            if ($column['format'] == 'email') {
                if ($columnInput != '') {
                    $columnInput = strtolower($columnInput);
                }
            }

            // Handle datetime type format
            if (in_array($column['format'], ['datetime-local', 'datetime'])) {
                if ($columnInput != '') {
                    $carbonDate = Carbon::parse($columnInput, auth($settings['guard'])->user()->time_zone);
                    $carbonDate->setTimezone('UTC');
                    $columnInput = $carbonDate->format('Y-m-d H:i:s');
                }
            }

            // Handle minor units conversion for currency fields stored as cents/minor units
            // Example: minorUnits=100 means 10.00 entered → 1000 stored
            // Supports: 100 (most currencies), 1 (JPY/KRW), 1000 (BHD/KWD/OMR)
            if (isset($column['minorUnits']) && $column['minorUnits'] > 1 && is_numeric($columnInput) && $columnInput !== '') {
                $columnInput = (int) round((float) $columnInput * $column['minorUnits']);
            }

            // Check if this is a nested JSON path for special type handling
            $jsonPathInfo = $column['json_path_info'] ?? null;
            $isNestedJsonPath = $jsonPathInfo && ($jsonPathInfo['is_json_path'] ?? false);

            // Handle number type column
            if ($column['type'] == 'number') {
                // Ensure numeric conversion if value is present
                $numValue = ($columnInput === null || $columnInput === '') ? null : $columnInput + 0; // +0 automatically casts to int or float

                if ($isNestedJsonPath) {
                    // Nested JSON path - queue for batch update
                    $jsonColumn = $jsonPathInfo['json_column'];
                    $jsonPath = $jsonPathInfo['json_path'];
                    
                     // Apply default value if input is null/empty and default is defined
                    $valueToStore = $numValue;
                    if (($valueToStore === null || $valueToStore === '') && isset($column['default']) && $column['default'] !== null) {
                        $valueToStore = $column['default'];
                    }

                    if (! isset($this->pendingJsonUpdates[$jsonColumn])) {
                        $this->pendingJsonUpdates[$jsonColumn] = [];
                    }
                    $this->pendingJsonUpdates[$jsonColumn][$jsonPath] = $valueToStore;
                } elseif (! str_contains($columnName, '.')) {
                    // Standard column (no dots) - set directly
                    $form['data']->{$columnName} = $numValue;
                }
            }
            // Handle string type column (and icon-picker which stores string values)
            elseif ($column['type'] == 'string' || $column['type'] == 'icon-picker') {
                if ($column['translatable']) {
                    // Translatable fields - set directly
                    $form['data']->{$columnName} = $columnInput;
                } elseif ($isNestedJsonPath) {
                    // Nested JSON path - queue for batch update
                    $jsonColumn = $jsonPathInfo['json_column'];
                    $jsonPath = $jsonPathInfo['json_path'];

                    // Apply default value if input is null/empty and default is defined
                    $valueToStore = $columnInput;
                    if (($valueToStore === null || $valueToStore === '') && isset($column['default']) && $column['default'] !== null) {
                        $valueToStore = $column['default'];
                    }

                    if (! isset($this->pendingJsonUpdates[$jsonColumn])) {
                        $this->pendingJsonUpdates[$jsonColumn] = [];
                    }
                    $this->pendingJsonUpdates[$jsonColumn][$jsonPath] = $valueToStore;
                } elseif (! str_contains($columnName, '.')) {
                    // Standard column (no dots) - set directly
                    $form['data']->{$columnName} = $columnInput;
                }
                // Skip dotted columns that couldn't be resolved as JSON paths
            }
            // Handle password type column
            elseif ($column['type'] == 'password') {
                if ($columnInput != '') {
                    $form['data']->{$columnName} = bcrypt($columnInput);
                }
            }
            // Handle boolean type column
            elseif ($column['type'] == 'boolean') {
                // Ensure proper boolean conversion
                // Checkboxes are often omitted from the request if unchecked
                // So we check if it exists and is truthy
                $boolValue = filter_var($columnInput, FILTER_VALIDATE_BOOLEAN);

                if ($isNestedJsonPath) {
                    // Nested JSON path - queue for batch update
                    $jsonColumn = $jsonPathInfo['json_column'];
                    $jsonPath = $jsonPathInfo['json_path'];

                    if (! isset($this->pendingJsonUpdates[$jsonColumn])) {
                        $this->pendingJsonUpdates[$jsonColumn] = [];
                    }
                    $this->pendingJsonUpdates[$jsonColumn][$jsonPath] = $boolValue;
                } elseif (! str_contains($columnName, '.')) {
                    // Standard column (no dots) - set directly
                    $form['data']->{$columnName} = $boolValue;
                }
            }
            // Handle image and avatar type columns
            elseif ($column['type'] == 'image' || $column['type'] == 'avatar') {
                $deleteImage = $request->get($columnName.'_deleted');
                $defaultImage = $request->get($columnName.'_default');

                // Closure to handle media operations after model save
                $postSaveActions[] = function ($model) use ($columnName, $deleteImage, $defaultImage, $request, $form) {
                    // Delete the existing image if requested
                    if ($deleteImage) {
                        $model->clearMediaCollection($columnName);
                    }

                    // Upload the new image if a file is present in the request
                    if ($request->hasFile($columnName)) {
                        $model->addMediaFromRequest($columnName)->toMediaCollection($columnName);
                    } elseif (! empty($defaultImage) && $form['view'] == 'insert') {
                        // If $defaultImage is not empty and the form view is 'insert'
                        // Convert the URL of $defaultImage to a local path using public_path()
                        $defaultImageLocal = public_path(parse_url($defaultImage, PHP_URL_PATH));

                        if (file_exists($defaultImageLocal)) {
                            // Make a copy of the original file
                            $copyOfDefaultImageLocal = $defaultImageLocal.'_copy';
                            copy($defaultImageLocal, $copyOfDefaultImageLocal);

                            // Add the copy of the local file to the media collection specified by $columnName
                            $model->addMedia($copyOfDefaultImageLocal)->toMediaCollection($columnName);
                        }
                    }
                };
            }
            // Handle default
            else {
                if ($column['exists_in_database']) {
                    // Check if this is a nested JSON path (from column config or by detecting dots in name)
                    $jsonPathInfo = $column['json_path_info'] ?? null;
                    $isJsonPath = $jsonPathInfo && ($jsonPathInfo['is_json_path'] ?? false);

                    // Fallback: if column name contains dots and we have a model, try to detect JSON path
                    if (! $isJsonPath && str_contains($columnName, '.') && isset($form['data'])) {
                        $segments = explode('.', $columnName);
                        $potentialJsonColumn = $segments[0];

                        // Check if the first segment is a JSON-cast column
                        $casts = $form['data']->getCasts();
                        $castType = $casts[$potentialJsonColumn] ?? null;

                        if (in_array($castType, ['array', 'json', 'object', 'collection'], true)) {
                            $isJsonPath = true;
                            $jsonPathInfo = [
                                'is_json_path' => true,
                                'json_column' => $potentialJsonColumn,
                                'json_path' => implode('.', array_slice($segments, 1)),
                            ];
                        }
                    }

                    if ($isJsonPath && $jsonPathInfo) {
                        // Queue this value for batch update to the JSON column
                        $jsonColumn = $jsonPathInfo['json_column'];
                        $jsonPath = $jsonPathInfo['json_path'];

                        // Apply default value if input is null/empty and default is defined
                        $valueToStore = $columnInput;
                        if (($valueToStore === null || $valueToStore === '') && isset($column['default']) && $column['default'] !== null) {
                            $valueToStore = $column['default'];
                        }
                        // Convert null to empty string for JSON storage if not default
                        if ($valueToStore === null) {
                            $valueToStore = '';
                        }

                        if (! isset($this->pendingJsonUpdates[$jsonColumn])) {
                            $this->pendingJsonUpdates[$jsonColumn] = [];
                        }

                        $this->pendingJsonUpdates[$jsonColumn][$jsonPath] = $valueToStore;
                    } elseif (! str_contains($columnName, '.')) {
                        // Standard column (no dots) - set directly
                        $form['data']->{$columnName} = $columnInput;
                    }
                    // Skip dotted columns that couldn't be resolved - don't set them directly
                }
            }

            // Handle legacy 'json' property for backwards compatibility
            // This is for fields that use 'json' => 'column_name' syntax
            if ($column['json'] && ! ($column['json_path_info']['is_json_path'] ?? false)) {
                // Safely get the JSON column value, defaulting to empty array if not retrieved
                $jsonColumn = $column['json'];
                $json = array_key_exists($jsonColumn, $form['data']->getAttributes())
                    ? ($form['data']->{$jsonColumn} ?? [])
                    : [];
                $json[$columnName] = $form['data']->{$columnName};
                    
                // Fix for null values being stored as "null" string or null
                if ($json[$columnName] === null) {
                     $json[$columnName] = '';
                }

                $form['data']->{$jsonColumn} = $json;
                unset($form['data']->{$columnName});
            }
        }

        // Apply all pending JSON updates (from nested JSON paths)
        $this->applyPendingJsonUpdates($form['data']);
        
        return function($model) use ($postSaveActions) {
            foreach ($postSaveActions as $action) {
                $action($model);
            }
        };
    }
}
