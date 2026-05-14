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
    'cancel' => 'Cancelar',
    'close' => 'Fechar',
    'delete_confirmation_text' => 'Tem certeza de que deseja excluir :item? Essa ação não pode ser desfeita.',
    'scanner_https_camera_notification' => 'Por favor, use HTTPS e um dispositivo com câmera.',
    'no_scanner_notification' => 'Seu dispositivo não suporta a leitura de códigos QR.',

    // Flatpickr Date Picker
    'datepicker' => [
        'weekdays' => [
            'shorthand' => ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
            'longhand' => ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'],
        ],
        'months' => [
            'shorthand' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
            'longhand' => ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
        ],
        'rangeSeparator' => ' até ',
        'weekAbbreviation' => 'Sem',
        'scrollTitle' => 'Role para aumentar',
        'toggleTitle' => 'Clique para alternar',
        'firstDayOfWeek' => 0, // Sunday
        'dateFormat' => 'j \\d\\e F \\d\\e Y', // 15 de Janeiro de 2025
    ],
];
