<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\TicketStatusChange;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * A support desk as it looks on an ordinary Tuesday.
 *
 * Everything is written by hand rather than generated: a reviewer opening the
 * application should read plausible tickets with plausible conversations, not
 * lorem ipsum. The dataset is deterministic, so two people looking at the demo
 * are looking at the same thing.
 */
class DemoDataSeeder extends Seeder
{
    private const PASSWORD = 'password';

    /**
     * @var array<string, array{name: string, email: string, role: RoleName}>
     */
    private const PEOPLE = [
        'camille' => ['name' => 'Camille Moreau', 'email' => 'manager@support.test', 'role' => RoleName::Manager],
        'sofia' => ['name' => 'Sofia Nunes', 'email' => 'sofia@support.test', 'role' => RoleName::Manager],

        'nadia' => ['name' => 'Nadia Ferrand', 'email' => 'nadia@support.test', 'role' => RoleName::Technician],
        'thomas' => ['name' => 'Thomas Leroy', 'email' => 'thomas@support.test', 'role' => RoleName::Technician],
        'ines' => ['name' => 'Inès Bouchard', 'email' => 'ines@support.test', 'role' => RoleName::Technician],

        'maxime' => ['name' => 'Maxime Blanco', 'email' => 'maxime@support.test', 'role' => RoleName::Requester],
        'julie' => ['name' => 'Julie Perrin', 'email' => 'julie@support.test', 'role' => RoleName::Requester],
        'karim' => ['name' => 'Karim Haddad', 'email' => 'karim@support.test', 'role' => RoleName::Requester],
        'lea' => ['name' => 'Léa Fontaine', 'email' => 'lea@support.test', 'role' => RoleName::Requester],
        'antoine' => ['name' => 'Antoine Girard', 'email' => 'antoine@support.test', 'role' => RoleName::Requester],
        'fatou' => ['name' => 'Fatou Diallo', 'email' => 'fatou@support.test', 'role' => RoleName::Requester],
    ];

    public function run(): void
    {
        $people = $this->createPeople();
        $reference = 1;

        foreach ($this->tickets() as $definition) {
            $this->createTicket($definition, $people, $reference++);
        }

        $this->command?->info(sprintf(
            '%d comptes, %d tickets, %d commentaires, %d changements de statut.',
            User::count(),
            Ticket::count(),
            Comment::count(),
            TicketStatusChange::count(),
        ));
        $this->command?->info('Mot de passe commun : '.self::PASSWORD);
    }

