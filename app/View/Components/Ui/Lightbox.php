<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Class Lightbox
 *
 * Represents a lightbox component in the UI.
 */
class Lightbox extends Component
{
    /**
     * Create a new Lightbox component instance.
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
        return view('components.ui.lightbox');
    }
}
