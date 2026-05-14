<?php

namespace App\View\Components\Forms;

use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * Class Checkbox
 *
 * Represents a checkbox input in a form.
 */
class Checkbox extends Component
{
    // Public properties for the component
    public $type;

    public $label;

    public $name;

    public $text;

    public $help;

    public $id;

    public $value;

    public $checked;

    public $autofocus;

    public $class;

    public $model;

    /**
     * Checkbox constructor.
     *
     * @param  string  $type  The input type. Default is 'check'.
     * @param  string|null  $label  The label text. Default is null.
     * @param  string|null  $name  The input name. Default is null.
     * @param  string|null  $text  The text below the input. Default is null.
     * @param  string|null  $help  The help popover. Default is null.
     * @param  string|null  $id  The input id. If not provided, it will be generated based on the name. Default is null.
     * @param  string  $value  The input value. Default is '0'.
     * @param  bool  $checked  Whether the checkbox is checked. Default is false.
     * @param  bool  $autofocus  Whether the checkbox should be autofocused. Default is false.
     * @param  string|null  $class  The input class. Default is null.
     * @param  string|null  $model  The model (Alpine.js x-model). Default is null.
     */
    public function __construct(
        $type = 'check',
        $label = null,
        $name = null,
        $text = null,
        $help = null,
        $id = null,
        $value = '0',
        $checked = false,
        $autofocus = false,
        $class = null,
        $model = null
    ) {
        $this->type = $type;
        $this->label = $label;
        $this->name = $name;
        $this->text = $text;
        $this->help = $help;
        $this->id = $id ?? Str::slug($name, '_').'-'.uniqid();
        $this->value = $value;
        $this->checked = old($name, $checked);
        $this->autofocus = $autofocus;
        $this->class = $class;
        $this->model = $model;
    }

    /**
     * Get the view or contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.forms.checkbox');
    }
}
