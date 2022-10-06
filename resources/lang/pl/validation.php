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

    'accepted' => 'Atrybut :attribute musi być zaakceptowany.',
    'active_url' => 'Atrybut :attribute nie jest poprawnym adresem URL.',
    'after' => 'Atrybut :attribute musi być datą po :date.',
    'alpha' => 'Atrybut :attribute może zawierać tylko litery.',
    'alpha_dash' => 'Atrybut :attribute może zawierać tylko litery, cyfry oraz myślniki.',
    'alpha_num' => 'Atrybut :attribute może zawierać tylko litery i cyfry.',
    'array' => 'Atrybut :attribute musi być tablicą.',
    'before' => 'Atrybut :attribute musi być datą przed :date.',
    'between' => [
        'numeric' => 'Atrybut :attribute musi mieć długość pomiędzy :min a :max znaków.',
        'file' => 'Atrybut :attribute musi mieć pojemność pomiędzy :min a :max kilobajtów.',
        'string' => 'Atrybut :attribute musi mieć długość pomiędzy :min a :max znaków.',
        'array' => 'Atrybut :attribute musi mieć pomiędzy :min a :max pozycji.',
    ],
    'boolean' => 'Atrybut :attribute musi mieć wartość pola true lub false.',
    'confirmed' => 'Atrybut :attribute potwierdzający nie pasuje.',
    'date' => 'Atrybut :attribute nie jest poprawną datą.',
    'date_format' => 'Atrybut :attribute nie pasuje do formatu :format.',
    'different' => 'Atrybut :attribute i :other musz być różne.',
    'digits' => 'Atrybut :attribute musi mieć :digits cyfr.',
    'digits_between' => 'Atrybut :attribute musi mieć pomiędzy :min a :max cyfr.',
    'email' => 'Atrybut :attribute musi być poprawnym adresem mailowym.',
    'exists' => 'Atrybut :attribute jest niepoprawny.',
    'filled' => 'Atrybut :attribute pola jest wymagany.',
    'image' => 'Atrybut :attribute musi być zdjęciem.',
    'in' => 'Wybrany atrybut :attribute jest niepoprawny.',
    'integer' => 'Atrybut :attribute musi być liczbą całkowitą.',
    'ip' => 'Atrybut :attribute musi być poprawnym adresem IP.',
    'json' => 'Atrybut :attribute musi być poprawnym ciągiem znaków JSON.',
    'max' => [
        'numeric' => 'Atrybut :attribute nie może być większy niż :max.',
        'file' => 'Atrybut :attribute nie może być większy niż :max kilobajtów.',
        'string' => 'Atrybut :attribute nie może być większy niż :max znaków.',
        'array' => 'Atrybut :attribute nie może być większy niż :max pozycji.',
    ],
    'mimes' => 'Atrybut :attribute musi być plikiem typu: :values.',
    'min' => [
        'numeric' => ':attribute musi mieć minimum :min.',
        'file' => ':attribute musi mieć minimum :min kilobajtów.',
        'string' => ':attribute musi mieć minimum :min znaków.',
        'array' => ':attribute musi mieć minimum :min pozycji.',
    ],
    'not_in' => 'Wybrany atrybut :attribute jest niepoprawny.',
    'numeric' => ':attribute musi być liczbą.',
    'regex' => ':attribute format jest niepoprawny.',
    'required' => 'Pole :attribute jest wymagane.',
    'required_if' => 'Pole :attribute jest wymagane jeżeli :other jest :value.',
    'required_unless' => 'Pole :attribute jest wymagane chyba, że :other jest w :values.',
    'required_with' => 'Pole :attribute jest wymagane kiedy :values jest obecne.',
    'required_with_all' => 'Pole :attribute jest wymagane kiedy :values są obecne.',
    'required_without' => 'Pole :attribute jest wymagane kiedy :values nie są obecne.',
    'required_without_all' => 'Pole :attribute jest wymagane kiedy żadne z :values nie są obecne.',
    'same' => 'Pole :attribute i :other muszą do siebie pasować.',
    'size' => [
        'numeric' => ':attribute musi mieć :size.',
        'file' => ':attribute musi mieć :size kilobajtów.',
        'string' => ':attribute musi zawierać :size znaków.',
        'array' => ':attribute musi zawierać :size pozycji.',
    ],
    'string' => 'Atrybut :attribute musi być ciągiem znaków.',
    'timezone' => 'Atrybut :attribute musi być ważną strefą.',
    'unique' => 'Atrybut :attribute jest zajęty.',
    'url' => 'Format :attribute jest niepoprawny.',

    'path' => [
        'valid' => 'Atrybut :attribute nie jest poprawny lub czytelną ścieżką.',
    ],

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
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],
];
