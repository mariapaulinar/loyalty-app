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
    |     cancel: "Annuler",
    |     close: "Fermer"
    | };
    |
    | You can use these variables in JavaScript like: _lang.close
    |
    |--------------------------------------------------------------------------
    */

    'ok' => 'OK',
    'cancel' => 'Annuler',
    'close' => 'Fermer',
    'delete_confirmation_text' => 'Êtes-vous sûr de vouloir supprimer :item ? Cette action est irréversible.',
    'scanner_https_camera_notification' => 'Veuillez utiliser HTTPS et un appareil équipé d\'une caméra.',
    'no_scanner_notification' => 'Votre appareil ne prend pas en charge la lecture de codes QR.',

    // Flatpickr Date Picker
    'datepicker' => [
        'weekdays' => [
            'shorthand' => ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
            'longhand' => ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
        ],
        'months' => [
            'shorthand' => ['Janv', 'Févr', 'Mars', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'],
            'longhand' => ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
        ],
        'rangeSeparator' => ' au ',
        'weekAbbreviation' => 'Sem',
        'scrollTitle' => 'Défiler pour incrémenter',
        'toggleTitle' => 'Cliquer pour basculer',
        'firstDayOfWeek' => 1, // Lundi (Monday)
        'dateFormat' => 'j F Y', // 15 janvier 2025
    ],
];
