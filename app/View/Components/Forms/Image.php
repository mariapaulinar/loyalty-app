<?php

namespace App\View\Components\Forms;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * Class Image
 *
 * Represents an image input in a form.
 */
class Image extends Component
{
    // Public properties for the component
    public $type;

    public $label;

    public $class;

    public $name;

    public $text;

    public $help;

    public $id;

    public $placeholder;

    public $height;

    public $icon;

    public $default;

    public $value;

    public $required;

    public $accept;

    public $validate;

    public $requirements;

    /**
     * Image constructor.
     *
     * @param  string  $type  The input type. Default is 'image'.
     * @param  string|null  $label  The label text. Default is null.
     * @param  string|null  $class  The input class. Default is null.
     * @param  string|null  $name  The input name. Default is null.
     * @param  string|null  $text  The text below the input. Default is null.
     * @param  string|null  $help  The tooltip balloon. Default is null.
     * @param  string|null  $id  The input id. If not provided, it will be generated based on the name. Default is null.
     * @param  string|null  $placeholder  The input placeholder. Default is null.
     * @param  string|null  $height  The Tailwind height class for the element. Default is null.
     * @param  string|null  $icon  The input icon. Default is null.
     * @param  string|null  $default  Default input value. Default is null.
     * @param  string|null  $value  The input value. Default is null.
     * @param  bool  $required  Whether the input is required. Default is false.
     * @param  string|false  $accept  The accepted MIME types. Default is false.
     * @param  array  $validate  The validation array. Default is an empty array.
     * @param  string  $requirements  The requirements to display at file upload. Default is an empty string.
     */
    public function __construct(
        $type = 'image',
        $label = null,
        $class = null,
        $name = null,
        $text = null,
        $help = null,
        $id = null,
        $placeholder = null,
        $height = null,
        $icon = null,
        $default = null,
        $value = null,
        $required = false,
        $accept = false,
        $validate = [],
        $requirements = ''
    ) {
        $this->type = $type;
        $this->label = $label;
        $this->class = $class;
        $this->name = $name;
        $this->text = $text;
        $this->help = $help;
        $this->id = $id ?? Str::slug($name, '_').'-'.uniqid();
        $this->placeholder = $placeholder;
        $this->height = $height;
        $this->icon = $icon;
        $this->default = $default;
        $this->value = old($name, $value) ?: $value;
        $this->value = $this->value ?? $this->default;
        $this->required = $required;
        if ($this->required) {
            $this->label .= ' *';
        }
        $this->accept = $accept;
        $this->validate = $validate;
        $this->requirements = ($this->validate) ? $this->processImageValidationRules($this->validate) : null;
    }

    /**
     * Process image validation rules and generate a human-readable requirements string.
     *
     * @param  array  $validate  An array of validation rules.
     * @return string A human-readable requirements string.
     */
    private function processImageValidationRules(array $validate): string
    {
        $mimes = '';
        $maxWidth = '';
        $maxHeight = '';
        $maxSizeInKb = '';
        foreach ($validate as $validation) {
            if (Str::startsWith($validation, 'mimes:')) {
                $mimes = explode(',', Str::replaceFirst('mimes:', '', $validation));
                $lastMime = array_pop($mimes);
                $mimes = implode(', ', $mimes).' '.strtolower(trans('common.or')).' '.$lastMime;
            }

            if (Str::startsWith($validation, 'dimensions:')) {
                $dimensions = explode(',', Str::replaceFirst('dimensions:', '', $validation));
                foreach ($dimensions as $dimension) {
                    if (Str::startsWith($dimension, 'max_width=')) {
                        $maxWidth = Str::replaceFirst('max_width=', '', $dimension);
                    }
                    if (Str::startsWith($dimension, 'max_height=')) {
                        $maxHeight = Str::replaceFirst('max_height=', '', $dimension);
                    }
                }
            }

            if (Str::startsWith($validation, 'max:')) {
                $maxSizeInKb = Str::replaceFirst('max:', '', $validation);
                $maxSizeInKb = formatBytes($maxSizeInKb * 1024);
            }
        }

        return "$mimes (Max. {$maxWidth}x{$maxHeight}px, $maxSizeInKb)";
    }

    /**
     * Get the view or contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.forms.image');
    }
}
