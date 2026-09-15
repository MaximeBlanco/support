<?php

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

class StartTicketWork
{
    public function __construct(private readonly TransitionTicketStatus $transition) {}

    public function handle(Ticket $ticket, ?User $author = null): Ticket
    {
        return $this->transition->handle($ticket, TicketStatus::InProgress, $author);
    }
}
