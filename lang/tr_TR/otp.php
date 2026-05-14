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

    'email_subject_login' => 'Güvenli giriş kodunuz',
    'email_subject_verify' => 'E-posta adresinizi doğrulayın',
    'email_subject_reset' => 'Şifrenizi sıfırlayın',
    'email_subject_default' => 'Doğrulama kodunuz',

    // ─────────────────────────────────────────────────────────────────────────
    // EMAIL CONTENT
    // ─────────────────────────────────────────────────────────────────────────

    'email_title_login' => ':app uygulamasına tekrar hoş geldiniz',
    'email_title_verify_email' => 'E-postanızı doğrulayalım',
    'email_title_registration' => 'Neredeyse tamam',
    'email_title_password_reset' => 'Şifrenizi sıfırlayın',
    'email_title_profile_update' => 'Profil değişikliklerinizi doğrulayın',
    'email_title_default' => 'Doğrulama kodunuz',

    'email_intro_login' => 'Güvenli giriş kodunuz burada. Hesabınıza erişmek için giriş sayfasına girin.',
    'email_intro_verify_email' => 'Bu e-postanın size ait olduğundan emin olmamız gerekiyor. Adresinizi doğrulamak için aşağıdaki kodu girin.',
    'email_intro_registration' => 'Hoş geldiniz! Kaydınızı tamamlamak ve hesabınızı açmak için bu kodu girin.',
    'email_intro_password_reset' => 'Merak etmeyin, olabilir. Yeni bir şifre oluşturmak için bu kodu kullanın.',
    'email_intro_profile_update' => 'Güvenliğiniz için, profilinizde değişiklik yapanın siz olduğunuzu doğrulayın.',
    'email_intro_default' => 'İstediğiniz doğrulama kodu burada.',

    'email_expires' => ':minutes dakika geçerlidir',
    'email_security_notice' => 'Bunu siz istemediniz mi? Bir işlem yapmanıza gerek yok — bu e-postayı yok sayın. Hesabınız güvende kalır.',
    'email_subcopy' => 'Bu kod yalnızca size özeldir. Hiçbir zaman kimseyle paylaşmanızı istemeyiz.',
    'verification_code' => 'Doğrulama kodunuz',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 1 (EMAIL ENTRY)
    // ─────────────────────────────────────────────────────────────────────────

    'step1_title' => 'Hesabınıza giriş yapın',
    'step1_subtitle' => 'Devam etmek için e-postanızı girin',
    'step1_subtitle_admin' => 'Yönetici Portalına giriş yapın',
    'step1_subtitle_partner' => 'İş Ortağı Portalına giriş yapın',
    'step1_subtitle_staff' => 'Personel Portalına giriş yapın',
    'step1_continue' => 'Devam et',
    'step1_no_account' => 'Hesabınız yok mu?',
    'step1_create_account' => 'Bir hesap oluşturun',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 2 (METHOD SELECTION)
    // ─────────────────────────────────────────────────────────────────────────

    'step2_welcome_back' => 'Tekrar hoş geldiniz',
    'step2_welcome' => 'Hoş geldiniz',
    'step2_change_email' => 'E-postayı değiştir',
    'step2_enter_password' => 'Şifrenizi girin',
    'step2_sign_in' => 'Giriş yap',
    'step2_or_divider' => 'veya',
    'step2_send_code' => 'Bana giriş kodu gönder',
    'step2_send_code_only' => 'Bana giriş kodu gönder',
    'step2_code_info' => 'E-postanıza 6 haneli bir kod göndereceğiz.',
    'step2_forgot_password' => 'Şifrenizi mi unuttunuz?',
    'step2_set_password' => 'Bunun yerine şifre ayarlayın',
    'step2_passwordless_title' => 'Şifresiz Giriş',
    'step2_passwordless_subtitle' => 'Güvenli erişim için e-postanıza 6 haneli bir kod göndereceğiz.',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 3 (OTP VERIFICATION)
    // ─────────────────────────────────────────────────────────────────────────

    'step3_title' => 'Doğrulama kodunu girin',
    'step3_subtitle' => '6 haneli bir kod gönderdik:',
    'step3_didnt_receive' => 'Kodu almadınız mı?',
    'step3_resend' => 'Kodu tekrar gönder',
    'step3_resend_in' => ':seconds sn içinde tekrar gönder',
    'step3_try_another' => 'Başka bir yöntem deneyin',
    'step3_verifying' => 'Doğrulanıyor...',
    'step3_code_sent' => 'Kod gönderildi!',
    'step3_check_email' => 'Doğrulama kodu için e-postanızı kontrol edin.',
    'step3_panel_description' => 'E-postanıza 6 haneli bir kod gönderdik. Oturum açmayı tamamlamak için yukarıya girin.',

    // ─────────────────────────────────────────────────────────────────────────
    // PORTAL NAMES
    // ─────────────────────────────────────────────────────────────────────────

    'admin_portal' => 'Yönetici Portalı',
    'partner_portal' => 'İş Ortağı Portalı',
    'staff_portal' => 'Personel Portalı',

    // ─────────────────────────────────────────────────────────────────────────
    // PIN INPUT
    // ─────────────────────────────────────────────────────────────────────────

    'pin_digit_label' => ':total içinde :position. hane',
    'pin_placeholder' => '·',

    // ─────────────────────────────────────────────────────────────────────────
    // SUCCESS MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'code_sent_success' => 'Doğrulama kodu :email adresine gönderildi',
    'verification_success' => 'Doğrulama başarılı!',
    'login_success' => 'Tekrar hoş geldiniz!',

    // ─────────────────────────────────────────────────────────────────────────
    // ERROR MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'rate_limited' => 'Çok fazla deneme. Lütfen :seconds saniye bekleyin.',
    'code_expired' => 'Bu kodun süresi doldu. Lütfen yeni bir kod isteyin.',
    'code_invalid' => 'Geçersiz kod. :remaining deneme hakkınız kaldı.',
    'code_locked' => 'Çok fazla hatalı deneme. Lütfen yeni bir kod isteyin.',
    'user_not_found' => 'Bu e-posta adresiyle bir hesap bulunamadı.',
    'account_not_found_create' => ':email için hesap bulunamadı. <a href=":register_url" class="font-semibold text-primary-600 dark:text-primary-400 hover:underline">Hesap oluşturulsun mu?</a>',
    'user_inactive' => 'Bu hesap aktif değil. Lütfen destekle iletişime geçin.',
    'send_failed' => 'Doğrulama kodu gönderilemedi. Lütfen tekrar deneyin.',
    'invalid_request' => 'Geçersiz istek. Lütfen tekrar deneyin.',

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRATION WITH OTP
    // ─────────────────────────────────────────────────────────────────────────

    'registration_verify_title' => 'E-postanızı doğrulayın',
    'registration_verify_subtitle' => '6 haneli bir kod gönderdik:',
    'registration_start_over' => 'Baştan başla',
    'registration_almost_there' => 'Neredeyse tamam!',
    'registration_verify_panel_title' => 'Son bir adım kaldı',
    'registration_verify_panel_text' => 'Kaydınızı tamamlamak için e-postanıza gönderdiğimiz doğrulama kodunu girin.',
    'registration_benefit_1' => 'Hatırlanacak şifre yok',
    'registration_benefit_2' => 'Güvenli şifresiz giriş',
    'registration_benefit_3' => 'Profilinizden istediğiniz zaman şifre belirleyin',
    'registration_success' => 'Hoş geldiniz! Hesabınız başarıyla oluşturuldu.',
    'registration_email_exists' => 'Bu e-postayla zaten bir hesap var. Lütfen giriş yapın.',
    'registration_otp_sent' => ':email adresine bir doğrulama kodu gönderildi',
    'registration_almost_done' => 'Hesabınızı doğrulamak ve kaydı tamamlamak için e-postanıza gönderdiğimiz 6 haneli kodu girin.',
    'verify_email_title' => 'E-postanızı Doğrulayın',
    'secure_verification' => 'Güvenli Doğrulama',
    'code_expires_minutes' => 'Kod :minutes dakika içinde sona erer',
    'never_share_code' => 'Bu kodu kimseyle paylaşmayın',
    'check_spam_folder' => 'E-postayı görmüyorsanız spam klasörünü kontrol edin',

    'pin_aria_label' => 'Doğrulama kodu',
    'pin_digit_aria' => ':total haneden :n. hane',

    // ─────────────────────────────────────────────────────────────────────────
    // PROFILE UPDATE OTP VERIFICATION
    // ─────────────────────────────────────────────────────────────────────────

    'profile_verification_title' => 'Kimliğinizi Doğrulayın',
    'profile_verification_subtitle' => 'Değişiklikleri kaydetmek için doğrulama kodu gerekir',
    'profile_send_code_info' => 'Gerçekten siz olduğunuzu doğrulamak için :email adresine 6 haneli bir kod göndereceğiz.',
    'profile_send_code_info_generic' => 'Gerçekten siz olduğunuzu doğrulamak için 6 haneli bir kod göndereceğiz.',
    'profile_send_code' => 'Doğrulama Kodu Gönder',
    'profile_verified' => 'Kimlik doğrulandı! Artık değişikliklerinizi kaydedebilirsiniz.',
    'profile_otp_required' => 'Lütfen e-postanıza gönderilen kodla kimliğinizi doğrulayın.',
    'verify_to_save' => 'Kaydetmek için Doğrulayın',
    'identity_verified' => 'Kimlik Doğrulandı!',
    'verification_failed' => 'Doğrulama başarısız. Lütfen tekrar deneyin.',
    'code_sent_success_short' => 'Kod gönderildi!',
    'sending' => 'Gönderiliyor...',

    // ─────────────────────────────────────────────────────────────────────────
    // DEMO MODE
    // ─────────────────────────────────────────────────────────────────────────

    'demo_otp_notice' => 'Demo modu: Giriş için :code kodunu kullanın.',

    // ─────────────────────────────────────────────────────────────────────────
    // LOADING STATE
    // ─────────────────────────────────────────────────────────────────────────

    'please_wait' => 'Lütfen bekleyin...',
];
