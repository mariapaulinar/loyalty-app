<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Validates installation form data with conditional rules based on
 * the selected mail driver and database connection type.
 */

namespace App\Http\Requests\Install;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PostInstallRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $mailDriver = $this->input('MAIL_MAILER', 'smtp');

        $rules = [
            // App settings
            'APP_NAME' => 'required|max:64',
            'APP_LOGO' => 'nullable',
            'APP_LOGO_DARK' => 'nullable',
            'APP_DEMO' => 'nullable|in:true,false',

            // Admin credentials
            'ADMIN_NAME' => 'required|max:64',
            'ADMIN_MAIL' => 'required|email|max:64',
            'ADMIN_PASS' => 'required|min:8|max:64|required_with:ADMIN_PASS_CONFIRM|same:ADMIN_PASS_CONFIRM',
            'ADMIN_PASS_CONFIRM' => 'required|min:8|max:64',
            'ADMIN_TIMEZONE' => 'required|timezone',

            // Mail basics (always required)
            'MAIL_FROM_ADDRESS' => 'required|email',
            'MAIL_FROM_NAME' => 'required|max:64',
            'MAIL_MAILER' => 'required|in:smtp,mailgun,ses,postmark,resend,sendmail,mailpit,log',

            // Database settings
            'DB_CONNECTION' => 'required|in:sqlite,mysql',
            'DB_HOST' => 'nullable|required_if:DB_CONNECTION,mysql',
            'DB_PORT' => 'nullable|numeric|required_if:DB_CONNECTION,mysql',
            'DB_DATABASE' => 'nullable|required_if:DB_CONNECTION,mysql',
            'DB_USERNAME' => 'nullable|required_if:DB_CONNECTION,mysql',
            'DB_PASSWORD' => 'nullable',
        ];

        // Add conditional mail rules based on driver
        $rules = array_merge($rules, $this->getMailDriverRules($mailDriver));

        return $rules;
    }

    /**
     * Get validation rules specific to the selected mail driver.
     *
     * @return array<string, mixed>
     */
    private function getMailDriverRules(string $driver): array
    {
        return match ($driver) {
            'smtp' => [
                'MAIL_HOST' => 'required|string|max:255',
                'MAIL_PORT' => 'required|numeric|min:1|max:65535',
                'MAIL_USERNAME' => 'nullable|string|max:255',
                'MAIL_PASSWORD' => 'nullable|string|max:255',
                'MAIL_ENCRYPTION' => 'nullable|in:tls,ssl,null',
            ],
            'mailgun' => [
                'MAILGUN_DOMAIN' => 'required|string|max:255',
                'MAILGUN_SECRET' => 'required|string|max:255',
                'MAILGUN_ENDPOINT' => 'nullable|string|in:api.mailgun.net,api.eu.mailgun.net',
            ],
            'ses' => [
                'AWS_ACCESS_KEY_ID' => 'required|string|max:255',
                'AWS_SECRET_ACCESS_KEY' => 'required|string|max:255',
                'AWS_DEFAULT_REGION' => 'required|string|max:50',
            ],
            'postmark' => [
                'POSTMARK_TOKEN' => 'required|string|max:255',
            ],
            'resend' => [
                'RESEND_KEY' => 'required|string|max:255',
            ],
            // sendmail, mailpit, log don't need extra fields
            default => [],
        };
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'APP_NAME' => 'app name',
            'ADMIN_NAME' => 'admin name',
            'ADMIN_PASS' => 'admin password',
            'ADMIN_PASS_CONFIRM' => 'admin password confirmation',
            'ADMIN_MAIL' => 'admin email',
            'MAIL_FROM_ADDRESS' => 'from email address',
            'MAIL_FROM_NAME' => 'from name',
            'MAIL_HOST' => 'SMTP host',
            'MAIL_PORT' => 'SMTP port',
            'MAIL_USERNAME' => 'SMTP username',
            'MAIL_PASSWORD' => 'SMTP password',
            'MAILGUN_DOMAIN' => 'Mailgun domain',
            'MAILGUN_SECRET' => 'Mailgun API key',
            'AWS_ACCESS_KEY_ID' => 'AWS Access Key',
            'AWS_SECRET_ACCESS_KEY' => 'AWS Secret Key',
            'AWS_DEFAULT_REGION' => 'AWS Region',
            'POSTMARK_TOKEN' => 'Postmark API token',
            'RESEND_KEY' => 'Resend API key',
            'DB_HOST' => 'database host',
            'DB_PORT' => 'database port',
            'DB_DATABASE' => 'database name',
            'DB_USERNAME' => 'database username',
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'MAIL_HOST.required' => 'Please enter the SMTP server address.',
            'MAIL_PORT.required' => 'Please enter the SMTP port (usually 587 for TLS).',
            'MAILGUN_DOMAIN.required' => 'Please enter your Mailgun sending domain.',
            'MAILGUN_SECRET.required' => 'Please enter your Mailgun API key.',
            'AWS_ACCESS_KEY_ID.required' => 'Please enter your AWS Access Key ID.',
            'AWS_SECRET_ACCESS_KEY.required' => 'Please enter your AWS Secret Access Key.',
            'AWS_DEFAULT_REGION.required' => 'Please select your AWS region.',
            'POSTMARK_TOKEN.required' => 'Please enter your Postmark Server API Token.',
            'RESEND_KEY.required' => 'Please enter your Resend API key.',
        ];
    }

    /**
     * Return json response on validation failure.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
