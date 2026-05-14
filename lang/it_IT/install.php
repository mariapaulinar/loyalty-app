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

    'installation' => 'Installazione',
    'install' => 'Installa',
    'install_script' => 'Installa lo script',
    'server_requirements' => 'Requisiti del server',
    'requirements' => 'Requisiti',
    'server_requirements_text' => 'I seguenti controlli determinano se lo script funzionerà sul tuo server, sebbene non sia possibile garantire la piena compatibilità.',
    'resolve_missing_requirements' => 'Risolvi i requisiti mancanti per continuare.',
    'next' => 'Avanti',
    'prev' => 'Indietro',
    'configuration' => 'Configurazione',
    'confirm' => 'Conferma',
    'app' => 'Applicazione',
    'name' => 'Nome',
    'email' => 'Email',
    'optional' => 'Facoltativo',
    '_optional_' => '(facoltativo)',
    'optional_email_config' => 'Puoi ignorare le impostazioni seguenti per ora. Possono essere configurate successivamente nel file .env nella root del sito. Nota: la funzionalità email richiede queste impostazioni.',
    'logo' => 'Logo',
    'logo_dark' => 'Logo (Modalità scura)',
    'user' => 'Utente',
    'email_address' => 'Indirizzo email',
    'time_zone' => 'Fuso orario',
    'password' => 'Password',
    'confirm_password' => 'Conferma password',
    'passwords_must_match' => 'Le password devono corrispondere.',
    'email_address_app' => 'Indirizzo email utilizzato dall\'applicazione per inviare email',
    'email_address_name_app' => 'Nome del mittente',
    'admin_login' => 'Accesso amministratore',
    'download_log' => 'Scarica file di log',
    'refresh_page' => 'Aggiorna questa pagina e riprova',
    'after_installation' => 'Una volta completata l\'installazione, usa le credenziali amministratore fornite in precedenza per accedere alla dashboard all\'indirizzo :admin_url.',
    'install_error' => 'Il server ha restituito un errore. Consulta il file di log (/storage/logs) per maggiori dettagli.',
    'database_info' => 'SQLite offre alte prestazioni ed è adatto al 95% degli utenti. Per volumi giornalieri maggiori, considera MySQL o MariaDB.',
    'install_acknowledge' => 'Installando il nostro software, riconosci che NowSquare non è responsabile per problemi derivanti dal suo utilizzo. Ricorda che qualsiasi software può contenere bug. Se ne trovi, contattaci via email o ticket di supporto così possiamo risolverli rapidamente.',

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    */

    'email_settings' => 'Invio email',
    'email_critical_title' => 'L\'email è essenziale',
    'email_critical_description' => 'I tuoi clienti riceveranno codici monouso (OTP) via email per accedere. Senza email funzionante, nessuno può accedere al sistema — incluso te.',
    'email_why_matters' => 'Perché è importante',
    'email_otp_explanation' => 'Utilizziamo l\'autenticazione senza password. Invece di memorizzare password, gli utenti ricevono un codice sicuro via email ad ogni accesso. Semplice, sicuro, moderno.',

    'mail_driver' => 'Come inviare le email?',
    'mail_driver_help' => 'Scegli il servizio che consegnerà le tue email ai clienti.',

    // Driver descriptions
    'driver_smtp' => 'Server SMTP',
    'driver_smtp_desc' => 'Connettiti a qualsiasi server di posta. Funziona con Gmail, Outlook, il tuo hosting o qualsiasi servizio SMTP.',
    'driver_smtp_best_for' => 'Ideale per: La maggior parte degli utenti, hosting provider',

    'driver_mailgun' => 'Mailgun',
    'driver_mailgun_desc' => 'Servizio professionale di invio email di Mailchimp. Affidabile, scalabile, con analisi dettagliate.',
    'driver_mailgun_best_for' => 'Ideale per: Aziende in crescita, volumi elevati',

    'driver_ses' => 'Amazon SES',
    'driver_ses_desc' => 'Invio email economico su larga scala da AWS. Eccellente deliverability e prezzi.',
    'driver_ses_best_for' => 'Ideale per: Utenti AWS, operazioni su larga scala',

    'driver_postmark' => 'Postmark',
    'driver_postmark_desc' => 'Progettato specificamente per email transazionali. Velocità di consegna di riferimento.',
    'driver_postmark_best_for' => 'Ideale per: Applicazioni time-critical',

    'driver_resend' => 'Resend',
    'driver_resend_desc' => 'API email moderna progettata per sviluppatori. Semplice, affidabile, ottima esperienza sviluppatore.',
    'driver_resend_best_for' => 'Ideale per: Team orientati allo sviluppo',

    'driver_sendmail' => 'Sendmail',
    'driver_sendmail_desc' => 'Usa il sistema di posta integrato del tuo server. Nessun servizio esterno necessario.',
    'driver_sendmail_best_for' => 'Ideale per: Configurazioni semplici, server Linux',

    'driver_mailpit' => 'Mailpit (Test)',
    'driver_mailpit_desc' => 'Cattura tutte le email localmente per lo sviluppo. Nessuna email reale viene inviata.',
    'driver_mailpit_best_for' => 'Ideale per: Solo sviluppo locale',

    'driver_log' => 'File di log (Sviluppo)',
    'driver_log_desc' => 'Scrive le email nei file di log invece di inviarle. Perfetto per i test iniziali.',
    'driver_log_best_for' => 'Ideale per: Test rapidi, debug',

    // SMTP Fields
    'smtp_host' => 'Server SMTP',
    'smtp_host_placeholder' => 'smtp.esempio.com',
    'smtp_host_help' => 'L\'indirizzo del tuo server di posta',

    'smtp_port' => 'Porta',
    'smtp_port_help' => 'Porte comuni: 587 (TLS), 465 (SSL), 25 (non crittografato)',

    'smtp_username' => 'Nome utente',
    'smtp_username_placeholder' => 'tua-email@esempio.com',
    'smtp_username_help' => 'Di solito il tuo indirizzo email completo',

    'smtp_password' => 'Password',
    'smtp_password_placeholder' => 'La tua password email o password app',
    'smtp_password_help' => 'Per Gmail/Google, usa una password app',

    'smtp_encryption' => 'Sicurezza',
    'smtp_encryption_help' => 'TLS è consigliato per la maggior parte dei provider',
    'smtp_encryption_tls' => 'TLS (Consigliato)',
    'smtp_encryption_ssl' => 'SSL',
    'smtp_encryption_none' => 'Nessuna (Non consigliato)',

    // Provider-specific
    'mailgun_domain' => 'Dominio Mailgun',
    'mailgun_domain_placeholder' => 'mg.tuodominio.com',
    'mailgun_domain_help' => 'Il tuo dominio di invio verificato in Mailgun',

    'mailgun_secret' => 'Chiave API',
    'mailgun_secret_placeholder' => 'key-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'mailgun_secret_help' => 'Trovalo in Mailgun → Impostazioni → Chiavi API',

    'mailgun_endpoint' => 'Regione',
    'mailgun_endpoint_us' => 'Stati Uniti (api.mailgun.net)',
    'mailgun_endpoint_eu' => 'Unione Europea (api.eu.mailgun.net)',

    'ses_key' => 'ID chiave di accesso AWS',
    'ses_key_placeholder' => 'AKIAIOSFODNN7EXAMPLE',
    'ses_key_help' => 'Dalle tue credenziali AWS IAM',

    'ses_secret' => 'Chiave di accesso segreta AWS',
    'ses_secret_placeholder' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'ses_secret_help' => 'Mantienila al sicuro, non condividerla mai',

    'ses_region' => 'Regione AWS',
    'ses_region_help' => 'La regione dove SES è configurato',

    'postmark_token' => 'Token API server',
    'postmark_token_placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    'postmark_token_help' => 'Trovalo in Postmark → Server → Token API',

    'resend_key' => 'Chiave API',
    'resend_key_placeholder' => 're_xxxxxxxxxxxxxxxxxxxxxxxxxx',
    'resend_key_help' => 'Trovalo nella dashboard Resend → Chiavi API',

    // From address
    'mail_from_address' => 'Email mittente',
    'mail_from_address_placeholder' => 'noreply@tuodominio.com',
    'mail_from_address_help' => 'I destinatari vedranno questo indirizzo come mittente',

    'mail_from_name' => 'Nome mittente',
    'mail_from_name_placeholder' => 'My Company',
    'mail_from_name_help' => 'Il nome mostrato ai destinatari',

    // Test email
    'test_email' => 'Invia email di test',
    'test_email_sending' => 'Invio in corso...',
    'test_email_success' => 'Email di test inviata! Controlla la tua casella di posta.',
    'test_email_failed' => 'Invio fallito. Verifica le tue impostazioni.',
    'test_email_check_spam' => 'Non la vedi? Controlla la cartella spam.',

    // Common provider help
    'gmail_help_title' => 'Usi Gmail?',
    'gmail_help_text' => 'Dovrai creare una password app nelle impostazioni del tuo account Google. Le password normali non funzioneranno.',
    'gmail_help_link' => 'Come creare una password app',

    'provider_setup_guide' => 'Guida alla configurazione',
    'need_help' => 'Hai bisogno di aiuto?',
    'skip_for_now' => 'Configura più tardi',
    'skip_warning' => 'Attenzione: senza email configurata, i codici di accesso non possono essere inviati. Puoi configurare questo più tardi nel tuo file .env.',

    // Validation
    'email_config_incomplete' => 'Completa tutte le impostazioni email richieste.',
];
