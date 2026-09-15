<?php

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

class UnassignTicket
{
    public function __construct(private readonly TransitionTicketStatus $transition) {}

    public function handle(Ticket $ticket, ?User $author = null): Ticket
    {
        $this->transition->handle($ticket, TicketStatus::Open, $author);

        $ticket->assignee()->dissociate();
        $ticket->assigned_at = null;
        $ticket->save();

        return $ticket;
    }
}
