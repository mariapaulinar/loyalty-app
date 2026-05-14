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

    'ok' => 'Tamam',
    'cancel' => 'İptal',
    'close' => 'Kapat',
    'delete_confirmation_text' => ':item silinsin mi? Bu işlem geri alınamaz.',
    'scanner_https_camera_notification' => 'Lütfen HTTPS ve kameralı bir cihaz kullanın.',
    'no_scanner_notification' => 'Cihazınız QR kod taramayı desteklemiyor.',

    // Flatpickr Date Picker
    'datepicker' => [
        'weekdays' => [
            'shorthand' => ['Paz', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt'],
            'longhand' => ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'],
        ],
        'months' => [
            'shorthand' => ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'],
            'longhand' => ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'],
        ],
        'rangeSeparator' => ' - ',
        'weekAbbreviation' => 'Hf',
        'scrollTitle' => 'Artırmak için kaydırın',
        'toggleTitle' => 'Aç/kapat için tıklayın',
        'firstDayOfWeek' => 1, // Pazartesi
        'dateFormat' => 'j F Y', // 15 Ocak 2025
    ],
];
