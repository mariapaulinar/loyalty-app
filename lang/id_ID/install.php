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

    'installation' => 'Instalasi',
    'install' => 'Instal',
    'install_script' => 'Instal Skrip',
    'server_requirements' => 'Persyaratan Server',
    'requirements' => 'Persyaratan',
    'server_requirements_text' => 'Pemeriksaan berikut membantu menentukan apakah skrip akan berfungsi di server Anda, meskipun kompatibilitas penuh tidak dapat dijamin.',
    'resolve_missing_requirements' => 'Selesaikan persyaratan yang belum terpenuhi untuk melanjutkan.',
    'next' => 'Berikutnya',
    'prev' => 'Sebelumnya',
    'configuration' => 'Konfigurasi',
    'confirm' => 'Konfirmasi',
    'app' => 'Aplikasi',
    'name' => 'Nama',
    'email' => 'Email',
    'optional' => 'Opsional',
    '_optional_' => '(opsional)',
    'optional_email_config' => 'Anda dapat melewati pengaturan di bawah ini untuk sementara. Pengaturan ini bisa dikonfigurasi nanti di file .env pada root web. Catatan: Fitur email memerlukan pengaturan ini.',
    'logo' => 'Logo',
    'logo_dark' => 'Logo (Mode Gelap)',
    'user' => 'Pengguna',
    'email_address' => 'Alamat Email',
    'time_zone' => 'Zona Waktu',
    'password' => 'Kata Sandi',
    'confirm_password' => 'Konfirmasi Kata Sandi',
    'passwords_must_match' => 'Kata sandi harus sama.',
    'email_address_app' => 'Email yang digunakan aplikasi untuk mengirim email',
    'email_address_name_app' => 'Nama Pengirim',
    'admin_login' => 'Login Admin',
    'download_log' => 'Unduh File Log',
    'refresh_page' => 'Muat ulang halaman ini dan coba lagi',
    'after_installation' => 'Setelah instalasi selesai, gunakan kredensial login admin yang diberikan sebelumnya untuk mengakses dashboard admin di :admin_url.',
    'install_error' => 'Server mengembalikan error. Periksa file log (/storage/logs) untuk detail.',
    'database_info' => 'SQLite menawarkan performa tinggi dan cocok untuk 95% pengguna. Untuk volume pengguna harian yang lebih besar, pertimbangkan MySQL atau MariaDB.',
    'install_acknowledge' => 'Dengan menginstal perangkat lunak kami, Anda memahami bahwa NowSquare tidak bertanggung jawab atas masalah apa pun yang timbul dari penggunaannya. Ingat bahwa semua perangkat lunak dapat memiliki bug. Jika Anda menemukannya, silakan hubungi kami melalui email atau tiket dukungan agar kami dapat menanganinya dengan cepat.',

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    */

    'email_settings' => 'Pengiriman Email',
    'email_critical_title' => 'Email Itu Penting',
    'email_critical_description' => 'Pelanggan Anda akan menerima kata sandi sekali pakai (OTP) melalui email untuk masuk. Tanpa email yang berfungsi, tidak ada yang dapat mengakses sistem—termasuk Anda.',
    'email_why_matters' => 'Mengapa ini penting',
    'email_otp_explanation' => 'Kami menggunakan autentikasi tanpa kata sandi. Alih-alih mengingat kata sandi, pengguna menerima kode aman via email setiap kali masuk. Sederhana, aman, modern.',

    'mail_driver' => 'Bagaimana email akan dikirim?',
    'mail_driver_help' => 'Pilih layanan yang akan mengirimkan email Anda ke pelanggan.',

    // Driver descriptions
    'driver_smtp' => 'Server SMTP',
    'driver_smtp_desc' => 'Hubungkan ke server email apa pun. Berfungsi dengan Gmail, Outlook, penyedia hosting Anda, atau layanan SMTP lainnya.',
    'driver_smtp_best_for' => 'Cocok untuk: Kebanyakan pengguna, penyedia hosting',

    'driver_mailgun' => 'Mailgun',
    'driver_mailgun_desc' => 'Layanan pengiriman email profesional dari Mailchimp. Andal, mudah diskalakan, dengan analitik lengkap.',
    'driver_mailgun_best_for' => 'Cocok untuk: Bisnis berkembang, volume tinggi',

    'driver_ses' => 'Amazon SES',
    'driver_ses_desc' => 'Email hemat biaya dalam skala besar dari AWS. Keterantaran dan harga yang sangat baik.',
    'driver_ses_best_for' => 'Cocok untuk: Pengguna AWS, operasi skala besar',

    'driver_postmark' => 'Postmark',
    'driver_postmark_desc' => 'Dibuat khusus untuk email transaksional. Kecepatan pengiriman terdepan di industri.',
    'driver_postmark_best_for' => 'Cocok untuk: Aplikasi yang butuh kecepatan',

    'driver_resend' => 'Resend',
    'driver_resend_desc' => 'API email modern untuk developer. Sederhana, andal, dengan pengalaman developer yang baik.',
    'driver_resend_best_for' => 'Cocok untuk: Tim yang fokus developer',

    'driver_sendmail' => 'Sendmail',
    'driver_sendmail_desc' => 'Gunakan sistem mail bawaan server Anda. Tidak perlu layanan eksternal.',
    'driver_sendmail_best_for' => 'Cocok untuk: Setup sederhana, server Linux',

    'driver_mailpit' => 'Mailpit (Testing)',
    'driver_mailpit_desc' => 'Menangkap semua email secara lokal untuk pengembangan. Tidak ada email nyata yang dikirim.',
    'driver_mailpit_best_for' => 'Cocok untuk: Pengembangan lokal saja',

    'driver_log' => 'File Log (Pengembangan)',
    'driver_log_desc' => 'Menulis email ke file log alih-alih mengirim. Cocok untuk pengujian awal.',
    'driver_log_best_for' => 'Cocok untuk: Pengujian cepat, debugging',

    // SMTP Fields
    'smtp_host' => 'SMTP Server',
    'smtp_host_placeholder' => 'smtp.example.com',
    'smtp_host_help' => 'Alamat server email Anda',

    'smtp_port' => 'Port',
    'smtp_port_help' => 'Port umum: 587 (TLS), 465 (SSL), 25 (tanpa enkripsi)',

    'smtp_username' => 'Nama Pengguna',
    'smtp_username_placeholder' => 'your-email@example.com',
    'smtp_username_help' => 'Biasanya alamat email lengkap Anda',

    'smtp_password' => 'Password',
    'smtp_password_placeholder' => 'Kata sandi email atau kata sandi aplikasi',
    'smtp_password_help' => 'Untuk Gmail/Google, gunakan Kata Sandi Aplikasi',

    'smtp_encryption' => 'Keamanan',
    'smtp_encryption_help' => 'TLS direkomendasikan untuk kebanyakan penyedia',
    'smtp_encryption_tls' => 'TLS (Direkomendasikan)',
    'smtp_encryption_ssl' => 'SSL',
    'smtp_encryption_none' => 'Tidak ada (Tidak direkomendasikan)',

    // Provider-specific
    'mailgun_domain' => 'Mailgun Domain',
    'mailgun_domain_placeholder' => 'mg.yourdomain.com',
    'mailgun_domain_help' => 'Domain pengirim yang sudah diverifikasi di Mailgun',

    'mailgun_secret' => 'API Key',
    'mailgun_secret_placeholder' => 'key-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'mailgun_secret_help' => 'Dapat ditemukan di Mailgun → Settings → API Keys',

    'mailgun_endpoint' => 'Wilayah',
    'mailgun_endpoint_us' => 'Amerika Serikat (api.mailgun.net)',
    'mailgun_endpoint_eu' => 'Uni Eropa (api.eu.mailgun.net)',

    'ses_key' => 'AWS Access Key ID',
    'ses_key_placeholder' => 'AKIAIOSFODNN7EXAMPLE',
    'ses_key_help' => 'Dari kredensial AWS IAM Anda',

    'ses_secret' => 'AWS Secret Access Key',
    'ses_secret_placeholder' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'ses_secret_help' => 'Jaga kerahasiaannya, jangan pernah membagikannya',

    'ses_region' => 'AWS Region',
    'ses_region_help' => 'Wilayah tempat SES dikonfigurasi',

    'postmark_token' => 'Token API Server',
    'postmark_token_placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    'postmark_token_help' => 'Dapat ditemukan di Postmark → Server → API Tokens',

    'resend_key' => 'API Key',
    'resend_key_placeholder' => 're_xxxxxxxxxxxxxxxxxxxxxxxxxx',
    'resend_key_help' => 'Dapat ditemukan di Dashboard Resend → API Keys',

    // From address
    'mail_from_address' => 'Email Pengirim',
    'mail_from_address_placeholder' => 'noreply@yourdomain.com',
    'mail_from_address_help' => 'Penerima akan melihat ini sebagai pengirim',

    'mail_from_name' => 'Nama Pengirim',
    'mail_from_name_placeholder' => 'My Company',
    'mail_from_name_help' => 'Nama yang ditampilkan kepada penerima',

    // Test email
    'test_email' => 'Kirim Email Uji',
    'test_email_sending' => 'Mengirim...',
    'test_email_success' => 'Email uji terkirim! Periksa kotak masuk Anda.',
    'test_email_failed' => 'Gagal mengirim. Silakan periksa pengaturan Anda.',
    'test_email_check_spam' => 'Tidak ada? Periksa folder spam Anda.',

    // Common provider help
    'gmail_help_title' => 'Menggunakan Gmail?',
    'gmail_help_text' => 'Anda perlu membuat Kata Sandi Aplikasi di pengaturan Akun Google Anda. Kata sandi biasa tidak akan berfungsi.',
    'gmail_help_link' => 'Cara membuat Kata Sandi Aplikasi',

    'provider_setup_guide' => 'Panduan Penyiapan',
    'need_help' => 'Butuh bantuan?',
    'skip_for_now' => 'Atur Nanti',
    'skip_warning' => 'Peringatan: Tanpa email yang dikonfigurasi, kode login tidak dapat dikirim. Anda dapat mengaturnya nanti di file .env Anda.',

    // Validation
    'email_config_incomplete' => 'Harap lengkapi semua pengaturan email yang wajib.',
];
