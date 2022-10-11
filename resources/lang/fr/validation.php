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

    'accepted' => 'La valeur de :attribute must be accepted.',
    'active_url' => 'La valeur de :attribute n\'est pas une URL valide.',
    'after' => 'La valeur de :attribute doit être une date après le :date.',
    'alpha' => 'La valeur de :attribute ne peut contenir que des lettres.',
    'alpha_dash' => 'La valeur de :attribute ne peut contenir que des lettres, des nombres et des tirets.',
    'alpha_num' => 'La valeur de :attribute ne peut contenir que des lettres et des nombres.',
    'array' => 'La valeur de :attribute doit être un tableau.',
    'before' => 'La valeur :attribute doit être une date avant le :date.',
    'between' => [
        'numeric' => 'La valeur de :attribute doit être entre :min et :max.',
        'file' => 'La taille de :attribute doit être entre :min et :max kilobytes.',
        'string' => 'La talle de :attribute doit être entre :min et :max caractères.',
        'array' => ':attribute doit être entre :min et :max éléments.',
    ],
    'boolean' => 'La valeur du champ :attribute doit être vraie ou fausse.',
    'confirmed' => 'La valeur du champ :attribute n\'est pas confirmée.',
    'date' => 'La valeur de :attribute n\'est pas une date valide.',
    'date_format' => 'La valeur de :attribute ne correspont pas au format :format.',
    'different' => 'Les valeurs de :attribute et de :other doivent être différentes.',
    'digits' => 'La valeur de :attribute doit être de :digits chiffres.',
    'digits_between' => 'La longueure de :attribute doit être entre :min et :max chiffres.',
    'email' => 'La valeur de :attribute doit être une adresse IP valide.',
    'exists' => 'L\attribut :attribute sélectionné n\'est pas valide.',
    'filled' => 'Le champ :attribute est requis.',
    'image' => 'Le :attribute doit être une image.',
    'in' => 'La valeur sélectionnée pour :attribute n\'est pas valide.',
    'integer' => 'La valeur de :attribute doit être un entier.',
    'ip' => 'La valeur de :attribute doit être une adresse IP valide.',
    'json' => 'La valeur de :attribute doit être une chaîne de caractères JSON valide.',
    'max' => [
        'numeric' => 'La valeur de :attribute ne peut pas être plus grand que :max.',
        'file' => 'La taille de :attribute ne peut pas être plus grand que :max kilobytes.',
        'string' => 'La taille de :attribute ne peut pas être plus grand que :max caractères.',
        'array' => ':attribute ne peut contenir plus de :max éléments.',
    ],
    'mimes' => 'Le :attribute doit être un fichier de type: :values.',
    'min' => [
        'numeric' => 'La valeur de :attribute doit être au moins de :min.',
        'file' => 'La taille de :attribute doit être au moins de :min kilobytes.',
        'string' => 'La taille de :attribute doit être au moins de :min caractères.',
        'array' => ':attribute doit contenir au moins :min éléments.',
    ],
    'not_in' => 'La valeur sélectionnée pour :attribute n\'est pas valide.',
    'numeric' => 'La valeur de :attribute doit être un nombre.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'required' => 'Le champ :attribute est nécessaire.',
    'required_if' => 'Le champ de :attribute est nécessaire quand le champ :other a la valeur :value.',
    'required_unless' => 'Le champ :attribute est nécessaire tant que le champ :other n\'a pas pour valeur :values.',
    'required_with' => 'Le champ :attribute est nécessaire quand la valeur :values est présent.',
    'required_with_all' => 'Le champ :attribute est nécessaire quand toutes les valeures :values sont présentes.',
    'required_without' => 'Le champ :attribute est nécessaire quand la valeur :values est absente.',
    'required_without_all' => 'Le champ :attribute est nécessaire quand toutes les valeures :values sont absentes.',
    'same' => 'Les valeur de :attribute de :other doivent correspondre.',
    'size' => [
        'numeric' => 'La valeur de :attribute doit être :size.',
        'file' => 'La taille de :attribute doit être de :size kilobytes.',
        'string' => 'La taille de :attribute doit être de :size caractères.',
        'array' => 'Le nombre d\'élément de :attribute doit être de :size.',
    ],
    'string' => 'La valeur de :attribute doit être une chaîne de caractères.',
    'timezone' => 'La valeur de :attribute doit être une zone temporelle valide.',
    'unique' => 'La valeur de :attribute doit être unique.',
    'url' => 'Le format de :attribute n\'est pas valide.',

    'path' => [
        'valid' => 'La valeur de :attribute n\'est pas un chemin valide ou lisible.',
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
