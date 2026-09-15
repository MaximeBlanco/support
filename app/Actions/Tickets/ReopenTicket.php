<?php

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

class ReopenTicket
{
    public function __construct(private readonly TransitionTicketStatus $transition) {}

    public function handle(Ticket $ticket, ?User $author = null): Ticket
    {
        $this->transition->handle($ticket, TicketStatus::InProgress, $author);

        $ticket->resolved_at = null;
        $ticket->resolved_within_target = null;
        $ticket->save();

        return $ticket;
    }
}
