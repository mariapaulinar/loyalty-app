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

    'installation' => 'Kurulum',
    'install' => 'Kur',
    'install_script' => 'Script\'i Kurun',
    'server_requirements' => 'Sunucu Gereksinimleri',
    'requirements' => 'Gereksinimler',
    'server_requirements_text' => 'Aşağıdaki kontroller, scriptin sunucunuzda çalışıp çalışmayacağını anlamaya yardımcı olur; ancak tam uyumluluk garanti edilemez.',
    'resolve_missing_requirements' => 'Devam etmek için eksik gereksinimleri giderin.',
    'next' => 'İleri',
    'prev' => 'Geri',
    'configuration' => 'Yapılandırma',
    'confirm' => 'Onayla',
    'app' => 'Uygulama',
    'name' => 'Ad',
    'email' => 'E-posta',
    'optional' => 'İsteğe bağlı',
    '_optional_' => '(isteğe bağlı)',
    'optional_email_config' => 'Şimdilik aşağıdaki ayarları atlayabilirsiniz. Daha sonra web kökündeki .env dosyasından yapılandırabilirsiniz. Not: E-posta işlevleri için bu ayarlar gereklidir.',
    'logo' => 'Logo',
    'logo_dark' => 'Logo (Koyu Mod)',
    'user' => 'Kullanıcı',
    'email_address' => 'E-posta Adresi',
    'time_zone' => 'Saat Dilimi',
    'password' => 'Şifre',
    'confirm_password' => 'Şifreyi Onayla',
    'passwords_must_match' => 'Şifreler eşleşmelidir.',
    'email_address_app' => 'Uygulamanın e-posta göndermek için kullandığı adres',
    'email_address_name_app' => 'E-posta Adı',
    'admin_login' => 'Yönetici Girişi',
    'download_log' => 'Günlük Dosyasını İndir',
    'refresh_page' => 'Bu sayfayı yenileyin ve tekrar deneyin',
    'after_installation' => 'Kurulum tamamlandıktan sonra, daha önce verilen yönetici giriş bilgilerini kullanarak :admin_url adresindeki yönetici paneline erişin.',
    'install_error' => 'Sunucu bir hata döndürdü. Ayrıntılar için günlük dosyasını (/storage/logs) kontrol edin.',
    'database_info' => 'SQLite yüksek performans sunar ve kullanıcıların %95\'i için uygundur. Günlük kullanıcı hacmi daha yüksekse MySQL veya MariaDB\'yi düşünün.',
    'install_acknowledge' => 'Yazılımımızı kurarak, NowSquare\'ın kullanımından doğabilecek sorunlardan sorumlu olmadığını kabul etmiş olursunuz. Unutmayın, her yazılım hata içerebilir. Bir sorunla karşılaşırsanız, hızlıca çözebilmemiz için lütfen e-posta veya destek bileti ile bize ulaşın.',

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    */

    'email_settings' => 'E-posta Teslimi',
    'email_critical_title' => 'E-posta Zorunludur',
    'email_critical_description' => 'Müşterileriniz giriş yapmak için tek kullanımlık şifreyi (OTP) e-posta ile alır. E-posta çalışmazsa, siz dahil kimse sisteme erişemez.',
    'email_why_matters' => 'Neden önemli?',
    'email_otp_explanation' => 'Şifresiz kimlik doğrulama kullanıyoruz. Şifre hatırlamak yerine kullanıcılar her girişte e-posta ile güvenli bir kod alır. Basit, güvenli, modern.',

    'mail_driver' => 'E-postaları nasıl göndermeliyiz?',
    'mail_driver_help' => 'E-postalarınızı müşterilere ulaştıracak servisi seçin.',

    // Driver descriptions
    'driver_smtp' => 'SMTP Sunucusu',
    'driver_smtp_desc' => 'Herhangi bir e-posta sunucusuna bağlanın. Gmail, Outlook, barındırma sağlayıcınız veya herhangi bir SMTP servisiyle çalışır.',
    'driver_smtp_best_for' => 'En iyisi: Çoğu kullanıcı ve barındırma sağlayıcıları',

    'driver_mailgun' => 'Mailgun',
    'driver_mailgun_desc' => 'Mailchimp\'in profesyonel e-posta teslim servisi. Güvenilir, ölçeklenebilir ve detaylı analizler sunar.',
    'driver_mailgun_best_for' => 'En iyisi: Büyüyen işletmeler, yüksek hacim',

    'driver_ses' => 'Amazon SES',
    'driver_ses_desc' => 'AWS üzerinden ölçekli ve uygun maliyetli e-posta. Yüksek teslimat oranı ve iyi fiyatlandırma.',
    'driver_ses_best_for' => 'En iyisi: AWS kullanıcıları, büyük ölçekli operasyonlar',

    'driver_postmark' => 'Postmark',
    'driver_postmark_desc' => 'İşlemsel e-postalar için özel olarak tasarlanmıştır. Sektör lideri teslimat hızı.',
    'driver_postmark_best_for' => 'En iyisi: Hızın kritik olduğu uygulamalar',

    'driver_resend' => 'Resend',
    'driver_resend_desc' => 'Geliştiriciler için modern e-posta API\'si. Basit, güvenilir ve iyi bir geliştirici deneyimi sunar.',
    'driver_resend_best_for' => 'En iyisi: Geliştirici odaklı ekipler',

    'driver_sendmail' => 'Sendmail',
    'driver_sendmail_desc' => 'Sunucunuzun yerleşik posta sistemini kullanır. Harici servis gerekmez.',
    'driver_sendmail_best_for' => 'En iyisi: Basit kurulumlar, Linux sunucular',

    'driver_mailpit' => 'Mailpit (Test)',
    'driver_mailpit_desc' => 'Geliştirme için tüm e-postaları yerelde yakalar. Gerçek e-posta gönderilmez.',
    'driver_mailpit_best_for' => 'En iyisi: Yalnızca yerel geliştirme',

    'driver_log' => 'Günlük Dosyası (Geliştirme)',
    'driver_log_desc' => 'Göndermek yerine e-postaları günlük dosyalarına yazar. İlk testler için idealdir.',
    'driver_log_best_for' => 'En iyisi: Hızlı test, hata ayıklama',

    // SMTP Fields
    'smtp_host' => 'SMTP Sunucusu',
    'smtp_host_placeholder' => 'smtp.example.com',
    'smtp_host_help' => 'E-posta sunucunuzun adresi',

    'smtp_port' => 'Port',
    'smtp_port_help' => 'Yaygın portlar: 587 (TLS), 465 (SSL), 25 (şifresiz)',

    'smtp_username' => 'Kullanıcı adı',
    'smtp_username_placeholder' => 'your-email@example.com',
    'smtp_username_help' => 'Genellikle tam e-posta adresiniz',

    'smtp_password' => 'Şifre',
    'smtp_password_placeholder' => 'E-posta şifreniz veya uygulama şifreniz',
    'smtp_password_help' => 'Gmail/Google için Uygulama Şifresi kullanın',

    'smtp_encryption' => 'Güvenlik',
    'smtp_encryption_help' => 'Çoğu sağlayıcı için TLS önerilir',
    'smtp_encryption_tls' => 'TLS (Önerilen)',
    'smtp_encryption_ssl' => 'SSL',
    'smtp_encryption_none' => 'Yok (Önerilmez)',

    // Provider-specific
    'mailgun_domain' => 'Mailgun Alan Adı',
    'mailgun_domain_placeholder' => 'mg.yourdomain.com',
    'mailgun_domain_help' => 'Mailgun\'da doğrulanmış gönderim alan adınız',

    'mailgun_secret' => 'API Anahtarı',
    'mailgun_secret_placeholder' => 'key-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'mailgun_secret_help' => 'Mailgun → Settings → API Keys bölümünde bulunur',

    'mailgun_endpoint' => 'Bölge',
    'mailgun_endpoint_us' => 'Amerika Birleşik Devletleri (api.mailgun.net)',
    'mailgun_endpoint_eu' => 'Avrupa Birliği (api.eu.mailgun.net)',

    'ses_key' => 'AWS Erişim Anahtarı Kimliği',
    'ses_key_placeholder' => 'AKIAIOSFODNN7EXAMPLE',
    'ses_key_help' => 'AWS IAM kimlik bilgilerinizden',

    'ses_secret' => 'AWS Gizli Erişim Anahtarı',
    'ses_secret_placeholder' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'ses_secret_help' => 'Güvende tutun, asla paylaşmayın',

    'ses_region' => 'AWS Bölgesi',
    'ses_region_help' => 'SES\'in yapılandırıldığı bölge',

    'postmark_token' => 'Sunucu API Tokenı',
    'postmark_token_placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    'postmark_token_help' => 'Postmark → Server → API Tokens bölümünde bulunur',

    'resend_key' => 'API Anahtarı',
    'resend_key_placeholder' => 're_xxxxxxxxxxxxxxxxxxxxxxxxxx',
    'resend_key_help' => 'Resend Dashboard → API Keys bölümünde bulunur',

    // From address
    'mail_from_address' => 'Gönderen E-posta',
    'mail_from_address_placeholder' => 'noreply@yourdomain.com',
    'mail_from_address_help' => 'Alıcılar bunu gönderen olarak görür',

    'mail_from_name' => 'Gönderen Adı',
    'mail_from_name_placeholder' => 'My Company',
    'mail_from_name_help' => 'Alıcılara görünen ad',

    // Test email
    'test_email' => 'Test E-postası Gönder',
    'test_email_sending' => 'Gönderiliyor...',
    'test_email_success' => 'Test e-postası gönderildi! Gelen kutunuzu kontrol edin.',
    'test_email_failed' => 'Gönderilemedi. Lütfen ayarlarınızı kontrol edin.',
    'test_email_check_spam' => 'Göremiyor musunuz? Spam klasörünü kontrol edin.',

    // Common provider help
    'gmail_help_title' => 'Gmail mi kullanıyorsunuz?',
    'gmail_help_text' => 'Google Hesabınızın ayarlarından bir Uygulama Şifresi oluşturmanız gerekir. Normal şifreler çalışmaz.',
    'gmail_help_link' => 'Uygulama Şifresi nasıl oluşturulur?',

    'provider_setup_guide' => 'Kurulum Rehberi',
    'need_help' => 'Yardım mı lazım?',
    'skip_for_now' => 'Daha Sonra Yapılandır',
    'skip_warning' => 'Uyarı: E-posta yapılandırılmadan giriş kodları gönderilemez. Bunu daha sonra .env dosyanızdan ayarlayabilirsiniz.',

    // Validation
    'email_config_incomplete' => 'Lütfen gerekli tüm e-posta ayarlarını doldurun.',
];
