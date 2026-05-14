<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Class Toast
 *
 * Represents a toast notification component in the UI.
 */
class Toast extends Component
{
    /**
     * Create a new Toast component instance.
     */
    public function __construct()
    {
        // No initialization required for this component
    }

    /**
     * Get the view or contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.toast');
    }
}
