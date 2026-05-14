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

    'email_subject_login' => 'Kode login aman Anda',
    'email_subject_verify' => 'Verifikasi alamat email Anda',
    'email_subject_reset' => 'Atur ulang kata sandi Anda',
    'email_subject_default' => 'Kode verifikasi Anda',

    // ─────────────────────────────────────────────────────────────────────────
    // EMAIL CONTENT
    // ─────────────────────────────────────────────────────────────────────────

    'email_title_login' => 'Selamat datang kembali di :app',
    'email_title_verify_email' => 'Mari verifikasi email Anda',
    'email_title_registration' => 'Sedikit lagi',
    'email_title_password_reset' => 'Atur ulang kata sandi Anda',
    'email_title_profile_update' => 'Verifikasi perubahan profil Anda',
    'email_title_default' => 'Kode verifikasi Anda',

    'email_intro_login' => 'Berikut kode login aman Anda. Masukkan di halaman masuk untuk mengakses akun Anda.',
    'email_intro_verify_email' => 'Kami hanya perlu memastikan email ini milik Anda. Masukkan kode di bawah untuk memverifikasi alamat Anda.',
    'email_intro_registration' => 'Selamat bergabung! Masukkan kode ini untuk menyelesaikan pendaftaran dan mengaktifkan akun baru Anda.',
    'email_intro_password_reset' => 'Tidak masalah, itu bisa terjadi. Gunakan kode ini untuk membuat kata sandi baru.',
    'email_intro_profile_update' => 'Demi keamanan, mohon konfirmasi bahwa Anda yang melakukan perubahan pada profil.',
    'email_intro_default' => 'Berikut kode verifikasi yang Anda minta.',

    'email_expires' => 'Berlaku selama :minutes menit',
    'email_security_notice' => 'Tidak merasa meminta ini? Tidak perlu tindakan — abaikan saja email ini. Akun Anda tetap aman.',
    'email_subcopy' => 'Kode ini hanya untuk Anda. Kami tidak akan pernah meminta Anda membagikannya kepada siapa pun.',
    'verification_code' => 'Kode verifikasi Anda',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 1 (EMAIL ENTRY)
    // ─────────────────────────────────────────────────────────────────────────

    'step1_title' => 'Masuk ke akun Anda',
    'step1_subtitle' => 'Masukkan email Anda untuk melanjutkan',
    'step1_subtitle_admin' => 'Masuk ke Portal Admin',
    'step1_subtitle_partner' => 'Masuk ke Portal Mitra',
    'step1_subtitle_staff' => 'Masuk ke Portal Staf',
    'step1_continue' => 'Lanjut',
    'step1_no_account' => 'Belum punya akun?',
    'step1_create_account' => 'Buat akun',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 2 (METHOD SELECTION)
    // ─────────────────────────────────────────────────────────────────────────

    'step2_welcome_back' => 'Selamat datang kembali',
    'step2_welcome' => 'Selamat datang',
    'step2_change_email' => 'Ganti email',
    'step2_enter_password' => 'Masukkan kata sandi Anda',
    'step2_sign_in' => 'Masuk',
    'step2_or_divider' => 'atau',
    'step2_send_code' => 'Kirim kode login',
    'step2_send_code_only' => 'Kirim kode login',
    'step2_code_info' => 'Kami akan mengirim kode 6 digit ke email Anda.',
    'step2_forgot_password' => 'Lupa kata sandi?',
    'step2_set_password' => 'Gunakan kata sandi saja',
    'step2_passwordless_title' => 'Masuk Tanpa Kata Sandi',
    'step2_passwordless_subtitle' => 'Kami akan mengirim kode 6 digit ke email Anda untuk akses aman.',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 3 (OTP VERIFICATION)
    // ─────────────────────────────────────────────────────────────────────────

    'step3_title' => 'Masukkan kode verifikasi',
    'step3_subtitle' => 'Kami mengirim kode 6 digit ke',
    'step3_didnt_receive' => 'Tidak menerima kode?',
    'step3_resend' => 'Kirim ulang kode',
    'step3_resend_in' => 'Kirim ulang dalam :seconds dtk',
    'step3_try_another' => 'Coba metode lain',
    'step3_verifying' => 'Memverifikasi...',
    'step3_code_sent' => 'Kode terkirim!',
    'step3_check_email' => 'Periksa email Anda untuk kode verifikasi.',
    'step3_panel_description' => 'Kami telah mengirimkan kode 6 digit ke email Anda. Masukkan di atas untuk menyelesaikan masuk.',

    // ─────────────────────────────────────────────────────────────────────────
    // PORTAL NAMES
    // ─────────────────────────────────────────────────────────────────────────

    'admin_portal' => 'Portal Admin',
    'partner_portal' => 'Portal Mitra',
    'staff_portal' => 'Portal Staf',

    // ─────────────────────────────────────────────────────────────────────────
    // PIN INPUT
    // ─────────────────────────────────────────────────────────────────────────

    'pin_digit_label' => 'Digit :position dari :total',
    'pin_placeholder' => '·',

    // ─────────────────────────────────────────────────────────────────────────
    // SUCCESS MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'code_sent_success' => 'Kode verifikasi dikirim ke :email',
    'verification_success' => 'Verifikasi berhasil!',
    'login_success' => 'Selamat datang kembali!',

    // ─────────────────────────────────────────────────────────────────────────
    // ERROR MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'rate_limited' => 'Terlalu banyak percobaan. Silakan tunggu :seconds detik.',
    'code_expired' => 'Kode ini sudah kedaluwarsa. Silakan minta kode baru.',
    'code_invalid' => 'Kode tidak valid. Tersisa :remaining percobaan.',
    'code_locked' => 'Terlalu banyak percobaan gagal. Silakan minta kode baru.',
    'user_not_found' => 'Tidak ada akun yang ditemukan dengan alamat email ini.',
    'account_not_found_create' => 'Tidak ada akun untuk :email. <a href=":register_url" class="font-semibold text-primary-600 dark:text-primary-400 hover:underline">Buat akun?</a>',
    'user_inactive' => 'Akun ini tidak aktif. Silakan hubungi dukungan.',
    'send_failed' => 'Gagal mengirim kode verifikasi. Silakan coba lagi.',
    'invalid_request' => 'Permintaan tidak valid. Silakan coba lagi.',

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRATION WITH OTP
    // ─────────────────────────────────────────────────────────────────────────

    'registration_verify_title' => 'Verifikasi email Anda',
    'registration_verify_subtitle' => 'Kami mengirim kode 6 digit ke',
    'registration_start_over' => 'Mulai ulang',
    'registration_almost_there' => 'Sedikit lagi!',
    'registration_verify_panel_title' => 'Tinggal satu langkah lagi',
    'registration_verify_panel_text' => 'Masukkan kode verifikasi yang kami kirim ke email Anda untuk menyelesaikan pendaftaran.',
    'registration_benefit_1' => 'Tidak perlu mengingat kata sandi',
    'registration_benefit_2' => 'Login tanpa kata sandi yang aman',
    'registration_benefit_3' => 'Anda dapat menambahkan kata sandi kapan saja lewat profil',
    'registration_success' => 'Selamat! Akun Anda berhasil dibuat.',
    'registration_email_exists' => 'Akun dengan email ini sudah ada. Silakan masuk.',
    'registration_otp_sent' => 'Kode verifikasi telah dikirim ke :email',
    'registration_almost_done' => 'Masukkan kode 6 digit yang kami kirim ke email Anda untuk memverifikasi akun dan menyelesaikan pendaftaran.',
    'verify_email_title' => 'Verifikasi Email Anda',
    'secure_verification' => 'Verifikasi Aman',
    'code_expires_minutes' => 'Kode kedaluwarsa dalam :minutes menit',
    'never_share_code' => 'Jangan pernah membagikan kode ini kepada siapa pun',
    'check_spam_folder' => 'Periksa folder spam jika Anda tidak melihat emailnya',

    'pin_aria_label' => 'Kode verifikasi',
    'pin_digit_aria' => 'Digit :n dari :total',

    // ─────────────────────────────────────────────────────────────────────────
    // PROFILE UPDATE OTP VERIFICATION
    // ─────────────────────────────────────────────────────────────────────────

    'profile_verification_title' => 'Verifikasi Identitas Anda',
    'profile_verification_subtitle' => 'Kode verifikasi diperlukan untuk menyimpan perubahan',
    'profile_send_code_info' => 'Kami akan mengirim kode 6 digit ke :email untuk memastikan itu benar-benar Anda.',
    'profile_send_code_info_generic' => 'Kami akan mengirim kode 6 digit untuk memastikan itu benar-benar Anda.',
    'profile_send_code' => 'Kirim Kode Verifikasi',
    'profile_verified' => 'Identitas terverifikasi! Anda kini dapat menyimpan perubahan.',
    'profile_otp_required' => 'Silakan verifikasi identitas Anda dengan kode yang dikirim ke email.',
    'verify_to_save' => 'Verifikasi untuk Menyimpan',
    'identity_verified' => 'Identitas Terverifikasi!',
    'verification_failed' => 'Verifikasi gagal. Silakan coba lagi.',
    'code_sent_success_short' => 'Kode terkirim!',
    'sending' => 'Mengirim...',

    // ─────────────────────────────────────────────────────────────────────────
    // DEMO MODE
    // ─────────────────────────────────────────────────────────────────────────

    'demo_otp_notice' => 'Mode demo: Gunakan kode :code untuk masuk.',

    // ─────────────────────────────────────────────────────────────────────────
    // LOADING STATE
    // ─────────────────────────────────────────────────────────────────────────

    'please_wait' => 'Mohon tunggu...',
];
