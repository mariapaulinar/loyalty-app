<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Class Breadcrumb
 *
 * Represents a breadcrumb navigation component.
 * Supports both text and icon-only crumbs.
 */
class Breadcrumb extends Component
{
    /**
     * The breadcrumb crumbs.
     */
    public ?array $crumbs;

    /**
     * Create a new component instance.
     *
     * @param  array|null  $crumbs  The breadcrumb crumbs. Each crumb can have:
     *                              - 'url': Link URL (optional)
     *                              - 'text': Display text (optional)
     *                              - 'icon': Icon name (optional)
     *                              - 'title': Tooltip/title attribute (optional)
     *                              - 'target': Link target (optional, e.g., '_blank')
     */
    public function __construct(?array $crumbs = null)
    {
        // Pass through as-is - let the Blade template handle rendering
        $this->crumbs = $crumbs;
    }

    /**
     * Get the view or contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.breadcrumb');
    }
}
