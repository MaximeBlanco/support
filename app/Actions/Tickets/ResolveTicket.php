<?php

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Events\TicketResolved;
use App\Jobs\ComputeTicketResolutionTime;
use App\Models\Ticket;
use App\Models\User;

class ResolveTicket
{
    public function __construct(private readonly TransitionTicketStatus $transition) {}

    public function handle(Ticket $ticket, ?User $author = null): Ticket
    {
        $this->transition->handle($ticket, TicketStatus::Resolved, $author);

        $ticket->resolved_at = now();
        $ticket->save();

        ComputeTicketResolutionTime::dispatch($ticket->getKey())->afterCommit();

        TicketResolved::dispatch($ticket, $author);

        return $ticket;
    }
}
