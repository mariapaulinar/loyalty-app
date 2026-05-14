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
    'cancel' => 'Cancel',
    'close' => 'Close',
    'delete_confirmation_text' => 'Are you sure you want to delete :item? This action cannot be undone.',
    'scanner_https_camera_notification' => 'Please use HTTPS and a device with a camera.',
    'no_scanner_notification' => "Your device doesn't support QR code scanning.",

    // Flatpickr Date Picker
    'datepicker' => [
        'weekdays' => [
            'shorthand' => ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
            'longhand' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        ],
        'months' => [
            'shorthand' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'longhand' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        ],
        'rangeSeparator' => ' to ',
        'weekAbbreviation' => 'Wk',
        'scrollTitle' => 'Scroll to increment',
        'toggleTitle' => 'Click to toggle',
        'firstDayOfWeek' => 0, // Sunday
        'dateFormat' => 'F j, Y', // January 15, 2025
    ],
];
