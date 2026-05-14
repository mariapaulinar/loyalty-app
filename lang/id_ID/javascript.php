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
    'cancel' => 'Batal',
    'close' => 'Tutup',
    'delete_confirmation_text' => 'Apakah Anda yakin ingin menghapus :item? Tindakan ini tidak dapat dibatalkan.',
    'scanner_https_camera_notification' => 'Harap gunakan HTTPS dan perangkat yang memiliki kamera.',
    'no_scanner_notification' => 'Perangkat Anda tidak mendukung pemindaian kode QR.',

    // Flatpickr Date Picker
    'datepicker' => [
        'weekdays' => [
            'shorthand' => ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            'longhand' => ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
        ],
        'months' => [
            'shorthand' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            'longhand' => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        ],
        'rangeSeparator' => ' sampai ',
        'weekAbbreviation' => 'Mgg',
        'scrollTitle' => 'Gulir untuk menambah',
        'toggleTitle' => 'Klik untuk beralih',
        'firstDayOfWeek' => 0, // Sunday
        'dateFormat' => 'j F Y', // 15 Januari 2025
    ],
];
