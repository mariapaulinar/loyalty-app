<?php

namespace App\View\Components\Forms;

use Illuminate\View\Component;

/**
 * Class Textarea
 *
 * Represents a textarea input in a form.
 */
class Textarea extends Component
{
    // Public properties for the component
    public $form;

    public $type;

    public $label;

    public $name;

    public $nameToDotNotation;

    public $text;

    public $help;

    public $id;

    public $placeholder;

    public $icon;

    public $value;

    public $required;

    public $autofocus;

    public $class;

    public $classLabel;

    public $rightText;

    public $rightLink;

    public $rightPosition;

    public $ai;

    /**
     * Create a new component instance.
     *
     * @param  array  $form  The form data.
     * @param  string  $type  The input type. Default is 'textarea'.
     * @param  string|null  $label  The label text. Default is null.
     * @param  string|null  $name  The input name. Default is null.
     * @param  string|null  $nameToDotNotation  The input name with dot notation. Default is null.
     * @param  string|null  $text  The text below the input. Default is null.
     * @param  string|null  $help  The tooltip balloon. Default is null.
     * @param  string|null  $id  The input id. If not provided, it will be generated based on the name.
     * @param  string|null  $placeholder  The input placeholder. Default is null.
     * @param  string|null  $icon  The input icon. Default is null.
     * @param  string|null  $value  The input value. Default is null.
     * @param  bool  $required  Whether the input is required. Default is false.
     * @param  bool  $autofocus  Whether the input should be autofocused. Default is false.
     * @param  string|null  $class  The input class. Default is null.
     * @param  string|null  $classLabel  The label class. Default is null.
     * @param  string|null  $rightText  The optional text/link right-aligned in the label. Default is null.
     * @param  string|null  $rightLink  The optional text/link right-aligned in the label. Default is null.
     * @param  string  $rightPosition  The position of the right text, 'top' or 'bottom'. Default is 'top'.
     * @param  array|null  $ai  AI related settings.
     */
    public function __construct(
        $form = [],
        $type = 'textarea',
        $label = null,
        $name = null,
        $nameToDotNotation = null,
        $text = null,
        $help = null,
        $id = null,
        $placeholder = null,
        $icon = null,
        $value = null,
        $required = false,
        $autofocus = false,
        $class = null,
        $classLabel = null,
        $rightText = null,
        $rightLink = null,
        $rightPosition = 'top',
        $ai = null
    ) {
        $this->form = $form;
        $this->type = $type;
        $this->label = $label;
        $this->name = $name;
        $this->nameToDotNotation = str_replace(['[', ']'], ['.', ''], $this->name);
        $this->text = $text;
        $this->help = $help;
        $this->id = $id ?? $this->nameToDotNotation;
        $this->placeholder = $placeholder;
        $this->icon = $icon;
        $this->value = old($this->nameToDotNotation, $value);
        $this->required = $required;
        $this->autofocus = $autofocus;
        $this->class = $class;
        $this->classLabel = $classLabel;
        $this->rightText = $rightText;
        $this->rightLink = $rightLink;
        $this->rightPosition = $rightPosition;
        $this->ai = (config('openai.enabled') && config('openai.api_key') && isset($ai['enabled']) && $ai['enabled']) ? $ai : null;

        if ($this->required && $this->label) {
            $this->label .= '&nbsp;*';
        }
    }

    /**
     * Get the view or contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.forms.textarea');
    }
}
