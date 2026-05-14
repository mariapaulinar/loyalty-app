<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Pole :attribute musi zostać zaakceptowane.',
    'accepted_if' => 'Pole :attribute musi zostać zaakceptowane, gdy :other ma wartość :value.',
    'active_url' => 'Pole :attribute musi być poprawnym adresem URL.',
    'after' => 'Pole :attribute musi zawierać datę późniejszą niż :date.',
    'after_or_equal' => 'Pole :attribute musi zawierać datę późniejszą lub równą :date.',
    'alpha' => 'Pole :attribute może zawierać tylko litery.',
    'alpha_dash' => 'Pole :attribute może zawierać tylko litery, cyfry, myślniki i podkreślenia.',
    'alpha_num' => 'Pole :attribute może zawierać tylko litery i cyfry.',
    'array' => 'Pole :attribute musi być tablicą.',
    'ascii' => 'Pole :attribute może zawierać tylko jednobajtowe znaki alfanumeryczne i symbole.',
    'before' => 'Pole :attribute musi zawierać datę wcześniejszą niż :date.',
    'before_or_equal' => 'Pole :attribute musi zawierać datę wcześniejszą lub równą :date.',
    'between' => [
        'array' => 'Pole :attribute musi zawierać od :min do :max elementów.',
        'file' => 'Pole :attribute musi mieć od :min do :max kilobajtów.',
        'numeric' => 'Pole :attribute musi mieć wartość od :min do :max.',
        'string' => 'Pole :attribute musi mieć od :min do :max znaków.',
    ],
    'boolean' => 'Pole :attribute musi mieć wartość prawda albo fałsz.',
    'confirmed' => 'Potwierdzenie pola :attribute nie jest zgodne.',
    'current_password' => 'Hasło jest nieprawidłowe.',
    'date' => 'Pole :attribute musi być poprawną datą.',
    'date_equals' => 'Pole :attribute musi zawierać datę równą :date.',
    'date_format' => 'Pole :attribute nie jest zgodne z formatem :format.',
    'decimal' => 'Pole :attribute musi mieć :decimal miejsc po przecinku.',
    'declined' => 'Pole :attribute musi zostać odrzucone.',
    'declined_if' => 'Pole :attribute musi zostać odrzucone, gdy :other ma wartość :value.',
    'different' => 'Pola :attribute i :other muszą się różnić.',
    'digits' => 'Pole :attribute musi mieć :digits cyfr.',
    'digits_between' => 'Pole :attribute musi mieć od :min do :max cyfr.',
    'dimensions' => 'Pole :attribute ma nieprawidłowe wymiary obrazu.',
    'distinct' => 'Pole :attribute zawiera zduplikowaną wartość.',
    'doesnt_end_with' => 'Pole :attribute nie może kończyć się jednym z następujących: :values.',
    'doesnt_start_with' => 'Pole :attribute nie może zaczynać się jednym z następujących: :values.',
    'email' => 'Pole :attribute musi być poprawnym adresem e-mail.',
    'ends_with' => 'Pole :attribute musi kończyć się jednym z następujących: :values.',
    'enum' => 'Wybrana wartość pola :attribute jest nieprawidłowa.',
    'exists' => 'Wybrana wartość pola :attribute jest nieprawidłowa.',
    'file' => 'Pole :attribute musi być plikiem.',
    'filled' => 'Pole :attribute jest wymagane.',
    'gt' => [
        'array' => 'Pole :attribute musi zawierać więcej niż :value elementów.',
        'file' => 'Pole :attribute musi być większe niż :value kilobajtów.',
        'numeric' => 'Pole :attribute musi być większe niż :value.',
        'string' => 'Pole :attribute musi mieć więcej niż :value znaków.',
    ],
    'gte' => [
        'array' => 'Pole :attribute musi zawierać :value elementów lub więcej.',
        'file' => 'Pole :attribute musi być większe lub równe :value kilobajtów.',
        'numeric' => 'Pole :attribute musi być większe lub równe :value.',
        'string' => 'Pole :attribute musi mieć co najmniej :value znaków.',
    ],
    'image' => 'Pole :attribute musi być obrazem.',
    'in' => 'Wybrana wartość pola :attribute jest nieprawidłowa.',
    'in_array' => 'Pole :attribute nie występuje w :other.',
    'integer' => 'Pole :attribute musi być liczbą całkowitą.',
    'ip' => 'Pole :attribute musi być poprawnym adresem IP.',
    'ipv4' => 'Pole :attribute musi być poprawnym adresem IPv4.',
    'ipv6' => 'Pole :attribute musi być poprawnym adresem IPv6.',
    'json' => 'Pole :attribute musi być poprawnym ciągiem JSON.',
    'lowercase' => 'Pole :attribute musi być zapisane małymi literami.',
    'lt' => [
        'array' => 'Pole :attribute musi zawierać mniej niż :value elementów.',
        'file' => 'Pole :attribute musi być mniejsze niż :value kilobajtów.',
        'numeric' => 'Pole :attribute musi być mniejsze niż :value.',
        'string' => 'Pole :attribute musi mieć mniej niż :value znaków.',
    ],
    'lte' => [
        'array' => 'Pole :attribute nie może zawierać więcej niż :value elementów.',
        'file' => 'Pole :attribute musi być mniejsze lub równe :value kilobajtów.',
        'numeric' => 'Pole :attribute musi być mniejsze lub równe :value.',
        'string' => 'Pole :attribute musi mieć co najwyżej :value znaków.',
    ],
    'mac_address' => 'Pole :attribute musi być poprawnym adresem MAC.',
    'max' => [
        'array' => 'Pole :attribute nie może zawierać więcej niż :max elementów.',
        'file' => 'Pole :attribute nie może być większe niż :max kilobajtów.',
        'numeric' => 'Pole :attribute nie może być większe niż :max.',
        'string' => 'Pole :attribute nie może mieć więcej niż :max znaków.',
    ],
    'max_digits' => 'Pole :attribute nie może mieć więcej niż :max cyfr.',
    'mimes' => 'Pole :attribute musi być plikiem typu: :values.',
    'mimetypes' => 'Pole :attribute musi być plikiem typu: :values.',
    'min' => [
        'array' => 'Pole :attribute musi zawierać co najmniej :min elementów.',
        'file' => 'Pole :attribute musi mieć co najmniej :min kilobajtów.',
        'numeric' => 'Pole :attribute musi być co najmniej :min.',
        'string' => 'Pole :attribute musi mieć co najmniej :min znaków.',
    ],
    'min_digits' => 'Pole :attribute musi mieć co najmniej :min cyfr.',
    'missing' => 'Pole :attribute musi być pominięte.',
    'missing_if' => 'Pole :attribute musi być pominięte, gdy :other ma wartość :value.',
    'missing_unless' => 'Pole :attribute musi być pominięte, chyba że :other ma wartość :value.',
    'missing_with' => 'Pole :attribute musi być pominięte, gdy obecne jest :values.',
    'missing_with_all' => 'Pole :attribute musi być pominięte, gdy obecne są :values.',
    'multiple_of' => 'Pole :attribute musi być wielokrotnością :value.',
    'not_in' => 'Wybrana wartość pola :attribute jest nieprawidłowa.',
    'not_regex' => 'Format pola :attribute jest nieprawidłowy.',
    'numeric' => 'Pole :attribute musi być liczbą.',
    'password' => [
        'letters' => 'Pole :attribute musi zawierać co najmniej jedną literę.',
        'mixed' => 'Pole :attribute musi zawierać co najmniej jedną wielką i jedną małą literę.',
        'numbers' => 'Pole :attribute musi zawierać co najmniej jedną cyfrę.',
        'symbols' => 'Pole :attribute musi zawierać co najmniej jeden symbol.',
        'uncompromised' => 'Podane :attribute pojawiło się w wycieku danych. Wybierz inne :attribute.',
    ],
    'present' => 'Pole :attribute musi występować.',
    'prohibited' => 'Pole :attribute jest niedozwolone.',
    'prohibited_if' => 'Pole :attribute jest niedozwolone, gdy :other ma wartość :value.',
    'prohibited_unless' => 'Pole :attribute jest niedozwolone, chyba że :other znajduje się w :values.',
    'prohibits' => 'Pole :attribute wyklucza obecność :other.',
    'regex' => 'Format pola :attribute jest nieprawidłowy.',
    'required' => 'Pole :attribute jest wymagane.',
    'required_array_keys' => 'Pole :attribute musi zawierać wpisy dla: :values.',
    'required_if' => 'Pole :attribute jest wymagane, gdy :other ma wartość :value.',
    'required_if_accepted' => 'Pole :attribute jest wymagane, gdy :other zostało zaakceptowane.',
    'required_unless' => 'Pole :attribute jest wymagane, chyba że :other znajduje się w :values.',
    'required_with' => 'Pole :attribute jest wymagane, gdy obecne jest :values.',
    'required_with_all' => 'Pole :attribute jest wymagane, gdy obecne są :values.',
    'required_without' => 'Pole :attribute jest wymagane, gdy :values nie występuje.',
    'required_without_all' => 'Pole :attribute jest wymagane, gdy żadne z :values nie występuje.',
    'same' => 'Pola :attribute i :other muszą być zgodne.',
    'size' => [
        'array' => 'Pole :attribute musi zawierać :size elementów.',
        'file' => 'Pole :attribute musi mieć :size kilobajtów.',
        'numeric' => 'Pole :attribute musi mieć wartość :size.',
        'string' => 'Pole :attribute musi mieć :size znaków.',
    ],
    'starts_with' => 'Pole :attribute musi zaczynać się jednym z następujących: :values',
    'string' => 'Pole :attribute musi być tekstem.',
    'timezone' => 'Pole :attribute musi być poprawną strefą czasową.',
    'ulid' => 'Pole :attribute musi być poprawnym ULID.',
    'unique' => 'Wartość pola :attribute jest już zajęta.',
    'uploaded' => 'Nie udało się przesłać pliku :attribute.',
    'uppercase' => 'Pole :attribute musi być zapisane wielkimi literami.',
    'url' => 'Format pola :attribute jest nieprawidłowy.',
    'uuid' => 'Pole :attribute musi być poprawnym UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'address' => 'adres',
        'age' => 'wiek',
        'amount' => 'kwota',
        'area' => 'obszar',
        'available' => 'dostępność',
        'birthday' => 'urodziny',
        'body' => 'treść',
        'city' => 'miasto',
        'content' => 'zawartość',
        'country' => 'kraj',
        'created_at' => 'data utworzenia',
        'creator' => 'autor',
        'current_password' => 'aktualne hasło',
        'date' => 'data',
        'date_of_birth' => 'data urodzenia',
        'day' => 'dzień',
        'deleted_at' => 'data usunięcia',
        'description' => 'opis',
        'district' => 'dzielnica',
        'duration' => 'czas trwania',
        'email' => 'adres e-mail',
        'excerpt' => 'zajawka',
        'filter' => 'filtr',
        'first_name' => 'imię',
        'gender' => 'płeć',
        'group' => 'grupa',
        'hour' => 'godzina',
        'image' => 'obraz',
        'last_name' => 'nazwisko',
        'lesson' => 'lekcja',
        'line_address_1' => 'adres linia 1',
        'line_address_2' => 'adres linia 2',
        'message' => 'wiadomość',
        'middle_name' => 'drugie imię',
        'minute' => 'minuta',
        'mobile' => 'telefon komórkowy',
        'month' => 'miesiąc',
        'name' => 'nazwa',
        'national_code' => 'kod krajowy',
        'number' => 'numer',
        'password' => 'hasło',
        'password_confirmation' => 'potwierdzenie hasła',
        'phone' => 'telefon',
        'photo' => 'zdjęcie',
        'postal_code' => 'kod pocztowy',
        'price' => 'cena',
        'province' => 'województwo',
        'recaptcha_response_field' => 'pole odpowiedzi recaptcha',
        'remember' => 'zapamiętaj',
        'restored_at' => 'data przywrócenia',
        'result_text_under_image' => 'tekst pod obrazem',
        'role' => 'rola',
        'second' => 'sekunda',
        'sex' => 'płeć',
        'short_text' => 'krótki tekst',
        'size' => 'rozmiar',
        'state' => 'stan',
        'street' => 'ulica',
        'student' => 'uczeń',
        'subject' => 'temat',
        'teacher' => 'nauczyciel',
        'terms' => 'warunki',
        'test_description' => 'opis testu',
        'test_locale' => 'lokalizacja testu',
        'test_name' => 'nazwa testu',
        'text' => 'tekst',
        'time' => 'czas',
        'title' => 'tytuł',
        'updated_at' => 'data aktualizacji',
        'username' => 'nazwa użytkownika',
        'year' => 'rok',
    ],

];
