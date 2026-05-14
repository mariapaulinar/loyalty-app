<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Premium Card Component
 *
 * A premium credit card experience with 3D effects, holographic shine,
 * and buttery-smooth animations—while preserving all existing parameters.
 *
 * Inspired by Apple Wallet, Revolut, and premium physical credit cards.
 */

namespace App\View\Components\Member;

use App\Services\TierService;
use Carbon\Carbon;
use Illuminate\View\Component;

class PremiumCard extends Component
{
    // All existing parameters preserved
    public $card;

    public $member;

    public $id;

    public $flippable;

    public $links;

    public $showQr;

    public $showBalance;

    public $customLink;

    public $element_id;

    public $type;

    public $icon;

    public $bgImage;

    public $bgColor;

    public $bgColorOpacity;

    public $textColor;

    public $textLabelColor;

    public $qrColorLight;

    public $qrColorDark;

    public $balance;

    public $logo;

    public $contentHead;

    public $contentTitle;

    public $contentDescription;

    public $identifier;

    public $issueDate;

    public $expirationDate;

    public $authCheck;

    public $urlToEarnPoints;

    public $isExpired;

    public $detailView;

    public $memberTier;

    public $tierIcon;

    public $tierColor;

    public $tierMultiplier;

    /**
     * Create a new premium card component instance.
     *
     * Accepts all existing card parameters for backward compatibility.
     */
    public function __construct(
        $card = null,
        $member = null,
        $id = null,
        $flippable = false,
        $links = true,
        $showQr = true,
        $showBalance = true,
        $customLink = null,
        $element_id = null,
        $type = 'loyalty',
        $icon = null,
        $bgImage = null,
        $bgColor = null,
        $bgColorOpacity = null,
        $textColor = null,
        $textLabelColor = null,
        $qrColorLight = null,
        $qrColorDark = null,
        $balance = 0,
        $logo = null,
        $contentHead = null,
        $contentTitle = null,
        $contentDescription = null,
        $identifier = null,
        $issueDate = null,
        $expirationDate = null,
        $detailView = false
    ) {
        // Initialize all properties exactly as the original component
        $this->card = $card;
        $this->member = $member ?? auth('member')->user();
        $this->id = $id ?? $card->id;
        $this->flippable = $flippable;
        $this->links = $links;
        $this->showQr = $showQr;
        $this->showBalance = $showBalance;
        $this->customLink = $customLink;
        $this->element_id = $element_id ?? 'premium_card_'.unique_code(12);
        $this->type = $type ?? $card->type;
        $this->icon = $icon ?? $card->icon;
        $this->bgImage = $bgImage ?? $card->getImageUrl('background', 'sm');
        $this->bgColor = $bgColor ?? $card->bg_color;
        $this->bgColorOpacity = $bgColorOpacity ?? $card->bg_color_opacity;
        $this->textColor = $textColor ?? $card->text_color;
        $this->balance = $balance;
        $this->logo = $logo ?? $card->getImageUrl('logo', 'md');
        $this->contentHead = $contentHead ?? $card->head;
        $this->contentTitle = $contentTitle ?? $card->title;
        $this->contentDescription = $contentDescription ?? $card->description;
        $this->identifier = $identifier ?? $card->unique_identifier;
        $this->issueDate = $issueDate ?? $card->issue_date;
        if ($this->issueDate) {
            $partner = null;
            if ($this->card && method_exists($this->card, 'relationLoaded') && $this->card->relationLoaded('partner')) {
                $partner = $this->card->getRelation('partner');
            }

            $timezone = $partner?->time_zone ?? config('app.timezone');
            $this->issueDate = Carbon::parse($this->issueDate, 'UTC')->setTimezone($timezone)->format('Y-m-d H:i:s');
        }

        $this->expirationDate = $expirationDate ?? $card->expiration_date;
        if ($this->expirationDate) {
            $partner = null;
            if ($this->card && method_exists($this->card, 'relationLoaded') && $this->card->relationLoaded('partner')) {
                $partner = $this->card->getRelation('partner');
            }

            $timezone = $partner?->time_zone ?? config('app.timezone');
            $this->expirationDate = Carbon::parse($this->expirationDate, 'UTC')->setTimezone($timezone)->format('Y-m-d H:i:s');
            $this->isExpired = Carbon::parse($this->expirationDate)->lt(Carbon::now());
        } else {
            $this->isExpired = false;
        }

        $this->authCheck = isset($this->member);
        $this->detailView = $detailView;

        // Calculate balance if needed
        if ($this->showBalance) {
            $this->balance = $this->member ? $card->getMemberBalance($this->member) : $card->getMemberBalance(null);
        }

        $this->urlToEarnPoints = ($this->authCheck && $this->identifier) ? route('staff.earn.points', ['member_identifier' => $this->member->unique_identifier, 'card_identifier' => $this->identifier]) : '';

        // Fetch member's tier for this card's club
        $this->memberTier = null;
        $this->tierIcon = null;
        $this->tierColor = null;
        $this->tierMultiplier = 1.00;

        $club = null;
        if ($this->card && method_exists($this->card, 'relationLoaded') && $this->card->relationLoaded('club')) {
            $club = $this->card->getRelation('club');
        }

        if ($this->member && $this->card && $club) {
            $activeTier = $this->member->memberTiers()
                ->forClub($club)
                ->active()
                ->with('tier')
                ->first();

            // Only show tier if member has actually earned points in this club
            if ($activeTier && $activeTier->tier) {
                // Use TierService to get lifetime points (correct way to check tier qualification)
                $tierService = app(TierService::class);
                $qualifyingStats = $tierService->getQualifyingStats($this->member, $club);
                $lifetimePoints = $qualifyingStats['lifetime_points'] ?? 0;

                // Only display tier if member has earned points (not just default tier assignment)
                if ($lifetimePoints > 0) {
                    $this->memberTier = $activeTier;
                    $this->tierIcon = $activeTier->tier->icon ?? '🥉';
                    $this->tierColor = $activeTier->tier->color ?? '#2563EB';
                    $this->tierMultiplier = $activeTier->tier->points_multiplier ?? 1.00;
                }
            }
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.member.premium-card');
    }
}
