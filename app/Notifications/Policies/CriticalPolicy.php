<?php

namespace App\Notifications\Policies;

use App\Enums\Permission;
use App\Enums\TicketPriority;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Channels\UrgencyChannel;
use App\Notifications\TicketNotificationPolicy;
use Illuminate\Database\Eloquent\Builder;

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
            ->whereHas('roles', fn (Builder $role): Builder => $role->whereHas(
                'permissions',
                fn (Builder $permission): Builder => $permission->where('name', Permission::AssignTicket->value),
            ))
            ->when(
                $ticket->assignee_id !== null,
                fn (Builder $query): Builder => $query->whereKeyNot($ticket->assignee_id),
            )
            ->get()
            ->all();
    }
}
