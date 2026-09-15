<?php

return [
    'name' => 'Support',
    'tagline' => 'Le suivi des demandes internes',

    'nav' => [
        'dashboard' => 'Tableau de bord',
        'tickets' => 'Tickets',
        'new_ticket' => 'Nouveau ticket',
        'main' => 'Navigation principale',
        'user' => 'Menu utilisateur',
        'notifications' => 'Notifications',
        'toggle_theme' => 'Changer de thème',
        'toggle_sidebar' => 'Afficher ou masquer le menu',
    ],

    'dashboard' => [
        'title' => 'Tableau de bord',
        'greeting' => 'Bonjour :name',
        'subtitle' => 'Voici l’état du support aujourd’hui.',
        'open' => 'Tickets ouverts',
        'in_progress' => 'En cours de traitement',
        'resolved_week' => 'Résolus cette semaine',
        'unassigned' => 'En attente d’assignation',
        'by_priority' => 'Répartition par priorité',
        'by_status' => 'Répartition par statut',
        'recent' => 'Derniers tickets',
        'see_all' => 'Tout voir',
        'my_queue' => 'Ma file',
    ],

    'notifications' => [
        'title' => 'Notifications',
        'empty' => 'Aucune notification.',
        'mark_all_read' => 'Tout marquer comme lu',
        'unread' => '{1} 1 non lue|[2,*] :count non lues',
    ],

    'auth' => [
        'login' => 'Connexion',
        'login_title' => 'Content de vous revoir',
        'login_subtitle' => 'Connectez-vous pour suivre vos demandes.',
        'register' => 'Créer un compte',
        'register_title' => 'Créer un compte',
        'register_subtitle' => 'Quelques secondes suffisent.',
        'logout' => 'Se déconnecter',
        'email' => 'Adresse e-mail',
        'password' => 'Mot de passe',
        'password_confirmation' => 'Confirmer le mot de passe',
        'name' => 'Nom complet',
        'remember' => 'Rester connecté',
        'no_account' => 'Pas encore de compte ?',
        'have_account' => 'Vous avez déjà un compte ?',
        'demo_hint' => 'Comptes de démonstration',
        'demo_password' => 'Mot de passe : password',
    ],

    'common' => [
        'search' => 'Rechercher',
        'loading' => 'Chargement…',
        'per_page' => 'Par page',
        'sort_by' => 'Trier par :column',
        'close' => 'Fermer',
        'confirm' => 'Confirmer',
        'yes' => 'Oui',
        'no' => 'Non',
        'none' => '—',
        'you' => 'vous',
    ],
];
