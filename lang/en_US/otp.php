<?php

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * OTP Authentication Translations
 */

return [
    // ─────────────────────────────────────────────────────────────────────────
    // EMAIL SUBJECTS
    // ─────────────────────────────────────────────────────────────────────────

    'email_subject_login' => 'Your secure login code',
    'email_subject_verify' => 'Verify your email address',
    'email_subject_reset' => 'Reset your password',
    'email_subject_default' => 'Your verification code',

    // ─────────────────────────────────────────────────────────────────────────
    // EMAIL CONTENT
    // ─────────────────────────────────────────────────────────────────────────

    'email_title_login' => 'Welcome back to :app',
    'email_title_verify_email' => 'Let\'s verify your email',
    'email_title_registration' => 'You\'re almost there',
    'email_title_password_reset' => 'Reset your password',
    'email_title_profile_update' => 'Verify your profile changes',
    'email_title_default' => 'Your verification code',

    'email_intro_login' => 'Here\'s your secure login code. Simply enter it on the sign-in page to access your account.',
    'email_intro_verify_email' => 'We just need to make sure this email belongs to you. Enter the code below to verify your address.',
    'email_intro_registration' => 'Welcome aboard! Enter this code to complete your registration and unlock your new account.',
    'email_intro_password_reset' => 'No worries, it happens to the best of us. Use this code to create a new password.',
    'email_intro_profile_update' => 'For your security, please confirm it\'s you making changes to your profile.',
    'email_intro_default' => 'Here\'s the verification code you requested.',

    'email_expires' => 'Valid for :minutes minutes',
    'email_security_notice' => 'Didn\'t request this? No action needed — simply ignore this email. Your account remains secure.',
    'email_subcopy' => 'This code is for your eyes only. We\'ll never ask you to share it with anyone, ever.',
    'verification_code' => 'Your verification code',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 1 (EMAIL ENTRY)
    // ─────────────────────────────────────────────────────────────────────────

    'step1_title' => 'Sign in to your account',
    'step1_subtitle' => 'Enter your email to continue',
    'step1_subtitle_admin' => 'Sign in to the Admin Portal',
    'step1_subtitle_partner' => 'Sign in to the Partner Portal',
    'step1_subtitle_staff' => 'Sign in to the Staff Portal',
    'step1_continue' => 'Continue',
    'step1_no_account' => 'Don\'t have an account?',
    'step1_create_account' => 'Create one',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 2 (METHOD SELECTION)
    // ─────────────────────────────────────────────────────────────────────────

    'step2_welcome_back' => 'Welcome back',
    'step2_welcome' => 'Welcome',
    'step2_change_email' => 'Change email',
    'step2_enter_password' => 'Enter your password',
    'step2_sign_in' => 'Sign in',
    'step2_or_divider' => 'or',
    'step2_send_code' => 'Send me a login code',
    'step2_send_code_only' => 'Send me a login code',
    'step2_code_info' => 'We\'ll send a 6-digit code to your email.',
    'step2_forgot_password' => 'Forgot password?',
    'step2_set_password' => 'Set up a password instead',
    'step2_passwordless_title' => 'Passwordless Sign In',
    'step2_passwordless_subtitle' => 'We\'ll send a 6-digit code to your email for secure access.',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 3 (OTP VERIFICATION)
    // ─────────────────────────────────────────────────────────────────────────

    'step3_title' => 'Enter verification code',
    'step3_subtitle' => 'We sent a 6-digit code to',
    'step3_didnt_receive' => 'Didn\'t receive the code?',
    'step3_resend' => 'Resend code',
    'step3_resend_in' => 'Resend in :seconds s',
    'step3_try_another' => 'Try another method',
    'step3_verifying' => 'Verifying...',
    'step3_code_sent' => 'Code sent!',
    'step3_check_email' => 'Check your email for the verification code.',
    'step3_panel_description' => 'We sent a 6-digit code to your email. Enter it above to complete your sign in.',

    // ─────────────────────────────────────────────────────────────────────────
    // PORTAL NAMES
    // ─────────────────────────────────────────────────────────────────────────

    'admin_portal' => 'Admin Portal',
    'partner_portal' => 'Partner Portal',
    'staff_portal' => 'Staff Portal',

    // ─────────────────────────────────────────────────────────────────────────
    // PIN INPUT
    // ─────────────────────────────────────────────────────────────────────────

    'pin_digit_label' => 'Digit :position of :total',
    'pin_placeholder' => '·',

    // ─────────────────────────────────────────────────────────────────────────
    // SUCCESS MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'code_sent_success' => 'Verification code sent to :email',
    'verification_success' => 'Verification successful!',
    'login_success' => 'Welcome back!',

    // ─────────────────────────────────────────────────────────────────────────
    // ERROR MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'rate_limited' => 'Too many attempts. Please wait :seconds seconds.',
    'code_expired' => 'This code has expired. Please request a new one.',
    'code_invalid' => 'Invalid code. :remaining attempt(s) remaining.',
    'code_locked' => 'Too many failed attempts. Please request a new code.',
    'user_not_found' => 'No account found with this email address.',
    'account_not_found_create' => 'No account found for :email. <a href=":register_url" class="font-semibold text-primary-600 dark:text-primary-400 hover:underline">Create an account?</a>',
    'user_inactive' => 'This account is not active. Please contact support.',
    'send_failed' => 'Failed to send verification code. Please try again.',
    'invalid_request' => 'Invalid request. Please try again.',

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRATION WITH OTP
    // ─────────────────────────────────────────────────────────────────────────

    'registration_verify_title' => 'Verify your email',
    'registration_verify_subtitle' => 'We sent a 6-digit code to',
    'registration_start_over' => 'Start over',
    'registration_almost_there' => 'Almost there!',
    'registration_verify_panel_title' => 'One more step to go',
    'registration_verify_panel_text' => 'Enter the verification code we sent to your email to complete your registration.',
    'registration_benefit_1' => 'No password to remember',
    'registration_benefit_2' => 'Secure passwordless login',
    'registration_benefit_3' => 'Set a password anytime from your profile',
    'registration_success' => 'Welcome! Your account has been created successfully.',
    'registration_email_exists' => 'An account with this email already exists. Please log in instead.',
    'registration_otp_sent' => 'A verification code has been sent to :email',
    'registration_almost_done' => 'Enter the 6-digit code we sent to your email to verify your account and complete registration.',
    'verify_email_title' => 'Verify Your Email',
    'secure_verification' => 'Secure Verification',
    'code_expires_minutes' => 'Code expires in :minutes minutes',
    'never_share_code' => 'Never share this code with anyone',
    'check_spam_folder' => 'Check spam folder if you don\'t see the email',

    'pin_aria_label' => 'Verification code',
    'pin_digit_aria' => 'Digit :n of :total',

    // ─────────────────────────────────────────────────────────────────────────
    // PROFILE UPDATE OTP VERIFICATION
    // ─────────────────────────────────────────────────────────────────────────

    'profile_verification_title' => 'Verify Your Identity',
    'profile_verification_subtitle' => 'A verification code is required to save changes',
    'profile_send_code_info' => 'We\'ll send a 6-digit code to :email to verify it\'s really you.',
    'profile_send_code_info_generic' => 'We\'ll send a 6-digit code to verify it\'s really you.',
    'profile_send_code' => 'Send Verification Code',
    'profile_verified' => 'Identity verified! You can now save your changes.',
    'profile_otp_required' => 'Please verify your identity with the code sent to your email.',
    'verify_to_save' => 'Verify to Save Changes',
    'identity_verified' => 'Identity Verified!',
    'verification_failed' => 'Verification failed. Please try again.',
    'code_sent_success_short' => 'Code sent!',
    'sending' => 'Sending...',

    // ─────────────────────────────────────────────────────────────────────────
    // DEMO MODE
    // ─────────────────────────────────────────────────────────────────────────

    'demo_otp_notice' => 'Demo mode: Use code :code to sign in.',

    // ─────────────────────────────────────────────────────────────────────────
    // LOADING STATE
    // ─────────────────────────────────────────────────────────────────────────

    'please_wait' => 'Please wait...',
];
