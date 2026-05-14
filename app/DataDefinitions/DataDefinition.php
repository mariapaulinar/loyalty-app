<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\DataDefinitions;

use App\Models\Admin;
use App\Models\Affiliate;
use App\Models\Member;
use App\Models\Partner;
use App\Models\Staff;
use App\View\Components\Ui\Icon;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravolt\Avatar\Facade as Avatar;

class DataDefinition
{
    /**
     * Check if a field name represents a nested JSON path.
     *
     * A field is considered a JSON path if:
     * 1. It contains dots (e.g., 'ecommerce_settings.shopify.enabled')
     * 2. The first segment corresponds to a JSON column on the model
     *
     * @param  string  $fieldName  The field name to check
     * @param  Model  $model  The model instance to check column types
     * @return array{is_json_path: bool, json_column: string|null, json_path: string|null}
     */
    private function parseJsonPath(string $fieldName, Model $model): array
    {
        // Early return if no dots - not a JSON path
        if (! Str::contains($fieldName, '.')) {
            return [
                'is_json_path' => false,
                'json_column' => null,
                'json_path' => null,
            ];
        }

        // Split by dots
        $segments = explode('.', $fieldName);

        // First segment is the potential JSON column name
        $potentialJsonColumn = $segments[0];

        // Check if this column exists in the database and is cast as array/json
        if (! $model->schemaHasColumn($potentialJsonColumn)) {
            return [
                'is_json_path' => false,
                'json_column' => null,
                'json_path' => null,
            ];
        }

        // Check if the model casts this column as array or json
        $casts = $model->getCasts();
        $castType = $casts[$potentialJsonColumn] ?? null;

        $isJsonCast = in_array($castType, ['array', 'json', 'object', 'collection'], true);

        if (! $isJsonCast) {
            return [
                'is_json_path' => false,
                'json_column' => null,
                'json_path' => null,
            ];
        }

        // Build the JSON path from remaining segments
        $jsonPath = implode('.', array_slice($segments, 1));

        return [
            'is_json_path' => true,
            'json_column' => $potentialJsonColumn,
            'json_path' => $jsonPath,
        ];
    }

    /**
     * Build a SQL expression to extract a value from a JSON column.
     *
     * @param  string  $jsonColumn  The JSON column name
     * @param  string  $jsonPath  The path within the JSON (e.g., 'shopify.enabled')
     * @param  string  $alias  The alias for the result
     */
    private function buildJsonExtractExpression(string $jsonColumn, string $jsonPath, string $alias): \Illuminate\Database\Query\Expression
    {
        // Convert dot notation to JSON path notation (e.g., 'shopify.enabled' -> '$.shopify.enabled')
        $jsonPathExpression = '$.'.str_replace('.', '.', $jsonPath);

        if (DB::getDriverName() === 'sqlite') {
            $sql = "json_extract({$jsonColumn}, '{$jsonPathExpression}')";
        } else {
            // MySQL - use JSON_UNQUOTE to get the actual value, not quoted string
            $sql = "JSON_UNQUOTE(JSON_EXTRACT({$jsonColumn}, '{$jsonPathExpression}'))";
        }

        // Escape the alias properly (use backticks for MySQL, quotes for others)
        $quotedAlias = DB::getDriverName() === 'mysql'
            ? "`{$alias}`"
            : "\"{$alias}\"";

        return DB::raw("{$sql} as {$quotedAlias}");
    }

    /**
     * Retrieve data.
     *
     * @param  string  $dataDefinitionName  The data definition name
     * @param  string  $dataDefinitionView  The data definition view
     * @param  array  $options  The options array
     * @param  Model  $model  The model instance
     * @param  array  $settings  The settings array
     * @param  array  $fields  The fields array
     * @return array The columns and data
     */
    public function getData(string $dataDefinitionName, string $dataDefinitionView, array $options, Model $model, array $settings, array $fields): array
    {
        // Get settings
        $settings = $this->getSettings($settings);

        // Get options
        $id = $options['id'] ?? null;

        // Check if there is a filter for a certain user role
        if ($settings['userFilterRole'] && isset($settings['userFilterRole'][auth($settings['guard'])->user()->role])) {
            // Get filtered data
            $filter = $settings['userFilterRole'][auth($settings['guard'])->user()->role];
            $query = $filter($model);
        } else {
            // Get data
            $query = $model->query();
        }

        // General query filter
        if ($settings['queryFilter']) {
            // Apply $query queryFilter
            $query = $settings['queryFilter']($query);
        }

        // Primary key
        $primaryKey = $model->getKeyName();
        $query->addSelect("{$primaryKey} as id");

        if ($model->schemaHasColumn('is_undeletable')) {
            $query->addSelect('is_undeletable');
        }
        if ($model->schemaHasColumn('is_uneditable')) {
            $query->addSelect('is_uneditable');
        }

        // Only access to record(s) created by current user
        if ($settings['userMustOwnRecords']) {
            $user_id = auth($settings['guard'])->user()->id;
            $query->where('created_by', $user_id);
        }

        // Add columns to query
        [$columns, $appends, $relations, $filters, $tabs] = $this->addColumnsToQuery($fields, $dataDefinitionView, $model, $query, $settings);

        // The "insert" view does not need data, so we return a new model instance
        if ($dataDefinitionView === 'insert') {
            $data = new $model;
        } elseif ($dataDefinitionView === 'view') {
            $eloquentObject = $query->where($primaryKey, $id);
            $data = $eloquentObject->first();

            // Process the record that will be shown in the view
            // For view mode, skip HTML formatting to avoid model casts converting HTML to wrong values
            // The model's media library magic will provide image URLs directly
            $processedData = $this->processRows($eloquentObject->get(), $columns, $dataDefinitionName, $settings, false, true)[0] ?? [];

            // Assign processed values to the model (except images which use model's media magic)
            foreach ($processedData as $columnName => $value) {
                $data->{$columnName} = $value;
            }
        } elseif ($dataDefinitionView === 'export') {
            // Apply search (export should match list filters)
            $searchTerm = trim((string) request()->get('search', ''));
            if ($searchTerm !== '') {
                $this->applySearchAllFields($query, $model, $fields, $searchTerm);
            }

            // Apply filter(s) (export should match list filters)
            if (request()->input('filter')) {
                foreach (request()->input('filter') as $columnName => $id) {
                    $query->where($columnName, $id);
                }
            }

            // Apply ordering (export should match list ordering)
            if ($settings['orderRelation']) {
                $query->selectSub($settings['orderRelation'], 'sub_order')
                    ->orderBy('sub_order', $settings['orderDirection']);
            } else {
                $query->orderBy($settings['orderByColumn'], $settings['orderDirection']);
            }

            // General query filter (defensive - some definitions rely on it being re-applied)
            if ($settings['queryFilter']) {
                $query = $settings['queryFilter']($query);
            }

            $rows = $query->get();

            // Export returns processed rows (arrays) with plain data (no HTML formatting).
            $processedRows = $this->processRows($rows, $columns, $dataDefinitionName, $settings, true);
            $data = collect($processedRows);
        } elseif ($id === null) {
            // Get search
            $searchTerm = trim((string) request()->get('search', ''));
            if ($searchTerm !== '') {
                $this->applySearchAllFields($query, $model, $fields, $searchTerm);
            }

            // Add filter(s)
            if (request()->input('filter')) {
                foreach (request()->input('filter') as $columnName => $id) {
                    $query->where($columnName, $id);
                }
            }

            // Pagination
            $currentPage = request()->input('page', 1);
            $itemsPerPage = $settings['itemsPerPage'];
            $recordsToRetrieve = $itemsPerPage * ($currentPage + 1);

            // Retrieve the records
            $allRecords = $query;

            if ($settings['orderRelation']) {
                $allRecords = $allRecords->selectSub($settings['orderRelation'], 'sub_order')
                    ->orderBy('sub_order', $settings['orderDirection']);
            } else {
                $allRecords = $allRecords->orderBy($settings['orderByColumn'], $settings['orderDirection']);
            }

            // General query filter
            if ($settings['queryFilter']) {
                $allRecords = $settings['queryFilter']($allRecords);
            }

            $totalRecords = $allRecords->count();

            $allRecords = $allRecords->take($recordsToRetrieve)->get();

            $currentPageRecords = $allRecords->slice(($currentPage - 1) * $itemsPerPage, $itemsPerPage);

            /*
            $resultsWithAppends = $currentPageRecords->map(function ($item) use ($appends) {
                foreach ($appends as $append) {
                    // Force the accessor to run and include the appended attribute in the toArray() results
                    $item->setAttribute($append, $item->{$append});
                }
                return $item;
            })->toArray();
            */

            $hasMorePages = $allRecords->count() > ($currentPage * $itemsPerPage);

            // Process the records that will be shown in the view
            $processedRows = $this->processRows($currentPageRecords, $columns, $dataDefinitionName, $settings);

            // Add processed records to paginator
            $data = new LengthAwarePaginator($processedRows, $totalRecords, $itemsPerPage, $currentPage, [
                'path' => route($settings['guard'].'.data.list', ['name' => $dataDefinitionName]),
                'hasMorePagesWhen' => $hasMorePages,
            ]);

            // Keep other query string parameters
            $data->appends(request()->except('page'));
        } else {
            $data = $query->where($primaryKey, $id)->first();

            // Process the record that will be shown in the form
            $processedData = $this->processFormatRows(collect([$data]), $columns, $dataDefinitionName, $settings)[0] ?? [];

            foreach ($processedData as $columnName => $value) {
                $data->{$columnName} = $value;
            }

            if (! Str::endsWith($dataDefinitionView, '.post') && $data !== null) {
                // Process relations
                foreach ($relations as $relation) {
                    // Use relation method name, not column name
                    $relationMethod = $relation['relation'] ?? $relation['column'];

                    if ($relation['type'] == 'belongsTo') {
                        // For belongsTo, just keep the foreign key value (single value, not array)
                        // The foreign key is already on the model, no need to query the relation
                        // This allows the select to show the current value
                    } else {
                        // For belongsToMany and other relations, get array of keys
                        $data->{$relation['column']} = $data->{$relationMethod}()->pluck($relation['key'])->toArray();
                    }
                }
            }
        }

        return [
            'columns' => $columns,
            'data' => $data,
            'relations' => $relations,
            'filters' => $filters,
            'tabs' => $tabs,
            'view' => $dataDefinitionView,
        ];
    }

