<?php

return [
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être du texte.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'unique' => 'Cette valeur de :attribute est déjà utilisée.',
    'exists' => 'La valeur sélectionnée pour :attribute est invalide.',
    'enum' => 'La valeur sélectionnée pour :attribute est invalide.',
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'in' => 'La valeur sélectionnée pour :attribute est invalide.',
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'date' => 'Le champ :attribute doit être une date valide.',

    'min' => [
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
        'numeric' => 'Le champ :attribute doit être au minimum :min.',
        'array' => 'Le champ :attribute doit contenir au moins :min éléments.',
    ],

    'max' => [
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
        'numeric' => 'Le champ :attribute ne doit pas dépasser :max.',
        'array' => 'Le champ :attribute ne doit pas contenir plus de :max éléments.',
    ],

    'password' => [
        'letters' => 'Le champ :attribute doit contenir au moins une lettre.',
        'mixed' => 'Le champ :attribute doit contenir au moins une majuscule et une minuscule.',
        'numbers' => 'Le champ :attribute doit contenir au moins un chiffre.',
        'symbols' => 'Le champ :attribute doit contenir au moins un symbole.',
        'uncompromised' => 'Ce :attribute est apparu dans une fuite de données. Choisissez-en un autre.',
    ],

    'attributes' => [
        'name' => 'nom',
        'email' => 'adresse e-mail',
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'title' => 'objet',
        'description' => 'description',
        'priority' => 'priorité',
        'status' => 'statut',
        'body' => 'commentaire',
        'assigneeId' => 'technicien',
    ],

    'custom' => [],
];
