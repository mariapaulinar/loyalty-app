<?php

namespace App\View\Components\Member;

use App\Models\Card;
use App\Models\Reward;
use Illuminate\View\Component;

/**
 * Class Rewards
 *
 * Represents the member's reward information.
 */
class Rewards extends Component
{
    // Public properties for the component
    public $card;

    public $currentReward;

    public $showClaimable;

    /**
     * Create a new component instance.
     *
     * @param  Card|null  $card  The card model.
     * @param  Reward|null  $currentReward  The currently active reward model.
     * @param  bool  $showClaimable  Indicates if a member has enough points to claim a reward.
     */
    public function __construct(?Card $card = null, ?Reward $currentReward = null, bool $showClaimable = false)
    {
        $this->card = $card;
        $this->currentReward = $currentReward;
        $this->showClaimable = $showClaimable;
    }

    /**
     * Get the view or contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.member.rewards');
    }
}
