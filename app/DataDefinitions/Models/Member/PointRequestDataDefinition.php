<?php

namespace App\DataDefinitions\Models\Member;

use App\DataDefinitions\DataDefinition;
use Illuminate\Database\Eloquent\Model;

/**
 * DataDefinition for listing, editing, and deleting point request links by a Member.
 *
 * Members can view their generated point request links, edit only the associated card and
 * whether the link is active, and delete their own request links.
 */
class PointRequestDataDefinition extends DataDefinition
{
    /**
     * Unique, URL‑friendly name for CRUD purposes.
     *
     * @var string
     */
    public $name = 'request-links';

    /**
     * The Eloquent model associated with the definition.
     *
     * @var Model
     */
    public $model;

    /**
     * The fields (columns) to display in the list and edit views.
     *
     * @var array
     */
    public $fields;

    /**
     * General settings for this data definition (query filter, allowed actions, etc.).
     *
     * @var array
     */
    public $settings;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Set the model – using the PointRequest model.
        $this->model = new \App\Models\PointRequest;

        // Define the columns for the list (and edit) view.
        $this->fields = [
            // Unique identifier as a clickable link.
            'unique_identifier' => [
                'text' => trans('common.link'),
                'type' => 'query',
                'searchable' => true,
                'classes::list' => 'w-2',
                'actions' => ['list'], // Not editable.
                'query' => function ($row) {
                    $url = route('member.request.points.send', ['request_identifier' => $row->unique_identifier]);
                    $label = trans('common.share_link');

                    return '<a href="'.$url.'" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-primary-600 dark:text-primary-400 bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-700 rounded-lg shadow-sm hover:bg-primary-50 dark:hover:bg-primary-500/10 hover:border-primary-300 dark:hover:border-primary-500/30 hover:text-primary-700 dark:hover:text-primary-300 transition-all duration-200 whitespace-nowrap"><svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 0 0-5.656 0l-4 4a4 4 0 1 0 5.656 5.656l1.102-1.101M10.172 13.828a4 4 0 0 0 5.656 0l4-4a4 4 0 0 0-5.656-5.656l-1.1 1.1" /></svg>'.$label.'</a>';
                },
            ],
            // Card selection.
            // Uses a belongsTo relation. If card_id is null, we show "Works with all cards".
            'card_id' => [
                'text' => trans('common.card'),
                'type' => 'query',
                'relation' => 'card',
                'relationKey' => 'cards.id',
                'relationValue' => 'cards.head',
                'relationModel' => new \App\Models\Card,
                'actions' => ['list', 'edit'],  // Allow editing this field.
                'sortable' => false,
                // Use a query callback to display a friendly message when no specific card is set.
                'query' => function ($row) {
                    return $row->card_id
                        ? $row->card->head
                        : trans('common.receive_points_for_all_cards');
                },
            ],
            // Whether the link is active.
            'is_active' => [
                'text' => trans('common.active'),
                'type' => 'boolean',
                'sortable' => true,
                'actions' => ['edit'], // Allow editing.
            ],
            // Usage count (read‑only).
            'usage_count' => [
                'text' => trans('common.uses'),
                'type' => 'number',
                'sortable' => true,
                'actions' => ['list'], // Not editable.
            ],
        ];

        // Define general settings.
        $this->settings = [
            'icon' => 'link',
            'title' => trans('common.request_links'),
            'description' => trans('common.request_links_description'),
            'guard' => 'member',
            'userMustOwnRecords' => true,
            'search' => false,

            // Help content (dismissable accordion)
            'helpContent' => [
                'icon' => 'link',
                'title' => trans('common.request_links_help_title'),
                'content' => trans('common.request_links_help_content'),
            ],

            // Custom link
            'customLink' => [
                'url' => route('member.request.points.generate'),
                'label' => trans('common.generate_request_link'),
                'icon' => 'plus',
            ],

            'onEmptyListRedirectTo' => route('member.request.points.generate'),

            // Allow listing, editing, and deleting.
            'actions' => [
                'subject_column' => 'unique_identifier',
                'list' => true,
                'insert' => false,
                'edit' => false,
                'delete' => true,
                'view' => false,
                'export' => false,
            ],
            'itemsPerPage' => 10,
            'orderByColumn' => 'created_at',
            'orderDirection' => 'desc',
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
