<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DemoDataSeeder extends Seeder
{
    private const TITLES = [
        'Impossible de se connecter au VPN',
        'Imprimante du 2e étage hors service',
        'Demande de licence Office',
        'Écran bleu au démarrage',
        'Accès refusé au partage comptabilité',
        'Téléphone fixe sans tonalité',
        'Lenteur anormale du poste',
        'Mot de passe expiré',
        'Demande de nouveau matériel',
        'Synchronisation des mails interrompue',
        'Badge d’accès non reconnu',
        'Erreur 500 sur l’intranet',
        'Sauvegarde nocturne en échec',
        'Casque audio non détecté',
        'Migration de boîte mail à planifier',
        'Double authentification à réinitialiser',
        'Poste à reconfigurer après déménagement',
        'Certificat SSL expiré sur le portail',
    ];

    public function run(): void
    {
        $this->accountFor(RoleName::Manager, 'Camille Moreau', 'manager@support.test');

        $technicians = $this->technicians();
        $requesters = $this->requesters();

        $sequence = 1;

        foreach (TicketStatus::cases() as $status) {
            foreach (TicketPriority::cases() as $priority) {
                $count = $status === TicketStatus::Closed ? 2 : 3;

                for ($index = 0; $index < $count; $index++) {
                    $this->makeTicket($status, $priority, $requesters->random(), $technicians->random(), $sequence++);
                }
            }
        }

        $this->command?->info(sprintf(
            '%d utilisateurs, %d tickets, %d commentaires.',
            User::count(),
            Ticket::count(),
            Comment::count(),
        ));
    }

    private function makeTicket(
        TicketStatus $status,
        TicketPriority $priority,
        User $requester,
        User $technician,
        int $sequence,
    ): void {
        $factory = Ticket::factory()->priority($priority);

        $factory = match ($status) {
            TicketStatus::Open => $factory,
            TicketStatus::Assigned => $factory->assignedTo($technician),
            TicketStatus::InProgress => $factory->inProgress($technician),
            TicketStatus::Resolved => $factory->resolved($technician),
            TicketStatus::Closed => $factory->closed($technician),
        };

        $ticket = $factory->create([
            'reference' => 'TCK-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
            'requester_id' => $requester->getKey(),
            'title' => self::TITLES[array_rand(self::TITLES)],
        ]);

        $this->recordTimeline($ticket, $requester, $technician);

        $authors = collect([$requester, $technician]);

        Comment::factory()
            ->count(rand(0, 4))
            ->sequence(fn (): array => ['author_id' => $authors->random()->getKey()])
            ->create(['ticket_id' => $ticket->getKey()]);
    }

    private function recordTimeline(Ticket $ticket, User $requester, User $technician): void
    {
        $path = match ($ticket->status) {
            TicketStatus::Open => [],
            TicketStatus::Assigned => [TicketStatus::Assigned],
            TicketStatus::InProgress => [TicketStatus::Assigned, TicketStatus::InProgress],
            TicketStatus::Resolved => [TicketStatus::Assigned, TicketStatus::InProgress, TicketStatus::Resolved],
            TicketStatus::Closed => [TicketStatus::Assigned, TicketStatus::InProgress, TicketStatus::Resolved, TicketStatus::Closed],
        };

        $moment = $ticket->created_at->copy();

        $ticket->statusChanges()->create([
            'author_id' => $requester->getKey(),
            'from_status' => null,
            'to_status' => TicketStatus::Open,
            'created_at' => $moment,
            'updated_at' => $moment,
        ]);

        $previous = TicketStatus::Open;

        foreach ($path as $step) {
            $moment = $moment->copy()->addHours(rand(1, 12));

            $ticket->statusChanges()->create([
                'author_id' => $technician->getKey(),
                'from_status' => $previous,
                'to_status' => $step,
                'created_at' => $moment,
                'updated_at' => $moment,
            ]);

            $previous = $step;
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function technicians(): Collection
    {
        return collect([
            ['Nadia Ferrand', 'nadia@support.test'],
            ['Thomas Leroy', 'thomas@support.test'],
            ['Inès Bouchard', 'ines@support.test'],
        ])->map(fn (array $profile): User => $this->accountFor(RoleName::Technician, $profile[0], $profile[1]));
    }

    /**
     * @return Collection<int, User>
     */
    private function requesters(): Collection
    {
        $named = collect([
            ['Maxime Blanco', 'maxime@support.test'],
            ['Julie Perrin', 'julie@support.test'],
            ['Karim Haddad', 'karim@support.test'],
        ])->map(fn (array $profile): User => $this->accountFor(RoleName::Requester, $profile[0], $profile[1]));

        return $named->concat(
            User::factory()->count(5)->withRole(RoleName::Requester)->create()
        );
    }

    private function accountFor(RoleName $role, string $name, string $email): User
    {
        return User::factory()->withRole($role)->create([
            'name' => $name,
            'email' => $email,
        ]);
    }
}
