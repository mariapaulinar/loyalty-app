<?php

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * OTP Authentication Translations
 */

return [
    // EMAIL SUBJECTS
    'email_subject_login' => 'Tu código de inicio de sesión seguro',
    'email_subject_verify' => 'Verifica tu dirección de correo electrónico',
    'email_subject_reset' => 'Restablece tu contraseña',
    'email_subject_default' => 'Tu código de verificación',

    // EMAIL CONTENT
    'email_title_login' => 'Bienvenido de nuevo a :app',
    'email_title_verify_email' => 'Verifiquemos tu correo',
    'email_title_registration' => 'Ya casi está',
    'email_title_password_reset' => 'Restablece tu contraseña',
    'email_title_profile_update' => 'Verifica los cambios de tu perfil',
    'email_title_default' => 'Tu código de verificación',

    'email_intro_login' => 'Aquí está tu código de inicio de sesión seguro. Simplemente ingrésalo en la página de inicio de sesión para acceder a tu cuenta.',
    'email_intro_verify_email' => 'Solo necesitamos verificar que esta dirección de correo te pertenece. Ingresa el código a continuación para confirmar tu dirección.',
    'email_intro_registration' => '¡Bienvenido! Ingresa este código para completar tu registro y activar tu nueva cuenta.',
    'email_intro_password_reset' => 'No te preocupes, le pasa a todo el mundo. Usa este código para crear una nueva contraseña.',
    'email_intro_profile_update' => 'Por tu seguridad, confirma que eres tú quien está modificando tu perfil.',
    'email_intro_default' => 'Aquí está el código de verificación que solicitaste.',

    'email_expires' => 'Válido por :minutes minutos',
    'email_security_notice' => '¿No solicitaste este código? No se requiere ninguna acción — simplemente ignora este correo. Tu cuenta permanece segura.',
    'email_subcopy' => 'Este código es estrictamente confidencial. Nunca te pediremos que lo compartas con nadie.',
    'verification_code' => 'Tu código de verificación',

    // LOGIN FLOW - STEP 1
    'step1_title' => 'Inicia sesión en tu cuenta',
    'step1_subtitle' => 'Ingresa tu correo para continuar',
    'step1_subtitle_admin' => 'Iniciar sesión en el Portal de Administración',
    'step1_subtitle_partner' => 'Iniciar sesión en el Portal de Socios',
    'step1_subtitle_staff' => 'Iniciar sesión en el Portal de Personal',
    'step1_continue' => 'Continuar',
    'step1_no_account' => '¿No tienes cuenta?',
    'step1_create_account' => 'Crea una',

    // LOGIN FLOW - STEP 2
    'step2_welcome_back' => 'Bienvenido de nuevo',
    'step2_welcome' => 'Bienvenido',
    'step2_change_email' => 'Cambiar correo',
    'step2_enter_password' => 'Ingresa tu contraseña',
    'step2_sign_in' => 'Iniciar sesión',
    'step2_or_divider' => 'o',
    'step2_send_code' => 'Enviarme un código de inicio de sesión',
    'step2_send_code_only' => 'Enviarme un código de inicio de sesión',
    'step2_code_info' => 'Te enviaremos un código de 6 dígitos por correo.',
    'step2_forgot_password' => '¿Olvidaste tu contraseña?',
    'step2_set_password' => 'Establecer una contraseña en su lugar',
    'step2_passwordless_title' => 'Inicio de sesión sin contraseña',
    'step2_passwordless_subtitle' => 'Te enviaremos un código de 6 dígitos por correo para un acceso seguro.',

    // LOGIN FLOW - STEP 3
    'step3_title' => 'Ingresa el código de verificación',
    'step3_subtitle' => 'Enviamos un código de 6 dígitos a',
    'step3_didnt_receive' => '¿No recibiste el código?',
    'step3_resend' => 'Reenviar código',
    'step3_resend_in' => 'Reenviar en :seconds s',
    'step3_try_another' => 'Probar otro método',
    'step3_verifying' => 'Verificando...',
    'step3_code_sent' => '¡Código enviado!',
    'step3_check_email' => 'Revisa tu correo para el código de verificación.',
    'step3_panel_description' => 'Enviamos un código de 6 dígitos a tu correo. Ingrésalo arriba para completar tu inicio de sesión.',

    // PORTAL NAMES
    'admin_portal' => 'Portal de Administración',
    'partner_portal' => 'Portal de Socios',
    'staff_portal' => 'Portal de Personal',

    // PIN INPUT
    'pin_digit_label' => 'Dígito :position de :total',
    'pin_placeholder' => '·',

    // SUCCESS MESSAGES
    'code_sent_success' => 'Código de verificación enviado a :email',
    'verification_success' => '¡Verificación exitosa!',
    'login_success' => '¡Bienvenido de nuevo!',

    // ERROR MESSAGES
    'rate_limited' => 'Demasiados intentos. Por favor espera :seconds segundos.',
    'code_expired' => 'Este código ha expirado. Por favor solicita uno nuevo.',
    'code_invalid' => 'Código inválido. Quedan :remaining intento(s).',
    'code_locked' => 'Demasiados intentos fallidos. Por favor solicita un nuevo código.',
    'user_not_found' => 'No se encontró ninguna cuenta con este correo electrónico.',
    'account_not_found_create' => 'No se encontró cuenta para :email. <a href=":register_url" class="font-semibold text-primary-600 dark:text-primary-400 hover:underline">¿Crear una cuenta?</a>',
    'user_inactive' => 'Esta cuenta no está activa. Por favor contacta al soporte.',
    'send_failed' => 'Error al enviar el código de verificación. Por favor inténtalo de nuevo.',
    'invalid_request' => 'Solicitud inválida. Por favor inténtalo de nuevo.',

    // REGISTRATION WITH OTP
    'registration_verify_title' => 'Verifica tu correo',
    'registration_verify_subtitle' => 'Enviamos un código de 6 dígitos a',
    'registration_start_over' => 'Empezar de nuevo',
    'registration_almost_there' => '¡Ya casi está!',
    'registration_verify_panel_title' => 'Un paso más',
    'registration_verify_panel_text' => 'Ingresa el código de verificación que enviamos a tu correo para completar tu registro.',
    'registration_benefit_1' => 'Sin contraseñas que recordar',
    'registration_benefit_2' => 'Inicio de sesión seguro sin contraseña',
    'registration_benefit_3' => 'Establece una contraseña en cualquier momento desde tu perfil',
    'registration_success' => '¡Bienvenido! Tu cuenta ha sido creada exitosamente.',
    'registration_email_exists' => 'Ya existe una cuenta con este correo electrónico. Por favor inicia sesión.',
    'registration_otp_sent' => 'Se ha enviado un código de verificación a :email',
    'registration_almost_done' => 'Ingresa el código de 6 dígitos que enviamos a tu correo para verificar tu cuenta y completar tu registro.',
    'verify_email_title' => 'Verifica tu correo',
    'secure_verification' => 'Verificación segura',
    'code_expires_minutes' => 'El código expira en :minutes minutos',
    'never_share_code' => 'Nunca compartas este código con nadie',
    'check_spam_folder' => 'Revisa tu carpeta de spam si no ves el correo',

    // PIN INPUT COMPONENT
    'pin_aria_label' => 'Código de verificación',
    'pin_digit_aria' => 'Dígito :n de :total',

    // PROFILE UPDATE OTP VERIFICATION
    'profile_verification_title' => 'Verifica tu identidad',
    'profile_verification_subtitle' => 'Se requiere un código de verificación para guardar los cambios',
    'profile_send_code_info' => 'Enviaremos un código de 6 dígitos a :email para verificar que eres tú.',
    'profile_send_code_info_generic' => 'Enviaremos un código de 6 dígitos para verificar que eres tú.',
    'profile_send_code' => 'Enviar código de verificación',
    'profile_verified' => '¡Identidad verificada! Ahora puedes guardar tus cambios.',
    'profile_otp_required' => 'Por favor verifica tu identidad con el código enviado a tu correo.',
    'verify_to_save' => 'Verificar para guardar',
    'identity_verified' => '¡Identidad verificada!',
    'verification_failed' => 'La verificación falló. Por favor inténtalo de nuevo.',
    'code_sent_success_short' => '¡Código enviado!',
    'sending' => 'Enviando...',

    // DEMO MODE
    'demo_otp_notice' => 'Modo demo: usa el código :code para iniciar sesión.',

    // LOADING STATE
    'please_wait' => 'Por favor espera...',
];
