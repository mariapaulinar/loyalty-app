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
    'install' => 'Installer',
    'install_script' => 'Installer le script',
    'server_requirements' => 'Configuration requise du serveur',
    'requirements' => 'Prérequis',
    'server_requirements_text' => 'Les vérifications suivantes permettent de déterminer si le script fonctionnera sur votre serveur, bien qu\'une compatibilité complète ne puisse être garantie.',
    'resolve_missing_requirements' => 'Résolvez les prérequis manquants pour continuer.',
    'next' => 'Suivant',
    'prev' => 'Précédent',
    'configuration' => 'Configuration',
    'confirm' => 'Confirmer',
    'app' => 'Application',
    'name' => 'Nom',
    'email' => 'E-mail',
    'optional' => 'Facultatif',
    '_optional_' => '(facultatif)',
    'optional_email_config' => 'Vous pouvez ignorer les paramètres ci-dessous pour le moment. Ils peuvent être configurés ultérieurement dans le fichier .env à la racine du site. Note : la fonctionnalité e-mail nécessite ces paramètres.',
    'logo' => 'Logo',
    'logo_dark' => 'Logo (Mode sombre)',
    'user' => 'Utilisateur',
    'email_address' => 'Adresse e-mail',
    'time_zone' => 'Fuseau horaire',
    'password' => 'Mot de passe',
    'confirm_password' => 'Confirmer le mot de passe',
    'passwords_must_match' => 'Les mots de passe doivent correspondre.',
    'email_address_app' => 'Adresse e-mail utilisée par l\'application pour envoyer des e-mails',
    'email_address_name_app' => 'Nom de l\'expéditeur',
    'admin_login' => 'Connexion administrateur',
    'download_log' => 'Télécharger le fichier journal',
    'refresh_page' => 'Actualisez cette page et réessayez',
    'after_installation' => 'Une fois l\'installation terminée, utilisez les identifiants administrateur fournis précédemment pour accéder au tableau de bord à l\'adresse :admin_url.',
    'install_error' => 'Le serveur a renvoyé une erreur. Consultez le fichier journal (/storage/logs) pour plus de détails.',
    'database_info' => 'SQLite offre de hautes performances et convient à 95% des utilisateurs. Pour des volumes quotidiens plus importants, envisagez MySQL ou MariaDB.',
    'install_acknowledge' => 'En installant notre logiciel, vous reconnaissez que NowSquare n\'est pas responsable des problèmes découlant de son utilisation. N\'oubliez pas que tout logiciel peut contenir des bugs. Si vous en rencontrez, veuillez nous contacter par e-mail ou via un ticket de support afin que nous puissions les corriger rapidement.',

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    */

    'email_settings' => 'Envoi d\'e-mails',
    'email_critical_title' => 'L\'e-mail est essentiel',
    'email_critical_description' => 'Vos clients recevront des codes à usage unique (OTP) par e-mail pour se connecter. Sans e-mail fonctionnel, personne ne peut accéder au système — y compris vous.',
    'email_why_matters' => 'Pourquoi c\'est important',
    'email_otp_explanation' => 'Nous utilisons l\'authentification sans mot de passe. Au lieu de mémoriser des mots de passe, les utilisateurs reçoivent un code sécurisé par e-mail à chaque connexion. Simple, sécurisé, moderne.',

    'mail_driver' => 'Comment envoyer les e-mails ?',
    'mail_driver_help' => 'Choisissez le service qui délivrera vos e-mails aux clients.',

    // Driver descriptions
    'driver_smtp' => 'Serveur SMTP',
    'driver_smtp_desc' => 'Connectez-vous à n\'importe quel serveur de messagerie. Fonctionne avec Gmail, Outlook, votre hébergeur ou tout service SMTP.',
    'driver_smtp_best_for' => 'Idéal pour : La plupart des utilisateurs, hébergeurs',

    'driver_mailgun' => 'Mailgun',
    'driver_mailgun_desc' => 'Service professionnel d\'envoi d\'e-mails par Mailchimp. Fiable, évolutif, avec des analyses détaillées.',
    'driver_mailgun_best_for' => 'Idéal pour : Entreprises en croissance, volumes élevés',

    'driver_ses' => 'Amazon SES',
    'driver_ses_desc' => 'Envoi d\'e-mails économique à grande échelle depuis AWS. Excellente délivrabilité et tarification.',
    'driver_ses_best_for' => 'Idéal pour : Utilisateurs AWS, opérations à grande échelle',

    'driver_postmark' => 'Postmark',
    'driver_postmark_desc' => 'Conçu spécifiquement pour les e-mails transactionnels. Vitesse de livraison de référence.',
    'driver_postmark_best_for' => 'Idéal pour : Applications critiques en temps',

    'driver_resend' => 'Resend',
    'driver_resend_desc' => 'API e-mail moderne conçue pour les développeurs. Simple, fiable, excellente expérience développeur.',
    'driver_resend_best_for' => 'Idéal pour : Équipes orientées développement',

    'driver_sendmail' => 'Sendmail',
    'driver_sendmail_desc' => 'Utilisez le système de messagerie intégré de votre serveur. Aucun service externe nécessaire.',
    'driver_sendmail_best_for' => 'Idéal pour : Configurations simples, serveurs Linux',

    'driver_mailpit' => 'Mailpit (Test)',
    'driver_mailpit_desc' => 'Capture tous les e-mails localement pour le développement. Aucun e-mail réel n\'est envoyé.',
    'driver_mailpit_best_for' => 'Idéal pour : Développement local uniquement',

    'driver_log' => 'Fichier journal (Développement)',
    'driver_log_desc' => 'Écrit les e-mails dans les fichiers journaux au lieu de les envoyer. Parfait pour les tests initiaux.',
    'driver_log_best_for' => 'Idéal pour : Tests rapides, débogage',

    // SMTP Fields
    'smtp_host' => 'Serveur SMTP',
    'smtp_host_placeholder' => 'smtp.exemple.com',
    'smtp_host_help' => 'L\'adresse de votre serveur de messagerie',

    'smtp_port' => 'Port',
    'smtp_port_help' => 'Ports courants : 587 (TLS), 465 (SSL), 25 (non chiffré)',

    'smtp_username' => 'Nom d\'utilisateur',
    'smtp_username_placeholder' => 'votre-email@exemple.com',
    'smtp_username_help' => 'Généralement votre adresse e-mail complète',

    'smtp_password' => 'Mot de passe',
    'smtp_password_placeholder' => 'Votre mot de passe e-mail ou mot de passe d\'application',
    'smtp_password_help' => 'Pour Gmail/Google, utilisez un mot de passe d\'application',

    'smtp_encryption' => 'Sécurité',
    'smtp_encryption_help' => 'TLS est recommandé pour la plupart des fournisseurs',
    'smtp_encryption_tls' => 'TLS (Recommandé)',
    'smtp_encryption_ssl' => 'SSL',
    'smtp_encryption_none' => 'Aucun (Non recommandé)',

    // Provider-specific
    'mailgun_domain' => 'Domaine Mailgun',
    'mailgun_domain_placeholder' => 'mg.votredomaine.com',
    'mailgun_domain_help' => 'Votre domaine d\'envoi vérifié dans Mailgun',

    'mailgun_secret' => 'Clé API',
    'mailgun_secret_placeholder' => 'key-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'mailgun_secret_help' => 'Trouvez-la dans Mailgun → Paramètres → Clés API',

    'mailgun_endpoint' => 'Région',
    'mailgun_endpoint_us' => 'États-Unis (api.mailgun.net)',
    'mailgun_endpoint_eu' => 'Union européenne (api.eu.mailgun.net)',

    'ses_key' => 'ID de clé d\'accès AWS',
    'ses_key_placeholder' => 'AKIAIOSFODNN7EXAMPLE',
    'ses_key_help' => 'Depuis vos identifiants AWS IAM',

    'ses_secret' => 'Clé d\'accès secrète AWS',
    'ses_secret_placeholder' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'ses_secret_help' => 'Gardez-la en sécurité, ne la partagez jamais',

    'ses_region' => 'Région AWS',
    'ses_region_help' => 'La région où SES est configuré',

    'postmark_token' => 'Token API serveur',
    'postmark_token_placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    'postmark_token_help' => 'Trouvez-le dans Postmark → Serveur → Tokens API',

    'resend_key' => 'Clé API',
    'resend_key_placeholder' => 're_xxxxxxxxxxxxxxxxxxxxxxxxxx',
    'resend_key_help' => 'Trouvez-la dans le tableau de bord Resend → Clés API',

    // From address
    'mail_from_address' => 'E-mail d\'expédition',
    'mail_from_address_placeholder' => 'noreply@votredomaine.com',
    'mail_from_address_help' => 'Les destinataires verront cette adresse comme expéditeur',

    'mail_from_name' => 'Nom d\'expédition',
    'mail_from_name_placeholder' => 'My Company',
    'mail_from_name_help' => 'Le nom affiché aux destinataires',

    // Test email
    'test_email' => 'Envoyer un e-mail de test',
    'test_email_sending' => 'Envoi...',
    'test_email_success' => 'E-mail de test envoyé ! Vérifiez votre boîte de réception.',
    'test_email_failed' => 'Échec de l\'envoi. Veuillez vérifier vos paramètres.',
    'test_email_check_spam' => 'Vous ne le voyez pas ? Vérifiez votre dossier spam.',

    // Common provider help
    'gmail_help_title' => 'Vous utilisez Gmail ?',
    'gmail_help_text' => 'Vous devrez créer un mot de passe d\'application dans les paramètres de votre compte Google. Les mots de passe ordinaires ne fonctionneront pas.',
    'gmail_help_link' => 'Comment créer un mot de passe d\'application',

    'provider_setup_guide' => 'Guide de configuration',
    'need_help' => 'Besoin d\'aide ?',
    'skip_for_now' => 'Configurer plus tard',
    'skip_warning' => 'Attention : sans e-mail configuré, les codes de connexion ne peuvent pas être envoyés. Vous pouvez configurer cela plus tard dans votre fichier .env.',

    // Validation
    'email_config_incomplete' => 'Veuillez compléter tous les paramètres e-mail requis.',
];
