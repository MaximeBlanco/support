<?php

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Events\TicketStatusChanged;
use App\Exceptions\IllegalTicketTransition;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransitionTicketStatus
{
    public function handle(Ticket $ticket, TicketStatus $target, ?User $author = null): Ticket
    {
        $current = $ticket->status;

        if (! $current->canTransitionTo($target)) {
            throw IllegalTicketTransition::between($current, $target);
        }

        DB::transaction(function () use ($ticket, $current, $target, $author): void {
            $ticket->status = $target;
            $ticket->save();

            $ticket->statusChanges()->create([
                'author_id' => $author?->getKey(),
                'from_status' => $current,
                'to_status' => $target,
            ]);
        });

        TicketStatusChanged::dispatch($ticket, $current, $target, $author);

        return $ticket;
    }
}
