<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * PIN/OTP input component for 6-digit verification codes.
 * Provides auto-advance, paste support, and elegant animations.
 *
 * Design Tenets:
 * - **Accessible**: Full ARIA support, keyboard navigation
 * - **Intuitive**: Auto-advances, handles paste, backspace smart
 * - **Beautiful**: Revolut/Linear inspired design with smooth animations
 */

namespace App\View\Components\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PinInput extends Component
{
    /**
     * The number of digits in the PIN.
     */
    public int $length;

    /**
     * The name attribute for the hidden input that holds the full value.
     */
    public string $name;

    /**
     * Whether to auto-submit when complete.
     */
    public bool $autoSubmit;

    /**
     * Delay in milliseconds before auto-submit.
     */
    public int $autoSubmitDelay;

    /**
     * Whether the input is disabled.
     */
    public bool $disabled;

    /**
     * Whether to show loading state.
     */
    public bool $loading;

    /**
     * Error message to display.
     */
    public ?string $error;

    /**
     * Success state.
     */
    public bool $success;

    /**
     * Create a new PIN input component instance.
     */
    public function __construct(
        int $length = 6,
        string $name = 'code',
        bool $autoSubmit = true,
        int $autoSubmitDelay = 400,
        bool $disabled = false,
        bool $loading = false,
        ?string $error = null,
        bool $success = false
    ) {
        $this->length = $length;
        $this->name = $name;
        $this->autoSubmit = $autoSubmit;
        $this->autoSubmitDelay = $autoSubmitDelay;
        $this->disabled = $disabled;
        $this->loading = $loading;
        $this->error = $error;
        $this->success = $success;
    }

    /**
     * Get the view or contents that represent the component.
     */
    public function render(): View
    {
        return view('components.ui.pin-input');
    }
}
