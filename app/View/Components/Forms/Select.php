<?php

namespace App\View\Components\Forms;

use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * Class Select
 *
 * Represents a select input in a form.
 */
class Select extends Component
{
    public string $type;

    public bool $multiselect;

    public ?array $options;

    public ?string $label;

    public ?string $name;

    public ?string $text;

    public ?string $help;

    public ?string $id;

    public ?string $placeholder;

    public ?string $icon;

    public $value;

    public bool $required;

    public bool $autofocus;

    public ?string $class;

    public ?string $rightText;

    public ?string $rightLink;

    public ?string $rightPosition;

    public bool $searchable;

    public ?string $searchPlaceholder;

    /**
     * Select constructor.
     *
     * @param  string  $type  The select type. Default is 'select'.
     * @param  bool  $multiselect  Whether the select is a multiselect. Default is false.
     * @param  array|null  $options  The select options. Default is an empty array.
     * @param  string|null  $label  The label text. Default is null.
     * @param  string|null  $name  The input name. Default is null.
     * @param  string|null  $text  The text below the input. Default is null.
     * @param  string|null  $help  The tooltip balloon. Default is null.
     * @param  string|null  $id  The input id. If not provided, it will be generated based on the name. Default is null.
     * @param  string|null  $placeholder  The input placeholder. Default is null.
     * @param  string|null  $icon  The input icon. Default is null.
     * @param  string|array|null  $value  The input value. Default is null.
     * @param  bool  $required  Whether the input is required. Default is false.
     * @param  bool  $autofocus  Whether the input should be autofocused. Default is false.
     * @param  string|null  $class  The input class. Default is null.
     * @param  string|null  $rightText  The optional text/link right-aligned in the label. Default is null.
     * @param  string|null  $rightLink  The optional text/link right-aligned in the label. Default is null.
     * @param  string|null  $rightPosition  The position of the right text, 'top' or 'bottom'. Default is 'top'.
     * @param  bool|null  $searchable  Whether the select has search/filter. Defaults to true for timezone/currency/locale.
     * @param  string|null  $searchPlaceholder  The search placeholder text. Default is 'Search...'.
     */
    public function __construct(
        string $type = 'select',
        bool $multiselect = false,
        ?array $options = [],
        ?string $label = null,
        ?string $name = null,
        ?string $text = null,
        ?string $help = null,
        ?string $id = null,
        ?string $placeholder = null,
        ?string $icon = null,
        $value = null,
        bool $required = false,
        bool $autofocus = false,
        ?string $class = null,
        ?string $rightText = null,
        ?string $rightLink = null,
        ?string $rightPosition = 'top',
        ?bool $searchable = null,
        ?string $searchPlaceholder = null
    ) {
        $this->type = $type;
        $this->multiselect = $multiselect;
        $this->options = $options ?? [];
        if (is_array($this->options)) {
            $this->value = array_map('intval', $this->options);
        }
        $this->label = $label;
        $this->name = $name;
        $this->text = $text;
        $this->help = $help;
        $this->id = $id ?? Str::slug($name, '_').'-'.uniqid();
        $this->placeholder = $placeholder ?? trans('common.select_option');
        $this->icon = $icon;
        $this->value = old($name, $value);
        if (is_array($this->value)) {
            $this->value = array_map('intval', $this->value);
        } elseif (is_numeric($this->value)) {
            $this->value = (string) $this->value;
        }
        $this->required = $required;
        $this->autofocus = $autofocus;
        $this->class = $class;
        $this->rightText = $rightText;
        $this->rightLink = $rightLink;
        $this->rightPosition = $rightPosition;
        
        // Auto-enable searchable for:
        // 1. Known types with many options (timezone, currency, locale)
        // 2. Any select with 7+ options (makes search useful)
        $autoSearchableTypes = ['time_zone', 'currency', 'locale'];
        $hasManualSetting = $searchable !== null;
        $hasManyOptions = count($this->options) >= 7;
        $isAutoSearchableType = in_array($type, $autoSearchableTypes);
        
        $this->searchable = $hasManualSetting 
            ? $searchable 
            : ($isAutoSearchableType || $hasManyOptions);
        $this->searchPlaceholder = $searchPlaceholder ?? trans('common.search').'...';

        if ($this->required) {
            $this->label .= ' *';
        } elseif ($type != 'belongsToMany') {
            // Add empty option
            $this->options = ['' => ''] + $this->options;
        }

        if ($this->type === 'time_zone' || $this->type === 'currency') {
            $i18nService = resolve('App\Services\I18nService');
            $data = $this->type === 'time_zone' ? $i18nService->getAllTimezones() : $i18nService->getAllCurrencies();
            $this->options += $data;
        }

        if ($this->type === 'locale') {
            $i18nService = resolve('App\Services\I18nService');
            $allTranslations = $i18nService->getAllTranslations();

            foreach ($allTranslations['all'] as $translation) {
                if (isset($translation['locale'])) {
                    $this->options += [$translation['locale'] => $translation['languageName'].' ('.$translation['countryName'].')'];
                }
            }
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|Closure|string
     */
    public function render()
    {
        return view('components.forms.select');
    }
}