    /**
     * Add columns to the query.
     *
     * @param  array  $fields  The fields to process
     * @param  string  $dataDefinitionView  The data definition view
     * @param  Model  $model  The model instance
     * @param  Builder  $query  The query builder instance
     * @param  array  $settings  The settings array
     * @return array The columns, appends and relations
     */
    private function addColumnsToQuery(array $fields, string $dataDefinitionView, Model $model, $query, array $settings): array
    {
        $columns = [];
        $appends = [];
        $relations = [];
        $filters = [];
        $tabs = [];


        // Flatten array in case of tabs
        // Supports two tab types:
        // 1. Standard tabs: contain 'fields' array that gets rendered as form inputs
        // 2. Custom tabs: contain 'type' => 'custom' and 'view' => 'blade.view.path'
        //    Custom tabs render a Blade view instead of form fields (useful for actions like delete account, data export)
        $fieldsFlatten = [];
        foreach ($fields as $columnName => $field) {
            // Check if this is a tab
            if (preg_match('/^tab\d/', $columnName)) {
                $tab = $columnName;
                
                // Store tab metadata (not just title) to support custom tab types
                $tabs[$tab] = [
                    'title' => $field['title'],
                    'type' => $field['type'] ?? 'standard',  // 'standard' or 'custom'
                    'view' => $field['view'] ?? null,        // Blade view path for custom tabs
                    'icon' => $field['icon'] ?? null,        // Optional icon for tab
                ];
                
                // Only flatten fields for standard tabs (custom tabs render a view instead)
                if (($field['type'] ?? 'standard') === 'standard' && isset($field['fields'])) {
                    foreach ($field['fields'] as $columnName => $field) {
                        $field['tab'] = $tab;
                        $fieldsFlatten[$columnName] = $field;
                    }
                }
            } else {
                $fieldsFlatten[$columnName] = $field;
            }
        }

        // Track JSON columns that have been added to the SELECT to avoid duplicates
        $selectedJsonColumns = [];

        foreach ($fieldsFlatten as $columnName => $field) {
            // Check if the column should be included in the query
            $actionsContainView = isset($field['actions']) && is_array($field['actions']) && in_array($dataDefinitionView, $field['actions']);
            $includeForSearch = $dataDefinitionView === 'list' && (($field['searchable'] ?? null) === true);
            $isSearchOnly = $includeForSearch && ! $actionsContainView;

            if ($actionsContainView || $includeForSearch || Str::contains($columnName, '::')) {
                $existsInDatabase = false;
                $skip = false;

                // Remove ::view from column name if present, otherwise skip this iteration
                if (Str::contains($columnName, '::')) {
                    if (Str::endsWith($columnName, '::'.$dataDefinitionView)) {
                        $columnName = Str::replaceLast('::'.$dataDefinitionView, '', $columnName);
                    } else {
                        $skip = true;
                    }
                }

                if (! $skip) {
                    // Check if this is a nested JSON path (e.g., 'ecommerce_settings.shopify.enabled')
                    $jsonPathInfo = $this->parseJsonPath($columnName, $model);

                    // Add column to select statement
                    if ($jsonPathInfo['is_json_path']) {
                        // This is a nested JSON field - use JSON_EXTRACT
                        $query->addSelect($this->buildJsonExtractExpression(
                            $jsonPathInfo['json_column'],
                            $jsonPathInfo['json_path'],
                            $columnName
                        ));

                        // ALSO select the actual JSON column so we can access the full JSON data
                        // Track which JSON columns we've already added to avoid duplicates
                        $jsonColumn = $jsonPathInfo['json_column'];
                        if (! isset($selectedJsonColumns[$jsonColumn])) {
                            $query->addSelect($jsonColumn);
                            $selectedJsonColumns[$jsonColumn] = true;
                        }

                        $existsInDatabase = true;
                    } elseif ($model->schemaHasColumn($columnName)) {
                        $query->addSelect($columnName);
                        $existsInDatabase = true;
                    } elseif (in_array($columnName, $model->getAppends())) {
                        $appends[] = $columnName;
                    } elseif (isset($field['sql'])) {
                        $query->addSelect(DB::raw($field['sql'].' as '.$columnName));
                    }

                    $format = ($field['type'] == 'string') ? 'text' : null;

                    // Prepare column data
                    $columnData = [
                        'exists_in_database' => $existsInDatabase,
                        'name' => $columnName,
                        'text' => $field['text::'.$dataDefinitionView] ?? $field['text'] ?? 'Column',
                        'default' => $field['default'] ?? null,
                        'default_when_null' => $field['default_when_null'] ?? null,
                        'allowHtml' => $field['allowHtml'] ?? false,
                        'placeholder' => $field['placeholder'] ?? null,
                        'json' => $field['json'] ?? null, // The value is a json column where the value of the data is stored
                        'json_path_info' => $jsonPathInfo, // Nested JSON path info for dotted field names
                        'prefix' => $field['prefix'] ?? null,
                        'suffix' => $field['suffix'] ?? null,
                        'type' => $field['type'] ?? 'string',
                        'translatable' => $field['translatable'] ?? false,
                        'generatePasswordButton' => $field['generatePasswordButton'] ?? false,
                        'mailUserPassword' => $field['mailUserPassword'] ?? false,
                        'mailUserPasswordChecked' => $field['mailUserPasswordChecked'] ?? false,
                        'min' => $field['min'] ?? null,
                        'max' => $field['max'] ?? null,
                        'step' => $field['step'] ?? null,
                        'minorUnits' => $field['minorUnits'] ?? null, // Currency minor units (100 for cents, 1000 for BHD)
                        'decimalPlaces' => $field['decimalPlaces'] ?? null, // Explicit decimal formatting (2 for currency)
                        'relation' => $field['relation'] ?? null,
                        'relationKey' => $field['relationKey'] ?? null,
                        'relationValue' => $field['relationValue'] ?? null,
                        'relationModel' => $field['relationModel'] ?? null,
                        'relationMustBeOwned' => $field['relationMustBeOwned'] ?? false,
                        'relationFilter' => $field['relationFilter'] ?? null,
                        'relationUserRoleFilter' => $field['relationUserRoleFilter'] ?? null,
                        'relationThrough' => $field['relationThrough'] ?? null,
                        'relationThroughPivot' => $field['relationThroughPivot'] ?? null,
                        'relationThroughValue' => $field['relationThroughValue'] ?? null,
                        'relationThroughOrderByColumn' => $field['relationThroughOrderByColumn'] ?? null,
                        'relationThroughOrderDirection' => $field['relationThroughOrderDirection'] ?? 'desc',
                        'relationThroughModel' => $field['relationThroughModel'] ?? null,
                        'relationThroughFilter' => $field['relationThroughFilter'] ?? null,
                        'relationThroughLink' => $field['relationThroughLink'] ?? null,
                        'query' => $field['query'] ?? null,
                        'textualAvatarBasedOnColumn' => $field['textualAvatarBasedOnColumn'] ?? null,
                        'titleColumn' => $field['titleColumn'] ?? null,
                        'format' => $field['format'] ?? $format,
                        'highlight' => $field['highlight'] ?? false,
                        'filter' => $field['filter'] ?? false,
                        // "Search-only" fields are included in the query for search, but not rendered in the table.
                        'hidden' => $isSearchOnly ? true : ($field['hidden'] ?? false),
                        'options' => $field['options'] ?? null,
                        // Search behavior:
                        // - true: always searchable
                        // - false: never searchable
                        // - null: auto (inferred in addSearchConditions)
                        // If omitted: searchable is "auto" at the DataDefinition level.
                        // For the global list search, we only EXCLUDE fields that explicitly set searchable=false.
                        'searchable' => $field['searchable'] ?? null,
                        'sortable' => $field['sortable'] ?? false,
                        'validate' => $field['validate'] ?? ['nullable'],
                        'guard' => $field['guard'] ?? null,
                        'help' => $field['help'] ?? null,
                        'url' => $field['url'] ?? null,
                        'classes::list' => $field['classes::list'] ?? null,
                        'classes::insert' => $field['classes::insert'] ?? null,
                        'container_start::insert' => $field['container_start::insert'] ?? null,
                        'container_end::insert' => $field['container_end::insert'] ?? false,
                        'classes::edit' => $field['classes::edit'] ?? null,
                        'container_start::edit' => $field['container_start::edit'] ?? null,
                        'container_end::edit' => $field['container_end::edit'] ?? false,
                        'classes::view' => $field['classes::view'] ?? null,
                        'container_start::view' => $field['container_start::view'] ?? null,
                        'container_end::view' => $field['container_end::view'] ?? false,
                        'accept' => $field['accept'] ?? null, // Image/file upload e.g "image/svg+xml, image/png, image/jpeg, image/gif"
                        'thumbnail' => $field['thumbnail'] ?? null, // Image conversion used for list
                        'conversion' => $field['conversion'] ?? null, // Image conversion used for view/edit
                        'tab' => $field['tab'] ?? null,
                        'ai' => $field['ai'] ?? null,
                        'variant' => $field['variant'] ?? null,
                        'icon' => $field['icon'] ?? null,
                    ];

                    // Process relations
                    if ($columnData['relationKey'] && $columnData['relationValue'] && $columnData['relationModel'] instanceof Model) {
                        // Set options based on related model and add column
                        if (in_array($columnData['type'], ['belongsToMany'])) {
                            // Check if there is a filter for a certain user role
                            if ($columnData['relationUserRoleFilter'] && isset($columnData['relationUserRoleFilter'][auth($settings['guard'])->user()->role])) {
                                // Get filtered options
                                $filter = $columnData['relationUserRoleFilter'][auth($settings['guard'])->user()->role];

                                if ($columnData['relationMustBeOwned']) {
                                    $columnData['options'] = $filter($columnData['relationModel']::where('created_by', auth($settings['guard'])->user()->id))
                                        ->pluck($columnData['relationValue'], $columnData['relationKey'])
                                        ->toArray();
                                } else {
                                    $columnData['options'] = $filter($columnData['relationModel'])->pluck($columnData['relationValue'], $columnData['relationKey'])->toArray();
                                }
                            } else {
                                // Get options
                                if ($columnData['relationMustBeOwned']) {
                                    $columnData['options'] = $columnData['relationModel']::where('created_by', auth($settings['guard'])->user()->id)
                                        ->pluck($columnData['relationValue'], $columnData['relationKey'])
                                        ->toArray();
                                } else {
                                    $columnData['options'] = $columnData['relationModel']->pluck($columnData['relationValue'], $columnData['relationKey'])->toArray();
                                }
                            }

                            // Add column
                            $query->with([
                                $columnData['relation'] => function ($q) use ($columnData) {
                                    $q->select([$columnData['relationValue']]);
                                },
                            ]);

                            $relations[] = [
                                'type' => $columnData['type'],
                                'column' => $columnName,
                                'key' => $columnData['relationKey'],
                                'value' => $columnData['relationValue'],
                            ];
                        }

                        // Set options list based on related model
                        if ($columnData['type'] == 'belongsTo') {
                            // Check if there is a filter for a certain user role
                            if ($columnData['relationUserRoleFilter'] && isset($columnData['relationUserRoleFilter'][auth($settings['guard'])->user()->role])) {
                                // Get filtered options
                                $filter = $columnData['relationUserRoleFilter'][auth($settings['guard'])->user()->role];

                                if ($columnData['relationMustBeOwned']) {
                                    $columnData['options'] = $filter($columnData['relationModel']::where('created_by', auth($settings['guard'])->user()->id))->pluck($columnData['relationValue'], $columnData['relationKey'])->toArray();
                                } else {
                                    $columnData['options'] = $filter($columnData['relationModel'])->pluck($columnData['relationValue'], $columnData['relationKey'])->toArray();
                                }
                            } else {
                                // Get options
                                if ($columnData['relationMustBeOwned']) {
                                    $columnData['options'] = $columnData['relationModel']::where('created_by', auth($settings['guard'])->user()->id)
                                        ->pluck($columnData['relationValue'], $columnData['relationKey'])
                                        ->toArray();
                                } else {
                                    $columnData['options'] = $columnData['relationModel']->pluck($columnData['relationValue'], $columnData['relationKey'])->toArray();
                                }
                            }

                            // Add belongsTo relation to relations array (needed for proper handling in DataService)
                            $relations[] = [
                                'type' => $columnData['type'],
                                'column' => $columnName,
                                'relation' => $columnData['relation'],
                                'key' => $columnData['relationKey'],
                                'value' => $columnData['relationValue'],
                            ];
                        }
                    }

                    // Process filters (for both relation-based and static options)
                    if ($columnData['filter'] && $columnData['options']) {
                        $filters[$columnName] = [
                            'text' => $columnData['text'],
                            'options' => $columnData['options'],
                        ];
                    }

                    // Process json arrays
                    if ($columnData['json']) {
                        if (DB::getDriverName() === 'sqlite') {
                            $query->addSelect(DB::raw("CASE WHEN json_valid({$columnData['json']}) THEN json_extract({$columnData['json']}, '$.$columnName') END as $columnName"));
                        } else {
                            $query->addSelect(DB::raw("CASE WHEN {$columnData['json']} IS NOT NULL THEN JSON_UNQUOTE(JSON_EXTRACT({$columnData['json']}, '$.$columnName')) END as $columnName"));
                        }

                        // Ensure the source JSON column is selected so model accessors work
                        $jsonCol = $columnData['json'];
                        if (! isset($selectedJsonColumns[$jsonCol])) {
                            $query->addSelect($jsonCol);
                            $selectedJsonColumns[$jsonCol] = true;
                        }
                    }

                    // Add column to the columns array
                    $columns[$columnName] = $columnData;
                }
            }
        }

        return [$columns, $appends, $relations, $filters, $tabs];
    }

