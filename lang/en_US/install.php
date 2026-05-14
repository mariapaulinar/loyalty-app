<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Installation
    |--------------------------------------------------------------------------
    */

    'installation' => 'Installation',
    'install' => 'Install',
    'install_script' => 'Install the Script',
    'server_requirements' => 'Server Requirements',
    'requirements' => 'Requirements',
    'server_requirements_text' => 'The following checks help determine if the script will function on your server, although complete compatibility can\'t be guaranteed.',
    'resolve_missing_requirements' => 'Resolve any missing requirements to proceed.',
    'next' => 'Next',
    'prev' => 'Previous',
    'configuration' => 'Configuration',
    'confirm' => 'Confirm',
    'app' => 'Application',
    'name' => 'Name',
    'email' => 'Email',
    'optional' => 'Optional',
    '_optional_' => '(optional)',
    'optional_email_config' => 'You may skip the settings below for now. They can be configured later in the .env file in the web root. Note: Email functionality requires these settings.',
    'logo' => 'Logo',
    'logo_dark' => 'Logo (Dark Mode)',
    'user' => 'User',
    'email_address' => 'Email Address',
    'time_zone' => 'Time Zone',
    'password' => 'Password',
    'confirm_password' => 'Confirm Password',
    'passwords_must_match' => 'Passwords must match.',
    'email_address_app' => 'Email used by the app for sending emails',
    'email_address_name_app' => 'Email Name',
    'admin_login' => 'Admin Login',
    'download_log' => 'Download Log File',
    'refresh_page' => 'Refresh this page and try again',
    'after_installation' => 'Once installation is complete, use the admin login credentials provided earlier to access the admin dashboard at :admin_url.',
    'install_error' => 'The server returned an error. Check the log file (/storage/logs) for details.',
    'database_info' => 'SQLite offers high performance and suits 95% of users. For larger daily user volumes, consider MySQL or MariaDB.',
    'install_acknowledge' => 'By installing our software, you acknowledge that NowSquare isn\'t liable for any issues stemming from its use. Remember that all software may contain bugs. If you encounter any, please reach out to us via email or a support ticket so we can address them promptly.',

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    */

    'email_settings' => 'Email Delivery',
    'email_critical_title' => 'Email is Essential',
    'email_critical_description' => 'Your customers will receive one-time passwords (OTP) via email to log in. Without working email, no one can access the system—including you.',
    'email_why_matters' => 'Why this matters',
    'email_otp_explanation' => 'We use passwordless authentication. Instead of remembering passwords, users receive a secure code via email every time they log in. Simple, secure, modern.',

    'mail_driver' => 'How should we send emails?',
    'mail_driver_help' => 'Choose the service that will deliver your emails to customers.',

    // Driver descriptions
    'driver_smtp' => 'SMTP Server',
    'driver_smtp_desc' => 'Connect to any email server. Works with Gmail, Outlook, your hosting provider, or any SMTP service.',
    'driver_smtp_best_for' => 'Best for: Most users, hosting providers',

    'driver_mailgun' => 'Mailgun',
    'driver_mailgun_desc' => 'Professional email delivery service by Mailchimp. Reliable, scalable, with detailed analytics.',
    'driver_mailgun_best_for' => 'Best for: Growing businesses, high volume',

    'driver_ses' => 'Amazon SES',
    'driver_ses_desc' => 'Cost-effective email at scale from AWS. Excellent deliverability and pricing.',
    'driver_ses_best_for' => 'Best for: AWS users, large scale operations',

    'driver_postmark' => 'Postmark',
    'driver_postmark_desc' => 'Built specifically for transactional email. Industry-leading delivery speed.',
    'driver_postmark_best_for' => 'Best for: Speed-critical applications',

    'driver_resend' => 'Resend',
    'driver_resend_desc' => 'Modern email API built for developers. Simple, reliable, with great DX.',
    'driver_resend_best_for' => 'Best for: Developer-focused teams',

    'driver_sendmail' => 'Sendmail',
    'driver_sendmail_desc' => 'Use your server\'s built-in mail system. No external service needed.',
    'driver_sendmail_best_for' => 'Best for: Simple setups, Linux servers',

    'driver_mailpit' => 'Mailpit (Testing)',
    'driver_mailpit_desc' => 'Catches all emails locally for development. No real emails are sent.',
    'driver_mailpit_best_for' => 'Best for: Local development only',

    'driver_log' => 'Log File (Development)',
    'driver_log_desc' => 'Writes emails to log files instead of sending. Perfect for initial testing.',
    'driver_log_best_for' => 'Best for: Quick testing, debugging',

    // SMTP Fields
    'smtp_host' => 'SMTP Server',
    'smtp_host_placeholder' => 'smtp.example.com',
    'smtp_host_help' => 'The address of your email server',

    'smtp_port' => 'Port',
    'smtp_port_help' => 'Common ports: 587 (TLS), 465 (SSL), 25 (unencrypted)',

    'smtp_username' => 'Username',
    'smtp_username_placeholder' => 'your-email@example.com',
    'smtp_username_help' => 'Usually your full email address',

    'smtp_password' => 'Password',
    'smtp_password_placeholder' => 'Your email password or app password',
    'smtp_password_help' => 'For Gmail/Google, use an App Password',

    'smtp_encryption' => 'Security',
    'smtp_encryption_help' => 'TLS is recommended for most providers',
    'smtp_encryption_tls' => 'TLS (Recommended)',
    'smtp_encryption_ssl' => 'SSL',
    'smtp_encryption_none' => 'None (Not recommended)',

    // Provider-specific
    'mailgun_domain' => 'Mailgun Domain',
    'mailgun_domain_placeholder' => 'mg.yourdomain.com',
    'mailgun_domain_help' => 'Your verified sending domain in Mailgun',

    'mailgun_secret' => 'API Key',
    'mailgun_secret_placeholder' => 'key-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'mailgun_secret_help' => 'Found in Mailgun → Settings → API Keys',

    'mailgun_endpoint' => 'Region',
    'mailgun_endpoint_us' => 'United States (api.mailgun.net)',
    'mailgun_endpoint_eu' => 'European Union (api.eu.mailgun.net)',

    'ses_key' => 'AWS Access Key ID',
    'ses_key_placeholder' => 'AKIAIOSFODNN7EXAMPLE',
    'ses_key_help' => 'From your AWS IAM credentials',

    'ses_secret' => 'AWS Secret Access Key',
    'ses_secret_placeholder' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'ses_secret_help' => 'Keep this secure, never share it',

    'ses_region' => 'AWS Region',
    'ses_region_help' => 'The region where SES is configured',

    'postmark_token' => 'Server API Token',
    'postmark_token_placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    'postmark_token_help' => 'Found in Postmark → Server → API Tokens',

    'resend_key' => 'API Key',
    'resend_key_placeholder' => 're_xxxxxxxxxxxxxxxxxxxxxxxxxx',
    'resend_key_help' => 'Found in Resend Dashboard → API Keys',

    // From address
    'mail_from_address' => 'From Email',
    'mail_from_address_placeholder' => 'noreply@yourdomain.com',
    'mail_from_address_help' => 'Recipients will see this as the sender',

    'mail_from_name' => 'From Name',
    'mail_from_name_placeholder' => 'My Company',
    'mail_from_name_help' => 'The friendly name shown to recipients',

    // Test email
    'test_email' => 'Send Test Email',
    'test_email_sending' => 'Sending...',
    'test_email_success' => 'Test email sent! Check your inbox.',
    'test_email_failed' => 'Failed to send. Please check your settings.',
    'test_email_check_spam' => 'Don\'t see it? Check your spam folder.',

    // Common provider help
    'gmail_help_title' => 'Using Gmail?',
    'gmail_help_text' => 'You\'ll need to create an App Password in your Google Account settings. Regular passwords won\'t work.',
    'gmail_help_link' => 'How to create an App Password',

    'provider_setup_guide' => 'Setup Guide',
    'need_help' => 'Need help?',
    'skip_for_now' => 'Configure Later',
    'skip_warning' => 'Warning: Without email configured, login codes cannot be sent. You can configure this later in your .env file.',

    // Validation
    'email_config_incomplete' => 'Please complete all required email settings.',
];
