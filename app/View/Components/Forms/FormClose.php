<?php

namespace App\View\Components\Forms;

use Illuminate\View\Component;

/**
 * Class FormClose
 *
 * Represents a closing form tag in a view.
 */
class FormClose extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        // No initialization required for this component
    }

    /**
     * Get the view or contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.forms.form-close');
    }
}
