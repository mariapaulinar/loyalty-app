<?php

namespace App\View\Components\Forms;

use Illuminate\View\Component;

/**
 * Class Input
 *
 * Represents an input field in a form.
 */
class Input extends Component
{
    // Public properties for the component
    public $form;

    public $type;

    public $label;

    public $name;

    public $nameToDotNotation;

    public $prefix;

    public $suffix;

    public $affixClass;

    public $text;

    public $help;

    public $id;

    public $placeholder;

    public $icon;

    public $value;

    public $required;

    public $autofocus;

    public $generatePassword;

    public $class;

    public $mailPassword;

    public $mailPasswordChecked;

    public $inputClass;

    public $classLabel;

    public $rightText;

    public $rightLink;

    public $rightPosition;

    public $ai;

    public $min;

    public $max;

    public $step;

    /**
     * Create a new component instance.
     *
     * @param  array  $form  Form data.
     * @param  string  $type  Input type. Default is 'text'.
     * @param  string|null  $label  Label text. Default is null.
     * @param  string|null  $name  Input name. Default is null.
     * @param  string|null  $nameToDotNotation  Input name with dot notation. Default is null.
     * @param  string|null  $prefix  Prefix. Default is null.
     * @param  string|null  $suffix  Suffix. Default is null.
     * @param  string|null  $affixClass  Class for prefix and suffix. Default is null.
     * @param  string|null  $text  Text below input. Default is null.
     * @param  string|null  $help  Tooltip balloon. Default is null.
     * @param  string|null  $id  Input id. If not provided, it will be generated based on the name.
     * @param  string|null  $placeholder  Input placeholder. Default is null.
     * @param  string|null  $icon  Input icon. Default is null.
     * @param  string|null  $value  Input value. Default is null.
     * @param  bool  $required  Whether the input is required. Default is false.
     * @param  bool  $autofocus  Whether the input should be autofocused. Default is false.
     * @param  bool  $generatePassword  Generate password button. Default is false.
     * @param  bool  $mailPassword  Add a checkbox with the option to send the user their password. Default is false.
     * @param  bool  $mailPasswordChecked  Mail password checkbox checked by default. Default is false.
     * @param  string|null  $class  Input class. Default is null.
     * @param  string|null  $inputClass  Input element class. Default is null.
     * @param  string|null  $classLabel  Label class. Default is null.
     * @param  string|null  $rightText  Optional text/link right-aligned in the label. Default is null.
     * @param  string|null  $rightLink  Optional text/link right-aligned in the label. Default is null.
     * @param  string  $rightPosition  Position of the right text, 'top' or 'bottom'. Default is 'top'.
     * @param  array|null  $ai  AI related settings. Default is null.
     */
    public function __construct(
        $form = [],
        $type = 'text',
        $label = null,
        $name = null,
        $nameToDotNotation = null,
        $prefix = null,
        $suffix = null,
        $affixClass = null,
        $text = null,
        $help = null,
        $id = null,
        $placeholder = null,
        $icon = null,
        $value = null,
        $required = false,
        $autofocus = false,
        $generatePassword = false,
        $mailPassword = false,
        $mailPasswordChecked = false,
        $class = null,
        $inputClass = null,
        $classLabel = null,
        $rightText = null,
        $rightLink = null,
        $rightPosition = 'top',
        $ai = null,
        $min = null,
        $max = null,
        $step = 1
    ) {
        $this->form = $form;
        $this->type = $type;
        $this->label = $label;
        $this->name = $name;
        $this->nameToDotNotation = str_replace(']', '', str_replace('[', '.', $this->name));
        $this->prefix = $prefix;
        $this->suffix = $suffix;
        $this->affixClass = $affixClass;
        $this->text = $text;
        $this->help = $help;
        $this->id = $id ?? $this->nameToDotNotation;
        $this->placeholder = $placeholder;
        $this->icon = $icon;
        $this->value = ($type === 'password') ? $value : old($this->nameToDotNotation, $value);
        $this->required = $required;
        $this->autofocus = $autofocus;
        $this->generatePassword = $generatePassword;
        $this->mailPassword = $mailPassword;
        $this->mailPasswordChecked = $mailPasswordChecked;
        $this->class = $class;
        $this->inputClass = $inputClass;
        $this->classLabel = $classLabel;
        $this->rightText = $rightText;
        $this->rightLink = $rightLink;
        $this->rightPosition = $rightPosition;
        $this->ai = (config('openai.enabled') && config('openai.api_key') && isset($ai['enabled']) && $ai['enabled']) ? $ai : null;
        $this->min = $min;
        $this->max = $max;
        $this->step = $step;

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
        return view('components.forms.input');
    }
}
