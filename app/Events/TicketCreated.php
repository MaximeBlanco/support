<?php

namespace App\Events;

use App\Events\Concerns\BroadcastsToTicketAudience;
use App\Models\Ticket;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCreated implements ShouldBroadcast
{
    use BroadcastsToTicketAudience, Dispatchable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
    ) {}
}
