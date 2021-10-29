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

    'accepted' => 'Il :attribute deve essere accettato.',
    'active_url' => 'Il :attribute non è un URL valido.',
    'after' => 'Il :attribute deve essere una data successiva a :date.',
    'alpha' => 'Il :attribute può contenere solo lettere.',
    'alpha_dash' => 'Il :attribute può contenere solo lettere, numeri e trattini.',
    'alpha_num' => 'Il :attribute può contenere solo lettere e numeri.',
    'array' => 'Il :attribute deve essere un array.',
    'before' => 'Il :attribute deve essere una data precedente a :date.',
    'between' => [
        'numeric' => 'Il :attribute deve essere compreso tra :min e :max.',
        'file' => 'Il :attribute deve essere compreso tra :min e :max kilobyte.',
        'string' => 'Il :attribute deve essere compreso tra :min e :max caratteri.',
        'array' => 'Il :attribute deve essere compreso tra :min e :max elementi.',
    ],
    'boolean' => 'Il campo :attribute deve essere vero o falso.',
    'confirmed' => 'La conferma di :attribute non corrisponde.',
    'date' => 'Il :attribute non è una data valida.',
    'date_format' => 'Il :attribute non corrisponde al formato :format.',
    'different' => 'Il :attribute e :other devono essere differenti.',
    'digits' => 'Il :attribute deve essere :digits cifre.',
    'digits_between' => 'Il :attribute deve essere compreso tra :min e :max cifre.',
    'email' => 'Il :attribute deve essere un indirizzo e-mail valido.',
    'exists' => 'Il :attribute selezionato non è valido.',
    'filled' => 'Il campo :attribute è richiesto.',
    'image' => 'Il :attribute deve essere una immagine.',
    'in' => 'Il :attribute selezionato non è valido.',
    'integer' => 'Il :attribute deve essere un intero.',
    'ip' => 'Il :attribute deve essere un indirizzo IP valido.',
    'json' => 'Il :attribute deve essere una stringa JSON valida.',
    'max' => [
        'numeric' => 'Il :attribute non può essere maggiore di :max.',
        'file' => 'Il :attribute non può essere maggiore di :max kilobyte.',
        'string' => 'Il :attribute non può essere maggiore di :max caratteri.',
        'array' => 'Il :attribute non può avere più di :max elemento.',
    ],
    'mimes' => 'Il :attribute deve essere un file di tipo: :values.',
    'min' => [
        'numeric' => 'Il :attribute deve essere almeno :min.',
        'file' => 'Il :attribute deve essere almeno :min kilobyte.',
        'string' => 'Il :attribute deve essere almeno :min caratteri.',
        'array' => 'Il :attribute must have almeno :min elementi.',
    ],
    'not_in' => 'Il :attribute selezionato non è valido.',
    'numeric' => 'Il :attribute deve essere un numero.',
    'regex' => 'Il formato di :attribute non è valido.',
    'required' => 'Il campo :attribute è richiesto.',
    'required_if' => 'Il campo :attribute è richiesto quando :other è :value.',
    'required_unless' => 'Il campo :attribute è richiesto a meno che :other è in :values.',
    'required_with' => 'Il campo :attribute è richiesto quando :values è presente.',
    'required_with_all' => 'Il campo :attribute è richiesto quando :values sono presenti.',
    'required_without' => 'Il campo :attribute è richiesto quando :values non sono presenti.',
    'required_without_all' => 'Il campo :attribute è richiesto quando nessun :values è presente.',
    'same' => 'Il :attribute e :other devono corrispondere.',
    'size' => [
        'numeric' => 'Il :attribute deve essere :size.',
        'file' => 'Il :attribute deve essere :size kilobyte.',
        'string' => 'Il :attribute deve essere :size caratteri.',
        'array' => 'Il :attribute must contain :size elementi.',
    ],
    'string' => 'Il :attribute deve essere una stringa.',
    'timezone' => 'Il :attribute deve essere una zona valida.',
    'unique' => 'Il :attribute è già stato preso.',
    'url' => 'Il formato :attribute non è valido.',

    'path' => [
        'valid' => 'Il :attribute non è un percorso valido o leggibile.',
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
