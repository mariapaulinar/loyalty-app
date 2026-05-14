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

    'installation' => 'Instalacja',
    'install' => 'Zainstaluj',
    'install_script' => 'Zainstaluj skrypt',
    'server_requirements' => 'Wymagania serwera',
    'requirements' => 'Wymagania',
    'server_requirements_text' => 'Poniższe testy pomagają określić, czy skrypt będzie działał na serwerze, ale pełna zgodność nie może być zagwarantowana.',
    'resolve_missing_requirements' => 'Uzupełnij brakujące wymagania, aby kontynuować.',
    'next' => 'Dalej',
    'prev' => 'Wstecz',
    'configuration' => 'Konfiguracja',
    'confirm' => 'Potwierdź',
    'app' => 'Aplikacja',
    'name' => 'Nazwa',
    'email' => 'E-mail',
    'optional' => 'Opcjonalne',
    '_optional_' => '(opcjonalnie)',
    'optional_email_config' => 'Ustawienia poniżej można na razie pominąć. Można je skonfigurować później w pliku .env w katalogu głównym aplikacji. Uwaga: funkcje e-mail wymagają tych ustawień.',
    'logo' => 'Logo',
    'logo_dark' => 'Logo (tryb ciemny)',
    'user' => 'Użytkownik',
    'email_address' => 'Adres e-mail',
    'time_zone' => 'Strefa czasowa',
    'password' => 'Hasło',
    'confirm_password' => 'Potwierdź hasło',
    'passwords_must_match' => 'Hasła muszą być takie same.',
    'email_address_app' => 'Adres e-mail używany przez aplikację do wysyłki wiadomości',
    'email_address_name_app' => 'Nazwa nadawcy',
    'admin_login' => 'Logowanie administratora',
    'download_log' => 'Pobierz plik logów',
    'refresh_page' => 'Odśwież stronę i spróbuj ponownie',
    'after_installation' => 'Po zakończeniu instalacji użyj danych logowania administratora podanych wcześniej, aby wejść do panelu administracyjnego pod adresem :admin_url.',
    'install_error' => 'Serwer zwrócił błąd. Sprawdź plik logów (/storage/logs), aby poznać szczegóły.',
    'database_info' => 'SQLite oferuje wysoką wydajność i sprawdza się u 95% użytkowników. Przy większym dziennym ruchu rozważ MySQL lub MariaDB.',
    'install_acknowledge' => 'Instalując nasze oprogramowanie, potwierdzasz, że NowSquare nie ponosi odpowiedzialności za problemy wynikające z jego użycia. Każde oprogramowanie może zawierać błędy. Jeśli taki problem się pojawi, skontaktuj się z nami e-mailem lub przez zgłoszenie supportowe, abyśmy mogli szybko go rozwiązać.',

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    */

    'email_settings' => 'Dostarczanie e-maili',
    'email_critical_title' => 'E-mail jest niezbędny',
    'email_critical_description' => 'Klienci otrzymują jednorazowe kody (OTP) e-mailem, aby się zalogować. Bez poprawnej konfiguracji e-mail nikt nie uzyska dostępu do systemu, w tym administrator.',
    'email_why_matters' => 'Dlaczego to ważne',
    'email_otp_explanation' => 'Używamy logowania bez hasła. Zamiast pamiętać hasło, użytkownik przy każdym logowaniu otrzymuje bezpieczny kod e-mailem. Prosto, bezpiecznie, nowocześnie.',

    'mail_driver' => 'Jak wysyłać e-maile?',
    'mail_driver_help' => 'Wybierz usługę, która będzie dostarczać e-maile do klientów.',

    // Driver descriptions
    'driver_smtp' => 'Serwer SMTP',
    'driver_smtp_desc' => 'Połączenie z dowolnym serwerem poczty. Działa z Gmail, Outlook, dostawcą hostingu i każdą usługą SMTP.',
    'driver_smtp_best_for' => 'Najlepsze dla: większości użytkowników, dostawców hostingu',

    'driver_mailgun' => 'Mailgun',
    'driver_mailgun_desc' => 'Profesjonalna usługa dostarczania e-maili od Mailchimp. Niezawodna, skalowalna, ze szczegółową analityką.',
    'driver_mailgun_best_for' => 'Najlepsze dla: rosnących firm, dużego wolumenu',

    'driver_ses' => 'Amazon SES',
    'driver_ses_desc' => 'Ekonomiczna wysyłka e-maili w skali od AWS. Bardzo dobra dostarczalność i ceny.',
    'driver_ses_best_for' => 'Najlepsze dla: użytkowników AWS, operacji na dużą skalę',

    'driver_postmark' => 'Postmark',
    'driver_postmark_desc' => 'Usługa stworzona specjalnie do e-maili transakcyjnych. Czołowa szybkość dostarczania.',
    'driver_postmark_best_for' => 'Najlepsze dla: aplikacji, gdzie liczy się szybkość',

    'driver_resend' => 'Resend',
    'driver_resend_desc' => 'Nowoczesne API e-mailowe tworzone z myślą o deweloperach. Proste, niezawodne, z dobrym DX.',
    'driver_resend_best_for' => 'Najlepsze dla: zespołów deweloperskich',

    'driver_sendmail' => 'Sendmail',
    'driver_sendmail_desc' => 'Korzysta z wbudowanego systemu poczty na serwerze. Bez zewnętrznej usługi.',
    'driver_sendmail_best_for' => 'Najlepsze dla: prostych konfiguracji, serwerów Linux',

    'driver_mailpit' => 'Mailpit (testy)',
    'driver_mailpit_desc' => 'Przechwytuje wszystkie e-maile lokalnie podczas developmentu. Żadne realne wiadomości nie są wysyłane.',
    'driver_mailpit_best_for' => 'Najlepsze dla: lokalnego developmentu',

    'driver_log' => 'Plik logów (development)',
    'driver_log_desc' => 'Zapisuje e-maile do logów zamiast wysyłać. Idealne do wstępnych testów.',
    'driver_log_best_for' => 'Najlepsze dla: szybkich testów, debugowania',

    // SMTP Fields
    'smtp_host' => 'Serwer SMTP',
    'smtp_host_placeholder' => 'smtp.example.com',
    'smtp_host_help' => 'Adres serwera poczty',

    'smtp_port' => 'Port',
    'smtp_port_help' => 'Najczęstsze porty: 587 (TLS), 465 (SSL), 25 (bez szyfrowania)',

    'smtp_username' => 'Nazwa użytkownika',
    'smtp_username_placeholder' => 'your-email@example.com',
    'smtp_username_help' => 'Najczęściej pełny adres e-mail',

    'smtp_password' => 'Hasło',
    'smtp_password_placeholder' => 'Hasło do e-maila lub hasło aplikacji',
    'smtp_password_help' => 'Dla Gmail/Google użyj hasła aplikacji',

    'smtp_encryption' => 'Zabezpieczenie',
    'smtp_encryption_help' => 'TLS jest zalecane u większości dostawców',
    'smtp_encryption_tls' => 'TLS (zalecane)',
    'smtp_encryption_ssl' => 'SSL',
    'smtp_encryption_none' => 'Brak (niezalecane)',

    // Provider-specific
    'mailgun_domain' => 'Domena Mailgun',
    'mailgun_domain_placeholder' => 'mg.yourdomain.com',
    'mailgun_domain_help' => 'Zweryfikowana domena nadawcza w Mailgun',

    'mailgun_secret' => 'Klucz API',
    'mailgun_secret_placeholder' => 'key-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'mailgun_secret_help' => 'Znajdziesz w Mailgun → Settings → API Keys',

    'mailgun_endpoint' => 'Region',
    'mailgun_endpoint_us' => 'Stany Zjednoczone (api.mailgun.net)',
    'mailgun_endpoint_eu' => 'Unia Europejska (api.eu.mailgun.net)',

    'ses_key' => 'AWS Access Key ID',
    'ses_key_placeholder' => 'AKIAIOSFODNN7EXAMPLE',
    'ses_key_help' => 'Z poświadczeń AWS IAM',

    'ses_secret' => 'AWS Secret Access Key',
    'ses_secret_placeholder' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'ses_secret_help' => 'Przechowuj bezpiecznie, nikomu nie udostępniaj',

    'ses_region' => 'Region AWS',
    'ses_region_help' => 'Region, w którym skonfigurowano SES',

    'postmark_token' => 'Server API Token',
    'postmark_token_placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    'postmark_token_help' => 'Znajdziesz w Postmark → Server → API Tokens',

    'resend_key' => 'Klucz API',
    'resend_key_placeholder' => 're_xxxxxxxxxxxxxxxxxxxxxxxxxx',
    'resend_key_help' => 'Znajdziesz w Resend Dashboard → API Keys',

    // From address
    'mail_from_address' => 'E-mail nadawcy',
    'mail_from_address_placeholder' => 'noreply@yourdomain.com',
    'mail_from_address_help' => 'Odbiorcy zobaczą ten adres jako nadawcę',

    'mail_from_name' => 'Nazwa nadawcy',
    'mail_from_name_placeholder' => 'My Company',
    'mail_from_name_help' => 'Przyjazna nazwa wyświetlana odbiorcom',

    // Test email
    'test_email' => 'Wyślij e-mail testowy',
    'test_email_sending' => 'Wysyłanie...',
    'test_email_success' => 'E-mail testowy wysłany. Sprawdź skrzynkę odbiorczą.',
    'test_email_failed' => 'Nie udało się wysłać wiadomości. Sprawdź ustawienia.',
    'test_email_check_spam' => 'Nie widzisz wiadomości? Sprawdź folder spam.',

    // Common provider help
    'gmail_help_title' => 'Używasz Gmaila?',
    'gmail_help_text' => 'Utwórz hasło aplikacji w ustawieniach konta Google. Zwykłe hasło nie zadziała.',
    'gmail_help_link' => 'Jak utworzyć hasło aplikacji',

    'provider_setup_guide' => 'Instrukcja konfiguracji',
    'need_help' => 'Potrzebujesz pomocy?',
    'skip_for_now' => 'Skonfiguruj później',
    'skip_warning' => 'Uwaga: bez konfiguracji e-mail nie da się wysyłać kodów logowania. Ustawienia można uzupełnić później w pliku .env.',

    // Validation
    'email_config_incomplete' => 'Uzupełnij wszystkie wymagane ustawienia e-mail.',
];
