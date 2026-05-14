<?php

namespace App\View\Components\Member;

use App\Models\Card;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Class CardContact
 *
 * Represents a card contact component in a view.
 */
class CardContact extends Component
{
    // Public properties for the component
    public $card;

    public $buttons;

    /**
     * Create a new component instance.
     *
     * @param  Card  $card  The card model.
     */
    public function __construct(Card $card)
    {
        $this->card = $card;
        $this->buttons = [];

        if ($card->meta['website'] ?? null) {
            $this->buttons[] = [
                'text' => trans('common.website'),
                'icon' => 'link',
                'url' => $card->meta['website'],
                'attr' => ['target' => '_blank', 'rel' => 'nofollow'],
            ];
        }

        if ($card->meta['route'] ?? null) {
            $this->buttons[] = [
                'text' => trans('common.route'),
                'icon' => 'map-pin',
                'url' => $card->meta['route'],
                'attr' => ['target' => '_blank', 'rel' => 'nofollow'],
            ];
        }

        if ($card->meta['phone'] ?? null) {
            $this->buttons[] = [
                'text' => trans('common.call'),
                'icon' => 'phone',
                'url' => 'tel:'.$card->meta['phone'],
                'attr' => ['rel' => 'nofollow'],
            ];
        }
    }

    /**
     * Get the view or contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.member.card-contact');
    }
}
