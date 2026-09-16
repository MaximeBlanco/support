<?php

namespace App\Events;

use App\Events\Concerns\BroadcastsToTicketAudience;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketAssigned implements ShouldBroadcast
{
    use BroadcastsToTicketAudience, Dispatchable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $technician,
        public readonly ?User $author = null,
    ) {}
}
