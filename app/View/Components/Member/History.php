<?php

namespace App\View\Components\Member;

use App\Models\Card;
use App\Models\Member;
use App\Services\Card\TransactionService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Class History
 *
 * Represents a transaction history component in a view.
 */
class History extends Component
{
    // Public properties for the component
    public $card;

    public $member;

    public $transactions;

    public $showNotes;

    public $showAttachments;

    public $showStaff;

    public $showExpiredAndUsedTransactions;

    /**
     * Create a new component instance.
     *
     * @param  TransactionService  $transactionService  The service to handle transactions.
     * @param  Card|null  $card  The card model.
     * @param  Member|null  $member  The member model.
     * @param  bool|null  $showNotes  Whether to show transaction notes.
     * @param  bool|null  $showAttachments  Whether to show transaction attachments.
     * @param  bool|null  $showStaff  Whether to show staff member.
     * @param  bool|null  $showExpiredAndUsedTransactions  Whether to show transactions with points that have expired or have been fully used.
     */
    public function __construct(
        TransactionService $transactionService,
        ?Card $card = null,
        ?Member $member = null,
        ?bool $showNotes = null,
        ?bool $showAttachments = null,
        ?bool $showStaff = null,
        bool $showExpiredAndUsedTransactions = true
    ) {
        $this->card = $card;
        $this->member = $member ?? auth('member')->user();
        $this->showNotes = $showNotes ?? false;
        $this->showAttachments = $showAttachments ?? false;
        $this->showStaff = $showStaff ?? false;
        $this->showExpiredAndUsedTransactions = $showExpiredAndUsedTransactions;

        if ($this->member) {
            $this->transactions = $transactionService->findTransactionsOfMemberForCard(
                $this->member, $this->card, $this->showExpiredAndUsedTransactions
            );
        }
    }

    /**
     * Get the view or contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.member.history');
    }
}
