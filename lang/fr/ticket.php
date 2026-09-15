<?php

return [
    'status' => [
        'open' => 'Ouvert',
        'assigned' => 'Assigné',
        'in_progress' => 'En cours',
        'resolved' => 'Résolu',
        'closed' => 'Clôturé',
    ],

    'priority' => [
        'low' => 'Basse',
        'normal' => 'Normale',
        'high' => 'Haute',
        'critical' => 'Critique',
    ],

    'fields' => [
        'reference' => 'Référence',
        'title' => 'Objet',
        'description' => 'Description',
        'status' => 'Statut',
        'priority' => 'Priorité',
        'requester' => 'Demandeur',
        'assignee' => 'Technicien',
        'comments' => 'Commentaires',
        'created_at' => 'Créé le',
        'updated_at' => 'Mis à jour le',
        'resolved_at' => 'Résolu le',
        'closed_at' => 'Clôturé le',
        'target' => 'Délai cible',
    ],

    'placeholders' => [
        'title' => 'Résumez le problème en une phrase',
        'description' => 'Décrivez ce qui se passe, ce que vous avez déjà essayé, et depuis quand.',
        'comment' => 'Ajouter un commentaire…',
        'search' => 'Rechercher une référence, un objet…',
    ],

    'actions' => [
        'create' => 'Nouveau ticket',
        'edit' => 'Modifier',
        'save' => 'Enregistrer',
        'cancel' => 'Annuler',
        'assign' => 'Assigner',
        'assign_to_me' => 'Me l’assigner',
        'reassign' => 'Réassigner',
        'unassign' => 'Désassigner',
        'start' => 'Démarrer le traitement',
        'resolve' => 'Marquer comme résolu',
        'reopen' => 'Rouvrir',
        'close' => 'Clôturer',
        'comment' => 'Commenter',
        'back' => 'Retour aux tickets',
    ],

    'filters' => [
        'all_statuses' => 'Tous les statuts',
        'all_priorities' => 'Toutes les priorités',
        'all_assignees' => 'Tous les techniciens',
        'unassigned' => 'Non assigné',
        'reset' => 'Réinitialiser les filtres',
        'mine' => 'Seulement les miens',
    ],

    'empty' => [
        'title' => 'Aucun ticket',
        'description' => 'Aucun ticket ne correspond à votre recherche. Essayez d’élargir les filtres.',
        'create' => 'Aucun ticket pour le moment. Créez le premier.',
        'comments' => 'Aucun commentaire pour l’instant.',
    ],

    'timeline' => [
        'title' => 'Historique',
        'created' => ':author a créé le ticket',
        'changed' => ':author a fait passer le ticket de :from à :to',
        'changed_unknown' => 'Le ticket est passé de :from à :to',
        'system' => 'Le système',
    ],

    'sla' => [
        'target' => 'Objectif : :hours h',
        'within' => 'Dans les délais',
        'breached' => 'Hors délai',
        'remaining' => 'Il reste :time',
        'overdue' => 'En retard de :time',
    ],

    'errors' => [
        'illegal_transition' => 'Transition refusée : un ticket « :from » ne peut pas passer à « :to ».',
        'no_technician' => 'Sélectionnez un technicien avant d’assigner le ticket.',
    ],

    'messages' => [
        'created' => 'Le ticket :reference a été créé.',
        'updated' => 'Le ticket a été mis à jour.',
        'assigned' => 'Le ticket a été assigné à :name.',
        'unassigned' => 'Le ticket a été désassigné.',
        'started' => 'Le traitement a démarré.',
        'resolved' => 'Le ticket est marqué comme résolu.',
        'reopened' => 'Le ticket a été rouvert.',
        'closed' => 'Le ticket a été clôturé.',
        'commented' => 'Votre commentaire a été publié.',
    ],

    'counters' => [
        'results' => '{0} Aucun résultat|{1} 1 ticket|[2,*] :count tickets',
        'comments' => '{0} Aucun commentaire|{1} 1 commentaire|[2,*] :count commentaires',
    ],
];
