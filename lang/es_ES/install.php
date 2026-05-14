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

    'installation' => 'Instalación',
    'install' => 'Instalar',
    'install_script' => 'Instalar el script',
    'server_requirements' => 'Requisitos del servidor',
    'requirements' => 'Requisitos',
    'server_requirements_text' => 'Las siguientes verificaciones determinan si el script funcionará en tu servidor, aunque no se puede garantizar una compatibilidad completa.',
    'resolve_missing_requirements' => 'Resuelve los requisitos faltantes para continuar.',
    'next' => 'Siguiente',
    'prev' => 'Anterior',
    'configuration' => 'Configuración',
    'confirm' => 'Confirmar',
    'app' => 'Aplicación',
    'name' => 'Nombre',
    'email' => 'Correo electrónico',
    'optional' => 'Opcional',
    '_optional_' => '(opcional)',
    'optional_email_config' => 'Puedes omitir la configuración a continuación por ahora. Se puede configurar más tarde en el archivo .env en la raíz del sitio. Nota: la funcionalidad de correo requiere esta configuración.',
    'logo' => 'Logo',
    'logo_dark' => 'Logo (Modo oscuro)',
    'user' => 'Usuario',
    'email_address' => 'Dirección de correo',
    'time_zone' => 'Zona horaria',
    'password' => 'Contraseña',
    'confirm_password' => 'Confirmar contraseña',
    'passwords_must_match' => 'Las contraseñas deben coincidir.',
    'email_address_app' => 'Dirección de correo usada por la aplicación para enviar correos',
    'email_address_name_app' => 'Nombre del remitente',
    'admin_login' => 'Inicio de sesión de administrador',
    'download_log' => 'Descargar archivo de registro',
    'refresh_page' => 'Actualiza esta página e inténtalo de nuevo',
    'after_installation' => 'Una vez completada la instalación, usa las credenciales de administrador proporcionadas anteriormente para acceder al panel en :admin_url.',
    'install_error' => 'El servidor devolvió un error. Consulta el archivo de registro (/storage/logs) para más detalles.',
    'database_info' => 'SQLite ofrece alto rendimiento y es adecuado para el 95% de los usuarios. Para volúmenes diarios más altos, considera MySQL o MariaDB.',
    'install_acknowledge' => 'Al instalar nuestro software, reconoces que NowSquare no es responsable de los problemas derivados de su uso. Recuerda que cualquier software puede contener errores. Si encuentras alguno, contáctanos por correo o ticket de soporte para que podamos corregirlo rápidamente.',

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    */

    'email_settings' => 'Envío de correos',
    'email_critical_title' => 'El correo es esencial',
    'email_critical_description' => 'Tus clientes recibirán códigos de un solo uso (OTP) por correo para iniciar sesión. Sin correo funcional, nadie puede acceder al sistema — incluyéndote a ti.',
    'email_why_matters' => 'Por qué es importante',
    'email_otp_explanation' => 'Usamos autenticación sin contraseña. En lugar de memorizar contraseñas, los usuarios reciben un código seguro por correo en cada inicio de sesión. Simple, seguro, moderno.',

    'mail_driver' => '¿Cómo enviar los correos?',
    'mail_driver_help' => 'Elige el servicio que entregará tus correos a los clientes.',

    // Driver descriptions
    'driver_smtp' => 'Servidor SMTP',
    'driver_smtp_desc' => 'Conéctate a cualquier servidor de correo. Funciona con Gmail, Outlook, tu hosting o cualquier servicio SMTP.',
    'driver_smtp_best_for' => 'Ideal para: La mayoría de usuarios, proveedores de hosting',

    'driver_mailgun' => 'Mailgun',
    'driver_mailgun_desc' => 'Servicio profesional de envío de correos de Mailchimp. Fiable, escalable, con análisis detallados.',
    'driver_mailgun_best_for' => 'Ideal para: Empresas en crecimiento, alto volumen',

    'driver_ses' => 'Amazon SES',
    'driver_ses_desc' => 'Envío de correos económico a gran escala desde AWS. Excelente entregabilidad y precios.',
    'driver_ses_best_for' => 'Ideal para: Usuarios de AWS, operaciones a gran escala',

    'driver_postmark' => 'Postmark',
    'driver_postmark_desc' => 'Diseñado específicamente para correos transaccionales. Velocidad de entrega de referencia.',
    'driver_postmark_best_for' => 'Ideal para: Aplicaciones críticas en tiempo',

    'driver_resend' => 'Resend',
    'driver_resend_desc' => 'API de correo moderna diseñada para desarrolladores. Simple, fiable, excelente experiencia de desarrollo.',
    'driver_resend_best_for' => 'Ideal para: Equipos orientados al desarrollo',

    'driver_sendmail' => 'Sendmail',
    'driver_sendmail_desc' => 'Usa el sistema de correo integrado de tu servidor. No se necesita servicio externo.',
    'driver_sendmail_best_for' => 'Ideal para: Configuraciones simples, servidores Linux',

    'driver_mailpit' => 'Mailpit (Pruebas)',
    'driver_mailpit_desc' => 'Captura todos los correos localmente para desarrollo. No se envían correos reales.',
    'driver_mailpit_best_for' => 'Ideal para: Solo desarrollo local',

    'driver_log' => 'Archivo de registro (Desarrollo)',
    'driver_log_desc' => 'Escribe los correos en archivos de registro en lugar de enviarlos. Perfecto para pruebas iniciales.',
    'driver_log_best_for' => 'Ideal para: Pruebas rápidas, depuración',

    // SMTP Fields
    'smtp_host' => 'Servidor SMTP',
    'smtp_host_placeholder' => 'smtp.ejemplo.com',
    'smtp_host_help' => 'La dirección de tu servidor de correo',

    'smtp_port' => 'Puerto',
    'smtp_port_help' => 'Puertos comunes: 587 (TLS), 465 (SSL), 25 (sin cifrar)',

    'smtp_username' => 'Nombre de usuario',
    'smtp_username_placeholder' => 'tu-correo@ejemplo.com',
    'smtp_username_help' => 'Generalmente tu dirección de correo completa',

    'smtp_password' => 'Contraseña',
    'smtp_password_placeholder' => 'Tu contraseña de correo o contraseña de aplicación',
    'smtp_password_help' => 'Para Gmail/Google, usa una contraseña de aplicación',

    'smtp_encryption' => 'Seguridad',
    'smtp_encryption_help' => 'TLS es recomendado para la mayoría de proveedores',
    'smtp_encryption_tls' => 'TLS (Recomendado)',
    'smtp_encryption_ssl' => 'SSL',
    'smtp_encryption_none' => 'Ninguno (No recomendado)',

    // Provider-specific
    'mailgun_domain' => 'Dominio Mailgun',
    'mailgun_domain_placeholder' => 'mg.tudominio.com',
    'mailgun_domain_help' => 'Tu dominio de envío verificado en Mailgun',

    'mailgun_secret' => 'Clave API',
    'mailgun_secret_placeholder' => 'key-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'mailgun_secret_help' => 'Encuéntrala en Mailgun → Configuración → Claves API',

    'mailgun_endpoint' => 'Región',
    'mailgun_endpoint_us' => 'Estados Unidos (api.mailgun.net)',
    'mailgun_endpoint_eu' => 'Unión Europea (api.eu.mailgun.net)',

    'ses_key' => 'ID de clave de acceso AWS',
    'ses_key_placeholder' => 'AKIAIOSFODNN7EXAMPLE',
    'ses_key_help' => 'Desde tus credenciales AWS IAM',

    'ses_secret' => 'Clave de acceso secreta AWS',
    'ses_secret_placeholder' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'ses_secret_help' => 'Mantenla segura, nunca la compartas',

    'ses_region' => 'Región AWS',
    'ses_region_help' => 'La región donde está configurado SES',

    'postmark_token' => 'Token API del servidor',
    'postmark_token_placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    'postmark_token_help' => 'Encuéntralo en Postmark → Servidor → Tokens API',

    'resend_key' => 'Clave API',
    'resend_key_placeholder' => 're_xxxxxxxxxxxxxxxxxxxxxxxxxx',
    'resend_key_help' => 'Encuéntrala en el panel de Resend → Claves API',

    // From address
    'mail_from_address' => 'Correo de envío',
    'mail_from_address_placeholder' => 'noreply@tudominio.com',
    'mail_from_address_help' => 'Los destinatarios verán esta dirección como remitente',

    'mail_from_name' => 'Nombre de envío',
    'mail_from_name_placeholder' => 'My Company',
    'mail_from_name_help' => 'El nombre mostrado a los destinatarios',

    // Test email
    'test_email' => 'Enviar correo de prueba',
    'test_email_sending' => 'Enviando...',
    'test_email_success' => '¡Correo de prueba enviado! Revisa tu bandeja de entrada.',
    'test_email_failed' => 'Error al enviar. Por favor verifica tu configuración.',
    'test_email_check_spam' => '¿No lo ves? Revisa tu carpeta de spam.',

    // Common provider help
    'gmail_help_title' => '¿Usas Gmail?',
    'gmail_help_text' => 'Necesitarás crear una contraseña de aplicación en la configuración de tu cuenta de Google. Las contraseñas normales no funcionarán.',
    'gmail_help_link' => 'Cómo crear una contraseña de aplicación',

    'provider_setup_guide' => 'Guía de configuración',
    'need_help' => '¿Necesitas ayuda?',
    'skip_for_now' => 'Configurar después',
    'skip_warning' => 'Atención: sin correo configurado, los códigos de inicio de sesión no pueden enviarse. Puedes configurar esto más tarde en tu archivo .env.',

    // Validation
    'email_config_incomplete' => 'Por favor completa todos los ajustes de correo requeridos.',
];
