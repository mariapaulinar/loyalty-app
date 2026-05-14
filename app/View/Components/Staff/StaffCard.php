<?php

namespace App\View\Components\Staff;

use App\Models\Staff;
use App\Models\Transaction;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Laravolt\Avatar\Facade as Avatar;

/**
 * Class StaffCard
 *
 * Represents a staff card component in a view.
 */
class StaffCard extends Component
{
    // Public properties for the component
    public $staff;

    public $transaction;

    public $avatar;

    /**
     * Create a new component instance.
     *
     * @param  Staff|null  $staff  The staff model. Default is null.
     * @param  Transaction|null  $transaction  The transaction model. Default is null.
     */
    public function __construct(?Staff $staff = null, ?Transaction $transaction = null)
    {
        $this->staff = $staff;
        $this->transaction = $transaction;
        $this->avatar = $staff->avatar ?: Avatar::create($this->getStaffName())->toBase64();
    }

    /**
     * Get the name of the staff for generating the avatar.
     */
    protected function getStaffName(): string
    {
        if ($this->transaction) {
            return parse_attr($this->transaction->staff_name);
        }

        return parse_attr($this->staff->name);
    }

    /**
     * Get the view or contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.staff.staff-card');
    }
}
