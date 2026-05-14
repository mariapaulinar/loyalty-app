<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\View\Components\Member;

use App\Models\Card;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Class FollowCard
 *
 * Add/Remove card from "My Cards" component.
 * Simplified logic: just checks if member follows the card.
 */
class FollowCard extends Component
{
    // Public properties for the component
    public $card;

    public $follows;

    /**
     * Create a new component instance.
     *
     * @param  Card|null  $card  The card model.
     * @param  bool  $follows  Indicates if the logged-in member follows this card.
     */
    public function __construct(?Card $card = null, bool $follows = false)
    {
        $this->card = $card;
        $this->follows = $follows;

        // Simplified logic: just check if member follows the card
        if (auth('member')->check() && $card) {
            $this->follows = $card->members()->where('members.id', auth('member')->user()->id)->exists();
        }
    }

    /**
     * Get the view or contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.member.follow-card');
    }
}
