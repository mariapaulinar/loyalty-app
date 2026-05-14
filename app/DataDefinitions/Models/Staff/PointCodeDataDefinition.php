<?php

namespace App\DataDefinitions\Models\Staff;

use App\DataDefinitions\DataDefinition;
use Illuminate\Database\Eloquent\Model;

/**
 * DataDefinition for listing/deleting point_codes by a Staff user.
 *
 * Staff sees only codes that belong to cards in their club & partner,
 * and can delete them if needed.
 */
class PointCodeDataDefinition extends DataDefinition
{
    /**
     * Unique, URL-friendly name for CRUD purposes.
     *
     * @var string
     */
    public $name = 'codes';

    /**
     * The Eloquent model.
     *
     * @var Model
     */
    public $model;

    /**
     * Table columns.
     *
     * @var array
     */
    public $fields;

    /**
     * General settings for listing, etc.
     *
     * @var array
     */
    public $settings;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->model = new \App\Models\PointCode;

        $this->fields = [
            // Which card this code belongs to
            'card_id' => [
                'text' => trans('common.card'),
                'type' => 'belongsTo',
                'relation' => 'card',
                'relationKey' => 'cards.id',
                'relationValue' => 'cards.head',
                'relationModel' => new \App\Models\Card,
                'actions' => ['list'],
                'sortable' => false,
            ],

            // The 4-digit code
            'code' => [
                'text' => trans('common.code'),
                'type' => 'string',
                'searchable' => true,
                'sortable' => false,
                'actions' => ['list'],
            ],

            // Number of points the code grants
            'points' => [
                'text' => trans('common.points'),
                'type' => 'number',
                'sortable' => false,
                'actions' => ['list'],
            ],

            // Human-readable expiration (diffForHumans)
            'expires_at' => [
                'text' => trans('common.expires'),
                'type' => 'query',
                'actions' => ['list'],
                'query' => function ($row) {
                    if (! $row->expires_at) {
                        return '-';
                    }

                    // If already past, show e.g. "Expired (3 minutes ago)"
                    if ($row->expires_at->isPast()) {
                        return trans('common.expired').' ('.$row->expires_at->diffForHumans().')';
                    }

                    // E.g. "in 15 minutes"
                    return $row->expires_at->diffForHumans();
                },
            ],

            // The member who used the code
            'used_by' => [
                'text' => trans('common.used_by'),
                'type' => 'belongsTo',
                'format' => 'hideEmail',
                'relation' => 'usedMember',
                'relationKey' => 'members.id',
                'relationValue' => 'members.email',
                'relationModel' => new \App\Models\Member,
                'actions' => ['list'],
                'sortable' => false,
            ],

            // When it was used, e.g. "3 hours ago"
            'used_at' => [
                'text' => trans('common.used'),
                'type' => 'query',
                'actions' => ['list'],
                'query' => function ($row) {
                    if (! $row->used_at) {
                        return trans('common.not_used_yet');
                    }

                    return $row->used_at->diffForHumans();
                },
            ],
        ];

        $this->settings = [
            // Filter so staff only sees codes that belong to a card in staff's club & partner
            'queryFilter' => function ($query) {
                $staffUser = auth('staff')->user();

                // First limit which records the staff can see
                $query->whereHas('card', function ($cardQ) use ($staffUser) {
                    $cardQ->where('club_id', $staffUser->club_id)
                        ->where('created_by', $staffUser->partner->id ?? 0);
                });

                // Then apply a custom ORDER BY:
                //   1) Put rows with non-null expires_at first (CASE WHEN expires_at IS NULL => 1, ELSE 0 => sorted ascending)
                //   2) Among those non-null, sort ascending by expires_at (earliest date on top).
                //   3) Then for rows with expires_at = NULL, sort them by used_at descending.
                $query->orderByRaw('
                    CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END ASC
                ')
                    ->orderBy('id', 'desc')
                    ->orderBy('used_at', 'desc');

                return $query;
            },
            'icon' => 'hash',
            'title' => trans('common.redemption_codes'),
            // Description shown below the title
            'description' => trans('common.codes_description'),
            // Help content for the list view (dismissable accordion)
            'helpContent' => [
                'icon' => 'hash',
                'title' => trans('common.codes_help_title'),
                'content' => trans('common.codes_help_content'),
                'steps' => [
                    [
                        'title' => trans('common.codes_help_step1_title'),
                        'description' => trans('common.codes_help_step1_desc'),
                    ],
                    [
                        'title' => trans('common.codes_help_step2_title'),
                        'description' => trans('common.codes_help_step2_desc'),
                    ],
                    [
                        'title' => trans('common.codes_help_step3_title'),
                        'description' => trans('common.codes_help_step3_desc'),
                    ],
                ],
            ],
            'guard' => 'staff',
            'userMustOwnRecords' => true,

            // Custom link
            'customLink' => [
                'url' => route('staff.data.list', ['name' => 'cards']),
                'label' => trans('common.generate_code'),
                'icon' => 'plus',
            ],

            // We allow "delete" so staff can remove code entries
            'actions' => [
                'subject_column' => 'code',
                'list' => true,
                'insert' => false,
                'edit' => false,
                'delete' => true,
                'view' => false,
                'export' => false,
            ],

            'itemsPerPage' => 10,
            'orderByColumn' => null,
            'orderDirection' => null,
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
