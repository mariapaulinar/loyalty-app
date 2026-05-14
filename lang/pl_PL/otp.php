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

    'email_subject_login' => 'Twój bezpieczny kod logowania',
    'email_subject_verify' => 'Zweryfikuj swój adres e-mail',
    'email_subject_reset' => 'Zresetuj hasło',
    'email_subject_default' => 'Twój kod weryfikacyjny',

    // ─────────────────────────────────────────────────────────────────────────
    // EMAIL CONTENT
    // ─────────────────────────────────────────────────────────────────────────

    'email_title_login' => 'Witamy ponownie w :app',
    'email_title_verify_email' => 'Zweryfikujmy adres e-mail',
    'email_title_registration' => 'Już prawie gotowe',
    'email_title_password_reset' => 'Zresetuj hasło',
    'email_title_profile_update' => 'Zweryfikuj zmiany profilu',
    'email_title_default' => 'Twój kod weryfikacyjny',

    'email_intro_login' => 'Oto bezpieczny kod logowania. Wpisz go na stronie logowania, aby uzyskać dostęp do konta.',
    'email_intro_verify_email' => 'Musimy upewnić się, że ten adres e-mail należy do Ciebie. Wpisz poniższy kod, aby zweryfikować adres.',
    'email_intro_registration' => 'Witamy na pokładzie. Wpisz ten kod, aby zakończyć rejestrację i aktywować konto.',
    'email_intro_password_reset' => 'Spokojnie, to się zdarza. Użyj tego kodu, aby utworzyć nowe hasło.',
    'email_intro_profile_update' => 'Dla bezpieczeństwa potwierdź, że to Ty wprowadzasz zmiany profilu.',
    'email_intro_default' => 'Oto kod weryfikacyjny, o który poproszono.',

    'email_expires' => 'Ważny przez :minutes minut',
    'email_security_notice' => 'Nie proszono o ten kod? Nie trzeba nic robić — po prostu zignoruj tę wiadomość. Konto pozostaje bezpieczne.',
    'email_subcopy' => 'Ten kod jest tylko dla Ciebie. Nigdy nie poprosimy o jego udostępnienie.',
    'verification_code' => 'Twój kod weryfikacyjny',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 1 (EMAIL ENTRY)
    // ─────────────────────────────────────────────────────────────────────────

    'step1_title' => 'Zaloguj się do konta',
    'step1_subtitle' => 'Wpisz adres e-mail, aby kontynuować',
    'step1_subtitle_admin' => 'Zaloguj się do panelu administratora',
    'step1_subtitle_partner' => 'Zaloguj się do panelu partnera',
    'step1_subtitle_staff' => 'Zaloguj się do panelu personelu',
    'step1_continue' => 'Kontynuuj',
    'step1_no_account' => 'Nie masz konta?',
    'step1_create_account' => 'Utwórz konto',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 2 (METHOD SELECTION)
    // ─────────────────────────────────────────────────────────────────────────

    'step2_welcome_back' => 'Witamy ponownie',
    'step2_welcome' => 'Witamy',
    'step2_change_email' => 'Zmień e-mail',
    'step2_enter_password' => 'Wpisz hasło',
    'step2_sign_in' => 'Zaloguj się',
    'step2_or_divider' => 'lub',
    'step2_send_code' => 'Wyślij kod logowania',
    'step2_send_code_only' => 'Wyślij kod logowania',
    'step2_code_info' => 'Wyślemy 6-cyfrowy kod na Twój e-mail.',
    'step2_forgot_password' => 'Nie pamiętasz hasła?',
    'step2_set_password' => 'Skonfiguruj hasło',
    'step2_passwordless_title' => 'Logowanie bez hasła',
    'step2_passwordless_subtitle' => 'Wyślemy 6-cyfrowy kod na adres e-mail, aby bezpiecznie zalogować się do konta.',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 3 (OTP VERIFICATION)
    // ─────────────────────────────────────────────────────────────────────────

    'step3_title' => 'Wpisz kod weryfikacyjny',
    'step3_subtitle' => 'Wysłaliśmy 6-cyfrowy kod na adres',
    'step3_didnt_receive' => 'Nie ma kodu?',
    'step3_resend' => 'Wyślij ponownie',
    'step3_resend_in' => 'Wyślij ponownie za :seconds s',
    'step3_try_another' => 'Użyj innej metody',
    'step3_verifying' => 'Weryfikacja...',
    'step3_code_sent' => 'Kod wysłany.',
    'step3_check_email' => 'Sprawdź skrzynkę e-mail, aby znaleźć kod weryfikacyjny.',
    'step3_panel_description' => 'Wysłaliśmy 6-cyfrowy kod na Twój adres e-mail. Wpisz go powyżej, aby dokończyć logowanie.',

    // ─────────────────────────────────────────────────────────────────────────
    // PORTAL NAMES
    // ─────────────────────────────────────────────────────────────────────────

    'admin_portal' => 'Portal Administratora',
    'partner_portal' => 'Portal Partnera',
    'staff_portal' => 'Portal Pracownika',

    // ─────────────────────────────────────────────────────────────────────────
    // PIN INPUT
    // ─────────────────────────────────────────────────────────────────────────

    'pin_digit_label' => 'Cyfra :position z :total',
    'pin_placeholder' => '·',

    // ─────────────────────────────────────────────────────────────────────────
    // SUCCESS MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'code_sent_success' => 'Kod weryfikacyjny wysłano na :email',
    'verification_success' => 'Weryfikacja zakończona pomyślnie.',
    'login_success' => 'Witamy ponownie.',

    // ─────────────────────────────────────────────────────────────────────────
    // ERROR MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'rate_limited' => 'Zbyt wiele prób. Odczekaj :seconds sekund.',
    'code_expired' => 'Kod wygasł. Poproś o nowy kod.',
    'code_invalid' => 'Nieprawidłowy kod. Pozostało prób: :remaining.',
    'code_locked' => 'Zbyt wiele nieudanych prób. Poproś o nowy kod.',
    'user_not_found' => 'Nie znaleziono konta dla tego adresu e-mail.',
    'account_not_found_create' => 'Nie znaleziono konta dla :email. <a href=":register_url" class="font-semibold text-primary-600 dark:text-primary-400 hover:underline">Utworzyć konto?</a>',
    'user_inactive' => 'To konto jest nieaktywne. Skontaktuj się z pomocą techniczną.',
    'send_failed' => 'Nie udało się wysłać kodu weryfikacyjnego. Spróbuj ponownie.',
    'invalid_request' => 'Nieprawidłowe żądanie. Spróbuj ponownie.',

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRATION WITH OTP
    // ─────────────────────────────────────────────────────────────────────────

    'registration_verify_title' => 'Zweryfikuj adres e-mail',
    'registration_verify_subtitle' => 'Wysłaliśmy 6-cyfrowy kod na adres',
    'registration_start_over' => 'Zacznij od nowa',
    'registration_almost_there' => 'Już prawie gotowe.',
    'registration_verify_panel_title' => 'Został ostatni krok',
    'registration_verify_panel_text' => 'Wpisz kod weryfikacyjny wysłany na e-mail, aby zakończyć rejestrację.',
    'registration_benefit_1' => 'Bez hasła do zapamiętania',
    'registration_benefit_2' => 'Bezpieczne logowanie bez hasła',
    'registration_benefit_3' => 'Hasło można ustawić w dowolnym momencie w profilu',
    'registration_success' => 'Witamy. Konto zostało utworzone pomyślnie.',
    'registration_email_exists' => 'Konto z tym adresem e-mail już istnieje. Zaloguj się.',
    'registration_otp_sent' => 'Kod weryfikacyjny został wysłany na :email',
    'registration_almost_done' => 'Wpisz 6-cyfrowy kod wysłany na e-mail, aby zweryfikować konto i zakończyć rejestrację.',
    'verify_email_title' => 'Zweryfikuj adres e-mail',
    'secure_verification' => 'Bezpieczna weryfikacja',
    'code_expires_minutes' => 'Kod wygasa za :minutes minut',
    'never_share_code' => 'Nigdy nie udostępniaj tego kodu',
    'check_spam_folder' => 'Sprawdź folder spam, jeśli nie widzisz wiadomości',

    'pin_aria_label' => 'Kod weryfikacyjny',
    'pin_digit_aria' => 'Cyfra :n z :total',

    // ─────────────────────────────────────────────────────────────────────────
    // PROFILE UPDATE OTP VERIFICATION
    // ─────────────────────────────────────────────────────────────────────────

    'profile_verification_title' => 'Zweryfikuj tożsamość',
    'profile_verification_subtitle' => 'Aby zapisać zmiany, wymagany jest kod weryfikacyjny',
    'profile_send_code_info' => 'Wyślemy 6-cyfrowy kod na :email, aby potwierdzić tożsamość.',
    'profile_send_code_info_generic' => 'Wyślemy 6-cyfrowy kod, aby potwierdzić tożsamość.',
    'profile_send_code' => 'Wyślij kod weryfikacyjny',
    'profile_verified' => 'Tożsamość potwierdzona. Teraz można zapisać zmiany.',
    'profile_otp_required' => 'Zweryfikuj tożsamość kodem wysłanym na e-mail.',
    'verify_to_save' => 'Zweryfikuj, aby zapisać zmiany',
    'identity_verified' => 'Tożsamość potwierdzona.',
    'verification_failed' => 'Weryfikacja nie powiodła się. Spróbuj ponownie.',
    'code_sent_success_short' => 'Kod wysłany.',
    'sending' => 'Wysyłanie...',

    // ─────────────────────────────────────────────────────────────────────────
    // DEMO MODE
    // ─────────────────────────────────────────────────────────────────────────

    'demo_otp_notice' => 'Tryb demo: użyj kodu :code, aby się zalogować.',

    // ─────────────────────────────────────────────────────────────────────────
    // LOADING STATE
    // ─────────────────────────────────────────────────────────────────────────

    'please_wait' => 'Proszę czekać...',
];
