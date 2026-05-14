<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Class Share
 *
 * Represents a share component in the UI.
 */
class Share extends Component
{
    // Public properties for the component
    public $url;

    public $text;

    /**
     * Create a new Share component instance.
     *
     * @param  string|null  $url  The URL to share. Default is the current URL.
     * @param  string|null  $text  The text to share. Default is null.
     */
    public function __construct(?string $url = null, ?string $text = null)
    {
        $this->url = $url ?? url()->full();
        $this->text = $text;
    }

    /**
     * Get the view or contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.share');
    }
}
