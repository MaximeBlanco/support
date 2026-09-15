<?php

namespace App\Listeners;

use App\Events\TicketResolved;
use App\Notifications\TicketResolvedNotification;

class NotifyRequesterOfResolution
{
    public function handle(TicketResolved $event): void
    {
        $event->ticket->requester->notify(new TicketResolvedNotification($event->ticket));
    }
}
