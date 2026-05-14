<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * DataDefinition for Partner "Business Settings" configuration.
 * Allows business owners to quickly update their public profile —
 * branding, location, contact info, and opening hours.
 *
 * Design Tenets:
 * - **Quick Updates**: No friction for updating hours, socials, branding
 * - **Micro-Site First**: Everything here powers the public business page
 * - **Visual Identity**: Logo, cover, colors, tagline
 * - **Location & Contact**: Address, maps, phone, website, socials
 * - **Operations**: Opening hours
 *
 * Architecture Notes:
 * Unlike ProfileDataDefinition (account/credentials), this is for PUBLIC-facing
 * business information. Data is stored directly on the Partner model.
 */

namespace App\DataDefinitions\Models\Partner;

use App\DataDefinitions\DataDefinition;
use Illuminate\Database\Eloquent\Model;

class BusinessSettingsDataDefinition extends DataDefinition
{
    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     */
    public $name = 'business-settings';

    /**
     * The model associated with the definition.
     */
    public $model;

    /**
     * Settings.
     */
    public $settings;

    /**
     * Model fields for list, edit, view.
     */
    public $fields;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Set the model
        $this->model = new \App\Models\Partner;

        // Get the current partner for dynamic field values
        $partner = auth('partner')->user();

        // Define the fields for the data definition
        $this->fields = [
            // ═════════════════════════════════════════════════════════════
            // TAB 1: BRANDING - Visual Identity
            // ═════════════════════════════════════════════════════════════
            'tab1' => [
                'title' => trans('common.branding'),
                'icon' => 'palette',
                'fields' => [
                    'business_name' => [
                        'text' => trans('common.business_name'),
                        'type' => 'string',
                        'help' => trans('common.business_name_help'),
                        'placeholder' => trans('common.business_name_placeholder'),
                        'validate' => ['nullable', 'max:128'],
                        'actions' => ['edit', 'view'],
                    ],
                    'tagline' => [
                        'text' => trans('common.tagline'),
                        'type' => 'string',
                        'help' => trans('common.tagline_help'),
                        'placeholder' => trans('common.tagline_placeholder'),
                        'validate' => ['nullable', 'max:160'],
                        'actions' => ['edit', 'view'],
                    ],
                    'brand_color' => [
                        'text' => trans('common.brand_color'),
                        'type' => 'string',
                        'format' => 'color',
                        'default' => '#10B981',
                        'help' => trans('common.brand_color_help'),
                        'validate' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
                        'actions' => ['edit', 'view'],
                    ],
                    'description' => [
                        'text' => trans('common.about'),
                        'type' => 'textarea',
                        'help' => trans('common.about_help'),
                        'validate' => ['nullable', 'max:500'],
                        'actions' => ['edit', 'view'],
                    ],
                    'avatar' => [
                        'thumbnail' => 'small',
                        'conversion' => 'medium',
                        'text' => trans('common.logo'),
                        'type' => 'avatar',
                        'textualAvatarBasedOnColumn' => 'name',
                        'accept' => 'image/svg+xml, image/png, image/jpeg, image/gif',
                        'help' => trans('common.logo_help'),
                        'validate' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:1024', 'dimensions:min_width=60,min_height=60,max_width=1024,max_height=1024'],
                        'actions' => ['edit', 'view'],
                    ],
                    'cover' => [
                        'thumbnail' => 'thumb',
                        'conversion' => 'large',
                        'text' => trans('common.cover_image'),
                        'type' => 'image',
                        'accept' => 'image/png, image/jpeg, image/gif, image/webp',
                        'help' => trans('common.cover_image_help'),
                        'validate' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048', 'dimensions:min_width=400,min_height=200,max_width=2400,max_height=1200'],
                        'actions' => ['edit', 'view'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 2: LOCATION - Address & Navigation
            // ═════════════════════════════════════════════════════════════
            'tab2' => [
                'title' => trans('common.location'),
                'icon' => 'map-pin',
                'fields' => [
                    'address_line_1' => [
                        'text' => trans('common.address_line_1'),
                        'type' => 'string',
                        'placeholder' => trans('common.address_line_1_placeholder'),
                        'validate' => ['nullable', 'max:128'],
                        'actions' => ['edit', 'view'],
                    ],
                    'address_line_2' => [
                        'text' => trans('common.address_line_2'),
                        'type' => 'string',
                        'placeholder' => trans('common.address_line_2_placeholder'),
                        'validate' => ['nullable', 'max:128'],
                        'actions' => ['edit', 'view'],
                    ],
                    'city' => [
                        'text' => trans('common.city'),
                        'type' => 'string',
                        'validate' => ['nullable', 'max:64'],
                        'actions' => ['edit', 'view'],
                    ],
                    'state' => [
                        'text' => trans('common.state_province'),
                        'type' => 'string',
                        'validate' => ['nullable', 'max:64'],
                        'actions' => ['edit', 'view'],
                    ],
                    'postal_code' => [
                        'text' => trans('common.postal_code'),
                        'type' => 'string',
                        'validate' => ['nullable', 'max:16'],
                        'actions' => ['edit', 'view'],
                    ],
                    'maps_url' => [
                        'text' => trans('common.google_maps_url'),
                        'type' => 'string',
                        'format' => 'url',
                        'help' => trans('common.google_maps_url_help'),
                        'placeholder' => trans('common.google_maps_url_placeholder'),
                        'validate' => ['nullable', 'url', 'max:500'],
                        'actions' => ['edit', 'view'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 3: CONTACT - Phone, Website, Socials
            // ═════════════════════════════════════════════════════════════
            'tab3' => [
                'title' => trans('common.contact'),
                'icon' => 'phone',
                'fields' => [
                    'website' => [
                        'text' => trans('common.website'),
                        'type' => 'string',
                        'format' => 'url',
                        'placeholder' => trans('common.website_placeholder'),
                        'validate' => ['nullable', 'url', 'max:255'],
                        'actions' => ['edit', 'view'],
                    ],
                    'social_links.instagram' => [
                        'text' => trans('common.instagram'),
                        'type' => 'string',
                        'format' => 'url',
                        'icon' => 'instagram',
                        'placeholder' => trans('common.instagram_placeholder'),
                        'help' => trans('common.social_url_help'),
                        'validate' => ['nullable', 'url', 'max:255'],
                        'actions' => ['edit', 'view'],
                    ],
                    'social_links.tiktok' => [
                        'text' => trans('common.tiktok'),
                        'type' => 'string',
                        'format' => 'url',
                        'icon' => 'tiktok',
                        'placeholder' => trans('common.tiktok_placeholder'),
                        'help' => trans('common.social_url_help'),
                        'validate' => ['nullable', 'url', 'max:255'],
                        'actions' => ['edit', 'view'],
                    ],
                    'social_links.facebook' => [
                        'text' => trans('common.facebook'),
                        'type' => 'string',
                        'format' => 'url',
                        'icon' => 'facebook',
                        'placeholder' => trans('common.facebook_placeholder'),
                        'help' => trans('common.social_url_help'),
                        'validate' => ['nullable', 'url', 'max:255'],
                        'actions' => ['edit', 'view'],
                    ],
                    'social_links.twitter' => [
                        'text' => trans('common.x_twitter'),
                        'type' => 'string',
                        'format' => 'url',
                        'icon' => 'twitter',
                        'placeholder' => trans('common.twitter_placeholder'),
                        'help' => trans('common.social_url_help'),
                        'validate' => ['nullable', 'url', 'max:255'],
                        'actions' => ['edit', 'view'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 4: HOURS - Opening Times
            // ═════════════════════════════════════════════════════════════
            'tab4' => [
                'title' => trans('common.hours'),
                'icon' => 'clock',
                'fields' => [
                    'opening_hours' => [
                        'text' => trans('common.opening_hours'),
                        'type' => 'opening_hours',
                        'help' => trans('common.opening_hours_help'),
                        'default' => [
                            'monday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
                            'tuesday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
                            'wednesday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
                            'thursday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
                            'friday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
                            'saturday' => ['open' => '10:00', 'close' => '16:00', 'closed' => false],
                            'sunday' => ['open' => '10:00', 'close' => '16:00', 'closed' => true],
                        ],
                        'validate' => ['nullable', 'array'],
                        'actions' => ['edit', 'view'],
                    ],
                ],
            ],
        ];

        // Define the general settings for the data definition
        $this->settings = [
            // Icon
            'icon' => 'building-2',
            // Title (plural of subject)
            'title' => null,
            // Override title (this title is shown in every view)
            'overrideTitle' => trans('common.business_settings'),
            // Guard of user that manages this data
            'guard' => 'partner',
            // Used for updating forms like profile, where user has to enter current password
            'editRequiresPassword' => false,
            // Modern passwordless verification using OTP sent to email
            // FALSE - Business settings don't require verification (quick updates)
            'editRequiresOtp' => false,
            // If true, the visitor is redirected to the edit form
            'redirectListToEdit' => true,
            // This column has to match auth($guard)->user()->id if 'redirectListToEdit' == true
            'redirectListToEditColumn' => 'id',
            // If true, the user id must match the created_by field
            'userMustOwnRecords' => false,
            // Should there be checkboxes for all rows
            'multiSelect' => false,
            // Default items per page for pagination
            'itemsPerPage' => 10,
            // Order by column
            'orderByColumn' => 'id',
            // Order direction, 'asc' or 'desc'
            'orderDirection' => 'desc',
            // Specify if the 'updated_by' column needs to be set
            'updatedBy' => false,
            // Possible actions for the data
            'actions' => [
                'subject_column' => 'name',
                'list' => false,
                'insert' => false,
                'edit' => true,
                'delete' => false,
                'view' => false,
                'export' => false,
            ],
        ];
    }

    /**
     * Do not modify below this line.
     *
     * ---------------------------------
     */

    /**
     * Retrieve data based on fields.
     */
    public function getData(?string $dateDefinitionName = null, string $dateDefinitionView = 'list', array $options = [], ?Model $model = null, array $settings = [], array $fields = []): array
    {
        return parent::getData($this->name, $dateDefinitionView, $options, $this->model, $this->settings, $this->fields);
    }

    /**
     * Parse settings.
     */
    public function getSettings(array $settings): array
    {
        return parent::getSettings($this->settings);
    }
}
