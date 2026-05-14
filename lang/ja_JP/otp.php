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

    'email_subject_login' => 'ログイン確認コード',
    'email_subject_verify' => 'メールアドレスを確認してください',
    'email_subject_reset' => 'パスワードをリセットしてください',
    'email_subject_default' => '認証コード',

    // ─────────────────────────────────────────────────────────────────────────
    // EMAIL CONTENT
    // ─────────────────────────────────────────────────────────────────────────

    'email_title_login' => ':appへようこそ',
    'email_title_verify_email' => 'メールアドレスを確認しましょう',
    'email_title_registration' => 'あと少しで完了です',
    'email_title_password_reset' => 'パスワードをリセット',
    'email_title_profile_update' => 'プロフィール変更を確認',
    'email_title_default' => '認証コード',

    'email_intro_login' => 'ログイン用のセキュアコードです。サインイン画面で入力するとアカウントへアクセスできます。',
    'email_intro_verify_email' => 'このメールアドレスがご本人のものか確認します。下のコードを入力してください。',
    'email_intro_registration' => 'ご登録ありがとうございます。下のコードを入力して登録を完了してください。',
    'email_intro_password_reset' => 'ご安心ください。下のコードを使って新しいパスワードを設定してください。',
    'email_intro_profile_update' => 'セキュリティ保護のため、プロフィール変更がご本人による操作か確認します。',
    'email_intro_default' => 'ご依頼の認証コードです。',

    'email_expires' => ':minutes分間有効です',
    'email_security_notice' => 'このメールに心当たりがない場合、対応は不要です。このメールを無視してください。アカウントは安全です。',
    'email_subcopy' => 'このコードはご本人専用です。第三者への共有はしないでください。',
    'verification_code' => '認証コード',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 1 (EMAIL ENTRY)
    // ─────────────────────────────────────────────────────────────────────────

    'step1_title' => 'アカウントにサインイン',
    'step1_subtitle' => '続行するにはメールアドレスを入力してください',
    'step1_subtitle_admin' => '管理者ポータルにサインイン',
    'step1_subtitle_partner' => 'パートナーポータルにサインイン',
    'step1_subtitle_staff' => 'スタッフポータルにサインイン',
    'step1_continue' => '続行',
    'step1_no_account' => 'アカウントをお持ちでないですか？',
    'step1_create_account' => '作成する',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 2 (METHOD SELECTION)
    // ─────────────────────────────────────────────────────────────────────────

    'step2_welcome_back' => 'おかえりなさい',
    'step2_welcome' => 'ようこそ',
    'step2_change_email' => 'メールアドレスを変更',
    'step2_enter_password' => 'パスワードを入力',
    'step2_sign_in' => 'サインイン',
    'step2_or_divider' => 'または',
    'step2_send_code' => 'ログインコードを送信',
    'step2_send_code_only' => 'ログインコードを送信',
    'step2_code_info' => '6桁のコードをメールで送信します。',
    'step2_forgot_password' => 'パスワードをお忘れですか？',
    'step2_set_password' => '代わりにパスワードを設定',
    'step2_passwordless_title' => 'パスワードレスサインイン',
    'step2_passwordless_subtitle' => '安全にアクセスできる6桁のコードをメールで送信します。',

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN FLOW - STEP 3 (OTP VERIFICATION)
    // ─────────────────────────────────────────────────────────────────────────

    'step3_title' => '認証コードを入力',
    'step3_subtitle' => '6桁のコードを次の宛先に送信しました',
    'step3_didnt_receive' => 'コードが届きませんか？',
    'step3_resend' => 'コードを再送',
    'step3_resend_in' => ':seconds秒後に再送可能',
    'step3_try_another' => '別の方法を試す',
    'step3_verifying' => '認証中...',
    'step3_code_sent' => 'コードを送信しました',
    'step3_check_email' => 'メールで認証コードをご確認ください。',
    'step3_panel_description' => 'メールに6桁のコードを送信しました。上に入力してサインインを完了してください。',

    // ─────────────────────────────────────────────────────────────────────────
    // PORTAL NAMES
    // ─────────────────────────────────────────────────────────────────────────

    'admin_portal' => '管理者ポータル',
    'partner_portal' => 'パートナーポータル',
    'staff_portal' => 'スタッフポータル',

    // ─────────────────────────────────────────────────────────────────────────
    // PIN INPUT
    // ─────────────────────────────────────────────────────────────────────────

    'pin_digit_label' => ':total桁中:position桁目',
    'pin_placeholder' => '·',

    // ─────────────────────────────────────────────────────────────────────────
    // SUCCESS MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'code_sent_success' => ':email に認証コードを送信しました',
    'verification_success' => '認証に成功しました。',
    'login_success' => 'ログインしました。',

    // ─────────────────────────────────────────────────────────────────────────
    // ERROR MESSAGES
    // ─────────────────────────────────────────────────────────────────────────

    'rate_limited' => '試行回数が多すぎます。:seconds秒お待ちください。',
    'code_expired' => 'このコードは期限切れです。新しいコードをリクエストしてください。',
    'code_invalid' => 'コードが正しくありません。残り:remaining回です。',
    'code_locked' => '失敗回数が上限に達しました。新しいコードをリクエストしてください。',
    'user_not_found' => 'このメールアドレスのアカウントは見つかりません。',
    'account_not_found_create' => ':email のアカウントが見つかりません。<a href=":register_url" class="font-semibold text-primary-600 dark:text-primary-400 hover:underline">アカウントを作成しますか？</a>',
    'user_inactive' => 'このアカウントは有効ではありません。サポートへご連絡ください。',
    'send_failed' => '認証コードの送信に失敗しました。もう一度お試しください。',
    'invalid_request' => '無効なリクエストです。もう一度お試しください。',

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRATION WITH OTP
    // ─────────────────────────────────────────────────────────────────────────

    'registration_verify_title' => 'メールアドレスを確認',
    'registration_verify_subtitle' => '6桁のコードを次の宛先に送信しました',
    'registration_start_over' => '最初からやり直す',
    'registration_almost_there' => 'あと少しです。',
    'registration_verify_panel_title' => '最後のステップです',
    'registration_verify_panel_text' => 'メールに送信した認証コードを入力して、登録を完了してください。',
    'registration_benefit_1' => 'パスワードの記憶が不要',
    'registration_benefit_2' => '安全なパスワードレスログイン',
    'registration_benefit_3' => 'プロフィールからいつでもパスワード設定可能',
    'registration_success' => 'ようこそ。アカウントを作成しました。',
    'registration_email_exists' => 'このメールアドレスは既に登録されています。ログインしてください。',
    'registration_otp_sent' => ':email に認証コードを送信しました',
    'registration_almost_done' => 'メールに送信した6桁コードを入力して、アカウント認証と登録を完了してください。',
    'verify_email_title' => 'メールアドレス確認',
    'secure_verification' => 'セキュア認証',
    'code_expires_minutes' => 'コードの有効期限: :minutes分',
    'never_share_code' => 'このコードは第三者に共有しないでください',
    'check_spam_folder' => 'メールが見つからない場合は迷惑メールフォルダをご確認ください',

    'pin_aria_label' => '認証コード',
    'pin_digit_aria' => ':total桁中:n桁目',

    // ─────────────────────────────────────────────────────────────────────────
    // PROFILE UPDATE OTP VERIFICATION
    // ─────────────────────────────────────────────────────────────────────────

    'profile_verification_title' => '本人確認',
    'profile_verification_subtitle' => '変更を保存するには認証コードが必要です',
    'profile_send_code_info' => 'ご本人確認のため、:email に6桁のコードを送信します。',
    'profile_send_code_info_generic' => 'ご本人確認のため、6桁のコードを送信します。',
    'profile_send_code' => '認証コードを送信',
    'profile_verified' => '本人確認が完了しました。変更を保存できます。',
    'profile_otp_required' => 'メールに送信したコードで本人確認を行ってください。',
    'verify_to_save' => '認証して保存',
    'identity_verified' => '本人確認済み',
    'verification_failed' => '認証に失敗しました。もう一度お試しください。',
    'code_sent_success_short' => 'コードを送信しました',
    'sending' => '送信中...',

    // ─────────────────────────────────────────────────────────────────────────
    // DEMO MODE
    // ─────────────────────────────────────────────────────────────────────────

    'demo_otp_notice' => 'デモモード: サインインにはコード :code を使用してください。',

    // ─────────────────────────────────────────────────────────────────────────
    // LOADING STATE
    // ─────────────────────────────────────────────────────────────────────────

    'please_wait' => 'しばらくお待ちください...',
];
