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

    'installation' => 'インストール',
    'install' => 'インストール',
    'install_script' => 'スクリプトをインストール',
    'server_requirements' => 'サーバー要件',
    'requirements' => '要件',
    'server_requirements_text' => '以下のチェックは、このスクリプトがサーバー上で動作するかを確認するためのものです。ただし、完全な互換性を保証するものではありません。',
    'resolve_missing_requirements' => '不足している要件を解消してから進んでください。',
    'next' => '次へ',
    'prev' => '前へ',
    'configuration' => '設定',
    'confirm' => '確認',
    'app' => 'アプリ',
    'name' => '名前',
    'email' => 'メール',
    'optional' => '任意',
    '_optional_' => '（任意）',
    'optional_email_config' => '下記設定は今はスキップできます。後でWebルートの .env ファイルで設定できます。※メール機能を使うには設定が必要です。',
    'logo' => 'ロゴ',
    'logo_dark' => 'ロゴ（ダークモード）',
    'user' => 'ユーザー',
    'email_address' => 'メールアドレス',
    'time_zone' => 'タイムゾーン',
    'password' => 'パスワード',
    'confirm_password' => 'パスワード確認',
    'passwords_must_match' => 'パスワードが一致している必要があります。',
    'email_address_app' => 'アプリのメール送信に使用するメールアドレス',
    'email_address_name_app' => '送信者名',
    'admin_login' => '管理者ログイン',
    'download_log' => 'ログファイルをダウンロード',
    'refresh_page' => 'このページを再読み込みして、もう一度お試しください',
    'after_installation' => 'インストール完了後、先ほどの管理者ログイン情報で :admin_url の管理ダッシュボードにアクセスしてください。',
    'install_error' => 'サーバーからエラーが返されました。詳細はログファイル（/storage/logs）を確認してください。',
    'database_info' => 'SQLiteは高速で、95%のユーザーに適しています。1日の利用者数が多い場合は、MySQLまたはMariaDBをご検討ください。',
    'install_acknowledge' => '本ソフトウェアをインストールすることで、NowSquareが本ソフトウェアの利用に起因する問題について責任を負わないことに同意したものとみなされます。すべてのソフトウェアには不具合が含まれる可能性があります。問題が発生した場合は、メールまたはサポートチケットでご連絡ください。迅速に対応します。',

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    */

    'email_settings' => 'メール配信',
    'email_critical_title' => 'メール設定は必須です',
    'email_critical_description' => 'ユーザーはログイン時にワンタイムパスワード（OTP）をメールで受け取ります。メールが正しく送信できないと、管理者を含め誰もシステムにアクセスできません。',
    'email_why_matters' => '重要な理由',
    'email_otp_explanation' => '本システムはパスワードレス認証です。パスワードの代わりに、ログイン時に毎回メールでセキュアなコードを受け取ります。シンプルで安全な方式です。',

    'mail_driver' => 'メール送信方法',
    'mail_driver_help' => '顧客へのメール配信に使用するサービスを選択してください。',

    // Driver descriptions
    'driver_smtp' => 'SMTPサーバー',
    'driver_smtp_desc' => '任意のメールサーバーに接続できます。Gmail、Outlook、ホスティング事業者、各種SMTPサービスに対応します。',
    'driver_smtp_best_for' => '最適: ほとんどのユーザー、ホスティング環境',

    'driver_mailgun' => 'Mailgun',
    'driver_mailgun_desc' => 'Mailchimp提供のプロ向けメール配信サービスです。高い信頼性と拡張性、詳細な分析機能があります。',
    'driver_mailgun_best_for' => '最適: 成長中のビジネス、大量配信',

    'driver_ses' => 'Amazon SES',
    'driver_ses_desc' => 'AWSの大規模かつ低コストなメール配信です。高い到達率と料金効率が特長です。',
    'driver_ses_best_for' => '最適: AWS利用者、大規模運用',

    'driver_postmark' => 'Postmark',
    'driver_postmark_desc' => 'トランザクションメール専用に設計されています。業界トップクラスの配信速度です。',
    'driver_postmark_best_for' => '最適: 速度重視のアプリ',

    'driver_resend' => 'Resend',
    'driver_resend_desc' => '開発者向けのモダンなメールAPIです。シンプルで信頼性が高く、DXに優れます。',
    'driver_resend_best_for' => '最適: 開発者中心のチーム',

    'driver_sendmail' => 'Sendmail',
    'driver_sendmail_desc' => 'サーバー内蔵のメールシステムを使用します。外部サービスは不要です。',
    'driver_sendmail_best_for' => '最適: シンプル構成、Linuxサーバー',

    'driver_mailpit' => 'Mailpit（テスト）',
    'driver_mailpit_desc' => '開発用にメールをローカルで受信します。実際のメールは送信されません。',
    'driver_mailpit_best_for' => '最適: ローカル開発のみ',

    'driver_log' => 'ログファイル（開発）',
    'driver_log_desc' => '送信せずにメール内容をログへ出力します。初期テストに最適です。',
    'driver_log_best_for' => '最適: 簡易テスト、デバッグ',

    // SMTP Fields
    'smtp_host' => 'SMTPサーバー',
    'smtp_host_placeholder' => 'smtp.example.com',
    'smtp_host_help' => 'メールサーバーのアドレス',

    'smtp_port' => 'ポート',
    'smtp_port_help' => '一般的なポート: 587（TLS）、465（SSL）、25（暗号化なし）',

    'smtp_username' => 'ユーザー名',
    'smtp_username_placeholder' => 'your-email@example.com',
    'smtp_username_help' => '通常はメールアドレス全体を指定します',

    'smtp_password' => 'パスワード',
    'smtp_password_placeholder' => 'メールのパスワードまたはアプリパスワード',
    'smtp_password_help' => 'Gmail/Googleの場合はアプリパスワードを使用してください',

    'smtp_encryption' => 'セキュリティ',
    'smtp_encryption_help' => 'ほとんどのプロバイダーではTLS推奨です',
    'smtp_encryption_tls' => 'TLS（推奨）',
    'smtp_encryption_ssl' => 'SSL',
    'smtp_encryption_none' => 'なし（非推奨）',

    // Provider-specific
    'mailgun_domain' => 'Mailgunドメイン',
    'mailgun_domain_placeholder' => 'mg.yourdomain.com',
    'mailgun_domain_help' => 'Mailgunで認証済みの送信ドメイン',

    'mailgun_secret' => 'APIキー',
    'mailgun_secret_placeholder' => 'key-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'mailgun_secret_help' => 'Mailgun → Settings → API Keys で確認できます',

    'mailgun_endpoint' => 'リージョン',
    'mailgun_endpoint_us' => '米国（api.mailgun.net）',
    'mailgun_endpoint_eu' => 'EU（api.eu.mailgun.net）',

    'ses_key' => 'AWS Access Key ID',
    'ses_key_placeholder' => 'AKIAIOSFODNN7EXAMPLE',
    'ses_key_help' => 'AWS IAMの認証情報で確認できます',

    'ses_secret' => 'AWS Secret Access Key',
    'ses_secret_placeholder' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'ses_secret_help' => '安全に保管し、共有しないでください',

    'ses_region' => 'AWSリージョン',
    'ses_region_help' => 'SESを設定したリージョン',

    'postmark_token' => 'サーバーAPIトークン',
    'postmark_token_placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    'postmark_token_help' => 'Postmark → Server → API Tokens で確認できます',

    'resend_key' => 'APIキー',
    'resend_key_placeholder' => 're_xxxxxxxxxxxxxxxxxxxxxxxxxx',
    'resend_key_help' => 'Resend Dashboard → API Keys で確認できます',

    // From address
    'mail_from_address' => '送信元メールアドレス',
    'mail_from_address_placeholder' => 'noreply@yourdomain.com',
    'mail_from_address_help' => '受信者にはこの送信者アドレスが表示されます',

    'mail_from_name' => '送信者名',
    'mail_from_name_placeholder' => 'My Company',
    'mail_from_name_help' => '受信者に表示される表示名',

    // Test email
    'test_email' => 'テストメールを送信',
    'test_email_sending' => '送信中...',
    'test_email_success' => 'テストメールを送信しました。受信トレイをご確認ください。',
    'test_email_failed' => '送信に失敗しました。設定をご確認ください。',
    'test_email_check_spam' => '見当たらない場合は迷惑メールフォルダもご確認ください。',

    // Common provider help
    'gmail_help_title' => 'Gmailをご利用ですか？',
    'gmail_help_text' => 'Googleアカウント設定でアプリパスワードを作成してください。通常のパスワードは利用できません。',
    'gmail_help_link' => 'アプリパスワードの作成方法',

    'provider_setup_guide' => '設定ガイド',
    'need_help' => 'お困りですか？',
    'skip_for_now' => '後で設定する',
    'skip_warning' => '注意: メール未設定ではログインコードを送信できません。後で .env ファイルで設定できます。',

    // Validation
    'email_config_incomplete' => '必要なメール設定をすべて入力してください。',
];
