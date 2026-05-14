<?php

namespace App\View\Components\Member;

use App\Models\Card as CardModel;
use App\Models\Member as MemberModel;
use Carbon\Carbon;
use Illuminate\View\Component;

/**
 * Class Card
 *
 * Represents a member card in a view.
 */
class Card extends Component
{
    // Public properties for the component
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

    public $hideLogin;

    /**
     * Create a new component instance.
     *
     * @param  CardModel|null  $card
     * @param  MemberModel|null  $member
     * @param  int|null  $id
     * @param  bool  $flippable
     * @param  bool  $links
     * @param  bool  $showQr
     * @param  bool  $showBalance
     * @param  string|null  $customLink
     * @param  string|null  $element_id
     * @param  string  $type
     * @param  string|null  $icon
     * @param  string|null  $bgImage
     * @param  string|null  $bgColor
     * @param  string|null  $bgColorOpacity
     * @param  string|null  $textColor
     * @param  string|null  $textLabelColor
     * @param  string|null  $qrColorLight
     * @param  string|null  $qrColorDark
     * @param  int  $balance
     * @param  string|null  $logo
     * @param  string|null  $contentHead
     * @param  string|null  $contentTitle
     * @param  string|null  $contentDescription
     * @param  string|null  $identifier
     * @param  string|null  $issueDate
     * @param  string|null  $expirationDate
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
        $authCheck = null,
        $hideLogin = false
    ) {
        $this->card = $card;
        $this->member = $member ?? auth('member')->user();
        $this->id = $id ?? $card->id;
        $this->flippable = $flippable;
        $this->links = $links;
        $this->showQr = $showQr;
        $this->showBalance = $showBalance;
        $this->customLink = $customLink;
        $this->element_id = $element_id ?? 'card_'.unique_code(12);
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
        $this->issueDate = Carbon::parse($this->issueDate, 'UTC')->setTimezone($this->card->partner->time_zone)->format('Y-m-d H:i:s');
        $this->expirationDate = $expirationDate ?? $card->expiration_date;
        $this->expirationDate = Carbon::parse($this->expirationDate, 'UTC')->setTimezone($this->card->partner->time_zone)->format('Y-m-d H:i:s');
        // Allow authCheck to be explicitly set, otherwise determine from member
        $this->authCheck = $authCheck ?? isset($this->member);
        $this->hideLogin = $hideLogin;
        if ($this->showBalance) {
            $this->balance = $this->member ? $card->getMemberBalance($this->member) : $card->getMemberBalance(null);
        }
        $this->urlToEarnPoints = $this->authCheck && $this->member ? route('staff.earn.points', ['member_identifier' => $this->member->unique_identifier, 'card_identifier' => $this->identifier]) : '';
        $this->isExpired = Carbon::parse($this->expirationDate)->lt(Carbon::now());
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.member.card');
    }
}
