<?php

namespace App\Events\Concerns;

use App\Broadcasting\TicketAudience;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Sends an existing domain event to the people entitled to hear about it.
 *
 * The events themselves were written for the domain, not for the screen: this
 * only decides where they land, which is why nothing new had to be invented to
 * make the list live.
 */
trait BroadcastsToTicketAudience
{
    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return app(TicketAudience::class)->channelsFor($this->ticket);
    }

    public function broadcastAs(): string
    {
        return 'ticket.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticket->getKey(),
            'reference' => $this->ticket->reference,
        ];
    }
}
