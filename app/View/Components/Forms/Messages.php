<?php

namespace App\View\Components\Forms;

use Illuminate\View\Component;

/**
 * Class Messages
 *
 * Represents a message component in a form, typically used for displaying error messages.
 */
class Messages extends Component
{
    // Public property for the component
    public $msg;

    /**
     * Create a new component instance.
     *
     * @param  string|null  $msg  The general error message. Default is null.
     * @return void
     */
    public function __construct($msg = null)
    {
        $this->msg = $msg ?? trans('common.form_contains_errors');
    }

    /**
     * Get the view or contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.forms.messages');
    }
}
