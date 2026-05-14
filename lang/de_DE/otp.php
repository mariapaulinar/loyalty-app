<?php

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * OTP Authentication Translations - German (Germany)
 */

return [
    // ─────────────────────────────────────────────────────────────────────────
    // EMAIL SUBJECTS
    // ─────────────────────────────────────────────────────────────────────────

    'email_subject_login' => 'Ihr sicherer Anmeldecode',
    'email_subject_verify' => 'Bestätigen Sie Ihre E-Mail-Adresse',
    'email_subject_reset' => 'Passwort zurücksetzen',
    'email_subject_default' => 'Ihr Bestätigungscode',

    // ─────────────────────────────────────────────────────────────────────────
    // EMAIL CONTENT
    // ─────────────────────────────────────────────────────────────────────────

    'email_title_login' => 'Willkommen zurück bei :app',
    'email_title_verify_email' => 'Lassen Sie uns Ihre E-Mail bestätigen',
    'email_title_registration' => 'Sie sind fast fertig',
    'email_title_password_reset' => 'Passwort zurücksetzen',
    'email_title_profile_update' => 'Bestätigen Sie Ihre Profiländerungen',
    'email_title_default' => 'Ihr Bestätigungscode',

    'email_intro_login' => 'Hier ist Ihr sicherer Anmeldecode. Geben Sie ihn einfach auf der Anmeldeseite ein, um auf Ihr Konto zuzugreifen.',
    'email_intro_verify_email' => 'Wir müssen nur sicherstellen, dass diese E-Mail-Adresse Ihnen gehört. Geben Sie den folgenden Code ein, um Ihre Adresse zu bestätigen.',
    'email_intro_registration' => 'Willkommen an Bord! Geben Sie diesen Code ein, um Ihre Registrierung abzuschließen und Ihr neues Konto freizuschalten.',
    'email_intro_password_reset' => 'Keine Sorge, das passiert den Besten. Verwenden Sie diesen Code, um ein neues Passwort zu erstellen.',
    'email_intro_profile_update' => 'Zu Ihrer Sicherheit bestätigen Sie bitte, dass Sie die Änderungen an Ihrem Profil vornehmen.',
    'email_intro_default' => 'Hier ist der von Ihnen angeforderte Bestätigungscode.',

    'email_expires' => 'Gültig für :minutes Minuten',
    'email_security_notice' => 'Haben Sie dies nicht angefordert? Keine Aktion erforderlich — ignorieren Sie diese E-Mail einfach. Ihr Konto bleibt sicher.',
    'email_subcopy' => 'Dieser Code ist nur für Sie bestimmt. Wir werden Sie niemals bitten, ihn mit jemandem zu teilen.',
    'verification_code' => 'Ihr Bestätigungscode',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 1 (EMAIL ENTRY)
    // ─────────────────────────────────────────────────────────────────────────

    'step1_title' => 'Bei Ihrem Konto anmelden',
    'step1_subtitle' => 'Geben Sie Ihre E-Mail ein, um fortzufahren',
    'step1_subtitle_admin' => 'Anmelden beim Admin-Portal',
    'step1_subtitle_partner' => 'Anmelden beim Partner-Portal',
    'step1_subtitle_staff' => 'Anmelden beim Mitarbeiter-Portal',
    'step1_continue' => 'Weiter',
    'step1_no_account' => 'Haben Sie kein Konto?',
    'step1_create_account' => 'Erstellen Sie eines',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 2 (METHOD SELECTION)
    // ─────────────────────────────────────────────────────────────────────────

    'step2_welcome_back' => 'Willkommen zurück',
    'step2_welcome' => 'Willkommen',
    'step2_change_email' => 'E-Mail ändern',
    'step2_enter_password' => 'Geben Sie Ihr Passwort ein',
    'step2_sign_in' => 'Anmelden',
    'step2_or_divider' => 'oder',
    'step2_send_code' => 'Anmeldecode zusenden',
    'step2_send_code_only' => 'Anmeldecode zusenden',
    'step2_code_info' => 'Wir senden Ihnen einen 6-stelligen Code per E-Mail.',
    'step2_forgot_password' => 'Passwort vergessen?',
    'step2_set_password' => 'Stattdessen ein Passwort festlegen',
    'step2_passwordless_title' => 'Passwortlose Anmeldung',
    'step2_passwordless_subtitle' => 'Wir senden Ihnen einen 6-stelligen Code per E-Mail für einen sicheren Zugang.',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 3 (OTP VERIFICATION)
    // ─────────────────────────────────────────────────────────────────────────

    'step3_title' => 'Bestätigungscode eingeben',
    'step3_subtitle' => 'Wir haben einen 6-stelligen Code gesendet an',
    'step3_didnt_receive' => 'Code nicht erhalten?',
    'step3_resend' => 'Code erneut senden',
    'step3_resend_in' => 'Erneut senden in :seconds s',
    'step3_try_another' => 'Andere Methode versuchen',
    'step3_verifying' => 'Verifizierung...',
    'step3_code_sent' => 'Code gesendet!',
    'step3_check_email' => 'Prüfen Sie Ihre E-Mail auf den Bestätigungscode.',
    'step3_panel_description' => 'Wir haben einen 6-stelligen Code an Ihre E-Mail gesendet. Geben Sie ihn oben ein, um die Anmeldung abzuschließen.',

    // ─────────────────────────────────────────────────────────────────────────
    // PORTAL NAMES
    // ─────────────────────────────────────────────────────────────────────────

    'admin_portal' => 'Admin-Portal',
    'partner_portal' => 'Partner-Portal',
    'staff_portal' => 'Mitarbeiter-Portal',

    // ─────────────────────────────────────────────────────────────────────────
    // PIN INPUT
    // ─────────────────────────────────────────────────────────────────────────

    'pin_digit_label' => 'Ziffer :position von :total',
    'pin_placeholder' => '·',

    // ─────────────────────────────────────────────────────────────────────────
    // SUCCESS MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'code_sent_success' => 'Bestätigungscode gesendet an :email',
    'verification_success' => 'Verifizierung erfolgreich!',
    'login_success' => 'Willkommen zurück!',

    // ─────────────────────────────────────────────────────────────────────────
    // ERROR MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'rate_limited' => 'Zu viele Versuche. Bitte warten Sie :seconds Sekunden.',
    'code_expired' => 'Dieser Code ist abgelaufen. Bitte fordern Sie einen neuen an.',
    'code_invalid' => 'Ungültiger Code. Noch :remaining Versuch(e) übrig.',
    'code_locked' => 'Zu viele fehlgeschlagene Versuche. Bitte fordern Sie einen neuen Code an.',
    'user_not_found' => 'Kein Konto mit dieser E-Mail-Adresse gefunden.',
    'account_not_found_create' => 'Kein Konto für :email gefunden. <a href=":register_url" class="font-semibold text-primary-600 dark:text-primary-400 hover:underline">Konto erstellen?</a>',
    'user_inactive' => 'Dieses Konto ist nicht aktiv. Bitte kontaktieren Sie den Support.',
    'send_failed' => 'Bestätigungscode konnte nicht gesendet werden. Bitte versuchen Sie es erneut.',
    'invalid_request' => 'Ungültige Anfrage. Bitte versuchen Sie es erneut.',

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRATION WITH OTP
    // ─────────────────────────────────────────────────────────────────────────

    'registration_verify_title' => 'E-Mail bestätigen',
    'registration_verify_subtitle' => 'Wir haben einen 6-stelligen Code gesendet an',
    'registration_start_over' => 'Von vorne beginnen',
    'registration_almost_there' => 'Fast geschafft!',
    'registration_verify_panel_title' => 'Noch ein Schritt',
    'registration_verify_panel_text' => 'Geben Sie den Bestätigungscode ein, den wir an Ihre E-Mail gesendet haben, um Ihre Registrierung abzuschließen.',
    'registration_benefit_1' => 'Kein Passwort zum Merken',
    'registration_benefit_2' => 'Sichere passwortlose Anmeldung',
    'registration_benefit_3' => 'Passwort jederzeit in Ihrem Profil festlegen',
    'registration_success' => 'Willkommen! Ihr Konto wurde erfolgreich erstellt.',
    'registration_email_exists' => 'Ein Konto mit dieser E-Mail-Adresse existiert bereits. Bitte melden Sie sich an.',
    'registration_otp_sent' => 'Ein Bestätigungscode wurde an :email gesendet',
    'registration_almost_done' => 'Geben Sie den 6-stelligen Code ein, den wir an Ihre E-Mail gesendet haben, um Ihr Konto zu verifizieren und die Registrierung abzuschließen.',
    'verify_email_title' => 'E-Mail bestätigen',
    'secure_verification' => 'Sichere Verifizierung',
    'code_expires_minutes' => 'Code läuft in :minutes Minuten ab',
    'never_share_code' => 'Teilen Sie diesen Code niemals mit anderen',
    'check_spam_folder' => 'Prüfen Sie Ihren Spam-Ordner, falls Sie die E-Mail nicht sehen',

    // ─────────────────────────────────────────────────────────────────────────
    // PIN INPUT COMPONENT
    // ─────────────────────────────────────────────────────────────────────────

    'pin_aria_label' => 'Bestätigungscode',
    'pin_digit_aria' => 'Ziffer :n von :total',

    // ─────────────────────────────────────────────────────────────────────────
    // PROFILE UPDATE OTP VERIFICATION
    // ─────────────────────────────────────────────────────────────────────────

    'profile_verification_title' => 'Identität bestätigen',
    'profile_verification_subtitle' => 'Ein Bestätigungscode ist erforderlich, um Änderungen zu speichern',
    'profile_send_code_info' => 'Wir senden einen 6-stelligen Code an :email, um sicherzustellen, dass Sie es sind.',
    'profile_send_code_info_generic' => 'Wir senden einen 6-stelligen Code, um sicherzustellen, dass Sie es sind.',
    'profile_send_code' => 'Bestätigungscode senden',
    'profile_verified' => 'Identität bestätigt! Sie können jetzt Ihre Änderungen speichern.',
    'profile_otp_required' => 'Bitte bestätigen Sie Ihre Identität mit dem Code, der an Ihre E-Mail gesendet wurde.',
    'verify_to_save' => 'Zum Speichern verifizieren',
    'identity_verified' => 'Identität bestätigt!',
    'verification_failed' => 'Verifizierung fehlgeschlagen. Bitte versuchen Sie es erneut.',
    'code_sent_success_short' => 'Code gesendet!',
    'sending' => 'Wird gesendet...',

    // ─────────────────────────────────────────────────────────────────────────
    // DEMO MODE
    // ─────────────────────────────────────────────────────────────────────────

    'demo_otp_notice' => 'Demo-Modus: Verwenden Sie den Code :code zum Anmelden.',

    // ─────────────────────────────────────────────────────────────────────────
    // LOADING STATE
    // ─────────────────────────────────────────────────────────────────────────

    'please_wait' => 'Bitte warten...',
];
