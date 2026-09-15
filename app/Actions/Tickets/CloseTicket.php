<?php

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

class CloseTicket
{
    public function __construct(private readonly TransitionTicketStatus $transition) {}

    public function handle(Ticket $ticket, ?User $author = null): Ticket
    {
        $this->transition->handle($ticket, TicketStatus::Closed, $author);

        $ticket->closed_at = now();
        $ticket->save();

        return $ticket;
    }
}
