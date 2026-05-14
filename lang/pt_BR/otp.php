<?php

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * OTP Authentication Translations - Portuguese (Brazil)
 */

return [
    // ─────────────────────────────────────────────────────────────────────────
    // EMAIL SUBJECTS
    // ─────────────────────────────────────────────────────────────────────────

    'email_subject_login' => 'Seu código de acesso seguro',
    'email_subject_verify' => 'Verifique seu endereço de e-mail',
    'email_subject_reset' => 'Redefinir sua senha',
    'email_subject_default' => 'Seu código de verificação',

    // ─────────────────────────────────────────────────────────────────────────
    // EMAIL CONTENT
    // ─────────────────────────────────────────────────────────────────────────

    'email_title_login' => 'Bem-vindo de volta ao :app',
    'email_title_verify_email' => 'Vamos verificar seu e-mail',
    'email_title_registration' => 'Você está quase lá',
    'email_title_password_reset' => 'Redefinir sua senha',
    'email_title_profile_update' => 'Verifique suas alterações de perfil',
    'email_title_default' => 'Seu código de verificação',

    'email_intro_login' => 'Aqui está seu código de acesso seguro. Basta digitá-lo na página de login para acessar sua conta.',
    'email_intro_verify_email' => 'Precisamos apenas confirmar que este e-mail pertence a você. Digite o código abaixo para verificar seu endereço.',
    'email_intro_registration' => 'Bem-vindo a bordo! Digite este código para completar seu cadastro e desbloquear sua nova conta.',
    'email_intro_password_reset' => 'Não se preocupe, acontece com os melhores. Use este código para criar uma nova senha.',
    'email_intro_profile_update' => 'Para sua segurança, confirme que é você quem está fazendo alterações no seu perfil.',
    'email_intro_default' => 'Aqui está o código de verificação que você solicitou.',

    'email_expires' => 'Válido por :minutes minutos',
    'email_security_notice' => 'Não solicitou isso? Nenhuma ação necessária — simplesmente ignore este e-mail. Sua conta permanece segura.',
    'email_subcopy' => 'Este código é apenas para você. Nunca pediremos que você o compartilhe com ninguém.',
    'verification_code' => 'Seu código de verificação',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 1 (EMAIL ENTRY)
    // ─────────────────────────────────────────────────────────────────────────

    'step1_title' => 'Entre na sua conta',
    'step1_subtitle' => 'Digite seu e-mail para continuar',
    'step1_subtitle_admin' => 'Entrar no Portal do Administrador',
    'step1_subtitle_partner' => 'Entrar no Portal do Parceiro',
    'step1_subtitle_staff' => 'Entrar no Portal da Equipe',
    'step1_continue' => 'Continuar',
    'step1_no_account' => 'Não tem uma conta?',
    'step1_create_account' => 'Crie uma',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 2 (METHOD SELECTION)
    // ─────────────────────────────────────────────────────────────────────────

    'step2_welcome_back' => 'Bem-vindo de volta',
    'step2_welcome' => 'Bem-vindo',
    'step2_change_email' => 'Alterar e-mail',
    'step2_enter_password' => 'Digite sua senha',
    'step2_sign_in' => 'Entrar',
    'step2_or_divider' => 'ou',
    'step2_send_code' => 'Enviar código de acesso',
    'step2_send_code_only' => 'Enviar código de acesso',
    'step2_code_info' => 'Enviaremos um código de 6 dígitos para seu e-mail.',
    'step2_forgot_password' => 'Esqueceu a senha?',
    'step2_set_password' => 'Configurar uma senha',
    'step2_passwordless_title' => 'Acesso sem Senha',
    'step2_passwordless_subtitle' => 'Enviaremos um código de 6 dígitos para seu e-mail para acesso seguro.',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 3 (OTP VERIFICATION)
    // ─────────────────────────────────────────────────────────────────────────

    'step3_title' => 'Digite o código de verificação',
    'step3_subtitle' => 'Enviamos um código de 6 dígitos para',
    'step3_didnt_receive' => 'Não recebeu o código?',
    'step3_resend' => 'Reenviar código',
    'step3_resend_in' => 'Reenviar em :seconds s',
    'step3_try_another' => 'Tentar outro método',
    'step3_verifying' => 'Verificando...',
    'step3_code_sent' => 'Código enviado!',
    'step3_check_email' => 'Verifique seu e-mail para o código de verificação.',
    'step3_panel_description' => 'Enviamos um código de 6 dígitos para o seu e-mail. Digite-o acima para concluir seu login.',

    // ─────────────────────────────────────────────────────────────────────────
    // PORTAL NAMES
    // ─────────────────────────────────────────────────────────────────────────

    'admin_portal' => 'Portal do Administrador',
    'partner_portal' => 'Portal do Parceiro',
    'staff_portal' => 'Portal do Funcionário',

    // ─────────────────────────────────────────────────────────────────────────
    // PIN INPUT
    // ─────────────────────────────────────────────────────────────────────────

    'pin_digit_label' => 'Dígito :position de :total',
    'pin_placeholder' => '·',

    // ─────────────────────────────────────────────────────────────────────────
    // SUCCESS MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'code_sent_success' => 'Código de verificação enviado para :email',
    'verification_success' => 'Verificação bem-sucedida!',
    'login_success' => 'Bem-vindo de volta!',

    // ─────────────────────────────────────────────────────────────────────────
    // ERROR MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'rate_limited' => 'Muitas tentativas. Aguarde :seconds segundos.',
    'code_expired' => 'Este código expirou. Solicite um novo.',
    'code_invalid' => 'Código inválido. :remaining tentativa(s) restante(s).',
    'code_locked' => 'Muitas tentativas falhas. Solicite um novo código.',
    'user_not_found' => 'Nenhuma conta encontrada com este endereço de e-mail.',
    'account_not_found_create' => 'Nenhuma conta encontrada para :email. <a href=":register_url" class="font-semibold text-primary-600 dark:text-primary-400 hover:underline">Criar uma conta?</a>',
    'user_inactive' => 'Esta conta não está ativa. Entre em contato com o suporte.',
    'send_failed' => 'Falha ao enviar código de verificação. Tente novamente.',
    'invalid_request' => 'Solicitação inválida. Tente novamente.',

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRATION WITH OTP
    // ─────────────────────────────────────────────────────────────────────────

    'registration_verify_title' => 'Verifique seu e-mail',
    'registration_verify_subtitle' => 'Enviamos um código de 6 dígitos para',
    'registration_start_over' => 'Começar de novo',
    'registration_almost_there' => 'Quase lá!',
    'registration_verify_panel_title' => 'Mais um passo',
    'registration_verify_panel_text' => 'Digite o código de verificação que enviamos para seu e-mail para completar seu cadastro.',
    'registration_benefit_1' => 'Sem senha para lembrar',
    'registration_benefit_2' => 'Login seguro sem senha',
    'registration_benefit_3' => 'Defina uma senha a qualquer momento no seu perfil',
    'registration_success' => 'Bem-vindo! Sua conta foi criada com sucesso.',
    'registration_email_exists' => 'Já existe uma conta com este e-mail. Faça login.',
    'registration_otp_sent' => 'Um código de verificação foi enviado para :email',
    'registration_almost_done' => 'Digite o código de 6 dígitos que enviamos para seu e-mail para verificar sua conta e completar o cadastro.',
    'verify_email_title' => 'Verifique Seu E-mail',
    'secure_verification' => 'Verificação Segura',
    'code_expires_minutes' => 'Código expira em :minutes minutos',
    'never_share_code' => 'Nunca compartilhe este código com ninguém',
    'check_spam_folder' => 'Verifique a pasta de spam se não encontrar o e-mail',

    // ─────────────────────────────────────────────────────────────────────────
    // PIN INPUT COMPONENT
    // ─────────────────────────────────────────────────────────────────────────

    'pin_aria_label' => 'Código de verificação',
    'pin_digit_aria' => 'Dígito :n de :total',

    // ─────────────────────────────────────────────────────────────────────────
    // PROFILE UPDATE OTP VERIFICATION
    // ─────────────────────────────────────────────────────────────────────────

    'profile_verification_title' => 'Verifique Sua Identidade',
    'profile_verification_subtitle' => 'Um código de verificação é necessário para salvar alterações',
    'profile_send_code_info' => 'Enviaremos um código de 6 dígitos para :email para confirmar que é você.',
    'profile_send_code_info_generic' => 'Enviaremos um código de 6 dígitos para confirmar que é você.',
    'profile_send_code' => 'Enviar Código de Verificação',
    'profile_verified' => 'Identidade verificada! Agora você pode salvar suas alterações.',
    'profile_otp_required' => 'Verifique sua identidade com o código enviado para seu e-mail.',
    'verify_to_save' => 'Verificar para Salvar Alterações',
    'identity_verified' => 'Identidade Verificada!',
    'verification_failed' => 'Falha na verificação. Tente novamente.',
    'code_sent_success_short' => 'Código enviado!',
    'sending' => 'Enviando...',

    // ─────────────────────────────────────────────────────────────────────────
    // DEMO MODE
    // ─────────────────────────────────────────────────────────────────────────

    'demo_otp_notice' => 'Modo demonstração: Use o código :code para entrar.',

    // ─────────────────────────────────────────────────────────────────────────
    // LOADING STATE
    // ─────────────────────────────────────────────────────────────────────────

    'please_wait' => 'Por favor aguarde...',
];
