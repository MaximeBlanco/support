<?php

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Events\TicketAssigned;
use App\Exceptions\IllegalTicketTransition;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssignTicket
{
    public function __construct(private readonly TransitionTicketStatus $transition) {}

    public function handle(Ticket $ticket, User $technician, ?User $author = null): Ticket
    {
        if ($ticket->status->isSettled()) {
            throw IllegalTicketTransition::between($ticket->status, TicketStatus::Assigned);
        }

        DB::transaction(function () use ($ticket, $technician): void {
            $ticket->assignee()->associate($technician);
            $ticket->assigned_at = now();
            $ticket->save();
        });

        if ($ticket->status === TicketStatus::Open) {
            $this->transition->handle($ticket, TicketStatus::Assigned, $author);
        }

        TicketAssigned::dispatch($ticket, $technician, $author);

        return $ticket;
    }
}
