<?php

return [
    'escalate' => [
        'examined' => '{0} Aucun ticket en retard.|{1} 1 ticket en retard examiné.|[2,*] :count tickets en retard examinés.',
        'nothing' => 'Rien à escalader : tout est dans les temps.',
        'raised' => '{0} Aucune priorité relevée.|{1} 1 ticket est passé à la priorité supérieure.|[2,*] :count tickets sont passés à la priorité supérieure.',
        'capped' => '{1} 1 ticket est déjà en priorité critique : signalé mais non relevé.|[2,*] :count tickets sont déjà en priorité critique : signalés mais non relevés.',
        'dry_run' => 'Mode simulation : aucune écriture. Retirez --dry-run pour appliquer.',
    ],

    'import' => [
        'read' => '{0} Aucune ligne lue.|{1} 1 ligne lue.|[2,*] :count lignes lues.',
        'created' => '{0} Aucun ticket créé.|{1} 1 ticket créé.|[2,*] :count tickets créés.',
        'rejected' => '{0} Aucune ligne rejetée.|{1} 1 ligne rejetée.|[2,*] :count lignes rejetées.',
    ],
];
