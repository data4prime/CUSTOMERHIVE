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

    'accepted'             => 'Il :attribute deve essere accettato.',
    'active_url'           => 'Il :attribute non è un URL valido.',
    'after'                => 'Il :attribute deve essere una data dopo il :date.',
    'after_or_equal'       => 'Il :attribute deve essere una data dopo o uguale al :date.',
    'alpha'                => 'Il :attribute può contenere solo lettere.',
    'alpha_dash'           => 'Il :attribute può contenere solo lettere, numeri e trattini.',
    'alpha_num'            => 'Il :attribute può contenere solo lettere e numeri.',
    'array'                => 'Il :attribute deve essere un array.',
    'before'               => 'Il :attribute deve essere una data prima del :date.',
    'before_or_equal'      => 'Il :attribute deve essere una data prima o uguale al :date.',
    'between'              => [
        'numeric' => 'Il :attribute deve essere compreso tra :min e :max.',
        'file'    => 'Il :attribute deve essere compreso tra :min e :max kilobyte.',
        'string'  => 'Il :attribute deve essere compreso tra :min e :max caratteri.',
        'array'   => 'Il :attribute deve avere tra :min e :max elementi.',
    ],
    'boolean'              => 'Il campo :attribute deve essere vero o falso.',
    'confirmed'            => 'La conferma di :attribute non corrisponde.',
    'date'                 => 'Il :attribute non è una data valida.',
    'date_format'          => 'Il :attribute non corrisponde al formato :format.',
    'different'            => 'Il :attribute e :other devono essere diversi.',
    'digits'               => 'Il :attribute deve essere composto da :digits cifre.',
    'digits_between'       => 'Il :attribute deve essere compreso tra :min e :max cifre.',
    'dimensions'           => 'Il :attribute ha dimensioni dell\'immagine non valide.',
    'distinct'             => 'Il campo :attribute ha un valore duplicato.',
    'email'                => 'Il :attribute deve essere un indirizzo email valido.',
    'exists'               => 'Il :attribute selezionato non è valido.',
    'file'                 => 'Il :attribute deve essere un file.',
    'filled'               => 'Il campo :attribute è obbligatorio.',
    'image'                => 'Il :attribute deve essere un\'immagine.',
    'in'                   => 'Il :attribute selezionato non è valido.',
    'in_array'             => 'Il campo :attribute non esiste in :other.',
    'integer'              => 'Il :attribute deve essere un numero intero.',
    'ip'                   => 'Il :attribute deve essere un indirizzo IP valido.',
    'json'                 => 'Il :attribute deve essere una stringa JSON valida.',
    'max'                  => [
        'numeric' => 'Il :attribute non può essere maggiore di :max.',
        'file'    => 'Il :attribute non può essere maggiore di :max kilobyte.',
        'string'  => 'Il :attribute non può essere maggiore di :max caratteri.',
        'array'   => 'Il :attribute non può avere più di :max elementi.',
    ],
    'mimes'                => 'Il :attribute deve essere un file di tipo: :values.',
    'mimetypes'            => 'Il :attribute deve essere un file di tipo: :values.',
    'min'                  => [
        'numeric' => 'Il :attribute deve essere almeno :min.',
        'file'    => 'Il :attribute deve essere almeno di :min kilobyte.',
        'string'  => 'Il :attribute deve contenere almeno :min caratteri.',
        'array'   => 'Il :attribute deve avere almeno :min elementi.',
    ],
    'not_in'               => 'Il :attribute selezionato non è valido.',
    'numeric'              => 'Il :attribute deve essere un numero.',
    'present'              => 'Il campo :attribute deve essere presente.',
    'regex'                => 'Il formato di :attribute non è valido.',
    'required'             => 'Il campo :attribute è obbligatorio.',
    'required_if'          => 'Il campo :attribute è obbligatorio quando :other è :value.',
    'required_unless'      => 'Il campo :attribute è obbligatorio a meno che :other non sia in :values.',
    'required_with'        => 'Il campo :attribute è obbligatorio quando :values è presente.',
    'required_with_all'    => 'Il campo :attribute è obbligatorio quando :values è presente.',
    'required_without'     => 'Il campo :attribute è obbligatorio quando :values non è presente.',
    'required_without_all' => 'Il campo :attribute è obbligatorio quando nessuno di :values è presente.',
    'same'                 => 'Il :attribute e :other devono corrispondere.',
    'size'                 => [
        'numeric' => 'Il :attribute deve essere :size.',
        'file'    => 'Il :attribute deve essere di :size kilobyte.',
        'string'  => 'Il :attribute deve contenere :size caratteri.',
        'array'   => 'Il :attribute deve contenere :size elementi.',
    ],
    'string'               => 'Il :attribute deve essere una stringa.',
    'timezone'             => 'Il :attribute deve essere una zona valida.',
    'unique'               => 'Il :attribute è già stato preso.',
    'uploaded'             => 'Il :attribute non è riuscito a caricarsi.',
    'url'                  => 'Il formato di :attribute non è valido.',


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
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],

];
