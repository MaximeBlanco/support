<?php

namespace App\Broadcasting;

use App\Enums\Permission;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Collection;

/**
 * Who is allowed to hear that a ticket moved.
 *
 * Broadcasting on one shared channel would be five minutes of work and would
 * hand every ticket to everyone. Instead each recipient is worked out here and
 * reached on their own private channel, and the test for "may hear" is the very
 * policy that decides "may see" — so the socket can never leak what the screen
 * would have hidden.
 */
class TicketAudience
{
    /**
     * @return array<int, PrivateChannel>
     */
    public function channelsFor(Ticket $ticket): array
    {
        return $this->recipients($ticket)
            ->map(fn (User $user): PrivateChannel => new PrivateChannel('users.'.$user->getKey()))
            ->all();
    }

    /**
     * @return Collection<int, User>
     */
    public function recipients(Ticket $ticket): Collection
    {
        $watchers = User::query()->withPermission(Permission::ViewAllTickets)->get();

        $involved = User::query()
            ->whereKey(array_filter([$ticket->requester_id, $ticket->assignee_id]))
            ->get()
            ->filter(fn (User $user): bool => $user->can('view', $ticket));

        return $watchers
            ->concat($involved)
            ->unique(fn (User $user): int => $user->getKey())
            ->values();
    }
}
