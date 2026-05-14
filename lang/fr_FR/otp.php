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

    'email_subject_login' => 'Votre code de connexion sécurisé',
    'email_subject_verify' => 'Vérifiez votre adresse e-mail',
    'email_subject_reset' => 'Réinitialisez votre mot de passe',
    'email_subject_default' => 'Votre code de vérification',

    // ─────────────────────────────────────────────────────────────────────────
    // EMAIL CONTENT
    // ─────────────────────────────────────────────────────────────────────────

    'email_title_login' => 'Bon retour sur :app',
    'email_title_verify_email' => 'Vérifions votre e-mail',
    'email_title_registration' => 'Vous y êtes presque',
    'email_title_password_reset' => 'Réinitialisez votre mot de passe',
    'email_title_profile_update' => 'Vérifiez les modifications de votre profil',
    'email_title_default' => 'Votre code de vérification',

    'email_intro_login' => 'Voici votre code de connexion sécurisé. Saisissez-le simplement sur la page de connexion pour accéder à votre compte.',
    'email_intro_verify_email' => 'Nous devons simplement vérifier que cette adresse e-mail vous appartient. Entrez le code ci-dessous pour confirmer votre adresse.',
    'email_intro_registration' => 'Bienvenue ! Entrez ce code pour finaliser votre inscription et activer votre nouveau compte.',
    'email_intro_password_reset' => 'Pas d\'inquiétude, cela arrive à tout le monde. Utilisez ce code pour créer un nouveau mot de passe.',
    'email_intro_profile_update' => 'Pour votre sécurité, veuillez confirmer que c\'est bien vous qui modifiez votre profil.',
    'email_intro_default' => 'Voici le code de vérification que vous avez demandé.',

    'email_expires' => 'Valable :minutes minutes',
    'email_security_notice' => 'Vous n\'avez pas demandé ce code ? Aucune action n\'est requise — ignorez simplement cet e-mail. Votre compte reste sécurisé.',
    'email_subcopy' => 'Ce code est strictement confidentiel. Nous ne vous demanderons jamais de le partager avec qui que ce soit.',
    'verification_code' => 'Votre code de vérification',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 1 (EMAIL ENTRY)
    // ─────────────────────────────────────────────────────────────────────────

    'step1_title' => 'Connectez-vous à votre compte',
    'step1_subtitle' => 'Entrez votre e-mail pour continuer',
    'step1_subtitle_admin' => 'Connectez-vous au Portail Administrateur',
    'step1_subtitle_partner' => 'Connectez-vous au Portail Partenaire',
    'step1_subtitle_staff' => 'Connectez-vous au Portail Personnel',
    'step1_continue' => 'Continuer',
    'step1_no_account' => 'Vous n\'avez pas de compte ?',
    'step1_create_account' => 'Créez-en un',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 2 (METHOD SELECTION)
    // ─────────────────────────────────────────────────────────────────────────

    'step2_welcome_back' => 'Bon retour',
    'step2_welcome' => 'Bienvenue',
    'step2_change_email' => 'Changer d\'e-mail',
    'step2_enter_password' => 'Entrez votre mot de passe',
    'step2_sign_in' => 'Se connecter',
    'step2_or_divider' => 'ou',
    'step2_send_code' => 'M\'envoyer un code de connexion',
    'step2_send_code_only' => 'M\'envoyer un code de connexion',
    'step2_code_info' => 'Nous vous enverrons un code à 6 chiffres par e-mail.',
    'step2_forgot_password' => 'Mot de passe oublié ?',
    'step2_set_password' => 'Définir un mot de passe à la place',
    'step2_passwordless_title' => 'Connexion sans mot de passe',
    'step2_passwordless_subtitle' => 'Nous vous enverrons un code à 6 chiffres par e-mail pour un accès sécurisé.',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 3 (OTP VERIFICATION)
    // ─────────────────────────────────────────────────────────────────────────

    'step3_title' => 'Entrez le code de vérification',
    'step3_subtitle' => 'Nous avons envoyé un code à 6 chiffres à',
    'step3_didnt_receive' => 'Vous n\'avez pas reçu le code ?',
    'step3_resend' => 'Renvoyer le code',
    'step3_resend_in' => 'Renvoyer dans :seconds s',
    'step3_try_another' => 'Essayer une autre méthode',
    'step3_verifying' => 'Vérification...',
    'step3_code_sent' => 'Code envoyé !',
    'step3_check_email' => 'Consultez votre e-mail pour le code de vérification.',
    'step3_panel_description' => 'Nous avons envoyé un code à 6 chiffres à votre adresse e-mail. Entrez-le ci-dessus pour terminer votre connexion.',

    // ─────────────────────────────────────────────────────────────────────────
    // PORTAL NAMES
    // ─────────────────────────────────────────────────────────────────────────

    'admin_portal' => 'Portail Admin',
    'partner_portal' => 'Portail Partenaire',
    'staff_portal' => 'Portail Employé',

    // ─────────────────────────────────────────────────────────────────────────
    // PIN INPUT
    // ─────────────────────────────────────────────────────────────────────────

    'pin_digit_label' => 'Chiffre :position sur :total',
    'pin_placeholder' => '·',

    // ─────────────────────────────────────────────────────────────────────────
    // SUCCESS MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'code_sent_success' => 'Code de vérification envoyé à :email',
    'verification_success' => 'Vérification réussie !',
    'login_success' => 'Bon retour !',

    // ─────────────────────────────────────────────────────────────────────────
    // ERROR MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'rate_limited' => 'Trop de tentatives. Veuillez patienter :seconds secondes.',
    'code_expired' => 'Ce code a expiré. Veuillez en demander un nouveau.',
    'code_invalid' => 'Code invalide. :remaining tentative(s) restante(s).',
    'code_locked' => 'Trop de tentatives échouées. Veuillez demander un nouveau code.',
    'user_not_found' => 'Aucun compte trouvé avec cette adresse e-mail.',
    'account_not_found_create' => 'Aucun compte trouvé pour :email. <a href=":register_url" class="font-semibold text-primary-600 dark:text-primary-400 hover:underline">Créer un compte ?</a>',
    'user_inactive' => 'Ce compte n\'est pas actif. Veuillez contacter le support.',
    'send_failed' => 'Échec de l\'envoi du code de vérification. Veuillez réessayer.',
    'invalid_request' => 'Requête invalide. Veuillez réessayer.',

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRATION WITH OTP
    // ─────────────────────────────────────────────────────────────────────────

    'registration_verify_title' => 'Vérifiez votre e-mail',
    'registration_verify_subtitle' => 'Nous avons envoyé un code à 6 chiffres à',
    'registration_start_over' => 'Recommencer',
    'registration_almost_there' => 'Vous y êtes presque !',
    'registration_verify_panel_title' => 'Plus qu\'une étape',
    'registration_verify_panel_text' => 'Entrez le code de vérification que nous avons envoyé à votre adresse e-mail pour finaliser votre inscription.',
    'registration_benefit_1' => 'Aucun mot de passe à retenir',
    'registration_benefit_2' => 'Connexion sécurisée sans mot de passe',
    'registration_benefit_3' => 'Définissez un mot de passe à tout moment depuis votre profil',
    'registration_success' => 'Bienvenue ! Votre compte a été créé avec succès.',
    'registration_email_exists' => 'Un compte avec cette adresse e-mail existe déjà. Veuillez vous connecter.',
    'registration_otp_sent' => 'Un code de vérification a été envoyé à :email',
    'registration_almost_done' => 'Entrez le code à 6 chiffres que nous avons envoyé à votre adresse e-mail pour vérifier votre compte et finaliser votre inscription.',
    'verify_email_title' => 'Vérifiez votre e-mail',
    'secure_verification' => 'Vérification sécurisée',
    'code_expires_minutes' => 'Le code expire dans :minutes minutes',
    'never_share_code' => 'Ne partagez jamais ce code avec qui que ce soit',
    'check_spam_folder' => 'Vérifiez votre dossier spam si vous ne voyez pas l\'e-mail',

    // ─────────────────────────────────────────────────────────────────────────
    // PIN INPUT COMPONENT
    // ─────────────────────────────────────────────────────────────────────────

    'pin_aria_label' => 'Code de vérification',
    'pin_digit_aria' => 'Chiffre :n sur :total',

    // ─────────────────────────────────────────────────────────────────────────
    // PROFILE UPDATE OTP VERIFICATION
    // ─────────────────────────────────────────────────────────────────────────

    'profile_verification_title' => 'Vérifiez votre identité',
    'profile_verification_subtitle' => 'Un code de vérification est requis pour enregistrer les modifications',
    'profile_send_code_info' => 'Nous enverrons un code à 6 chiffres à :email pour vérifier que c\'est bien vous.',
    'profile_send_code_info_generic' => 'Nous enverrons un code à 6 chiffres pour vérifier que c\'est bien vous.',
    'profile_send_code' => 'Envoyer le code de vérification',
    'profile_verified' => 'Identité vérifiée ! Vous pouvez maintenant enregistrer vos modifications.',
    'profile_otp_required' => 'Veuillez vérifier votre identité avec le code envoyé à votre adresse e-mail.',
    'verify_to_save' => 'Vérifier pour enregistrer',
    'identity_verified' => 'Identité vérifiée !',
    'verification_failed' => 'La vérification a échoué. Veuillez réessayer.',
    'code_sent_success_short' => 'Code envoyé !',
    'sending' => 'Envoi...',

    // ─────────────────────────────────────────────────────────────────────────
    // DEMO MODE
    // ─────────────────────────────────────────────────────────────────────────

    'demo_otp_notice' => 'Mode démo : utilisez le code :code pour vous connecter.',

    // ─────────────────────────────────────────────────────────────────────────
    // LOADING STATE
    // ─────────────────────────────────────────────────────────────────────────

    'please_wait' => 'Veuillez patienter...',
];