    /**
     * Loop through rows and set default values and formatting for forms.
     *
     * @param  \Illuminate\Contracts\Pagination\CursorPaginator|\Illuminate\Support\Collection  $rows  The rows to process
     * @param  array  $columns  The columns array
     * @param  string  $dataDefinitionName  The data definition name
     * @param  array  $settings  The settings array
     * @return array The processed rows
     */
    private function processFormatRows($rows, array $columns, string $dataDefinitionName, array $settings): array
    {
        $processedRows = [];
        foreach ($rows as $row) {
            // Skip null rows (record not found or no access)
            if ($row === null) {
                continue;
            }

            $processedColumns['id'] = $row->id;
            foreach ($columns as $column) {
                $columnParsed = false;
                $value = $row->{$column['name']} ?? $column['default'];

                // Format datetime-local
                if ($column['format'] === 'datetime-local') {
                    $carbonDate = Carbon::parse($value, 'UTC');
                    $carbonDate->setTimezone(auth($settings['guard'])->user()->time_zone);
                    $value = $carbonDate->format('Y-m-d H:i:s');
                    $columnParsed = true;
                }

                if ($columnParsed) {
                    $processedColumns[$column['name']] = $value ?? '';
                }
            }
            $processedRows[] = $processedColumns;
        }

        return $processedRows;
    }

