<?php

namespace App\Events;

use App\Enums\TicketPriority;
use App\Models\Ticket;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketEscalated
{
    use Dispatchable, SerializesModels;

    /**
     * @param  TicketPriority|null  $to  null when the ticket was already critical
     */
    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketPriority $from,
        public readonly ?TicketPriority $to,
    ) {}
}