    /**
     * @return Collection<string, User>
     */
    private function createPeople(): Collection
    {
        return collect(self::PEOPLE)->map(fn (array $person): User => tap(
            User::query()->create([
                'name' => $person['name'],
                'email' => $person['email'],
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ]),
            fn (User $user) => $user->roles()->attach(
                \App\Models\Role::query()->where('name', $person['role'])->sole()
            ),
        ));
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  Collection<string, User>  $people
     */
    private function createTicket(array $definition, Collection $people, int $sequence): void
    {
        $requester = $people[$definition['requester']];
        $technician = isset($definition['technician']) ? $people[$definition['technician']] : null;
        $status = $definition['status'];
        $openedAt = now()->subHours($definition['age']);

        $ticket = new Ticket([
            'reference' => 'TCK-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
            'requester_id' => $requester->getKey(),
            'title' => $definition['title'],
            'description' => $definition['description'],
            'status' => $status,
            'priority' => $definition['priority'],
        ]);

        $ticket->created_at = $openedAt;
        $ticket->updated_at = $openedAt;
        $ticket->save();

        $moment = $this->writeTimeline($ticket, $status, $requester, $technician, $openedAt);

        if ($technician !== null && $status !== TicketStatus::Open) {
            $ticket->assignee_id = $technician->getKey();
            $ticket->assigned_at = $openedAt->copy()->addHours(1);
        }

        if ($status === TicketStatus::Resolved || $status === TicketStatus::Closed) {
            $ticket->resolved_at = $moment->copy()->subHours(1);
            $ticket->resolved_within_target = $definition['within_target'] ?? true;
        }

        if ($status === TicketStatus::Closed) {
            $ticket->closed_at = $moment;
        }

        $ticket->updated_at = $moment;
        $ticket->saveQuietly();

        $this->writeComments($ticket, $definition['comments'] ?? [], $people, $openedAt);
    }

    /**
     * @param  Collection<string, User>  $people
     */
    private function writeTimeline(
        Ticket $ticket,
        TicketStatus $status,
        User $requester,
        ?User $technician,
        Carbon $openedAt,
    ): Carbon {
        $path = match ($status) {
            TicketStatus::Open => [],
            TicketStatus::Assigned => [TicketStatus::Assigned],
            TicketStatus::InProgress => [TicketStatus::Assigned, TicketStatus::InProgress],
            TicketStatus::Resolved => [TicketStatus::Assigned, TicketStatus::InProgress, TicketStatus::Resolved],
            TicketStatus::Closed => [TicketStatus::Assigned, TicketStatus::InProgress, TicketStatus::Resolved, TicketStatus::Closed],
        };

        $moment = $openedAt->copy();

        $ticket->statusChanges()->create([
            'author_id' => $requester->getKey(),
            'from_status' => null,
            'to_status' => TicketStatus::Open,
            'created_at' => $moment,
            'updated_at' => $moment,
        ]);

        $previous = TicketStatus::Open;

        foreach ($path as $index => $step) {
            $moment = $moment->copy()->addHours(2 + $index * 3);

            $ticket->statusChanges()->create([
                'author_id' => $technician?->getKey(),
                'from_status' => $previous,
                'to_status' => $step,
                'created_at' => $moment,
                'updated_at' => $moment,
            ]);

            $previous = $step;
        }

        return $moment;
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $comments
     * @param  Collection<string, User>  $people
     */
    private function writeComments(Ticket $ticket, array $comments, Collection $people, Carbon $openedAt): void
    {
        foreach ($comments as $index => [$author, $body]) {
            $writtenAt = $openedAt->copy()->addHours(1 + $index * 4);

            $comment = $ticket->comments()->create([
                'author_id' => $people[$author]->getKey(),
                'body' => $body,
            ]);

            $comment->created_at = $writtenAt;
            $comment->updated_at = $writtenAt;
            $comment->saveQuietly();
        }
    }

    /**
     * The demo desk, ticket by ticket.
     *
     * `age` is how many hours ago the ticket was opened, which is what makes some
     * of them visibly late against the target their priority promises.
     *
     * @return array<int, array<string, mixed>>
     */
    private function tickets(): array
    {
        return [
            // --- Critiques ---
            [
                'title' => 'Le serveur de fichiers comptabilité est inaccessible',
                'description' => "Depuis 8h15, plus personne du service comptabilité n'accède au partage réseau. L'explorateur affiche « chemin réseau introuvable » sur les cinq postes du plateau. La clôture mensuelle est en cours, c'est bloquant pour six personnes.",
                'priority' => TicketPriority::Critical,
                'status' => TicketStatus::InProgress,
                'requester' => 'julie',
                'technician' => 'nadia',
                'age' => 3,
                'comments' => [
                    ['nadia', "Je prends. Le serveur répond au ping mais le service SMB est tombé. Je redémarre le service, ne touchez à rien pendant deux minutes."],
                    ['julie', "Reçu, on patiente. Merci d'aller vite, on a la clôture ce soir."],
                    ['nadia', "Service relancé, le partage remonte chez moi. Vous pouvez retester ?"],
                ],
            ],
            [
                'title' => 'Aucun poste ne démarre sur le plateau commercial',
                'description' => "Ce matin, douze postes du plateau commercial affichent un écran noir avec « no bootable device ». Ils étaient tous éteints hier soir normalement. Une mise à jour a peut-être été poussée pendant la nuit.",
                'priority' => TicketPriority::Critical,
                'status' => TicketStatus::Resolved,
                'requester' => 'karim',
                'technician' => 'thomas',
                'age' => 30,
                'within_target' => false,
                'comments' => [
                    ['thomas', "Confirmé sur place : la mise à jour du BIOS poussée cette nuit a changé l'ordre de démarrage. Je repasse les douze machines en UEFI first."],
                    ['karim', "Combien de temps ? On a une réunion clients à 14h."],
                    ['thomas', "Cinq minutes par poste. Les six premiers sont déjà repartis, vous aurez tout avant midi."],
                    ['thomas', "Les douze postes redémarrent. J'ai aussi bloqué la tâche de mise à jour BIOS en attendant qu'on la teste correctement."],
                ],
            ],
            [
                'title' => 'Fuite d’eau au-dessus de la baie réseau du 3e',
                'description' => "Une canalisation goutte juste au-dessus de la baie réseau du 3e étage. J'ai mis un seau mais ça tombe à côté des onduleurs. Je préfère prévenir avant que ça touche l'électrique.",
                'priority' => TicketPriority::Critical,
                'status' => TicketStatus::Assigned,
                'requester' => 'lea',
                'technician' => 'ines',
                'age' => 1,
                'comments' => [
                    ['ines', "Je monte tout de suite avec une bâche. Ne coupez rien vous-même, je m'occupe de l'onduleur."],
                ],
            ],
            [
                'title' => 'Le site vitrine renvoie une erreur 500',
                'description' => "Le site public affiche une page blanche avec « 500 Internal Server Error » depuis une dizaine de minutes. Un client vient de m'appeler pour le signaler.",
                'priority' => TicketPriority::Critical,
                'status' => TicketStatus::Closed,
                'requester' => 'antoine',
                'technician' => 'nadia',
                'age' => 96,
                'within_target' => true,
                'comments' => [
                    ['nadia', "Certificat SSL expiré côté reverse proxy, le renouvellement automatique avait échoué. Je renouvelle à la main."],
                    ['antoine', "C'est revenu, merci !"],
                    ['nadia', "J'ai aussi remis une alerte à J-15 sur l'expiration pour qu'on ne le découvre plus par un client."],
                ],
            ],

            // --- Hautes ---
            [
                'title' => 'Impossible de se connecter au VPN depuis la mise à jour',
                'description' => "Le client VPN renvoie « erreur 812 : la connexion a été refusée » depuis la mise à jour de mardi. J'ai réinstallé le client, redémarré, changé de réseau : même résultat. Je suis en télétravail jusqu'à vendredi, je n'accède plus à rien.",
                'priority' => TicketPriority::High,
                'status' => TicketStatus::InProgress,
                'requester' => 'maxime',
                'technician' => 'thomas',
                'age' => 6,
                'comments' => [
                    ['thomas', "L'erreur 812 côté serveur veut dire que la stratégie d'accès réseau a refusé le compte. Vous êtes bien dans le groupe VPN ? Je vérifie."],
                    ['maxime', "Je n'ai pas changé de poste ni de compte depuis six mois, si ça peut aider."],
                    ['thomas', "Trouvé : la mise à jour a réinitialisé l'appartenance au groupe pour les comptes créés avant 2024. Vous n'êtes pas le seul. Je corrige la stratégie pour tout le lot."],
                ],
            ],
            [
                'title' => 'La sauvegarde nocturne échoue depuis trois nuits',
                'description' => "Le rapport de sauvegarde arrive tous les matins avec « échec : espace insuffisant sur la cible ». Trois nuits d'affilée maintenant. Je ne sais pas si les données sont encore protégées.",
                'priority' => TicketPriority::High,
                'status' => TicketStatus::Assigned,
                'requester' => 'julie',
                'technician' => 'ines',
                'age' => 14,
                'comments' => [
                    ['ines', "La cible est pleine à 98 %. Je purge les points de restauration au-delà de la rétention et je relance une sauvegarde complète ce soir."],
                ],
            ],
            [
                'title' => 'Mot de passe expiré, plus d’accès à la messagerie',
                'description' => "Mon mot de passe a expiré pendant mes congés et la page de renouvellement refuse mes anciens identifiants. Je n'ai plus accès à ma boîte mail depuis ce matin.",
                'priority' => TicketPriority::High,
                'status' => TicketStatus::Closed,
                'requester' => 'fatou',
                'technician' => 'nadia',
                'age' => 120,
                'within_target' => true,
                'comments' => [
                    ['nadia', "Je réinitialise, vous recevrez un mot de passe temporaire sur votre numéro professionnel. À changer à la première connexion."],
                    ['fatou', "Reçu et changé, tout est revenu. Merci."],
                ],
            ],
            [
                'title' => 'Double authentification à réinitialiser après changement de téléphone',
                'description' => "J'ai changé de téléphone et je n'ai pas pensé à exporter l'application d'authentification. Je suis bloqué à l'étape du code sur tous les outils internes.",
                'priority' => TicketPriority::High,
                'status' => TicketStatus::Resolved,
                'requester' => 'antoine',
                'technician' => 'thomas',
                'age' => 20,
                'within_target' => false,
                'comments' => [
                    ['thomas', "Je dois vérifier votre identité avant de réinitialiser. Passez me voir au 2e avec votre badge."],
                    ['antoine', "J'arrive dans dix minutes."],
                    ['thomas', "Réinitialisé, pensez à enregistrer les codes de secours cette fois."],
                ],
            ],
            [
                'title' => 'Lenteurs importantes sur l’outil de facturation',
                'description' => "Depuis lundi, chaque enregistrement de facture prend une quinzaine de secondes contre moins d'une avant. Sur cent factures par jour, ça devient invivable.",
                'priority' => TicketPriority::High,
                'status' => TicketStatus::Open,
                'requester' => 'julie',
                'age' => 9,
            ],
            [
                'title' => 'L’imprimante du 2e refuse tous les travaux',
                'description' => "L'imprimante du 2e étage accepte les travaux puis les supprime de la file sans rien imprimer. Le panneau n'affiche aucune erreur, les bacs sont pleins.",
                'priority' => TicketPriority::High,
                'status' => TicketStatus::InProgress,
                'requester' => 'lea',
                'technician' => 'ines',
                'age' => 5,
                'comments' => [
                    ['ines', "Le spouleur d'impression est bloqué côté serveur. Je le redémarre et je vide la file."],
                ],
            ],

            // --- Normales ---
            [
                'title' => 'Demande de licence Office pour un nouvel arrivant',
                'description' => "Nous accueillons un alternant lundi prochain au service marketing. Il lui faut une licence Office complète, un accès au partage marketing et une boîte mail nominative.",
                'priority' => TicketPriority::Normal,
                'status' => TicketStatus::Assigned,
                'requester' => 'karim',
                'technician' => 'nadia',
                'age' => 16,
                'comments' => [
                    ['nadia', "Licence réservée. Il me faut son nom complet et sa date d'arrivée exacte pour créer le compte."],
                ],
            ],
            [
                'title' => 'Écran secondaire non détecté après changement de station d’accueil',
                'description' => "Ma nouvelle station d'accueil ne pilote qu'un seul écran alors que l'ancienne en gérait deux. J'ai testé les deux ports, inversé les câbles, rien n'y fait.",
                'priority' => TicketPriority::Normal,
                'status' => TicketStatus::Resolved,
                'requester' => 'maxime',
                'technician' => 'ines',
                'age' => 40,
                'within_target' => true,
                'comments' => [
                    ['ines', "Cette station a besoin du pilote DisplayLink pour le second écran. Je vous l'installe à distance."],
                    ['maxime', "Les deux écrans fonctionnent, parfait."],
                ],
            ],
            [
                'title' => 'Badge d’accès non reconnu au portique du parking',
                'description' => "Mon badge ouvre les portes de l'immeuble mais pas le portique du parking. Il fonctionnait encore la semaine dernière.",
                'priority' => TicketPriority::Normal,
                'status' => TicketStatus::Closed,
                'requester' => 'fatou',
                'technician' => 'thomas',
                'age' => 200,
                'within_target' => true,
                'comments' => [
                    ['thomas', "Le droit parking avait sauté lors de la synchronisation annuelle. Je l'ai remis, testez demain matin."],
                    ['fatou', "Ça passe, merci."],
                ],
            ],
            [
                'title' => 'Synchronisation des mails interrompue sur mobile',
                'description' => "Ma boîte mail ne se synchronise plus sur le téléphone professionnel depuis environ deux jours. Sur l'ordinateur tout fonctionne normalement.",
                'priority' => TicketPriority::Normal,
                'status' => TicketStatus::InProgress,
                'requester' => 'lea',
                'technician' => 'nadia',
                'age' => 12,
                'comments' => [
                    ['nadia', "Le profil de messagerie du téléphone a expiré. Je pousse un nouveau profil, acceptez la notification qui va arriver."],
                ],
            ],
            [
                'title' => 'Demande d’accès au dossier partagé des ressources humaines',
                'description' => "Dans le cadre de ma prise de poste, j'ai besoin d'un accès en lecture au dossier partagé RH. Ma responsable, Camille Moreau, est en copie et valide la demande.",
                'priority' => TicketPriority::Normal,
                'status' => TicketStatus::Open,
                'requester' => 'antoine',
                'age' => 4,
            ],
            [
                'title' => 'Casque audio non détecté en visioconférence',
                'description' => "Mon casque USB est bien reconnu par le système mais n'apparaît pas dans la liste des périphériques de l'outil de visioconférence. J'utilise le micro du portable en attendant.",
                'priority' => TicketPriority::Normal,
                'status' => TicketStatus::Assigned,
                'requester' => 'karim',
                'technician' => 'thomas',
                'age' => 26,
                'comments' => [
                    ['thomas', "Ce modèle a besoin d'une autorisation micro au niveau du navigateur. Je vous envoie la marche à suivre."],
                ],
            ],
            [
                'title' => 'Migration de boîte mail à planifier pour le service achats',
                'description' => "Le service achats doit basculer sur la nouvelle plateforme de messagerie. Nous sommes huit, il faut planifier ça en dehors des heures de facturation, idéalement un vendredi après-midi.",
                'priority' => TicketPriority::Normal,
                'status' => TicketStatus::Open,
                'requester' => 'julie',
                'age' => 30,
            ],
            [
                'title' => 'Le scanner du 1er envoie des PDF vides',
                'description' => "Le scanner produit des PDF de la bonne taille mais entièrement blancs. Testé avec plusieurs documents et deux destinataires différents.",
                'priority' => TicketPriority::Normal,
                'status' => TicketStatus::Resolved,
                'requester' => 'maxime',
                'technician' => 'ines',
                'age' => 50,
                'within_target' => false,
                'comments' => [
                    ['ines', "La vitre était propre mais la lampe du chariot est morte. Pièce commandée."],
                    ['ines', "Lampe remplacée ce matin, les scans sortent correctement."],
                ],
            ],
            [
                'title' => 'Poste à reconfigurer après déménagement de bureau',
                'description' => "J'ai déménagé du 1er au 3e étage. Le poste ne retrouve ni l'imprimante d'étage, ni le lecteur réseau du service.",
                'priority' => TicketPriority::Normal,
                'status' => TicketStatus::Closed,
                'requester' => 'fatou',
                'technician' => 'nadia',
                'age' => 260,
                'within_target' => true,
                'comments' => [
                    ['nadia', "Je repousse la configuration du 3e sur votre poste, comptez cinq minutes."],
                    ['fatou', "Tout est en place. Merci."],
                ],
            ],

            // --- Basses ---
            [
                'title' => 'Demande d’un second écran pour le poste d’accueil',
                'description' => "Le poste d'accueil gagnerait beaucoup à avoir un second écran : on jongle en permanence entre le planning et la messagerie. Pas urgent, mais ça ferait gagner du temps tous les jours.",
                'priority' => TicketPriority::Low,
                'status' => TicketStatus::Open,
                'requester' => 'lea',
                'age' => 80,
            ],
            [
                'title' => 'Fond d’écran d’entreprise à mettre à jour',
                'description' => "Le fond d'écran déployé sur les postes porte encore l'ancien logo. Ce n'est pas gênant mais ça se voit en réunion client quand on partage l'écran.",
                'priority' => TicketPriority::Low,
                'status' => TicketStatus::Assigned,
                'requester' => 'karim',
                'technician' => 'thomas',
                'age' => 100,
                'comments' => [
                    ['thomas', "Bien vu. Je récupère le visuel à jour auprès de la communication et je le déploie au prochain redémarrage."],
                ],
            ],
            [
                'title' => 'Clavier dont deux touches accrochent',
                'description' => "Les touches E et R de mon clavier accrochent depuis un café renversé la semaine dernière. Ça reste utilisable, mais un remplacement serait bienvenu.",
                'priority' => TicketPriority::Low,
                'status' => TicketStatus::Resolved,
                'requester' => 'antoine',
                'technician' => 'ines',
                'age' => 150,
                'within_target' => true,
                'comments' => [
                    ['ines', "Un clavier de remplacement vous attend à l'accueil du 2e."],
                    ['antoine', "Récupéré, merci."],
                ],
            ],
            [
                'title' => 'Demande de formation sur le nouvel intranet',
                'description' => "Plusieurs personnes du service ne trouvent pas les notes de frais sur le nouvel intranet. Une session de trente minutes suffirait probablement.",
                'priority' => TicketPriority::Low,
                'status' => TicketStatus::Closed,
                'requester' => 'julie',
                'technician' => 'nadia',
                'age' => 400,
                'within_target' => true,
                'comments' => [
                    ['nadia', "Session calée jeudi 14h en salle Erable, j'ai invité tout le service."],
                    ['julie', "Parfait, c'était très clair pour tout le monde."],
                ],
            ],
            [
                'title' => 'Corbeille du bureau à distance jamais vidée',
                'description' => "La corbeille du serveur de bureau à distance contient des fichiers vieux de plusieurs mois. Ça ne dérange personne mais ça prend de la place.",
                'priority' => TicketPriority::Low,
                'status' => TicketStatus::InProgress,
                'requester' => 'maxime',
                'technician' => 'thomas',
                'age' => 60,
                'comments' => [
                    ['thomas', "Je mets en place une purge automatique à 90 jours plutôt que de le refaire à la main tous les six mois."],
                ],
            ],
            [
                'title' => 'Demande de tapis de souris ergonomique',
                'description' => "J'ai des douleurs au poignet en fin de journée. La médecine du travail recommande un tapis avec repose-poignet.",
                'priority' => TicketPriority::Low,
                'status' => TicketStatus::Open,
                'requester' => 'fatou',
                'age' => 200,
            ],
        ];
    }
}
