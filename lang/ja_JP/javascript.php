<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JavaScript Translations
    |--------------------------------------------------------------------------
    |
    | These translations are included in a JavaScript file:
    |
    | let _lang = {
    |     ok: "OK",
    |     cancel: "Cancel",
    |     close: "Close"
    | };
    |
    | You can use these variables in JavaScript like: _lang.close
    |
    |--------------------------------------------------------------------------
    */

    'ok' => 'OK',
    'cancel' => 'キャンセル',
    'close' => '閉じる',
    'delete_confirmation_text' => ':itemを削除してもよろしいですか？この操作は取り消せません。',
    'scanner_https_camera_notification' => 'HTTPS環境かつカメラ付き端末をご利用ください。',
    'no_scanner_notification' => 'この端末はQRコード読み取りに対応していません。',

    // Flatpickr Date Picker
    'datepicker' => [
        'weekdays' => [
            'shorthand' => ['日', '月', '火', '水', '木', '金', '土'],
            'longhand' => ['日曜日', '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日'],
        ],
        'months' => [
            'shorthand' => ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
            'longhand' => ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
        ],
        'rangeSeparator' => ' から ',
        'weekAbbreviation' => '週',
        'scrollTitle' => 'スクロールで増減',
        'toggleTitle' => 'クリックで切替',
        'firstDayOfWeek' => 0, // Sunday
        'dateFormat' => 'Y年n月j日', // 2025年1月15日
    ],
];
