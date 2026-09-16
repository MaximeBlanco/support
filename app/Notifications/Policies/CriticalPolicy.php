<?php

namespace App\Notifications\Policies;

use App\Enums\Permission;
use App\Enums\TicketPriority;
use App\Models\Builders\UserBuilder;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Channels\UrgencyChannel;
use App\Notifications\TicketNotificationPolicy;

class CriticalPolicy implements TicketNotificationPolicy
{
    public function handles(TicketPriority $priority): bool
    {
        return $priority === TicketPriority::Critical;
    }

    /**
     * @return array<int, string>
     */
    public function channels(): array
    {
        return ['mail', 'database', UrgencyChannel::class];
    }

    /**
     * A critical ticket also wakes whoever can reassign it.
     *
     * @return array<int, User>
     */
    public function additionalRecipients(Ticket $ticket): array
    {
        return User::query()
            ->withPermission(Permission::AssignTicket)
            ->when(
                $ticket->assignee_id !== null,
                fn (UserBuilder $query): UserBuilder => $query->whereKeyNot($ticket->assignee_id),
            )
            ->get()
            ->all();
    }
}
