<?php

namespace App\DataDefinitions\Models\Staff;

use App\DataDefinitions\DataDefinition;
use Illuminate\Database\Eloquent\Model;

/**
 * DataDefinition class for listing Cards accessible to a Staff user,
 * including an "Actions" column with a "Generate code" link.
 */
class CardDataDefinition extends DataDefinition
{
    /**
     * Unique, URL-friendly name for CRUD purposes.
     *
     * @var string
     */
    public $name = 'cards';

    /**
     * The model associated with this DataDefinition.
     *
     * @var Model
     */
    public $model;

    /**
     * The fields (columns) that will appear in the list.
     *
     * @var array
     */
    public $fields;

    /**
     * Settings such as query filters, guard, and actions.
     *
     * @var array
     */
    public $settings;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Set the Eloquent model
        $this->model = new \App\Models\Card;

        // Define columns
        $this->fields = [
            'head' => [
                'text' => trans('common.name'),
                'type' => 'string',
                'searchable' => true,
                'sortable' => true,
                'actions' => ['list'],
            ],
            'unique_identifier' => [
                'text' => trans('common.identifier'),
                'type' => 'string',
                'searchable' => true,
                'actions' => ['list'],
            ],
            // This is the new "Actions" column:
            'actions' => [
                'text' => trans('common.actions'),
                'type' => 'query',    // We return custom HTML with a closure
                'allowHtml' => true,       // Ensure the HTML isn't escaped
                'actions' => ['list'],   // Only on the list view
                'query' => function ($row) {
                    // Build a "Generate Code" link.
                    $url = route('staff.code.generate', [
                        'card_identifier' => $row->unique_identifier,
                    ]);

                    $label = trans('common.generate_redemption_code');

                    return '<a href="'.$url.'" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-primary-600 dark:text-primary-400 bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-700 rounded-lg shadow-sm hover:bg-primary-50 dark:hover:bg-primary-500/10 hover:border-primary-300 dark:hover:border-primary-500/30 hover:text-primary-700 dark:hover:text-primary-300 transition-all duration-200 whitespace-nowrap"><svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 9h16M4 15h16M10 3l-2 18M16 3l-2 18" /></svg>'.$label.'</a>';
                },
            ],
        ];

        // Settings
        $this->settings = [
            'queryFilter' => function ($query) {
                $staffUser = auth('staff')->user();

                return $query->where('club_id', $staffUser->club_id)
                    ->where('created_by', $staffUser->partner->id ?? 0);
            },
            'icon' => 'credit-card',
            'title' => trans('common.loyalty_cards'),
            'guard' => 'staff',
            'userMustOwnRecords' => false,
            'actions' => [
                'subject_column' => 'head',
                'list' => true,
                'insert' => false,
                'edit' => false,
                'delete' => false,
                'view' => false,
                'export' => false,
            ],
            'itemsPerPage' => 10,
            'orderByColumn' => 'id',
            'orderDirection' => 'desc',
            'editRequiresPassword' => false,
            'redirectListToEdit' => false,
            'redirectListToEditColumn' => null,
            'multiSelect' => false,
        ];
    }

    /**
     * Retrieve data based on fields.
     */
    public function getData(
        ?string $dataDefinitionName = null,
        string $dataDefinitionView = 'list',
        array $options = [],
        ?Model $model = null,
        array $settings = [],
        array $fields = []
    ): array {
        return parent::getData(
            $this->name,
            $dataDefinitionView,
            $options,
            $this->model,
            $this->settings,
            $this->fields
        );
    }

    /**
     * Parse settings.
     */
    public function getSettings(array $settings): array
    {
        return parent::getSettings($this->settings);
    }
}
