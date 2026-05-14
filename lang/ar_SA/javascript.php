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

    'ok' => 'موافق',
    'cancel' => 'إلغاء',
    'close' => 'إغلاق',
    'delete_confirmation_text' => 'هل أنت متأكد أنك تريد حذف :item؟ لا يمكن التراجع عن هذا الإجراء.',
    'scanner_https_camera_notification' => 'يرجى استخدام HTTPS وجهاز يحتوي على كاميرا.',
    'no_scanner_notification' => 'جهازك لا يدعم مسح رمز QR.',

    // Flatpickr Date Picker
    'datepicker' => [
        'weekdays' => [
            'shorthand' => ['أحد', 'اثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت'],
            'longhand' => ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'],
        ],
        'months' => [
            'shorthand' => ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'],
            'longhand' => ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
        ],
        'rangeSeparator' => ' إلى ',
        'weekAbbreviation' => 'أسبوع',
        'scrollTitle' => 'قم بالتمرير للزيادة',
        'toggleTitle' => 'اضغط للتبديل',
        'firstDayOfWeek' => 6, // Saturday (Arabic regions typically start week on Saturday)
        'dateFormat' => 'j F، Y', // 15 يناير، 2025
    ],
];
