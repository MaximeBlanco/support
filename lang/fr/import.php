<?php

return [
    'title' => 'Importer des tickets',
    'subtitle' => 'Déposez un fichier CSV : chaque ligne devient un ticket ouvert.',

    'field' => 'Fichier CSV',
    'submit' => 'Lancer l’import',
    'queued' => 'L’import est lancé. Vous recevrez le rapport dès qu’il est terminé.',

    'format' => [
        'title' => 'Format attendu',
        'description' => 'Une ligne d’en-tête, puis une ligne par ticket. Les colonnes peuvent être dans n’importe quel ordre.',
        'download' => 'Télécharger un modèle',
    ],

    'columns' => [
        'email' => 'Adresse e-mail du demandeur, qui doit déjà exister',
        'title' => 'Objet du ticket, de 5 à 150 caractères',
        'description' => 'Description, au moins 10 caractères',
        'priority' => 'low, normal, high ou critical',
    ],

    'errors' => [
        'unknown_requester' => 'Aucun utilisateur ne correspond à l’adresse :email.',
        'file' => 'Le fichier doit être un CSV de 5 Mo maximum.',
    ],

    'notification' => [
        'subject' => 'Rapport d’import de tickets',
        'action' => 'Voir les tickets',
        'inbox' => 'Import terminé : :reference tickets créés.',
        'rejection' => 'Ligne :line — :reason',
        'failed_subject' => 'L’import de tickets a échoué',
        'failed_line' => 'Aucun ticket n’a été créé : le fichier n’a pas pu être traité.',
        'failed_inbox' => 'L’import de tickets a échoué.',
    ],
];
