<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Partner Registration Request
 *
 * Validates partner registration form data.
 */

namespace App\Http\Requests\Partner;

use App\Services\Partner\AuthService;
use Illuminate\Foundation\Http\FormRequest;

class RegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow if registration is enabled
        return AuthService::isRegistrationEnabled();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim($this->email ?? '')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                app()->environment('testing') ? 'email:rfc' : 'email:rfc,dns',
                'max:128',
                'unique:partners,email',
            ],
            'name' => [
                'required',
                'string',
                'min:2',
                'max:128',
            ],
            'time_zone' => [
                'nullable',
                'string',
                'max:48',
            ],
            'consent' => [
                'required',
                'boolean',
                'accepted',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => trans('common.email_already_registered'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => strtolower(trans('common.email_address')),
            'name' => strtolower(trans('common.business_name')),
            'consent' => strtolower(trans('common.agreement')),
        ];
    }
}
