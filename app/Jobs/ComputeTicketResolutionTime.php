<?php

namespace App\Jobs;

use App\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ComputeTicketResolutionTime implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $ticketId) {}

    public function handle(): void
    {
        $ticket = Ticket::find($this->ticketId);

        if ($ticket === null || $ticket->resolved_at === null) {
            return;
        }

        $elapsedHours = $ticket->created_at->diffInHours($ticket->resolved_at);

        $ticket->resolved_within_target = $elapsedHours <= $ticket->priority->targetResolutionHours();
        $ticket->save();
    }
}