    /**
     * Loop through rows and set default values and formatting.
     *
     * @param  \Illuminate\Contracts\Pagination\CursorPaginator  $rows  The rows to process
     * @param  array  $columns  The columns array
     * @param  string  $dataDefinitionName  The data definition name
     * @param  array  $settings  The settings array
     * @param  bool  $forExport  Whether processing for export (skip HTML formatting)
     * @param  bool  $forView  Whether processing for view mode (skip image processing, number formatting to preserve model magic/casts)
     * @return array The processed rows
     */
    private function processRows($rows, array $columns, string $dataDefinitionName, array $settings, bool $forExport = false, bool $forView = false): array
    {
        $processedRows = [];
        foreach ($rows as $row) {
            $processedColumns['id'] = $row->id;
            foreach ($columns as $column) {
                // For display purposes (list, view, export), don't apply defaults.
                // Null values should display as "-" (or empty for export), not as the default value.
                // Defaults are only for form field pre-population (insert/edit).

                // Safely get attribute value - catches MissingAttributeException for computed/virtual
                // columns that may not be loaded (e.g., member_count, qr).
                try {
                    $value = $row->{$column['name']};
                } catch (\Illuminate\Database\Eloquent\MissingAttributeException) {
                    $value = null;
                }

                // Image - skip for view mode (model's media library magic provides URLs)
                if ($column['type'] === 'image' && $value !== null && ! $forView) {
                    $value = $this->processImageColumn($column, $row);
                }

                // Avatar - skip for view mode (model's media library magic provides URLs)
                if ($column['type'] === 'avatar' && ! $forView) {
                    $value = $this->processAvatarColumn($column, $row);
                }

                // Boolean - skip HTML formatting for view mode to avoid model casts converting HTML to 1/0
                if ($column['type'] === 'boolean' && ! $forView) {
                    $value = $this->processBooleanColumn($column, $value, $forExport);
                }

                // Number - skip HTML wrapper for export and view (to preserve model casts)
                // For view mode, also check raw value to preserve null (model casts convert null to 0)
                // Note: Numbers can be defined as type=number OR type=string with format=number
                $isNumberColumn = $column['type'] === 'number' || ($column['format'] ?? null) === 'number';
                if ($isNumberColumn) {
                    if ($forView) {
                        // Check raw value before model cast - preserve null to show dash instead of 0
                        $rawValue = $row->getRawOriginal($column['name']);
                        if ($rawValue === null) {
                            $value = null;
                        }
                    } elseif ($value !== null && ! $forExport) {
                        $value = '<span class="format-number">'.$value.'</span>';
                    }
                }

                // Date time
                if ($column['type'] === 'date_time' && $value !== null) {
                    $value = $this->processDateTimeColumn($column, $row, $value);
                }

                // Language / locale
                if ($column['type'] === 'locale') {
                    $value = $this->processLocaleColumn($column, $row, $value);
                }

                // Time zone
                if ($column['type'] === 'time_zone') {
                    $value = $this->processTimeZoneColumn($column, $row, $value);
                }

                // Currency
                if ($column['type'] === 'currency') {
                    $value = $this->processCurrencyColumn($column, $row, $value);
                }

                // User
                if (Str::startsWith($column['type'], 'user.')) {
                    $value = $this->processUserColumn($column, $row, $value, $settings);
                }

                // Select
                if ($column['type'] === 'select') {
                    $value = $this->processSelectColumn($column, $row, $value, $settings);
                }

                // Relations
                if (in_array($column['type'], ['belongsToMany', 'belongsTo', 'manyToMany'])) {
                    $value = $this->processRelationColumn($column, $row, $value, $settings);
                }

                // Impersonate as user, log in to account
                if ($column['type'] === 'impersonate') {
                    $value = $this->processImpersonateColumn($dataDefinitionName, $column, $row, $settings);
                }

                // QR code with link
                if ($column['type'] === 'qr') {
                    $value = $this->processQrColumn($dataDefinitionName, $column, $row, $settings);
                }

                // Query
                if ($column['type'] === 'query') {
                    $value = $this->processQueryColumn($column, $row, $settings);
                }

                // Formatting is done after getting values
                // Skip HTML wrapper for export and view (to preserve model casts)
                if ($column['format'] === 'number' && $value !== null && ! $forExport && ! $forView) {
                    $value = '<span class="format-number">'.$value.'</span>';
                }

                // Hide full email address
                if ($column['format'] === 'hideEmail') {
                    $value = hideEmailAddress($value ?? '');
                }

                // Format email
                if ($column['format'] === 'email') {
                    $value = strtolower($value ?? '');
                }

                // Format datetime-local
                if ($column['format'] === 'datetime-local') {
                    $carbonDate = Carbon::parse($value, 'UTC');
                    $carbonDate->setTimezone(auth($settings['guard'])->user()->time_zone);
                    $value = $carbonDate->format('Y-m-d H:i:s');
                }

                $processedColumns[$column['name']] = $value ?? '';
            }
            $processedRows[] = $processedColumns;
        }

        return $processedRows;
    }

    /**
     * Process the image column for the given row.
     *
     * @param  array  $column  The column configuration array
     * @param  object  $row  The row object containing the data for the image column
     * @return string The formatted image HTML string
     */
    private function processImageColumn(array $column, $row): string
    {
        if ($row->{$column['name']} !== null && $column['thumbnail'] !== null) {
            $value = $row->{$column['name'].'-'.$column['thumbnail']};
        }
        $value = '<img src="'.$value.'" class="mx-auto rounded-lg shadow-lg">';

        return $value;
    }

    /**
     * Process the avatar column for the given row.
     *
     * @param  array  $column  The column configuration array
     * @param  object  $row  The row object containing the data for the avatar column
     * @return string The formatted avatar HTML string
     */
    private function processAvatarColumn(array $column, $row): string
    {
        if (! $row->{$column['name']} && $column['textualAvatarBasedOnColumn'] !== null) {
            $value = Avatar::create($row->{$column['textualAvatarBasedOnColumn']})->toBase64();
        } elseif ($row->{$column['name']} !== null && $column['thumbnail'] !== null) {
            $value = $row->{$column['name'].'-'.$column['thumbnail']};
        }
        $value = '<img src="'.$value.'" class="w-10 h-10 mx-auto rounded-full">';

        return $value;
    }

    /**
     * Process and format the boolean column value based on the given configuration.
     *
     * @param  array  $column  The column configuration array
     * @param  mixed  $value  The boolean value to be formatted
     * @param  bool  $forExport  Whether processing for export (return plain text)
     * @return string The formatted boolean value as a string
     */
    private function processBooleanColumn(array $column, $value, bool $forExport = false): string
    {
        // For exports, always return plain text representation
        if ($forExport) {
            return $value ? 'true' : 'false';
        }

        // Determine effective boolean value
        // Use default if value is strictly null
        $effectiveValue = $value;
        if ($value === null && isset($column['default'])) {
            $effectiveValue = $column['default'];
        }
        
        // Convert to strict boolean
        $isTrue = filter_var($effectiveValue, FILTER_VALIDATE_BOOLEAN);

        if ($column['format'] == 'text') {
            $value = $isTrue
                ? trans('common.yes')
                : trans('common.no');
        } elseif ($column['format'] == 'icon') {
            $iconName = $isTrue ? 'check' : 'x';
            $colorClasses = $isTrue
                ? 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900/50'
                : 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-900/50';

            $iconComponent = new Icon($iconName, 'w-4 h-4');
            $iconHtml = $iconComponent->render()->render();

            $value = '<div class="inline-flex items-center justify-center w-6 h-6 rounded-full '.$colorClasses.'">'.$iconHtml.'</div>';
        } else {
            $value = ($value)
                ? '<div class="flex items-center"><div class="w-4 h-4 bg-green-500 rounded-full" style="background-color: rgb(14, 159, 110);"></div></div>'
                : '<div class="flex items-center"><div class="w-4 h-4 bg-red-500 rounded-full" style="background-color: rgb(240, 82, 82);"></div></div>';
        }

        return $value;
    }

