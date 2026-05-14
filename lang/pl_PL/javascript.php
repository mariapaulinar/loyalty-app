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
    'cancel' => 'Anuluj',
    'close' => 'Zamknij',
    'delete_confirmation_text' => 'Czy na pewno usunąć :item? Tej operacji nie można cofnąć.',
    'scanner_https_camera_notification' => 'Użyj HTTPS oraz urządzenia z aparatem.',
    'no_scanner_notification' => 'Urządzenie nie obsługuje skanowania kodów QR.',

    // Flatpickr Date Picker
    'datepicker' => [
        'weekdays' => [
            'shorthand' => ['Nd', 'Pn', 'Wt', 'Śr', 'Cz', 'Pt', 'Sb'],
            'longhand' => ['Niedziela', 'Poniedziałek', 'Wtorek', 'Środa', 'Czwartek', 'Piątek', 'Sobota'],
        ],
        'months' => [
            'shorthand' => ['Sty', 'Lut', 'Mar', 'Kwi', 'Maj', 'Cze', 'Lip', 'Sie', 'Wrz', 'Paź', 'Lis', 'Gru'],
            'longhand' => ['Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'],
        ],
        'rangeSeparator' => ' do ',
        'weekAbbreviation' => 'Tydz.',
        'scrollTitle' => 'Przewiń, aby zwiększyć',
        'toggleTitle' => 'Kliknij, aby przełączyć',
        'firstDayOfWeek' => 1, // Monday
        'dateFormat' => 'j F Y', // 15 stycznia 2025
    ],
];
