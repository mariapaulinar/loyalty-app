<?php

namespace App\View\Components\Forms;

use Illuminate\View\Component;

/**
 * Class Button
 *
 * Represents a button in a form, which may include a back button.
 */
class Button extends Component
{
    // Public properties for the component
    public $type;

    public $label;

    public $class;

    public $buttonClass;

    public $back;

    public $backUrl;

    public $backText;

    /**
     * Button constructor.
     *
     * @param  string  $type  The button type. Default is 'submit'.
     * @param  string|null  $label  The button text. Default is null.
     * @param  string|null  $class  The div container class. Default is null.
     * @param  string|null  $buttonClass  The button class. Default is null.
     * @param  bool  $back  Whether to show the back button. Default is false.
     * @param  string  $backUrl  The back button URL. Default is 'javascript:history.go(-1);'.
     * @param  string|null  $backText  The back button text. Default is null. Uses 'common.back' translation if null.
     */
    public function __construct(
        $type = 'submit',
        $label = null,
        $class = null,
        $buttonClass = null,
        $back = false,
        $backUrl = 'javascript:history.go(-1);',
        $backText = null
    ) {
        $this->type = $type;
        $this->label = $label;
        $this->class = $class;
        $this->buttonClass = $buttonClass;
        $this->back = $back;
        $this->backUrl = $backUrl;
        $this->backText = $backText ?? trans('common.back');
    }

    /**
     * Get the view or contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.forms.button');
    }
}