    /**
     * Process and format the date time column value based on the given configuration.
     *
     * @param  array  $column  The column configuration array
     * @param  mixed  $row  The row containing the date time value
     * @param  mixed  $value  The boolean value to be formatted
     * @return string The formatted date time value as a string, or an empty string if the value is null
     */
    private function processDateTimeColumn(array $column, $row, $value): string
    {
        if ($row->{$column['name']} !== null) {
            $timeZone = app()->make('i18n')->time_zone;
            $dateTime = new Carbon($value, 'UTC'); // Set the source timezone to UTC
            $dateTime = $dateTime->timezone($timeZone)->format('Y-m-d H:i:s');

            return $dateTime;
        } else {
            return (string) $column['default'];
        }
    }

    /**
     * Process and format the language (locale) column value based on the given configuration.
     *
     * @param  array  $column  The column configuration array
     * @param  mixed  $row  The row containing the date time value
     * @param  mixed  $value  The boolean value to be formatted
     * @return string The formatted date time value as a string, or an empty string if the value is null
     */
    private function processLocaleColumn(array $column, $row, $value): string
    {
        if ($row->{$column['name']} === null) {
            $value = $column['default'];
        } else {
            $value = $row->{$column['name']};
        }

        $i18nService = app(\App\Services\I18nService::class);
        $value = $i18nService->getLocaleName($column['default']);

        return $value;
    }

    /**
     * Process and format the time zone column value based on the given configuration.
     *
     * @param  array  $column  The column configuration array
     * @param  mixed  $row  The row containing the date time value
     * @param  mixed  $value  The boolean value to be formatted
     * @return string The formatted date time value as a string, or an empty string if the value is null
     */
    private function processTimeZoneColumn(array $column, $row, $value): string
    {
        if ($row->{$column['name']} === null) {
            $value = (string) $column['default'];
        }

        return str_replace('_', ' ', $value);
    }

    /**
     * Process and format the currency column value based on the given configuration.
     *
     * @param  array  $column  The column configuration array
     * @param  mixed  $row  The row containing the date time value
     * @param  mixed  $value  The boolean value to be formatted
     * @return string The formatted date time value as a string, or an empty string if the value is null
     */
    private function processCurrencyColumn(array $column, $row, $value): string
    {
        if ($row->{$column['name']} === null) {
            $value = (string) $column['default'];
            if ($value !== '') {
                $language = app()->make('i18n')->language;
                $i18nService = resolve('App\Services\I18nService');
                $currency = $i18nService->getCurrencyDetails($value);
                $currencyName = $currency['name'][$language->current->languageCode] ?? $currency['name']['en'];
                $value = $currencyName.' ('.$currency['id'].')';
            }
        } else {
            $language = app()->make('i18n')->language;
            $i18nService = resolve('App\Services\I18nService');
            $currency = $i18nService->getCurrencyDetails($value);
            $currencyName = $currency['name'][$language->current->languageCode] ?? $currency['name']['en'];
            $value = $currencyName.' ('.$currency['id'].')';
        }

        return $value;
    }

    /**
     * Process and format a user column value based on the given configuration.
     *
     * @param  array  $column  The column configuration array
     * @param  mixed  $row  The row containing the date time value
     * @param  mixed  $value  The boolean value to be formatted
     * @param  array  $settings  The settings array
     * @return string The formatted user value as a string, or the default value if the value is null or user not found
     *
     * @throws Exception If the user type does not exist
     */
    private function processUserColumn(array $column, $row, $value, array $settings): string
    {
        // Early return if the row value is null
        if ($row->{$column['name']} === null) {
            return (string) $column['default'];
        }

        // Define a mapping of user types to their corresponding models
        $userTypeToModelMap = [
            'member' => Member::class,
            'staff' => Staff::class,
            'partner' => Partner::class,
            'admin' => Admin::class,
            'affiliate' => Affiliate::class,
        ];

        $type = explode('.', $column['type'])[1];

        if (! array_key_exists($type, $userTypeToModelMap)) {
            throw new \Exception('User type does not exist');
        }

        // Fetch the corresponding user based on type
        $user = $userTypeToModelMap[$type]::find($value);

        // If user is null, return default
        if ($user === null) {
            return (string) $column['default'];
        }

        // Format the value using user name and email, fall back to email if name is null
        return $user->name !== null ? "{$user->name} ({$user->email})" : $user->email;
    }

    /**
     * Process and format a select column value based on the given configuration.
     *
     * @param  array  $column  The column configuration array
     * @param  mixed  $row  The row containing the date time value
     * @param  mixed  $value  The boolean value to be formatted
     * @param  array  $settings  The settings array
     * @return string The formatted user value as a string, or an empty string if the value is null
     */
    private function processSelectColumn(array $column, $row, $value, array $settings): string
    {
        if ($row->{$column['name']} === null) {
            $value = (string) $column['default'];
        } else {
            $value = ($column['options'] !== null && isset($column['options'][$value])) ? $column['options'][$value] : (string) $column['default'];
        }

        return $value;
    }

    /**
     * Process and format a select relation column value based on the given configuration.
     *
     * @param  array  $column  The column configuration array
     * @param  mixed  $row  The row containing the date time value
     * @param  mixed  $value  The boolean value to be formatted
     * @param  array  $settings  The settings array
     * @return string The formatted user value as a string, or an empty string if the value is null
     */
    private function processRelationColumn(array $column, $row, $value, array $settings): string
    {
        // Default value
        $formattedValue = (string) $column['default'];

        // Process relations
        // NOTE: Check column configuration BEFORE touching $row->{$column['name']}.
        // Some columns (e.g. "manyToMany" via relationThrough) are not real attributes,
        // and accessing them would throw a MissingAttributeException when partial selects are used.
        if ($column['relationKey'] && $column['relationValue'] && $row->{$column['name']} !== null) {
            if (in_array($column['type'], ['belongsToMany'])) {
                $formattedValue = $row->{$column['relation']}()->pluck($column['relationValue'])->toArray();
                $formattedValue = implode(', ', $formattedValue);
            }
            if ($column['type'] == 'belongsTo') {
                $formattedValue = $row->{$column['relation']}()->pluck($column['relationValue'])->first();
            }
        } elseif ($column['type'] == 'manyToMany' && $column['relationThroughPivot'] && $column['relationThroughValue']) {
            $filter = $column['relationThroughFilter'];
            $query = $row->{$column['relationThrough']}()
                ->with($column['relationThroughPivot']) // eager load relation
                ->where(function ($query) use ($filter) {
                    $filter($query);
                });

            if ($column['relationThroughOrderByColumn']) {
                $query->orderBy($column['relationThroughOrderByColumn'], $column['relationThroughOrderDirection']);
            }

            $values = $query->get()
                ->map(function ($q) use ($column) {
                    return $q->{$column['relationThroughPivot']}?->{$column['relationThroughValue']};
                })
                ->filter() // remove null values
                ->unique() // filter out duplicate values
                ->toArray();

            if ($column['relationThroughLink']) {
                $parsedValues = [];
                $uniqueValues = [];
                foreach ($query->get() as $relationThrough) {
                    if ($relationThrough->{$column['relationThroughPivot']}) {
                        $id = $relationThrough->{$column['relationThroughPivot']}->id;
                        $value = $relationThrough->{$column['relationThroughPivot']}->{$column['relationThroughValue']};

                        // Check if the value is already processed, if yes then continue the loop
                        if (in_array($id, $uniqueValues)) {
                            continue;
                        }

                        $link = $column['relationThroughLink']($row, $column, $relationThrough);
                        $parsedValues[] = '<a href="'.$link.'" class="text-link">'.$value.'</a>';

                        // Record the processed value
                        $uniqueValues[] = $id;
                    }
                }
                $formattedValue = implode(', ', $parsedValues);
            } else {
                $formattedValue = implode(', ', $values);
            }
        }

        return $formattedValue ?? ''; // Ensure the method always returns a string
    }

