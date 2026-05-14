<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Class Tabs
 *
 * Represents a tabs component in the UI.
 */
class Tabs extends Component
{
    // Public properties for the component
    public $tabs;

    public $activeTab;

    public $tabClass;

    public $style;

    /**
     * Create a new Tabs component instance.
     *
     * @param  array|null  $tabs  The tabs. Default is null.
     * @param  int|null  $activeTab  The active tab (1 to n). Default is null.
     * @param  string|null  $tabClass  The tab class. Default is null.
     * @param  string|null  $style  The style class. Default is 'tabs-underline'.
     */
    public function __construct(
        ?array $tabs = null,
        ?int $activeTab = null,
        ?string $tabClass = null,
        ?string $style = null
    ) {
        $this->tabs = $tabs;
        $this->activeTab = $activeTab;
        $this->tabClass = $tabClass;
        $this->style = $style ?? 'tabs-underline';
    }

    /**
     * Get the view or contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.tabs');
    }
}
