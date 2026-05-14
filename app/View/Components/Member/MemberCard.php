<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\View\Components\Member;

use App\Models\Club;
use App\Models\Member;
use App\Models\MemberTier;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Class MemberCard
 *
 * Represents a member card component in a view.
 * Displays member info with optional tier status badge.
 */
class MemberCard extends Component
{
    public ?Member $member;

    public ?Club $club;

    public ?MemberTier $memberTier;

    public bool $showTier;

    /**
     * Create a new component instance.
     *
     * @param  Member|null  $member  The member model.
     * @param  Club|null  $club  The club context for tier display.
     * @param  bool  $showTier  Whether to show tier badge.
     */
    public function __construct(?Member $member = null, ?Club $club = null, bool $showTier = true)
    {
        $this->member = $member;
        $this->club = $club;
        $this->showTier = $showTier;
        $this->memberTier = null;

        // Fetch tier info if requested and member/club are available
        if ($this->showTier && $this->member && $this->club) {
            $this->memberTier = $this->member->memberTiers()
                ->forClub($this->club)
                ->active()
                ->with('tier')
                ->first();
        }
    }

    /**
     * Get the view or contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.member.member-card');
    }
}