    /**
     * Create an impersonate link for the given row based on the column configuration.
     *
     * @param  string  $dataDefinitionName  The name of The data definition
     * @param  array  $column  The column configuration array
     * @param  mixed  $row  The row containing the impersonate data
     * @param  array  $settings  The settings array
     * @return string The impersonate link as a formatted HTML string
     */
    private function processImpersonateColumn(string $dataDefinitionName, array $column, $row, array $settings): string
    {
        $iconComponent = new Icon('key', 'h-3.5 w-3.5');
        $icon = $iconComponent->render()->render(); // The second render() call is to render the View object to a string

        $value = '<a href="'.route($settings['guard'].'.data.impersonate', ['name' => $dataDefinitionName, 'guard' => $column['guard'], 'id' => $row->id]).'" data-fb="tooltip" title="'.trans('common.log_in_to_account').'" class="inline-flex items-center justify-center p-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-200 rounded-lg hover:bg-zinc-50 hover:text-primary-600 focus:z-10 focus:ring-2 focus:ring-primary-500 focus:text-primary-600 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-400 dark:hover:text-white dark:hover:bg-zinc-700 transition-all shadow-sm">'.$icon.'</a>';

        return $value;
    }

    /**
     * Create a premium QR link for the given row based on the column configuration.
     *
     * Inspired by iOS design philosophy: minimal, focused, and beautiful.
     *
     * @param  string  $dataDefinitionName  The name of The data definition
     * @param  array  $column  The column configuration array
     * @param  mixed  $row  The row containing the data
     * @param  array  $settings  The settings array
     * @return string The QR trigger as a formatted HTML string
     */
    private function processQrColumn(string $dataDefinitionName, array $column, $row, array $settings): string
    {
        $iconComponent = new Icon('qr-code', 'h-5 w-5');
        $icon = $iconComponent->render()->render();

        $id = $row->id;
        $title = ($column['titleColumn']) ? $row->{$column['titleColumn']} : trans('common.loyalty_card');
        $subtitle = trans('common.scan_to_access');
        $url = str_replace(':id', $id, $column['url']);
        $showQrLabel = trans('common.show_qr_code');

        // Escape for HTML attributes - NO server-side QR generation!
        $titleEsc = htmlspecialchars($title, ENT_QUOTES);
        $subtitleEsc = htmlspecialchars($subtitle, ENT_QUOTES);
        $urlEsc = htmlspecialchars($url, ENT_QUOTES);

        return <<<HTML
<button type="button"
        data-qr-title="{$titleEsc}"
        data-qr-subtitle="{$subtitleEsc}"
        data-qr-url="{$urlEsc}"
        @click="\$dispatch('open-qr-modal-data', { 
            title: \$el.dataset.qrTitle, 
            subtitle: \$el.dataset.qrSubtitle, 
            url: \$el.dataset.qrUrl 
        })"
        class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-linear-to-br from-primary-50 to-primary-100 border border-primary-200 hover:from-primary-100 hover:to-primary-200 hover:border-primary-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:from-primary-900/20 dark:to-primary-800/20 dark:border-primary-700 dark:hover:from-primary-900/30 dark:hover:to-primary-800/30 transition-all duration-200 shadow-sm hover:shadow hover:scale-105 group"
        aria-label="{$showQrLabel}">
    <span class="text-primary-600 dark:text-primary-400 group-hover:scale-110 transition-transform">{$icon}</span>
