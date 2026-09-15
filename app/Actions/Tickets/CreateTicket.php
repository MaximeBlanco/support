<?php

namespace App\Actions\Tickets;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Events\TicketCreated;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTicket
{
    public function handle(User $requester, string $title, string $description, TicketPriority $priority): Ticket
    {
        $ticket = DB::transaction(function () use ($requester, $title, $description, $priority): Ticket {
            $ticket = new Ticket([
                'reference' => $this->nextReference(),
                'requester_id' => $requester->getKey(),
                'title' => $title,
                'description' => $description,
                'status' => TicketStatus::Open,
                'priority' => $priority,
            ]);

            $ticket->save();

            $ticket->statusChanges()->create([
                'author_id' => $requester->getKey(),
                'from_status' => null,
                'to_status' => TicketStatus::Open,
            ]);

            return $ticket;
        });

        TicketCreated::dispatch($ticket);

        return $ticket;
    }

    private function nextReference(): string
    {
        $next = (int) Ticket::withTrashed()->max('id') + 1;

        return 'TCK-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
