<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * TipTap WYSIWYG Editor Component
 *
 * Purpose:
 * Provides a rich text editor for email campaigns.
 * Partners can format text without knowing HTML.
 *
 * Features:
 * - Bold, Italic, Links, Lists
 * - Clean output for email compatibility
 * - Matches design system styling
 */

namespace App\View\Components\Forms;

use Illuminate\View\Component;

class Tiptap extends Component
{
    /**
     * The input name attribute.
     */
    public string $name;

    /**
     * The name converted to dot notation for error handling.
     */
    public string $nameToDotNotation;

    /**
     * The field label.
     */
    public ?string $label;

    /**
     * The initial content value.
     */
    public ?string $value;

    /**
     * Help text shown below the editor.
     */
    public ?string $help;

    /**
     * Placeholder text when editor is empty.
     */
    public ?string $placeholder;

    /**
     * Whether the field is required.
     */
    public bool $required;

    /**
     * Additional CSS classes.
     */
    public ?string $class;

    /**
     * Unique ID for the component.
     */
    public string $id;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $name,
        ?string $label = null,
        ?string $value = null,
        ?string $help = null,
        ?string $placeholder = null,
        bool $required = false,
        ?string $class = null,
        ?string $id = null
    ) {
        $this->name = $name;
        $this->nameToDotNotation = str_replace(['[', ']'], ['.', ''], $name);
        $this->label = $label;
        // Ensure value is always a string (old() can return null even with default)
        $this->value = old($this->nameToDotNotation, $value ?? '') ?? '';
        $this->help = $help;
        $this->placeholder = $placeholder;
        $this->required = $required;
        $this->class = $class;
        $this->id = $id ?? 'tiptap-'.str_replace(['.', '[', ']'], '-', $this->nameToDotNotation);

        // Add required indicator to label
        if ($this->required && $this->label) {
            $this->label .= '&nbsp;*';
        }
    }

    /**
     * Get the view that represents the component.
     */
    public function render()
    {
        return view('components.forms.tiptap');
    }
}