</button>
HTML;
    }

    /**
     * Execute the query callback of the column on the row if present, convert the result to a string and return.
     * If the 'query' key is not present or null, the default value of the column is returned.
     *
     * @param  array  $column  An array representing the column settings. Should contain a 'query' key that is a callable.
     * @param  mixed  $row  The current row that is processed.
     * @param  array  $settings  The settings of the table, not used in the current implementation.
     * @return string The result of the query, converted to a string, or the default value from the column settings.
     */
    private function processQueryColumn(array $column, $row, array $settings): string
    {
        // Extract the query function from the column array
        $queryFunction = $column['query'] ?? null;

        // Check if the query function exists
        if ($queryFunction) {
            // Call the function on the $row, the result might not be a string
            $result = $queryFunction($row);

            // Convert the result to a string, if it's not already a string
            // strval() is a PHP function that can convert various types to string.
            // The ternary operator is used here to check if the result is already a string,
            // if it is, it will be returned as is, otherwise it will be converted.
            $value = is_string($result) ? $result : strval($result);
        } else {
            // If the query function does not exist, return the default value from the column settings
            $value = $column['default'] ?? '';
        }

        // Return the result, or default value if result is null
        return $value;
    }

    /**
     * Merge and validate the provided settings array with default values.
     *
     * @param  array  $settings  The user-defined settings array
     * @return array The merged and validated settings array
     */
    public function getSettings(array $settings): array
    {
        // Per row actions
        $edit = isset($settings['actions']) && ($settings['actions']['edit'] ?? false);
        $delete = isset($settings['actions']) && ($settings['actions']['delete'] ?? false);
        $view = isset($settings['actions']) && ($settings['actions']['view'] ?? false);
        $hasActions = $edit || $delete || $view;

        // General actions
        $list = isset($settings['actions']) && ($settings['actions']['list'] ?? false);
        $insert = isset($settings['actions']) && ($settings['actions']['insert'] ?? false);
        $export = isset($settings['actions']) && ($settings['actions']['export'] ?? false);
        $subject_column = (isset($settings['actions']) && isset($settings['actions']['subject_column']))
            ? $settings['actions']['subject_column'] : false;

        $settings = [
            // Redirect if list is empty to this route
            'onEmptyListRedirectTo' => $settings['onEmptyListRedirectTo'] ?? null,
            // Query filter
            'queryFilter' => $settings['queryFilter'] ?? null,
            // Icon
            'icon' => $settings['icon'] ?? null,
            // Title (plural of subject)
            'title' => $settings['title'] ?? 'Items',
            // Override title (this title is shown in every view, without sub title like "Edit item" or "View item")
            'overrideTitle' => $settings['overrideTitle'] ?? null,
            // Description
            'description' => $settings['description'] ?? null,
            // Show search field
            'search' => $settings['search'] ?? true,
            // Guard of user that manages this data (member, staff, partner or admin)
            // This guard is also used for routes and to include the correct Blade layout
            'guard' => $settings['guard'] ?? 'admin',
            // The guard used for sending the user password
            'mailUserPasswordGuard' => $settings['mailUserPasswordGuard'] ?? null,
            // If set, these role(s) are required
            'roles' => $settings['roles'] ?? null,
            // Used for updating forms like profile, where user has to enter current password in order to save
            // Keep in mind that there is no check whether the logged in user actually "owns" the record,
            // for that check 'redirectListToEdit' is required
            'editRequiresPassword' => $settings['editRequiresPassword'] ?? false,
            // Used for updating forms like profile, where user has to verify with OTP code sent to their email
            // Modern passwordless alternative to 'editRequiresPassword'
            'editRequiresOtp' => $settings['editRequiresOtp'] ?? false,
            // If true, the visitor is redirected to the edit form. This can be used for editing a user profile.
            'redirectListToEdit' => $settings['redirectListToEdit'] ?? false,
            // This column has to match auth($guard)->user()->id if 'redirectListToEdit' == true (usually it will be 'id' or 'created_by')
            // This is also validated on save
            'redirectListToEditColumn' => $settings['redirectListToEditColumn'] ?? null,
            // If true, the user id must match the created_by field
            'userMustOwnRecords' => $settings['userMustOwnRecords'] ?? true,
            // Filter with certain role
            'userFilterRole' => $settings['userFilterRole'] ?? null,
            // Should there be checkboxes for all rows (e.g. to delete multiple records with one action)
            'multiSelect' => $settings['multiSelect'] ?? false,
            // Default items per page for pagination
            'itemsPerPageOptions' => $settings['itemsPerPageOptions'] ?? [10, 20, 50, 100],
            'itemsPerPage' => (int) ($settings['itemsPerPage'] ?? 20),
            // Default order by column
            'orderByColumn' => request()->input('order', null) ?? $settings['orderByColumn'] ?? 'id',
            // Default order direction, 'asc' or 'desc'
            'orderDirection' => request()->input('orderDir', null) ?? $settings['orderDirection'] ?? 'asc',
            // Order by relation
            'orderRelation' => $settings['orderRelation'] ?? null,
            // Specify if the 'updated_by' column needs to
            'updatedBy' => $settings['updatedBy'] ?? true,
            // Possible actions for the data
            'edit' => $edit,
            'delete' => $delete,
            // This column is used for page titles and delete confirmations
            'subject_column' => $subject_column,
            'view' => $view,
            // Is list view allowed?
            'list' => $list,
            'insert' => $insert,
            'export' => $export,
            'hasActions' => $hasActions,
            // Callback before inserting a record (sets attributes before save)
            'beforeInsert' => $settings['beforeInsert'] ?? null,
            // Callback after inserting a record
            'afterInsert' => $settings['afterInsert'] ?? null,
            // Callback before updating a record (runs even when saveQuietly is used)
            'beforeUpdate' => $settings['beforeUpdate'] ?? null,
            // Callback after updating a record (runs even when saveQuietly is used)
            'afterUpdate' => $settings['afterUpdate'] ?? null,
            // Custom link
            'customLink' => $settings['customLink'] ?? null,
            // Custom JavaScript for views (string for all, or array with keys: insert, edit, list, view, all)
            'js' => $settings['js'] ?? null,
            // Limit reached flag (set by DataDefinitions when creator hits their limit)
            'limitReached' => $settings['limitReached'] ?? false,
            'limitReachedMessage' => $settings['limitReachedMessage'] ?? null,
            // Help content displayed as collapsible accordion at top of list view
            // Format: ['icon' => 'lightbulb', 'title' => '...', 'content' => '...', 'steps' => [...], 'link' => [...]]
            'helpContent' => $settings['helpContent'] ?? null,
            // Custom description shown when list is empty (no results)
            'emptyStateDescription' => $settings['emptyStateDescription'] ?? null,
        ];

        // Allow overriding items per page via query string or cookie (last preference),
        // but keep it constrained to safe values.
        $definitionName = property_exists($this, 'name') ? (string) $this->{'name'} : 'data';
        $cookieName = 'rl_dd_per_page_'.Str::slug($settings['guard'].'_'.$definitionName);

        $requestedItemsPerPage = request()->integer('perPage');
        if (is_int($requestedItemsPerPage) && in_array($requestedItemsPerPage, $settings['itemsPerPageOptions'], true)) {
            $settings['itemsPerPage'] = $requestedItemsPerPage;

            // Persist preference for 1 year.
            Cookie::queue(cookie()->forever($cookieName, (string) $requestedItemsPerPage));
        } else {
            $cookieItemsPerPage = request()->cookie($cookieName);
            $cookieItemsPerPage = is_numeric($cookieItemsPerPage) ? (int) $cookieItemsPerPage : null;
            if (is_int($cookieItemsPerPage) && in_array($cookieItemsPerPage, $settings['itemsPerPageOptions'], true)) {
                $settings['itemsPerPage'] = $cookieItemsPerPage;
            }
        }

        // Enforce minimum (protect against misconfiguration).
        $minPerPage = min($settings['itemsPerPageOptions']);
        if ($settings['itemsPerPage'] < $minPerPage) {
            $settings['itemsPerPage'] = $minPerPage;
        }

        // Default should remain 20 unless explicitly overridden.
        if (! in_array($settings['itemsPerPage'], $settings['itemsPerPageOptions'], true)) {
            $settings['itemsPerPage'] = 20;
        }

        // Verify if user role is required and matches
        if (is_array($settings['roles']) && ! in_array(auth($settings['guard'])->user()->role, $settings['roles'])) {
            Log::notice('app\DataDefinitions\DataDefinition.php - User ('.auth($settings['guard'])->user()->email.') does not have required role ('.implode(', ', $settings['roles']).')');
            abort(404);
        }

        return $settings;
    }

    /**
     * Apply search conditions to the query based on provided columns and search term.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  The query builder instance
     * @param  array  $columns  The list of column definitions
     * @param  string  $searchTerm  The search term to apply to the query
     */
    private function addSearchConditions($query, array $columns, string $searchTerm): void
    {
        $searchableColumns = [];
        foreach ($columns as $column) {
            if ($column['searchable'] === true || ($column['searchable'] === null && $this->isSearchableByDefault($column))) {
                $searchableColumns[] = $column['name'];
            }
        }

        if ($searchableColumns === []) {
            return;
        }

        $query->where(function (Builder $q) use ($searchableColumns, $searchTerm, $columns): void {
            $columnsByName = collect($columns)->keyBy('name');

            foreach ($searchableColumns as $i => $columnName) {
                /** @var array $column */
                $column = (array) ($columnsByName->get($columnName) ?? []);

                $isJsonField = isset($column['json']) && is_string($column['json']) && $column['json'] !== '';
                if ($isJsonField) {
                    // This field lives inside a JSON column; we must search the JSON column, not the select alias.
                    $jsonColumn = $column['json'];

                    if (DB::getDriverName() === 'sqlite') {
                        $expression = "json_extract({$jsonColumn}, '$.\"{$columnName}\"')";
                    } else {
                        $expression = "JSON_UNQUOTE(JSON_EXTRACT({$jsonColumn}, '$.\"{$columnName}\"'))";
                    }

                    if ($i === 0) {
                        $q->whereRaw($expression.' LIKE ?', ['%'.$searchTerm.'%']);
                    } else {
                        $q->orWhereRaw($expression.' LIKE ?', ['%'.$searchTerm.'%']);
                    }

                    continue;
                }

                if ($i === 0) {
                    $q->where($columnName, 'LIKE', '%'.$searchTerm.'%');
                } else {
                    $q->orWhere($columnName, 'LIKE', '%'.$searchTerm.'%');
                }
            }
        });
    }

    /**
     * Apply search across ALL fields of a DataDefinition, regardless of view actions.
     *
     * The only opt-out is `'searchable' => false`.
     */
    private function applySearchAllFields(Builder $query, Model $model, array $fields, string $searchTerm): void
    {
        $searchTerm = trim($searchTerm);
        if ($searchTerm === '') {
            return;
        }

        $flatFields = $this->flattenFields($fields);

        /** @var array<int, array{type:'column', column:string}|array{type:'raw', sql:string, bindings:array<int, mixed>}> $conditions */
        $conditions = [];

        foreach ($flatFields as $fieldName => $field) {
            if (! is_string($fieldName) || ! is_array($field)) {
                continue;
            }

            if (($field['searchable'] ?? null) === false) {
                continue;
            }

            $columnName = Str::contains($fieldName, '::')
                ? Str::before($fieldName, '::')
                : $fieldName;

            $type = (string) ($field['type'] ?? 'string');

            // Skip non-searchable / non-SQL types.
            if (in_array($type, ['password', 'boolean', 'number', 'date_time', 'image', 'avatar'], true)) {
                continue;
            }
            if (in_array($type, ['belongsToMany', 'belongsTo', 'manyToMany', 'impersonate', 'qr'], true)) {
                continue;
            }

            // JSON extraction field: value lives inside a JSON column.
            $jsonColumn = $field['json'] ?? null;
            if (is_string($jsonColumn) && $jsonColumn !== '') {
                if (DB::getDriverName() === 'sqlite') {
                    $expression = "json_extract({$jsonColumn}, '$.\"{$columnName}\"')";
                } else {
                    $expression = "JSON_UNQUOTE(JSON_EXTRACT({$jsonColumn}, '$.\"{$columnName}\"'))";
                }

                $conditions[] = [
                    'type' => 'raw',
                    'sql' => $expression.' LIKE ?',
                    'bindings' => ['%'.$searchTerm.'%'],
                ];

                continue;
            }

            // Translated label → raw value mapping.
            // When a field displays a translated label (e.g. "Points Credited")
            // but stores a raw slug (e.g. "staff_credited_points_for_purchase"),
            // the search_map closure returns matching raw values for the search term
            // so the user can search by what they see on screen.
            // This runs BEFORE the column/SQL checks because it's additive — a field
            // can have both a LIKE condition and a search_map IN condition.
            $searchMap = $field['search_map'] ?? null;
            if ($searchMap instanceof \Closure) {
                $matchingValues = $searchMap($searchTerm);
                if (is_array($matchingValues) && $matchingValues !== []) {
                    $searchMapColumn = $model->schemaHasColumn($columnName) ? $columnName : null;
                    if ($searchMapColumn !== null) {
                        $conditions[] = [
                            'type' => 'in',
                            'column' => $searchMapColumn,
                            'values' => $matchingValues,
                        ];
                    }
                }
            }

            // Normal DB columns (including JSON columns for translatable fields).
            if ($model->schemaHasColumn($columnName)) {
                $conditions[] = [
                    'type' => 'column',
                    'column' => $columnName,
                ];

                continue;
            }

            // SQL expression fields (rare; opt-out with searchable=false if needed).
            $sql = $field['sql'] ?? null;
            if (is_string($sql) && $sql !== '') {
                $conditions[] = [
                    'type' => 'raw',
                    'sql' => '('.$sql.') LIKE ?',
                    'bindings' => ['%'.$searchTerm.'%'],
                ];
            }
        }

        if ($conditions === []) {
            return;
        }

        $query->where(function (Builder $q) use ($conditions, $searchTerm): void {
            foreach ($conditions as $i => $condition) {
                if ($condition['type'] === 'column') {
                    if ($i === 0) {
                        $q->where($condition['column'], 'LIKE', '%'.$searchTerm.'%');
                    } else {
                        $q->orWhere($condition['column'], 'LIKE', '%'.$searchTerm.'%');
                    }

                    continue;
                }

                if ($condition['type'] === 'in') {
                    if ($i === 0) {
                        $q->whereIn($condition['column'], $condition['values']);
                    } else {
                        $q->orWhereIn($condition['column'], $condition['values']);
                    }

                    continue;
                }

                if ($condition['type'] === 'raw') {
                    if ($i === 0) {
                        $q->whereRaw($condition['sql'], $condition['bindings']);
                    } else {
                        $q->orWhereRaw($condition['sql'], $condition['bindings']);
                    }
                }
            }
        });
    }

    /**
     * Flatten tabbed DataDefinition fields into a single array.
     *
     * @return array<string, mixed>
     */
    private function flattenFields(array $fields): array
    {
        $flat = [];

        foreach ($fields as $columnName => $field) {
            if (is_string($columnName) && preg_match('/^tab\d/', $columnName) && is_array($field) && isset($field['fields']) && is_array($field['fields'])) {
                foreach ($field['fields'] as $innerName => $innerField) {
                    $flat[$innerName] = $innerField;
                }

                continue;
            }

            $flat[$columnName] = $field;
        }

        return $flat;
    }

    /**
     * Infer whether a column should be searched by default.
     *
     * Goal: "it just works" for common CRUD columns, without making search noisy
     * (IDs, flags, timestamps, relations, etc.).
     */
    private function isSearchableByDefault(array $column): bool
    {
        if (($column['hidden'] ?? false) === true) {
            return false;
        }

        if (($column['exists_in_database'] ?? false) !== true && empty($column['json'])) {
            // Skip computed columns / appends unless explicitly marked searchable.
            return false;
        }

        $type = (string) ($column['type'] ?? '');
        if (in_array($type, ['password', 'boolean', 'number', 'date_time', 'image', 'avatar'], true)) {
            return false;
        }

        // Relations and action columns should never be auto-searched.
        if (in_array($type, ['belongsToMany', 'belongsTo', 'manyToMany', 'impersonate', 'qr'], true)) {
            return false;
        }

        $name = (string) ($column['name'] ?? '');
        if ($name === '' || in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by'], true)) {
            return false;
        }

        // Default allow list: string-like input columns.
        return in_array($type, ['string', 'textarea', 'select', 'locale', 'currency', 'time_zone'], true);
    }

    /**
     * Provide lightweight search suggestions for the list search autocomplete.
     *
     * Notes:
     * - Uses only searchable columns from the DataDefinition fields.
     * - Respects the same access constraints as list (guard, userMustOwnRecords, queryFilter).
     *
     * @return array<int, array{id:string, label:string}>
     */
    public function getSearchSuggestions(string $searchTerm, int $limit = 8): array
    {
        $settingsSource = property_exists($this, 'settings') ? $this->{'settings'} : [];
        $settings = $this->getSettings(is_array($settingsSource) ? $settingsSource : []);

        $model = property_exists($this, 'model') ? $this->{'model'} : null;

        if (! $model instanceof Model) {
            return [];
        }

        $searchTerm = trim($searchTerm);
        if ($searchTerm === '' || mb_strlen($searchTerm) < 2) {
            return [];
        }

        $query = $model->query();

        // Role-based filter.
        if ($settings['userFilterRole'] && isset($settings['userFilterRole'][auth($settings['guard'])->user()->role])) {
            $filter = $settings['userFilterRole'][auth($settings['guard'])->user()->role];
            $query = $filter($model);
        }

        // Global query filter.
        if ($settings['queryFilter']) {
            $query = $settings['queryFilter']($query);
        }

        // Ownership restriction.
        if ($settings['userMustOwnRecords']) {
            $userId = auth($settings['guard'])->user()->id;
            $query->where('created_by', $userId);
        }

        $primaryKey = $model->getKeyName();
        $query->addSelect("{$primaryKey} as id");

        $fieldsSource = property_exists($this, 'fields') ? $this->{'fields'} : [];
        $fields = is_array($fieldsSource) ? $fieldsSource : [];

        $this->applySearchAllFields($query, $model, $fields, $searchTerm);

        // Determine which columns to select for label generation.
        // Subclasses that override getSuggestionLabel() declare
        // which columns they need via getSuggestionSelectColumns().
        $selectColumns = $this->getSuggestionSelectColumns($model);
        foreach ($selectColumns as $col) {
            $query->addSelect($col);
        }

        // Keep ordering consistent with list.
        if ($settings['orderRelation']) {
            $query->selectSub($settings['orderRelation'], 'sub_order')
                ->orderBy('sub_order', $settings['orderDirection']);
        } else {
            $query->orderBy($settings['orderByColumn'], $settings['orderDirection']);
        }

        /** @var array<int, array{id:string, label:string}> $suggestions */
        $suggestions = [];

        $rows = $query->limit(max(1, min($limit, 15)))->get();

        foreach ($rows as $row) {
            $label = trim($this->getSuggestionLabel($row));

            if ($label === '') {
                continue;
            }

            $suggestions[] = [
                'id' => (string) $row->id,
                'label' => $label,
            ];
        }

        return $suggestions;
    }

    /**
     * Build the display label for a search suggestion row.
     *
     * Override in subclasses to compose multi-field labels
     * (e.g. "Points Credited · Gold Card · John Smith").
     * The base implementation falls back to subject_column → name.
     */
    protected function getSuggestionLabel(Model $row): string
    {
        $settingsSource = property_exists($this, 'settings') ? $this->{'settings'} : [];
        $settings = $this->getSettings(is_array($settingsSource) ? $settingsSource : []);
        $subjectColumn = $settings['subject_column'] ?: null;

        if (is_string($subjectColumn) && $subjectColumn !== '' && isset($row->{$subjectColumn})) {
            return (string) $row->{$subjectColumn};
        }

        if (isset($row->name)) {
            return (string) $row->name;
        }

        return '';
    }

    /**
     * Columns needed by getSuggestionLabel().
     *
     * Override alongside getSuggestionLabel() when the label
     * requires columns beyond subject_column / name.
     *
     * @return list<string>
     */
    protected function getSuggestionSelectColumns(Model $model): array
    {
        $settingsSource = property_exists($this, 'settings') ? $this->{'settings'} : [];
        $settings = $this->getSettings(is_array($settingsSource) ? $settingsSource : []);
        $subjectColumn = $settings['subject_column'] ?: null;

        if (is_string($subjectColumn) && $subjectColumn !== '' && $model->schemaHasColumn($subjectColumn)) {
            return [$subjectColumn];
        }

        if ($model->schemaHasColumn('name')) {
            return ['name'];
        }

        // Fallback: find first searchable string column.
        $fieldsSource = property_exists($this, 'fields') ? $this->{'fields'} : [];
        $fields = is_array($fieldsSource) ? $fieldsSource : [];

        foreach ($this->flattenFields($fields) as $columnName => $field) {
            if (! is_string($columnName) || ! is_array($field)) {
                continue;
            }
            if (($field['searchable'] ?? null) === false) {
                continue;
            }
            $type = (string) ($field['type'] ?? 'string');
            if (! in_array($type, ['string', 'textarea', 'select'], true)) {
                continue;
            }
            $baseName = Str::contains($columnName, '::') ? Str::before($columnName, '::') : $columnName;
            if ($model->schemaHasColumn($baseName)) {
                return [$baseName];
            }
        }

        return [];
    }
}
