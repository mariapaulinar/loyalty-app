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
    |     cancel: "Annulla",
    |     close: "Chiudi"
    | };
    |
    | You can use these variables in JavaScript like: _lang.close
    |
    |--------------------------------------------------------------------------
    */

    'ok' => 'OK',
    'cancel' => 'Annulla',
    'close' => 'Chiudi',
    'delete_confirmation_text' => 'Sei sicuro di voler eliminare :item? Questa azione è irreversibile.',
    'scanner_https_camera_notification' => 'Si prega di utilizzare HTTPS e un dispositivo dotato di fotocamera.',
    'no_scanner_notification' => 'Il tuo dispositivo non supporta la lettura di codici QR.',

    // Flatpickr Date Picker
    'datepicker' => [
        'weekdays' => [
            'shorthand' => ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'],
            'longhand' => ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'],
        ],
        'months' => [
            'shorthand' => ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
            'longhand' => ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'],
        ],
        'rangeSeparator' => ' al ',
        'weekAbbreviation' => 'Sett',
        'scrollTitle' => 'Scorri per incrementare',
        'toggleTitle' => 'Clicca per cambiare',
        'firstDayOfWeek' => 1, // Lunedì (Monday)
        'dateFormat' => 'j F Y', // 15 gennaio 2025
    ],
];
