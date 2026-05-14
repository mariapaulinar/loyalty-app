<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;

/**
 * Class Button
 *
 * Represents a button component in the UI.
 */
class Button extends Component
{
    // Public properties for the component
    public string $type;

    public ?string $text;

    public ?string $href;

    public ?string $icon;

    /**
     * Create a new component instance.
     *
     * @param  string  $type  The button type. Default is 'pink-orange'.
     * @param  string|null  $text  The button text. Default is null.
     * @param  string|null  $href  The button href. Default is null.
     * @param  string|null  $icon  The button icon. Default is null.
     */
    public function __construct(
        string $type = 'pink-orange',
        ?string $text = null,
        ?string $href = null,
        ?string $icon = null
    ) {
        $this->type = $type;
        $this->text = $text;
        $this->href = $href;
        $this->icon = $icon;
    }

    /**
     * Get the view or contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.ui.button');
    }
}
