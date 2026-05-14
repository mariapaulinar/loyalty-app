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

    'email_subject_login' => 'Il tuo codice di accesso sicuro',
    'email_subject_verify' => 'Verifica il tuo indirizzo email',
    'email_subject_reset' => 'Reimposta la tua password',
    'email_subject_default' => 'Il tuo codice di verifica',

    // ─────────────────────────────────────────────────────────────────────────
    // EMAIL CONTENT
    // ─────────────────────────────────────────────────────────────────────────

    'email_title_login' => 'Bentornato su :app',
    'email_title_verify_email' => 'Verifichiamo la tua email',
    'email_title_registration' => 'Ci sei quasi',
    'email_title_password_reset' => 'Reimposta la tua password',
    'email_title_profile_update' => 'Verifica le modifiche al tuo profilo',
    'email_title_default' => 'Il tuo codice di verifica',

    'email_intro_login' => 'Ecco il tuo codice di accesso sicuro. Inseriscilo semplicemente nella pagina di login per accedere al tuo account.',
    'email_intro_verify_email' => 'Dobbiamo solo verificare che questo indirizzo email ti appartenga. Inserisci il codice qui sotto per confermare il tuo indirizzo.',
    'email_intro_registration' => 'Benvenuto! Inserisci questo codice per completare la registrazione e attivare il tuo nuovo account.',
    'email_intro_password_reset' => 'Non preoccuparti, capita a tutti. Usa questo codice per creare una nuova password.',
    'email_intro_profile_update' => 'Per la tua sicurezza, conferma che sei tu a modificare il tuo profilo.',
    'email_intro_default' => 'Ecco il codice di verifica che hai richiesto.',

    'email_expires' => 'Valido per :minutes minuti',
    'email_security_notice' => 'Non hai richiesto questo codice? Nessuna azione richiesta — ignora semplicemente questa email. Il tuo account rimane sicuro.',
    'email_subcopy' => 'Questo codice è strettamente riservato. Non ti chiederemo mai di condividerlo con nessuno.',
    'verification_code' => 'Il tuo codice di verifica',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 1 (EMAIL ENTRY)
    // ─────────────────────────────────────────────────────────────────────────

    'step1_title' => 'Accedi al tuo account',
    'step1_subtitle' => 'Inserisci la tua email per continuare',
    'step1_subtitle_admin' => 'Accedi al Portale Amministratore',
    'step1_subtitle_partner' => 'Accedi al Portale Partner',
    'step1_subtitle_staff' => 'Accedi al Portale Staff',
    'step1_continue' => 'Continua',
    'step1_no_account' => 'Non hai un account?',
    'step1_create_account' => 'Creane uno',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 2 (METHOD SELECTION)
    // ─────────────────────────────────────────────────────────────────────────

    'step2_welcome_back' => 'Bentornato',
    'step2_welcome' => 'Benvenuto',
    'step2_change_email' => 'Cambia email',
    'step2_enter_password' => 'Inserisci la tua password',
    'step2_sign_in' => 'Accedi',
    'step2_or_divider' => 'oppure',
    'step2_send_code' => 'Inviami un codice di accesso',
    'step2_send_code_only' => 'Inviami un codice di accesso',
    'step2_code_info' => 'Ti invieremo un codice a 6 cifre via email.',
    'step2_forgot_password' => 'Password dimenticata?',
    'step2_set_password' => 'Imposta invece una password',
    'step2_passwordless_title' => 'Accesso senza password',
    'step2_passwordless_subtitle' => 'Ti invieremo un codice a 6 cifre via email per un accesso sicuro.',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 3 (OTP VERIFICATION)
    // ─────────────────────────────────────────────────────────────────────────

    'step3_title' => 'Inserisci il codice di verifica',
    'step3_subtitle' => 'Abbiamo inviato un codice a 6 cifre a',
    'step3_didnt_receive' => 'Non hai ricevuto il codice?',
    'step3_resend' => 'Reinvia il codice',
    'step3_resend_in' => 'Reinvia tra :seconds s',
    'step3_try_another' => 'Prova un altro metodo',
    'step3_verifying' => 'Verifica in corso...',
    'step3_code_sent' => 'Codice inviato!',
    'step3_check_email' => 'Controlla la tua email per il codice di verifica.',
    'step3_panel_description' => 'Abbiamo inviato un codice a 6 cifre alla tua email. Inseriscilo qui sopra per completare l\'accesso.',

    // ─────────────────────────────────────────────────────────────────────────
    // PORTAL NAMES
    // ─────────────────────────────────────────────────────────────────────────

    'admin_portal' => 'Portale Admin',
    'partner_portal' => 'Portale Partner',
    'staff_portal' => 'Portale Staff',

    // ─────────────────────────────────────────────────────────────────────────
    // PIN INPUT
    // ─────────────────────────────────────────────────────────────────────────

    'pin_digit_label' => 'Cifra :position di :total',
    'pin_placeholder' => '·',

    // ─────────────────────────────────────────────────────────────────────────
    // SUCCESS MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'code_sent_success' => 'Codice di verifica inviato a :email',
    'verification_success' => 'Verifica completata!',
    'login_success' => 'Bentornato!',

    // ─────────────────────────────────────────────────────────────────────────
    // ERROR MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'rate_limited' => 'Troppi tentativi. Attendi :seconds secondi.',
    'code_expired' => 'Questo codice è scaduto. Richiedine uno nuovo.',
    'code_invalid' => 'Codice non valido. :remaining tentativo/i rimanente/i.',
    'code_locked' => 'Troppi tentativi falliti. Richiedi un nuovo codice.',
    'user_not_found' => 'Nessun account trovato con questo indirizzo email.',
    'account_not_found_create' => 'Nessun account trovato per :email. <a href=":register_url" class="font-semibold text-primary-600 dark:text-primary-400 hover:underline">Creare un account?</a>',
    'user_inactive' => 'Questo account non è attivo. Contatta il supporto.',
    'send_failed' => 'Invio del codice di verifica non riuscito. Riprova.',
    'invalid_request' => 'Richiesta non valida. Riprova.',

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRATION WITH OTP
    // ─────────────────────────────────────────────────────────────────────────

    'registration_verify_title' => 'Verifica la tua email',
    'registration_verify_subtitle' => 'Abbiamo inviato un codice a 6 cifre a',
    'registration_start_over' => 'Ricomincia',
    'registration_almost_there' => 'Ci sei quasi!',
    'registration_verify_panel_title' => 'Ancora un passaggio',
    'registration_verify_panel_text' => 'Inserisci il codice di verifica che abbiamo inviato al tuo indirizzo email per completare la registrazione.',
    'registration_benefit_1' => 'Nessuna password da ricordare',
    'registration_benefit_2' => 'Accesso sicuro senza password',
    'registration_benefit_3' => 'Imposta una password in qualsiasi momento dal tuo profilo',
    'registration_success' => 'Benvenuto! Il tuo account è stato creato con successo.',
    'registration_email_exists' => 'Un account con questo indirizzo email esiste già. Effettua l\'accesso.',
    'registration_otp_sent' => 'Un codice di verifica è stato inviato a :email',
    'registration_almost_done' => 'Inserisci il codice a 6 cifre che abbiamo inviato al tuo indirizzo email per verificare il tuo account e completare la registrazione.',
    'verify_email_title' => 'Verifica la tua email',
    'secure_verification' => 'Verifica sicura',
    'code_expires_minutes' => 'Il codice scade tra :minutes minuti',
    'never_share_code' => 'Non condividere mai questo codice con nessuno',
    'check_spam_folder' => 'Controlla la cartella spam se non vedi l\'email',

    // ─────────────────────────────────────────────────────────────────────────
    // PIN INPUT COMPONENT
    // ─────────────────────────────────────────────────────────────────────────

    'pin_aria_label' => 'Codice di verifica',
    'pin_digit_aria' => 'Cifra :n di :total',

    // ─────────────────────────────────────────────────────────────────────────
    // PROFILE UPDATE OTP VERIFICATION
    // ─────────────────────────────────────────────────────────────────────────

    'profile_verification_title' => 'Verifica la tua identità',
    'profile_verification_subtitle' => 'È richiesto un codice di verifica per salvare le modifiche',
    'profile_send_code_info' => 'Invieremo un codice a 6 cifre a :email per verificare che sei tu.',
    'profile_send_code_info_generic' => 'Invieremo un codice a 6 cifre per verificare che sei tu.',
    'profile_send_code' => 'Invia codice di verifica',
    'profile_verified' => 'Identità verificata! Ora puoi salvare le modifiche.',
    'profile_otp_required' => 'Verifica la tua identità con il codice inviato al tuo indirizzo email.',
    'verify_to_save' => 'Verifica per salvare',
    'identity_verified' => 'Identità verificata!',
    'verification_failed' => 'Verifica fallita. Riprova.',
    'code_sent_success_short' => 'Codice inviato!',
    'sending' => 'Invio in corso...',

    // ─────────────────────────────────────────────────────────────────────────
    // DEMO MODE
    // ─────────────────────────────────────────────────────────────────────────

    'demo_otp_notice' => 'Modalità demo: usa il codice :code per accedere.',

    // ─────────────────────────────────────────────────────────────────────────
    // LOADING STATE
    // ─────────────────────────────────────────────────────────────────────────

    'please_wait' => 'Attendere prego...',
];
